/**
 * nota.js — Nota / Invoice Module
 */

let notaList = [];
let notaEditId = null;
let notaItems = [];
let sortColNota = 'tanggal';
let sortDirNota = -1;

// ===== INIT =====
document.addEventListener('DOMContentLoaded', async () => {
  // Sync first
  await syncLocalToDB();

  notaList = await dbStore.get(KEYS.NOTA_LIST);
  await updateSidebarBadges();
  renderNotaTable(notaList);
  buildNotaFilterDropdowns();
  setupNotaFilters();
  setupNotaModal();
  updateNotaStats();
  // Auto-open form if came from dashboard quick action
  if (new URLSearchParams(window.location.search).get('new') === '1') {
    setTimeout(openNewNota, 300);
  } else if (new URLSearchParams(window.location.search).get('new') === 'from_pn') {
    setTimeout(openNewNotaFromPn, 300);
  }

  // Handle filter_status param
  const filterStatus = new URLSearchParams(window.location.search).get('filter_status');
  if (filterStatus) {
    const el = document.getElementById('filter-status-nota');
    if (el) {
      el.value = filterStatus;
      applyNotaFilter();
    }
  }
});

// ===== STATS =====
function updateNotaStats() {
  const now = new Date();
  const thisMonth = notaList.filter(n => {
    const d = new Date(n.tanggal);
    return d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear();
  });
  const totalNilai = notaList.reduce((s, n) => s + getNotaGrandTotal(n), 0);
  const thisMonthNilai = thisMonth.reduce((s, n) => s + getNotaGrandTotal(n), 0);

  const e = id => document.getElementById(id);
  if (e('nota-stat-total')) e('nota-stat-total').textContent = notaList.length;
  if (e('nota-stat-bulan')) e('nota-stat-bulan').textContent = thisMonth.length;
  if (e('nota-stat-nilai')) e('nota-stat-nilai').textContent = fmt.rupiah(totalNilai);
  if (e('nota-stat-nilai-bulan')) e('nota-stat-nilai-bulan').textContent = fmt.rupiah(thisMonthNilai);

  // Overdue Page Alert
  const today = new Date(); today.setHours(0, 0, 0, 0);
  const overdueList = notaList.filter(n => {
    if (n.status === 'Lunas') return false;
    const term = parseInt(n.termin) || 30;
    const d = new Date(n.tanggal);
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

  // Sementara Page Alert (Only show for items >= 1 week old)
  const sementaraListOld = notaList.filter(n => {
    if (n.status !== 'Sementara') return false;
    const diff = Math.abs(today - new Date(n.tanggal));
    return diff >= (7 * 24 * 60 * 60 * 1000); // 7 days
  });

  const semAlert = document.getElementById('sementara-page-alert');
  const semCount = document.getElementById('sementara-page-count');
  if (semAlert && sementaraListOld.length > 0) {
    semAlert.style.display = 'flex';
    if (semCount) semCount.textContent = sementaraListOld.length;
  } else if (semAlert) {
    semAlert.style.display = 'none';
  }

  // Sidebar Badge color
  const sbBadge = document.getElementById('sb-nota-count');
  if (sbBadge) {
    sbBadge.textContent = notaList.length;
  }

}

function getNotaGrandTotal(n) {
  const t = calcNotaTotals(n.items || [], n.disc || 0, n.dp || 0, n.ppnMode || 'none');
  return t.grandTotal;
}

// ===== FILTER =====
function buildNotaFilterDropdowns() {
  const custNames = [...new Set(notaList.map(n => n.customer).filter(Boolean))].sort();
  const sel = document.getElementById('filter-customer');
  if (!sel) return;
  sel.innerHTML = '<option value="">Semua Customer</option>' +
    custNames.map(n => `<option value="${esc(n)}">${esc(n)}</option>`).join('');

  const years = [...new Set(notaList.map(n => new Date(n.tanggal).getFullYear()))].sort((a, b) => b - a);
  const selYear = document.getElementById('filter-year-nota');
  if (selYear) {
    selYear.innerHTML = '<option value="">Semua Tahun</option>' +
      years.map(y => `<option value="${esc(y)}">${esc(y)}</option>`).join('');
  }
}

function setupNotaFilters() {
  document.getElementById('btn-filter-nota')?.addEventListener('click', applyNotaFilter);
  document.getElementById('btn-reset-filter-nota')?.addEventListener('click', () => {
    ['filter-customer', 'filter-year-nota', 'filter-month-nota', 'filter-nomor-nota', 'filter-status-nota'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.value = '';
    });
    applyNotaFilter();
  });
  document.getElementById('filter-nomor-nota')?.addEventListener('input', applyNotaFilter);
  document.getElementById('filter-status-nota')?.addEventListener('change', applyNotaFilter);
}

async function applyNotaFilter() {
  notaList = await dbStore.get(KEYS.NOTA_LIST);
  let filtered = [...notaList];
  const cust = document.getElementById('filter-customer')?.value || '';
  const year = document.getElementById('filter-year-nota')?.value || '';
  const month = document.getElementById('filter-month-nota')?.value || '';
  const nomor = document.getElementById('filter-nomor-nota')?.value?.toLowerCase() || '';
  const status = document.getElementById('filter-status-nota')?.value || '';

  if (cust) filtered = filtered.filter(n => n.customer === cust);
  if (year) filtered = filtered.filter(n => new Date(n.tanggal).getFullYear() == year);
  if (month) filtered = filtered.filter(n => (new Date(n.tanggal).getMonth() + 1) == month);
  if (nomor) filtered = filtered.filter(n => n.nomor.toLowerCase().includes(nomor));

  if (status) {
    const today = new Date(); today.setHours(0, 0, 0, 0);
    if (status === 'Overdue') {
      filtered = filtered.filter(n => {
        if (n.status === 'Lunas') return false;
        const term = parseInt(n.termin) || 30;
        const d = new Date(n.tanggal);
        d.setDate(d.getDate() + term);
        return today > d;
      });
    } else if (status === 'Sementara') {
      filtered = filtered.filter(n => n.status === 'Sementara');
    } else {
      filtered = filtered.filter(n => n.status === status);
    }
  }

  renderNotaTable(filtered);
}

// ===== RENDER TABLE =====
function renderNotaTable(data) {
  const tbody = document.getElementById('nota-tbody');
  if (!tbody) return;

  const sorted = [...data].sort((a, b) => {
    if (sortColNota === 'grandTotal') {
      return (getNotaGrandTotal(a) - getNotaGrandTotal(b)) * sortDirNota;
    }
    const va = String(a[sortColNota] || '');
    const vb = String(b[sortColNota] || '');
    return va < vb ? -sortDirNota : va > vb ? sortDirNota : 0;
  });

  if (!sorted.length) {
    tbody.innerHTML = `<tr><td colspan="7">
      <div class="empty-state"><div class="empty-icon">🧾</div><p>Belum ada data Nota / Invoice</p></div>
    </td></tr>`;
    return;
  }

  tbody.innerHTML = sorted.map(n => {
    const t = calcNotaTotals(n.items || [], n.disc || 0, n.dp || 0, n.ppnMode || 'none');
    const ppnBadge = n.ppnMode === 'exclude'
      ? '<span class="badge badge-green">PPN +11%</span>'
      : n.ppnMode === 'include'
        ? '<span class="badge badge-blue">PPN Inc</span>'
        : '<span class="badge badge-gray">No PPN</span>';

    // Due Date Logic
    const termin = parseInt(n.termin) || 30;
    const dateObj = new Date(n.tanggal);
    const dueDate = new Date(dateObj);
    dueDate.setDate(dateObj.getDate() + termin);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const isOverdue = n.status !== 'Lunas' && today > dueDate;

    let dueStr = '';
    try { dueStr = dueDate.toISOString().split('T')[0]; } catch (e) { dueStr = n.tanggal; }

    const statusBadge = n.status === 'Lunas'
      ? '<span class="badge badge-green">Lunas</span>'
      : isOverdue
        ? '<span class="badge badge-danger">⚠️ Jatuh Tempo</span>'
        : n.status === 'Sementara'
          ? '<span class="badge badge-warning">📝 Sementara</span>'
          : '<span class="badge badge-amber">Belum Bayar</span>';

    let smtStr = '';
    if (n.status === 'Sementara') {
      const diffTime = Math.abs(today - dateObj);
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
      const weeks = Math.floor(diffDays / 7);
      if (weeks > 0) {
        smtStr = `<br><small style="color:#b91c1c; font-weight:800; background:#fee2e2; padding:2px 4px; border-radius:4px; display:inline-block; margin-top:4px">⚠️ Sudah ${weeks} Minggu</small>`;
      } else {
        smtStr = `<br><small style="color:#b45309; font-weight:700;">🕒 Belum 1 Minggu</small>`;
      }
    }

    return `<tr>
      <td class="td-mono">${esc(n.nomor)}</td>
      <td>${fmt.date(n.tanggal)}${smtStr}${n.status !== 'Lunas' ? `<br><small style="color:var(--navy-400)">Due: ${fmt.date(dueStr)}</small>` : ''}</td>
      <td><strong>${esc(n.customer)}</strong></td>
      <td><span class="td-mono" style="font-size:11px">${esc(n.refPO || '-')}</span></td>
      <td>${ppnBadge}</td>
      <td>${statusBadge}</td>
      <td class="td-amount">${fmt.rupiah(t.grandTotal)}</td>
      <td class="td-actions">
        <button class="btn btn-sm btn-ghost btn-icon" title="Cetak Invoice" onclick="printNota('${n.id}','invoice')">🖨️</button>
        <button class="btn btn-sm btn-ghost btn-icon" title="Cetak Surat Jalan" onclick="printNota('${n.id}','sj')" style="color:var(--am-600)">📦</button>
        <button class="btn btn-sm btn-warning btn-icon" title="Edit" onclick="editNota('${n.id}')">✏️</button>
        <button class="btn btn-sm btn-danger btn-icon" title="Hapus" onclick="deleteNota('${n.id}')">🗑️</button>
      </td>
    </tr>`;
  }).join('');

  const countEl = document.getElementById('nota-count');
  if (countEl) countEl.textContent = sorted.length + ' Nota';
}

// ===== SORT =====
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('thead th.sortable').forEach(th => {
    th.addEventListener('click', () => {
      const col = th.dataset.col;
      if (sortColNota === col) sortDirNota *= -1;
      else { sortColNota = col; sortDirNota = 1; }
      document.querySelectorAll('thead th.sortable').forEach(t => t.classList.remove('sort-asc', 'sort-desc'));
      th.classList.add(sortDirNota === 1 ? 'sort-asc' : 'sort-desc');
      applyNotaFilter();
    });
  });
});

