<?php
require_once 'auth.php';
requireLogin();
include 'db.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cetak Penawaran — CV. Tidar Teknik</title>
<style>
.kop-logo img { width:56px; height:56px; object-fit:contain; }
.letter-content { font-size: 10.5pt; line-height: 1.5; color: #1e293b; margin-top: 16px; margin-bottom: 16px; }
.letter-header { margin-bottom: 24px; font-size: 10.5pt; line-height: 1.4; color: #1e293b; }
.letter-header table { width: 100%; }
.letter-header td { vertical-align: top; padding: 2px 0; }
.sub-judul-box { font-weight: bold; text-align: center; font-size: 11pt; margin: 20px 0 10px 0; text-transform: uppercase; text-decoration: underline; }
.signature-box { display: flex; justify-content: flex-end; margin-top: 40px; }
.signature-content { text-align: center; width: 200px; }
.signature-content img { height: 70px; object-fit: contain; margin: 10px 0; }
.notes-box { margin-top: 20px; font-weight: bold; font-size: 10.5pt; white-space: pre-wrap; line-height: 1.4; }
</style>
<link rel="stylesheet" href="css/print.css">
</head>
<body>

<div class="print-toolbar no-print">
  <h2>🖨️ Preview Penawaran Harga</h2>
  <button class="btn-back" onclick="if(window.opener){window.close()}else{window.location.href='penawaran.php'}">← Back</button>
  <button class="btn-print-action" onclick="window.print()">🖨️ Print / Save PDF</button>
</div>

<div class="print-page-wrapper">
  <div class="print-doc" id="pn-print-doc">
    <div style="text-align:center;padding:60px;color:#94a3b8">Loading document...</div>
  </div>
</div>

<script src="js/app.js?v=1.1"></script>
<script>
document.addEventListener('DOMContentLoaded', async () => {
  const params = new URLSearchParams(window.location.search);
  const id = params.get('id');
  if (!id) { document.getElementById('pn-print-doc').innerHTML = '<div style="text-align:center;padding:60px;color:red">ID Penawaran tidak ditemukan</div>'; return; }

  await syncLocalToDB();

  const pnList = await dbStore.get(KEYS.PENAWARAN_LIST);
  const p = pnList.find(x => x.id == id || x.nomor == id);
  if (!p) { document.getElementById('pn-print-doc').innerHTML = '<div style="text-align:center;padding:60px;color:red">Penawaran tidak ditemukan</div>'; return; }

  document.title = `Penawaran ${p.nomor} — CV. Tidar Teknik`;

  const items = p.items || [];
  const t = calcPenawaranTotals(items, p.disc || 0, p.dp || 0, p.ppnMode || 'none');
  const showTotal = (p.show_total === undefined || p.show_total === null) ? true : Boolean(p.show_total);

  const itemRows = items.map((item, i) => {
    const s = item.specs || {};
    const parts = [];
    if (s.capacity) parts.push(['Capacity', s.capacity]);
    if (s.pressure) parts.push(['Pressure', s.pressure]);
    if (s.inoutlet) parts.push(['In/Outlet', s.inoutlet]);
    if (s.power)    parts.push(['Power', s.power]);
    if (s.material) parts.push(['Material', s.material]);

    const specHtml = parts.length > 0 ? `
      <div style="width: 230px; padding-left: 15px; border-left: 1px solid #e2e8f0; flex-shrink: 0;">
        <table style="width: 100%; border: none; font-size: 8.2pt; line-height: 1.4; border-collapse: collapse;">
          ${parts.map(p => `
            <tr>
              <td style="padding: 1px 0; color: #64748b; width: 70px; border: none;">${p[0]}</td>
              <td style="padding: 1px 4px; color: #64748b; width: 8px; border: none;">:</td>
              <td style="padding: 1px 0; color: #1e293b; font-weight: 500; border: none;">${p[1]}</td>
            </tr>
          `).join('')}
        </table>
      </div>` : '';

    return `<tr>
      <td class="td-no text-center" style="vertical-align: top; padding-top: 12px;">${i+1}</td>
      <td class="td-desc" style="text-align: left; vertical-align: top; padding: 10px 12px;">
        <div style="display: flex; gap: 10px; align-items: flex-start; text-align: left;">
          <div style="flex: 1; text-align: left;">
            <div style="color: #0f172a; margin-bottom: 4px; font-size: 10pt; line-height: 1.3;">${item.desc||''}</div>
            ${item.note_item ? `<div style="font-size: 8.5pt; color: #64748b; font-weight: normal; margin-top: 6px; padding-top: 4px; border-top: 1px dashed #f1f5f9;">Note: ${item.note_item}</div>` : ''}
          </div>
          ${specHtml}
        </div>
      </td>
      <td class="td-qty text-center" style="vertical-align: top; padding-top: 12px;">${Math.floor(item.qty)||0}</td>
      <td class="text-center" style="vertical-align: top; padding-top: 12px;">${item.satuan||''}</td>
      <td class="td-price text-right" style="vertical-align: top; padding-top: 12px;">${item.price ? (parseFloat(item.price)||0).toLocaleString('id-ID') : ''}</td>
      <td class="td-amount text-right amount-cell" style="vertical-align: top; padding-top: 12px;">${item.amount ? (parseFloat(item.amount)||0).toLocaleString('id-ID') : ''}</td>
    </tr>`;
  }).join('');

  document.getElementById('pn-print-doc').innerHTML = `
    <div class="watermark-container"><img src="img/watermark.png" alt="Watermark"></div>
    
    <!-- KOP -->
    <div class="kop" style="margin-bottom:30px">
      <div class="kop-logo"><img src="img/logo.png" alt="CV. Tidar Teknik"></div>
      <div class="kop-info">
        <h1>CV. TIDAR TEKNIK</h1>
        <div class="kop-tagline">Supplier Teknik &amp; Industri</div>
        <div class="kop-contact">
          📍 Perum Bumi Mas Blok C1 No 2, Jedong Wagir, Kab. Malang 65158<br>
          ✉️ tidarteknik@gmail.com<br>
          📞 0822 2525 5251
        </div>
      </div>
      <div class="kop-doc-title" style="flex:1;text-align:right">
        <span class="doc-label">PENAWARAN HARGA</span>
      </div>
    </div>

    <!-- HEADER LETTER -->
    <div class="letter-header">
      <div style="margin-bottom: 10px">Malang, ${fmt.date(p.tanggal)}</div>
      <table style="width:auto">
        <tr>
          <td style="width:70px">Nomor</td>
          <td style="width:10px">:</td>
          <td><strong>${p.nomor}</strong></td>
        </tr>
        <tr>
          <td>Perihal</td>
          <td>:</td>
          <td><strong>Penawaran Harga</strong></td>
        </tr>
      </table>
      
      <div style="margin-top:20px">
        Kepada Yth,<br>
        <strong>${p.customer||''}</strong><br>
        ${p.kota ? `${p.kota}<br>` : ''}
        ${p.att ? `ATT: <strong>${p.att}</strong><br>` : ''}
      </div>
    </div>

    <!-- CONTENT -->
    <div class="letter-content">
      Dengan hormat,<br>
      Bersama dengan surat ini, kami mengajukan penawaran barang ke perusahaan yang Bapak / Ibu kelola. 
      Kami ingin mengirim daftar barang yang kami tawarkan meliputi,
    </div>

    ${p.sub_judul ? `<div class="sub-judul-box">${p.sub_judul}</div>` : ''}

    <!-- ITEMS TABLE -->
    <table class="items-table" style="margin-bottom:10px">
      <thead><tr>
        <th class="text-center" style="width:28px">No</th>
        <th>Description / Specification</th>
        <th class="text-center" style="width:60px">Qty</th>
        <th class="text-center" style="width:70px">Type</th>
        <th class="text-right" style="width:110px">Price (IDR)</th>
        <th class="text-right" style="width:120px">Amount (IDR)</th>
      </tr></thead>
      <tbody>
        ${itemRows}
        ${(() => {
          if (!showTotal) return '';
          const hasTaxOrDisc = (p.ppnMode && p.ppnMode !== 'none') || t.discVal || t.dpVal;
          if (hasTaxOrDisc) {
            return `
              <tr style="background:#f8fafc; font-weight:normal;">
                <td colspan="5" class="text-right" style="padding: 6px 12px; border-top:1px solid #e2e8f0; border-bottom:1px solid #f1f5f9; font-size:9pt;">Sub Total</td>
                <td class="text-right amount-cell" style="padding: 6px 8px; border-top:1px solid #e2e8f0; border-bottom:1px solid #f1f5f9; font-size:9pt;">${fmt.rupiah(t.total)}</td>
              </tr>
              ${t.discVal ? `
              <tr style="background:#f8fafc; font-weight:normal;">
                <td colspan="5" class="text-right" style="padding: 6px 12px; border-bottom:1px solid #f1f5f9; font-size:9pt;">Diskon</td>
                <td class="text-right amount-cell" style="padding: 6px 8px; border-bottom:1px solid #f1f5f9; font-size:9pt;">- ${fmt.rupiah(t.discVal)}</td>
              </tr>` : ''}
              ${t.dpVal ? `
              <tr style="background:#f8fafc; font-weight:normal;">
                <td colspan="5" class="text-right" style="padding: 6px 12px; border-bottom:1px solid #f1f5f9; font-size:9pt;">Down Payment</td>
                <td class="text-right amount-cell" style="padding: 6px 8px; border-bottom:1px solid #f1f5f9; font-size:9pt;">- ${fmt.rupiah(t.dpVal)}</td>
              </tr>` : ''}
              ${p.ppnMode === 'include' ? `
              <tr style="background:#f8fafc; font-weight:normal;">
                <td colspan="5" class="text-right" style="padding: 6px 12px; border-bottom:1px solid #f1f5f9; font-size:9pt;">Subtotal Sebelum PPN (DPP)</td>
                <td class="text-right amount-cell" style="padding: 6px 8px; border-bottom:1px solid #f1f5f9; font-size:9pt;">${fmt.rupiah(t.dpp)}</td>
              </tr>` : ''}
              ${p.ppnMode && p.ppnMode !== 'none' ? `
              <tr style="background:#f8fafc; font-weight:normal;">
                <td colspan="5" class="text-right" style="padding: 6px 12px; border-bottom:1px solid #f1f5f9; font-size:9pt;">PPN 11% ${p.ppnMode === 'include' ? '(Included)' : ''}</td>
                <td class="text-right amount-cell" style="padding: 6px 8px; border-bottom:1px solid #f1f5f9; font-size:9pt;">${fmt.rupiah(t.ppn)}</td>
              </tr>` : ''}
              <tr style="background:#f8fafc; font-weight:bold; border-top: 2px solid #0284c7;">
                <td colspan="5" class="text-right" style="padding: 8px 12px; font-size: 9.5pt;">GRAND TOTAL</td>
                <td class="text-right amount-cell" style="padding: 8px 8px; font-size: 9.5pt; font-weight: bold; color: #0284c7;">${fmt.rupiah(t.grandTotal)}</td>
              </tr>`;
          } else {
            return `
              <tr style="background:#f8fafc; font-weight:bold; border-top: 2px solid #0284c7;">
                <td colspan="5" class="text-right" style="padding: 8px 12px; font-size: 9.5pt;">TOTAL</td>
                <td class="text-right amount-cell" style="padding: 8px 8px; font-size: 9.5pt; font-weight: bold; color: #0284c7;">${fmt.rupiah(t.total)}</td>
              </tr>`;
          }
        })()}
      </tbody>
    </table>

    ${p.ppnMode === 'include' ? '<div style="font-size: 8.5pt; color: #475569; font-style: italic; margin-bottom: 15px;">*) Harga sudah termasuk PPN 11%</div>' : ''}

    ${p.note ? `<div class="notes-box">Notes:<br>${p.note}</div>` : ''}

    <div class="letter-content" style="margin-top:30px">
      Demikian Penawaran Harga kami sampaikan, sambil menunggu kabar selanjutnya dari Bapak / Ibu, atas perhatiannya kami ucapkan terima kasih.
    </div>

    <!-- SIGNATURE -->
    <div class="signature-box" style="justify-content: flex-start">
      <div class="signature-content" style="text-align: left">
        Hormat Kami,<br>
        <img src="img/logo.tandatangan.png" alt="Signature" style="height: 70px; object-fit: contain; margin: 5px 0">
        <div class="sign-name">&nbsp;</div>
      </div>
    </div>
  `;
});
</script>
</body>
</html>
