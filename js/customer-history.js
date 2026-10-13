/**
 * customer-history.js — Filter & Grouping Version
 */

let ledgerList = [];
let currentSearch = '';
let ledgerEditId = null;
let activeName = null;

// Filter States
let selMonth = 'all';
let selYear = 'all';
let selCustomer = 'all';

document.addEventListener('DOMContentLoaded', async () => {
  try {
    await syncLocalToDB();
    ledgerList = await dbStore.get(KEYS.LEDGER) || [];
  } catch (err) {
    console.error('Init error:', err);
    ledgerList = [];
  }
  renderNameList();
  setupSearch();
  
  const modalLedger = document.getElementById('modal-ledger');
  if (modalLedger) {
    modalLedger.addEventListener('input', (e) => {
      const tr = e.target.closest('.item-row');
      if (!tr) return;
      
      if (
        e.target.classList.contains('f-20-item') || 
        e.target.classList.contains('f-21-item') || 
        e.target.classList.contains('f-28') || 
        e.target.classList.contains('c_nom')
      ) {
        recalculateSales(tr);
      }
      if (
        e.target.classList.contains('f-30-item') || 
        e.target.classList.contains('f-31-item') || 
        e.target.classList.contains('f-41-item') || 
        e.target.classList.contains('f-42-item') || 
        e.target.classList.contains('f-38') || 
        e.target.classList.contains('c_nom')
      ) {
        recalculatePurchase(tr);
      }
    });
  }
  
  // Default: Show all filtered view if needed, or keep current flow
  if (ledgerList.length > 0) {
     renderDetailPanel(); 
  }
});

function setupSearch() {
  const input = document.getElementById('search-customer');
  if (input) {
    input.addEventListener('input', (e) => {
      currentSearch = e.target.value.toLowerCase();
      renderNameList();
    });
  }
}

function getSalesItems(data) {
  if (data && data.items_sales && data.items_sales.length > 0) return data.items_sales;
  if (data && data.data) {
    const barang = data.data[18] || '';
    const qty = parseFloat(data.data[20]) || 0;
    const hrg = parseFloat(data.data[21]) || 0;
    const jumlah = parseFloat(data.data[22]) || 0;
    if (barang || qty || hrg || jumlah) {
      return [{ barang, qty, hrg, jumlah }];
    }
  }
  return [{ barang: '', qty: '', hrg: '', jumlah: '' }];
}

function getPurchaseItems(data) {
  if (data && data.items_purchase && data.items_purchase.length > 0) return data.items_purchase;
  if (data && data.data) {
    const barang = data.data[9] || '';
    const qty_ppn = parseFloat(data.data[30]) || 0;
    const hrg_ppn = parseFloat(data.data[31]) || 0;
    const jumlah_ppn = parseFloat(data.data[32]) || 0;
    const qty_non = parseFloat(data.data[41]) || 0;
    const jumlah_non = parseFloat(data.data[42]) || 0;
    if (barang || qty_ppn || hrg_ppn || qty_non || jumlah_non) {
      return [{ barang, qty_ppn, hrg_ppn, jumlah_ppn, qty_non, jumlah_non }];
    }
  }
  return [{ barang: '', qty_ppn: '', hrg_ppn: '', jumlah_ppn: '', qty_non: '', jumlah_non: '' }];
}

function addSubItemRowSales(btn) {
  const tr = btn.closest('.item-row');
  const containerBarang = tr.querySelector('.items-barang-sales');
  const containerQty = tr.querySelector('.items-qty-sales');
  const containerHrg = tr.querySelector('.items-hrg-sales');
  const containerJumlah = tr.querySelector('.items-jumlah-sales');
  
  const newIdx = containerBarang.children.length;
  
  const divBarang = document.createElement('div');
  divBarang.className = 'sub-item-row';
  divBarang.dataset.index = newIdx;
  divBarang.style.position = 'relative';
  divBarang.innerHTML = `
    <input type="text" class="excel-input f-18-item" value="" style="width:180px; padding-right:20px;" oninput="syncItemName(this)">
    <button type="button" class="btn-del-sub-item" onclick="delSubItemSales(this)" style="position: absolute; right: 2px; top: 50%; transform: translateY(-50%); border: none; background: none; color: #ef4444; cursor: pointer; font-size: 14px;">×</button>
  `;
  containerBarang.appendChild(divBarang);
  
  const divQty = document.createElement('div');
  divQty.className = 'sub-item-row';
  divQty.dataset.index = newIdx;
  divQty.innerHTML = `<input type="number" class="excel-input f-20-item" value="" style="width:50px">`;
  containerQty.appendChild(divQty);
  
  const divHrg = document.createElement('div');
  divHrg.className = 'sub-item-row';
  divHrg.dataset.index = newIdx;
  divHrg.innerHTML = `<input type="text" class="excel-input input-number f-21-item" value="" style="width:100px">`;
  containerHrg.appendChild(divHrg);
  
  const divJumlah = document.createElement('div');
  divJumlah.className = 'sub-item-row';
  divJumlah.dataset.index = newIdx;
  divJumlah.innerHTML = `<input type="text" class="excel-input input-number f-22-item" readonly value="" style="width:110px">`;
  containerJumlah.appendChild(divJumlah);
  
  if (typeof setupMasking === 'function') {
    setupMasking(divHrg);
    setupMasking(divJumlah);
  }
}

function delSubItemSales(btn) {
  const subRow = btn.closest('.sub-item-row');
  const index = Array.from(subRow.parentNode.children).indexOf(subRow);
  const tr = btn.closest('.item-row');
  
  tr.querySelector('.items-barang-sales').children[index]?.remove();
  tr.querySelector('.items-qty-sales').children[index]?.remove();
  tr.querySelector('.items-hrg-sales').children[index]?.remove();
  tr.querySelector('.items-jumlah-sales').children[index]?.remove();
  
  const updateIndexes = (container) => {
    Array.from(container.children).forEach((child, i) => {
      child.dataset.index = i;
    });
  };
  updateIndexes(tr.querySelector('.items-barang-sales'));
  updateIndexes(tr.querySelector('.items-qty-sales'));
  updateIndexes(tr.querySelector('.items-hrg-sales'));
  updateIndexes(tr.querySelector('.items-jumlah-sales'));
  
  recalculateSales(tr);
}

