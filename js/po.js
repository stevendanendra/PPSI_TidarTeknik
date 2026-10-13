/**
 * po.js — Purchase Order Module
 */

let poList = [];
let poEditId = null;
let poItems = [];
let sortColPO = 'tanggal';
let sortDirPO = -1; // newest first

// ===== INIT =====
document.addEventListener('DOMContentLoaded', async () => {
  // Sync first
  await syncLocalToDB();

  poList = await dbStore.get(KEYS.PO_LIST);
  await updateSidebarBadges();
  renderPOTable(poList);
  buildFilterDropdowns();
  setupPOFilters();
  setupPOModal();
  updatePOStats();
  // Auto-open form if came from dashboard quick action
  if (new URLSearchParams(window.location.search).get('new') === '1') {
    setTimeout(openNewPO, 300);
  }
});

// ===== STATS =====
function updatePOStats() {
  const now = new Date();
  const thisMonth = poList.filter(p => {
    const d = new Date(p.tanggal);
    return d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear();
  });
  const totalNilai = poList.reduce((s, p) => s + getPoTotal(p), 0);
  const thisMonthNilai = thisMonth.reduce((s, p) => s + getPoTotal(p), 0);

  const elTotal = document.getElementById('po-stat-total');
  const elBulan = document.getElementById('po-stat-bulan');
  const elNilai = document.getElementById('po-stat-nilai');
  const elNilaiBulan = document.getElementById('po-stat-nilai-bulan');
  if (elTotal) elTotal.textContent = poList.length;
  if (elBulan) elBulan.textContent = thisMonth.length;
  if (elNilai) elNilai.textContent = fmt.rupiah(totalNilai);
  if (elNilaiBulan) elNilaiBulan.textContent = fmt.rupiah(thisMonthNilai);

  // Overdue Page Alert
  const today = new Date(); today.setHours(0, 0, 0, 0);
  const overdueList = poList.filter(p => {
    if (p.status === 'Lunas') return false;
    const term = parseInt(p.termin) || 30;
    const d = new Date(p.tanggal);
    d.setDate(d.getDate() + term);
    return today > d;
  });

  const pageAlert = document.getElementById('overdue-page-alert');
  const pageCount = document.getElementById('overdue-page-count');
  if (pageAlert && overdueList.length > 0) {
    pageAlert.style.display = 'flex';
    if (pageCount) pageCount.textContent = overdueList.length;
  } else if (pageAlert) {
    pageAlert.style.display = 'none';
  }

  // Sidebar Badge color
  const sbBadge = document.getElementById('sb-po-count');
  if (sbBadge) {
    sbBadge.textContent = poList.length;
    if (overdueList.length > 0) {
      sbBadge.style.background = 'var(--em-500)';
      sbBadge.style.color = 'white';
      sbBadge.title = `${overdueList.length} Jatuh Tempo!`;
    } else {
      sbBadge.style.background = 'var(--navy-100)';
      sbBadge.style.color = 'var(--navy-600)';
    }
  }
}

function getPoTotal(p) {
  const t = calcPOTotals(p.items || [], p.disc || 0, p.dp || 0, p.ppnMode || 'none');
  return t.total;
}

// ===== FILTER =====
function buildFilterDropdowns() {
  const suppNames = [...new Set(poList.map(p => p.supplier).filter(Boolean))].sort();
  const sel = document.getElementById('filter-supplier');
  if (sel) {
    const currentVal = sel.value;
    sel.innerHTML = '<option value="">Semua Supplier</option>' +
      suppNames.map(n => `<option value="${n}">${n}</option>`).join('');
    if (currentVal && suppNames.includes(currentVal)) sel.value = currentVal;
  }

  // month/year
  const years = [...new Set(poList.map(p => {
    if (!p.tanggal) return null;
    return p.tanggal.split('-')[0];
  }).filter(Boolean))].sort((a, b) => b - a);

  const selYear = document.getElementById('filter-year');
  if (selYear) {
    const currentYear = selYear.value;
    selYear.innerHTML = '<option value="">Semua Tahun</option>' +
      years.map(y => `<option value="${y}">${y}</option>`).join('');
    if (currentYear && years.includes(currentYear)) selYear.value = currentYear;
  }
}

