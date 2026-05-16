<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>West Pool | West Farm Resort and Hotel</title>
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;600;700&family=Josefin+Sans:wght@300;400;600;700&family=Lora:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="../assets/css/public_westpool.css">
</head>
<body>

<!-- NAV -->
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
        <a href="../public/westpool.php" class="active">WEST POOL</a>
        <a href="../public/westcrays.php">WEST CRAY ORDERING</a>
        <a href="../public/playground.php">PLAYGROUND</a>
      </div>
    </li>
    <li class="nav-item">
      <a href="#" class="nav-btn">ACCOMMODATIONS</a>
      <div class="dropdown-menu">
        <a href="#">GLAMPING</a>
        <a href="#">LUXURY VILLAS</a>
        <a href="#">COTTAGES</a>
        <a href="#">PAVILLION</a>
      </div>
    </li>
    <li><a href="../public/events.php">EVENTS</a></li>
    <li><a href="../public/faqs.php">FAQs</a></li>
    <li><a href="../public/contact.php">CONTACT</a></li>
    <li><a href="../public/booking.php" class="nav-book-btn">BOOK NOW</a></li>
  </ul>
</nav>

<!-- HERO -->
<section class="hero">
  <img class="hero-bg"
    src="../assets/images/West Pool.jpg"
    alt="West Pool"
    onerror="this.src='https://images.unsplash.com/photo-1540541338287-41700207dee6?w=1400&q=85'"
  />
  <svg class="hero-blob-mask" viewBox="0 0 1440 640" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M1440 0 L860 0 C800 0, 740 30, 720 120 C700 210, 740 270, 745 340 C750 410, 710 490, 730 580 C745 640, 810 640, 880 640 L1440 640 Z" fill="white" opacity="0.95"/>
  </svg>
  <div class="hero-left" style="margin-left:auto; padding: 60px 72px 60px 60px;">
    <p class="hero-eyebrow">Dive Into</p>
    <h1 class="hero-title">Cool Waters,<br>Pure Bliss. 🌊</h1>
    <p class="hero-desc">West Pool is your serene escape — a crystal-clear retreat surrounded by lush nature where every splash becomes a cherished memory.</p>
    <a href="#rates" class="btn-primary">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5S10.62 6.5 12 6.5s2.5 1.12 2.5 2.5S13.38 11.5 12 11.5z"/></svg>
      View Pool Rates
    </a>
  </div>
</section>

<!-- FEATURES BAR — 3 items, 3 columns (empty item removed) -->
<div class="features-bar">
  <div class="feature-item">
    <div class="feature-icon">
      <svg viewBox="0 0 24 24"><path d="M22 21c-1.11 0-1.73-.37-2.18-.64-.37-.22-.6-.36-1.15-.36-.56 0-.78.13-1.15.36-.46.27-1.07.64-2.18.64s-1.73-.37-2.18-.64c-.37-.22-.6-.36-1.15-.36-.56 0-.78.13-1.15.36C10.51 20.63 9.89 21 8.78 21c-1.11 0-1.73-.37-2.18-.64-.37-.22-.6-.36-1.15-.36-.56 0-.78.13-1.15.36C3.84 20.63 3.22 21 2.11 21H2v2h.11c1.11 0 1.73-.37 2.18-.64.37-.22.6-.36 1.15-.36.56 0 .78.13 1.15.36.46.27 1.08.64 2.19.64 1.11 0 1.73-.37 2.18-.64.37-.22.6-.36 1.15-.36.56 0 .78.13 1.15.36.46.27 1.08.64 2.19.64 1.11 0 1.73-.37 2.18-.64.37-.22.6-.36 1.15-.36.56 0 .78.13 1.15.36.45.27 1.07.64 2.18.64h.11v-2H22zM12 2C9.24 2 7 4.24 7 7c0 1.93 1.03 3.61 2.56 4.55L7 16h2l1.5-2h3l1.5 2h2l-2.56-4.45C15.97 10.61 17 8.93 17 7c0-2.76-2.24-5-5-5zm0 7.5c-1.38 0-2.5-1.12-2.5-2.5S10.62 4.5 12 4.5s2.5 1.12 2.5 2.5S13.38 9.5 12 9.5z"/></svg>
    </div>
    <div class="feature-text">
      <h4>Crystal Clear Water</h4>
      <p>Regularly treated and filtered for safe, pristine swimming.</p>
    </div>
  </div>
  <div class="feature-item">
    <div class="feature-icon">
      <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
    </div>
    <div class="feature-text">
      <h4>All Ages Welcome</h4>
      <p>Shallow and deep sections for every swimmer.</p>
    </div>
  </div>
  <div class="feature-item">
    <div class="feature-icon">
      <svg viewBox="0 0 24 24"><path d="M17 8C8 10 5.9 16.17 3.82 21H5.71C6.66 19 8 17.72 10 17c1.6-.6 3.3-.6 5 .07V21h2V3c-2.36.85-4.19 2.58-5 5z"/></svg>
    </div>
    <div class="feature-text">
      <h4>Nature Setting</h4>
      <p>Lush greenery surrounds the pool for a tranquil feel.</p>
    </div>
  </div>