function addSubItemRowPurch(btn) {
  const tr = btn.closest('.item-row');
  const containerBarang = tr.querySelector('.items-barang-purch');
  const containerQtyPpn = tr.querySelector('.items-qty-purch-ppn');
  const containerHrgPpn = tr.querySelector('.items-hrg-purch-ppn');
  const containerJumlahPpn = tr.querySelector('.items-jumlah-purch-ppn');
  const containerQtyNon = tr.querySelector('.items-qty-purch-non');
  const containerJumlahNon = tr.querySelector('.items-jumlah-purch-non');
  
  const newIdx = containerBarang.children.length;
  
  const divBarang = document.createElement('div');
  divBarang.className = 'sub-item-row';
  divBarang.dataset.index = newIdx;
  divBarang.style.position = 'relative';
  divBarang.innerHTML = `
    <input type="text" class="excel-input f-9-item" value="" style="width:180px; padding-right:20px;">
    <button type="button" class="btn-del-sub-item" onclick="delSubItemPurch(this)" style="position: absolute; right: 2px; top: 50%; transform: translateY(-50%); border: none; background: none; color: #ef4444; cursor: pointer; font-size: 14px;">×</button>
  `;
  containerBarang.appendChild(divBarang);
  
  const divQtyPpn = document.createElement('div');
  divQtyPpn.className = 'sub-item-row';
  divQtyPpn.dataset.index = newIdx;
  divQtyPpn.innerHTML = `<input type="number" class="excel-input f-30-item" value="" style="width:50px">`;
  containerQtyPpn.appendChild(divQtyPpn);
  
  const divHrgPpn = document.createElement('div');
  divHrgPpn.className = 'sub-item-row';
  divHrgPpn.dataset.index = newIdx;
  divHrgPpn.innerHTML = `<input type="text" class="excel-input input-number f-31-item" value="" style="width:100px">`;
  containerHrgPpn.appendChild(divHrgPpn);
  
  const divJumlahPpn = document.createElement('div');
  divJumlahPpn.className = 'sub-item-row';
  divJumlahPpn.dataset.index = newIdx;
  divJumlahPpn.innerHTML = `<input type="text" class="excel-input input-number f-32-item" readonly value="" style="width:110px">`;
  containerJumlahPpn.appendChild(divJumlahPpn);
  
  const divQtyNon = document.createElement('div');
  divQtyNon.className = 'sub-item-row';
  divQtyNon.dataset.index = newIdx;
  divQtyNon.innerHTML = `<input type="number" class="excel-input f-41-item" value="" style="width:50px">`;
  containerQtyNon.appendChild(divQtyNon);
  
  const divJumlahNon = document.createElement('div');
  divJumlahNon.className = 'sub-item-row';
  divJumlahNon.dataset.index = newIdx;
  divJumlahNon.innerHTML = `<input type="text" class="excel-input input-number f-42-item" value="" style="width:110px">`;
  containerJumlahNon.appendChild(divJumlahNon);
  
  if (typeof setupMasking === 'function') {
    setupMasking(divHrgPpn);
    setupMasking(divJumlahNon);
  }
}

function delSubItemPurch(btn) {
  const subRow = btn.closest('.sub-item-row');
  const index = Array.from(subRow.parentNode.children).indexOf(subRow);
  const tr = btn.closest('.item-row');
  
  tr.querySelector('.items-barang-purch').children[index]?.remove();
  tr.querySelector('.items-qty-purch-ppn').children[index]?.remove();
  tr.querySelector('.items-hrg-purch-ppn').children[index]?.remove();
  tr.querySelector('.items-jumlah-purch-ppn').children[index]?.remove();
  tr.querySelector('.items-qty-purch-non').children[index]?.remove();
  tr.querySelector('.items-jumlah-purch-non').children[index]?.remove();
  
  const updateIndexes = (container) => {
    Array.from(container.children).forEach((child, i) => {
      child.dataset.index = i;
    });
  };
  updateIndexes(tr.querySelector('.items-barang-purch'));
  updateIndexes(tr.querySelector('.items-qty-purch-ppn'));
  updateIndexes(tr.querySelector('.items-hrg-purch-ppn'));
  updateIndexes(tr.querySelector('.items-jumlah-purch-ppn'));
  updateIndexes(tr.querySelector('.items-qty-purch-non'));
  updateIndexes(tr.querySelector('.items-jumlah-purch-non'));
  
  recalculatePurchase(tr);
}

function recalculateSales(row) {
  let totalJumlah = 0;
  const subRowsBarang = row.querySelectorAll('.items-barang-sales .sub-item-row');
  subRowsBarang.forEach((subRow, index) => {
    const qtyInput = row.querySelectorAll('.items-qty-sales .f-20-item')[index];
    const hrgInput = row.querySelectorAll('.items-hrg-sales .f-21-item')[index];
    const jumlahInput = row.querySelectorAll('.items-jumlah-sales .f-22-item')[index];
    
    const qty = parseFloat(qtyInput?.value) || 0;
    const hrg = fmt.unmask(hrgInput?.value) || 0;
    const jumlah = qty * hrg;
    
    if (jumlahInput) jumlahInput.value = fmt.formatInput(jumlah);
    totalJumlah += jumlah;
  });
  
  const tagihanInput = row.querySelector('.f-19');
  if (tagihanInput) tagihanInput.value = fmt.formatInput(totalJumlah);
  
  const ppnInput = row.querySelector('.f-23');
  const ppn = Math.round(totalJumlah * 0.11);
  if (ppnInput) ppnInput.value = fmt.formatInput(ppn);
  
  const sthPpnInput = row.querySelector('.f-24');
  const sthPpn = totalJumlah + ppn;
  if (sthPpnInput) sthPpnInput.value = fmt.formatInput(sthPpn);
  
  const titipan = fmt.unmask(row.querySelector('.f-28')?.value) || 0;
  
  let totalCicilan = 0;
  row.querySelectorAll('.cic_sales_list .c_nom').forEach(cInput => {
    totalCicilan += fmt.unmask(cInput.value) || 0;
  });
  
  const utangInput = row.querySelector('.f-29');
  if (utangInput) {
    const baseAmount = sthPpn > 0 ? sthPpn : totalJumlah;
    const utang = baseAmount - titipan - totalCicilan;
    utangInput.value = fmt.formatInput(utang);
  }
}