function setupPOFilters() {
  const btnFilter = document.getElementById('btn-filter-po');
  const btnReset = document.getElementById('btn-reset-filter-po');
  if (btnFilter) btnFilter.addEventListener('click', applyPOFilter);
  if (btnReset) btnReset.addEventListener('click', () => {
    ['filter-supplier', 'filter-year', 'filter-month', 'filter-nomor-po', 'filter-status-po'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.value = '';
    });
    applyPOFilter();
  });
  // live search
  const searchEl = document.getElementById('filter-nomor-po');
  if (searchEl) searchEl.addEventListener('input', applyPOFilter);
  const statusEl = document.getElementById('filter-status-po');
  if (statusEl) statusEl.addEventListener('change', applyPOFilter);
}

async function applyPOFilter(overrideList) {
  if (overrideList && Array.isArray(overrideList)) {
    poList = overrideList;
  } else {
    poList = await dbStore.get(KEYS.PO_LIST);
  }
  let filtered = [...poList];
  const supplier = document.getElementById('filter-supplier')?.value || '';
  const year = document.getElementById('filter-year')?.value || '';
  const month = document.getElementById('filter-month')?.value || '';
  const nomor = document.getElementById('filter-nomor-po')?.value?.toLowerCase() || '';

  if (supplier) filtered = filtered.filter(p => p.supplier === supplier);
  if (year) {
    filtered = filtered.filter(p => {
      if (!p.tanggal) return false;
      const parts = p.tanggal.split('-');
      return parts[0] == year;
    });
  }
  if (month) {
    filtered = filtered.filter(p => {
      if (!p.tanggal) return false;
      const parts = p.tanggal.split('-');
      return parseInt(parts[1], 10) == month;
    });
  }
  if (nomor) filtered = filtered.filter(p => p.nomor && p.nomor.toLowerCase().includes(nomor));

  const status = document.getElementById('filter-status-po')?.value || '';
  if (status) {
    const today = new Date(); today.setHours(0, 0, 0, 0);
    if (status === 'Overdue') {
      filtered = filtered.filter(p => {
        if (p.status === 'Lunas') return false;
        const term = parseInt(p.termin) || 30;
        const d = new Date(p.tanggal);
        d.setDate(d.getDate() + term);
        return today > d;
      });
    } else {
      filtered = filtered.filter(p => p.status === status);
    }
  }

  renderPOTable(filtered);
}

// ===== RENDER TABLE =====
function renderPOTable(data) {
  const tbody = document.getElementById('po-tbody');
  if (!tbody) return;

  // sort
  const sorted = [...data].sort((a, b) => {
    if (sortColPO === 'total') {
      return (getPoTotal(a) - getPoTotal(b)) * sortDirPO;
    }
    const va = String(a[sortColPO] || '');
    const vb = String(b[sortColPO] || '');
    return va < vb ? -sortDirPO : va > vb ? sortDirPO : 0;
  });

  if (!sorted.length) {
    tbody.innerHTML = `<tr><td colspan="8">
      <div class="empty-state"><div class="empty-icon">📋</div><p>Belum ada data Purchase Order</p></div>
    </td></tr>`;
    return;
  }

  tbody.innerHTML = sorted.map((p, i) => {
    const totals = calcPOTotals(p.items || [], p.disc || 0, p.dp || 0, p.ppnMode || 'none');
    const ppnBadge = p.ppnMode === 'exclude'
      ? '<span class="badge badge-green">PPN 11%</span>'
      : p.ppnMode === 'include'
        ? '<span class="badge badge-blue">Inc PPN</span>'
        : '<span class="badge badge-gray">No PPN</span>';

    // Due Date Logic
    const termin = parseInt(p.termin) || 30;
    const dateObj = new Date(p.tanggal);
    const dueDate = new Date(dateObj);
    dueDate.setDate(dateObj.getDate() + termin);
    const today = new Date(); today.setHours(0, 0, 0, 0);
    const isOverdue = p.status !== 'Lunas' && today > dueDate;

    let dueStr = '';
    try { dueStr = dueDate.toISOString().split('T')[0]; } catch (e) { dueStr = p.tanggal; }

    const statusBadge = p.status === 'Lunas'
      ? '<span class="badge badge-green">Lunas</span>'
      : isOverdue
        ? '<span class="badge badge-danger">⚠️ Jatuh Tempo</span>'
        : '<span class="badge badge-amber">Belum Bayar</span>';

    return `<tr>
      <td class="td-mono">${p.nomor}</td>
      <td>${fmt.date(p.tanggal)}<br><small style="color:var(--navy-400)">Due: ${fmt.date(dueStr)}</small></td>
      <td><strong>${p.supplier}</strong><br><small style="color:var(--navy-500)">${p.kota || ''}</small></td>
      <td>${p.note1 || ''}<br><small>${p.note2 || ''}</small></td>
      <td>${ppnBadge}</td>
      <td>${statusBadge}</td>
      <td class="td-amount">${fmt.rupiah(totals.grandTotal)}</td>
      <td class="td-actions">
        <button class="btn btn-sm btn-ghost btn-icon" title="Lihat/Cetak" onclick="printPO('${p.id}')">🖨️</button>
        <button class="btn btn-sm btn-warning btn-icon" title="Edit" onclick="editPO('${p.id}')">✏️</button>
        <button class="btn btn-sm btn-danger btn-icon" title="Hapus" onclick="deletePO('${p.id}')">🗑️</button>
      </td>
    </tr>`;
  }).join('');

  // update count
  const countEl = document.getElementById('po-count');
  if (countEl) countEl.textContent = sorted.length + ' PO';
}