// ===== MODAL =====
function setupNotaModal() {
  const overlay = document.getElementById('modal-nota');
  if (!overlay) return;
  document.getElementById('btn-new-nota')?.addEventListener('click', openNewNota);
  document.querySelector('#modal-nota .modal-close')?.addEventListener('click', closeNotaModal);
  overlay.addEventListener('click', e => { if (e.target === overlay) closeNotaModal(); });
  document.getElementById('btn-add-item-nota')?.addEventListener('click', addNotaItemRow);
  document.getElementById('btn-save-nota')?.addEventListener('click', saveNota);
  document.getElementById('btn-suggest-nota')?.addEventListener('click', (e) => {
    e.preventDefault();
    suggestNomorNota();
  });


  // Customer autocomplete
  const input = document.getElementById('nota-customer');
  const list = document.getElementById('nota-customer-ac');
  if (input && list) {
    setupAutocomplete(input, list, async () => await dbStore.get(KEYS.CUSTOMERS), (item) => {
      input.value = item.nama;
      const invEl = document.getElementById('nota-alamat-invoice');
      const sjEl = document.getElementById('nota-alamat-sj');
      // Always overwrite if from master for consistency
      if (invEl) invEl.value = item.alamat || '';
      if (sjEl) sjEl.value = item.alamat || '';
      if (!notaEditId) {
        suggestNomorNota();
      }
    });
  }

  // Auto suggest number when customer typed manually
  input?.addEventListener('input', () => {
    if (!notaEditId) {
      suggestNomorNota();
    }
  });

  // Auto suggest number when date changes
  document.getElementById('nota-tanggal')?.addEventListener('change', () => {
    if (!notaEditId) {
      suggestNomorNota();
    }
  });

  // PPN change
  document.querySelectorAll('input[name="nota-ppn"]').forEach(r => {
    r.addEventListener('change', updateNotaSummary);
  });
  document.getElementById('nota-disc')?.addEventListener('input', updateNotaSummary);
  document.getElementById('nota-dp')?.addEventListener('input', updateNotaSummary);

  // Status change handler (lock termin if sementara)
  document.getElementById('nota-status')?.addEventListener('change', (e) => {
    handleNotaStatusChange(e.target.value);
  });
}