function recalculatePurchase(row) {
  let totalJumlahPpn = 0;
  const subRowsBarangPpn = row.querySelectorAll('.items-barang-purch .sub-item-row');
  subRowsBarangPpn.forEach((subRow, index) => {
    const qtyInput = row.querySelectorAll('.items-qty-purch-ppn .f-30-item')[index];
    const hrgInput = row.querySelectorAll('.items-hrg-purch-ppn .f-31-item')[index];
    const jumlahInput = row.querySelectorAll('.items-jumlah-purch-ppn .f-32-item')[index];
    
    const qty = parseFloat(qtyInput?.value) || 0;
    const hrg = fmt.unmask(hrgInput?.value) || 0;
    const jumlah = qty * hrg;
    
    if (jumlahInput) jumlahInput.value = fmt.formatInput(jumlah);
    totalJumlahPpn += jumlah;
  });
  
  let totalJumlahNon = 0;
  subRowsBarangPpn.forEach((subRow, index) => {
    const qtyInput = row.querySelectorAll('.items-qty-purch-non .f-41-item')[index];
    const jumlahInput = row.querySelectorAll('.items-jumlah-purch-non .f-42-item')[index];
    
    const qty = parseFloat(qtyInput?.value) || 0;
    const jumlah = fmt.unmask(jumlahInput?.value) || 0;
    totalJumlahNon += jumlah;
  });
  
  const tagihanInput = row.querySelector('.f-10');
  const totalPurch = totalJumlahPpn > 0 ? totalJumlahPpn : totalJumlahNon;
  if (tagihanInput) tagihanInput.value = fmt.formatInput(totalPurch);
  
  const ppnInput = row.querySelector('.f-33');
  const ppn = Math.round(totalJumlahPpn * 0.11);
  if (ppnInput) ppnInput.value = fmt.formatInput(ppn);
  
  const sthPpnInput = row.querySelector('.f-34');
  const sthPpn = totalJumlahPpn + ppn;
  if (sthPpnInput) sthPpnInput.value = fmt.formatInput(sthPpn);
  
  const totalNonInput = row.querySelector('.f-43');
  if (totalNonInput) totalNonInput.value = fmt.formatInput(totalJumlahNon);
  
  const titipanPpn = fmt.unmask(row.querySelector('.f-38')?.value) || 0;
  let totalCicilanPurch = 0;
  row.querySelectorAll('.cic_purch_list .c_nom').forEach(cInput => {
    totalCicilanPurch += fmt.unmask(cInput.value) || 0;
  });
  
  const utangInput = row.querySelector('.f-40');
  if (utangInput) {
    const baseAmount = sthPpn > 0 ? sthPpn : totalJumlahPpn;
    const utang = baseAmount - titipanPpn - totalCicilanPurch;
    if (baseAmount > 0) {
      utangInput.value = fmt.formatInput(utang);
    }
  }
}

async function saveLedger() {
  const allRecords = [];
  document.querySelectorAll('#items-list-unified .item-row').forEach(row => {
    const rec = { id: uid(), data: [] };
    for(let i=1; i<=45; i++) {
      const el = row.querySelector(`.f-${i}`);
      if (el) rec.data[i] = el.classList.contains('input-number') ? fmt.unmask(el.value) : el.value;
      else rec.data[i] = '';
    }
    
    rec.cicilan_sales = [];
    row.querySelectorAll('.cic_sales_list .cicilan-mini-row').forEach(cRow => {
      rec.cicilan_sales.push({ tgl: cRow.querySelector('.c_tgl').value, nominal: fmt.unmask(cRow.querySelector('.c_nom').value) });
    });
    rec.cicilan_purchase = [];
    row.querySelectorAll('.cic_purch_list .cicilan-mini-row').forEach(cRow => {
      rec.cicilan_purchase.push({ tgl: cRow.querySelector('.c_tgl').value, nominal: fmt.unmask(cRow.querySelector('.c_nom').value) });
    });

    rec.items_sales = [];
    row.querySelectorAll('.items-barang-sales .sub-item-row').forEach((sRow, index) => {
      const barang = sRow.querySelector('.f-18-item').value;
      const qty = parseFloat(row.querySelectorAll('.items-qty-sales .f-20-item')[index]?.value) || 0;
      const hrg = fmt.unmask(row.querySelectorAll('.items-hrg-sales .f-21-item')[index]?.value) || 0;
      const jumlah = fmt.unmask(row.querySelectorAll('.items-jumlah-sales .f-22-item')[index]?.value) || 0;
      if (barang || qty || hrg || jumlah) {
        rec.items_sales.push({ barang, qty, hrg, jumlah });
      }
    });

    rec.items_purchase = [];
    row.querySelectorAll('.items-barang-purch .sub-item-row').forEach((pRow, index) => {
      const barang = pRow.querySelector('.f-9-item').value;
      const qty_ppn = parseFloat(row.querySelectorAll('.items-qty-purch-ppn .f-30-item')[index]?.value) || 0;
      const hrg_ppn = fmt.unmask(row.querySelectorAll('.items-hrg-purch-ppn .f-31-item')[index]?.value) || 0;
      const jumlah_ppn = fmt.unmask(row.querySelectorAll('.items-jumlah-purch-ppn .f-32-item')[index]?.value) || 0;
      const qty_non = parseFloat(row.querySelectorAll('.items-qty-purch-non .f-41-item')[index]?.value) || 0;
      const jumlah_non = fmt.unmask(row.querySelectorAll('.items-jumlah-purch-non .f-42-item')[index]?.value) || 0;
      if (barang || qty_ppn || hrg_ppn || jumlah_ppn || qty_non || jumlah_non) {
        rec.items_purchase.push({ barang, qty_ppn, hrg_ppn, jumlah_ppn, qty_non, jumlah_non });
      }
    });

    if (rec.items_sales.length > 0) {
      rec.data[18] = rec.items_sales.map(it => it.barang).filter(Boolean).join(', ');
      rec.data[20] = rec.items_sales.reduce((sum, it) => sum + (it.qty || 0), 0);
      rec.data[21] = rec.items_sales[0]?.hrg || 0;
      rec.data[22] = rec.items_sales.reduce((sum, it) => sum + (it.jumlah || 0), 0);
    }
    if (rec.items_purchase.length > 0) {
      rec.data[9] = rec.items_purchase.map(it => it.barang).filter(Boolean).join(', ');
      rec.data[30] = rec.items_purchase.reduce((sum, it) => sum + (it.qty_ppn || 0), 0);
      rec.data[31] = rec.items_purchase[0]?.hrg_ppn || 0;
      rec.data[32] = rec.items_purchase.reduce((sum, it) => sum + (it.jumlah_ppn || 0), 0);
      rec.data[41] = rec.items_purchase.reduce((sum, it) => sum + (it.qty_non || 0), 0);
      rec.data[42] = rec.items_purchase.reduce((sum, it) => sum + (it.jumlah_non || 0), 0);
    }

    rec.nama = rec.data[13] || rec.data[3] || 'Tanpa Nama';
    rec.tanggal = rec.data[12] || rec.data[2] || new Date().toISOString().split('T')[0];
    
    if (rec.data[30] || rec.items_purchase.some(it => it.qty_ppn)) rec.type = 'purchase_ppn';
    else if (rec.data[41] || rec.items_purchase.some(it => it.qty_non)) rec.type = 'purchase_non_ppn';
    else rec.type = 'sales';
    allRecords.push(rec);
  });

  if (ledgerEditId) {
    const idx = ledgerList.findIndex(x => x.id === ledgerEditId);
    if (idx > -1) ledgerList.splice(idx, 1, ...allRecords);
  } else {
    ledgerList = [...allRecords, ...ledgerList];
  }

  await dbStore.set(KEYS.LEDGER, ledgerList);
  showToast('Data disimpan', 'success');
  document.getElementById('modal-ledger').classList.remove('open');
  renderNameList();
  renderDetailPanel();
}