// ===== SORT =====
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('thead th.sortable').forEach(th => {
    th.addEventListener('click', () => {
      const col = th.dataset.col;
      if (sortColPO === col) sortDirPO *= -1;
      else { sortColPO = col; sortDirPO = 1; }
      document.querySelectorAll('thead th.sortable').forEach(t => t.classList.remove('sort-asc', 'sort-desc'));
      th.classList.add(sortDirPO === 1 ? 'sort-asc' : 'sort-desc');
      applyPOFilter();
    });
  });
});

// ===== MODAL =====
function setupPOModal() {
  const overlay = document.getElementById('modal-po');
  if (!overlay) return;
  document.getElementById('btn-new-po')?.addEventListener('click', openNewPO);
  document.querySelector('#modal-po .modal-close')?.addEventListener('click', closePOModal);
  overlay.addEventListener('click', e => { if (e.target === overlay) closePOModal(); });
  document.getElementById('btn-add-item-po')?.addEventListener('click', addPOItemRow);
  document.getElementById('btn-save-po')?.addEventListener('click', savePO);
  document.getElementById('btn-suggest-po')?.addEventListener('click', (e) => {
    e.preventDefault();
    suggestNomorPO();
  });


  // Supplier autocomplete
  const input = document.getElementById('po-supplier');
  const list = document.getElementById('po-supplier-ac');
  if (input && list) {
    setupAutocomplete(input, list, async () => await dbStore.get(KEYS.SUPPLIERS), (item) => {
      input.value = item.nama;
      const kotaEl = document.getElementById('po-kota');
      if (kotaEl) kotaEl.value = item.kota || '';
      if (!poEditId) {
        suggestNomorPO(item.kode || item.nama);
      }
    });
  }

  // Auto suggest number when supplier typed manually
  input?.addEventListener('input', () => {
    if (!poEditId) {
      suggestNomorPO();
    }
  });

  // Auto suggest number when date changes
  document.getElementById('po-tanggal')?.addEventListener('change', () => {
    if (!poEditId) {
      suggestNomorPO();
    }
  });

  // PPN mode change
  document.querySelectorAll('input[name="po-ppn"]').forEach(r => {
    r.addEventListener('change', updatePOSummary);
  });
  // Disc/DP
  document.getElementById('po-disc')?.addEventListener('input', updatePOSummary);
  document.getElementById('po-dp')?.addEventListener('input', updatePOSummary);
}