function handleNotaStatusChange(status) {
  const terminEl = document.getElementById('nota-termin');
  const terminWrapper = terminEl ? terminEl.closest('.form-group') : null;
  if (!terminEl) return;

  if (status === 'Lunas') {
    // Lunas = sudah bayar, termin tidak relevan — sembunyikan field
    terminEl.value = 0;
    terminEl.disabled = true;
    if (terminWrapper) terminWrapper.style.display = 'none';
  } else if (status === 'Sementara') {
    terminEl.value = 0;
    terminEl.disabled = true;
    if (terminWrapper) terminWrapper.style.display = '';
    terminEl.style.backgroundColor = '#f1f5f9'; // Grey out
    terminEl.style.cursor = 'not-allowed';
  } else {
    terminEl.disabled = false;
    if (terminWrapper) terminWrapper.style.display = '';
    terminEl.style.backgroundColor = '';
    terminEl.style.cursor = '';
    if (terminEl.value == 0) terminEl.value = 30; // Default fallback
  }
}

function openNewNota() {
  notaEditId = null;
  notaItems = [{ qty: 1, satuan: 'PCS', code: '', desc: '', price: 0, amount: 0, specs: { capacity: '', pressure: '', inoutlet: '', power: '', material: '' }, hasSpecs: false }];
  document.getElementById('modal-nota-title').textContent = 'Tambah Nota / Invoice';
  document.getElementById('nota-form').reset();
  
  // Default date to today
  const elTgl = document.getElementById('nota-tanggal');
  if (elTgl) {
    elTgl.value = new Date().toISOString().split('T')[0];
  }

  setNotaRadio('none');
  handleNotaStatusChange('Belum Bayar'); // Initial reset
  renderNotaItems();
  updateNotaSummary();
  suggestNomorNota();
  document.getElementById('modal-nota').classList.add('open');
}

