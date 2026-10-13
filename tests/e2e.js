// Uji E2E SIM Dropship CV. Tidar Teknik — mengikuti docs/Test_Case_SIT_UAT.md.
// Jalankan: NODE_PATH=<folder node_modules playwright> node tests/e2e.js  (server di BASE_URL, DB lokal kosong/dump)
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const L = require('./lib');
const { BASE, sql, assert, t, skip, adminLogin, custLogin, bodyText, PNG, tmpFile } = L;

const ctx = {};            // state antar bagian
const only = (process.env.ONLY || '').split(',').filter(Boolean);
const run = k => !only.length || only.includes(k);

async function sectionA(b) {
  console.log('\nA. Autentikasi & akun');
  const anon = await b.newPage();
  await t('A04', 'Halaman admin tanpa login diarahkan ke login', async () => {
    await anon.goto(BASE + 'index.php'); assert(/login\.php/.test(anon.url()), anon.url());
  });
  await t('A02', 'Login admin salah ditolak', async () => {
    await adminLogin(anon, 'salah-banget'); assert(/login\.php/.test(anon.url()), anon.url());
    assert(/salah|gagal/i.test(await bodyText(anon)), 'tidak ada pesan gagal');
  });
  ctx.admin = await b.newPage();
  await t('A01', 'Login admin benar', async () => {
    // kunci per-IP/sesi? pakai konteks baru agar bersih
    const c = await b.newContext(); ctx.adminCtx = c; ctx.admin = await c.newPage();
    await adminLogin(ctx.admin); assert(/index\.php/.test(ctx.admin.url()), ctx.admin.url());
  });
  await anon.close();

  const A = ctx.admin;
  await t('A05', 'Admin membuat akun customer (Alfa & Beta)', async () => {
    for (const [nama, email] of [['Uji Alfa', 'alfa@test.com'], ['Uji Beta', 'beta@test.com']]) {
      await A.goto(BASE + 'admin-customers.php');
      await A.fill('input[name=nama]', nama); await A.fill('input[name=email]', email);
      await A.fill('input[name=kota]', 'MALANG'); await A.fill('textarea[name=alamat]', 'Jl. Uji ' + nama);
      await Promise.all([A.waitForNavigation({ waitUntil: 'load' }), A.click('button:has-text("Buat Akun")')]);
    }
    assert(sql("select count(*) from customers where email in ('alfa@test.com','beta@test.com')") === '2', 'akun tidak terbentuk');
  });
  await t('A06', 'Nama perusahaan kembar ditolak', async () => {
    await A.goto(BASE + 'admin-customers.php');
    await A.fill('input[name=nama]', 'uji alfa'); await A.fill('input[name=email]', 'alfa2@test.com');
    await Promise.all([A.waitForNavigation({ waitUntil: 'load' }), A.click('button:has-text("Buat Akun")')]);
    assert(/nama perusahaan yang sama/i.test(await bodyText(A)), 'tidak ada pesan');
    assert(sql("select count(*) from customers where email='alfa2@test.com'") === '0', 'akun kembar terbentuk');
  });

  ctx.ca = await (await b.newContext()).newPage();
  ctx.cb = await (await b.newContext()).newPage();
  for (const p of [ctx.admin, ctx.ca, ctx.cb]) p.on('dialog', d => d.accept());
  const defaultPass = 'tidar123';
  await t('A07', 'Wajib ganti password saat login pertama', async () => {
    await custLogin(ctx.ca, 'alfa@test.com', defaultPass);
    await ctx.ca.goto(BASE + 'customer-dashboard.php');
    assert(/customer-profile\.php/.test(ctx.ca.url()), ctx.ca.url());
    await ctx.ca.fill('input[name=pass_lama]', defaultPass);
    await ctx.ca.fill('input[name=pass_baru]', 'Alfa12345'); await ctx.ca.fill('input[name=pass_conf]', 'Alfa12345');
    await Promise.all([ctx.ca.waitForNavigation({ waitUntil: 'load' }), ctx.ca.click('button:has-text("Ubah Password"), button.btn-warning')]);
    await ctx.ca.goto(BASE + 'customer-dashboard.php');
    assert(/customer-dashboard\.php/.test(ctx.ca.url()), 'masih dipaksa ganti: ' + ctx.ca.url());
  });
  await t('A08', 'Tutorial login pertama muncul sekali & bisa ditutup', async () => {
    await ctx.ca.goto(BASE + 'customer-dashboard.php');
    const ov = ctx.ca.locator('#tutorial-overlay');
    assert(await ov.count() > 0 && await ov.isVisible(), 'tutorial tidak tampil');
    await ctx.ca.click('#tutorial-overlay button:has-text("Mengerti")'); await ctx.ca.waitForTimeout(500);
    await ctx.ca.goto(BASE + 'customer-dashboard.php');
    assert(await ctx.ca.locator('#tutorial-overlay').count() === 0, 'tutorial muncul lagi');
  });
  await t('A12', 'Help Center menampilkan FAQ', async () => {
    await ctx.ca.goto(BASE + 'customer-help.php');
    const txt = await bodyText(ctx.ca); assert(/Help Center/i.test(txt) && /password|pesanan|retur/i.test(txt), txt.slice(0, 200));
  });
  await t('A13', 'Registrasi mandiri mati', async () => {
    const p = await b.newPage(); await p.goto(BASE + 'customer-register.php');
    assert(/customer-login\.php/.test(p.url()), p.url()); await p.close();
  });
  await t('A09', 'Lupa password: pesan seragam untuk email terdaftar/tidak', async () => {
    const p = await b.newPage();
    const msgs = [];
    for (const em of ['beta@test.com', 'tidak-ada@test.com']) {
      await p.goto(BASE + 'customer-forgot-password.php'); await p.fill('input[name=email]', em);
      await Promise.all([p.waitForNavigation({ waitUntil: 'load' }), p.click('button[type=submit]')]);
      msgs.push((await bodyText(p)).match(/Jika email terdaftar[^.]*\./)?.[0]);
    }
    assert(msgs[0] && msgs[0] === msgs[1], JSON.stringify(msgs));
    await p.close();
  });
  await t('A10', 'Reset password via tautan; tautan tidak bisa dipakai ulang', async () => {
    const p = await b.newPage();
    await p.goto(BASE + 'customer-forgot-password.php'); await p.fill('input[name=email]', 'beta@test.com');
    await Promise.all([p.waitForNavigation({ waitUntil: 'load' }), p.click('button[type=submit]')]);
    const href = await p.locator('a[href*="customer-reset-password.php?token="]').first().getAttribute('href');
    assert(href, 'tautan reset (mode dev) tidak tampil');
    await p.goto(href); await p.fill('input[name=password]', 'Beta12345'); await p.fill('input[name=password_confirm]', 'Beta12345');
    await Promise.all([p.waitForNavigation({ waitUntil: 'load' }), p.click('button[type=submit]')]);
    assert(/berhasil/i.test(await bodyText(p)), (await bodyText(p)).slice(0, 200));
    await p.goto(href); assert(/tidak valid|kedaluwarsa/i.test(await bodyText(p)), 'tautan bisa dipakai ulang');
    await custLogin(ctx.cb, 'beta@test.com', 'Beta12345');
    await ctx.cb.goto(BASE + 'customer-dashboard.php'); assert(/customer-dashboard\.php/.test(ctx.cb.url()), ctx.cb.url());
    await p.close();
  });
  await t('A11', 'Akun dinonaktifkan: request berikutnya diarahkan ke login', async () => {
    // pakai akun ketiga agar tidak mengganggu skenario lain
    await A.goto(BASE + 'admin-customers.php');
    await A.fill('input[name=nama]', 'Uji Gamma'); await A.fill('input[name=email]', 'gamma@test.com');
    await Promise.all([A.waitForNavigation({ waitUntil: 'load' }), A.click('button:has-text("Buat Akun")')]);
    sql("update customers set must_change_password=0 where email='gamma@test.com'");
    const c = await b.newContext(); const g = await c.newPage();
    await custLogin(g, 'gamma@test.com', 'tidar123'); await g.goto(BASE + 'customer-dashboard.php');
    assert(/customer-dashboard/.test(g.url()), 'gamma gagal login: ' + g.url());
    sql("update customers set status='inactive' where email='gamma@test.com'");
    await g.goto(BASE + 'customer-dashboard.php');
    assert(/customer-login\.php/.test(g.url()), 'masih bisa akses: ' + g.url());
    await c.close();
  });
  await t('A03', 'Batas percobaan login (5x salah -> terkunci)', async () => {
    const p = await b.newPage();
    for (let i = 0; i < 6; i++) await adminLogin(p, 'salah' + i);
    assert(/terlalu banyak/i.test(await bodyText(p)), (await bodyText(p)).slice(0, 200));
    await adminLogin(p); // benar pun tetap terkunci
    assert(/login\.php/.test(p.url()) || /terlalu banyak/i.test(await bodyText(p)), 'login benar saat terkunci lolos');
    await p.close();
    sql('delete from login_attempts');
  });
}


