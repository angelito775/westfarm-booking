<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>About | West Farm Resort and Hotel</title>
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;600;700&family=Josefin+Sans:wght@300;400;600;700&family=Lora:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="../assets/css/public_about.css">

    </head>
<body>

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
    <li><a href="../public/about.php" class="active">ABOUT</a></li>
    <li class="nav-item">
      <a href="#" class="nav-btn">AMENITIES</a>
      <div class="dropdown-menu">
        <a href="../public/westpool.php">WEST POOL</a>
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
    <li><a href="../EVENTS/events.html">EVENTS</a></li>
    <li><a href="../FAQS/faqs.html">FAQs</a></li>
    <li><a href="../CONTACT/contact.html">CONTACT</a></li>
    <li><a href="../BOOKING/booking.html" class="nav-book-btn">BOOK NOW</a></li>
  </ul>
</nav>

<!-- HERO SLIDESHOW -->
<section class="hero" id="hero">
  <div class="hero-slide active" style="background-image:url('../assets/images/about1.jpg')"></div>
  <div class="hero-slide" style="background-image:url('../assets/images/about2.jpg')"></div>
  <div class="hero-slide" style="background-image:url('../assets/images/about3.jpg')"></div>
  <div class="hero-slide" style="background-image:url('../assets/images/about4.jpg')"></div>
  <div class="hero-overlay"><h1>About</h1></div>
  <button class="hero-arr left" onclick="heroSlide(-1)">&#8249;</button>
  <button class="hero-arr right" onclick="heroSlide(1)">&#8250;</button>
  <div class="hero-dots" id="hero-dots"></div>
</section>

<!-- WHY CHOOSE US -->
<section class="why-choose">
  <h2>Why Choose Us</h2>
  <p class="subtitle">Top Reasons to Choose West Farm</p>
  <div class="features">
    <div class="feature">
      <div class="feature-icon">
        <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M32 8C20 8 12 20 20 30C24 35 32 56 32 56C32 56 40 35 44 30C52 20 44 8 32 8Z" fill="#1a3a1a"/>
          <path d="M24 22C24 22 28 28 36 26" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
          <path d="M28 28C28 28 30 36 36 34" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </div>
      <h3>Live Amidst Nature</h3>
      <p>Immerse yourself in nature's beauty to rejuvenate and refresh.</p>
    </div>
    <div class="feature">
      <div class="feature-icon">
        <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect x="16" y="28" width="32" height="20" rx="2" fill="#8b5e3c"/>
          <path d="M10 30 L32 14 L54 30" stroke="#8b5e3c" stroke-width="3" stroke-linecap="round"/>
          <rect x="24" y="36" width="16" height="12" fill="#c49a6c"/>
          <rect x="28" y="32" width="8" height="6" rx="1" fill="#5a3a1a"/>
        </svg>
      </div>
      <h3>Rustic Haven</h3>
      <p>Discover rustic charm in a wood-inspired atmosphere for an authentic experience.</p>
    </div>
    <div class="feature">
      <div class="feature-icon">
        <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="22" cy="18" r="8" fill="#1a3a1a"/>
          <circle cx="42" cy="18" r="8" fill="#1a3a1a"/>
          <path d="M8 52C8 40 18 34 22 34C30 34 34 34 42 34C46 34 56 40 56 52" fill="#1a3a1a"/>
          <circle cx="32" cy="30" r="5" fill="#2c552c"/>
        </svg>
      </div>
      <h3>Family Friendly</h3>
      <p>Enjoy a calm and welcoming environment that feels just like home for your family.</p>
    </div>
  </div>
</section>

<!-- SPLIT: IMAGE SLIDESHOW + TEXT -->
<section class="split">
  <div class="split-images" id="slideshow">
    <img id="slide-img" src="../assets/images/about5.jpg" alt="Farm landscape">
    <span class="slideshow-counter" id="counter">1 of 4</span>
    <div class="slideshow-nav">
      <button onclick="prevSlide()" aria-label="Previous">&#8249;</button>
      <button onclick="nextSlide()" aria-label="Next">&#8250;</button>
    </div>
  </div>
  <div class="split-text">
    <h2>Why West Farm?</h2>
    <p>West Farm began as a simple dream to create a peaceful retreat inspired by the beauty of nature and the calmness of farm life. Surrounded by open spaces, fresh air, and greenery, the founders envisioned a place where people could slow down, relax, and reconnect with the outdoors. What started as a private farm gradually grew into a thoughtfully developed destination, blending natural landscapes with comfortable amenities for guests to enjoy.</p>
    <p>Over time, West Farm became the family's personal escape from the noise and fast pace of everyday living. They spent their days enjoying the scenery, hosting small gatherings, and sharing meaningful moments with friends and relatives. Seeing the happiness and relaxation the place brought to their visitors, the family decided to open West Farm to the public — offering a serene getaway where everyone can experience nature, comfort, and genuine hospitality.</p>
  </div>
</section>

<!-- TWO COL: ECOFARM + WHY WEST -->
<div class="two-col">
  <div class="col-cream">
    <h2>Why Ecofarm?</h2>
    <p>The farm is surrounded by vast fields of corn and rice paddies, offering a refreshing countryside view that highlights the natural beauty of agricultural life. Within the ecofarm, various fruit-bearing trees such as mango, banana, and coconut can be found, along with different hardwood trees that help maintain a balanced and sustainable ecosystem.</p>
    <p>The walkways are illuminated by solar-powered lights, enhancing safety while promoting energy efficiency. The walls of the glamping rooms are made from bamboo wood, showcasing a natural and eco-friendly design that blends with the surroundings.</p>
    <p>Through these sustainable features, we aim to show our guests that living an eco-friendly lifestyle is possible without sacrificing comfort, quality, and a meaningful experience in nature.</p>
  </div>
  <div class="col-green">
    <h2>Why West?</h2>
    <p>The name "West" also represents its identity and roots in the western part of Basista, while symbolizing direction, calmness, and balance. It reflects the farm's vision of becoming a destination where guests can unwind, experience nature-based living, and appreciate sustainable farm life.</p>
    <p>At West Farm Resort and Hotel, we aim to blend comfort with nature — offering not just accommodation, but a meaningful experience where guests can enjoy the beauty of the countryside while embracing a simple and sustainable way of living.</p>
  </div>
</div>

<!-- BOOK BANNER -->
<section class="book-banner">
  <h2>Book Your Stay Now!</h2>
  <p>Call Us: <a href="tel:+639107305969">+63 910 730 5969</a></p>
  <a href="#" class="book-btn">Book Now</a>
</section>

<!-- VISION & MISSION -->
<section class="vision-mission">
  <div class="vm-block">
    <h2>Our Vision</h2>
    <div class="vm-divider"><span>🌿</span></div>
    <ul>
      <li>To become premier eco-tourism destination in the Pangasinan</li>
      <li>To be known as one of the leading choice for destination weddings and events.</li>
      <li>To impart with the guests the knowledge on how to have sustainable practices.</li>
      <li>To provide well-rounded nature retreat to all guests.</li>
    </ul>
  </div>
  <div class="vm-block">
    <h2>Our Mission</h2>
    <div class="vm-divider"><span>🌿</span></div>
    <p>Committed in providing environmentally-sound experience and a well-rounded retreat to new and returning guests that exceeds their expectations with our sustainable practice and outstanding service.</p>
  </div>
</section>

<!-- ── FOOTER (matching home.html) ── -->
<footer>
  <div class="footer-image">
    <img src="../assets/images/westfarmgate.jpg" alt="WestFarm sign">
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
<script src="../assets/js/public_about.js"></script>
</body>
</html>