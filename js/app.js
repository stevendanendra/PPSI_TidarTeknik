/**
 * app.js — Shared utilities for CV. Tidar Teknik
 */

// ===== KEAMANAN =====
// Semua fetch non-GET otomatis membawa token CSRF (dari <meta name="csrf-token"> yang disisipkan server).
(function () {
  const meta = document.querySelector('meta[name="csrf-token"]');
  const token = meta ? meta.content : '';
  const nativeFetch = window.fetch;
  window.fetch = function (input, init) {
    init = init || {};
    const method = String(init.method || (input && input.method) || 'GET').toUpperCase();
    if (method !== 'GET' && method !== 'HEAD' && token) {
      const headers = new Headers(init.headers || (input && input.headers) || {});
      headers.set('X-CSRF-Token', token);
      init.headers = headers;
    }
    return nativeFetch.call(this, input, init);
  };
})();

/** Escape teks sebelum disisipkan ke HTML (innerHTML / atribut) agar data pengguna tidak dieksekusi. */
function esc(value) {
  return String(value === null || value === undefined ? '' : value)
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

// ===== FORMAT HELPERS =====
const fmt = {
  /** Format number as Rupiah e.g. Rp 4.000.000 */
  rupiah(n) {
    if (n === null || n === undefined || n === '') return 'Rp 0';
    let num = parseFloat(n) || 0;
    return 'Rp\u00A0' + num.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
  },
  /** Format number with dots as thousands separator */
  number(n) {
    if (n === null || n === undefined || n === '') return '0';
    let num = parseFloat(n) || 0;
    return num.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
  },
  /** Parse Rupiah string back to number */
  parseRupiah(s) {
    if (!s) return 0;
    return parseFloat(String(s).replace(/[Rp\s\.]/g, '').replace(',', '.')) || 0;
  },
  /** Format date to Indonesian format e.g. "18 April 2026" */
  date(d) {
    if (!d) return '-';
    const bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
      'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    const dt = new Date(d);
    if (isNaN(dt)) return d;
    return `${dt.getDate()} ${bulan[dt.getMonth()]} ${dt.getFullYear()}`;
  },
  /** Format date to YYYY-MM-DD for input[type=date] */
  toInputDate(d) {
    if (!d) return '';
    const dt = new Date(d);
    if (isNaN(dt)) return '';
    return dt.toISOString().split('T')[0];
  },
  /** Get month-year string e.g. "April 2026" */
  monthYear(d) {
    if (!d) return '-';
    const bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
      'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    const dt = new Date(d);
    return `${bulan[dt.getMonth()]} ${dt.getFullYear()}`;
  },
  /** Remove all non-numeric chars except comma/dot */
  unmask(v) {
    if (v === null || v === undefined) return 0;
    if (typeof v === 'number') return v;
    let s = String(v).trim().replace(/Rp/g, '').replace(/\s/g, '');
    if (s === '') return 0;

    // Bersihkan karakter yang bukan angka atau koma
    s = s.replace(/[^0-9,]/g, '');

    let parts = s.split(',');
    let integerPart = parts[0];

    if (parts.length > 1) {
      let decimalPart = parts.slice(1).join('');
      if (decimalPart.length > 2) decimalPart = decimalPart.substring(0, 2);
      return parseFloat(integerPart + '.' + decimalPart) || 0;
    }

    return parseFloat(integerPart) || 0;
  },
  formatInput(v) {
    if (v === null || v === undefined || v === '') return '';

    // Jika v adalah angka murni (JS Number)
    if (typeof v === 'number' && !isNaN(v)) {
      let parts = String(v).split('.');
      parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ".");
      if (parts[1] && parts[1].length > 2) parts[1] = parts[1].substring(0, 2);
      return parts.join(',');
    }

    let s = String(v).trim();

    // Jika v adalah string DB float murni: hanya digit, satu titik, maks 2 desimal
    // Contoh valid: "8910000.00", "500.5", "1000"
    // "2.0001" TIDAK cocok karena 4 digit setelah titik → bukan DB float
    if (/^-?\d+\.\d{1,2}$/.test(s)) {
      return this.formatInput(parseFloat(s));
    }
    // String berupa pure integer dari DB: "8910000"
    if (/^-?\d+$/.test(s)) {
      return this.formatInput(parseFloat(s));
    }

    // String input user (sudah ada format titik ribuan / koma desimal)
    // Hapus semua kecuali angka dan koma
    s = s.replace(/[^0-9,]/g, '');

    let parts = s.split(',');
    let integerPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ".");

    if (parts.length > 1) {
      let decimalPart = parts.slice(1).join('');
      if (decimalPart.length > 2) decimalPart = decimalPart.substring(0, 2);
      return integerPart + ',' + decimalPart;
    }

    return integerPart;
  }
};