async function sectionB(b) {
  console.log('\nB. Katalog & supplier');
  const A = ctx.admin, ca = ctx.ca;
  await t('B01', 'Produk dengan rentang harga tampil "Rp X – Y" di katalog customer', async () => {
    await A.goto(BASE + 'admin-products.php');
    await A.fill('#prod-nama', 'POMPA UJI RENTANG'); await A.fill('#prod-kategori', 'Pompa');
    await A.fill('#prod-harga', '1000000'); await A.fill('#prod-harga-min', '1000000'); await A.fill('#prod-harga-max', '1500000');
    await Promise.all([A.waitForNavigation({ waitUntil: 'load' }), A.click('#prod-form button[type=submit]')]);
    assert(sql("select count(*) from products where nama='POMPA UJI RENTANG'") === '1', 'produk tidak tersimpan');
    ctx.rangeProd = sql("select id from products where nama='POMPA UJI RENTANG'");
    await ca.goto(BASE + 'customer-products.php?q=POMPA+UJI');
    const txt = await bodyText(ca);
    assert(/1\.000\.000\s*[–-]\s*(Rp\s*)?1\.500\.000/.test(txt), 'rentang tidak tampil: ' + txt.match(/Rp[^A-Za-z]{0,40}/g)?.slice(0, 3));
  });
  await t('B02', 'Stok tidak tampil di katalog & detail produk', async () => {
    for (const u of ['customer-products.php', 'customer-product-detail.php?id=PRD-001']) {
      await ca.goto(BASE + u); const txt = await bodyText(ca);
      assert(!/\bstok\b|ready stock|stock ready/i.test(txt), u + ' memuat kata stok: ' + txt.match(/.{20}\bstok\b.{20}/i)?.[0]);
    }
  });
  await t('B03', 'Kandidat supplier & pemilihan final mengubah supplier produk', async () => {
    await A.goto(BASE + 'admin-product-suppliers.php?product_id=PRD-003');
    for (const sid of ['2', '3']) {
      await A.selectOption('select[name=supplier_id]', sid); await A.fill('input[name=harga_modal]', sid === '2' ? '12000000' : '11500000');
      await Promise.all([A.waitForNavigation({ waitUntil: 'load' }), A.click('button:has-text("Simpan Kandidat")')]);
    }
    const n = sql("select count(*) from product_suppliers where product_id='PRD-003'");
    assert(+n >= 2, 'kandidat kurang: ' + n);
    await A.goto(BASE + 'admin-product-suppliers.php?product_id=PRD-003');
    // jadikan supplier 3 final
    await Promise.all([A.waitForNavigation({ waitUntil: 'load' }), A.locator('form:has(input[name=supplier_id][value="3"]) button[name=act][value=final]').click()]);
    assert(sql("select supplier_id from products where id='PRD-003'") === '3', 'supplier produk tidak berubah: ' + sql("select supplier_id from products where id='PRD-003'"));
    assert(sql("select count(*) from product_suppliers where product_id='PRD-003' and is_final=1") === '1', 'final tidak tunggal');
  });
  await t('B04', 'Pencarian & filter kategori katalog', async () => {
    await ca.goto(BASE + 'customer-products.php?q=BEARING');
    const names = await ca.locator('.product-title').allInnerTexts();
    assert(names.length >= 2 && names.every(n => /BEARING/i.test(n)), 'hasil cari: ' + names.join('|'));
    const kat = sql("select kategori from products where id='PRD-003'");
    await ca.goto(BASE + 'customer-products.php?kategori=' + encodeURIComponent(kat));
    const n2 = await ca.locator('.product-title').allInnerTexts();
    assert(n2.length >= 1 && n2.some(n => /ROLLER CHAIN/i.test(n)), 'filter kategori: ' + n2.join('|'));
  });
  await t('B05', 'Wishlist tambah/hapus', async () => {
    await ca.goto(BASE + 'customer-product-detail.php?id=PRD-001');
    const btn = ca.locator('.wishlist-btn-corner').first();
    await btn.click(); await ca.waitForTimeout(700);
    const aid = sql("select id from customers where email='alfa@test.com'");
    assert(sql(`select count(*) from customer_wishlist where customer_id='${aid}'`) === '1', 'tidak masuk wishlist');
    await btn.click(); await ca.waitForTimeout(700);
    assert(sql(`select count(*) from customer_wishlist where customer_id='${aid}'`) === '0', 'tidak terhapus');
  });
}

