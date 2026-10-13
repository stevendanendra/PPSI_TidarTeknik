/**
 * penawaran.js — Penawaran Harga Module
 */

let pnList = [];
let pnEditId = null;
let pnItems = [];
let sortColPn = 'tanggal';
let sortDirPn = -1;

// ===== INIT =====
document.addEventListener('DOMContentLoaded', async () => {
  await syncLocalToDB();
  pnList = await dbStore.get(KEYS.PENAWARAN_LIST);
  
  // 1. DEDUPLICATE: Remove exact duplicates
  const uniquePn = [];
  const seen = new Set();
  let needsSync = false;

  pnList.forEach(pn => {
    const key = (pn.nomor && pn.nomor.trim() !== "") 
      ? `num-${pn.nomor}-v${pn.versi || 1}` 
      : `val-${pn.customer}-${pn.tanggal}-${pn.total}`;
    
    if (!seen.has(key)) {
      seen.add(key);
      uniquePn.push(pn);
    } else {
      needsSync = true;
    }
  });
  pnList = uniquePn;

  // 2. AUTO-REPAIR: Fix any Rp 0 totals
  pnList.forEach(n => {
    const calculatedTotal = (n.items || []).reduce((s, i) => s + (parseFloat(i.amount) || 0), 0);
    if ((!n.total || n.total == 0) && calculatedTotal > 0) {
      n.total = calculatedTotal;
      needsSync = true;
    }
  });

  if (needsSync) {
    await dbStore.set(KEYS.PENAWARAN_LIST, pnList);
    console.log('Penawaran list auto-cleaned and repaired ✅');
  }

  await updateSidebarBadges();
  renderPnTable(pnList);
  buildPnFilterDropdowns();
  setupPnFilters();
  setupPnModal();
  updatePnStats();
});

// ===== STATS =====
function updatePnStats() {
  const now = new Date();
  const thisMonth = pnList.filter(n => {
    const d = new Date(n.tanggal);
    return d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear();
  });
  const totalNilai = pnList.reduce((s,n) => s + (parseFloat(n.total)||0), 0);
  const thisMonthNilai = thisMonth.reduce((s,n) => s + (parseFloat(n.total)||0), 0);

  const e = id => document.getElementById(id);
  if (e('pn-stat-total'))  e('pn-stat-total').textContent = pnList.length;
  if (e('pn-stat-bulan'))  e('pn-stat-bulan').textContent = thisMonth.length;
  if (e('pn-stat-nilai'))  e('pn-stat-nilai').textContent = fmt.rupiah(totalNilai);

  // Sidebar Badge
  const sbBadge = document.getElementById('sb-penawaran-count');
  if (sbBadge) {
    sbBadge.textContent = pnList.length;
  }
}


// ===== FILTER =====
function buildPnFilterDropdowns() {
  const custNames = [...new Set(pnList.map(n => n.customer).filter(Boolean))].sort();
  const sel = document.getElementById('filter-customer');
  if (!sel) return;
  sel.innerHTML = '<option value="">Semua Customer</option>' + custNames.map(n => `<option value="${n}">${n}</option>`).join('');

  const years = [...new Set(pnList.map(n => new Date(n.tanggal).getFullYear()))].sort((a,b)=>b-a);
  const selYear = document.getElementById('filter-year');
  if (selYear) {
    selYear.innerHTML = '<option value="">Semua Tahun</option>' + years.map(y => `<option value="${y}">${y}</option>`).join('');
  }
}

function setupPnFilters() {
  document.getElementById('btn-filter-pn')?.addEventListener('click', applyPnFilter);
  document.getElementById('btn-reset-filter-pn')?.addEventListener('click', () => {
    ['filter-customer','filter-year','filter-month','filter-nomor'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.value = '';
    });
    applyPnFilter();
  });
  document.getElementById('filter-nomor')?.addEventListener('input', applyPnFilter);
}

