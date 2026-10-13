    </main>
  </div>

  <!-- Shared Customer Portal Scripts -->
  <script src="js/app.js?v=<?= time() ?>"></script>
  <script>
    // Toggle wishlist AJAX
    async function toggleWishlist(btn, productId) {
      try {
        const formData = new FormData();
        formData.append('action', 'toggle_wishlist');
        formData.append('product_id', productId);

        const res = await fetch('customer-api.php', {
          method: 'POST',
          body: formData
        });
        const data = await res.json();
        if (data.status === 'success') {
          if (data.is_wishlist) {
            btn.classList.add('active');
            btn.innerHTML = '❤️';
            showToast('Ditambahkan ke Wishlist', 'success');
          } else {
            btn.classList.remove('active');
            btn.innerHTML = '🤍';
            showToast('Dihapus dari Wishlist', 'info');
          }
        }
      } catch (err) {
        console.error('Wishlist error:', err);
      }
    }

    // Keranjang
    async function addToCart(productId, qty = 1) {
      try {
        const fd = new FormData();
        fd.append('action', 'add_to_cart');
        fd.append('product_id', productId);
        fd.append('qty', qty);
        const res = await fetch('customer-api.php', { method: 'POST', body: fd });
        const d = await res.json();
        if (d.status === 'success') showToast('Ditambahkan ke keranjang (' + d.count + ' jenis barang)', 'success');
        else showToast(d.message || 'Gagal menambah ke keranjang', 'error');
      } catch (e) { showToast('Gagal menambah ke keranjang', 'error'); }
    }

    // Notifikasi internal (polling tiap 15 detik)
    (function () {
      const bell = document.getElementById('notif-bell');
      if (!bell) return;
      const countEl = document.getElementById('notif-count');
      const panel = document.getElementById('notif-panel');
      const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
      async function load() {
        try {
          const res = await fetch('customer-api.php?action=notifications', { cache: 'no-store' });
          const d = await res.json();
          if (d.status !== 'success') return;
          countEl.style.display = d.unread > 0 ? 'inline-block' : 'none';
          countEl.textContent = d.unread;
          panel.innerHTML = d.items.length
            ? d.items.map(n => `<a href="${esc(n.link || '#')}" style="display:block;padding:10px 14px;border-bottom:1px solid var(--navy-200);font-size:12.5px;text-decoration:none;color:var(--navy-800);${n.is_read == 0 ? 'background:rgba(14,165,233,.08);font-weight:600' : ''}">${esc(n.pesan)}<br><small style="color:var(--navy-400)">${esc(n.created_at)}</small></a>`).join('')
            : '<div style="padding:14px;font-size:12.5px;color:var(--navy-400)">Belum ada notifikasi.</div>';
        } catch (e) {}
      }
      bell.addEventListener('click', async () => {
        const open = panel.style.display === 'block';
        panel.style.display = open ? 'none' : 'block';
        if (!open) {
          const fd = new FormData(); fd.append('action', 'notifications_read');
          await fetch('customer-api.php', { method: 'POST', body: fd });
          setTimeout(load, 300);
        }
      });
      load(); setInterval(load, 15000);
    })();
  </script>
<?php if (!empty($currentCust) && empty($currentCust['tutorial_seen']) && empty($currentCust['must_change_password'])): ?>
  <div id="tutorial-overlay" style="position:fixed;inset:0;background:rgba(15,23,42,.6);z-index:100;display:flex;align-items:center;justify-content:center;padding:16px">
    <div class="card" style="max-width:480px;width:100%;padding:24px">
      <div class="card-title" style="font-size:18px;margin-bottom:12px">👋 Selamat datang di Portal Customer</div>
      <ol style="padding-left:18px;line-height:1.8;font-size:13.5px;color:var(--navy-700)">
        <li><b>Marketplace</b> — pilih produk, masukkan ke keranjang.</li>
        <li><b>Request Barang</b> — barang tidak ada di katalog? Ajukan permintaan.</li>
        <li><b>Penawaran</b> — setujui, tolak, atau minta revisi penawaran dari kami.</li>
        <li><b>Pesanan</b> — bayar via transfer BCA, unggah bukti, pantau pengiriman, lalu konfirmasi barang diterima.</li>
        <li><b>Retur</b> — barang bermasalah? Ajukan retur dengan foto/video.</li>
      </ol>
      <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px">
        <a href="customer-help.php" class="btn btn-ghost" onclick="tutorialDone()">Buka Help Center</a>
        <button class="btn btn-primary" onclick="tutorialDone()">Mengerti</button>
      </div>
    </div>
  </div>
  <script>
    async function tutorialDone() {
      const fd = new FormData(); fd.append('action', 'tutorial_done');
      await fetch('customer-api.php', { method: 'POST', body: fd, keepalive: true });
      document.getElementById('tutorial-overlay')?.remove();
    }
  </script>
<?php endif; ?>
</body>
</html>