async function sectionC(b) {
  console.log('\nC. Order, keranjang, penawaran');
  const A = ctx.admin, ca = ctx.ca, cb = ctx.cb;
  const aid = () => sql("select id from customers where email='alfa@test.com'");
  await t('C01', 'Keranjang multi-produk, jumlah bisa diubah', async () => {
    for (const id of ['PRD-001', 'PRD-003']) {
      await ca.goto(BASE + 'customer-product-detail.php?id=' + id);
      await ca.locator('button:has-text("Keranjang")').first().click(); await ca.waitForTimeout(600);
    }
    await ca.goto(BASE + 'customer-cart.php');
    assert(await ca.locator('input[name^="qty["]').count() === 2, 'jumlah item di keranjang != 2');
    await ca.fill('input[name="qty[PRD-001]"]', '3');
    await Promise.all([ca.waitForNavigation({ waitUntil: 'load' }), ca.click('button:has-text("Perbarui")')]);
    assert(sql(`select qty from cart_items where customer_id='${aid()}' and product_id='PRD-001'`) === '3.00', 'qty tidak berubah');
  });
  await t('C02', 'Checkout multi-produk: 1 order 2 item, keranjang kosong', async () => {
    await ca.goto(BASE + 'customer-checkout.php?cart=1');
    await ca.fill('input[name=kota_pengiriman]', 'MALANG'); await ca.fill('textarea[name=alamat_pengiriman]', 'Jl. Uji Alfa 1');
    await Promise.all([ca.waitForNavigation({ waitUntil: 'load' }), ca.click('button[name=submit_order]')]);
    assert(/customer-order-detail\.php\?id=/.test(ca.url()), ca.url());
    ctx.order1 = new URL(ca.url()).searchParams.get('id');
    assert(sql(`select count(*) from order_items where order_id='${ctx.order1}'`) === '2', 'item order != 2');
    assert(sql(`select count(*) from cart_items where customer_id='${aid()}'`) === '0', 'keranjang belum kosong');
    assert(sql(`select count(*) from notifications where audience='admin' and pesan like '%pesanan%'`) >= '1', 'admin tidak dinotif');
  });
  await t('C03', 'Klik "Buat Pesanan" dua kali -> hanya 1 pesanan', async () => {
    await ca.goto(BASE + 'customer-product-detail.php?id=PRD-002');
    await ca.locator('button:has-text("Keranjang")').first().click(); await ca.waitForTimeout(600);
    const before = sql(`select count(*) from orders where customer_id='${aid()}'`);
    await ca.goto(BASE + 'customer-checkout.php?cart=1');
    await ca.fill('input[name=kota_pengiriman]', 'MALANG'); await ca.fill('textarea[name=alamat_pengiriman]', 'Jl. Uji Alfa 2');
    // kirim dua POST bersamaan dengan token & isi form yang sama
    const resp = await ca.evaluate(async () => {
      const f = document.querySelector('#checkout-form'); const fd = new FormData(f); fd.append('submit_order', '1');
      const post = () => fetch(location.href, { method: 'POST', body: new URLSearchParams(fd), redirect: 'manual' }).then(r => r.status);
      return Promise.all([post(), post()]);
    });
    const after = sql(`select count(*) from orders where customer_id='${aid()}'`);
    assert(+after - +before === 1, `pesanan bertambah ${+after - +before} (resp ${resp})`);
    ctx.order2 = sql(`select id from orders where customer_id='${aid()}' order by created_at desc, id desc limit 1`);
  });
  await t('C04', 'Jumlah tidak wajar ditolak (0, pecahan kecil, raksasa)', async () => {
    const before = sql('select count(*) from orders');
    for (const q of ['0', '0.0001', '99999999999']) {
      await ca.goto(BASE + 'customer-product-detail.php?id=PRD-006');
      const r = await ca.evaluate(async (qq) => {
        const fd = new FormData(); fd.append('action', 'add_to_cart'); fd.append('product_id', 'PRD-006'); fd.append('qty', qq);
        const res = await fetch('customer-api.php', { method: 'POST', body: fd, headers: { 'X-CSRF-Token': document.querySelector('meta[name=csrf-token]').content } });
        return res.json();
      }, q);
      const inCart = sql(`select coalesce(sum(qty),0) from cart_items where customer_id='${aid()}' and product_id='PRD-006'`);
      assert(r.status === 'error' && +inCart === 0, `qty ${q} diterima: ${JSON.stringify(r)} cart=${inCart}`);
    }
    // via checkout langsung
    await ca.goto(BASE + 'customer-cart.php');
    sql(`delete from cart_items where customer_id='${aid()}'`);
    assert(sql('select count(*) from orders') === before, 'order bertambah');
  });
  await t('C05', 'Request barang custom -> admin setujui + harga -> checkout dari request', async () => {
    await ca.goto(BASE + 'customer-request.php');
    await ca.fill('input[name=nama_barang]', 'GEARBOX CUSTOM UJI'); await ca.fill('input[name=qty]', '2');
    await ca.fill('textarea[name=spesifikasi]', 'Rasio 1:30'); 
    await Promise.all([ca.waitForNavigation({ waitUntil: 'load' }), ca.click('button[name=submit_request]')]);
    ctx.reqId = sql("select id from product_requests where nama_barang='GEARBOX CUSTOM UJI'");
    assert(ctx.reqId, 'request tidak tersimpan');
    await A.goto(BASE + 'admin-requests.php');
    await A.evaluate(id => { const row = document.querySelector(`[onclick*="${id}"]`); row.click(); }, ctx.reqId);
    await A.selectOption('#modal-select-status', 'approved');
    await A.fill('input[name=harga_disetujui]', '2500000');
    await Promise.all([A.waitForNavigation({ waitUntil: 'load' }), A.click('#modal-review button[type=submit], form:has(input[name=action_review]) button[type=submit]')]);
    assert(sql(`select status from product_requests where id='${ctx.reqId}'`) === 'approved', 'status request bukan approved');
    await ca.goto(BASE + 'customer-checkout.php?request_id=' + ctx.reqId);
    await ca.fill('input[name=kota_pengiriman]', 'SURABAYA'); await ca.fill('textarea[name=alamat_pengiriman]', 'Jl. Request 1');
    await Promise.all([ca.waitForNavigation({ waitUntil: 'load' }), ca.click('button[name=submit_order]')]);
    assert(/customer-order-detail\.php\?id=/.test(ca.url()), ca.url());
    ctx.order3 = new URL(ca.url()).searchParams.get('id');
    assert(sql(`select harga_satuan from order_items where order_id='${ctx.order3}'`) === '2500000.00', 'harga bukan dari request');
  });
  await t('C06', 'Request disetujui hanya bisa dipakai sekali', async () => {
    await ca.goto(BASE + 'customer-checkout.php?request_id=' + ctx.reqId);
    const txt = await bodyText(ca);
    assert(/belum disetujui|sudah diproses|Belum ada barang/i.test(txt), txt.slice(0, 200));
    assert(await ca.locator('button[name=submit_order]').count() === 0, 'form checkout masih ada');
  });
  // Penawaran (admin menyiapkan data lewat DB karena form penawaran panjang; alur kirim/revisi lewat UI)
  await t('C07', 'Penawaran V1 dikirim admin; customer melihatnya', async () => {
    const nm = sql("select nama from customers where email='alfa@test.com'");
    sql(`insert into penawaran (id, nomor, tanggal, customer, kota, total, status) values ('pn_uji1','SP/UJI/001', CURDATE(), '${nm}', 'MALANG', 5000000, 'Pending')`);
    sql("insert into penawaran_items (penawaran_id, deskripsi, qty, satuan, price, amount) values ('pn_uji1','BEARING UJI',5,'PCS',1000000,5000000)");
    await A.goto(BASE + 'penawaran.php'); await A.waitForTimeout(1200);
    await A.evaluate(() => kirimPn('pn_uji1')); await A.waitForTimeout(1200);
    assert(sql("select status from penawaran where id='pn_uji1'") === 'Dikirim', 'status bukan Dikirim: ' + sql("select status from penawaran where id='pn_uji1'"));
    await ca.goto(BASE + 'customer-quotations.php');
    assert(/BEARING UJI/.test(await bodyText(ca)), 'penawaran tidak tampil di customer');
  });
  await t('C08', 'Tolak/minta revisi wajib alasan; admin mendapat notifikasi', async () => {
    await ca.goto(BASE + 'customer-quotations.php');
    await Promise.all([ca.waitForNavigation({ waitUntil: 'load' }), ca.click('button[name=decision][value=revisi]')]);
    assert(sql("select status from penawaran where id='pn_uji1'") === 'Dikirim', 'revisi tanpa alasan lolos');
    await ca.fill('textarea[name=alasan]', 'Harga terlalu tinggi, mohon diskon');
    await Promise.all([ca.waitForNavigation({ waitUntil: 'load' }), ca.click('button[name=decision][value=revisi]')]);
    assert(sql("select status from penawaran where id='pn_uji1'") === 'Ditolak', 'status bukan Ditolak');
    assert(sql("select count(*) from notifications where audience='admin' and pesan like '%penawaran%'") >= '1', 'admin tidak dinotif');
  });
  await t('C09', 'Revisi V2 berisi salinan item', async () => {
    await A.goto(BASE + 'penawaran.php'); await A.waitForTimeout(1200);
    await A.evaluate(() => revisiPn('pn_uji1')); await A.waitForTimeout(1500);
    assert(sql("select count(*) from penawaran where parent_id='pn_uji1' and versi=2") === '1', 'V2 tidak terbentuk');
    ctx.pnV2 = sql("select id from penawaran where parent_id='pn_uji1' and versi=2");
    assert(sql(`select count(*) from penawaran_items where penawaran_id='${ctx.pnV2}'`) === '1', 'item tidak tersalin');
    assert(sql("select nomor<>'' from penawaran where id='pn_uji1'") === '1', 'nomor induk kosong');
  });
  await t('C10', 'Customer menyetujui V2 -> Disetujui + notifikasi admin', async () => {
    await A.evaluate(id => kirimPn(id), ctx.pnV2); await A.waitForTimeout(1200);
    await ca.goto(BASE + 'customer-quotations.php');
    await Promise.all([ca.waitForNavigation({ waitUntil: 'load' }), ca.locator(`form:has(input[name=id][value="${ctx.pnV2}"]) button[value=setuju]`).click()]);
    assert(sql(`select status from penawaran where id='${ctx.pnV2}'`) === 'Disetujui', 'status: ' + sql(`select status from penawaran where id='${ctx.pnV2}'`));
    assert(sql("select count(*) from notifications where audience='admin' and pesan like '%MENYETUJUI%'") >= '1', 'admin tidak dinotif setuju');
  });
  await t('C11', 'Penomoran otomatis PREFIX/0001/TT/BLN/THN', async () => {
    const ok0 = await A.evaluate(async () => {
      const list = await (await fetch('api.php?action=get_penawaran')).json();
      list.push({ id: 'pn_uji_auto', nomor: '', tanggal: new Date().toISOString().slice(0, 10), customer: 'X', items: [] });
      const r = await fetch('api.php?action=save_penawaran', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify(list) });
      return r.json();
    });
    assert(ok0.status === 'success', JSON.stringify(ok0));
    const rows = sql("select nomor from penawaran where id='pn_uji_auto'");
    assert(/^SP\/\d{4}\/TT\/[IVX]+\/\d{4}$/.test(rows), 'format nomor penawaran: ' + rows);
    // PO otomatis dari order (D01) dicek lagi di sana; di sini cek API nomor kosong untuk nota
    const ok = await A.evaluate(async () => {
      const list = await (await fetch('api.php?action=get_nota')).json();
      list.push({ id: 'nota_uji_auto', nomor: '', tanggal: new Date().toISOString().slice(0, 10), customer: 'X', items: [] });
      const r = await fetch('api.php?action=save_nota', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify(list) });
      return r.json();
    });
    assert(ok.status === 'success', JSON.stringify(ok));
    const n = sql("select nomor from nota where id='nota_uji_auto'");
    assert(/^INV\/\d{4}\/TT\/[IVX]+\/\d{4}$/.test(n), 'nomor nota: ' + n);
  });
}