function addItemRowUnified(data = null) {
  const container = document.getElementById('items-list-unified');
  if (!container) return;

  let lastRow = null;
  if (!data) {
    const rows = container.querySelectorAll('.item-row');
    if (rows.length > 0) lastRow = rows[rows.length - 1];
  }

  const tr = document.createElement('tr');
  tr.className = 'item-row';
  
  const getVal = (idx) => {
    if (data) return data.data[idx] || '';
    if (lastRow) {
      if ([1,2,3,4,5,6,7,8, 11,12,13,14,15,16,17].includes(idx)) {
        return lastRow.querySelector(`.f-${idx}`)?.value || '';
      }
    }
    if (!lastRow && [2, 12].includes(idx)) return fmt.toInputDate(new Date());
    return '';
  };

  const salesItems = getSalesItems(data);
  const purchaseItems = getPurchaseItems(data);

  const barangSalesHtml = salesItems.map((it, idx) => `
    <div class="sub-item-row" data-index="${idx}" style="position: relative;">
      <input type="text" class="excel-input f-18-item" value="${esc(it.barang || '')}" style="width:180px; padding-right:20px;" oninput="syncItemName(this)">
      ${idx > 0 ? `<button type="button" class="btn-del-sub-item" onclick="delSubItemSales(this)" style="position: absolute; right: 2px; top: 50%; transform: translateY(-50%); border: none; background: none; color: #ef4444; cursor: pointer; font-size: 14px;">×</button>` : ''}
    </div>
  `).join('');

  const qtySalesHtml = salesItems.map((it, idx) => `
    <div class="sub-item-row" data-index="${idx}">
      <input type="number" class="excel-input f-20-item" value="${esc(it.qty || '')}" style="width:50px">
    </div>
  `).join('');

  const hrgSalesHtml = salesItems.map((it, idx) => `
    <div class="sub-item-row" data-index="${idx}">
      <input type="text" class="excel-input input-number f-21-item" value="${it.hrg ? fmt.formatInput(it.hrg) : ''}" style="width:100px">
    </div>
  `).join('');

  const jumlahSalesHtml = salesItems.map((it, idx) => `
    <div class="sub-item-row" data-index="${idx}">
      <input type="text" class="excel-input input-number f-22-item" readonly value="${it.jumlah ? fmt.formatInput(it.jumlah) : ''}" style="width:110px">
    </div>
  `).join('');

  const barangPurchHtml = purchaseItems.map((it, idx) => `
    <div class="sub-item-row" data-index="${idx}" style="position: relative;">
      <input type="text" class="excel-input f-9-item" value="${esc(it.barang || '')}" style="width:180px; padding-right:20px;">
      ${idx > 0 ? `<button type="button" class="btn-del-sub-item" onclick="delSubItemPurch(this)" style="position: absolute; right: 2px; top: 50%; transform: translateY(-50%); border: none; background: none; color: #ef4444; cursor: pointer; font-size: 14px;">×</button>` : ''}
    </div>
  `).join('');

  const qtyPurchPpnHtml = purchaseItems.map((it, idx) => `
    <div class="sub-item-row" data-index="${idx}">
      <input type="number" class="excel-input f-30-item" value="${esc(it.qty_ppn || '')}" style="width:50px">
    </div>
  `).join('');

  const hrgPurchPpnHtml = purchaseItems.map((it, idx) => `
    <div class="sub-item-row" data-index="${idx}">
      <input type="text" class="excel-input input-number f-31-item" value="${it.hrg_ppn ? fmt.formatInput(it.hrg_ppn) : ''}" style="width:100px">
    </div>
  `).join('');

  const jumlahPurchPpnHtml = purchaseItems.map((it, idx) => `
    <div class="sub-item-row" data-index="${idx}">
      <input type="text" class="excel-input input-number f-32-item" readonly value="${it.jumlah_ppn ? fmt.formatInput(it.jumlah_ppn) : ''}" style="width:110px">
    </div>
  `).join('');

  const qtyPurchNonHtml = purchaseItems.map((it, idx) => `
    <div class="sub-item-row" data-index="${idx}">
      <input type="number" class="excel-input f-41-item" value="${esc(it.qty_non || '')}" style="width:50px">
    </div>
  `).join('');

  const jumlahPurchNonHtml = purchaseItems.map((it, idx) => `
    <div class="sub-item-row" data-index="${idx}">
      <input type="text" class="excel-input input-number f-42-item" value="${it.jumlah_non ? fmt.formatInput(it.jumlah_non) : ''}" style="width:110px">
    </div>
  `).join('');

  tr.innerHTML = `
    <td style="text-align:center; border-right:1px solid #ddd; vertical-align:middle">
      <button type="button" onclick="this.closest('tr').remove()" style="border:none; background:none; color:var(--red-400); cursor:pointer">🗑️</button>
    </td>
    <!-- SALES COLS (11-29) -->
    <td style="vertical-align:middle"><input type="text" class="f-11 excel-input" value="${esc(getVal(11))}" style="width:50px"></td>
    <td style="vertical-align:middle"><input type="date" class="f-12 excel-input" value="${esc(getVal(12))}" style="width:125px"></td>
    <td style="vertical-align:middle"><input type="text" class="f-13 excel-input" value="${esc(getVal(13))}" style="width:180px"></td>
    <td style="vertical-align:middle"><input type="text" class="f-14 excel-input" value="${esc(getVal(14))}" style="width:200px"></td>
    <td style="vertical-align:middle"><input type="text" class="f-15 excel-input" value="${esc(getVal(15))}" style="width:110px"></td>
    <td style="vertical-align:middle"><input type="text" class="f-16 excel-input" value="${esc(getVal(16))}" style="width:100px"></td>
    <td style="vertical-align:middle"><input type="text" class="f-17 excel-input" value="${esc(getVal(17))}" style="width:100px"></td>
    <td style="padding: 0 !important; vertical-align: top;">
      <div class="items-barang-sales">${barangSalesHtml}</div>
      <div style="padding: 4px; text-align: center;">
        <button type="button" class="btn-mini-add" onclick="addSubItemRowSales(this)">+ Barang</button>
      </div>
    </td>
    <td style="vertical-align:middle"><input type="text" class="f-19 excel-input input-number" value="${data ? fmt.formatInput(data.data[19]) : ''}" style="width:100px"></td>
    <td style="padding: 0 !important; vertical-align: top;"><div class="items-qty-sales">${qtySalesHtml}</div></td>
    <td style="padding: 0 !important; vertical-align: top;"><div class="items-hrg-sales">${hrgSalesHtml}</div></td>
    <td style="padding: 0 !important; vertical-align: top;"><div class="items-jumlah-sales">${jumlahSalesHtml}</div></td>
    <td style="vertical-align:middle"><input type="text" class="f-23 excel-input input-number" value="${data ? fmt.formatInput(data.data[23]) : ''}" style="width:100px"></td>
    <td style="vertical-align:middle"><input type="text" class="f-24 excel-input input-number" value="${data ? fmt.formatInput(data.data[24]) : ''}" style="width:110px"></td>
    <td style="vertical-align:middle"><input type="text" class="f-25 excel-input" value="${esc(getVal(25))}" style="width:100px"></td>
    <td style="vertical-align:middle"><input type="text" class="f-26 excel-input input-number" value="${data ? fmt.formatInput(data.data[26]) : ''}" style="width:100px"></td>
    <td style="vertical-align:middle"><input type="date" class="f-27 excel-input" value="${esc(getVal(27))}" style="width:125px"></td>
    <td style="vertical-align:middle"><input type="text" class="f-28 excel-input input-number" value="${data ? fmt.formatInput(data.data[28]) : ''}" style="width:100px"></td>
    <td style="vertical-align:middle"><input type="text" class="f-29 excel-input input-number" value="${data ? fmt.formatInput(data.data[29]) : ''}" style="width:100px"></td>
    <td style="background:var(--am-50); min-width:220px; vertical-align:middle"><div class="cic_sales_list"></div><button type="button" class="btn-mini-add" onclick="addMiniCicilan(this, 'sales')">+ Cicilan</button></td>

    <!-- PURCH COLS (1-10) -->
    <td style="vertical-align:middle"><input type="text" class="f-1 excel-input" value="${esc(getVal(1))}" style="width:50px"></td>
    <td style="vertical-align:middle"><input type="date" class="f-2 excel-input" value="${esc(getVal(2))}" style="width:125px"></td>
    <td style="vertical-align:middle"><input type="text" class="f-3 excel-input" value="${esc(getVal(3))}" style="width:180px"></td>
    <td style="vertical-align:middle"><input type="text" class="f-4 excel-input" value="${esc(getVal(4))}" style="width:180px"></td>
    <td style="vertical-align:middle"><input type="text" class="f-5 excel-input" value="${esc(getVal(5))}" style="width:150px"></td>
    <td style="vertical-align:middle"><input type="text" class="f-6 excel-input" value="${esc(getVal(6))}" style="width:120px"></td>
    <td style="vertical-align:middle"><input type="date" class="f-7 excel-input" value="${esc(getVal(7))}" style="width:125px"></td>
    <td style="vertical-align:middle"><input type="text" class="f-8 excel-input" value="${esc(getVal(8))}" style="width:120px"></td>
    <td style="padding: 0 !important; vertical-align: top;">
      <div class="items-barang-purch">${barangPurchHtml}</div>
      <div style="padding: 4px; text-align: center;">
        <button type="button" class="btn-mini-add" onclick="addSubItemRowPurch(this)">+ Barang</button>
      </div>
    </td>
    <td style="vertical-align:middle"><input type="text" class="f-10 excel-input input-number" value="${data ? fmt.formatInput(data.data[10]) : ''}" style="width:110px"></td>
    <td style="background:var(--am-50); min-width:220px; vertical-align:middle"><div class="cic_purch_list"></div><button type="button" class="btn-mini-add" onclick="addMiniCicilan(this, 'purch')">+ Cicilan</button></td>

    <!-- PPN (30-40) -->
    <td style="padding: 0 !important; vertical-align: top;"><div class="items-qty-purch-ppn">${qtyPurchPpnHtml}</div></td>
    <td style="padding: 0 !important; vertical-align: top;"><div class="items-hrg-purch-ppn">${hrgPurchPpnHtml}</div></td>
    <td style="padding: 0 !important; vertical-align: top;"><div class="items-jumlah-purch-ppn">${jumlahPurchPpnHtml}</div></td>
    <td style="vertical-align:middle"><input type="text" class="f-33 excel-input input-number" value="${data ? fmt.formatInput(data.data[33]) : ''}" style="width:100px"></td>
    <td style="vertical-align:middle"><input type="text" class="f-34 excel-input input-number" value="${data ? fmt.formatInput(data.data[34]) : ''}" style="width:110px"></td>
    <td style="vertical-align:middle"><input type="text" class="f-35 excel-input" value="${esc(getVal(35))}" style="width:100px"></td>
    <td style="vertical-align:middle"><input type="text" class="f-36 excel-input input-number" value="${data ? fmt.formatInput(data.data[36]) : ''}" style="width:100px"></td>
    <td style="vertical-align:middle"><input type="date" class="f-37 excel-input" value="${esc(getVal(37))}" style="width:125px"></td>
    <td style="vertical-align:middle"><input type="text" class="f-38 excel-input input-number" value="${data ? fmt.formatInput(data.data[38]) : ''}" style="width:100px"></td>
    <td style="vertical-align:middle"><input type="text" class="f-39 excel-input input-number" value="${data ? fmt.formatInput(data.data[39]) : ''}" style="width:100px"></td>
    <td style="vertical-align:middle"><input type="text" class="f-40 excel-input input-number" value="${data ? fmt.formatInput(data.data[40]) : ''}" style="width:100px"></td>

    <!-- NON (41-45) -->
    <td style="padding: 0 !important; vertical-align: top;"><div class="items-qty-purch-non">${qtyPurchNonHtml}</div></td>
    <td style="padding: 0 !important; vertical-align: top;"><div class="items-jumlah-purch-non">${jumlahPurchNonHtml}</div></td>
    <td style="vertical-align:middle"><input type="text" class="f-43 excel-input input-number" value="${data ? fmt.formatInput(data.data[43]) : ''}" style="width:100px"></td>
    <td style="vertical-align:middle"><input type="text" class="f-44 excel-input" value="${esc(getVal(44))}" style="width:100px"></td>
    <td style="vertical-align:middle"><input type="text" class="f-45 excel-input input-number" value="${data ? fmt.formatInput(data.data[45]) : ''}" style="width:100px"></td>
  `;

  if (data) {
    (data.cicilan_sales || []).forEach(c => addMiniCicilan(tr.querySelector('.cic_sales_list'), 'sales', c));
    (data.cicilan_purchase || []).forEach(c => addMiniCicilan(tr.querySelector('.cic_purch_list'), 'purch', c));
  }
  container.appendChild(tr);
  if (typeof setupMasking === 'function') setupMasking(tr);
}