async function applyPnFilter() {
  pnList = await dbStore.get(KEYS.PENAWARAN_LIST);
  let filtered = [...pnList];
  const cust  = document.getElementById('filter-customer')?.value || '';
  const year  = document.getElementById('filter-year')?.value || '';
  const month = document.getElementById('filter-month')?.value || '';
  const nomor = document.getElementById('filter-nomor')?.value?.toLowerCase() || '';

  if (cust)  filtered = filtered.filter(n => n.customer === cust);
  if (year)  filtered = filtered.filter(n => new Date(n.tanggal).getFullYear() == year);
  if (month) filtered = filtered.filter(n => (new Date(n.tanggal).getMonth()+1) == month);
  if (nomor) filtered = filtered.filter(n => n.nomor.toLowerCase().includes(nomor));

  renderPnTable(filtered);
}

// ===== RENDER TABLE =====
function renderPnTable(data) {
  const tbody = document.getElementById('pn-tbody');
  if (!tbody) return;

  const sorted = [...data].sort((a,b) => {
    if (sortColPn === 'total') {
      return ((parseFloat(a.total)||0) - (parseFloat(b.total)||0)) * sortDirPn;
    }
    const va = String(a[sortColPn]||'');
    const vb = String(b[sortColPn]||'');
    return va < vb ? -sortDirPn : va > vb ? sortDirPn : 0;
  });

  if (!sorted.length) {
    tbody.innerHTML = `<tr><td colspan="7">
      <div class="empty-state"><div class="empty-icon">📝</div><p>Belum ada data Penawaran</p></div>
    </td></tr>`;
    return;
  }

  tbody.innerHTML = sorted.map(n => {
    // Get short notes (first line or first 30 chars)
    let shortNotes = (n.note || '').split('\n')[0];
    if (shortNotes.length > 40) shortNotes = shortNotes.substring(0, 40) + '...';

    const ppnBadge = n.ppnMode === 'exclude'
      ? '<br><span class="badge badge-green" style="font-size:9px;padding:1px 4px;display:inline-block;margin-top:2px">PPN +11%</span>'
      : n.ppnMode === 'include'
        ? '<br><span class="badge badge-blue" style="font-size:9px;padding:1px 4px;display:inline-block;margin-top:2px">PPN Inc</span>'
        : '';

    return `<tr>
      <td class="td-mono">${n.nomor}${pnVersionBadge(n)}</td>
      <td>${fmt.date(n.tanggal)}</td>
      <td><strong>${n.customer}</strong></td>
      <td>${n.att || '-'}</td>
      <td style="font-size: 11px; color: var(--navy-500)">${shortNotes}</td>
      <td class="td-amount">${fmt.rupiah(n.total)}${ppnBadge}</td>
      <td class="td-actions" style="display:flex;gap:4px">
        <button class="btn btn-sm btn-ghost btn-icon" title="Cetak Surat Penawaran" onclick="printPn('${n.id}')">🖨️</button>
        <button class="btn btn-sm btn-warning btn-icon" title="Edit" onclick="editPn('${n.id}')">✏️</button>
        <button class="btn btn-sm btn-danger btn-icon" title="Hapus" onclick="deletePn('${n.id}')">🗑️</button>
        <button class="btn btn-sm btn-success" style="font-size:11px;padding:2px 6px" onclick="buatNotaDariPn('${n.id}')">Buat Nota</button>
        ${n.status === 'Pending' || n.status === 'Ditolak' ? `<button class="btn btn-sm btn-ghost" style="font-size:11px;padding:2px 6px" title="Kirim ke portal customer untuk disetujui" onclick="kirimPn('${n.id}')">📤 Kirim</button>` : ''}
        ${n.status !== 'Revisi' ? `<button class="btn btn-sm btn-ghost" style="font-size:11px;padding:2px 6px" title="Buat versi revisi (V${(n.versi || 1) + 1})" onclick="revisiPn('${n.id}')">🔁 Revisi</button>` : ''}
      </td>
    </tr>`;
  }).join('');

  const countEl = document.getElementById('pn-count');
  if (countEl) countEl.textContent = sorted.length + ' Penawaran';
}

