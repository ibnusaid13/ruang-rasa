(function () {
  'use strict';
  document.documentElement.classList.add('js');

  // ---------- Toast ----------
  function toastWrap() {
    var w = document.querySelector('.toast-wrap');
    if (!w) { w = document.createElement('div'); w.className = 'toast-wrap'; w.setAttribute('aria-live', 'polite'); document.body.appendChild(w); }
    return w;
  }
  window.rrToast = function (message, type, link) {
    var el = document.createElement('div');
    el.className = 'toast' + (type === 'error' ? ' error' : '');
    var icon = document.createElement('span'); icon.className = 't-icon'; icon.textContent = type === 'error' ? '⚠️' : '🧁';
    var text = document.createElement('span'); text.textContent = message; // textContent: aman dari XSS
    el.appendChild(icon); el.appendChild(text);
    if (link) { var a = document.createElement('a'); a.href = link.href; a.textContent = link.label; el.appendChild(a); }
    toastWrap().appendChild(el);
    setTimeout(function () { el.classList.add('out'); setTimeout(function () { el.remove(); }, 320); }, 3800);
  };

  // Flash message dari server
  document.querySelectorAll('[data-toast]').forEach(function (n) {
    window.rrToast(n.getAttribute('data-message'), n.getAttribute('data-toast'));
  });

  // ---------- Badge keranjang ----------
  function setCount(n) {
    document.querySelectorAll('.cart-badge').forEach(function (b) {
      b.textContent = n; b.setAttribute('data-count', n);
      b.classList.add('bump'); setTimeout(function () { b.classList.remove('bump'); }, 300);
    });
  }

  // ---------- Tambah ke keranjang (AJAX, fallback form biasa) ----------
  var token = (document.querySelector('meta[name="csrf-token"]') || {}).content;
  document.querySelectorAll('form.js-add-cart').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!window.fetch) return; // fallback: submit biasa
      e.preventDefault();
      var btn = form.querySelector('button[type=submit]');
      if (btn) btn.classList.add('is-loading');
      fetch(form.action, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
        body: new FormData(form),
        credentials: 'same-origin'
      }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
        .then(function (res) {
          if (res.ok) {
            setCount(res.body.count);
            window.rrToast(res.body.message, 'ok', { href: form.dataset.cartUrl || '/cart', label: 'Lihat keranjang' });
          } else {
            var msg = res.body.message || 'Gagal menambahkan ke keranjang.';
            window.rrToast(msg, 'error');
          }
        })
        .catch(function () { window.rrToast('Koneksi bermasalah. Coba lagi.', 'error'); })
        .finally(function () { if (btn) btn.classList.remove('is-loading'); });
    });
  });

  // ---------- Loading state pada form biasa ----------
  document.querySelectorAll('form[data-loading]').forEach(function (form) {
    form.addEventListener('submit', function () {
      var btn = form.querySelector('button[type=submit]');
      if (btn && !btn.classList.contains('is-loading')) {
        // ditunda agar data tombol tetap terkirim & tidak memblokir submit
        setTimeout(function () { btn.classList.add('is-loading'); btn.setAttribute('aria-busy', 'true'); }, 0);
      }
    });
  });

  // ---------- Konfirmasi hapus / aksi penting ----------
  document.addEventListener('submit', function (e) {
    var msg = e.target.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) { e.preventDefault(); e.stopImmediatePropagation(); }
  }, true);

  // ---------- Reveal on scroll ----------
  var items = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } });
    }, { threshold: .12 });
    items.forEach(function (el) { io.observe(el); });
  } else { items.forEach(function (el) { el.classList.add('in'); }); }

  // ---------- Hero slider: ganti foto Best Seller otomatis ----------
  document.querySelectorAll('[data-slider]').forEach(function (root) {
    var slides = root.querySelectorAll('.hero-slide');
    var dots = root.querySelectorAll('.hero-dot');
    if (slides.length < 2) return;
    var interval = parseInt(root.getAttribute('data-interval'), 10) || 4500;
    var current = 0, timer = null;
    var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function show(n) {
      current = (n + slides.length) % slides.length;
      slides.forEach(function (s, i) { s.classList.toggle('is-active', i === current); });
      dots.forEach(function (d, i) { d.classList.toggle('is-active', i === current); });
    }
    function start() { if (!reduced && !timer) timer = setInterval(function () { show(current + 1); }, interval); }
    function stop() { clearInterval(timer); timer = null; }

    dots.forEach(function (d, i) { d.addEventListener('click', function (e) { e.preventDefault(); show(i); stop(); start(); }); });
    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', start);
    root.addEventListener('focusin', stop);
    root.addEventListener('focusout', start);
    document.addEventListener('visibilitychange', function () { document.hidden ? stop() : start(); });
    start();
  });

  // ---------- Navbar & sidebar mobile ----------
  var navToggle = document.querySelector('.nav-toggle');
  if (navToggle) navToggle.addEventListener('click', function () { document.querySelector('.nav-links').classList.toggle('open'); });
  var sideToggle = document.querySelector('.side-toggle');
  if (sideToggle) sideToggle.addEventListener('click', function () { document.querySelector('.sidebar').classList.toggle('open'); });

  // ---------- Checkout: tampilkan alamat & ongkir sesuai metode ----------
  var fulfil = document.querySelectorAll('input[name=fulfillment_method]');
  var addr = document.getElementById('address-field');
  var shipRow = document.getElementById('ship-row');
  var totalEl = document.getElementById('grand-total');
  function updateFulfil() {
    var sel = document.querySelector('input[name=fulfillment_method]:checked');
    if (!sel || !addr) return;
    var delivery = sel.value === 'delivery';
    addr.style.display = delivery ? '' : 'none';
    if (shipRow) shipRow.style.display = delivery ? '' : 'none';
    if (totalEl) { // hanya tampilan; total final dihitung ulang di server
      var base = parseInt(totalEl.dataset.subtotal, 10), fee = parseInt(totalEl.dataset.fee, 10);
      totalEl.textContent = 'Rp ' + (base + (delivery ? fee : 0)).toLocaleString('id-ID');
    }
  }
  fulfil.forEach(function (r) { r.addEventListener('change', updateFulfil); });
  updateFulfil();
})();