function addMiniCicilan(target, type, data = null) {
  const list = target.tagName === 'BUTTON' ? target.previousElementSibling : target;
  const div = document.createElement('div');
  div.className = 'cicilan-mini-row';
  div.style = 'display:flex; gap:4px; margin-bottom:4px';
  div.innerHTML = `
    <input type="date" class="c_tgl" value="${data ? data.tgl : ''}" style="width:105px; font-size:10px; padding:2px; border:1px solid #ddd">
    <input type="text" class="c_nom input-number" value="${data ? fmt.formatInput(data.nominal) : ''}" style="width:80px; font-size:10px; padding:2px; border:1px solid #ddd">
    <button type="button" onclick="const tr = this.closest('.item-row'); this.parentElement.remove(); if (tr) { recalculateSales(tr); recalculatePurchase(tr); }" style="border:none; background:none; color:#ccc; cursor:pointer">×</button>
  `;
  list.appendChild(div);
  if (typeof setupMasking === 'function') setupMasking(div);
}

function syncItemName(input) {
  const tr = input.closest('tr');
  const purInput = tr.querySelector('.f-9');
  if (purInput) purInput.value = input.value;
}

function openNewLedger() {
  ledgerEditId = null;
  document.getElementById('modal-ledger-title').textContent = 'Tambah Transaksi Terpadu';
  const itemsList = document.getElementById('items-list-unified');
  if (itemsList) itemsList.innerHTML = '';
  addItemRowUnified();
  document.getElementById('modal-ledger').classList.add('open');
}

