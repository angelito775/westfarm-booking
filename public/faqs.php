<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>West Farm – FAQs</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Lato:wght@300;400;700&family=Josefin+Sans:wght@300;400;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/public_faqs.css"/>
  <link rel="stylesheet" href="../assets/css/public_nav.css"> 
  
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
        <a href="../public/glamping.php">GLAMPING</a>
        <a href="../public/luxury-villas.php">LUXURY VILLAS</a>
        <a href="../public/cottages.php">COTTAGES</a>
        <a href="../public/pavillion.php">PAVILLION</a>
      </div>
    </li>
    <li><a href="../public/events.php">EVENTS</a></li>
    <li><a href="../public/faqs.php" class="active">FAQs</a></li>
    <li><a href="../public/contact.php">CONTACT</a></li>
    <li><a href="../public/booking.php" class="nav-book-btn">BOOK NOW</a></li>
  </ul>
</nav>

<!-- ── HERO ── -->
<section class="hero">
  <img src="../assets/images/about2.jpg" alt="West Farm Resort" style="object-fit: cover; width: 100%; height: 100%;">
  <div class="hero-content">
    <h1>FAQS</h1>
  </div>
</section>

<section class="faq-section">

  <div class="faq-item">
    <button class="faq-question" onclick="faqToggle(this)">
      Where are you located?
      <span class="faq-icon">+</span>
    </button>
    <div class="faq-answer">
      We are located in Dumpay, Basista, Pangasinan, Philippines.
      The farm is easily accessible by private vehicle. Landmarks and
      detailed directions can be found on our Contact page or by calling us.
    </div>
  </div>

  <div class="faq-item">
    <button class="faq-question" onclick="faqToggle(this)">
      Are you pet-friendly?
      <span class="faq-icon">+</span>
    </button>
    <div class="faq-answer">
      Yes! We welcome well-behaved pets. Please keep them on a leash at all
      times and be responsible for cleaning up after them. Some areas may be
      restricted — please check with our staff upon arrival.
    </div>
  </div>

  <div class="faq-item">
    <button class="faq-question" onclick="faqToggle(this)">
      Can we bring outside food?
      <span class="faq-icon">+</span>
    </button>
    <div class="faq-answer">
      Outside food is generally allowed for personal consumption. However,
      we encourage guests to try our in-house restaurant and café, which
      uses fresh, farm-to-table ingredients. Corkage fees may apply for
      alcoholic beverages.
    </div>
  </div>

  <div class="faq-item">
    <button class="faq-question" onclick="faqToggle(this)">
      What is your mode of payment?
      <span class="faq-icon">+</span>
    </button>
    <div class="faq-answer">
      We accept cash and GCash. For reservations and events, a down payment
      may be required to confirm your booking. Please contact us for specific
      payment arrangements.
    </div>
  </div>

</section>

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

<script>
  // Basic FAQ toggle functionality
  function faqToggle(btn) {
    const item = btn.parentElement;
    const answer = item.querySelector('.faq-answer');
    const icon = btn.querySelector('.faq-icon');
    
    // Close all other open items
    document.querySelectorAll('.faq-item.open').forEach(openItem => {
      if (openItem !== item) {
        openItem.classList.remove('open');
        openItem.querySelector('.faq-answer').style.maxHeight = '0px';
        openItem.querySelector('.faq-icon').textContent = '+';
      }
    });

    item.classList.toggle('open');
    answer.style.maxHeight = item.classList.contains('open') ? answer.scrollHeight + 'px' : '0px';
    icon.textContent = item.classList.contains('open') ? '−' : '+';
  }
</script>
<script src="../assets/js/public_nav.js"></script>
</body>
</html>