function openNewPO() {
  poEditId = null;
  poItems = [{ desc: '', type: '', qty: '', price: 0, amount: 0, specs: { capacity: '', pressure: '', inoutlet: '', power: '', material: '' }, hasSpecs: false }];
  document.getElementById('modal-po-title').textContent = 'Tambah Purchase Order';
  document.getElementById('po-form').reset();

  // Default date to today
  const elTgl = document.getElementById('po-tanggal');
  if (elTgl) {
    elTgl.value = new Date().toISOString().split('T')[0];
  }

  // Default Note 1 to PAYMENT 30 HARI
  const elNote1 = document.getElementById('po-note1');
  if (elNote1) {
    elNote1.value = 'PAYMENT 30 HARI';
  }

  setPoRadio('none');
  document.getElementById('po-termin').value = 30;
  document.getElementById('po-status').value = 'Belum Bayar';
  renderPOItems();
  updatePOSummary();
  suggestNomorPO();
  document.getElementById('modal-po').classList.add('open');
}

async function editPO(id) {
  poList = await dbStore.get(KEYS.PO_LIST);
  const p = poList.find(x => x.id === id);
  if (!p) return;
  poEditId = id;
  poItems = (p.items || []).map(i => ({
    ...i,
    specs: i.specs || { capacity: '', pressure: '', inoutlet: '', power: '', material: '' },
    hasSpecs: i.hasSpecs || (i.specs && Object.values(i.specs).some(v => v))
  }));
  if (!poItems.length) poItems.push({ desc: '', type: '', qty: '', price: 0, amount: 0, specs: { capacity: '', pressure: '', inoutlet: '', power: '', material: '' }, hasSpecs: false });

  document.getElementById('modal-po-title').textContent = 'Edit Purchase Order';
  document.getElementById('po-nomor').value = p.nomor || '';
  document.getElementById('po-tanggal').value = p.tanggal || '';
  document.getElementById('po-supplier').value = p.supplier || '';
  document.getElementById('po-kota').value = p.kota || '';
  document.getElementById('po-note1').value = p.note1 || '';
  document.getElementById('po-note2').value = p.note2 || '';
  document.getElementById('po-alamat-kirim').value = p.alamatKirim || '';
  document.getElementById('po-disc').value = p.disc ? fmt.formatInput(p.disc) : '';
  document.getElementById('po-dp').value = p.dp ? fmt.formatInput(p.dp) : '';
  document.getElementById('po-termin').value = p.termin || 30;
  document.getElementById('po-status').value = p.status || 'Belum Bayar';
  setPoRadio(p.ppnMode || 'none');
  renderPOItems();
  updatePOSummary();
  document.getElementById('modal-po').classList.add('open');
}

function setPoRadio(val) {
  const r = document.querySelector(`input[name="po-ppn"][value="${val}"]`);
  if (r) r.checked = true;
}

function closePOModal() {
  document.getElementById('modal-po').classList.remove('open');
}

