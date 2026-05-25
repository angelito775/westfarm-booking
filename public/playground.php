<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Playground – Play. Learn. Grow.</title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Lato:wght@300;400;700&family=Dancing+Script:wght@700&family=Josefin+Sans:wght@300;400;600;700&family=Lora:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="../assets/css/public_playground.css" />
</head>
<body>

<!-- ── NAVBAR ── -->
<nav id="main-nav">
  <a class="nav-logo" href="../public/index.php">
    <img src="../assets/images/westfarmlogo.png" alt="West Farm logo">
    <div class="nav-logo-text">
      <span class="name">WEST FARM</span>
      <span class="sub">Resort and Hotel</span>
    </div>
  </a>
  <ul class="nav-links">
    <li><a href="../public/index.php">HOME</a></li>
    <li><a href="../public/about.php">ABOUT</a></li>
    <li class="nav-item">
      <a href="#" class="nav-btn active">AMENITIES</a>
      <div class="dropdown-menu">
        <a href="../public/westpool.php">WEST POOL</a>
        <a href="../public/westcrays.php">WEST CRAY ORDERING</a>
        <a href="../public/playground.php" class="active">PLAYGROUND</a>
      </div>
    </li>
    <li class="nav-item">
      <a href="#" class="nav-btn">ACCOMMODATIONS</a>
      <div class="dropdown-menu">
        <a href="../public/glamping.php">GLAMPING</a>
        <a href="../public/luxury-villas.php">LUXURY VILLAS</a>
        <a href="../public/cottages.php">COTTAGES</a>
        <a href="../public/pavillion.php">PAVILLION</a>
      </div>
    </li>
    <li><a href="../public/events.php">EVENTS</a></li>
    <li><a href="../public/faqs.php">FAQs</a></li>
    <li><a href="../public/contact.php">CONTACT</a></li>
    <li><a href="../public/booking.php" class="nav-book-btn">BOOK NOW</a></li>
  </ul>
</nav>

<!-- ── HERO ── -->
<section class="hero">
  <img class="hero-bg"
    src="../assets/images/playground_bg.jpg"
    alt="Playground surrounded by nature"
    onerror="this.src='https://images.unsplash.com/photo-1575783970733-1aaedde1db74?w=1400&q=85'"
  />

  <svg class="hero-blob-mask" viewBox="0 0 1440 640" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M0 0 L580 0 C640 0, 700 30, 720 120 C740 210, 700 270, 695 340 C690 410, 730 490, 710 580 C695 640, 630 640, 560 640 L0 640 Z" fill="white" opacity="0.95"/>
  </svg>

  <svg class="hero-deco-leaf" viewBox="0 0 48 72" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M24 3 C6 12, 3 38, 12 55 C18 66, 30 68, 36 58 C44 44, 45 18, 32 6 C30 4, 27 2, 24 3Z" fill="#3d6b2c" opacity="0.7"/>
    <path d="M24 3 C24 3, 22 36, 16 58" stroke="#2d4a1e" stroke-width="1.2" fill="none" stroke-linecap="round"/>
  </svg>

  <div class="hero-left">
    <p class="hero-eyebrow">A Space to</p>
    <h1 class="hero-title">
      Play, Rest,<br>and Recharge.<span class="hero-title-leaf">🌿</span>
    </h1>
    <p class="hero-desc">Our playground is more than a place to play. It's a peaceful escape where families can relax, children can have fun, and nature brings us closer together.</p>
    <a href="#" class="btn-primary">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C9.2 2 6 5.2 6 9c0 4.5 6 13 6 13s6-8.5 6-13c0-3.8-3.2-7-6-7zm0 9.5c-1.4 0-2.5-1.1-2.5-2.5S10.6 6.5 12 6.5 14.5 7.6 14.5 9 13.4 11.5 12 11.5z"/></svg>
      A Place for Everyone
    </a>
  </div>
</section>

<!-- ── FEATURES BAR ── -->
<div class="features-bar">
  <div class="feature-item">
    <div class="feature-icon">
      <svg viewBox="0 0 24 24"><path d="M17 8C8 10 5.9 16.17 3.82 21H5.71C6.66 19 8 17.72 10 17c1.6-.6 3.3-.6 5 .07V21h2V3c-2.36.85-4.19 2.58-5 5z"/></svg>
    </div>
    <div class="feature-text">
      <h4>Nature Inspired</h4>
      <p>Designed with greenery to promote relaxation and well-being.</p>
    </div>
  </div>
  <div class="feature-item">
    <div class="feature-icon">
      <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.5 3.8 10.7 9 12 5.2-1.3 9-6.5 9-12V5L12 1z"/></svg>
    </div>
    <div class="feature-text">
      <h4>Safe & Secure</h4>
      <p>Well-maintained equipment with safety as our top priority.</p>
    </div>
  </div>
  <div class="feature-item">
    <div class="feature-icon">
      <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
    </div>
    <div class="feature-text">
      <h4>Family Friendly</h4>
      <p>A perfect spot for quality time with loved ones.</p>
    </div>
  </div>
  <div class="feature-item">
    <div class="feature-icon">
      <svg viewBox="0 0 24 24"><path d="M6.76 4.84l-1.8-1.79-1.41 1.41 1.79 1.79 1.42-1.41zM4 10.5H1v2h3v-2zm9-9.95h-2V3.5h2V.55zm7.45 3.91l-1.41-1.41-1.79 1.79 1.41 1.41 1.79-1.79zm-3.21 13.7l1.79 1.8 1.41-1.41-1.8-1.79-1.4 1.4zM20 10.5v2h3v-2h-3zm-8-5c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6-2.69-6-6-6zm-1 16.95h2V19.5h-2v2.95zm-7.45-3.91l1.41 1.41 1.79-1.8-1.41-1.41-1.79 1.8z"/></svg>
    </div>
    <div class="feature-text">
      <h4>Open & Fresh Air</h4>
      <p>Enjoy the outdoors in a clean, open, and refreshing environment.</p>
    </div>
  </div>
