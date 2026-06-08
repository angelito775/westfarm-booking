<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']) && $_SESSION['user_type_id'] == 2;
if ($is_logged_in) {
    require_once '../config/db_connection.php';
}
require_once '../config/crayfish_settings.php';
$cs = getCrayfishSettings();
$price_per_kg = $cs['price_per_kg'];
$min_order_kg = $cs['min_order_kg'];
$max_order_kg = $cs['max_order_kg'];
$product_name = $cs['product_name'];
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
  <link rel="stylesheet" href="../assets/css/public_nav.css">
</head>
<body>

<!-- ═══════════════ NAV ═══════════════ -->
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
        <a href="../public/westcrays.php" class="active-sub">WEST CRAY ORDERING</a>
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
    <?php if ($is_logged_in): ?>
      <li><a href="../customer/profile.php">MY PROFILE</a></li>
      <li><a href="../logic/logout_customer.php" class="nav-book-btn" style="background:transparent;border:1px solid rgba(255,255,255,0.4);">
        <i class="fas fa-sign-out-alt"></i> SIGN OUT
      </a></li>
    <?php else: ?>
      <li><a href="#" class="nav-book-btn" onclick="openSignInModal(); return false;"><i class="fas fa-sign-in-alt"></i> SIGN IN</a></li>
      <li><a href="../public/booking.php" class="nav-book-btn" style="margin-left:6px;">BOOK NOW</a></li>
    <?php endif; ?>
  </ul>
</nav>

<!-- ═══════════════ HERO ═══════════════ -->
<div class="wc-hero">
  <div class="wc-hero-bg">
    <img src="../assets/images/westcrays1.jpg" alt="Fresh Crayfish" onerror="this.style.display='none';">
    <div class="wc-hero-overlay"></div>
  </div>
  <div class="wc-hero-content">
    <div class="wc-hero-tagline">West Farm Amenities</div>
    <h1>West Cray<br><span>Ordering</span></h1>
    <p class="wc-hero-desc">Fresh, handpicked crayfish from our organic farm ponds — ordered by the kilogram, ready for pickup.</p>
    <div class="wc-hero-badges">
      <div class="wc-hero-badge"><i class="fas fa-water"></i> Farm-Raised</div>
      <div class="wc-hero-badge"><i class="fas fa-leaf"></i> Organic</div>
      <div class="wc-hero-badge"><i class="fas fa-weight-hanging"></i> Per Kilo</div>
    </div>
  </div>
</div>

