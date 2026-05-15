 window.addEventListener('scroll', () => {
    document.getElementById('main-nav').classList.toggle('scrolled', window.scrollY > 40);
  });

  
  const slides = document.querySelectorAll('.slide');
  const dotsEl = document.getElementById('hero-dots');
  let cur = 0;

  slides.forEach((_, i) => {
    const d = document.createElement('div');
    d.className = 'hero-dot' + (i === 0 ? ' active' : '');
    d.onclick = () => goTo(i);
    dotsEl.appendChild(d);
  });

  function goTo(n) {
    slides[cur].classList.remove('active');
    dotsEl.children[cur].classList.remove('active');
    cur = (n + slides.length) % slides.length;
    slides[cur].classList.add('active');
    dotsEl.children[cur].classList.add('active');
  }

  setInterval(() => goTo(cur + 1), 5000);

  const navItems = document.querySelectorAll('.nav-item');
  navItems.forEach(item => {
    item.querySelector('a').addEventListener('click', e => {
      e.preventDefault(); e.stopPropagation();
      const open = item.classList.contains('open');
      navItems.forEach(i => i.classList.remove('open'));
      if (!open) item.classList.add('open');
    });
  });
  document.addEventListener('click', () => navItems.forEach(i => i.classList.remove('open')));

  // ── FAQ ──
  function faqToggle(btn) {
    const item = btn.closest('.faq-item');
    const wasOpen = item.classList.contains('open');
    document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('open'));
    if (!wasOpen) item.classList.add('open');
  }