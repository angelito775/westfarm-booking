<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']) && $_SESSION['user_type_id'] == 2;
$customer_name = '';
if ($is_logged_in) {
    require_once '../config/db_connection.php';
    $stmt = $pdo->prepare("SELECT first_name, last_name FROM user_profiles WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $profile = $stmt->fetch();
    $customer_name = $profile ? ($profile['first_name'] . ' ' . $profile['last_name']) : 'Guest';
}
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
        <a href="../public/glamping.php">GLAMPING</a>
        <a href="../public/luxury-villas.php">LUXURY VILLAS</a>
        <a href="../public/cottages.php">COTTAGES</a>
        <a href="../public/pavillion.php">PAVILLION</a>
      </div>
    </li>
    <li><a href="../public/events.php">EVENTS</a></li>
    <li><a href="../public/faqs.php">FAQs</a></li>
    <li><a href="../public/contact.php">CONTACT</a></li>
    <li><a href="../public/booking.php" class="nav-book-btn active">BOOK NOW</a></li>
    <?php if ($is_logged_in): ?>
    <li><a href="../customer/payment_booking.php"><i class="fas fa-calendar-alt"></i> MY BOOKINGS</a></li>
    <li><a href="../customer/profile.php"><i class="fas fa-user-circle"></i> MY PROFILE</a></li>
    <?php endif; ?>
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
        <!-- User info / login prompt -->
        <div id="bookingUserArea" style="margin-bottom: 12px;">
          <?php if ($is_logged_in): ?>
            <div style="background:#f0fdf4;padding:10px 12px;border-radius:8px;border:1px solid #bbf7d0;">
              <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                <div>
                  <div style="font-size:12px;color:#16a34a;">Booking as</div>
                  <div style="font-weight:600;color:#14532d;font-size:14px;"><?php echo htmlspecialchars($customer_name); ?> <i class="fas fa-user-check" style="color:#16a34a;"></i></div>
                </div>
                <a href="../logic/logout_customer.php" style="font-size:12px;color:#6b7280;text-decoration:none;">Sign out</a>
              </div>
              <div style="display:flex;gap:6px;">
                <a href="../customer/payment_booking.php" style="flex:1;display:flex;align-items:center;justify-content:center;gap:5px;padding:6px 8px;background:#fff;border:1px solid #bbf7d0;border-radius:6px;font-size:11px;font-weight:600;color:#166534;text-decoration:none;transition:background 0.2s;">
                  <i class="fas fa-calendar-alt"></i> My Bookings
                </a>
                <a href="../customer/profile.php" style="flex:1;display:flex;align-items:center;justify-content:center;gap:5px;padding:6px 8px;background:#fff;border:1px solid #bbf7d0;border-radius:6px;font-size:11px;font-weight:600;color:#166534;text-decoration:none;transition:background 0.2s;">
                  <i class="fas fa-user-circle"></i> My Profile
                </a>
              </div>
            </div>
          <?php else: ?>
            <button id="loginToBookBtn" style="width:100%;padding:10px;background:#2F3D2E;color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:600;font-size:14px;">
              <i class="fas fa-lock" style="margin-right:6px;"></i> Sign in to Book
            </button>
          <?php endif; ?>
        </div>

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

        <!-- Selected facility display -->
        <div id="selectedFacilityBox" style="display:none;margin-top:12px;padding:10px;background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;">
          <div style="font-size:11px;color:#0369a1;font-weight:600;margin-bottom:2px;">SELECTED FACILITY</div>
          <div id="selectedFacilityName" style="font-weight:700;color:#0c4a6e;font-size:14px;"></div>
          <div id="selectedFacilityPrice" style="font-size:13px; color:#0369a1;"></div>
        </div>

        <div id="bookingTotalBox" style="display:none;margin-top:8px;text-align:right;font-size:13px;color:#6b7280;">
          Total: <strong id="bookingTotalAmount" style="font-size:18px;color:#2F3D2E;">₱ 0</strong>
        </div>

        <button class="btn block" style="margin-top: 16px;" id="bookBtn" disabled>Select a facility</button>

        <!-- Crayfish ordering section -->
        <div style="margin-top:24px;padding-top:20px;border-top:1px solid #e5e7eb;">
          <div style="font-family:'Josefin Sans',sans-serif;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#888;margin-bottom:10px;">Resort Dining</div>
          <a href="../public/westcrays.php" style="display:flex;align-items:center;gap:10px;padding:14px 16px;background:linear-gradient(135deg,#f0fdf4 0%,#dcfce7 100%);border:1px solid #bbf7d0;border-radius:12px;text-decoration:none;transition:all 0.2s;" onmouseover="this.style.borderColor='#16a34a';this.style.boxShadow='0 4px 12px rgba(22,163,74,0.15)';" onmouseout="this.style.borderColor='#bbf7d0';this.style.boxShadow='none';">
            <span style="font-size:28px;">🦞</span>
            <div style="flex:1;">
              <div style="font-family:'Dancing Script',cursive;font-size:18px;font-weight:700;color:#166534;">West Cray Ordering</div>
              <div style="font-size:12px;color:#16a34a;margin-top:2px;">Fresh crayfish delivered to your room</div>
            </div>
            <i class="fas fa-arrow-right" style="color:#16a34a;font-size:14px;"></i>
          </a>
        </div>
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

<!-- ═══ LOGIN / REGISTER MODAL ═══ -->
<div id="authModal" class="auth-modal-overlay" style="display:none;">
  <div class="auth-modal">
    <button class="auth-modal-close" onclick="closeAuthModal()">&times;</button>
    <div class="auth-modal-header">
      <div class="auth-tabs">
        <button class="auth-tab active" id="loginTabBtn" onclick="switchAuthTab('login')">Sign In</button>
        <button class="auth-tab" id="registerTabBtn" onclick="switchAuthTab('register')">Create Account</button>
      </div>
    </div>
    <div class="auth-modal-body">
      <!-- Login Form -->
      <form id="loginForm" onsubmit="handleLoginSubmit(event)" novalidate>
        <div class="auth-error" id="loginError" style="display:none;"></div>
        <div class="auth-field">
          <label for="loginEmail">Email Address</label>
          <div class="auth-input-wrap">
            <span class="auth-input-icon"><i class="fas fa-envelope"></i></span>
            <input type="email" id="loginEmail" name="email" placeholder="you@example.com" required>
          </div>
          <div class="auth-field-error" id="loginEmailError"></div>
        </div>
        <div class="auth-field">
          <label for="loginPassword">Password</label>
          <div class="auth-input-wrap">
            <span class="auth-input-icon"><i class="fas fa-lock"></i></span>
            <input type="password" id="loginPassword" name="password" placeholder="••••••••" required>
            <button type="button" class="auth-pw-toggle" onclick="toggleLoginPw()">
              <i class="fas fa-eye" id="loginPwIcon"></i>
            </button>
          </div>
          <div class="auth-field-error" id="loginPasswordError"></div>
        </div>
        <button type="submit" class="auth-submit-btn" id="loginSubmitBtn">
          <i class="fas fa-sign-in-alt"></i> Sign In
        </button>
      </form>

      <!-- Register Form -->
      <form id="registerForm" style="display:none;" onsubmit="handleRegisterSubmit(event)" novalidate>
        <div class="auth-error" id="registerError" style="display:none;"></div>
        <div class="auth-field-row">
          <div class="auth-field">
            <label for="regFirstName">First Name <span class="auth-required">*</span></label>
            <input type="text" id="regFirstName" name="first_name" placeholder="Juan" required>
            <div class="auth-field-error" id="regFirstNameError"></div>
          </div>
          <div class="auth-field">
            <label for="regLastName">Last Name <span class="auth-required">*</span></label>
            <input type="text" id="regLastName" name="last_name" placeholder="Dela Cruz" required>
            <div class="auth-field-error" id="regLastNameError"></div>
          </div>
        </div>
        <div class="auth-field">
          <label for="regEmail">Email Address <span class="auth-required">*</span></label>
          <div class="auth-input-wrap">
            <span class="auth-input-icon"><i class="fas fa-envelope"></i></span>
            <input type="email" id="regEmail" name="email" placeholder="you@example.com" required>
          </div>
          <div class="auth-field-error" id="regEmailError"></div>
        </div>
        <div class="auth-field">
          <label for="regPhone">Phone Number</label>
          <div class="auth-input-wrap">
            <span class="auth-input-icon"><i class="fas fa-phone"></i></span>
            <input type="text" id="regPhone" name="phone_number" placeholder="09XXXXXXXXX" inputmode="numeric">
          </div>
          <div class="auth-field-error" id="regPhoneError"></div>
        </div>
        <div class="auth-field-row">
          <div class="auth-field">
            <label for="regPassword">Password <span class="auth-required">*</span></label>
            <input type="password" id="regPassword" name="password" placeholder="Min 6 characters" minlength="6" required>
            <div class="auth-field-error" id="regPasswordError"></div>
          </div>
          <div class="auth-field">
            <label for="regConfirmPassword">Confirm <span class="auth-required">*</span></label>
            <input type="password" id="regConfirmPassword" name="confirm_password" placeholder="Re-enter password" required>
            <div class="auth-field-error" id="regConfirmPasswordError"></div>
          </div>
        </div>
        <button type="submit" class="auth-submit-btn" id="registerSubmitBtn">
          <i class="fas fa-user-plus"></i> Create Account
        </button>
      </form>
    </div>
  </div>
</div>

<!-- ═══ CONFIRM BOOKING MODAL ═══ -->
<div id="confirmBookingModal" class="auth-modal-overlay" style="display:none;">
  <div class="auth-modal" style="max-width:480px;">
    <button class="auth-modal-close" onclick="closeConfirmBookingModal()">&times;</button>
    <div class="auth-modal-header">
      <h3 style="font-family:'Dancing Script',cursive;font-size:24px;color:#2F3D2E;margin:0;">Confirm Your Booking</h3>
    </div>
    <div class="auth-modal-body">
      <div id="confirmBookingSummary" style="background:#f9fafb;padding:16px;border-radius:8px;border:1px solid #e5e7eb;margin-bottom:16px;">
      </div>
      <form id="confirmBookingForm" action="../logic/customer_booking_process.php" method="POST">
        <input type="hidden" name="action" value="customer_create_booking">
        <input type="hidden" name="facility_id" id="confirmFacilityId" value="">
        <input type="hidden" name="check_in_date" id="confirmCheckIn" value="">
        <input type="hidden" name="check_out_date" id="confirmCheckOut" value="">
        <input type="hidden" name="num_guests" id="confirmNumGuests" value="">
        <button type="submit" class="auth-submit-btn" id="confirmBookingBtn">
          <i class="fas fa-check-circle"></i> Confirm &amp; Submit Booking
        </button>
        <p style="margin-top:12px;font-size:12px;color:#6b7280;text-align:center;">
          Your booking will be submitted as <strong>Pending</strong>. You will receive confirmation from the resort.
        </p>
      </form>
    </div>
  </div>
</div>

<!-- ═══ BOOKING SUCCESS MODAL ═══ -->
<div id="bookingSuccessModal" class="auth-modal-overlay" style="display:none;">
  <div class="auth-modal" style="max-width:400px;text-align:center;">
    <div class="auth-modal-body" style="padding:40px 24px;">
      <div style="font-size:48px;color:#16a34a;"><i class="fas fa-check-circle"></i></div>
      <h3 style="margin:16px 0 8px;font-family:'Dancing Script',cursive;font-size:28px;color:#2F3D2E;">Booking Submitted!</h3>
      <p style="color:#6b7280;font-size:14px;margin-bottom:20px;">Your reservation has been submitted as <strong>Pending</strong>. The resort will review and confirm your booking. Please check your email for updates.</p>
      <button class="auth-submit-btn" onclick="closeSuccessModal()">Got it</button>
    </div>
  </div>
</div>

<script>
// Check for success/error from booking processor
<?php if (isset($_GET['booking_success'])): ?>
window.__bookingSuccess = true;
<?php elseif (isset($_GET['booking_error'])): ?>
window.__bookingError = <?php echo json_encode($_GET['booking_error']); ?>;
<?php endif; ?>

// Login state
window.__isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
</script>
<script src="../assets/js/public_booking.js"></script>
</body>
</html>