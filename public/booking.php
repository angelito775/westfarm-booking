<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Booking | West Farm Resort and Hotel</title>
  <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;600;700&family=Josefin+Sans:wght@300;400;600;700&family=Lora:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/public_booking.css">
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
      <a href="#" class="nav-btn">AMENITIES</a>
      <div class="dropdown-menu">
        <a href="../public/westpool.php">WEST POOL</a>
        <a href="../public/westcrays.php">WEST CRAY ORDERING</a>
        <a href="../public/playground.php">PLAYGROUND</a>
      </div>
    </li>
    <li class="nav-item">
      <a href="#" class="nav-btn active">ACCOMMODATIONS</a>
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

<header class="hero">
  <div class="hero-inner">
    <h1>West Farm Resort and Hotel: Where nature meets nurture.</h1>
    <p>Cabins, cozy cottages, and Private Villas tucked into the lush embrace of nature.</p>
  </div>
</header>

<main class="container">
  <div class="layout">
    <section>
      <div class="filterbar">
        <div class="tabs" id="tabs">
          <button class="active" data-filter="All">All</button>
          <span class="sep">|</span>
          <button data-filter="Cabin">Cabin</button>
          <span class="sep">|</span>
          <button data-filter="Private Villa">Private Villa</button>
          <span class="sep">|</span>
          <button data-filter="Cottage">Cottages</button>
        </div>
        <div class="search">
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-3.5-3.5"/></svg>
          <input id="search" type="text" placeholder="Search accommodation" />
        </div>
      </div>
      <div class="cards" id="cards"></div>
    </section>

    <aside class="sidebar">
      <div class="booking">
        <div class="booking-title">Plan Your Stay</div>
        <div class="cal-head">
          <button id="prevMonth">‹</button>
          <div class="cal-title" id="calTitle"></div>
          <button id="nextMonth">›</button>
        </div>
        <div class="cal" id="cal"></div>
        <div class="dates">
          <div class="date-box"><div class="lbl">Check In</div><div class="val" id="checkIn">—</div></div>
          <div class="date-box"><div class="lbl">Check Out</div><div class="val" id="checkOut">—</div></div>
        </div>
        <div class="counters">
          <div class="counter"><span>Adults:</span><div class="ctrl"><button data-c="adults" data-d="-1">−</button><span class="val" id="adults">1</span><button data-c="adults" data-d="1">+</button></div></div>
          <div class="counter"><span>Kids:</span><div class="ctrl"><button data-c="kids" data-d="-1">−</button><span class="val" id="kids">0</span><button data-c="kids" data-d="1">+</button></div></div>
        </div>
        <div class="nights"><span class="num" id="nightsNum">0</span>Nights</div>
        <button class="btn block" style="margin-top: 16px;" id="bookBtn">Book Now</button>
      </div>
    </aside>
  </div>
</main>

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
    <div id="copyright-info">© 2026. Angelito, Hazel, Relynne, Raymund All rights reserved.</div>
  </div>
</footer>

<div class="toast" id="toast"></div>

<script src="../assets/js/public_booking.js"></script>
</body>
</html>