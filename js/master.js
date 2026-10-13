/**
 * master.js — Master Data Perusahaan
 */

let activeTab = 'supplier';
let supplierList = [];
let customerList = [];

document.addEventListener('DOMContentLoaded', async () => {
  // Sync first
  await syncLocalToDB();
  
  supplierList = await dbStore.get(KEYS.SUPPLIERS);
  customerList = await dbStore.get(KEYS.CUSTOMERS);

  renderSupplierTable();
  renderCustomerTable();
  setupMasterForms();
  setupTabs();
});

// ===== TABS =====
function setupTabs() {
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      const target = btn.dataset.tab;
      document.getElementById(`tab-${target}`)?.classList.add('active');
      activeTab = target;
    });
  });
}

// ===== SUPPLIER =====
function renderSupplierTable() {
  const list = supplierList;
  const tbody = document.getElementById('supplier-tbody');
  if (!tbody) return;
  if (!list.length) {
    tbody.innerHTML = `<tr><td colspan="6"><div class="empty-state"><div class="empty-icon">🏭</div><p>Belum ada data Supplier</p></div></td></tr>`;
    return;
  }
  tbody.innerHTML = list.map((s, idx) => `<tr>
    <td><strong>${s.nama}</strong></td>
    <td><span class="badge badge-blue">${s.kode||'-'}</span></td>
    <td>${s.kota||'-'}</td>
    <td style="font-size:12px">${s.alamat||'-'}</td>
    <td class="td-actions">
      <button class="btn btn-sm btn-warning btn-icon" onclick="editSupplier(${idx})">✏️</button>
      <button class="btn btn-sm btn-danger btn-icon" onclick="deleteSupplier(${idx})">🗑️</button>
    </td>
  </tr>`).join('');
}

function editSupplier(idx) {
  const s = supplierList[idx];
  if (!s) return;
  document.getElementById('sup-edit-id').value    = idx; // use index for simplicity in this bulk update mode
  document.getElementById('sup-nama').value        = s.nama;
  document.getElementById('sup-kode').value        = s.kode||'';
  document.getElementById('sup-kota').value        = s.kota||'';
  document.getElementById('sup-alamat').value      = s.alamat||'';
  document.getElementById('sup-form-title').textContent = 'Edit Supplier';
  document.getElementById('sup-form-section').scrollIntoView({behavior:'smooth'});
}

async function deleteSupplier(idx) {
  if (!confirmDialog('Hapus supplier ini?')) return;
  supplierList.splice(idx, 1);
  await dbStore.set(KEYS.SUPPLIERS, supplierList);
  renderSupplierTable();
  showToast('Supplier dihapus','success');
}

// ===== CUSTOMER =====
function renderCustomerTable() {
  const list = customerList;
  const tbody = document.getElementById('customer-tbody');
  if (!tbody) return;
  if (!list.length) {
    tbody.innerHTML = `<tr><td colspan="6"><div class="empty-state"><div class="empty-icon">👥</div><p>Belum ada data Customer</p></div></td></tr>`;
    return;
  }
  tbody.innerHTML = list.map((c, idx) => `<tr>
    <td><strong>${c.nama}</strong></td>
    <td><span class="badge badge-green">${c.kode||'-'}</span></td>
    <td>${c.kota||'-'}</td>
    <td style="font-size:12px">${c.alamat||'-'}</td>
    <td class="td-actions">
      <button class="btn btn-sm btn-warning btn-icon" onclick="editCustomer(${idx})">✏️</button>
      <button class="btn btn-sm btn-danger btn-icon" onclick="deleteCustomer(${idx})">🗑️</button>
    </td>
  </tr>`).join('');
}

function editCustomer(idx) {
  const c = customerList[idx];
  if (!c) return;
  document.getElementById('cus-edit-id').value   = idx;
  document.getElementById('cus-nama').value       = c.nama;
  document.getElementById('cus-kode').value       = c.kode||'';
  document.getElementById('cus-kota').value       = c.kota||'';
  document.getElementById('cus-pic').value        = c.pic||'';
  document.getElementById('cus-npwp').value       = c.npwp||'';
  document.getElementById('cus-alamat').value     = c.alamat||'';
  document.getElementById('cus-form-title').textContent = 'Edit Customer';
  document.getElementById('cus-form-section').scrollIntoView({behavior:'smooth'});
}

async function deleteCustomer(idx) {
  if (!confirmDialog('Hapus customer ini?')) return;
  customerList.splice(idx, 1);
  await dbStore.set(KEYS.CUSTOMERS, customerList);
  renderCustomerTable();
  showToast('Customer dihapus','success');
}

// ===== FORMS =====
function setupMasterForms() {
  // Supplier form
  document.getElementById('btn-save-supplier')?.addEventListener('click', async () => {
    const editIdx = document.getElementById('sup-edit-id').value;
    const nama  = document.getElementById('sup-nama').value.trim();
    const kode  = document.getElementById('sup-kode').value.trim().toUpperCase();
    const kota  = document.getElementById('sup-kota').value.trim().toUpperCase();
    const alamat= document.getElementById('sup-alamat').value.trim();
    if (!nama) { showToast('Nama supplier wajib diisi','error'); return; }
    
    if (editIdx !== '') {
      supplierList[editIdx] = { ...supplierList[editIdx], nama, kode, kota, alamat };
    } else {
      supplierList.push({ id: uid(), nama, kode, kota, alamat });
    }
    await dbStore.set(KEYS.SUPPLIERS, supplierList);
    renderSupplierTable();
    resetSupplierForm();
    showToast('Supplier disimpan ✅','success');
  });

  document.getElementById('btn-reset-supplier')?.addEventListener('click', resetSupplierForm);

  // Customer form
  document.getElementById('btn-save-customer')?.addEventListener('click', async () => {
    const editIdx = document.getElementById('cus-edit-id').value;
    const nama  = document.getElementById('cus-nama').value.trim();
    const kode  = document.getElementById('cus-kode').value.trim().toUpperCase();
    const kota  = document.getElementById('cus-kota').value.trim().toUpperCase();
    const pic   = document.getElementById('cus-pic').value.trim();
    const npwp  = document.getElementById('cus-npwp').value.trim();
    const alamat= document.getElementById('cus-alamat').value.trim();
    if (!nama) { showToast('Nama customer wajib diisi','error'); return; }

    if (editIdx !== '') {
      customerList[editIdx] = { ...customerList[editIdx], nama, kode, kota, alamat, pic, npwp };
    } else {
      customerList.push({ id: uid(), nama, kode, kota, alamat, pic, npwp });
    }
    await dbStore.set(KEYS.CUSTOMERS, customerList);
    renderCustomerTable();
    resetCustomerForm();
    showToast('Customer disimpan ✅','success');
  });

  document.getElementById('btn-reset-customer')?.addEventListener('click', resetCustomerForm);
}

function resetSupplierForm() {
  document.getElementById('sup-edit-id').value = '';
  document.getElementById('sup-nama').value = '';
  document.getElementById('sup-kode').value = '';
  document.getElementById('sup-kota').value = '';
  document.getElementById('sup-alamat').value = '';
  document.getElementById('sup-form-title').textContent = 'Tambah Supplier';
}

function resetCustomerForm() {
  document.getElementById('cus-edit-id').value = '';
  document.getElementById('cus-nama').value = '';
  document.getElementById('cus-kode').value = '';
  document.getElementById('cus-kota').value = '';
  document.getElementById('cus-pic').value = '';
  document.getElementById('cus-npwp').value = '';
  document.getElementById('cus-alamat').value = '';
  document.getElementById('cus-form-title').textContent = 'Tambah Customer';
}
