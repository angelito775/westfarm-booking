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

  <div class="hero">
    <img class="hero-img" src="../assets/images/westcrays1.jpg" alt="Crayfish underwater"
      onerror="this.style.display='none'; this.parentElement.style.background='#0d2b0d';" />
    <div class="hero-overlay">
      <div class="hero-breadcrumb">Amenities &rsaquo; West Cray Ordering</div>
      <h1>West <span>Cray</span><br>Ordering</h1>
      <p>Fresh crayfish — delivered to your room or cottage</p>
      <div class="hero-badges">
        <span class="badge">Live arrival guarantee</span>
        <span class="badge">Handpicked &amp; healthy</span>
        <span class="badge">Resort exclusive</span>
      </div>
    </div>
  </div>

  <div class="page">

    <!-- Products -->
    <div style="margin-bottom:2.5rem;">
      <div class="section-label">Choose your crayfish</div>
      <div class="product-grid" id="productGrid"></div>
    </div>

    <!-- Cart -->
    <div style="margin-bottom:2rem;">
      <div class="section-label">Your order</div>
      <div class="cart-panel">
        <div class="cart-empty" id="cartEmpty">No items yet — add some crayfish above.</div>
        <div id="cartItems"></div>
        <div class="cart-total" id="cartTotal" style="display:none;">
          <span class="cart-total-label">Total</span>
          <span class="cart-total-amount" id="totalAmt">&#8369;0</span>
        </div>
      </div>
    </div>

    <!-- Success -->
    <div class="success-box" id="successBox">
      <div class="tick">🦞</div>
      <h3>Order placed successfully!</h3>
      <p id="successMsg">We'll deliver your crayfish to your room shortly.</p>
    </div>

    <!-- Checkout -->
    <div>
      <div class="section-label">Guest details</div>
      <div class="checkout-form">
        <div class="form-grid">
          <div class="form-group">
            <label>Full name</label>
            <input type="text" id="guestName" placeholder="Your name" />
          </div>
          <div class="form-group">
            <label>Contact number</label>
            <input type="text" id="guestPhone" placeholder="+63 9XX XXX XXXX" />
          </div>
          <div class="form-group">
            <label>Preferred delivery time</label>
            <select id="guestTime">
              <option value="">Select time</option>
              <option>As soon as possible</option>
              <option>Morning (7AM – 11AM)</option>
              <option>Noon (11AM – 2PM)</option>
              <option>Afternoon (2PM – 6PM)</option>
              <option>Evening (6PM – 9PM)</option>
            </select>
          </div>
          <div class="form-group full">
            <label>Special notes</label>
            <textarea id="guestNotes" placeholder="Any special requests for your crayfish order..."></textarea>
          </div>
        </div>
        <button class="checkout-btn" id="checkoutBtn" disabled onclick="placeOrder()">Place Order</button>
      </div>
    </div>

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
        <label class="edit-qty-label">Quantity</label>
        <div class="edit-qty-ctrl">
          <button class="edit-qty-btn" onclick="adjustEditQty(-1)">−</button>
          <span class="edit-qty-num" id="editQtyNum">1</span>
          <button class="edit-qty-btn" onclick="adjustEditQty(1)">+</button>
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

  <script src="../assets/js/westcrays.js"></script>
</body>
</html>