function renderPOItems() {
  const tbody = document.getElementById('po-items-tbody');
  if (!tbody) return;
  tbody.innerHTML = poItems.map((item, i) => {
    const s = item.specs || { capacity: '', pressure: '', inoutlet: '', power: '', material: '' };
    const hasSpecs = item.hasSpecs || false;

    return `
    <tr data-idx="${i}">
      <td class="item-row-no">${i + 1}</td>
      <td>
        <div style="display: flex; gap: 10px; align-items: flex-start;">
          <div style="flex: 1;">
            <textarea class="form-control desc-multiline" placeholder="Ketik nama dan spesifikasi..." onchange="updatePOItem(${i},'desc',this.value)">${item.desc || ''}</textarea>
            <button type="button" class="btn-spec-toggle ${hasSpecs ? 'active' : ''}" onclick="toggleSpecPO(${i})" style="margin-top: 4px;">
              ⚙️ Spesifikasi ${hasSpecs ? '(Aktif)' : ''}
            </button>
          </div>
          
          <div class="spec-grid" style="display: ${hasSpecs ? 'grid' : 'none'}; margin-top: 0; width: 200px; flex-shrink: 0;">
            <div class="spec-field">
              <label>Capacity</label>
              <input type="text" class="form-control" value="${s.capacity || ''}" oninput="updateSpecPO(${i},'capacity',this.value)" placeholder="...">
            </div>
            <div class="spec-field">
              <label>Pressure</label>
              <input type="text" class="form-control" value="${s.pressure || ''}" oninput="updateSpecPO(${i},'pressure',this.value)" placeholder="...">
            </div>
            <div class="spec-field">
              <label>In/Outlet</label>
              <input type="text" class="form-control" value="${s.inoutlet || ''}" oninput="updateSpecPO(${i},'inoutlet',this.value)" placeholder="...">
            </div>
            <div class="spec-field">
              <label>Power</label>
              <input type="text" class="form-control" value="${s.power || ''}" oninput="updateSpecPO(${i},'power',this.value)" placeholder="...">
            </div>
            <div class="spec-field">
              <label>Material</label>
              <input type="text" class="form-control" value="${s.material || ''}" oninput="updateSpecPO(${i},'material',this.value)" placeholder="...">
            </div>
          </div>
        </div>
      </td>
      <td><input class="form-control" type="number" step="1" min="0" placeholder="Qty" value="${item.qty || ''}" oninput="updatePOItemQty(${i},this.value)" style="text-align:center"></td>
      <td><input class="form-control" type="text" placeholder="Type" value="${item.type || ''}" oninput="updatePOItem(${i},'type',this.value)"></td>
      <td><input class="form-control mask-money" type="text" placeholder="0" value="${item.price ? fmt.formatInput(item.price) : ''}" oninput="updatePOItemPrice(${i},this.value)" style="text-align:right"></td>
      <td><input class="form-control po-item-amount" type="text" value="${item.amount ? fmt.formatInput(item.amount) : ''}" readonly style="text-align:right;background:var(--navy-50);color:var(--em-600);font-weight:700"></td>
      <td class="item-row-del"><button class="btn-del-row" onclick="deletePOItem(${i})" title="Hapus baris">✕</button></td>
    </tr>`;
  }).join('');
}

function updatePOItem(idx, field, val) {
  poItems[idx][field] = val;
}

function updatePOItemQty(idx, val) {
  poItems[idx].qty = parseFloat(val) || 0;
  poItems[idx].amount = poItems[idx].qty * (parseFloat(poItems[idx].price) || 0);
  rerenderItemAmount(idx);
  updatePOSummary();
}

function updatePOItemPrice(idx, val) {
  poItems[idx].price = fmt.unmask(val);
  const numQty = parseFloat(String(poItems[idx].qty).replace(/[^0-9.]/g, '')) || 0;
  poItems[idx].amount = numQty * poItems[idx].price;
  rerenderItemAmount(idx);
  updatePOSummary();
}

function rerenderItemAmount(idx) {
  const row = document.querySelector(`#po-items-tbody tr[data-idx="${idx}"]`);
  if (!row) return;
  const amountInput = row.querySelector('.po-item-amount');
  if (amountInput) amountInput.value = fmt.formatInput(poItems[idx].amount || 0);
}

function addPOItemRow() {
  poItems.push({ desc: '', type: '', qty: '', price: 0, amount: 0, specs: { capacity: '', pressure: '', inoutlet: '', power: '', material: '' }, hasSpecs: false });
  renderPOItems();
}

function updateSpecPO(idx, field, val) {
  if (!poItems[idx].specs) poItems[idx].specs = { capacity: '', pressure: '', inoutlet: '', power: '', material: '' };
  poItems[idx].specs[field] = val;
}

function toggleSpecPO(idx) {
  poItems[idx].hasSpecs = !poItems[idx].hasSpecs;
  renderPOItems();
}

function deletePOItem(idx) {
  if (poItems.length <= 1) { showToast('Minimal 1 item diperlukan', 'warning'); return; }
  poItems.splice(idx, 1);
  renderPOItems();
  updatePOSummary();
}