// ===== SORT =====
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('thead th.sortable').forEach(th => {
    th.addEventListener('click', () => {
      const col = th.dataset.col;
      if (sortColPn === col) sortDirPn *= -1;
      else { sortColPn = col; sortDirPn = 1; }
      document.querySelectorAll('thead th.sortable').forEach(t => t.classList.remove('sort-asc','sort-desc'));
      th.classList.add(sortDirPn===1?'sort-asc':'sort-desc');
      applyPnFilter();
    });
  });
});

// ===== MODAL =====
function setupPnModal() {
  const overlay = document.getElementById('modal-pn');
  if (!overlay) return;
  document.getElementById('btn-new-pn')?.addEventListener('click', openNewPn);
  document.querySelector('#modal-pn .modal-close')?.addEventListener('click', closePnModal);
  overlay.addEventListener('click', e => { if(e.target===overlay) closePnModal(); });
  document.getElementById('btn-add-item-pn')?.addEventListener('click', addPnItemRow);
  document.getElementById('btn-save-pn')?.addEventListener('click', savePn);

  // Suggest Nomor
  document.getElementById('btn-suggest-nomor')?.addEventListener('click', (e) => {
    e.preventDefault();
    suggestNomor();
  });

  // Customer autocomplete
  const input = document.getElementById('pn-customer');
  const list  = document.getElementById('pn-customer-ac');
  if (input && list) {
    setupAutocomplete(input, list, async () => await dbStore.get(KEYS.CUSTOMERS), (item) => {
      input.value = item.nama;
      const kotaEl = document.getElementById('pn-kota');
      const attEl  = document.getElementById('pn-att');
      if (kotaEl) kotaEl.value = item.kota || '';
      if (attEl)  attEl.value  = item.pic || '';
      if (!pnEditId) {
        suggestNomor(item.kode || item.nama);
      }
    });
  }

  // Auto suggest number when customer typed manually
  input?.addEventListener('input', () => {
    if (!pnEditId) {
      suggestNomor();
    }
  });

  // Auto suggest number when date changes
  document.getElementById('pn-tanggal')?.addEventListener('change', () => {
    if (!pnEditId) {
      suggestNomor();
    }
  });

  // Checkbox show total
  document.getElementById('pn-show-total')?.addEventListener('change', updatePnSummary);

  // PPN radio listeners
  document.querySelectorAll('input[name="pn-ppn"]').forEach(r => {
    r.addEventListener('change', updatePnSummary);
  });

  // Disc and DP inputs
  document.getElementById('pn-disc')?.addEventListener('input', updatePnSummary);
  document.getElementById('pn-dp')?.addEventListener('input', updatePnSummary);
}

function getRomawiBulan(month) {
    const romawi = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
    return romawi[month] || 'I';
}

function getCustomerInitials(name) {
  if (!name) return "CUST";
  let clean = name.replace(/^(pt\b\.?|cv\b\.?)/i, '').trim();
  const words = clean.split(/\s+/).filter(Boolean);
  if (words.length >= 2) {
    return words.map(w => w[0].toUpperCase()).join('').substring(0, 4);
  }
  return clean.substring(0, 3).toUpperCase();
}

function getCustomerKode(name, selectedKode) {
  if (selectedKode && selectedKode !== name) return selectedKode;
  if (!name) return "CUST";

  const cleanName = name.trim().toLowerCase();
  const custList = store.get(KEYS.CUSTOMERS, []);
  const found = custList.find(c => c.nama && c.nama.trim().toLowerCase() === cleanName);
  if (found && found.kode) return found.kode;

  return getCustomerInitials(name);
}