function openNewNotaFromPn() {
  openNewNota(); // reset and open

  const raw = sessionStorage.getItem('tt_import_to_nota');
  if (!raw) return;

  try {
    const data = JSON.parse(raw);
    document.getElementById('nota-customer').value = data.customer || '';
    document.getElementById('nota-alamat-invoice').value = data.alamatInvoice || '';
    document.getElementById('nota-alamat-sj').value = data.alamatSJ || '';

    if (data.ppnMode) {
      setNotaRadio(data.ppnMode);
    }
    if (data.disc) {
      document.getElementById('nota-disc').value = fmt.formatInput(data.disc);
    }
    if (data.dp) {
      document.getElementById('nota-dp').value = fmt.formatInput(data.dp);
    }

    if (data.items && data.items.length > 0) {
      notaItems = data.items.map(i => ({ ...i }));
      renderNotaItems();
      updateNotaSummary();
    }

    suggestNomorNota();
    showToast('Data berhasil dimuat dari Penawaran', 'success');
    sessionStorage.removeItem('tt_import_to_nota');
  } catch (e) {
    console.error(e);
  }
}

function editNota(id) {
  // Use current notaList as it should be up to date from render
  const n = notaList.find(x => x.id === id);
  if (!n) return;
  notaEditId = id;
  notaItems = (n.items || []).map(i => ({
    ...i,
    specs: i.specs || { capacity: '', pressure: '', inoutlet: '', power: '', material: '' },
    hasSpecs: i.hasSpecs || false
  }));
  if (!notaItems.length) notaItems.push({ qty: 1, satuan: 'PCS', code: '', desc: '', price: 0, amount: 0, specs: { capacity: '', pressure: '', inoutlet: '', power: '', material: '' }, hasSpecs: false });

  document.getElementById('modal-nota-title').textContent = 'Edit Nota / Invoice';
  const f = (elId, val) => { const el = document.getElementById(elId); if (el) el.value = val || ''; };
  f('nota-nomor', n.nomor);
  f('nota-tanggal', n.tanggal);
  f('nota-customer', n.customer);
  f('nota-alamat-invoice', n.alamatInvoice);
  f('nota-alamat-sj', n.alamatSJ);
  f('nota-ref-po', n.refPO);
  f('nota-ekspedisi', n.ekspedisi);
  f('nota-tujuan-kirim', n.tujuanKirim);
  f('nota-tanggal-kirim', n.tanggalKirim);
  f('nota-seri-faktur', n.seriFaktur);
  f('nota-disc', n.disc ? fmt.formatInput(n.disc) : '');
  f('nota-dp', n.dp ? fmt.formatInput(n.dp) : '');
  f('nota-pembayaran', n.pembayaran ? fmt.formatInput(n.pembayaran) : '');
  f('nota-tanggal-pembayaran', n.tanggalPembayaran);
  f('nota-termin', n.termin || 30);
  f('nota-status', n.status || 'Belum Bayar');
  setNotaRadio(n.ppnMode || 'none');
  handleNotaStatusChange(n.status || 'Belum Bayar'); // Sync on edit
  renderNotaItems();
  updateNotaSummary();
  document.getElementById('modal-nota').classList.add('open');
}