<!-- ═══════════════ MAIN ═══════════════ -->
<div class="wc-page">

  <!-- ═══ Steps ═══ -->
  <div class="wc-steps-bar">
    <div class="wc-step active">
      <div class="wc-step-num"><i class="fas fa-weight-hanging"></i></div>
      <span>Select weight</span>
    </div>
    <div class="wc-step-line"></div>
    <div class="wc-step">
      <div class="wc-step-num"><i class="fas fa-calendar-alt"></i></div>
      <span>Pickup date</span>
    </div>
    <div class="wc-step-line"></div>
    <div class="wc-step">
      <div class="wc-step-num"><i class="fas fa-check-circle"></i></div>
      <span>Confirm</span>
    </div>
  </div>

  <!-- ═══ Product + Order ═══ -->
  <section class="wc-product-section">
    <div class="wc-product-visual">
      <img src="../assets/images/westcrays1.jpg" alt="Fresh Live Crayfish" onerror="this.style.display='none'; this.parentElement.classList.add('wc-product-visual-fallback');">
      <div class="wc-product-shadow"></div>
    </div>
    <div class="wc-product-info">
      <div class="wc-product-tag"><i class="fas fa-star"></i> Customer Favorite</div>
      <h2><?php echo htmlspecialchars($product_name); ?></h2>
      <p class="wc-product-desc">Sourced daily from our on-site crayfish ponds. Every order is handpicked, purged in clean water, and packed live — ensuring maximum freshness for your cooking.</p>
      <div class="wc-product-specs">
        <div class="wc-spec"><i class="fas fa-weight-hanging"></i> Sold per kilogram</div>
        <div class="wc-spec"><i class="fas fa-clock"></i> Same-day harvest</div>
        <div class="wc-spec"><i class="fas fa-temperature-low"></i> Live &amp; packed cold</div>
      </div>
      <div class="wc-price-block">
        <div class="wc-price-main">₱<?php echo number_format($price_per_kg, 0); ?> <span>per kg</span></div>
        <div class="wc-price-sub">Min <?php echo number_format($min_order_kg, 1); ?> kg &middot; Max <?php echo number_format($max_order_kg, 0); ?> kg</div>
      </div>
    </div>
  </section>

  <!-- ═══ Weight + Cart ═══ -->
  <section class="wc-order-section">
    <div class="wc-grid2">
      <div class="wc-card">
        <div class="wc-card-head"><h3><i class="fas fa-weight-hanging"></i> Select Weight</h3></div>
        <div class="wc-card-body">
          <div class="wc-weight-presets">
            <button type="button" class="wc-preset-btn" onclick="setWeight(0.5)">0.5 kg<span>₱60</span></button>
            <button type="button" class="wc-preset-btn" onclick="setWeight(1)">1 kg<span>₱120</span></button>
            <button type="button" class="wc-preset-btn" onclick="setWeight(2)">2 kg<span>₱240</span></button>
            <button type="button" class="wc-preset-btn" onclick="setWeight(3)">3 kg<span>₱360</span></button>
            <button type="button" class="wc-preset-btn" onclick="setWeight(5)">5 kg<span>₱600</span></button>
          </div>
          <div class="wc-weight-custom">
            <label>Or enter custom weight</label>
            <div class="wc-weight-input-row">
              <button class="wc-weight-minus" onclick="adjustWeight(-0.5)">−</button>
              <input type="number" id="wcWeightInput" min="0.5" max="100" step="0.5" value="1" placeholder="—" oninput="onWeightInput()">
              <span class="wc-weight-unit">kg</span>
              <button class="wc-weight-plus" onclick="adjustWeight(0.5)">+</button>
            </div>
          </div>
          <div class="wc-live-estimate" id="wcLiveEstimate" style="display:none;">
            <div class="wc-estimate-label">Estimated total</div>
            <div class="wc-estimate-amt" id="wcEstimateAmt">₱120</div>
          </div>
          <button class="wc-add-btn" id="wcAddBtn" onclick="addToCart()" disabled>
            <i class="fas fa-cart-plus"></i> Add to Order
          </button>
        </div>
      </div>

      <div class="wc-card wc-cart-card">
        <div class="wc-card-head">
          <h3><i class="fas fa-receipt"></i> Your Order</h3>
          <span class="wc-cart-badge" id="wcCartBadge" style="display:none;"><span id="wcCartKg">0</span> kg</span>
        </div>
        <div class="wc-card-body">
          <div class="wc-cart-empty" id="wcCartEmpty">
            <div class="wc-cart-empty-icon"><i class="fas fa-shopping-basket"></i></div>
            <span>No items yet — select a weight to start.</span>
          </div>
          <div id="wcCartItems"></div>
          <div class="wc-cart-summary" id="wcCartSummary" style="display:none;">
            <div class="wc-summary-row"><span>Subtotal</span><span id="wcSubtotal">₱0</span></div>
            <div class="wc-summary-divider"></div>
            <div class="wc-summary-row wc-summary-total"><span>Total</span><span id="wcTotalAmt">₱0</span></div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ═══ Pickup Date ═══ -->
  <section class="wc-pickup-section">
    <div class="wc-card">
      <div class="wc-card-head"><h3><i class="fas fa-calendar-alt"></i> Pickup Date &amp; Time</h3></div>
      <div class="wc-card-body">
        <p class="wc-pickup-intro">When would you like to pick up your crayfish? We'll have them harvested, purged, and packed live for you.</p>
        <div class="wc-pickup-grid">
          <div class="wc-field">
            <label><i class="fas fa-calendar"></i> Preferred pickup date <span class="wc-req">*</span></label>
            <input type="date" id="wcPickupDate" min="<?php echo date('Y-m-d'); ?>">
          </div>
          <div class="wc-field">
            <label><i class="fas fa-clock"></i> Preferred time <span class="wc-req">*</span></label>
            <select id="wcPickupTime">
              <option value="">Select time window</option>
              <option>Morning (7AM – 11AM)</option>
              <option>Noon (11AM – 2PM)</option>
              <option>Afternoon (2PM – 6PM)</option>
              <option>Evening (6PM – 9PM)</option>
            </select>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ═══ Success ═══ -->
  <div class="wc-success" id="wcSuccess">
    <div class="wc-success-ring"><i class="fas fa-check"></i></div>
    <h3>Order Placed Successfully!</h3>
    <p id="wcSuccessMsg">We'll prepare your crayfish shortly.</p>
  </div>

  <!-- ═══ Place Order ═══ -->
  <section class="wc-place-section">
    <button class="wc-place-btn" id="wcPlaceBtn" disabled onclick="placeOrder()">
      <i class="fas fa-paper-plane"></i> Place Order
    </button>
    <div class="wc-place-hint" id="wcPlaceHint"><i class="fas fa-info-circle"></i> Add at least 0.5 kg and select a pickup date to enable ordering.</div>
  </section>

</div>