// ===== PO SUMMARY =====
function updatePOSummary() {
  const disc = fmt.unmask(document.getElementById('po-disc')?.value);
  const dp = fmt.unmask(document.getElementById('po-dp')?.value);
  const ppnMode = document.querySelector('input[name="po-ppn"]:checked')?.value || 'none';
  const t = calcPOTotals(poItems, disc, dp, ppnMode);

  const e = id => document.getElementById(id);
  if (e('po-sum-subtotal')) e('po-sum-subtotal').textContent = fmt.rupiah(t.total);
  if (e('po-sum-disc')) e('po-sum-disc').textContent = t.discVal ? '- ' + fmt.rupiah(t.discVal) : '-';
  if (e('po-sum-dp')) e('po-sum-dp').textContent = t.dpVal ? '- ' + fmt.rupiah(t.dpVal) : '-';

  if (e('po-sum-ppn')) {
    e('po-sum-ppn').textContent = ppnMode === 'none' ? '-' : fmt.rupiah(t.ppn);
  }

  const labelEl = document.querySelector('label[for="po-sum-ppn"]') || { textContent: '' };
  if (ppnMode === 'include') labelEl.textContent = 'PPN 11% (Sudah termasuk)';
  else if (ppnMode === 'exclude') labelEl.textContent = 'PPN 11% (Ditambahkan)';
  else labelEl.textContent = 'PPN 11%';

  if (e('po-sum-total')) e('po-sum-total').textContent = fmt.rupiah(t.grandTotal);
}

// ===== SAVE PO =====
async function savePO() {
  const btnSave = document.getElementById('btn-save-po');
  if (btnSave) {
    btnSave.disabled = true;
    const originalText = btnSave.innerHTML;
    btnSave.innerHTML = 'Menyimpan...';
  }

  const resetBtn = () => {
    if (btnSave) {
      btnSave.disabled = false;
      btnSave.innerHTML = '💾 Simpan PO';
    }
  };

  const nomor = document.getElementById('po-nomor').value.trim();
  const tanggal = document.getElementById('po-tanggal').value;
  const supplier = document.getElementById('po-supplier').value.trim();
  const kota = document.getElementById('po-kota').value.trim();
  const note1 = document.getElementById('po-note1').value.trim();
  const note2 = document.getElementById('po-note2').value.trim();
  const alamatKirim = document.getElementById('po-alamat-kirim').value.trim();
  const disc = fmt.unmask(document.getElementById('po-disc').value);
  const dp = fmt.unmask(document.getElementById('po-dp').value);
  const ppnMode = document.querySelector('input[name="po-ppn"]:checked')?.value || 'none';
  const termin = parseInt(document.getElementById('po-termin').value) || 30;
  const status = document.getElementById('po-status').value;

  if (!nomor) { showToast('Nomor PO wajib diisi', 'error'); resetBtn(); return; }
  if (!tanggal) { showToast('Tanggal wajib diisi', 'error'); resetBtn(); return; }
  if (!supplier) { showToast('Nama Supplier wajib diisi', 'error'); resetBtn(); return; }

  // Sync items from DOM
  document.querySelectorAll('#po-items-tbody tr').forEach((row, i) => {
    if (!poItems[i]) return;
    const ta = row.querySelector('textarea');
    if (ta) poItems[i].desc = ta.value;

    // Only sync specs if the spec panel is actually active for this item
    if (poItems[i].hasSpecs) {
      const specInputs = row.querySelectorAll('.spec-grid input');
      if (specInputs.length >= 5) {
        poItems[i].specs = {
          capacity: specInputs[0].value,
          pressure: specInputs[1].value,
          inoutlet: specInputs[2].value,
          power: specInputs[3].value,
          material: specInputs[4].value
        };
      }
    } else {
      // Specs not active — clear to avoid ghost data
      poItems[i].specs = { capacity:'', pressure:'', inoutlet:'', power:'', material:'' };
    }
  });

  poList = await dbStore.get(KEYS.PO_LIST);
  const tTotals = calcPOTotals(poItems, disc, dp, ppnMode);
  const record = { id: poEditId || uid(), nomor, tanggal, supplier, kota, note1, note2, alamatKirim, disc, dp, ppnMode, catatan: '', termin, status, total: tTotals.grandTotal, items: [...poItems] };

  if (poEditId) {
    const idx = poList.findIndex(x => x.id === poEditId);
    if (idx > -1) poList[idx] = record;
  } else {
    const existingIdx = poList.findIndex(x => x.nomor === record.nomor);
    if (existingIdx > -1) {
      poList[existingIdx] = record;
    } else {
      poList.unshift(record);
    }
  }

  try {
    await dbStore.set(KEYS.PO_LIST, poList);
    showToast(poEditId ? 'PO berhasil diupdate ✅' : 'PO berhasil disimpan ✅', 'success');
    closePOModal();

    if (!poEditId) {
      // Reset active filters so the newly created PO is guaranteed to be shown in the table
      ['filter-supplier', 'filter-year', 'filter-month', 'filter-nomor-po', 'filter-status-po'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
      });
    }

    await applyPOFilter(poList);
    buildFilterDropdowns();
    updatePOStats();
  } catch (err) {
    console.error(err);
    showToast('Gagal menyimpan PO: ' + err.message, 'error');
  } finally {
    resetBtn();
  }
}