function setNotaRadio(val) {
  const r = document.querySelector(`input[name="nota-ppn"][value="${val}"]`);
  if (r) r.checked = true;
}

function closeNotaModal() {
  document.getElementById('modal-nota').classList.remove('open');
}

// ===== NOTA ITEMS =====
function renderNotaItems() {
  const tbody = document.getElementById('nota-items-tbody');
  if (!tbody) return;
  tbody.innerHTML = notaItems.map((item, i) => {
    const s = item.specs || { capacity: '', pressure: '', inoutlet: '', power: '', material: '' };
    const hasSpecs = item.hasSpecs || false;

    return `
    <tr data-idx="${i}">
      <td class="item-row-no">${i + 1}</td>
      <td>
        <div style="display: flex; gap: 10px; align-items: flex-start;">
          <div style="flex: 1;">
            <textarea class="form-control" rows="2" placeholder="Deskripsi / Spesifikasi" onchange="updateNotaItem(${i},'desc',this.value)">${esc(item.desc || '')}</textarea>
            <button type="button" class="btn-spec-toggle ${hasSpecs ? 'active' : ''}" onclick="toggleSpec(${i})" style="margin-top: 4px;">
              ⚙️ Spesifikasi ${hasSpecs ? '(Aktif)' : ''}
            </button>
          </div>
          
          <div class="spec-grid" style="display: ${hasSpecs ? 'grid' : 'none'}; margin-top: 0; width: 200px; flex-shrink: 0;">
            <div class="spec-field">
              <label>Capacity</label>
              <input type="text" class="form-control" value="${esc(s.capacity || '')}" oninput="updateNotaSpec(${i},'capacity',this.value)" placeholder="...">
            </div>
            <div class="spec-field">
              <label>Pressure</label>
              <input type="text" class="form-control" value="${esc(s.pressure || '')}" oninput="updateNotaSpec(${i},'pressure',this.value)" placeholder="...">
            </div>
            <div class="spec-field">
              <label>In/Outlet</label>
              <input type="text" class="form-control" value="${esc(s.inoutlet || '')}" oninput="updateNotaSpec(${i},'inoutlet',this.value)" placeholder="...">
            </div>
            <div class="spec-field">
              <label>Power</label>
              <input type="text" class="form-control" value="${esc(s.power || '')}" oninput="updateNotaSpec(${i},'power',this.value)" placeholder="...">
            </div>
            <div class="spec-field">
              <label>Material</label>
              <input type="text" class="form-control" value="${esc(s.material || '')}" oninput="updateNotaSpec(${i},'material',this.value)" placeholder="...">
            </div>
          </div>
        </div>
      </td>
      <td><input class="form-control mask-money" type="text" placeholder="0" value="${item.jasaRakit ? fmt.formatInput(item.jasaRakit) : ''}" oninput="updateNotaItemJasa(${i},this.value)" style="text-align:right"></td>
      <td><input class="form-control" type="number" step="1" min="0" value="${esc(item.qty || 1)}" oninput="updateNotaItemQty(${i},this.value)" style="text-align:center"></td>
      <td><input class="form-control" type="text" placeholder="Type" value="${esc(item.satuan || '')}" oninput="updateNotaItem(${i},'satuan',this.value)"></td>
      <td><input class="form-control mask-money" type="text" placeholder="0" value="${item.price ? fmt.formatInput(item.price) : ''}" oninput="updateNotaItemPrice(${i},this.value)" style="text-align:right"></td>
      <td><input class="form-control" type="text" value="${item.amount ? fmt.formatInput(item.amount) : ''}" readonly style="text-align:right;background:var(--navy-50);color:var(--em-600);font-weight:700"></td>
      <td class="item-row-del"><button class="btn-del-row" onclick="deleteNotaItem(${i})" title="Hapus baris">✕</button></td>
    </tr>
  `;
  }).join('');
}