</div>

<!-- ── PLAY AREAS ── -->
<section>
  <div class="section-header">
    <h2><span class="leaf-emoji">🌿</span> Explore Our Play Areas <span class="leaf-emoji">🌿</span></h2>
  </div>
  <div class="play-areas">
    <div class="play-grid">

      <div class="play-card">
        <div class="play-card-img-wrap">
          <img src="../assets/images/adventure.jpg" alt="Adventure Zone"/>
        </div>
        <div class="play-card-body">
          <h3>Adventure Zone</h3>
          <p>Climb, slide, and explore exciting structures that spark creativity and courage.</p>
        </div>
      </div>

      <div class="play-card">
        <div class="play-card-img-wrap">
          <img src="../assets/images/swing.png" alt="Swing Area"/> 
        </div>
        <div class="play-card-body">
          <h3>Swing Area</h3>
          <p>Feel the breeze and let the worries go as you swing into happiness.</p>
        </div>
      </div>

      <div class="play-card">
        <div class="play-card-img-wrap">
          <img src="../assets/images/openspace.png" alt="Open Space"/>
        </div>
        <div class="play-card-body">
          <h3>Open Space</h3>
          <p>Wide open spaces to run, play, or simply relax under the trees.</p>
        </div>
      </div>

      <div class="play-card">
        <div class="play-card-img-wrap">
          <img src="../assets/images/pg.jpg" alt="Imagination Corner"/>
        </div>
        <div class="play-card-body">
          <h3>Imagination Corner</h3>
          <p>A creative space where kids can imagine, pretend, and create their own adventures.</p>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ── WHY SECTION ── -->
<section class="why-section">
  <div class="why-left">
    <h2>Why Families<br>Love Our Playground 🌿</h2>
    <div class="why-divider"></div>
    <ul class="why-list">
      <li>
        <span class="why-check"><svg viewBox="0 0 12 12"><polyline points="2,6 5,9 10,3"/></svg></span>
        Surrounded by nature for a calming experience
      </li>
      <li>
        <span class="why-check"><svg viewBox="0 0 12 12"><polyline points="2,6 5,9 10,3"/></svg></span>
        Clean, safe, and well cared for
      </li>
      <li>
        <span class="why-check"><svg viewBox="0 0 12 12"><polyline points="2,6 5,9 10,3"/></svg></span>
        Encourages active play and creativity
      </li>
      <li>
        <span class="why-check"><svg viewBox="0 0 12 12"><polyline points="2,6 5,9 10,3"/></svg></span>
        A relaxing getaway for the whole family
      </li>
    </ul>
  </div>

  <div class="why-right">
    <div class="img-block">
      <img src="../assets/images/playground1.jpg" alt="Family at playground"/>
    </div>
    <div class="img-block">
      <img src="../assets/images/adventure.jpg" alt="Nature playground"/>
    </div>
    <div class="img-block">
      <img src="../assets/images/playground3.jpg" alt="Children playing"/>
    </div>
    <div class="img-block">
      <img src="../assets/images/playground2.jpg" alt="Playground slide"/>
    </div>
  </div>
</section>

<!-- ── FOOTER (matching home.html) ── -->
<footer>
  <div class="footer-image">
    <img src="../assets/images/westfarm1.jpg" alt="WestFarm sign">
  </div>
  <div class="footer-col">
    <h4>Call Us</h4>
    <div class="footer-phones">
      <a href="tel:09107305969">0910-730-5969</a>
      <a href="tel:09630113868">0963-011-3868</a>
    </div>
    <div class="footer-hours">
      Monday to Friday &nbsp;·&nbsp; 9am – 10pm<br>
      Weekend &nbsp;·&nbsp; 8am – 10pm
    </div>
    <div class="footer-social">
      <a href="#"><i class="fab fa-facebook-f"></i></a>
      <a href="#"><i class="fab fa-instagram"></i></a>
      <a href="#"><i class="fab fa-tiktok"></i></a>
    </div>
  </div>
  <div class="footer-col footer-nav">
    <h4>Navigation</h4>
    <a href="../public/index.php">Home</a>
    <a href="../public/about.php">About</a>
    <a href="#">Amenities</a>
    <a href="#">Accommodations</a>
    <a href="../public/events.php">Events</a>
    <a href="../public/faqs.php">FAQs</a>
    <a href="../public/contact.php">Contact</a>
  </div>
  <div class="footer-col footer-contact">
    <h4>Contact Info</h4>
    <p>📍 Dumpay West, Basista,<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Pangasinan, Philippines</p>
    <p>✉️ <a href="mailto:westfarmresort@gmail.com">westfarmresort@gmail.com</a></p>
  </div>
  <div class="footer-bottom">
    <div>
      <a href="#">Terms &amp; Conditions</a>
      <a href="#">Privacy Policy</a>
    </div>
    <div>© 2026. Angelito, Hazel, Relynne, Raymund All rights reserved.</div>
  </div>
</footer>

<button class="scroll-top" onclick="window.scrollTo({top:0,behavior:'smooth'})">▲</button>

<script src="../assets/js/public_playground.js"></script>
</body>
</html>