const poRows = (oid) => sql(`select id from po where order_id='${oid}' order by nomor`).split('\n').filter(Boolean);
const csrfOf = async (page) => page.evaluate(() => document.querySelector('meta[name=csrf-token]').content);

async function uploadPayment(ca, oid, tipe, jumlah, opts = {}) {
  await ca.goto(BASE + 'customer-order-detail.php?id=' + oid);
  if (!(await ca.locator('select[name=tipe]').count())) return 'FORM-TIDAK-ADA: ' + (await bodyText(ca)).slice(0, 300);
  await ca.selectOption('select[name=tipe]', tipe); await ca.fill('input[name=jumlah]', String(jumlah));
  await ca.fill('input[name=nama_pengirim]', 'Uji Alfa'); await ca.fill('input[name=bank_pengirim]', 'BCA');
  await ca.setInputFiles('input[name=bukti]', opts.file || { name: 'bukti.png', mimeType: 'image/png', buffer: PNG });
  await Promise.all([ca.waitForNavigation({ waitUntil: 'load' }), ca.click('button[name=submit_payment]')]);
  return bodyText(ca);
}
async function verifyPayment(A, oid, decision, note = '') {
  await A.goto(BASE + 'admin-order-detail.php?id=' + oid);
  const form = A.locator('form:has(input[name=act][value=verify_payment])').first();
  if (note) await form.locator('input[name=catatan_admin]').fill(note);
  await Promise.all([A.waitForNavigation({ waitUntil: 'load' }), form.locator(`button[value=${decision}]`).click()]);
  return bodyText(A);
}
const orderRow = (oid) => sql(`select status_pembayaran,total_dibayar,status_pesanan from orders where id='${oid}'`).split('\t');