function suggestNomor(selectedKode) {
    const tglVal = document.getElementById('pn-tanggal')?.value;
    const dateObj = tglVal ? new Date(tglVal) : new Date();

    let currentYear = dateObj.getFullYear();
    let currentMonth = dateObj.getMonth();

    if (tglVal) {
      const parts = tglVal.split('-');
      if (parts.length === 3) {
        currentYear = parseInt(parts[0], 10);
        currentMonth = parseInt(parts[1], 10) - 1;
      }
    }

    const currentYearStr = String(currentYear).slice(-2);
    const currentMonthRomawi = getRomawiBulan(currentMonth);
    
    // Find highest running number for current year
    let highestNum = 0;
    pnList.forEach(pn => {
        if (!pn.nomor) return;
        // Assume format is: NUM/SP-CODE/TT/MONTH/YY
        const parts = pn.nomor.split('/');
        if (parts.length > 0) {
            const numPart = parseInt(parts[0], 10);
            if (!isNaN(numPart) && pn.nomor.endsWith(`/${currentYearStr}`)) {
                if (numPart > highestNum) highestNum = numPart;
            }
        }
    });
    
    const nextNum = highestNum > 0 ? highestNum + 1 : 2500;
    const custVal = document.getElementById('pn-customer')?.value || '';
    const customerKode = getCustomerKode(custVal, selectedKode);
    
    const suggested = `${nextNum}/SP-${customerKode}/TT/${currentMonthRomawi}/${currentYearStr}`;
    const elNomor = document.getElementById('pn-nomor');
    if (elNomor) {
      elNomor.value = suggested;
    }
}

function openNewPn() {
  pnEditId = null;
  // Reset items with full structure including specs
  pnItems = [{ desc:'', qty:1, satuan:'UNIT', code:'', price:0, amount:0, note_item:'', specs:{ capacity:'', pressure:'', inoutlet:'', power:'', material:'' }, hasSpecs:false }];
  document.getElementById('modal-pn-title').textContent = 'Tambah Penawaran Harga';
  document.getElementById('pn-form').reset();
  
  // Set default date
  document.getElementById('pn-tanggal').value = fmt.toInputDate(new Date());
  
  // Reset show_total checkbox
  const showTotalCheck = document.getElementById('pn-show-total');
  if (showTotalCheck) showTotalCheck.checked = true;

  // Reset PPN, disc, dp inputs
  const ppnNone = document.getElementById('pn-ppn-none');
  if (ppnNone) ppnNone.checked = true;
  const discInput = document.getElementById('pn-disc');
  if (discInput) discInput.value = '';
  const dpInput = document.getElementById('pn-dp');
  if (dpInput) dpInput.value = '';

  renderPnItems();
  updatePnSummary();
  suggestNomor();
  document.getElementById('modal-pn').classList.add('open');
}

function editPn(id) {
  const n = pnList.find(x => x.id === id);
  if (!n) return;
  pnEditId = id;
  pnItems = (n.items || []).map(i => ({
    ...i,
    specs: i.specs || { capacity:'', pressure:'', inoutlet:'', power:'', material:'' },
    hasSpecs: i.hasSpecs || (i.specs && Object.values(i.specs).some(v=>v))
  }));
  if (!pnItems.length) pnItems.push({ desc:'', qty:1, satuan:'UNIT', price:0, amount:0, note_item:'', specs: { capacity:'', pressure:'', inoutlet:'', power:'', material:'' }, hasSpecs: false });

  document.getElementById('modal-pn-title').textContent = 'Edit Penawaran Harga';
  const f = (elId, val) => { const el = document.getElementById(elId); if(el) el.value = val||''; };
  f('pn-nomor',        n.nomor);
  f('pn-tanggal',      n.tanggal);
  f('pn-customer',     n.customer);
  f('pn-kota',         n.kota);
  f('pn-att',          n.att);
  f('pn-sub-judul',    n.sub_judul);
  f('pn-catatan',      n.note);
  
  const showTotalCheck = document.getElementById('pn-show-total');
  if (showTotalCheck) {
      // Default true if not defined
      showTotalCheck.checked = (n.show_total === undefined || n.show_total === null) ? true : Boolean(n.show_total);
  }

  // Load PPN Mode, disc, dp
  const ppnMode = n.ppnMode || 'none';
  const radio = document.getElementById('pn-ppn-' + ppnMode);
  if (radio) radio.checked = true;
  
  const discInput = document.getElementById('pn-disc');
  if (discInput) discInput.value = n.disc ? fmt.formatInput(n.disc) : '';
  
  const dpInput = document.getElementById('pn-dp');
  if (dpInput) dpInput.value = n.dp ? fmt.formatInput(n.dp) : '';

  renderPnItems();
  updatePnSummary();
  document.getElementById('modal-pn').classList.add('open');
}