// ===== STORAGE =====
const store = {
  get(key, fallback = []) {
    try {
      const v = localStorage.getItem(key);
      return v ? JSON.parse(v) : fallback;
    } catch { return fallback; }
  },
  set(key, val) {
    localStorage.setItem(key, JSON.stringify(val));
  }
};

// Database Storage helper
const dbStore = {
  async get(key) {
    const actionMap = {
      [KEYS.SUPPLIERS]: 'get_suppliers',
      [KEYS.CUSTOMERS]: 'get_customers',
      [KEYS.PO_LIST]: 'get_po',
      [KEYS.NOTA_LIST]: 'get_nota',
      [KEYS.PENAWARAN_LIST]: 'get_penawaran',
      [KEYS.LEDGER]: 'get_ledger'
    };
    const res = await fetch(`api.php?action=${actionMap[key]}&_t=${Date.now()}`);
    return await res.json();
  },
  async set(key, val) {
    const actionMap = {
      [KEYS.SUPPLIERS]: 'save_suppliers',
      [KEYS.CUSTOMERS]: 'save_customers',
      [KEYS.PO_LIST]: 'save_po',
      [KEYS.NOTA_LIST]: 'save_nota',
      [KEYS.PENAWARAN_LIST]: 'save_penawaran',
      [KEYS.LEDGER]: 'save_ledger'
    };
    // Daftar kosong hanya boleh menghapus semua data bila dikirim eksplisit (mis. menghapus item terakhir).
    const allowEmpty = Array.isArray(val) && val.length === 0 ? '&allow_empty=1' : '';
    const res = await fetch(`api.php?action=${actionMap[key]}${allowEmpty}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(val)
    });

    const text = await res.text();
    let json;
    try {
      json = JSON.parse(text);
    } catch (e) {
      console.error('Server returned non-JSON:', text);
      throw new Error('Server error: Respon server bukan JSON. Kemungkinan ada error PHP. Isi respon: ' + text.substring(0, 100));
    }

    if (!res.ok || json.status !== 'success') {
      throw new Error(json.message || 'Gagal menyimpan data ke server');
    }
    // Update local cache to keep in sync
    store.set(key, val);
    return json;
  }
};

// Storage keys
const KEYS = {
  SUPPLIERS: 'tt_suppliers',
  CUSTOMERS: 'tt_customers',
  PO_LIST: 'tt_po_list',
  NOTA_LIST: 'tt_nota_list',
  PENAWARAN_LIST: 'tt_penawaran_list',
  LEDGER: 'tt_ledger'
};

// Migration / Sync
async function syncLocalToDB() {
  const suppliers = store.get(KEYS.SUPPLIERS);
  const customers = store.get(KEYS.CUSTOMERS);
  const poList = store.get(KEYS.PO_LIST);
  const notaList = store.get(KEYS.NOTA_LIST);

  // If DB is empty, sync from local
  const dbSup = await dbStore.get(KEYS.SUPPLIERS);
  if (dbSup.length === 0 && suppliers.length > 0) {
    console.log('Syncing suppliers to DB...');
    await dbStore.set(KEYS.SUPPLIERS, suppliers);
  }

  const dbCus = await dbStore.get(KEYS.CUSTOMERS);
  if (dbCus.length === 0 && customers.length > 0) {
    console.log('Syncing customers to DB...');
    await dbStore.set(KEYS.CUSTOMERS, customers);
  }

  const dbPO = await dbStore.get(KEYS.PO_LIST);
  if (dbPO.length === 0 && poList.length > 0) {
    console.log('Syncing PO to DB...');
    await dbStore.set(KEYS.PO_LIST, poList);
  }

  const dbNota = await dbStore.get(KEYS.NOTA_LIST);
  if (dbNota.length === 0 && notaList.length > 0) {
    console.log('Syncing Nota to DB...');
    await dbStore.set(KEYS.NOTA_LIST, notaList);
  }

  const penawaranList = store.get(KEYS.PENAWARAN_LIST);
  const dbPenawaran = await dbStore.get(KEYS.PENAWARAN_LIST);
  if (dbPenawaran.length === 0 && penawaranList.length > 0) {
    console.log('Syncing Penawaran to DB...');
    await dbStore.set(KEYS.PENAWARAN_LIST, penawaranList);
  }
}

async function updateSidebarBadges() {
  const po = await dbStore.get(KEYS.PO_LIST) || [];
  const nota = await dbStore.get(KEYS.NOTA_LIST) || [];
  const pn = await dbStore.get(KEYS.PENAWARAN_LIST) || [];

  const elPO = document.getElementById('sb-po-count');
  const elNota = document.getElementById('sb-nota-count');
  const elPn = document.getElementById('sb-penawaran-count');

  const today = new Date(); today.setHours(0, 0, 0, 0);

  // PO Overdue
  const overduePO = po.filter(p => {
    if (p.status === 'Lunas') return false;
    const term = parseInt(p.termin) || 30;
    const d = new Date(p.tanggal);
    d.setDate(d.getDate() + term);
    return today > d;
  });

  // Nota Overdue
  const overdueNota = nota.filter(n => {
    if (n.status === 'Lunas') return false;
    const term = parseInt(n.termin) || 30;
    const d = new Date(n.tanggal);
    d.setDate(d.getDate() + term);
    return today > d;
  });

  if (elPO) {
    elPO.textContent = po.length;
    elPO.style.background = 'var(--em-500)'; // Green
    elPO.style.color = 'white';
  }

  if (elNota) {
    elNota.textContent = nota.length;
    elNota.style.background = '#ef4444'; // Red
    elNota.style.color = 'white';
  }

  if (elPn) {
    elPn.textContent = pn.length;
    elPn.style.background = 'var(--teal-500)'; // Blue
    elPn.style.color = 'white';
  }
}



// ===== GLOBAL LIVE CURRENCY MASK =====
document.addEventListener('input', (e) => {
  if (e.target.classList.contains('mask-money')) {
    const start = e.target.selectionStart;
    const oldLen = e.target.value.length;
    e.target.value = fmt.formatInput(e.target.value);
    const newLen = e.target.value.length;
    // Restore cursor position roughly
    e.target.setSelectionRange(start + (newLen - oldLen), start + (newLen - oldLen));
  }
});
const SEED_SUPPLIERS = [
  { id: 'sup1', nama: 'CV. MEGA TEKNIK ENGINEERING', kode: 'MTE', kota: 'SURABAYA', alamat: 'Jl. Raya Surabaya No. 10' },
  { id: 'sup2', nama: 'CV. ANTIKA SUKSES GEMILANG', kode: 'ASG', kota: 'SURABAYA', alamat: 'Jl. Antika No. 5, Surabaya' },
  { id: 'sup3', nama: 'PT. BOSCO INDOLESTARI', kode: 'BI', kota: 'JAKARTA', alamat: 'Jl. Bosco Raya, Jakarta' },
  { id: 'sup4', nama: 'PT CAHAYA SELANG', kode: 'CS', kota: 'SURABAYA', alamat: 'Jl. Cahaya No. 12, Surabaya' },
  { id: 'sup5', nama: 'SAKURA BEARING', kode: 'SB', kota: 'SURABAYA', alamat: 'Jl. Sakura No. 7, Surabaya' },
  { id: 'sup6', nama: 'CV. SOLUSI JAYA BERSAMA', kode: 'SJB', kota: 'MALANG', alamat: 'Jl. Solusi No. 3, Malang' },
  { id: 'sup7', nama: 'PT. ISTANA POMPA', kode: 'IS', kota: 'SURABAYA', alamat: 'Jl. Istana No. 15, Surabaya' },
];

const SEED_CUSTOMERS = [
  { id: 'cus1', nama: 'PT. INDO MINA BAHARI', kode: 'NP', kota: 'JAKARTA', alamat: 'Jl. Indo Mina No. 1, Jakarta' },
  { id: 'cus2', nama: 'PT MCENERGY BIOMAS INDONESIA', kode: 'MBI', kota: 'PASURUAN', alamat: 'JL. RAYA SURABAYA MALANG NO 12, PASURUAN (PAK RIDWAN 081775766704)' },
  { id: 'cus3', nama: 'BAPAK MOH. HOSEN', kode: 'MH', kota: 'MALANG', alamat: 'Jl. Hosen No. 5, Malang' },
  { id: 'cus4', nama: 'BITUMEN MARASENDE', kode: 'BM', kota: 'KALIMANTAN', alamat: 'Jl. Marasende, Kalimantan' },
  { id: 'cus5', nama: 'BAPAK MASHUDI', kode: '', kota: 'MALANG', alamat: 'Jl. Mashudi, Malang' },
  { id: 'cus6', nama: 'BESTAGAR PUREINDO INTERNASIONAL', kode: 'BPI', kota: 'JAKARTA', alamat: 'Jl. Bestagar No. 8, Jakarta' },
  { id: 'cus7', nama: 'PT. HEFRAM ASASTA INDONESIA', kode: 'HAI', kota: 'SURABAYA', alamat: 'Jl. Hefram No. 12, Surabaya' },
  { id: 'cus8', nama: 'BIOSEVEN FIBERGLAS INDONESIA', kode: 'BFI', kota: 'JAKARTA', alamat: 'Jl. Bioseven No. 4, Jakarta' },
  { id: 'cus9', nama: 'PT. MAJU PERKASA NUSANTARA', kode: 'MPN', kota: 'SURABAYA', alamat: 'Jl. Maju Perkasa No. 9, Surabaya' },
  { id: 'cus10', nama: 'MAYA MUNCAR', kode: 'MM', kota: 'BANYUWANGI', alamat: 'Jl. Muncar No. 2, Banyuwangi' },
  { id: 'cus11', nama: 'PT. WAHYU DAYA MULIA', kode: 'WDM', kota: 'SURABAYA', alamat: 'Jl. Wahyu No. 6, Surabaya' },
  { id: 'cus12', nama: 'BAPAK SUGENG', kode: 'SG', kota: 'MALANG', alamat: 'Jl. Sugeng No. 3, Malang' },
  { id: 'cus13', nama: 'PT. SAPUTRA TIRTHA AMERTHA', kode: 'STA', kota: 'BALI', alamat: 'Jl. Tirtha No. 10, Bali' },
  { id: 'cus14', nama: 'PT. CAKRA JAYARAYA ABADI', kode: 'CJA', kota: 'SURABAYA', alamat: 'Jl. Cakra No. 7, Surabaya' },
  { id: 'cus15', nama: 'PT. ANUGERAH MULIA FARM', kode: 'AMF', kota: 'MALANG', alamat: 'Jl. Anugerah No. 5, Malang' },
];

const SEED_PO = [
  {
    id: 'po1',
    nomor: '4030/TT-MTE/IV/26',
    tanggal: '2026-04-18',
    supplier: 'CV. MEGA TEKNIK ENGINEERING',
    kota: 'SURABAYA',
    note1: 'PAYMENT 30HARI',
    note2: 'INCLUDE PPN',
    alamatKirim: '',
    ppnMode: 'include',
    disc: 0,
    dp: 0,
    items: [
      { desc: 'SPEED CONTROL CHANG YI', type: '', qty: '1', price: 4000000, amount: 4000000 }
    ]
  },
  {
    id: 'po2',
    nomor: '4017/TT-SB/IV/26',
    tanggal: '2026-04-08',
    supplier: 'SAKURA BEARING',
    kota: 'SURABAYA',
    note1: 'PAYMENT CASH',
    note2: '',
    alamatKirim: '',
    ppnMode: 'exclude',
    disc: 0,
    dp: 0,
    items: [
      { desc: 'BEARING 33A120 ZWZ', type: '', qty: '9', price: 1500000, amount: 13500000 },
      { desc: 'BEARING 33A120 NETRAL', type: '', qty: '1', price: 450000, amount: 450000 }
    ]
  }
];

const SEED_NOTA = [
  {
    id: 'nota1',
    nomor: '21/ IV /TT-MBI/2026',
    tanggal: '2026-04-15',
    customer: 'PT MCENERGY BIOMAS INDONESIA',
    alamatInvoice: 'JL. RAYA SURABAYA MALANG NO 12, PASURUAN (PAK RIDWAN 081775766704)',
    alamatSJ: 'JL. RAYA SURABAYA MALANG NO 12, PASURUAN',
    refPO: '0',
    ekspedisi: '',
    tujuanKirim: 'BERAU',
    disc: 0,
    dp: 0,
    ppnMode: 'include',
    items: [
      { qty: 1, satuan: 'PCS', code: '', desc: '80-1 SS JEPANG', price: 13900000, amount: 13900000 },
      { qty: 1, satuan: 'PCS', code: '', desc: '80-2 SS JEPANG', price: 17498000, amount: 17498000 },
      { qty: 4, satuan: 'PCS', code: '', desc: 'CONNETING RP 80-II', price: 350000, amount: 1400000 }
    ]
  }
];

// ===== INIT SEED =====
function initSeedData() {
  if (!localStorage.getItem(KEYS.SUPPLIERS)) store.set(KEYS.SUPPLIERS, SEED_SUPPLIERS);
  if (!localStorage.getItem(KEYS.CUSTOMERS)) store.set(KEYS.CUSTOMERS, SEED_CUSTOMERS);
  if (!localStorage.getItem(KEYS.PO_LIST)) store.set(KEYS.PO_LIST, SEED_PO);
  if (!localStorage.getItem(KEYS.NOTA_LIST)) store.set(KEYS.NOTA_LIST, SEED_NOTA);
}

// ===== UNIQUE ID =====
function uid() {
  return Date.now().toString(36) + Math.random().toString(36).slice(2, 6);
}

// ===== TERBILANG (Indonesian number-to-words) =====
function terbilang(n) {
  const satuan = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan',
    'sepuluh', 'sebelas', 'dua belas', 'tiga belas', 'empat belas', 'lima belas',
    'enam belas', 'tujuh belas', 'delapan belas', 'sembilan belas'];
  function helper(x) {
    if (x < 20) return satuan[x];
    if (x < 100) {
      const t = Math.floor(x / 10);
      const s = x % 10;
      return (t === 1 ? 'satu' : satuan[t]) + ' puluh' + (s ? ' ' + satuan[s] : '');
    }
    if (x < 1000) {
      const r = Math.floor(x / 100);
      return (r === 1 ? 'seratus' : satuan[r] + ' ratus') + (x % 100 ? ' ' + helper(x % 100) : '');
    }
    if (x < 1000000) {
      const r = Math.floor(x / 1000);
      return (r === 1 ? 'seribu' : helper(r) + ' ribu') + (x % 1000 ? ' ' + helper(x % 1000) : '');
    }
    if (x < 1000000000) {
      return helper(Math.floor(x / 1000000)) + ' juta' + (x % 1000000 ? ' ' + helper(x % 1000000) : '');
    }
    return helper(Math.floor(x / 1000000000)) + ' miliar' + (x % 1000000000 ? ' ' + helper(x % 1000000000) : '');
  }
  const num = Math.round(Math.abs(n));
  if (num === 0) return 'nol';
  return (n < 0 ? 'minus ' : '') + helper(num) + ' rupiah';
}

// ===== TOAST =====
function showToast(msg, type = 'info') {
  let container = document.querySelector('.toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
  }
  const toast = document.createElement('div');
  const icons = { info: 'ℹ️', success: '✅', error: '❌', warning: '⚠️' };
  toast.className = `toast ${type}`;
  toast.innerHTML = `<span>${icons[type] || 'ℹ️'}</span><span>${esc(msg)}</span>`;
  container.appendChild(toast);
  setTimeout(() => {
    toast.style.animation = 'toastIn .2s ease reverse';
    setTimeout(() => toast.remove(), 200);
  }, 3000);
}

// ===== CONFIRM DIALOG =====
function confirmDialog(msg) {
  return window.confirm(msg);
}

// ===== AUTOCOMPLETE =====
function setupAutocomplete(inputEl, listEl, getDataFn, onSelect) {
  inputEl.addEventListener('input', async () => {
    const q = inputEl.value.trim().toLowerCase();
    const allItems = await getDataFn();
    const items = allItems.filter(d => d.nama.toLowerCase().includes(q));
    renderList(items);
  });
  inputEl.addEventListener('focus', async () => {
    const allItems = await getDataFn();
    if (allItems.length) renderList(allItems.slice(0, 8));
  });
  document.addEventListener('click', (e) => {
    if (!inputEl.contains(e.target) && !listEl.contains(e.target)) {
      listEl.innerHTML = '';
      listEl.style.display = 'none';
    }
  });
  function renderList(items) {
    if (!items.length) { listEl.innerHTML = ''; listEl.style.display = 'none'; return; }
    listEl.style.display = 'block';
    listEl.innerHTML = items.map((d, i) =>
      `<div class="autocomplete-item" data-idx="${i}">
        <strong>${esc(d.nama)}</strong>
        <div class="ac-sub">${d.kode ? '[' + d.kode + ']' : ''} ${esc(d.kota || '')}</div>
      </div>`
    ).join('');
    listEl.querySelectorAll('.autocomplete-item').forEach((el, i) => {
      el.addEventListener('click', () => {
        onSelect(items[i]);
        listEl.innerHTML = '';
        listEl.style.display = 'none';
      });
    });
  }
}

// ===== SORT TABLE =====
function makeSortable(tbodyId, data, cols, renderFn) {
  let sortCol = null, sortDir = 1;
  document.querySelectorAll('thead th.sortable').forEach(th => {
    th.addEventListener('click', () => {
      const col = th.dataset.col;
      if (sortCol === col) sortDir *= -1;
      else { sortCol = col; sortDir = 1; }
      document.querySelectorAll('thead th.sortable').forEach(t => {
        t.classList.remove('sort-asc', 'sort-desc');
      });
      th.classList.add(sortDir === 1 ? 'sort-asc' : 'sort-desc');
      const sorted = [...data].sort((a, b) => {
        let va = a[col], vb = b[col];
        if (cols[col] === 'number') { va = parseFloat(va) || 0; vb = parseFloat(vb) || 0; }
        else { va = String(va || '').toLowerCase(); vb = String(vb || '').toLowerCase(); }
        return va < vb ? -sortDir : va > vb ? sortDir : 0;
      });
      renderFn(sorted);
    });
  });
}

// ===== CALCULATE PO TOTALS =====
function calcPOTotals(items, disc, dp, ppnMode) {
  const total = items.reduce((s, i) => s + (parseFloat(i.amount) || 0), 0);
  const discVal = parseFloat(disc) || 0;
  const dpVal = parseFloat(dp) || 0;
  let ppn = 0;
  let dpp = total;

  if (ppnMode === 'include') {
    dpp = Math.round(total / 1.11);
    ppn = Math.round(total - dpp);
  } else if (ppnMode === 'exclude') {
    ppn = Math.round(total * 0.11);
  }

  const grandTotal = Math.round(total - discVal - dpVal + (ppnMode === 'exclude' ? ppn : 0));
  return {
    total: total,
    discVal: discVal,
    dpVal: dpVal,
    ppn: ppn,
    dpp: dpp,
    grandTotal: grandTotal
  };
}

// ===== CALCULATE NOTA TOTALS =====
function calcNotaTotals(items, disc, dp, ppnMode) {
  const total = items.reduce((s, i) => s + (parseFloat(i.amount) || 0), 0);
  const discVal = parseFloat(disc) || 0;
  const dpVal = parseFloat(dp) || 0;
  let ppn = 0;
  let dpp = total;

  if (ppnMode === 'include') {
    dpp = Math.round(total / 1.11);
    ppn = Math.round(total - dpp);
  } else if (ppnMode === 'exclude') {
    ppn = Math.round(total * 0.11);
  }

  const grandTotal = Math.round(total - discVal - dpVal + (ppnMode === 'exclude' ? ppn : 0));
  return {
    total: total,
    discVal: discVal,
    dpVal: dpVal,
    ppn: ppn,
    dpp: dpp,
    grandTotal: grandTotal
  };
}

// ===== CALCULATE PENAWARAN TOTALS =====
function calcPenawaranTotals(items, disc, dp, ppnMode) {
  const total = items.reduce((s, i) => s + (parseFloat(i.amount) || 0), 0);
  const discVal = parseFloat(disc) || 0;
  const dpVal = parseFloat(dp) || 0;
  let ppn = 0;
  let dpp = total;

  if (ppnMode === 'include') {
    dpp = Math.round(total / 1.11);
    ppn = Math.round(total - dpp);
  } else if (ppnMode === 'exclude') {
    ppn = Math.round(total * 0.11);
  }

  const grandTotal = Math.round(total - discVal - dpVal + (ppnMode === 'exclude' ? ppn : 0));
  return {
    total: total,
    discVal: discVal,
    dpVal: dpVal,
    ppn: ppn,
    dpp: dpp,
    grandTotal: grandTotal
  };
}

// ===== MOBILE SIDEBAR TOGGLE =====
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebar-overlay');
  if (!sidebar) return;

  if (window.innerWidth > 768) {
    document.body.classList.toggle('sidebar-collapsed');
    const isCollapsed = document.body.classList.contains('sidebar-collapsed');
    localStorage.setItem('sidebar_collapsed', isCollapsed ? 'true' : 'false');
  } else {
    const isOpen = sidebar.classList.contains('open');
    if (isOpen) {
      closeSidebar();
    } else {
      sidebar.classList.add('open');
      if (overlay) overlay.classList.add('open');
      document.body.style.overflow = 'hidden'; // prevent background scroll
    }
  }
}

function closeSidebar() {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebar-overlay');
  if (sidebar) sidebar.classList.remove('open');
  if (overlay) overlay.classList.remove('open');
  document.body.style.overflow = '';
}

// Auto-close sidebar when a nav-item is clicked (mobile)
document.addEventListener('DOMContentLoaded', () => {
  if (localStorage.getItem('sidebar_collapsed') === 'true' && window.innerWidth > 768) {
    document.body.classList.add('sidebar-collapsed');
  }

  document.querySelectorAll('.nav-item').forEach(link => {
    link.addEventListener('click', () => {
      if (window.innerWidth <= 768) closeSidebar();
    });
  });

  // Auto-close on resize to desktop
  window.addEventListener('resize', () => {
    if (window.innerWidth > 768) closeSidebar();
  });

  // Forward wheel scroll to .sidebar-nav if mouse is anywhere over sidebar
  const sb = document.getElementById('sidebar');
  const sbNav = sb ? sb.querySelector('.sidebar-nav') : null;
  if (sb && sbNav) {
    sb.addEventListener('wheel', (e) => {
      if (!sbNav.contains(e.target) || sbNav === e.target) {
        sbNav.scrollTop += e.deltaY;
      }
    }, { passive: true });
  }
});

// Menu admin tambahan (Akun Customer, Retur) + lonceng notifikasi internal
document.addEventListener('DOMContentLoaded', () => {
  const anchor = document.querySelector('.sidebar a[href="admin-products.php"]');
  if (anchor && !document.querySelector('a[href="admin-customers.php"]')) {
    const here = location.pathname.split('/').pop();
    const mk = (href, icon, label) => {
      const a = document.createElement('a');
      a.href = href;
      a.className = 'nav-item' + (here === href ? ' active' : '');
      a.innerHTML = `<span class="nav-icon">${icon}</span> ${label}`;
      return a;
    };
    anchor.after(mk('admin-customers.php', '🔑', 'Akun Customer'), mk('admin-returns.php', '↩️', 'Retur Barang'));
  }

  const actions = document.querySelector('.topbar-actions');
  if (!actions || document.getElementById('notif-bell') || document.querySelector('.badge-portal')) return;
  const wrap = document.createElement('div');
  wrap.style.position = 'relative';
  wrap.innerHTML = `<button type="button" class="btn btn-ghost" id="notif-bell" style="font-size:16px;padding:6px 10px" aria-label="Notifikasi">🔔<span id="notif-count" class="nav-badge" style="display:none;position:absolute;top:-4px;right:-4px">0</span></button>
    <div id="notif-panel" class="card" style="display:none;position:absolute;right:0;top:44px;width:340px;max-width:85vw;z-index:50;padding:0;max-height:360px;overflow:auto"></div>`;
  actions.prepend(wrap);
  const countEl = wrap.querySelector('#notif-count'), panel = wrap.querySelector('#notif-panel');
  const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  async function load() {
    try {
      const res = await fetch('api.php?action=get_notifications&_t=' + Date.now());
      const d = await res.json();
      if (d.status !== 'success') return;
      countEl.style.display = d.unread > 0 ? 'inline-block' : 'none';
      countEl.textContent = d.unread;
      panel.innerHTML = d.items.length
        ? d.items.map(n => `<a href="${esc(n.link || '#')}" style="display:block;padding:10px 14px;border-bottom:1px solid var(--navy-200);font-size:12.5px;text-decoration:none;color:var(--navy-800);${n.is_read == 0 ? 'background:rgba(14,165,233,.08);font-weight:600' : ''}">${esc(n.pesan)}<br><small style="color:var(--navy-400)">${esc(n.created_at)}</small></a>`).join('')
        : '<div style="padding:14px;font-size:12.5px;color:var(--navy-400)">Belum ada notifikasi.</div>';
    } catch (e) {}
  }
  wrap.querySelector('#notif-bell').addEventListener('click', async () => {
    const open = panel.style.display === 'block';
    panel.style.display = open ? 'none' : 'block';
    if (!open) { await fetch('api.php?action=read_notifications', { method: 'POST' }); setTimeout(load, 300); }
  });
  load(); setInterval(load, 15000);
});

// init on load
initSeedData();