async function sectionD(b) {
  console.log('\nD. PO, pembayaran, pengiriman');
  const A = ctx.admin, ca = ctx.ca;
  await t('D01', 'Satu order -> beberapa PO (dipecah per supplier)', async () => {
    await A.goto(BASE + 'admin-order-detail.php?id=' + ctx.order1);
    const sels = A.locator('select[name^="supplier["]');
    assert(await sels.count() === 2, 'jumlah baris item != 2');
    const vals = await sels.evaluateAll(els => els.map(e => e.value));
    assert(vals.every(Boolean) && new Set(vals).size === 2, 'supplier final tidak terpilih berbeda: ' + vals);
    await Promise.all([A.waitForNavigation({ waitUntil: 'load' }), A.click('button:has-text("Buat PO per Supplier")')]);
    const pos = poRows(ctx.order1); assert(pos.length === 2, 'jumlah PO ' + pos.length + ' | halaman: ' + (await bodyText(A)).match(/(✅|⚠️)[^🔔]{0,200}/)?.[0]);
    ctx.po1 = pos[0];
    const nom = sql(`select nomor from po where order_id='${ctx.order1}' order by nomor`).split('\n');
    assert(nom.every(n => /^PO\/\d{4}\/TT\/[IVX]+\/\d{4}$/.test(n)), 'format nomor: ' + nom);
  });
  await t('D02', 'Cetak PO menampilkan nomor, supplier, item, total', async () => {
    const p = await A.context().newPage();
    await p.goto(BASE + 'print-po.php?id=' + ctx.po1); await p.waitForTimeout(1500);
    const txt = await bodyText(p);
    const [nomor, sup] = sql(`select nomor, supplier from po where id='${ctx.po1}'`).split('\t');
    assert(txt.includes(nomor), 'nomor PO tidak tampil'); assert(txt.includes(sup), 'supplier tidak tampil');
    assert(/Rp|TOTAL|Total/i.test(txt), 'total tidak tampil');
    await p.close();
  });
  const total = () => parseFloat(sql(`select total from orders where id='${ctx.order1}'`));
  await t('D03', 'Upload bukti DP: status menunggu verifikasi', async () => {
    const dp = Math.round(total() * 0.3);
    const txt = await uploadPayment(ca, ctx.order1, 'dp', dp);
    assert(/terkirim|diverifikasi|menunggu/i.test(txt), txt.slice(0, 200));
    assert(sql(`select count(*) from payments where order_id='${ctx.order1}' and status='menunggu' and tipe='dp'`) === '1', 'payment tidak tercatat');
    assert(orderRow(ctx.order1)[0] === 'sudah_bayar', 'status order: ' + orderRow(ctx.order1)[0]);
    ctx.dp = dp;
  });
  await t('D04', 'File bukan gambar/PDF ditolak', async () => {
    const before = sql('select count(*) from payments');
    await uploadPayment(ca, ctx.order1, 'partial', 1000, { file: { name: 'evil.php', mimeType: 'image/png', buffer: Buffer.from('<?php echo 1;') } });
    assert(sql('select count(*) from payments') === before, 'file PHP diterima sebagai bukti');
    await uploadPayment(ca, ctx.order1, 'partial', 1000, { file: { name: 'a.txt', mimeType: 'text/plain', buffer: Buffer.from('hello') } });
    assert(sql('select count(*) from payments') === before, 'file txt diterima');
  });
  await t('D05', 'Jumlah melebihi sisa tagihan ditolak', async () => {
    const before = sql('select count(*) from payments');
    const txt = await uploadPayment(ca, ctx.order1, 'pelunasan', Math.round(total()) + 5000000);
    assert(sql('select count(*) from payments') === before, 'pembayaran berlebih diterima');
  });
  await t('D06', 'Verifikasi DP: status DP, sisa tagihan berkurang', async () => {
    await verifyPayment(A, ctx.order1, 'terima');
    const [st, paid] = orderRow(ctx.order1);
    assert(st === 'dp', 'status ' + st); assert(Math.abs(parseFloat(paid) - ctx.dp) < 1, 'total_dibayar ' + paid);
    await ca.goto(BASE + 'customer-order-detail.php?id=' + ctx.order1);
    assert(/DP/.test(await bodyText(ca)), 'customer tidak melihat DP');
  });
  await t('D08', 'Tolak bukti: catatan wajib, customer dinotif & bisa unggah ulang', async () => {
    await uploadPayment(ca, ctx.order1, 'term', 1500000);
    const pid = sql(`select max(id) from payments where order_id='${ctx.order1}'`);
    await verifyPayment(A, ctx.order1, 'tolak', '');
    assert(sql(`select status from payments where id=${pid}`) === 'menunggu', 'ditolak tanpa catatan');
    await verifyPayment(A, ctx.order1, 'tolak', 'Nominal tidak sesuai mutasi');
    assert(sql(`select status from payments where id=${pid}`) === 'ditolak', 'tidak ditolak');
    assert(+sql(`select count(*) from notifications where audience='customer' and pesan like '%ditolak%'`) >= 1, 'customer tidak dinotif');
    assert(orderRow(ctx.order1)[0] === 'dp', 'status berubah akibat penolakan: ' + orderRow(ctx.order1)[0]);
    const txt = await uploadPayment(ca, ctx.order1, 'term', 1500000);   // unggah ulang
    assert(sql(`select count(*) from payments where order_id='${ctx.order1}' and status='menunggu'`) === '1', 'tidak bisa unggah ulang');
  });
  await t('D09', 'Status Term lalu Partial mengikuti pembayaran terverifikasi', async () => {
    await verifyPayment(A, ctx.order1, 'terima');          // term 1.5jt menunggu -> terverifikasi
    assert(orderRow(ctx.order1)[0] === 'term', 'status ' + orderRow(ctx.order1)[0]);
    await uploadPayment(ca, ctx.order1, 'partial', 1000000);
    await verifyPayment(A, ctx.order1, 'terima');
    assert(orderRow(ctx.order1)[0] === 'partial', 'status ' + orderRow(ctx.order1)[0]);
  });
  await t('D07', 'Pelunasan -> status Paid', async () => {
    const sisa = Math.round(total() - parseFloat(orderRow(ctx.order1)[1]));
    await uploadPayment(ca, ctx.order1, 'pelunasan', sisa);
    await verifyPayment(A, ctx.order1, 'terima');
    const [st, paid] = orderRow(ctx.order1);
    assert(st === 'paid', 'status ' + st); assert(Math.abs(parseFloat(paid) - total()) < 1, `dibayar ${paid} vs total ${total()}`);
    const txt = await uploadPayment(ca, ctx.order1, 'pelunasan', 1000);
    assert(sql(`select count(*) from payments where order_id='${ctx.order1}' and jumlah=1000`) === '0', 'bayar lagi setelah lunas diterima');
  });
  await t('D10', 'Admin kirim barang (resi) -> status Dikirim, customer dinotif', async () => {
    await A.goto(BASE + 'admin-order-detail.php?id=' + ctx.order1);
    const f = A.locator('form:has(input[name=act][value=update_status])');
    await f.locator('select[name=status_pesanan]').selectOption('dikirim'); await f.locator('input[name=no_resi]').fill('RESI-UJI-001');
    await Promise.all([A.waitForNavigation({ waitUntil: 'load' }), f.locator('button').click()]);
    assert(sql(`select status_pesanan from orders where id='${ctx.order1}'`) === 'dikirim', 'status bukan dikirim');
    assert(sql(`select dikirim_at is not null from orders where id='${ctx.order1}'`) === '1', 'dikirim_at kosong');
    assert(+sql("select count(*) from notifications where audience='customer' and pesan like '%RESI-UJI-001%'") >= 1, 'customer tidak dinotif resi');
  });
  await t('D11', 'Customer konfirmasi terima -> Selesai', async () => {
    await ca.goto(BASE + 'customer-order-detail.php?id=' + ctx.order1);
    await Promise.all([ca.waitForNavigation({ waitUntil: 'load' }), ca.click('button[name=confirm_received]')]);
    assert(sql(`select status_pesanan from orders where id='${ctx.order1}'`) === 'selesai', 'status ' + sql(`select status_pesanan from orders where id='${ctx.order1}'`));
  });
  await t('D12', 'Selesai otomatis setelah >7 hari dikirim', async () => {
    sql(`update orders set status_pesanan='dikirim', dikirim_at=NOW() - INTERVAL 9 DAY, tanggal_terima=NULL where id='${ctx.order3}'`);
    await ca.goto(BASE + 'customer-orders.php'); await ca.waitForTimeout(500);
    assert(sql(`select status_pesanan from orders where id='${ctx.order3}'`) === 'selesai', 'tidak otomatis selesai: ' + sql(`select status_pesanan from orders where id='${ctx.order3}'`));
    sql(`update orders set status_pesanan='pending', dikirim_at=NULL, tanggal_terima=NULL where id='${ctx.order3}'`);
  });
  await t('D13', 'Customer membuka invoice pesanannya', async () => {
    const p = await ca.context().newPage();
    await p.goto(BASE + 'customer-invoice.php?id=' + ctx.order1); const txt = await bodyText(p);
    assert(txt.includes(sql(`select nomor_order from orders where id='${ctx.order1}'`)), 'nomor order tidak ada di invoice');
    assert(/Rp/.test(txt), 'tidak ada nominal'); await p.close();
  });
}

