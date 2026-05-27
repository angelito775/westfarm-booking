(function () {
  'use strict';

  // ── Build page transition overlay ──
  var overlay = document.createElement('div');
  overlay.id = 'page-transition';
  for (var i = 0; i < 5; i++) {
    var slice = document.createElement('div');
    slice.className = 'pt-slice';
    overlay.appendChild(slice);
  }
  document.body.appendChild(overlay);

  // ── Subtle content fade-in on page load ──
  document.body.classList.add('page-enter');

  // ── Intercept internal nav clicks ──
  function isInternalNav(href) {
    if (!href || href === '#' || href.indexOf('#') === 0) return false;
    if (href.indexOf('tel:') === 0 || href.indexOf('mailto:') === 0 || href.indexOf('javascript:') === 0) return false;
    if (href.indexOf('http') === 0 && href.indexOf(window.location.origin) !== 0) return false;
    return true;
  }

  document.addEventListener('click', function (e) {
    var link = e.target.closest('a');
    if (!link) return;

    var href = link.getAttribute('href');
    if (!isInternalNav(href)) return;
    if (e.ctrlKey || e.metaKey || e.button !== 0) return;
    if (link.target === '_blank') return;

    e.preventDefault();

    // Animate bars sliding in from right to cover page
    overlay.classList.add('active');

    setTimeout(function () {
      window.location.href = href;
    }, 500);
  });

  // ── Scroll-based nav styling ──
  window.addEventListener('scroll', function () {
    var nav = document.getElementById('main-nav');
    if (nav) nav.classList.toggle('scrolled', window.scrollY > 40);
  });

  // ── Dropdown toggle (click-based) ──
  var navItems = document.querySelectorAll('.nav-item');
  navItems.forEach(function (item) {
    var btn = item.querySelector('a');
    if (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var isOpen = item.classList.contains('open');
        navItems.forEach(function (ni) { ni.classList.remove('open'); });
        if (!isOpen) item.classList.add('open');
      });
    }
  });
  document.addEventListener('click', function () {
    navItems.forEach(function (ni) { ni.classList.remove('open'); });
  });
})();