function closePnModal() {
  document.getElementById('modal-pn').classList.remove('open');
}

// ===== ITEMS =====
function renderPnItems() {
  const tbody = document.getElementById('pn-items-tbody');
  if (!tbody) return;
  tbody.innerHTML = pnItems.map((item, i) => {
    const s = item.specs || { capacity:'', pressure:'', inoutlet:'', power:'', material:'' };
    const hasSpecs = item.hasSpecs || false;

    return `
    <tr data-idx="${i}">
      <td class="item-row-no">${i+1}</td>
      <td>
        <div style="display: flex; gap: 10px; align-items: flex-start;">
          <div style="flex: 1;">
            <textarea class="form-control desc-multiline" placeholder="Ketik nama dan spesifikasi..." onchange="updatePnItem(${i},'desc',this.value)">${item.desc||''}</textarea>
            <button type="button" class="btn-spec-toggle ${hasSpecs?'active':''}" onclick="toggleSpecPn(${i})" style="margin-top: 4px;">
              ⚙️ Spesifikasi ${hasSpecs?'(Aktif)':''}
            </button>
          </div>
          
          <div class="spec-grid" style="display: ${hasSpecs ? 'grid' : 'none'}; margin-top: 0; width: 200px; flex-shrink: 0;">
            <div class="spec-field">
              <label>Capacity</label>
              <input type="text" class="form-control" value="${s.capacity||''}" oninput="updateSpecPn(${i},'capacity',this.value)" placeholder="...">
            </div>
            <div class="spec-field">
              <label>Pressure</label>
              <input type="text" class="form-control" value="${s.pressure||''}" oninput="updateSpecPn(${i},'pressure',this.value)" placeholder="...">
            </div>
            <div class="spec-field">
              <label>In/Outlet</label>
              <input type="text" class="form-control" value="${s.inoutlet||''}" oninput="updateSpecPn(${i},'inoutlet',this.value)" placeholder="...">
            </div>
            <div class="spec-field">
              <label>Power</label>
              <input type="text" class="form-control" value="${s.power||''}" oninput="updateSpecPn(${i},'power',this.value)" placeholder="...">
            </div>
            <div class="spec-field">
              <label>Material</label>
              <input type="text" class="form-control" value="${s.material||''}" oninput="updateSpecPn(${i},'material',this.value)" placeholder="...">
            </div>
          </div>
        </div>
      </td>
      <td><input class="form-control" type="number" step="1" min="0" value="${item.qty||1}" oninput="updatePnItemQty(${i},this.value)" style="text-align:center"></td>
      <td><input class="form-control" type="text" placeholder="Type" value="${item.satuan||''}" oninput="updatePnItem(${i},'satuan',this.value)"></td>
      <td><input class="form-control mask-money" type="text" placeholder="0" value="${item.price ? fmt.formatInput(item.price) : ''}" oninput="updatePnItemPrice(${i},this.value)" style="text-align:right"></td>
      <td><input class="form-control" type="text" value="${item.amount ? fmt.formatInput(item.amount) : ''}" readonly style="text-align:right;background:var(--navy-50);color:var(--em-600);font-weight:700"></td>
      <td><input class="form-control" type="text" placeholder="Note per item" value="${item.note_item||''}" oninput="updatePnItem(${i},'note_item',this.value)"></td>
      <td class="item-row-del"><button class="btn-del-row" onclick="deletePnItem(${i})" title="Hapus baris">✕</button></td>
    </tr>`;
  }).join('');
}

function updatePnItem(idx, field, val) {
  pnItems[idx][field] = val;
}

function updatePnItemQty(idx, val) {
  pnItems[idx].qty = parseInt(val)||0;
  pnItems[idx].amount = pnItems[idx].qty * (parseFloat(pnItems[idx].price)||0);
  rerenderPnItemAmount(idx);
  updatePnSummary();
}

function updatePnItemPrice(idx, val) {
  pnItems[idx].price = fmt.unmask(val);
  pnItems[idx].amount = (parseFloat(pnItems[idx].qty)||0) * pnItems[idx].price;
  rerenderPnItemAmount(idx);
  updatePnSummary();
}

