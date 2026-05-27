
// Nav dropdown handled by public_nav.js

  
  const scrollBtn = document.querySelector('.scroll-top');
  if (scrollBtn) {
    window.addEventListener('scroll', () => {
      if (window.scrollY > 400) scrollBtn.style.opacity = '1';
      else scrollBtn.style.opacity = '0.7';
    });
  }