function editLedger(id) {
  const l = ledgerList.find(x => x.id === id);
  if (!l) return;
  ledgerEditId = id;
  document.getElementById('modal-ledger-title').innerText = 'Edit Transaksi Terpadu';
  const itemsList = document.getElementById('items-list-unified');
  if (itemsList) itemsList.innerHTML = '';
  addItemRowUnified(l);
  document.getElementById('modal-ledger').classList.add('open');
}

function getInitials(name) {
  if (!name) return '??';
  let cleanName = name.replace(/^(PT|CV|UD|PD|TB)\.?\s+/i, '');
  const words = cleanName.split(/\s+/).filter(Boolean);
  if (words.length >= 2) {
    return (words[0][0] + words[1][0]).toUpperCase();
  }
  return cleanName.substring(0, 2).toUpperCase();
}

function toggleCustomerSidebar() {
  const wrap = document.getElementById('list-wrap-cus');
  const sidebar = document.getElementById('sidebar-cus');
  if (wrap && sidebar) {
    wrap.classList.toggle('sidebar-collapsed');
    sidebar.classList.toggle('collapsed');
  }
}

function renderNameList() {
  const listEl = document.getElementById('customer-sidebar-list');
  if (!listEl) return;
  const names = [...new Set(ledgerList.map(l => l.nama))].sort();
  const filtered = names.filter(n => n.toLowerCase().includes(currentSearch));
  listEl.innerHTML = filtered.map(name => {
    const initials = getInitials(name);
    return `<div class="customer-item ${selCustomer === name ? 'active' : ''}" data-name="${esc(name)}" onclick="selectCustomer(this.dataset.name)" title="${esc(name)}">
      <div class="code-row">
        <span class="badge-code">${initials}</span>
      </div>
      <div class="name">${esc(name)}</div>
    </div>`;
  }).join('');
}

