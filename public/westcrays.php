<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']) && $_SESSION['user_type_id'] == 2;
$customer_name = '';
$customer_phone = '';
if ($is_logged_in) {
    require_once '../config/db_connection.php';
    $stmt = $pdo->prepare("SELECT first_name, last_name, phone_number FROM user_profiles WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $profile = $stmt->fetch();
    if ($profile) {
        $customer_name = $profile['first_name'] . ' ' . $profile['last_name'];
        $customer_phone = $profile['phone_number'] ?? '';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>West Cray Ordering | West Farm Resort and Hotel</title>
  <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;600;700&family=Josefin+Sans:wght@300;400;600;700&family=Lora:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/public_westcrays.css">
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
      <a href="#" class="nav-btn active">AMENITIES</a>
      <div class="dropdown-menu">
        <a href="../public/westpool.php">WEST POOL</a>
        <a href="../public/westcrays.php" class="active">WEST CRAY ORDERING</a>
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
    <li><a href="../public/faqs.php">FAQs</a></li>
    <li><a href="../public/contact.php">CONTACT</a></li>
    <li><a href="../public/booking.php" class="nav-book-btn">BOOK NOW</a></li>
  </ul>
</nav>

  <div class="hero">
    <img class="hero-img" src="../assets/images/westcrays1.jpg" alt="Crayfish underwater"
      onerror="this.style.display='none'; this.parentElement.style.background='#0d2b0d';" />
    <div class="hero-overlay">
      <div class="hero-breadcrumb">Amenities &rsaquo; West Cray Ordering</div>
      <h1>West <span>Cray</span><br>Ordering</h1>
      <p>Fresh crayfish — ₱120 per kilogram, delivered to your door</p>
      <div class="hero-badges">
        <span class="badge"><i class="fas fa-check-circle"></i> Live arrival guarantee</span>
        <span class="badge"><i class="fas fa-leaf"></i> Handpicked &amp; healthy</span>
        <span class="badge"><i class="fas fa-weight-hanging"></i> Sold per kilo</span>
      </div>
    </div>
  </div>

  <div class="page">

    <!-- Logged-in user banner -->
    <?php if ($is_logged_in): ?>
    <div class="user-banner">
      <div class="user-banner-info">
        <div class="user-banner-avatar"><i class="fas fa-user"></i></div>
        <div>
          <div class="user-banner-name">Ordering as <strong><?php echo htmlspecialchars($customer_name); ?></strong></div>
          <div class="user-banner-meta"><?php echo $customer_phone ? htmlspecialchars($customer_phone) : 'No phone on file'; ?></div>
        </div>
      </div>
      <a href="../logic/logout_customer.php" class="user-banner-logout">Sign out</a>
    </div>
    <?php endif; ?>

    <!-- Product -->
    <section class="products-section">
      <div class="section-header">
        <div class="section-label">Our crayfish</div>
        <div class="section-sub">Fresh from our farm — sold per kilogram</div>
      </div>
      <div class="product-single" id="productArea"></div>
    </section>

    <!-- Cart -->
    <section class="cart-section">
      <div class="section-header">
        <div class="section-label">Your order</div>
        <div class="cart-count-badge" id="cartCountBadge" style="display:none;"><span id="cartCountNum">0</span> kg</div>
      </div>
      <div class="cart-panel">
        <div class="cart-empty" id="cartEmpty">
          <i class="fas fa-shopping-basket"></i>
          <span>No items yet — select a weight above.</span>
        </div>
        <div id="cartItems"></div>
        <div class="cart-summary" id="cartTotal" style="display:none;">
          <div class="cart-summary-row">
            <span>Subtotal</span>
            <span id="subtotalAmt">&#8369;0</span>
          </div>
          <div class="cart-total-row">
            <span class="cart-total-label">Total</span>
            <span class="cart-total-amount" id="totalAmt">&#8369;0</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Success -->
    <div class="success-box" id="successBox">
      <div class="tick"><i class="fas fa-check-circle"></i></div>
      <h3>Order placed successfully!</h3>
      <p id="successMsg">We'll deliver your crayfish to your address shortly.</p>
    </div>

    <!-- Checkout -->
    <section class="checkout-section">
      <div class="section-header">
        <div class="section-label">Guest details</div>
        <div class="section-sub">Where should we deliver your order?</div>
      </div>
      <div class="checkout-form">
        <div class="form-grid">
          <div class="form-group">
            <label><i class="fas fa-user"></i> Full name <span class="req">*</span></label>
            <input type="text" id="guestName" value="<?php echo htmlspecialchars($customer_name); ?>" placeholder="Your name" />
          </div>
          <div class="form-group">
            <label><i class="fas fa-phone"></i> Contact number <span class="req">*</span></label>
            <input type="text" id="guestPhone" value="<?php echo htmlspecialchars($customer_phone); ?>" placeholder="+63 9XX XXX XXXX" />
          </div>
          <div class="form-group full">
            <label><i class="fas fa-map-marker-alt"></i> Delivery address <span class="req">*</span></label>
            <textarea id="guestAddress" rows="2" placeholder="Enter your complete delivery address..."></textarea>
            <div class="form-hint">Can be inside the resort (e.g., Room 204, Mango Cottage) or an outside address.</div>
          </div>
          <div class="form-group">
            <label><i class="fas fa-clock"></i> Preferred delivery time <span class="req">*</span></label>
            <select id="guestTime">
              <option value="">Select time</option>
              <option>As soon as possible</option>
              <option>Morning (7AM – 11AM)</option>
              <option>Noon (11AM – 2PM)</option>
              <option>Afternoon (2PM – 6PM)</option>
              <option>Evening (6PM – 9PM)</option>
            </select>
          </div>
          <div class="form-group">
            <label><i class="fas fa-utensils"></i> Preparation preference</label>
            <select id="guestPrep">
              <option value="">No preference</option>
              <option>Live (for cooking yourself)</option>
              <option>Cleaned &amp; prepped</option>
              <option>Cooked — garlic butter</option>
              <option>Cooked — spicy</option>
              <option>Cooked — coconut milk (ginataan)</option>
            </select>
          </div>
          <div class="form-group full">
            <label><i class="fas fa-sticky-note"></i> Special notes</label>
            <textarea id="guestNotes" placeholder="Any special requests for your crayfish order..."></textarea>
          </div>
        </div>
        <button class="checkout-btn" id="checkoutBtn" disabled onclick="placeOrder()">
          <i class="fas fa-paper-plane"></i> Place Order
        </button>
      </div>
    </section>

  </div>

  <!-- DELETE CONFIRM MODAL -->
  <div class="modal-backdrop" id="deleteBackdrop" onclick="closeDeleteModal()"></div>
  <div class="delete-modal" id="deleteModal">
    <div class="delete-modal-icon">🗑️</div>
    <div class="delete-modal-title">Remove Item?</div>
    <div class="delete-modal-msg">Are you sure you want to remove <strong id="deleteItemName"></strong> from your order?</div>
    <div class="delete-modal-footer">
      <button class="edit-cancel-btn" onclick="closeDeleteModal()">Cancel</button>
      <button class="delete-confirm-btn" onclick="confirmDelete()">Yes, Remove</button>
    </div>
  </div>

  <!-- EDIT MODAL -->
  <div class="modal-backdrop" id="modalBackdrop" onclick="closeModal()"></div>
  <div class="edit-modal" id="editModal">
    <div class="edit-modal-header">
      <div class="edit-modal-title">Edit Order Item</div>
      <button class="edit-modal-close" onclick="closeModal()">✕</button>
    </div>
    <div class="edit-modal-body">
      <div class="edit-product-info">
        <div class="edit-thumb" id="editThumb">🦞</div>
        <div>
          <div class="edit-product-name" id="editProductName"></div>
          <div class="edit-product-price" id="editProductPrice"></div>
        </div>
      </div>
      <div class="edit-qty-section">
        <label class="edit-qty-label">Weight (kg)</label>
        <div class="edit-qty-ctrl">
          <button class="edit-qty-btn" onclick="adjustEditQty(-0.5)">−</button>
          <span class="edit-qty-num" id="editQtyNum">1</span>
          <button class="edit-qty-btn" onclick="adjustEditQty(0.5)">+</button>
        </div>
      </div>
      <div class="edit-subtotal">
        Subtotal: <span id="editSubtotal">₱0</span>
      </div>
    </div>
    <div class="edit-modal-delete-row">
      <button class="edit-delete-item-btn" onclick="openDeleteFromEdit()">🗑️ Remove this item</button>
    </div>
    <div class="edit-modal-footer">
      <button class="edit-cancel-btn" onclick="closeModal()">Cancel</button>
      <button class="edit-save-btn" onclick="saveEdit()">Save Changes</button>
    </div>
  </div>

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

  <script>
  window.__isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
  </script>
  <script src="../assets/js/public_westcrays.js"></script>
</body>
</html>
