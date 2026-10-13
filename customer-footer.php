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
  </script>
</body>
</html>
