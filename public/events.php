<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Events at West Farm</title>
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;600;700&family=Josefin+Sans:wght@300;400;600;700&family=Lora:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="../assets/css/public_events.css">

</head>
<body>

<!-- ── NAV ── -->
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
  <div class="hero-overlay"></div>
  <div class="hero-content">
    <h1 class="hero-title">Celebrate Your Moments</h1>
    <p class="hero-desc">From intimate gatherings to grand celebrations, West Farm provides the perfect backdrop for your most memorable events.</p>
    <a href="../public/contact.php" class="btn-primary">Inquire Now</a>
  </div>
</section>

<!-- ── PERFECT MOMENTS ── -->
<section class="moments">
  <div class="moments-left">
    <p class="section-label">Celebrate in Style</p>
    <h2 class="section-title">Creating Your<br>Perfect Moments</h2>
    <div class="moments-body">
      <p>Experience the pinnacle of memorable occasions at WestFarm, where your perfect moments are elevated amidst breathtaking landscapes. Envision exchanging vows within the ethereal ambiance of the glass chapel, a sanctuary that melds nature and elegance.</p>
      <p>Following the ceremony, bask in the luxury of our pool clubhouse, a haven of relaxation and celebration, where vibrant conversations and laughter resonate alongside refreshing waters. WestFarm offers an unrivaled canvas to paint your cherished events, harmonizing beauty, comfort, and the magic of shared memories.</p>
    </div>
    <div class="inquiry-box">
      <h3>Unlock Unforgettable Moments!</h3>
      <p>For event inquiries, kindly provide the following details:</p>
      <ul>
        <li>Name</li>
        <li>Event Name</li>
        <li>Event Date</li>
        <li>Number of Pax</li>
        <li>Contact Number</li>
        <li>Email Address</li>
        <li>Other Relevant Information</li>
      </ul>
      <p class="send-info">And send to <a href="mailto:westfarmresort@gmail.com">westfarmresort@gmail.com</a> and we'll get in touch with you shortly.</p>
    </div>
  </div>

  <div class="photo-grid">
    <div class="photo"><img src="../assets/images/bday.jpg" alt="Birthday"></div>
    <div class="photo"><img src="../assets/images/wedding.jpg" alt="Wedding"></div>
    <div class="photo"><img src="../assets/images/7.jpg" alt="Event"></div>
    <div class="photo"><img src="../assets/images/18.jpg" alt="Event"></div>
  </div>
</section>

<hr class="divider">

<!-- ── CATERING ── -->
<section class="catering">
  <div>
    <p class="section-label">Choose the Best</p>
    <h2 class="section-title">Catering That Reflects<br>Your Unique Style</h2>
  </div>
  <div>
    <p style="font-family:'Lora',serif;font-size:0.88rem;color:#555;line-height:1.85;margin-bottom:28px;">
      Savor exquisite catering that marries scrumptious delights, a medley of menu choices, and contemporary elegance.
    </p>
    <div class="catering-points">
      <div class="catering-point"><span class="num">01.</span> Delicious Meals</div>
      <div class="catering-point"><span class="num">02.</span> Varied Menu Options</div>
      <div class="catering-point"><span class="num">03.</span> Modern Presentation</div>
    </div>
  </div>
</section>

<!-- ── EVENT TYPES ── -->
<section class="event-types">
  <div class="event-types-grid">
    <div class="event-card">
      <img src="../assets/images/parties.jpg" alt="Parties">
      <div class="event-card-overlay"></div>
      <span class="event-card-label">Parties</span>
    </div>
    <div class="event-card">
      <img src="../assets/images/weddings.jpg" alt="Wedding">
      <div class="event-card-overlay"></div>
      <span class="event-card-label">Wedding</span>
    </div>
    <div class="event-card">
      <img src="../assets/images/conferences.jpg" alt="Conferences">
      <div class="event-card-overlay"></div>
      <span class="event-card-label">Conferences</span>
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

<!-- scroll to top button -->
<button class="scroll-top" onclick="window.scrollTo({top:0,behavior:'smooth'})">▲</button>

<script src="../assets/js/public_events.js"></script>
</body>
</html>