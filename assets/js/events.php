<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Events at West Farm</title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Lato:wght@300;400;700&family=Dancing+Script:wght@700&family=Josefin+Sans:wght@300;400;600;700&family=Lora:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="../assets/css/public_events.css" />
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
    <li><a href="../public/events.php" class="active">EVENTS</a></li>
    <li><a href="../public/faqs.php">FAQs</a></li>
    <li><a href="../public/contact.php">CONTACT</a></li>
    <li><a href="../public/booking.php" class="nav-book-btn">BOOK NOW</a></li>
  </ul>
</nav>

<!-- ── HERO ── -->
<section class="hero">
  <img class="hero-bg"
    src="../assets/images/events_bg.jpg"
    alt="Elegant event setup at West Farm"
    onerror="this.src='https://images.unsplash.com/photo-1527529482837-4698179dc6ce?w=1400&q=85'"
  />
  <div class="hero-content">
    <h1 class="hero-title">Celebrate Your Moments</h1>
    <p class="hero-desc">From intimate gatherings to grand celebrations, West Farm provides the perfect backdrop for your most memorable events.</p>
    <a href="#contact" class="btn-primary">Inquire Now</a>
  </div>
</section>

<!-- ── EVENT TYPES ── -->
<section class="event-types">
  <div class="section-header">
    <h2>Host Your Event With Us</h2>
    <p>We cater to a wide range of events, ensuring each one is unique and special.</p>
  </div>
  <div class="event-grid">
    <div class="event-card">
      <img src="../assets/images/event_wedding.jpg" alt="Wedding celebration">
      <h3>Weddings</h3>
      <p>Create the wedding of your dreams amidst our scenic landscapes and elegant venues.</p>
    </div>
    <div class="event-card">
      <img src="../assets/images/event_corporate.jpg" alt="Corporate meeting">
      <h3>Corporate Functions</h3>
      <p>Inspire your team with our professional and refreshing environment for meetings and retreats.</p>
    </div>
    <div class="event-card">
      <img src="../assets/images/event_birthday.jpg" alt="Birthday party">
      <h3>Birthdays & Parties</h3>
      <p>Celebrate another year of life with a fun-filled party for all ages.</p>
    </div>
    <div class="event-card">
      <img src="../assets/images/event_family.jpg" alt="Family reunion">
      <h3>Family Reunions</h3>
      <p>Gather your loved ones and make new memories in a place that feels like home.</p>
    </div>
  </div>
</section>

<!-- ── VENUES ── -->
<section class="venues">
    <div class="section-header">
        <h2>Our Venues</h2>
        <p>Choose the perfect space for your occasion.</p>
    </div>
    <div class="venue-item">
        <img src="../assets/images/venue_pavillion.jpg" alt="The Grand Pavillion">
        <div class="venue-info">
            <h3>The Grand Pavillion</h3>
            <p>Our largest venue, perfect for weddings and large corporate events. Features high ceilings and a panoramic view of the resort.</p>
            <span>Capacity: 200 guests</span>
        </div>
    </div>
    <div class="venue-item">
        <img src="../assets/images/venue_garden.jpg" alt="The Garden Terrace">
        <div class="venue-info">
            <h3>The Garden Terrace</h3>
            <p>An open-air venue surrounded by lush greenery, ideal for intimate ceremonies and cocktail parties.</p>
            <span>Capacity: 80 guests</span>
        </div>
    </div>
</section>

<!-- ── FOOTER ── -->
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
  <div class="footer-col footer-contact" id="contact">
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

<script src="../assets/js/public_events.js"></script>
</body>
</html>