</div>

<!-- POOL AREAS — 3 cards, 3 columns -->
<section>
  <div class="section-header">
    <h2>🌊 Explore Our Pool <span style="display:inline-block">🌊</span></h2>
  </div>
  <div class="pool-areas">
    <div class="pool-grid">
      <div class="pool-card">
        <div class="pool-card-img-wrap">
          <img src="../assets/images/publicpool.png" alt="Public Pool"
            onerror="this.src='https://images.unsplash.com/photo-1575783970733-1aaedde1db74?w=600&q=80'"/>
        </div>
        <div class="pool-card-body">
          <h3>Public Pool</h3>
          <p>Our spacious main pool is perfect for laps, leisure, and making waves with friends and family.</p>
        </div>
      </div>
      <div class="pool-card">
        <div class="pool-card-img-wrap">
          <img src="../assets/images/privatepool.jpg" alt="Private Pool"
            onerror="this.src='https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?w=600&q=80'"/>
        </div>
        <div class="pool-card-body">
          <h3>Private Pool</h3>
          <p>A safe, shallow pool specially designed for little ones to splash and play worry-free.</p>
        </div>
      </div>
      <div class="pool-card">
        <div class="pool-card-img-wrap">
          <img src="../assets/images/poolshower.png" alt="Shower & Changing Area"
            onerror="this.src='https://images.unsplash.com/photo-1584622650111-993a426fbf0a?w=600&q=80'"/>
        </div>
        <div class="pool-card-body">
          <h3>Shower & Amenities</h3>
          <p>Clean, well-maintained shower rooms and changing facilities for your convenience.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- RATES SECTION -->
<section class="rates-section" id="rates">
  <div class="rates-inner">
    <h2>Pool Entry Rates</h2>
    <p class="rates-sub">Simple, transparent pricing for everyone</p>
    <div class="rates-grid">
      <div class="rate-card">
        <div class="rate-badge">Day Pass</div>
        <h3>Children</h3>
        <div class="rate-price">₱100</div>
        <div class="rate-period">per child · ages 3–12</div>
        <ul class="rate-features">
          <li>Access to main pool</li>
          <li>Access to kiddie playground</li>
          <li>Shower facilities</li>
        </ul>
      </div>
      <div class="rate-card featured">
        <div class="rate-badge">Most Popular</div>
        <h3>Adults</h3>
        <div class="rate-price">₱120</div>
        <div class="rate-period">per adult · ages 13+</div>
        <ul class="rate-features">
          <li>Public pool access</li>
          <li>Access to resort tour</li>
          <li>Shower facilities</li>
        </ul>
      </div>
      <div class="rate-card">
        <div class="rate-badge">Best Value</div>
        <h3>Family Bundle</h3>
        <div class="rate-price">₱7500</div>
        <div class="rate-period">6 to 8 guests, whether adults or children</div>
        <ul class="rate-features">
          <li>Full pool access for all both private and public areas</li>
          <li>Priority lounge seats</li>
          <li>Private Room</li>
          <li>Private Bathroom Shower</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<!-- WHY SECTION -->
<section class="why-section">
  <div class="why-images">
    <div class="img-block"><img src="../assets/images/view.jpg" alt="Pool view"></div>
    <div class="img-block"><img src="../assets/images/West Pool.jpg" alt="Guests swimming"></div>
    <div class="img-block"><img src="../assets/images/publicpool.png" alt="Poolside lounge"></div>
    <div class="img-block"><img src="../assets/images/view1.jpg" alt="Kids in pool"></div>
  </div>
  <div class="why-left">
    <h2>Why Guests Love<br>West Pool 🌊</h2>
    <div class="why-divider"></div>
    <ul class="why-list">
      <li>
        <span class="why-check"><svg viewBox="0 0 12 12"><polyline points="2,6 5,9 10,3"/></svg></span>
        Crystal-clear, regularly maintained water
      </li>
      <li>
        <span class="why-check"><svg viewBox="0 0 12 12"><polyline points="2,6 5,9 10,3"/></svg></span>
        Surrounded by calming, lush greenery
      </li>
      <li>
        <span class="why-check"><svg viewBox="0 0 12 12"><polyline points="2,6 5,9 10,3"/></svg></span>
        Separate sections for private and public
      </li>
      <li>
        <span class="why-check"><svg viewBox="0 0 12 12"><polyline points="2,6 5,9 10,3"/></svg></span>
        Clean facilities and comfortable lounging
      </li>
    </ul>
  </div>
</section>

<!-- FOOTER -->
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
    <a href="#">Home</a>
    <a href="#">About</a>
    <a href="#">Amenities</a>
    <a href="#">Accommodations</a>
    <a href="#">Events</a>
    <a href="#">FAQs</a>
    <a href="#">Contact</a>
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

<script src="../assets/js/public_westpool.js"></script>
</body>
</html>