function toggleSpec(idx) {
  notaItems[idx].hasSpecs = !notaItems[idx].hasSpecs;
  if (!notaItems[idx].specs) {
    notaItems[idx].specs = { capacity: '', pressure: '', inoutlet: '', power: '', material: '' };
  }
  renderNotaItems();
}

function updateNotaSpec(idx, field, val) {
  if (!notaItems[idx].specs) notaItems[idx].specs = {};
  notaItems[idx].specs[field] = val;
}

function updateNotaItem(idx, field, val) {
  notaItems[idx][field] = val;
}

function updateNotaItemJasa(idx, val) {
  notaItems[idx].jasaRakit = fmt.unmask(val);
  notaItems[idx].amount = (parseFloat(notaItems[idx].qty) || 0) * (parseFloat(notaItems[idx].price) || 0) + notaItems[idx].jasaRakit;
  rerenderNotaItemAmount(idx);
  updateNotaSummary();
}

function updateNotaItemQty(idx, val) {
  const v = parseFloat(val) || 0;
  notaItems[idx].qty = v;
  notaItems[idx].amount = v * (parseFloat(notaItems[idx].price) || 0) + (parseFloat(notaItems[idx].jasaRakit) || 0);
  rerenderNotaItemAmount(idx);
  updateNotaSummary();
}

function updateNotaItemPrice(idx, val) {
  notaItems[idx].price = fmt.unmask(val);
  notaItems[idx].amount = (parseFloat(notaItems[idx].qty) || 0) * notaItems[idx].price + (parseFloat(notaItems[idx].jasaRakit) || 0);
  rerenderNotaItemAmount(idx);
  updateNotaSummary();
}

function rerenderNotaItemAmount(idx) {
  const row = document.querySelector(`#nota-items-tbody tr[data-idx="${idx}"]`);
  if (!row) return;
  const inputs = row.querySelectorAll('input');
  const amountInput = inputs[inputs.length - 1];
  if (amountInput) amountInput.value = fmt.formatInput(notaItems[idx].amount || 0);
}

function addNotaItemRow() {
  notaItems.push({ qty: 1, satuan: 'PCS', code: '', desc: '', price: 0, amount: 0, specs: { capacity: '', pressure: '', inoutlet: '', power: '', material: '' }, hasSpecs: false });
  renderNotaItems();
}

function deleteNotaItem(idx) {
  if (notaItems.length <= 1) { showToast('Minimal 1 item diperlukan', 'warning'); return; }
  notaItems.splice(idx, 1);
  renderNotaItems();
  updateNotaSummary();
}

// ===== NOTA SUMMARY =====
function updateNotaSummary() {
  const disc = fmt.unmask(document.getElementById('nota-disc')?.value);
  const dp = fmt.unmask(document.getElementById('nota-dp')?.value);
  const bayar = fmt.unmask(document.getElementById('nota-pembayaran')?.value);
  console.log("DEBUG NOTA:", { disc, dp, bayar, raw_dp: document.getElementById('nota-dp')?.value });
  const ppnMode = document.querySelector('input[name="nota-ppn"]:checked')?.value || 'none';
  const t = calcNotaTotals(notaItems, disc, dp, ppnMode);

  const e = id => document.getElementById(id);
  if (e('nota-sum-total')) e('nota-sum-total').textContent = fmt.rupiah(t.total);
  if (e('nota-sum-disc')) e('nota-sum-disc').textContent = t.discVal ? '- ' + fmt.rupiah(t.discVal) : '-';
  if (e('nota-sum-dp')) e('nota-sum-dp').textContent = t.dpVal ? '- ' + fmt.rupiah(t.dpVal) : '-';

  // DPP & PPN Logic
  if (e('nota-row-dpp')) {
    if (ppnMode === 'include') {
      e('nota-row-dpp').style.display = 'flex';
      if (e('nota-sum-dpp-label')) e('nota-sum-dpp-label').textContent = 'Harga Sebelum PPN (DPP)';
      if (e('nota-sum-dpp')) e('nota-sum-dpp').textContent = fmt.rupiah(t.dpp);

      if (e('nota-sum-ppn-label')) e('nota-sum-ppn-label').textContent = 'PPN 11% (Included)';
      if (e('nota-sum-ppn')) e('nota-sum-ppn').textContent = fmt.rupiah(t.ppn);
    } else if (ppnMode === 'exclude') {
      e('nota-row-dpp').style.display = 'none'; // DPP is same as Total in Exclude mode
      if (e('nota-sum-ppn-label')) e('nota-sum-ppn-label').textContent = 'PPN 11% (Terpisah)';
      if (e('nota-sum-ppn')) e('nota-sum-ppn').textContent = fmt.rupiah(t.ppn);
    } else {
      e('nota-row-dpp').style.display = 'none';
      if (e('nota-sum-ppn-label')) e('nota-sum-ppn-label').textContent = 'PPN';
      if (e('nota-sum-ppn')) e('nota-sum-ppn').textContent = '-';
    }
  }

  if (e('nota-sum-grand')) e('nota-sum-grand').textContent = fmt.rupiah(t.grandTotal);
}

