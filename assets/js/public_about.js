const heroSlides = document.querySelectorAll('.hero-slide');
  const dotsContainer = document.getElementById('hero-dots');
  let heroCur = 0;

  heroSlides.forEach((_, i) => {
    const d = document.createElement('button');
    d.className = 'hero-dot' + (i === 0 ? ' active' : '');
    d.onclick = () => goHero(i);
    dotsContainer.appendChild(d);
  });

  function goHero(n) {
    heroSlides[heroCur].classList.remove('active');
    dotsContainer.children[heroCur].classList.remove('active');
    heroCur = (n + heroSlides.length) % heroSlides.length;
    heroSlides[heroCur].classList.add('active');
    dotsContainer.children[heroCur].classList.add('active');
  }
  function heroSlide(dir) { goHero(heroCur + dir); }
  setInterval(() => heroSlide(1), 4500);

  // ── FARM VIEW SLIDESHOW ──
  const slides = ['../assets/images/about5.jpg','../assets/images/about6.jpg','../assets/images/about7.jpg','../assets/images/about8.jpg'];
  let cur = 0;
  const imgElement = document.getElementById('slide-img');
  const counterSpan = document.getElementById('counter');

  function updateSlide() {
    imgElement.style.opacity = '0.5';
    setTimeout(() => {
      imgElement.src = slides[cur];
      imgElement.style.opacity = '1';
    }, 120);
    counterSpan.textContent = (cur + 1) + ' of ' + slides.length;
  }
  function nextSlide() { cur = (cur + 1) % slides.length; updateSlide(); }
  function prevSlide() { cur = (cur - 1 + slides.length) % slides.length; updateSlide(); }
  setInterval(nextSlide, 5000);
  updateSlide();

  // Nav dropdown handled by public_nav.js