<!-- ═══ Edit Modal ═══ -->
<div class="wc-modal-bg" id="wcEditBg" onclick="closeEditModal()"></div>
<div class="wc-modal" id="wcEditModal">
  <div class="wc-modal-top">
    <h4>Edit Order</h4>
    <button class="wc-modal-x" onclick="closeEditModal()">✕</button>
  </div>
  <div class="wc-modal-body">
    <div class="wc-edit-product">
      <div class="wc-edit-thumb"><i class="fas fa-fish"></i></div>
      <div>
        <div class="wc-edit-name">Fresh Crayfish</div>
        <div class="wc-edit-price">₱120 per kg</div>
      </div>
    </div>
    <div class="wc-edit-qty-row">
      <label>Weight</label>
      <div class="wc-edit-qty-ctrl">
        <button class="wc-edit-qty-btn" onclick="adjustEditQty(-0.5)">−</button>
        <span class="wc-edit-qty-val" id="wcEditQtyVal">1 kg</span>
        <button class="wc-edit-qty-btn" onclick="adjustEditQty(0.5)">+</button>
      </div>
    </div>
    <div class="wc-edit-sub"><span>Subtotal: <strong id="wcEditSubtotal">₱120</strong></span></div>
  </div>
  <div class="wc-modal-actions">
    <button class="wc-btn-ghost" onclick="closeEditModal()">Cancel</button>
    <button class="wc-btn-primary" onclick="saveEdit()">Save Changes</button>
  </div>
</div>

<!-- ═══ Delete Modal ═══ -->
<div class="wc-modal-bg" id="wcDeleteBg" onclick="closeDeleteModal()"></div>
<div class="wc-modal" id="wcDeleteModal">
  <div class="wc-modal-icon wc-modal-icon-danger"><i class="fas fa-trash-alt"></i></div>
  <h4>Remove Item?</h4>
  <p>Are you sure you want to remove <strong id="wcDeleteName"></strong> from your order?</p>
  <div class="wc-modal-actions">
    <button class="wc-btn-ghost" onclick="closeDeleteModal()">Cancel</button>
    <button class="wc-btn-danger" onclick="confirmDelete()">Yes, Remove</button>
  </div>
</div>

<!-- ═══ Sign In Modal ═══ -->
<div class="wc-modal-bg" id="wcSignInBg" onclick="closeSignInModal()"></div>
<div class="wc-modal" id="wcSignInModal" style="max-width:400px;">
  <div class="wc-modal-top">
    <h4><i class="fas fa-sign-in-alt"></i> Sign In</h4>
    <button class="wc-modal-x" onclick="closeSignInModal()">✕</button>
  </div>
  <div class="wc-modal-body" style="padding:24px 20px 10px;">
    <p style="font-size:13px;color:var(--text-soft);margin-bottom:18px;text-align:center;">Sign in to link your order to your account.</p>
    <div class="wc-signin-field" style="margin-bottom:14px;">
      <label style="font-family:'Josefin Sans',sans-serif;font-size:10px;letter-spacing:1.5px;text-transform:uppercase;color:var(--text-soft);font-weight:600;display:block;margin-bottom:6px;">Email</label>
      <input type="email" id="wcSignInEmail" placeholder="you@example.com" style="width:100%;padding:11px 14px;border-radius:10px;border:1.5px solid var(--border);background:var(--cream-warm);font-family:'Lora',serif;font-size:14px;color:var(--text);outline:none;transition:border-color .2s,box-shadow .2s;">
    </div>
    <div class="wc-signin-field" style="margin-bottom:6px;">
      <label style="font-family:'Josefin Sans',sans-serif;font-size:10px;letter-spacing:1.5px;text-transform:uppercase;color:var(--text-soft);font-weight:600;display:block;margin-bottom:6px;">Password</label>
      <input type="password" id="wcSignInPass" placeholder="••••••••" style="width:100%;padding:11px 14px;border-radius:10px;border:1.5px solid var(--border);background:var(--cream-warm);font-family:'Lora',serif;font-size:14px;color:var(--text);outline:none;transition:border-color .2s,box-shadow .2s;">
    </div>
    <p style="font-size:11px;color:#bbb;text-align:right;margin-bottom:16px;margin-top:4px;"><a href="#" style="color:var(--forest);text-decoration:none;">Forgot password?</a></p>
    <button onclick="submitSignIn()" style="width:100%;padding:13px;background:linear-gradient(135deg,var(--forest),var(--forest-mid));color:#fff;border:none;border-radius:50px;font-family:'Josefin Sans',sans-serif;font-size:13px;font-weight:700;letter-spacing:2px;text-transform:uppercase;cursor:pointer;transition:all .25s;display:flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 4px 16px rgba(26,58,26,0.2);">
      <i class="fas fa-sign-in-alt"></i> Sign In
    </button>
    <p style="font-size:12px;color:#bbb;text-align:center;margin-top:14px;">Don't have an account? <a href="../pages/register.php" style="color:var(--forest);font-weight:600;text-decoration:none;">Create Account</a></p>
  </div>
</div>

<!-- ═══════════════ FOOTER ═══════════════ -->
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

<script>
window.__isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
window.__crayfishPrice = <?php echo json_encode($price_per_kg); ?>;
window.__crayfishMin = <?php echo json_encode($min_order_kg); ?>;
window.__crayfishMax = <?php echo json_encode($max_order_kg); ?>;
</script>
<script src="../assets/js/public_westcrays.js"></script>
<script src="../assets/js/public_nav.js"></script>
</body>
</html>