// ===== DELETE PO =====
async function deletePO(id) {
  if (!confirmDialog('Hapus PO ini? Tindakan tidak bisa dibatalkan.')) return;
  poList = await dbStore.get(KEYS.PO_LIST);
  poList = poList.filter(x => x.id !== id);
  await dbStore.set(KEYS.PO_LIST, poList);
  showToast('PO berhasil dihapus', 'success');
  applyPOFilter();
  buildFilterDropdowns();
  updatePOStats();
}

// ===== PRINT =====
function printPO(id) {
  window.open(`print-po.php?id=${id}`, '_blank');
}
// ===== IMPORT EXCEL =====
document.getElementById('import-po-file')?.addEventListener('change', async function (e) {
  const file = e.target.files[0];
  if (!file) return;

  try {
    const results = await SmartParser.parseFile(file);
    const newPOs = results.po;

    if (!newPOs.length) {
      showToast('Tidak ditemukan data Purchase Order di file ini', 'error');
      return;
    }

    poList = await dbStore.get(KEYS.PO_LIST);

    // Merge or append
    newPOs.forEach(newPo => {
      const exists = poList.findIndex(x => x.nomor === newPo.nomor);
      if (exists > -1) {
        poList[exists] = newPo; // Update existing
      } else {
        poList.unshift(newPo);
      }
    });

    await dbStore.set(KEYS.PO_LIST, poList);
    showToast(`${newPOs.length} data PO berhasil diimport ✅`, 'success');
    applyPOFilter();
    e.target.value = ''; // Reset
  } catch (err) {
    console.error(err);
    showToast('Gagal membaca file Excel: ' + err.message, 'error');
  }
});

function getSupplierInitials(name) {
  if (!name) return "SUPP";
  let clean = name.replace(/^(pt\b\.?|cv\b\.?)/i, '').trim();
  const words = clean.split(/\s+/).filter(Boolean);
  if (words.length >= 2) {
    return words.map(w => w[0].toUpperCase()).join('').substring(0, 4);
  }
  return clean.substring(0, 3).toUpperCase();
}

function getSupplierKode(name, selectedKode) {
  if (selectedKode && selectedKode !== name) return selectedKode;
  if (!name) return "SUPP";

  const cleanName = name.trim().toLowerCase();
  const suppList = store.get(KEYS.SUPPLIERS, []);
  const found = suppList.find(s => s.nama && s.nama.trim().toLowerCase() === cleanName);
  if (found && found.kode) return found.kode;

  return getSupplierInitials(name);
}

function suggestNomorPO(selectedKode) {
  const tglVal = document.getElementById('po-tanggal')?.value;
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
  const romawi = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
  const currentMonthRomawi = romawi[currentMonth];

  let highestNum = 0;
  poList.forEach(po => {
    if (!po.nomor) return;
    const parts = po.nomor.split('/');
    if (parts.length > 0) {
      const numPart = parseInt(parts[0], 10);
      if (!isNaN(numPart) && po.nomor.endsWith(`/${currentYearStr}`)) {
        if (numPart > highestNum) highestNum = numPart;
      }
    }
  });

  const nextNum = highestNum > 0 ? highestNum + 1 : 4000;
  const suppVal = document.getElementById('po-supplier')?.value || '';
  const supplierKode = getSupplierKode(suppVal, selectedKode);

  const suggested = `${nextNum}/TT-${supplierKode}/${currentMonthRomawi}/${currentYearStr}`;
  const elNomor = document.getElementById('po-nomor');
  if (elNomor) {
    elNomor.value = suggested;
  }
}