function rerenderPnItemAmount(idx) {
  const row = document.querySelector(`#pn-items-tbody tr[data-idx="${idx}"]`);
  if (!row) return;
  const inputs = row.querySelectorAll('input');
  // inputs order: [0]=qty, [1]=satuan, [2]=price(mask-money), [3]=amount(readonly), [4]=note_item
  if (inputs[3]) inputs[3].value = fmt.formatInput(pnItems[idx].amount||0);
}

function addPnItemRow() {
  pnItems.push({ desc:'', qty:1, satuan:'UNIT', price:0, amount:0, note_item:'', specs: { capacity:'', pressure:'', inoutlet:'', power:'', material:'' }, hasSpecs: false });
  renderPnItems();
}

function updateSpecPn(idx, field, val) {
  if (!pnItems[idx].specs) pnItems[idx].specs = { capacity:'', pressure:'', inoutlet:'', power:'', material:'' };
  pnItems[idx].specs[field] = val;
}

function toggleSpecPn(idx) {
  pnItems[idx].hasSpecs = !pnItems[idx].hasSpecs;
  renderPnItems();
}

function deletePnItem(idx) {
  if (pnItems.length <= 1) { showToast('Minimal 1 item diperlukan','warning'); return; }
  pnItems.splice(idx, 1);
  renderPnItems();
  updatePnSummary();
}

// ===== SUMMARY =====
function updatePnSummary() {
  const disc = fmt.unmask(document.getElementById('pn-disc')?.value);
  const dp = fmt.unmask(document.getElementById('pn-dp')?.value);
  const ppnMode = document.querySelector('input[name="pn-ppn"]:checked')?.value || 'none';
  const t = calcPenawaranTotals(pnItems, disc, dp, ppnMode);

  const e = id => document.getElementById(id);
  if (e('pn-sum-subtotal')) e('pn-sum-subtotal').textContent = fmt.rupiah(t.total);
  if (e('pn-sum-disc')) e('pn-sum-disc').textContent = t.discVal ? '- ' + fmt.rupiah(t.discVal) : '-';
  if (e('pn-sum-dp')) e('pn-sum-dp').textContent = t.dpVal ? '- ' + fmt.rupiah(t.dpVal) : '-';

  // DPP & PPN Logic
  if (e('pn-row-dpp')) {
    if (ppnMode === 'include') {
      e('pn-row-dpp').style.display = 'flex';
      if (e('pn-sum-dpp-label')) e('pn-sum-dpp-label').textContent = 'Harga Sebelum PPN (DPP)';
      if (e('pn-sum-dpp')) e('pn-sum-dpp').textContent = fmt.rupiah(t.dpp);

      if (e('pn-sum-ppn-label')) e('pn-sum-ppn-label').textContent = 'PPN 11% (Included)';
      if (e('pn-sum-ppn')) e('pn-sum-ppn').textContent = fmt.rupiah(t.ppn);
    } else if (ppnMode === 'exclude') {
      e('pn-row-dpp').style.display = 'none';
      if (e('pn-sum-ppn-label')) e('pn-sum-ppn-label').textContent = 'PPN 11% (Terpisah)';
      if (e('pn-sum-ppn')) e('pn-sum-ppn').textContent = fmt.rupiah(t.ppn);
    } else {
      e('pn-row-dpp').style.display = 'none';
      if (e('pn-sum-ppn-label')) e('pn-sum-ppn-label').textContent = 'PPN';
      if (e('pn-sum-ppn')) e('pn-sum-ppn').textContent = '-';
    }
  }

  const showTotal = document.getElementById('pn-show-total')?.checked;
  const sumValEl = document.getElementById('pn-sum-total');
  
  if (sumValEl) {
      if (showTotal) {
          sumValEl.textContent = fmt.rupiah(t.grandTotal);
          sumValEl.style.textDecoration = 'none';
          sumValEl.style.color = 'var(--navy-800)';
      } else {
          sumValEl.textContent = 'Disembunyikan';
          sumValEl.style.color = 'var(--navy-400)';
      }
  }
}