// ===== SAVE NOTA =====
async function saveNota() {
  const btnSave = document.getElementById('btn-save-nota');
  if (btnSave) {
    btnSave.disabled = true;
    const originalText = btnSave.innerHTML;
    btnSave.innerHTML = 'Menyimpan...';
  }

  const resetBtn = () => {
    if (btnSave) {
      btnSave.disabled = false;
      btnSave.innerHTML = '💾 Simpan Nota';
    }
  };

  const nomor = document.getElementById('nota-nomor').value.trim();
  const tanggal = document.getElementById('nota-tanggal').value;
  const customer = document.getElementById('nota-customer').value.trim();
  const alamatInvoice = document.getElementById('nota-alamat-invoice').value.trim();
  const alamatSJ = document.getElementById('nota-alamat-sj').value.trim();
  const refPO = document.getElementById('nota-ref-po').value.trim() || '0';
  const ekspedisi = document.getElementById('nota-ekspedisi').value.trim();
  const tujuanKirim = document.getElementById('nota-tujuan-kirim').value.trim();
  const tanggalKirim = document.getElementById('nota-tanggal-kirim')?.value || '';
  const seriFaktur = document.getElementById('nota-seri-faktur')?.value?.trim() || '';
  const disc = fmt.unmask(document.getElementById('nota-disc').value);
  const dp = fmt.unmask(document.getElementById('nota-dp').value);
  const pembayaran = fmt.unmask(document.getElementById('nota-pembayaran')?.value || '0');
  const tanggalPembayaran = document.getElementById('nota-tanggal-pembayaran')?.value || '';
  const ppnMode = document.querySelector('input[name="nota-ppn"]:checked')?.value || 'none';
  const status = document.getElementById('nota-status').value;
  const termin = (status === 'Sementara' || status === 'Lunas') ? 0 : (parseInt(document.getElementById('nota-termin').value) || 30);

  if (!nomor) { showToast('Nomor Nota wajib diisi', 'error'); resetBtn(); return; }
  if (!tanggal) { showToast('Tanggal wajib diisi', 'error'); resetBtn(); return; }
  if (!customer) { showToast('Nama Customer wajib diisi', 'error'); resetBtn(); return; }

  // Sync items from DOM (ensure everything is captured)
  document.querySelectorAll('#nota-items-tbody tr').forEach((row, i) => {
    if (!notaItems[i]) return;
    const ta = row.querySelector('textarea');
    if (ta) notaItems[i].desc = ta.value;

    // Only sync specs if the spec panel is actually active for this item
    if (notaItems[i].hasSpecs) {
      const specInputs = row.querySelectorAll('.spec-grid input');
      if (specInputs.length >= 5) {
        notaItems[i].specs = {
          capacity: specInputs[0].value,
          pressure: specInputs[1].value,
          inoutlet: specInputs[2].value,
          power: specInputs[3].value,
          material: specInputs[4].value
        };
      }
    } else {
      // Specs not active — clear to avoid ghost data
      notaItems[i].specs = { capacity:'', pressure:'', inoutlet:'', power:'', material:'' };
    }
    // Qty, Satuan, Code, and Price are already handled by oninput/onchange in the HTML
    // and they update notaItems[i] directly.
    const jasa = parseFloat(notaItems[i].jasaRakit) || 0;
    notaItems[i].amount = (parseFloat(notaItems[i].qty) || 0) * (parseFloat(notaItems[i].price) || 0) + jasa;
  });

  notaList = await dbStore.get(KEYS.NOTA_LIST);
  const tTotals = calcNotaTotals(notaItems, disc, dp, ppnMode);
  const record = {
    id: notaEditId || uid(), nomor, tanggal, customer, alamatInvoice, alamatSJ,
    refPO, ekspedisi, tujuanKirim, tanggalKirim, seriFaktur,
    disc, dp, pembayaran, tanggalPembayaran,
    ppnMode, catatan: '', termin, status, total: tTotals.grandTotal, items: [...notaItems]
  };

  if (notaEditId) {
    const idx = notaList.findIndex(x => x.id === notaEditId);
    if (idx > -1) notaList[idx] = record;
  } else {
    notaList.unshift(record);
  }

  try {
    await dbStore.set(KEYS.NOTA_LIST, notaList);
    showToast(notaEditId ? 'Nota berhasil diupdate ✅' : 'Nota berhasil disimpan ✅', 'success');
    closeNotaModal();
    await applyNotaFilter();
    buildNotaFilterDropdowns();
    updateNotaStats();
  } catch (err) {
    console.error(err);
    showToast('Gagal menyimpan Nota: ' + err.message, 'error');
  } finally {
    resetBtn();
  }
}

