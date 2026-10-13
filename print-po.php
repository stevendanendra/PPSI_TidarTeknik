<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cetak PO — CV. Tidar Teknik</title>
  <style>
    .kop-logo img {
      width: 56px;
      height: 56px;
      object-fit: contain;
    }
    /* Force background colors in print — overrides Chrome's Background Graphics setting */
    @media print {
      * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
      }
      .items-table thead tr {
        background-color: #0284c7 !important;
      }
      .items-table thead th {
        background-color: #0284c7 !important;
        color: #ffffff !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
      }
      .items-table {
        width: 100%;
      }
      .items-table thead th:nth-child(1) { width: 4%; }
      .items-table thead th:nth-child(2) { width: auto; }
      .items-table thead th:nth-child(3) { width: 7%; }
      .items-table thead th:nth-child(4) { width: 8%; }
      .items-table thead th:nth-child(5) { width: 11%; }
      .items-table thead th:nth-child(6) { width: 14%; }
      .items-table thead th:nth-child(7) { width: 15%; }
    }
  </style>
  <link rel="stylesheet" href="css/print.css">
</head>

<body>

  <div class="print-toolbar no-print">
    <h2>🖨️ Preview Purchase Order</h2>
    <button class="btn-back" onclick="if(window.opener){window.close()}else{window.location.href='po.php'}">← Back</button>
    <button class="btn-print-action" onclick="window.print()">🖨️ Print / Save PDF</button>
  </div>

  <div class="print-page-wrapper">
    <div class="print-doc" id="po-print-doc">
      <!-- Dinamis dari JS -->
      <div style="text-align:center;padding:60px;color:#94a3b8">Loading document...</div>
    </div>
  </div>

  <script src="js/app.js?v=1.1"></script>
  <script>
    document.addEventListener('DOMContentLoaded', async () => {
      const params = new URLSearchParams(window.location.search);
      const id = params.get('id');
      if (!id) { document.getElementById('po-print-doc').innerHTML = '<div style="text-align:center;padding:60px;color:red">ID PO tidak ditemukan</div>'; return; }

      // Sync first
      await syncLocalToDB();

      const poList = await dbStore.get(KEYS.PO_LIST);
      const p = poList.find(x => x.id == id || x.nomor == id);
      if (!p) { document.getElementById('po-print-doc').innerHTML = '<div style="text-align:center;padding:60px;color:red">PO tidak ditemukan</div>'; return; }

      document.title = `Purchase Order ${p.nomor} — CV. Tidar Teknik`;

      const items = p.items || [];
      const t = calcPOTotals(items, p.disc || 0, p.dp || 0, p.ppnMode || 'none');

      const ppnText = p.ppnMode === 'include' ? 'Include dalam harga' : p.ppnMode === 'exclude' ? fmt.rupiah(t.ppn) : 'Tidak ada PPN';

      const itemRows = items.map((item, i) => {
        const numQty = parseInt(item.qty) || 0;
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
      <td class="td-no text-center" style="vertical-align: top; padding-top: 12px;">${i + 1}</td>
      <td class="td-desc" style="text-align: left; vertical-align: top; padding: 10px 12px;">
        <div style="display: flex; gap: 10px; align-items: flex-start; text-align: left;">
          <div style="flex: 1; text-align: left; color: #0f172a; font-size: 10pt; line-height: 1.3;">${item.desc || ''}</div>
          ${specHtml}
        </div>
      </td>
      <td class="td-qty text-center" style="vertical-align: top; padding-top: 12px;">${numQty}</td>
      <td class="text-center" style="vertical-align: top; padding-top: 12px;">${item.type || ''}</td>
      <td class="text-center" style="vertical-align: top; padding-top: 12px;">${item.code || ''}</td>
      <td class="td-price text-right" style="vertical-align: top; padding-top: 12px;">${item.price ? (parseFloat(item.price) || 0).toLocaleString('id-ID') : ''}</td>
      <td class="td-amount text-right amount-cell" style="vertical-align: top; padding-top: 12px;">${item.amount ? (parseFloat(item.amount) || 0).toLocaleString('id-ID') : ''}</td>
    </tr>`;
      }).join('');

      // Add empty rows to fill up table (min 5 rows)
      const emptyRows = 0;
      const blankRows = '';
      document.getElementById('po-print-doc').innerHTML = `
    <div class="watermark-container"><img src="img/watermark.png" alt="Watermark"></div>
    <!-- KOP -->
    <div class="kop">
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
      <div class="kop-doc-title">
        <span class="doc-label">PURCHASE<br>ORDER</span>
        <div class="doc-no">No: ${p.nomor}</div>
      </div>
    </div>

    <!-- META -->
    <div class="doc-meta">
      <div class="meta-block">
        <div class="meta-label">To:</div>
        <div class="meta-value big">${p.supplier || ''}</div>
        ${p.kota ? `<div class="meta-value" style="font-size:9pt;color:#475569">${p.kota}</div>` : ''}
      </div>
      <div class="meta-block">
        <div class="meta-label">Date</div>
        <div class="meta-value">${fmt.date(p.tanggal)}</div>
      </div>
      ${p.note1 ? `<div class="meta-block"><div class="meta-label">Payment</div><div class="meta-value">${p.note1}</div></div>` : '<div></div>'}
      ${p.note2 ? `<div class="meta-block"><div class="meta-label">Note</div><div class="meta-value">${p.note2}</div></div>` : '<div></div>'}
    </div>

    ${p.alamatKirim ? `<div class="doc-address-block"><div class="meta-label">Shipping Address</div><div class="meta-value">${p.alamatKirim}</div></div>` : ''}

    <!-- ITEMS TABLE -->
    <table class="items-table">
      <thead style="background-color:#0284c7; -webkit-print-color-adjust:exact; print-color-adjust:exact;">
        <tr style="background-color:#0284c7;">
          <th class="text-center" style="width:28px; background-color:#0284c7; color:#fff;">No</th>
          <th style="background-color:#0284c7; color:#fff;">Description / Specification</th>
          <th class="text-center" style="width:60px; background-color:#0284c7; color:#fff;">Qty</th>
          <th class="text-center" style="width:70px; background-color:#0284c7; color:#fff;">Type</th>
          <th class="text-center" style="width:100px; background-color:#0284c7; color:#fff;">Code</th>
          <th class="text-right" style="width:110px; background-color:#0284c7; color:#fff;">Price (IDR)</th>
          <th class="text-right" style="width:120px; background-color:#0284c7; color:#fff;">Amount (IDR)</th>
        </tr>
      </thead>
      <tbody>${itemRows}${blankRows}</tbody>
    </table>

    <!-- SUMMARY -->
    <div class="summary-section">
      <table class="summary-table">
        <tr><td>Sub Total</td><td>${fmt.rupiah(t.total)}</td></tr>
        ${t.discVal ? `<tr><td>Discount</td><td>- ${fmt.rupiah(t.discVal)}</td></tr>` : ''}
        ${t.dpVal ? `<tr><td>Down Payment</td><td>- ${fmt.rupiah(t.dpVal)}</td></tr>` : ''}
        <tr><td>VAT 11%</td><td>${ppnText}</td></tr>
        <tr class="total-row"><td>TOTAL</td><td>${fmt.rupiah(t.grandTotal)}</td></tr>
      </table>
    </div>

    <div class="terbilang-row">Saying: <em>${terbilang(t.grandTotal)}</em></div>

    ${p.ppnMode === 'include' ? '<div class="ppn-note">*) Price includes VAT 11%</div>' : ''}

    <!-- FOOTER -->
    <div class="doc-footer" style="justify-content: flex-start">
      <div class="sign-block" style="align-items: flex-start; text-align: left">
        <div class="sign-title">Sincerely,</div>
        <img src="img/logo.tandatangan.png" alt="Signature" class="sign-img">
        <div class="sign-name">&nbsp;</div>
      </div>
    </div>
  `;
    });
  </script>
</body>

</html>