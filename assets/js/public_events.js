
  const navItems = document.querySelectorAll('.nav-item');
  navItems.forEach(item => {
    const link = item.querySelector('a');
    if (link) {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const isOpen = item.classList.contains('open');
        navItems.forEach(i => i.classList.remove('open'));
        if (!isOpen) item.classList.add('open');
      });
    }
  });
  document.addEventListener('click', () => navItems.forEach(i => i.classList.remove('open')));

  
  const scrollBtn = document.querySelector('.scroll-top');
  if (scrollBtn) {
    window.addEventListener('scroll', () => {
      if (window.scrollY > 400) scrollBtn.style.opacity = '1';
      else scrollBtn.style.opacity = '0.7';
    });
  }