async function sectionE(b) {
  console.log('\nE. Pembatalan & retur');
  const A = ctx.admin, ca = ctx.ca;
  await t('E01', 'Batal sebelum PO: alasan wajib, status Dibatalkan', async () => {
    await ca.goto(BASE + 'customer-order-detail.php?id=' + ctx.order2);
    await ca.evaluate(() => { document.querySelector('textarea[name=alasan_batal]').removeAttribute('required'); });
    await Promise.all([ca.waitForNavigation({ waitUntil: 'load' }), ca.click('button[name=cancel_order]')]);
    assert(sql(`select status_pesanan from orders where id='${ctx.order2}'`) !== 'dibatalkan', 'batal tanpa alasan lolos');
    await ca.fill('textarea[name=alasan_batal]', 'Salah pilih produk');
    await Promise.all([ca.waitForNavigation({ waitUntil: 'load' }), ca.click('button[name=cancel_order]')]);
    assert(sql(`select status_pesanan from orders where id='${ctx.order2}'`) === 'dibatalkan', 'tidak dibatalkan');
    assert(+sql("select count(*) from notifications where audience='admin' and pesan like '%Salah pilih produk%'") >= 1 || +sql("select count(*) from notifications where audience='admin' and pesan like '%batal%'") >= 1, 'admin tidak dinotif');
  });
  await t('E02', 'Batal setelah PO terbit ditolak', async () => {
    await A.goto(BASE + 'admin-order-detail.php?id=' + ctx.order3);
    const sel = A.locator('select[name^="supplier["]').first();
    if (!(await sel.inputValue())) await sel.selectOption('2');
    await Promise.all([A.waitForNavigation({ waitUntil: 'load' }), A.click('button:has-text("Buat PO per Supplier")')]);
    assert(poRows(ctx.order3).length === 1, 'PO order3 tidak terbit');
    await ca.goto(BASE + 'customer-order-detail.php?id=' + ctx.order3);
    assert(await ca.locator('button[name=cancel_order]').count() === 0, 'tombol batal masih ada');
    const tok = await csrfOf(ca);
    await ca.evaluate(async ([id, tk]) => { await fetch('customer-order-detail.php?id=' + id, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ cancel_order: '1', alasan_batal: 'coba paksa', csrf_token: tk }) }); }, [ctx.order3, tok]);
    assert(sql(`select status_pesanan from orders where id='${ctx.order3}'`) !== 'dibatalkan', 'berhasil dibatalkan setelah PO');
  });
  const submitReturn = async (alasan, solusi, files) => {
    await ca.goto(BASE + 'customer-return-new.php?order_id=' + ctx.order1);
    await ca.fill('textarea[name=alasan]', alasan); await ca.selectOption('select[name=solusi]', solusi);
    if (files) await ca.setInputFiles('input[name="media[]"]', files);
    await Promise.all([ca.waitForNavigation({ waitUntil: 'load' }), ca.click('button:has-text("Kirim Pengajuan Retur")')]);
    return bodyText(ca);
  };
  await t('E03', 'Ajukan retur dengan foto: tersimpan, admin dinotif', async () => {
    await submitReturn('Bearing retak saat dibuka, ukuran tidak sesuai', 'ganti', [{ name: 'rusak.png', mimeType: 'image/png', buffer: PNG }]);
    ctx.ret1 = sql(`select id from returns where order_id='${ctx.order1}' order by created_at limit 1`);
    assert(ctx.ret1, 'retur tidak tersimpan');
    assert(sql(`select count(*) from return_media where return_id='${ctx.ret1}'`) === '1', 'media tidak tersimpan');
    assert(+sql("select count(*) from notifications where audience='admin' and pesan like '%retur%'") >= 1, 'admin tidak dinotif');
  });
  const retAction = async (rid, decision, note = '') => {
    await A.goto(BASE + 'admin-returns.php');
    const f = A.locator(`#r-${rid} form`).first();
    if (note) await f.locator('input[name=catatan_admin]').fill(note);
    await Promise.all([A.waitForNavigation({ waitUntil: 'load' }), A.locator(`#r-${rid} button[value=${decision}]`).click()]);
  };
  await t('E05', 'Tolak retur: alasan wajib', async () => {
    await retAction(ctx.ret1, 'tolak', '');
    assert(sql(`select status from returns where id='${ctx.ret1}'`) === 'diajukan', 'ditolak tanpa alasan');
    await retAction(ctx.ret1, 'tolak', 'Kerusakan akibat pemasangan');
    assert(sql(`select status from returns where id='${ctx.ret1}'`) === 'ditolak', 'tidak ditolak');
  });
  await t('E04', 'Retur disetujui lalu selesai (ganti & refund)', async () => {
    for (const solusi of ['ganti', 'refund']) {
      await submitReturn('Barang pengganti ' + solusi + ' diperlukan, rusak', solusi, [{ name: 'r.png', mimeType: 'image/png', buffer: PNG }]);
      const rid = sql(`select id from returns where order_id='${ctx.order1}' and status='diajukan' order by created_at desc limit 1`);
      assert(rid, 'retur ' + solusi + ' tidak terbentuk (mungkin diblok setelah penolakan)');
      await retAction(rid, 'setuju'); assert(sql(`select status from returns where id='${rid}'`) === 'disetujui', 'tidak disetujui');
      await retAction(rid, 'selesai'); assert(sql(`select status from returns where id='${rid}'`) === 'selesai', 'tidak selesai');
      assert(+sql(`select count(*) from notifications where audience='customer' and pesan like '%${sql(`select nomor_retur from returns where id='${rid}'`)}%'`) >= 2, 'customer kurang notif');
    }
  });
  await t('E06', 'Retur ditolak bila bukan foto/video & tanpa lampiran', async () => {
    const before = sql('select count(*) from returns');
    await submitReturn('Barang rusak saat diterima pelanggan', 'ganti', [{ name: 'x.txt', mimeType: 'text/plain', buffer: Buffer.from('x') }]);
    assert(sql('select count(*) from returns') === before, 'retur dengan txt diterima');
  });
}