// ===== SAVE =====
async function savePn() {
  const btnSave = document.getElementById('btn-save-pn');
  if (btnSave) {
    btnSave.disabled = true;
    const originalText = btnSave.innerHTML;
    btnSave.innerHTML = 'Menyimpan...';
  }

  const resetBtn = () => {
    if (btnSave) {
      btnSave.disabled = false;
      btnSave.innerHTML = '💾 Simpan Penawaran';
    }
  };

  const nomor    = document.getElementById('pn-nomor').value.trim();
  const tanggal  = document.getElementById('pn-tanggal').value;
  const customer = document.getElementById('pn-customer').value.trim();
  const kota     = document.getElementById('pn-kota').value.trim();
  const att      = document.getElementById('pn-att').value.trim();
  const sub_judul = document.getElementById('pn-sub-judul').value.trim();
  const note     = document.getElementById('pn-catatan').value.trim();
  const show_total = document.getElementById('pn-show-total')?.checked ? 1 : 0;
  
  if (!nomor)    { showToast('Nomor Surat wajib diisi','error'); resetBtn(); return; }
  if (!tanggal)  { showToast('Tanggal wajib diisi','error'); resetBtn(); return; }
  if (!customer) { showToast('Nama Perusahaan wajib diisi','error'); resetBtn(); return; }

  // Sync items from DOM
  document.querySelectorAll('#pn-items-tbody tr').forEach((row, i) => {
    if (!pnItems[i]) return;
    const ta = row.querySelector('textarea');
    if (ta) pnItems[i].desc = ta.value;
    
    // Only sync specs if the spec panel is actually active for this item
    if (pnItems[i].hasSpecs) {
      const specInputs = row.querySelectorAll('.spec-grid input');
      if (specInputs.length >= 5) {
        pnItems[i].specs = {
          capacity: specInputs[0].value,
          pressure: specInputs[1].value,
          inoutlet: specInputs[2].value,
          power: specInputs[3].value,
          material: specInputs[4].value
        };
      }
    } else {
      // Specs not active — clear specs data to avoid ghost data
      pnItems[i].specs = { capacity:'', pressure:'', inoutlet:'', power:'', material:'' };
    }
  });

  const ppnMode  = document.querySelector('input[name="pn-ppn"]:checked')?.value || 'none';
  const disc     = fmt.unmask(document.getElementById('pn-disc')?.value);
  const dp       = fmt.unmask(document.getElementById('pn-dp')?.value);

  const t = calcPenawaranTotals(pnItems, disc, dp, ppnMode);
  
  const record = { 
      id: pnEditId||uid(), 
      nomor, tanggal, customer, kota, att, sub_judul, note, show_total, 
      total: t.grandTotal, 
      ppnMode, disc, dp,
      items: [...pnItems] 
  };

  if (pnEditId) {
    const idx = pnList.findIndex(x => x.id === pnEditId);
    if (idx > -1) pnList[idx] = record;
  } else {
    pnList.unshift(record);
  }

  try {
    await dbStore.set(KEYS.PENAWARAN_LIST, pnList);
    showToast(pnEditId ? 'Penawaran berhasil diupdate ✅' : 'Penawaran berhasil disimpan ✅', 'success');
    closePnModal();
    await applyPnFilter();
    buildPnFilterDropdowns();
    updatePnStats();
  } catch (err) {
    console.error(err);
    showToast('Gagal menyimpan Penawaran: ' + err.message, 'error');
  } finally {
    resetBtn();
  }
}

// ===== VERSIONING & PERSETUJUAN CUSTOMER =====
function pnVersionBadge(n) {
  const v = parseInt(n.versi) || 1;
  const cls = { Dikirim: 'badge-blue', Disetujui: 'badge-green', Ditolak: 'badge-danger', Revisi: 'badge-gray' }[n.status];
  const st = cls ? ` <span class="badge ${cls}" style="font-size:9px;padding:1px 4px">${n.status}</span>` : '';
  return `<br><span class="badge badge-amber" style="font-size:9px;padding:1px 4px">V${v}</span>${st}`;
}

