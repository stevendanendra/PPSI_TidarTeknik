/**
 * smart-parser.js — Heuristic Excel Parser for Tidar Teknik
 * Stable Version 3.0 - Multi-doc sheet safe, Excel Date cell fix
 *
 * Changelog v3.0:
 * - cellDates:true → Excel Date serial cells become JS Date objects
 * - Date objects normalised to Indonesian text BEFORE parsing
 * - extractDate() returns null (not today's date) when no date found
 * - All parsers scan every row for date, not just rows containing "MALANG"
 * - PO supplier detection only fires AFTER nomor is found (prevents
 *   picking up Kepada Yth. from a side-by-side letter in the same sheet)
 * - Stricter ISO date regex (20xx-MM-DD) to avoid nomor PO false-positives
 */

window.SmartParser = {
    async parseFile(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                try {
                    const data = new Uint8Array(e.target.result);
                    // cellDates:true → Excel date serials become JS Date objects
                    const workbook = XLSX.read(data, { type: 'array', cellDates: true });
                    const results = { po: [], nota: [], penawaran: [] };

                    workbook.SheetNames.forEach(sheetName => {
                        try {
                            const sheet = workbook.Sheets[sheetName];
                            const rawRows = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: '' });
                            if (rawRows.length === 0) return;

                            // Normalise JS Date objects → Indonesian text ("12 November 2025")
                            // so extractDate() can parse them with existing logic
                            const rows = rawRows.map(row => row.map(cell => {
                                if (cell instanceof Date && !isNaN(cell)) {
                                    const bulan = ['Januari','Februari','Maret','April','Mei','Juni',
                                                   'Juli','Agustus','September','Oktober','November','Desember'];
                                    return `${cell.getDate()} ${bulan[cell.getMonth()]} ${cell.getFullYear()}`;
                                }
                                return cell;
                            }));
                            if (rows.length === 0) return;

                            const docType = this.detectDocType(rows, sheetName);
                            if (!docType) return;

                            const extracted = this.extractData(docType, rows);
                            if (extracted) {
                                extracted.id = this.uid();
                                results[docType].push(extracted);
                            }
                        } catch (sheetErr) {
                            // Sheet ini error tapi sheet lainnya tetap diproses
                            console.warn(`[SmartParser] Sheet "${sheetName}" dilewati karena error:`, sheetErr.message);
                        }
                    });
                    resolve(results);
                } catch (err) { reject(err); }
            };
            reader.onerror = reject;
            reader.readAsArrayBuffer(file);
        });
    },

    uid() {
        return Math.random().toString(36).substring(2, 11) + Date.now().toString(36);
    },

    detectDocType(rows, sheetName) {
        const content = rows.flat().join(' ').toUpperCase().replace(/\s+/g, ' ');
        const sn = sheetName.toUpperCase();

        if (content.includes('SURAT PENAWARAN') || content.includes('PENAWARAN HARGA') || sn.includes('PENAWARAN') || sn.includes('SPW')) {
            return 'penawaran';
        }
        if (sn.includes('PURCHASE ORDER') || sn.includes(' PO') || content.includes('PURCHASE ORDER')) {
            return 'po';
        }
        if (content.includes('NOMOR NOTA') || content.includes('INVOICE') || content.includes('SURAT JALAN')) {
            if (content.includes('PENAWARAN')) return 'penawaran';
            return 'nota';
        }
        if (content.includes('NAMA BARANG') && content.includes('HARGA SATUAN')) {
            return 'penawaran';
        }
        if (content.includes('KEPADA YTH') && content.includes('QTY')) {
            return 'nota';
        }
        return null;
    },

    extractData(type, rows) {
        if (type === 'po') return this.parsePO(rows);
        if (type === 'nota') return this.parseNota(rows);
        if (type === 'penawaran') return this.parsePenawaran(rows);
        return null;
    },

    parsePO(rows) {
        const data = { id: this.uid(), nomor: '', tanggal: '', supplier: '', items: [], status: 'Belum Bayar', total: 0 };
        let tableStartRow = -1;

        rows.forEach((row, rowIndex) => {
            const line = row.join(' ').toUpperCase();

            // 1. Nomor PO
            if ((line.includes('NO.') || line.includes('PO NO') || line.includes('NUMBER')) && !data.nomor) {
                data.nomor = this.valAfter(row, 'NO') || this.valAfter(row, 'NUMBER');
            }

            // 2. Tanggal — scan setiap baris (handles Excel Date cells di mana saja)
            //    Hanya ambil tanggal setelah nomor ditemukan, ATAU dari baris yang punya "MALANG"
            //    Ini cegah tanggal dari surat sampingan (kanan sheet) diambil duluan
            if (!data.tanggal) {
                const shouldTry = data.nomor || line.includes('MALANG');
                if (shouldTry) {
                    const d = this.extractDate(row.join(' '));
                    if (d) data.tanggal = d;
                }
            }

            // 3. Supplier — hanya setelah nomor ditemukan (hindari "Kepada Yth" dari surat di sisi kanan)
            if (data.nomor && !data.supplier && (line.includes('KEPADA') || line.includes('SUPPLIER'))) {
                const idx = row.findIndex(c => String(c || '').toUpperCase().includes('YTH') || String(c || '').toUpperCase().includes('SUPPLIER'));
                const candidate = this.extractCustomer(rows, rowIndex, idx);
                // Tolak jika isinya "di Tempat" atau terlalu pendek (kemungkinan surat sampingan)
                if (candidate && candidate.length > 4 && !candidate.toUpperCase().includes('DI TEMPAT')) {
                    data.supplier = candidate;
                }
            }

            // 4. Tabel item
            const hasDesc = line.includes('DESCRIPTION') || line.includes('DESKRIPSI');
            if (hasDesc && (line.includes('QTY') || line.includes('JUMLAH'))) tableStartRow = rowIndex;
        });

        // Fallback: tanggal dari baris mana saja jika belum ketemu
        if (!data.tanggal) {
            for (const row of rows) {
                const d = this.extractDate(row.join(' '));
                if (d) { data.tanggal = d; break; }
            }
        }

        if (tableStartRow !== -1) {
            data.items = this.extractItems(rows, tableStartRow);
            const sum = this.extractSummary(rows, tableStartRow + data.items.length);
            Object.assign(data, sum);
            if (data.total === 0) data.total = data.items.reduce((s, i) => s + i.amount, 0);
        }
        return data;
    },

    parseNota(rows) {
        const data = { id: this.uid(), nomor: '', tanggal: '', customer: '', items: [], status: 'Belum Bayar', total: 0 };
        let tableStartRow = -1;

        rows.forEach((row, rowIndex) => {
            const line = row.join(' ').toUpperCase();
            if ((line.includes('NO :') || line.includes('NOMOR :') || line.includes('NO.')) && !data.nomor) {
                data.nomor = this.valAfter(row, 'NO') || this.valAfter(row, 'NOMOR');
            }
            // Scan setiap baris untuk tanggal
            if (!data.tanggal) {
                const d = this.extractDate(row.join(' '));
                if (d) data.tanggal = d;
            }
            if (!data.customer && (line.includes('KEPADA') || line.includes('YTH'))) {
                const idx = row.findIndex(c => String(c || '').toUpperCase().includes('YTH') || String(c || '').toUpperCase().includes('KEPADA'));
                const candidate = this.extractCustomer(rows, rowIndex, idx);
                if (candidate && candidate.length > 4 && !candidate.toUpperCase().includes('DI TEMPAT')) {
                    data.customer = candidate;
                }
            }
            const hasDesc = line.includes('DESCRIPTION') || line.includes('DESKRIPSI') || line.includes('NAMA BARANG');
            if (hasDesc && (line.includes('QTY') || line.includes('JUMLAH'))) tableStartRow = rowIndex;
        });

        if (tableStartRow !== -1) {
            data.items = this.extractItems(rows, tableStartRow);
            const sum = this.extractSummary(rows, tableStartRow + data.items.length);
            Object.assign(data, sum);
            if (data.total === 0) data.total = data.items.reduce((s, i) => s + i.amount, 0);
        }
        return data;
    },

    parsePenawaran(rows) {
        const data = { id: this.uid(), nomor: '', tanggal: '', customer: '', items: [], total: 0, show_total: 1, note: '' };
        let tableStartRow = -1;

        rows.forEach((row, rowIndex) => {
            const line = row.join(' ').trim();
            const upLine = line.toUpperCase();
            
            // 1. Nomor Surat
            if (!data.nomor) {
                const noMatch = line.match(/(?:NO|NOMOR|NO\.)[\s:.\\-]*([^\s]{5,})/i);
                if (noMatch) data.nomor = noMatch[1].trim();
            }
            
            // 2. Tanggal — scan setiap baris
            if (!data.tanggal) {
                const d = this.extractDate(line);
                if (d) data.tanggal = d;
            }
            
            // 3. Customer
            if (!data.customer && (upLine.includes('KEPADA') || upLine.includes('YTH'))) {
                const idx = row.findIndex(c => {
                    const s = String(c || '').toUpperCase();
                    return s.includes('YTH') || s.includes('KEPADA');
                });
                const candidate = this.extractCustomer(rows, rowIndex, idx);
                if (candidate && candidate.length > 4 && !candidate.toUpperCase().includes('DI TEMPAT')) {
                    data.customer = candidate;
                }
            }

            // 4. ATT
            if (!data.att && (upLine.includes('ATT') || upLine.includes('U.P') || upLine.includes('UP.'))) {
                data.att = this.valAfter(row, 'ATT') || this.valAfter(row, 'U.P') || this.valAfter(row, 'UP');
            }

            // 5. Tabel
            const hasDesc = upLine.includes('DESCRIPTION') || upLine.includes('DESKRIPSI') || upLine.includes('NAMA BARANG');
            if (hasDesc && (upLine.includes('QTY') || upLine.includes('JUMLAH'))) tableStartRow = rowIndex;
        });

        if (tableStartRow !== -1) {
            data.items = this.extractItems(rows, tableStartRow);
            const summaryStart = tableStartRow + data.items.length + 1;
            const sum = this.extractSummary(rows, summaryStart);
            Object.assign(data, sum);
            data.note = this.extractFooterNotes(rows, summaryStart);
            if (data.total === 0) data.total = data.items.reduce((s, i) => s + i.amount, 0);
        }
        return data;
    },

    valAfter(row, label) {
        const upLabel = label.toUpperCase().replace(':', '').replace('.', '').trim();
        const idx = row.findIndex(c => String(c || '').toUpperCase().includes(upLabel));
        if (idx !== -1) {
            const cellVal = String(row[idx] || '');
            
            // 1. Try split by colon first
            if (cellVal.includes(':')) {
                const parts = cellVal.split(':');
                if (parts[1] && parts[1].trim()) return parts[1].trim();
            }
            
            // 2. Try extract text after label in the same cell (e.g. "ATT. BAPAK RIDWAN")
            const uCell = cellVal.toUpperCase();
            const startIdx = uCell.indexOf(upLabel);
            if (startIdx !== -1) {
                const after = cellVal.substring(startIdx + upLabel.length).replace(/^[:\s.-]+/, '').trim();
                if (after && after.length > 2) return after;
            }

            // 3. Look in subsequent cells
            for (let i = idx + 1; i < Math.min(idx + 5, row.length); i++) {
                const val = String(row[i] || '').trim().replace(/^[:\s.-]+/, '');
                if (val && val.length > 2) return val;
            }
        }
        return '';
    },

    cleanNum(val) {
        if (val === undefined || val === null || val === '') return 0;
        if (typeof val === 'number') return val;
        let s = String(val).trim().replace(/[^\d,.-]/g, '');
        if (!s) return 0;

        if (s.includes('.') && s.includes(',')) {
            s = s.replace(/\./g, '').replace(',', '.');
        } else if ((s.match(/\./g) || []).length > 1) {
            s = s.replace(/\./g, '');
        } else if (s.includes(',')) {
            const parts = s.split(',');
            if (parts[1].length <= 2) s = parts[0].replace(/\./g, '') + '.' + parts[1];
            else s = s.replace(',', '');
        } else if (s.includes('.')) {
            const parts = s.split('.');
            if (parts[1].length === 3) s = s.replace(/\./g, '');
        }
        const num = parseFloat(s);
        return isNaN(num) ? 0 : num;
    },

    extractDate(line) {
        const months = {
            'JANUARI': '01', 'FEBRUARI': '02', 'MARET': '03', 'APRIL': '04',
            'MEI': '05', 'JUNI': '06', 'JULI': '07', 'AGUSTUS': '08',
            'SEPTEMBER': '09', 'OKTOBER': '10', 'NOVEMBER': '11', 'DESEMBER': '12'
        };
        const upperLine = String(line).toUpperCase();
        // Search whole line by default; if MALANG found, search from after it
        let datePart = upperLine;
        if (upperLine.includes('MALANG')) {
            datePart = upperLine.substring(upperLine.indexOf('MALANG') + 6).replace(/^[:\s,]+/, '');
        }
        for (const [m, v] of Object.entries(months)) {
            if (datePart.includes(m)) {
                const dayMatch = datePart.match(new RegExp(`(\\d{1,2})\\s*${m}`));
                const day = dayMatch ? dayMatch[1] : '01';
                const yearMatch = datePart.match(new RegExp(`${m}\\s*(\\d{4})`));
                const year = yearMatch ? yearMatch[1] : null;
                if (!year) continue; // Skip if no year found (avoids false positives)
                return `${year}-${v}-${day.padStart(2, '0')}`;
            }
        }
        // Fallback: strict ISO date YYYY-MM-DD (year 2000–2099, valid month/day ranges)
        const isoMatch = String(line).match(/\b(20\d{2})[-/](0?[1-9]|1[0-2])[-/](0?[1-9]|[12]\d|3[01])\b/);
        if (isoMatch) return `${isoMatch[1]}-${isoMatch[2].padStart(2,'0')}-${isoMatch[3].padStart(2,'0')}`;
        // Return null — no fallback to today's date
        return null;
    },

    extractCustomer(rows, rowIndex, colIndex) {
        for (let c = colIndex + 1; c < Math.min(colIndex + 5, rows[rowIndex].length); c++) {
            const val = String(rows[rowIndex][c] || '').trim();
            if (val && !val.includes(':') && val.length > 3) return val;
        }
        for (let r = rowIndex + 1; r < Math.min(rowIndex + 4, rows.length); r++) {
            if (!rows[r]) continue;
            const val = String(rows[r][colIndex] || rows[r][colIndex+1] || '').trim();
            if (val && val.length > 3) return val;
        }
        return '';
    },

    extractItems(rows, startRow) {
        const items = [];
        const header = rows[startRow].map(c => String(c || '').toUpperCase().trim());
        
        let qtyIdx = header.findIndex(c => c === 'QTY' || c === 'QUANTITY');
        if (qtyIdx === -1) qtyIdx = header.findIndex(c => c.includes('QTY') || c.includes('JUMLAH'));
        
        const colIdx = {
            no: header.findIndex(c => c === 'NO' || c === 'NO.' || c.includes('NO.') || (c.length <= 3 && c.includes('N'))),
            qty: qtyIdx,
            desc: header.findIndex(c => c.includes('DESCRIPTION') || c.includes('DESKRIPSI') || c.includes('NAMA BARANG')),
            price: header.findIndex(c => c.includes('PRICE') || c.includes('HARGA')),
            code: header.findIndex(c => c.includes('CODE') || c.includes('TYPE')),
            unit: header.findIndex(c => c.includes('UNIT') || c.includes('SATUAN'))
        };

        for (let i = startRow + 1; i < rows.length; i++) {
            const row = rows[i];
            if (!row || row.length === 0) continue;
            
            const line = row.join(' ').trim();
            const upLine = line.toUpperCase();

            // BREAK if we hit total summary OR global notes
            // Use word-boundary regex for DP/DISC to avoid false-positives on item names
            // e.g. "AODD FAP 25" contains 'DP' but should NOT break item parsing.
            const isBreakLine = upLine.includes('TOTAL') || upLine.includes('SUB') ||
                /\bDISC(OUNT)?\b/.test(upLine) || upLine.includes('PPN') ||
                /\bDP\b/.test(upLine) || upLine.includes('DOWN PAYMENT');
            if (isBreakLine) break;
            if (upLine.includes('NOTE') || upLine.includes('CATATAN:')) break; 
            
            let qty = this.cleanNum(row[colIdx.qty]);
            let price = this.cleanNum(row[colIdx.price]);
            let desc = colIdx.desc !== -1 ? String(row[colIdx.desc] || '').trim() : '';
            
            // Robustness: jika kolom harga berisi teks dan kolom berikutnya berisi angka
            if (price === 0 && colIdx.price !== -1 && row[colIdx.price+1] !== undefined) {
                const nextVal = this.cleanNum(row[colIdx.price+1]);
                if (nextVal > 0) price = nextVal;
            }
            // Heuristic NO: angka di kolom NO → baris baru; kosong → kelanjutan
            const rawNo = colIdx.no !== -1 ? String(row[colIdx.no] || '').trim() : '';
            const hasNoValue = rawNo && /^\d+$/.test(rawNo);

            // Penggabungan baris lanjutan
            if (items.length > 0 && !hasNoValue && (desc || qty > 0)) {
                const prev = items[items.length-1];
                if (desc) prev.desc += '\n' + desc;
                if (qty > 0 && prev.qty === 0) prev.qty = qty;
                if (price > 0 && prev.price === 0) {
                    prev.price = price;
                    prev.amount = prev.qty * price;
                }
                const rowUnit = colIdx.unit !== -1 ? String(row[colIdx.unit] || '').trim() : '';
                if (rowUnit && (!prev.satuan || prev.satuan === 'UNIT')) prev.satuan = rowUnit;
                continue;
            }

            if (desc || qty > 0) {
                items.push({
                    qty, desc, price, amount: qty * price,
                    type: colIdx.code !== -1 ? String(row[colIdx.code] || '').trim() : '',
                    satuan: colIdx.unit !== -1 ? String(row[colIdx.unit] || '').trim() : 'UNIT'
                });
            }
        }
        return items;
    },

    extractSummary(rows, startRow) {
        const res = { total: 0, disc: 0, dp: 0, ppnMode: 'none' };

        // Step 1: Detect which column index holds monetary values
        // by finding the rightmost non-zero numeric cell in the Sub Total row.
        let valueCol = -1;
        for (let i = startRow; i < Math.min(startRow + 30, rows.length); i++) {
            const row = rows[i] || [];
            const line = row.join(' ').toUpperCase();
            if (line.includes('SUB') && line.includes('TOTAL')) {
                for (let c = row.length - 1; c >= 0; c--) {
                    const v = this.cleanNum(row[c]);
                    if (v > 0) { valueCol = c; break; }
                }
                break;
            }
        }

        // Step 2: Parse summary rows — read values only from the detected column
        for (let i = startRow; i < Math.min(startRow + 30, rows.length); i++) {
            const row = rows[i] || [];
            const line = row.join(' ').toUpperCase();

            // Stop parsing when we hit footer area (signatures, bank info, etc.)
            if (line.includes('HORMAT KAMI') || line.includes('TANDA TERIMA') ||
                line.includes('REK BCA') || line.includes('REKENING') ||
                line.includes('A/N') || line.includes('A/C ')) break;

            // Read value strictly from detected amount column (if found),
            // otherwise fall back to rightmost numeric cell.
            let val = 0;
            if (valueCol !== -1) {
                val = this.cleanNum(row[valueCol]);
            } else {
                const numericCell = [...row].reverse().find(c => {
                    const s = String(c || '').trim();
                    return s && (typeof c === 'number' || /[\d,.]+/.test(s)) && !/[A-Z]{3,}/.test(s);
                });
                val = this.cleanNum(numericCell);
            }

            // Use strict word-boundary checks to avoid false positives:
            // - 'DISC' can match as substring of unrelated words
            // - 'DP' can match in "AODD", "UPDATE", etc.
            const isDisc = /\bDISC(OUNT)?\b/.test(line);
            const isDp = /\bDP\b/.test(line) || line.includes('DOWN PAYMENT');

            if (isDisc && val > 0) res.disc = val;
            if (isDp && val > 0) res.dp = val;
            if (line.includes('PPN') || line.includes('VAT')) { if (val > 0) res.ppnMode = 'exclude'; }
            if (line.includes('TOTAL') && !line.includes('SUB')) res.total = val;
        }
        return res;
    },

    extractFooterNotes(rows, startRow) {
        let notes = [];
        let foundNoteStart = false;
        for (let i = startRow; i < rows.length; i++) {
            const row = rows[i] || [];
            const line = row.join(' ').trim();
            const upLine = line.toUpperCase();

            if (upLine.includes('NOTE') || upLine.includes('CATATAN:')) {
                foundNoteStart = true;
                const afterNote = line.split(/[:\s-]+/i).slice(1).join(' ').trim();
                if (afterNote && afterNote.length > 3) notes.push(afterNote);
                continue;
            }

            if (foundNoteStart) {
                if (upLine.includes('HORMAT KAMI') || upLine.includes('MALANG,') || upLine.includes('SIGNATURE')) break;
                if (line) notes.push(line);
            }
        }
        return notes.join('\n');
    }
};
