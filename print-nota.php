<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cetak Nota — CV. Tidar Teknik</title>
  <style>
    .kop-logo img {
      width: 56px;
      height: 56px;
      object-fit: contain;
    }
  </style>
  <link rel="stylesheet" href="css/print.css">
</head>

<body>

  <div class="print-toolbar no-print">
    <h2 id="toolbar-title">🖨️ Preview Invoice</h2>
    <button class="btn-back" onclick="if(window.opener){window.close()}else{window.location.href='nota.php'}">← Back</button>
    <button class="btn-print-action" onclick="window.print()">🖨️ Print / Save PDF</button>
  </div>

  <div class="print-page-wrapper">
    <div id="nota-print-wrap">
      <div style="text-align:center;padding:60px;color:#94a3b8">Loading document...</div>
    </div>
  </div>

  <script src="js/app.js?v=1.1"></script>
  <script>
    document.addEventListener('DOMContentLoaded', async () => {
      const params = new URLSearchParams(window.location.search);
      const id = params.get('id');
      const mode = params.get('mode') || 'invoice'; // 'invoice' | 'sj' | 'both'
      if (!id) { document.getElementById('nota-print-wrap').innerHTML = '<div style="text-align:center;padding:60px;color:red">ID Nota tidak ditemukan</div>'; return; }

      // Sync first
      await syncLocalToDB();

      const notaList = await dbStore.get(KEYS.NOTA_LIST);
      const n = notaList.find(x => x.id == id || x.nomor == id);
      if (!n) { document.getElementById('nota-print-wrap').innerHTML = '<div style="text-align:center;padding:60px;color:red">Nota tidak ditemukan</div>'; return; }

      document.title = `Invoice ${n.nomor} — CV. Tidar Teknik`;
      if (mode === 'sj') {
        document.title = `Surat Jalan ${n.nomor} — CV. Tidar Teknik`;
        document.getElementById('toolbar-title').textContent = '📦 Preview Surat Jalan';
      }

      const items = n.items || [];
      const t = calcNotaTotals(items, n.disc || 0, n.dp || 0, n.ppnMode || 'none');

      function buildItemRows(items) {
        const rows = items.map((item, i) => {
          let specHtml = '';
          const parts = [];
          if (item.hasSpecs && item.specs) {
            const s = item.specs;
            if (s.capacity) parts.push(['Capacity', s.capacity]);
            if (s.pressure) parts.push(['Pressure', s.pressure]);
            if (s.inoutlet) parts.push(['In/Outlet', s.inoutlet]);
            if (s.power)    parts.push(['Power', s.power]);
            if (s.material) parts.push(['Material', s.material]);
          }

          return `<tr>
            <td class="td-no text-center" style="vertical-align: top; padding-top: 12px;">${i + 1}</td>
            <td class="td-desc" style="text-align: left; vertical-align: top; padding: 10px 12px;">
              <div style="display: flex; gap: 10px; align-items: flex-start; text-align: left;">
                <div style="flex: 1; text-align: left; color: #0f172a; font-size: 10pt; line-height: 1.3;">${item.desc || ''}</div>
                ${item.hasSpecs && parts.length > 0 ? `
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
                  </div>
                ` : ''}
              </div>
            </td>
            <td class="td-qty text-center" style="vertical-align: top; padding-top: 12px;">${Math.floor(item.qty) || 0}</td>
            <td class="text-center" style="vertical-align: top; padding-top: 12px;">${item.satuan || ''}</td>
            <td class="td-price text-right" style="vertical-align: top; padding-top: 12px;">${item.price ? (parseFloat(item.price) || 0).toLocaleString('id-ID') : ''}</td>
            <td class="td-amount text-right amount-cell" style="vertical-align: top; padding-top: 12px;">${item.amount ? (parseFloat(item.amount) || 0).toLocaleString('id-ID') : ''}</td>
          </tr>`;
        }).join('');

        const emptyRows = 0;
        const blanks = Array(emptyRows).fill('').map((_, i) => `<tr style="height:28px">
      <td class="text-center">${items.length + i + 1}</td>
      <td></td><td></td><td></td><td></td><td></td><td></td>
    </tr>`).join('');
        return rows + blanks;
      }

      function buildInvoiceDoc(isCopy = false) {
        const isLunas = n.status === 'Lunas';
        const termin = isLunas ? 0 : (parseInt(n.termin) || 30);
        const dateObj = new Date(n.tanggal);
        const dueDate = new Date(dateObj);
        if (!isLunas) dueDate.setDate(dateObj.getDate() + termin);
        let dueStr = '';
        try { dueStr = dueDate.toISOString().split('T')[0]; } catch (e) { dueStr = n.tanggal; }

        return `
    <div class="print-doc ${isCopy ? 'sj-separator' : ''}">
      <div class="watermark-container"><img src="img/watermark.png" alt="Watermark"></div>
      ${isCopy ? '<div class="copy-stamp" style="top: 38%">COPY</div>' : ''}
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
          <span class="doc-label">INVOICE</span>
          <div class="doc-no">No: ${n.nomor}</div>
        </div>
      </div>

      <!-- META -->
      <div class="doc-meta">
        <div class="meta-block">
          <div class="meta-label">To:</div>
          <div class="meta-value big">${n.customer || ''}</div>
        </div>
        <div class="meta-block">
          <div class="meta-label">Date</div>
          <div class="meta-value">${fmt.date(n.tanggal)}</div>
        </div>
        <div class="meta-block">
          <div class="meta-label">Customer PO No.</div>
          <div class="meta-value">${n.refPO || '0'}</div>
        </div>
        ${!isLunas ? `
        <div class="meta-block">
          <div class="meta-label">Term of Payment</div>
          <div class="meta-value">${termin} Hari</div>
        </div>
        <div class="meta-block">
          <div class="meta-label">Due Date</div>
          <div class="meta-value">${fmt.date(dueStr)}</div>
        </div>` : `<div></div>`}
        ${n.tujuanKirim ? `<div class="meta-block"><div class="meta-label">Destination</div><div class="meta-value">${n.tujuanKirim}</div></div>` : '<div></div>'}
      </div>

      ${n.alamatInvoice ? `<div class="doc-address-block"><div class="meta-label">Address</div><div class="meta-value">${n.alamatInvoice}</div></div>` : ''}
      ${n.ekspedisi ? `<div class="doc-address-block" style="border-color:var(--am-500)"><div class="meta-label">Expedition</div><div class="meta-value">${n.ekspedisi}</div></div>` : ''}

      <!-- ITEMS -->
      <table class="items-table">
        <thead><tr>
          <th class="text-center" style="width:28px">No</th>
          <th>Description / Specification</th>
          <th class="text-center" style="width:50px">Qty</th>
          <th class="text-center" style="width:55px">Type</th>
          <th class="text-right" style="width:110px">Price (IDR)</th>
          <th class="text-right" style="width:120px">Amount (IDR)</th>
        </tr></thead>
        <tbody>${buildItemRows(items)}</tbody>
      </table>

      <!-- SUMMARY -->
      <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:20px;margin-bottom:16px">
        <div class="payment-info-box" style="flex:1">
          <div class="pi-title">💳 Informasi Pembayaran</div>
          <div class="pi-value">BCA: A/C 816.202.2252</div>
          <div class="pi-name">a/n TIDAR TEKNIK CV</div>
        </div>
        <table class="summary-table">
          <tr><td>Total</td><td>${fmt.rupiah(t.total)}</td></tr>
          ${t.discVal ? `<tr><td>Discount</td><td>- ${fmt.rupiah(t.discVal)}</td></tr>` : ''}
          ${t.dpVal ? `<tr><td>Down Payment</td><td>- ${fmt.rupiah(t.dpVal)}</td></tr>` : ''}
          ${n.ppnMode === 'include' ? `<tr><td>Subtotal before Tax (DPP)</td><td>${fmt.rupiah(t.dpp)}</td></tr>` : ''}
          ${n.ppnMode !== 'none' ? `<tr><td>VAT 11% ${n.ppnMode === 'include' ? '(Included)' : ''}</td><td>${fmt.rupiah(t.ppn)}</td></tr>` : ''}
          <tr class="total-row"><td>GRAND TOTAL</td><td>${fmt.rupiah(t.grandTotal)}</td></tr>
        </table>
      </div>

      <div class="terbilang-row">Saying: <em>${terbilang(t.grandTotal)}</em></div>
      ${n.ppnMode === 'include' ? '<div class="ppn-note">*) Harga sudah termasuk PPN 11%</div>' : ''}

      <!-- FOOTER INVOICE -->
      <div class="doc-footer" style="justify-content: flex-start">
        <div class="sign-block" style="align-items: flex-start; text-align: left">
          <div class="sign-title">Sincerely,</div>
          ${isCopy ? '<img src="img/logo.tandatangan.png" alt="Signature" class="sign-img">' : '<div class="sign-img" style="height:70px"></div>'}
          <div class="sign-name">&nbsp;</div>



        </div>
      </div>
    </div>`;
      }

      function buildSJItemRows(items) {
        const rows = items.map((item, i) => {
          const parts = [];
          if (item.hasSpecs && item.specs) {
            const s = item.specs;
            if (s.capacity) parts.push(['Capacity', s.capacity]);
            if (s.pressure) parts.push(['Pressure', s.pressure]);
            if (s.inoutlet) parts.push(['In/Outlet', s.inoutlet]);
            if (s.power)    parts.push(['Power', s.power]);
            if (s.material) parts.push(['Material', s.material]);
          }

          return `<tr>
            <td class="td-no text-center" style="vertical-align: top; padding-top: 12px;">${i + 1}</td>
            <td class="td-desc" style="text-align: left; vertical-align: top; padding: 10px 12px;">
              <div style="display: flex; gap: 10px; align-items: flex-start; text-align: left;">
                <div style="flex: 1; text-align: left; color: #0f172a; font-size: 10pt; line-height: 1.3;">${item.desc || ''}</div>
                ${item.hasSpecs && parts.length > 0 ? `
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
                  </div>
                ` : ''}
              </div>
            </td>
            <td class="td-qty text-center" style="vertical-align: top; padding-top: 12px;">${Math.floor(item.qty) || 0}</td>
            <td class="text-center" style="vertical-align: top; padding-top: 12px;">${item.satuan || ''}</td>
          </tr>`;
        }).join('');

        const emptyRows = 0;
        const blanks = '';
        return rows + blanks;
      }

      function buildSJDoc(isCopy = false) {
        return `
    <div class="print-doc sj-separator">
      <div class="watermark-container" style="top: 32%"><img src="img/watermark.png" alt="Watermark"></div>
      ${isCopy ? '<div class="copy-stamp" style="top: 38%">COPY</div>' : ''}
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
          <span class="doc-label">SURAT JALAN</span>
          <div class="doc-no">No: ${n.nomor}</div>
        </div>
      </div>

      <!-- META -->
      <div class="doc-meta">
        <div class="meta-block">
          <div class="meta-label">To:</div>
          <div class="meta-value big">${n.customer || ''}</div>
        </div>
        <div class="meta-block">
          <div class="meta-label">Date</div>
          <div class="meta-value">${fmt.date(n.tanggal)}</div>
        </div>
        <div class="meta-block">
          <div class="meta-label">Customer PO No.</div>
          <div class="meta-value">${n.refPO || '0'}</div>
        </div>
        ${n.tujuanKirim ? `<div class="meta-block"><div class="meta-label">Destination</div><div class="meta-value">${n.tujuanKirim}</div></div>` : '<div></div>'}
      </div>

      ${n.alamatSJ ? `<div class="doc-address-block"><div class="meta-label">Shipping Address</div><div class="meta-value">${n.alamatSJ}</div></div>` : ''}
      ${n.ekspedisi ? `<div class="doc-address-block" style="border-color:#f59e0b"><div class="meta-label">Expedition</div><div class="meta-value">${n.ekspedisi}</div></div>` : ''}

      <!-- ITEMS -->
      <table class="items-table">
        <thead><tr>
          <th class="text-center" style="width:28px">No</th>
          <th>Description / Specification</th>
          <th class="text-center" style="width:60px">Qty</th>
          <th class="text-center" style="width:70px">Type</th>
        </tr></thead>
        <tbody>${buildSJItemRows(items)}</tbody>
      </table>

      <!-- SUMMARY SURAT JALAN -->
      <div style="margin-bottom:24px">
        <div style="font-size:8.5pt;color:#64748b">
          The above items have been delivered in good condition and complete.
        </div>
      </div>

      ${n.catatan ? `<div class="doc-notes"><div class="doc-notes-title">Notes:</div><div style="white-space:pre-wrap">${n.catatan}</div></div>` : ''}

      <!-- FOOTER SJ -->
      <div class="doc-footer" style="justify-content: space-between; border-top: none; margin-top: 10px">
        <div class="sign-block">
          <div class="sign-title">Sender,</div>
          <img src="img/logo.tandatangan.png" alt="Pengirim" class="sign-img">
          <div class="sign-name">&nbsp;</div>
        </div>
        <div class="sign-block">
          <div class="sign-title">Receiver,</div>
          <div class="sign-name">&nbsp;</div>
        </div>
      </div>
    </div>`;
      }

      const wrap = document.getElementById('nota-print-wrap');
      if (mode === 'invoice') {
        wrap.innerHTML = buildInvoiceDoc(false) + buildInvoiceDoc(true);
      } else if (mode === 'sj') {
        wrap.innerHTML = buildSJDoc(false) + buildSJDoc(true);
      } else {
        // both
        wrap.innerHTML = buildInvoiceDoc(false) + buildInvoiceDoc(true) + buildSJDoc(false) + buildSJDoc(true);
      }
    });
  </script>
</body>

</html>