async function pnAction(action, id) {
  const res = await fetch(`api.php?action=${action}`, {
    method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id })
  });
  const json = await res.json().catch(() => ({}));
  if (!res.ok || json.status !== 'success') throw new Error(json.message || 'Aksi gagal');
}

async function kirimPn(id) {
  if (!confirmDialog('Kirim penawaran ini ke portal customer untuk disetujui/ditolak?')) return;
  try {
    await pnAction('penawaran_send', id);
    showToast('Penawaran dikirim ke customer', 'success');
    pnList = await dbStore.get(KEYS.PENAWARAN_LIST);
    applyPnFilter();
  } catch (e) { showToast(e.message, 'error'); }
}

async function revisiPn(id) {
  if (!confirmDialog('Buat versi revisi baru dari penawaran ini?')) return;
  try {
    await pnAction('penawaran_revise', id);
    showToast('Versi revisi dibuat (status Draft)', 'success');
    pnList = await dbStore.get(KEYS.PENAWARAN_LIST);
    applyPnFilter();
    updatePnStats();
  } catch (e) { showToast(e.message, 'error'); }
}

// ===== DELETE =====
async function deletePn(id) {
  if (!confirmDialog('Hapus Penawaran ini? Tindakan tidak bisa dibatalkan.')) return;
  pnList = await dbStore.get(KEYS.PENAWARAN_LIST);
  pnList = pnList.filter(x => x.id !== id);
  await dbStore.set(KEYS.PENAWARAN_LIST, pnList);
  showToast('Penawaran berhasil dihapus','success');
  applyPnFilter();
  buildPnFilterDropdowns();
  updatePnStats();
}

// ===== PRINT =====
function printPn(id) {
  window.open(`print-penawaran.php?id=${id}`, '_blank');
}

// ===== BUAT NOTA DARI PENAWARAN =====
function buatNotaDariPn(id) {
    const pn = pnList.find(x => x.id === id);
    if (!pn) return;
    
    // We can store the data temporarily in localStorage and then redirect to nota.php?new=1
    const importData = {
        customer: pn.customer,
        alamatInvoice: pn.kota,
        alamatSJ: pn.kota,
        ppnMode: pn.ppnMode || 'none',
        disc: pn.disc || 0,
        dp: pn.dp || 0,
        items: pn.items.map(item => ({
            qty: item.qty,
            satuan: item.satuan,
            code: '',
            desc: item.desc,
            price: item.price,
            amount: item.amount
        }))
    };
    
    sessionStorage.setItem('tt_import_to_nota', JSON.stringify(importData));
    window.location.href = 'nota.php?new=from_pn';
}

// ===== IMPORT EXCEL =====
document.getElementById('import-pn-file')?.addEventListener('change', async function(e) {
  const file = e.target.files[0];
  if (!file) return;

  try {
    const results = await SmartParser.parseFile(file);
    const newPns = results.penawaran;

    if (!newPns.length) {
      showToast('Tidak ditemukan data Penawaran di file ini','error');
      return;
    }

    pnList = await dbStore.get(KEYS.PENAWARAN_LIST);
    
    newPns.forEach(newP => {
      let exists = -1;
      
      if (newP.nomor && newP.nomor.trim() !== "") {
        // Match by Nomor Surat
        exists = pnList.findIndex(x => x.nomor === newP.nomor);
      } else {
        // If no nomor, match by Customer + Date + Total (Heuristic for duplicates)
        exists = pnList.findIndex(x => 
          (!x.nomor || x.nomor.trim() === "") && 
          x.customer === newP.customer && 
          x.tanggal === newP.tanggal && 
          Math.abs(x.total - newP.total) < 1 // Same total
        );
      }

      if (exists > -1) {
        pnList[exists] = newP;
      } else {
        pnList.unshift(newP);
      }
    });

    await dbStore.set(KEYS.PENAWARAN_LIST, pnList);
    showToast(`${newPns.length} data Penawaran berhasil diimport ✅`, 'success');
    applyPnFilter();
    updatePnStats();
    e.target.value = ''; // Reset
  } catch (err) {
    console.error(err);
    showToast('Gagal membaca file Excel: ' + err.message, 'error');
  }
});