async function sectionF(b) {
  console.log('\nF. Dashboard, notifikasi, dokumen');
  const A = ctx.admin;
  const fs = require('fs');
  const chartJs = fs.existsSync('/tmp/chartjs/package/dist/chart.umd.js') ? fs.readFileSync('/tmp/chartjs/package/dist/chart.umd.js', 'utf8') : null;
  await t('F01', 'Grafik PO & Nota terisi, tombol 3/6/12 bulan berfungsi', async () => {
    assert(chartJs, 'Chart.js lokal tidak tersedia (CDN diblokir di lingkungan uji)');
    const p = await A.context().newPage();
    await p.route('**/chart.umd.min.js', r => r.fulfill({ contentType: 'application/javascript', body: chartJs }));
    const errs = []; p.on('pageerror', e => errs.push(e.message));
    await p.goto(BASE + 'index.php'); await p.waitForTimeout(2500);
    const info = await p.evaluate(() => {
      const c1 = Chart.getChart('chart-po'), c2 = Chart.getChart('chart-nota');
      return { po: c1.data.datasets[0].data.reduce((a, b) => a + b, 0), nota: c2.data.datasets[0].data.reduce((a, b) => a + b, 0), n: c1.data.labels.length };
    });
    assert(info.po > 0, 'grafik PO kosong'); assert(info.nota > 0, 'grafik Nota kosong'); assert(info.n === 6, 'label default != 6');
    await p.click('.period-btn[data-chart=po][data-n="12"]'); await p.waitForTimeout(400);
    assert(await p.evaluate(() => Chart.getChart('chart-po').data.labels.length) === 12, 'tombol 12 bln gagal');
    await p.click('.period-btn[data-chart=po][data-n="3"]'); await p.waitForTimeout(400);
    assert(await p.evaluate(() => Chart.getChart('chart-po').data.labels.length) === 3, 'tombol 3 bln gagal');
    assert(!errs.length, errs.join('|')); await p.close();
  });
  await t('F02', 'Banner dokumen jatuh tempo muncul dengan hitungan', async () => {
    const p = await A.context().newPage(); await p.goto(BASE + 'index.php'); await p.waitForTimeout(2000);
    const vis = await p.locator('#overdue-alert').isVisible();
    const n = +(await p.locator('#overdue-count-nota').innerText()), q = +(await p.locator('#overdue-count-po').innerText());
    assert(vis && n + q > 0, `banner vis=${vis} nota=${n} po=${q}`);
    const notaOver = sql("select count(*) from nota where status<>'Lunas' and status<>'Sementara' and DATE_ADD(tanggal, INTERVAL COALESCE(termin,30) DAY) < CURDATE()");
    assert(Math.abs(n - +notaOver) <= 2, `hitungan nota ${n} vs SQL ${notaOver}`); await p.close();
  });
  await t('F03', 'Lonceng notifikasi: angka naik; dibuka -> terbaca', async () => {
    const p = await A.context().newPage(); await p.goto(BASE + 'index.php'); await p.waitForTimeout(1500);
    const cnt = +(await p.locator('#notif-count').innerText() || 0);
    assert(cnt > 0, 'angka belum-dibaca 0 padahal ada notifikasi: ' + sql("select count(*) from notifications where audience='admin' and is_read=0"));
    await p.click('#notif-bell'); await p.waitForTimeout(1200);
    assert(+sql("select count(*) from notifications where audience='admin' and is_read=0") === 0, 'belum ditandai terbaca');
    sql(`insert into notifications (audience, pesan, link) values ('admin','Uji baru','index.php')`);
    await p.waitForTimeout(16000);
    assert(+(await p.locator('#notif-count').innerText() || 0) >= 1, 'polling tidak memperbarui lonceng'); await p.close();
  });
  await t('F04', 'Cetak Penawaran/PO/Nota/Surat Jalan', async () => {
    const p = await A.context().newPage();
    const nota = sql("select id from nota where status<>'Sementara' order by tanggal desc limit 1");
    const pn = sql("select id from penawaran where id='pn_uji1'");
    const po = sql("select id from po where order_id is null order by tanggal desc limit 1");
    for (const [u, re] of [[`print-nota.php?id=${nota}&mode=invoice`, /Total|TOTAL/i], [`print-nota.php?id=${nota}&mode=sj`, /Surat Jalan|SURAT JALAN/i],
                           [`print-penawaran.php?id=${pn}`, /BEARING UJI/], [`print-po.php?id=${po}`, /Purchase Order|PURCHASE ORDER|PO/]]) {
      await p.goto(BASE + u); await p.waitForTimeout(1500);
      const txt = await bodyText(p); assert(re.test(txt) && !/Fatal|Warning:|Parse error/.test(txt), u + ' -> ' + txt.slice(0, 120));
    }
    await p.close();
  });
  await t('F05', 'Tampilan HP 390px tanpa overflow horizontal', async () => {
    const mAdmin = await b.newContext({ viewport: { width: 390, height: 844 } }); const pa = await mAdmin.newPage();
    await adminLogin(pa);
    const bad = [];
    for (const u of ['index.php', 'po.php', 'nota.php', 'penawaran.php', 'master.php', 'admin-orders.php', 'admin-order-detail.php?id=' + ctx.order1, 'admin-products.php', 'admin-customers.php', 'admin-returns.php', 'admin-requests.php']) {
      await pa.goto(BASE + u); await pa.waitForTimeout(700);
      const w = await pa.evaluate(() => [document.documentElement.scrollWidth, window.innerWidth]);
      if (w[0] > w[1] + 2) bad.push(`${u}(${w[0]}>${w[1]})`);
    }
    await mAdmin.close();
    const mc = await b.newContext({ viewport: { width: 390, height: 844 } }); const pc = await mc.newPage();
    await custLogin(pc, 'alfa@test.com', 'Alfa12345');
    for (const u of ['customer-dashboard.php', 'customer-products.php', 'customer-product-detail.php?id=PRD-001', 'customer-cart.php', 'customer-orders.php', 'customer-order-detail.php?id=' + ctx.order1, 'customer-request.php', 'customer-quotations.php', 'customer-returns.php', 'customer-help.php']) {
      await pc.goto(BASE + u); await pc.waitForTimeout(600);
      const w = await pc.evaluate(() => [document.documentElement.scrollWidth, window.innerWidth]);
      if (w[0] > w[1] + 2) bad.push(`${u}(${w[0]}>${w[1]})`);
    }
    await mc.close();
    assert(!bad.length, 'overflow: ' + bad.join(', '));
  });
}