function selectCustomer(name) {
  selCustomer = name;
  renderNameList();
  renderDetailPanel();
}

function renderDetailPanel() {
  const panel = document.getElementById('history-detail-panel');
  if (!panel) return;

  // Prepare Master List for Filter
  const allNames = [...new Set(ledgerList.map(l => l.nama))].sort();
  const allYears = [...new Set(ledgerList.map(l => (l.tanggal || '').split('-')[0]))].sort((a,b) => b-a);
  const months = ["Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];

  // Filter Logic
  const filtered = ledgerList.filter(l => {
    const matchCust = selCustomer === 'all' || l.nama === selCustomer;
    const [y, m] = (l.tanggal || '').split('-');
    const matchMonth = selMonth === 'all' || parseInt(m) === parseInt(selMonth);
    const matchYear = selYear === 'all' || y === selYear;
    return matchCust && matchMonth && matchYear;
  }).sort((a,b) => new Date(b.tanggal) - new Date(a.tanggal));

  panel.innerHTML = `
    <div class="history-header">
      <div class="cus-identity-card" style="margin-bottom:0">
        <div style="flex:1">
          <h2 style="margin:0">${selCustomer === 'all' ? 'Semua Transaksi' : esc(selCustomer)}</h2>
          <p style="margin:4px 0 0">Pencarian data riwayat terpadu</p>
        </div>
        <div style="display:flex; gap:10px">
          <button class="btn btn-ghost btn-sm" onclick="window.print()" style="color:#fff;border-color:rgba(255,255,255,.55);background:rgba(255,255,255,.08)">🖨️ Cetak</button>
          <button class="btn-close-drawer" onclick="closeHistoryDrawer()">×</button>
        </div>
      </div>
      
      <!-- FILTER BAR -->
      <div style="background:#fff; padding:15px 25px; border-bottom:1px solid #e2e8f0; display:flex; gap:15px; align-items:center; flex-wrap:wrap">
        <div style="flex:1; min-width:200px">
          <label style="display:block; font-size:9px; font-weight:800; color:var(--navy-400); margin-bottom:4px">MASTER CUSTOMER</label>
          <select class="excel-input" style="border:1px solid #ddd; border-radius:6px; height:35px" onchange="selCustomer=this.value; renderDetailPanel(); renderNameList()">
            <option value="all">-- Semua Customer --</option>
            ${allNames.map(n => `<option value="${esc(n)}" ${selCustomer===n ? 'selected':''}>${esc(n)}</option>`).join('')}
          </select>
        </div>
        <div style="width:150px">
          <label style="display:block; font-size:9px; font-weight:800; color:var(--navy-400); margin-bottom:4px">BULAN</label>
          <select class="excel-input" style="border:1px solid #ddd; border-radius:6px; height:35px" onchange="selMonth=this.value; renderDetailPanel()">
            <option value="all">-- Semua --</option>
            ${months.map((m, i) => `<option value="${i+1}" ${parseInt(selMonth)===i+1 ? 'selected':''}>${m}</option>`).join('')}
          </select>
        </div>
        <div style="width:120px">
          <label style="display:block; font-size:9px; font-weight:800; color:var(--navy-400); margin-bottom:4px">TAHUN</label>
          <select class="excel-input" style="border:1px solid #ddd; border-radius:6px; height:35px" onchange="selYear=this.value; renderDetailPanel()">
            <option value="all">-- Semua --</option>
            ${allYears.map(y => `<option value="${y}" ${selYear===y ? 'selected':''}>${y}</option>`).join('')}
          </select>
        </div>
        <button class="btn btn-sm btn-ghost" onclick="selCustomer='all'; selMonth='all'; selYear='all'; renderDetailPanel(); renderNameList()" style="margin-top:14px; color:var(--red-500)">Reset</button>
      </div>
    </div>
    
    <div class="ledger-table-container" style="overflow-x:auto; padding:0; flex:1">
      ${renderUnifiedTable(filtered)}
    </div>
  `;
  panel.classList.add('open');
}

function renderUnifiedTable(data) {
  if (data.length === 0) return '<div style="padding:80px; text-align:center; color:var(--navy-300)"><h3>Tidak ada data</h3><p>Coba ubah filter pencarian Anda</p></div>';
  
  let currentMonthGroup = "";
  const rowsHtml = data.map(l => {
    // Month Grouping
    const dateObj = new Date(l.tanggal);
    const monthName = dateObj.toLocaleString('id-ID', { month: 'long', year: 'numeric' }).toUpperCase();
    let groupHeader = "";
    if (monthName !== currentMonthGroup) {
      currentMonthGroup = monthName;
      groupHeader = `<tr class="group-header"><td colspan="50" style="background:#f1f5f9; padding:10px 25px; font-weight:800; color:var(--navy-600); border-bottom:2px solid #cbd5e1">📅 ${monthName}</td></tr>`;
    }

    const salesItems = getSalesItems(l);
    const purchaseItems = getPurchaseItems(l);
    const rowSpanCount = Math.max(1, salesItems.length, purchaseItems.length);

    const colOrder = [11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,'cic_sales',1,2,3,4,5,6,7,8,9,10,'cic_purch',30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45];

    const trs = [];
    for (let r = 0; r < rowSpanCount; r++) {
      const isFirst = r === 0;
      const sItem = salesItems[r] || null;
      const pItem = purchaseItems[r] || null;
      
      const tds = [];
      
      colOrder.forEach(idx => {
        const isSalesItemCol = [18, 20, 21, 22].includes(idx);
        const isPurchItemCol = [9, 30, 31, 32, 41, 42].includes(idx);
        
        if (isSalesItemCol) {
          if (idx === 18) {
            tds.push(`<td>${esc(sItem ? sItem.barang : '')}</td>`);
          } else if (idx === 20) {
            tds.push(`<td class="text-right">${sItem && sItem.qty ? fmt.number(sItem.qty) : ''}</td>`);
          } else if (idx === 21) {
            tds.push(`<td class="text-right">${sItem && sItem.hrg ? fmt.number(sItem.hrg) : ''}</td>`);
          } else if (idx === 22) {
            tds.push(`<td class="text-right">${sItem && sItem.jumlah ? fmt.number(sItem.jumlah) : ''}</td>`);
          }
        } else if (isPurchItemCol) {
          if (idx === 9) {
            tds.push(`<td>${esc(pItem ? pItem.barang : '')}</td>`);
          } else if (idx === 30) {
            tds.push(`<td class="text-right">${pItem && pItem.qty_ppn ? fmt.number(pItem.qty_ppn) : ''}</td>`);
          } else if (idx === 31) {
            tds.push(`<td class="text-right">${pItem && pItem.hrg_ppn ? fmt.number(pItem.hrg_ppn) : ''}</td>`);
          } else if (idx === 32) {
            tds.push(`<td class="text-right">${pItem && pItem.jumlah_ppn ? fmt.number(pItem.jumlah_ppn) : ''}</td>`);
          } else if (idx === 41) {
            tds.push(`<td class="text-right">${pItem && pItem.qty_non ? fmt.number(pItem.qty_non) : ''}</td>`);
          } else if (idx === 42) {
            tds.push(`<td class="text-right">${pItem && pItem.jumlah_non ? fmt.number(pItem.jumlah_non) : ''}</td>`);
          }
        } else {
          if (isFirst) {
            const rowspanAttr = rowSpanCount > 1 ? ` rowspan="${rowSpanCount}"` : '';
            if (idx === 'cic_sales' || idx === 'cic_purch') {
              const list = idx === 'cic_sales' ? (l.cicilan_sales || []) : (l.cicilan_purchase || []);
              const total = list.reduce((sum, c) => sum + (parseFloat(c.nominal) || 0), 0);
              if (total === 0) {
                tds.push(`<td class="text-center"${rowspanAttr} style="color:#cbd5e1">-</td>`);
              } else {
                tds.push(`<td class="text-right"${rowspanAttr} style="background:var(--am-50)"><span style="font-weight:700; color:var(--am-600)">${fmt.number(total)}</span></td>`);
              }
            } else {
              const val = l.data[idx] || '';
              const isNum = [10,19,23,24,25,26,28,29,33,34,35,36,38,39,40,43,44,45].includes(idx);
              tds.push(`<td class="${isNum ? 'text-right' : ''}"${rowspanAttr}>${isNum ? fmt.number(val) : esc(val)}</td>`);
            }
          }
        }
      });
      
      if (isFirst) {
        const rowspanAttr = rowSpanCount > 1 ? ` rowspan="${rowSpanCount}"` : '';
        tds.push(`<td class="td-actions"${rowspanAttr} style="position:sticky; right:0; background:white; z-index:25"><button class="btn btn-icon btn-sm" onclick="editLedger('${esc(l.id)}')">✏️</button><button class="btn btn-icon btn-sm" style="color:var(--red-500)" onclick="deleteLedger('${esc(l.id)}')">🗑️</button></td>`);
      }
      
      trs.push(`<tr>${tds.join('')}</tr>`);
    }

    return groupHeader + trs.join('');
  }).join('');

  return `<table class="ledger-grid">
    <thead>
      <tr><th colspan="20" class="th-sales">PENJUALAN</th><th colspan="11" class="th-purch-start">PEMBELIAN (AWAL)</th><th colspan="11" class="th-purch-ppn">PEMBELIAN PPN</th><th colspan="5" class="th-purch-non">PEMBELIAN NON PPN</th><th rowspan="2" style="position:sticky; right:0; background:var(--navy-50); color:var(--navy-700); z-index:30">AKSI</th></tr>
      <tr class="sub-header"><th>NO</th><th>TGL</th><th>NAMA</th><th>ALAMAT</th><th>TELP</th><th>NO PO</th><th>SERI F.PJK</th><th>BARANG</th><th>TAGIHAN</th><th>QTY</th><th>HRG</th><th>JUMLAH</th><th>PPN</th><th>STH PPN</th><th>BANK</th><th>SELISIH</th><th>TGL BAYAR</th><th>TITIPAN</th><th>UTANG</th><th style="background:var(--am-100) !important; color:var(--am-600)">CICILAN</th><th>NO</th><th>TGL</th><th>NAMA</th><th>ALAMAT</th><th>NPWP</th><th>NOTA/SJ</th><th>TGL KIRIM</th><th>SERI F.PJK</th><th>BARANG</th><th>TAGIHAN</th><th style="background:var(--am-100) !important; color:var(--am-600)">CICILAN</th><th>QTY</th><th>HRG</th><th>JUMLAH</th><th>PPN 11%</th><th>STH PPN</th><th>BANK</th><th>TUNAI</th><th>TGL</th><th>TITIPAN</th><th>BY ADM</th><th>UTANG</th><th>QTY</th><th>JUMLAH</th><th>TOTAL</th><th>BANK</th><th>TUNAI</th></tr>
    </thead>
    <tbody>
      ${rowsHtml}
    </tbody>
  </table>`;
}

function closeHistoryDrawer() {
  document.getElementById('history-detail-panel').classList.remove('open');
  selCustomer = 'all';
  renderNameList();
}

async function deleteLedger(id) {
  if (!confirm('Hapus data ini?')) return;
  ledgerList = ledgerList.filter(x => x.id !== id);
  await dbStore.set(KEYS.LEDGER, ledgerList);
  renderNameList();
  renderDetailPanel();
}