// ===== DELETE =====
async function deleteNota(id) {
  if (!confirmDialog('Hapus Nota ini? Tindakan tidak bisa dibatalkan.')) return;
  notaList = await dbStore.get(KEYS.NOTA_LIST);
  notaList = notaList.filter(x => x.id !== id);
  await dbStore.set(KEYS.NOTA_LIST, notaList);
  showToast('Nota berhasil dihapus', 'success');
  applyNotaFilter();
  buildNotaFilterDropdowns();
  updateNotaStats();
}

// ===== PRINT =====
function printNota(id, mode) {
  window.open(`print-nota.php?id=${id}&mode=${mode}`, '_blank');
}
// ===== IMPORT EXCEL =====
document.getElementById('import-nota-file')?.addEventListener('change', async function (e) {
  const file = e.target.files[0];
  if (!file) return;

  try {
    const results = await SmartParser.parseFile(file);
    const newNotas = results.nota;

    if (!newNotas.length) {
      showToast('Tidak ditemukan data Nota/Invoice di file ini', 'error');
      return;
    }

    notaList = await dbStore.get(KEYS.NOTA_LIST);

    newNotas.forEach(newN => {
      const exists = notaList.findIndex(x => x.nomor === newN.nomor);
      if (exists > -1) {
        notaList[exists] = newN;
      } else {
        notaList.unshift(newN);
      }
    });

    await dbStore.set(KEYS.NOTA_LIST, notaList);
    showToast(`${newNotas.length} data Nota berhasil diimport ✅`, 'success');
    applyNotaFilter();
    e.target.value = ''; // Reset
  } catch (err) {
    console.error(err);
    showToast('Gagal membaca file Excel: ' + err.message, 'error');
  }
});

function getCustomerInitials(name) {
  if (!name) return "CUST";
  let clean = name.replace(/^(pt\b\.?|cv\b\.?)/i, '').trim();
  const words = clean.split(/\s+/).filter(Boolean);
  if (words.length >= 2) {
    return words.map(w => w[0].toUpperCase()).join('').substring(0, 4);
  }
  return clean.substring(0, 3).toUpperCase();
}

function suggestNomorNota() {
  const tglVal = document.getElementById('nota-tanggal')?.value;
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

  const romawi = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
  const currentMonthRomawi = romawi[currentMonth];

  let highestNum = 0;
  notaList.forEach(n => {
    if (!n.tanggal) return;
    const partsTgl = n.tanggal.split('-');
    if (partsTgl.length === 3) {
      const yr = parseInt(partsTgl[0], 10);
      const mo = parseInt(partsTgl[1], 10) - 1;
      if (yr === currentYear && mo === currentMonth) {
        const partsNomor = n.nomor.split('/');
        if (partsNomor.length > 0) {
          const numPart = parseInt(partsNomor[0], 10);
          if (!isNaN(numPart) && numPart > highestNum) {
            highestNum = numPart;
          }
        }
      }
    }
  });

  const nextNum = highestNum + 1;
  const custVal = document.getElementById('nota-customer')?.value || '';
  const customerKode = getCustomerInitials(custVal);

  const suggested = `${nextNum}/ ${currentMonthRomawi} /TT-${customerKode}/${currentYear}`;
  const elNomor = document.getElementById('nota-nomor');
  if (elNomor) {
    elNomor.value = suggested;
  }
}