async function sectionG(b) {
  console.log('\nG. Keamanan');
  const A = ctx.admin, ca = ctx.ca, cb = ctx.cb;
  await t('G01', 'api.php tanpa login -> 401 JSON', async () => {
    const r = await b.newContext(); const res = await r.request.get(BASE + 'api.php?action=get_po');
    assert(res.status() === 401, 'status ' + res.status()); const j = await res.json(); assert(j.status === 'error', JSON.stringify(j));
    const r2 = await r.request.post(BASE + 'api.php?action=save_po', { data: '[]', headers: { 'Content-Type': 'application/json' } });
    assert([401, 403].includes(r2.status()), 'POST status ' + r2.status()); await r.close();
  });
  await t('G02', 'POST tanpa token CSRF -> 403', async () => {
    const before = sql('select count(*) from po');
    const res = await A.context().request.post(BASE + 'api.php?action=save_po', { data: '[]', headers: { 'Content-Type': 'application/json' } });
    assert(res.status() === 403, 'api status ' + res.status());
    const res2 = await ca.context().request.post(BASE + 'customer-cart.php', { form: { act: 'remove', product_id: 'PRD-001' } });
    assert(res2.status() === 403, 'form status ' + res2.status());
    assert(sql('select count(*) from po') === before, 'data berubah');
  });
  await t('G03', 'Customer B tidak bisa membuka pesanan/invoice/bukti customer A', async () => {
    const pid = sql(`select id from payments where order_id='${ctx.order1}' limit 1`);
    for (const u of ['customer-order-detail.php?id=' + ctx.order1, 'customer-invoice.php?id=' + ctx.order1, 'download.php?t=payment&id=' + pid]) {
      const res = await cb.context().request.get(BASE + u);
      const txt = await res.text();
      const nomor = sql(`select nomor_order from orders where id='${ctx.order1}'`);
      assert(res.status() >= 400 || (!txt.includes(nomor) && !txt.startsWith('\x89PNG')), `${u} bocor (status ${res.status()})`);
    }
    // admin tetap bisa
    const ok = await A.context().request.get(BASE + 'download.php?t=payment&id=' + pid); assert(ok.status() === 200, 'admin ditolak: ' + ok.status());
  });
  await t('G04', 'Akses langsung /uploads, /db/*.sql, /README.md ditolak', async () => {
    const up = sql(`select bukti_file from payments where bukti_file is not null limit 1`);
    const anon = await b.newContext();
    const out = {};
    for (const u of [up, 'db/ppsi_tidarteknik.sql', 'README.md', 'config.php', 'uploads/', 'lib.php']) {
      const r = await anon.request.get(BASE + u); const txt = await r.text();
      out[u] = r.status();
      const leaked = r.status() === 200 && (u === up || /\.(sql|md)$/.test(u) || /<\?php|\$conn|mysqli_/.test(txt.slice(0, 2000)));
      assert(!leaked, `${u} dapat diakses (status ${r.status()})`);
    }
    await anon.close();
  });
  await t('G05', 'Nama berisi tag HTML tampil sebagai teks di semua halaman', async () => {
    const X = '<img src=x onerror=window.__xss=1>';
    const bid = sql("select id from customers where email='beta@test.com'");
    sql(`update customers set nama='${X}' where id='${bid}'`);
    sql(`insert into orders (id,nomor_order,customer_id,tanggal,subtotal,ongkir,total,alamat_pengiriman,kota_pengiriman,metode_pembayaran,status_pembayaran,status_pesanan) values ('ord_xss','ORD-XSS','${bid}',CURDATE(),1000,0,1000,'${X}','${X}','Transfer','menunggu_pembayaran','pending')`);
    sql(`insert into order_items (order_id,nama_barang,qty,satuan,harga_satuan,subtotal) values ('ord_xss','${X}',1,'PCS',1000,1000)`);
    sql(`insert into nota (id,nomor,tanggal,customer,alamat_invoice,status,total) values ('nota_xss','XSS/1',CURDATE(),'${X}','${X}','Belum Bayar',1000)`);
    sql(`insert into nota_items (nota_id,qty,satuan,deskripsi,price,amount) values ('nota_xss',1,'PCS','${X}',1000,1000)`);
    sql(`insert into po (id,nomor,tanggal,supplier,kota,total,status) values ('po_xss','POXSS/1',CURDATE(),'${X}','${X}',1000,'Belum Bayar')`);
    sql(`insert into po_items (po_id,deskripsi,qty,price,amount,specs) values ('po_xss','${X}',1,1000,1000,'[]')`);
    sql(`insert into penawaran (id,nomor,tanggal,customer,kota,att,total) values ('pn_xss','PNXSS/1',CURDATE(),'${X}','${X}','${X}',1000)`);
    sql(`insert into penawaran_items (penawaran_id,deskripsi,qty,satuan,price,amount) values ('pn_xss','${X}',1,'PCS',1000,1000)`);
    const hits = [];
    const check = async (page, u) => {
      await page.evaluate(() => { window.__xss = undefined; });
      await page.goto(BASE + u); await page.waitForTimeout(1500);
      if (await page.evaluate(() => window.__xss === 1)) hits.push(u);
      if (await page.locator('img[src="x"]').count() > 0) hits.push(u + '(img)');
    };
    const px = await A.context().newPage();
    for (const u of ['admin-customers.php', 'admin-orders.php', 'admin-order-detail.php?id=ord_xss', 'admin-returns.php', 'admin-requests.php', 'customer-history.php', 'master.php', 'nota.php', 'po.php', 'penawaran.php', 'index.php',
                     'print-nota.php?id=nota_xss&mode=invoice', 'print-nota.php?id=nota_xss&mode=sj', 'print-po.php?id=po_xss', 'print-penawaran.php?id=pn_xss']) await check(px, u);
    await px.close();
    assert(!hits.length, 'XSS tereksekusi/ter-render di: ' + [...new Set(hits)].join(', '));
  });
  await t('G06', 'Simpan ulang data lama lewat UI tidak mengubah jumlah; daftar kosong tidak menghapus', async () => {
    const q = "select (select count(*) from po),(select count(*) from nota),(select count(*) from penawaran),(select count(*) from customers),(select count(*) from suppliers)";
    const before = sql(q);
    const res = await A.evaluate(async () => {
      const tok = document.querySelector('meta[name=csrf-token]')?.content || '';
      const out = [];
      for (const [g, s] of [['get_po', 'save_po'], ['get_nota', 'save_nota'], ['get_penawaran', 'save_penawaran'], ['get_customers', 'save_customers'], ['get_suppliers', 'save_suppliers']]) {
        const list = await (await fetch('api.php?action=' + g)).json();
        const hdr = { 'Content-Type': 'application/json', 'X-CSRF-Token': tok };
        out.push((await (await fetch('api.php?action=' + s, { method: 'POST', headers: hdr, body: JSON.stringify(list) })).json()).status);
        out.push((await (await fetch('api.php?action=' + s, { method: 'POST', headers: hdr, body: '[]' })).json()).status);
      }
      return out;
    }).catch(async () => { await A.goto(BASE + 'index.php'); return null; });
    assert(res, 'evaluate gagal');
    assert(sql(q) === before, `jumlah berubah: ${before} -> ${sql(q)} (resp ${res})`);
  });
  skip('G07', 'Konfigurasi produksi (hash admin, base_url, HTTPS)', 'cek manual di server produksi');
}

async function sectionH() {
  console.log('\nH. Kompatibilitas');
  skip('H02', 'Edge / Firefox', 'hanya Chromium tersedia di lingkungan uji');
  skip('H03', 'HP asli (Android/iOS)', 'perlu perangkat fisik; emulasi lebar 390px dicakup F05');
  skip('H04', 'MySQL 8 dan MariaDB', 'hanya MariaDB 10.11 di lingkungan uji; import dump sukses (resetDb)');
}

(async () => {
  L.resetDb();
  const b = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium', args: ['--no-sandbox'] });
  try {
    await sectionA(b);
    if (run('B')) await sectionB(b);
    if (run('C')) await sectionC(b);
    if (run('D')) await sectionD(b);
    if (run('E')) await sectionE(b);
    if (run('F')) await sectionF(b);
    if (run('G')) await sectionG(b);
    await sectionH();
  } finally {
    await b.close();
    const ok = L.results.filter(r => r.ok === true).length, bad = L.results.filter(r => r.ok === false), sk = L.results.filter(r => r.ok === null).length;
    console.log(`\nRingkas: ${ok} lulus, ${bad.length} gagal, ${sk} dilewati dari ${L.results.length}`);
    require('fs').writeFileSync(require('path').join(__dirname, 'hasil-e2e.json'), JSON.stringify(L.results, null, 2));
    process.exitCode = bad.length ? 1 : 0;
  }
})();
