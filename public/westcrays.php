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

  <!-- ═══ Payment ═══ -->
  <section class="wc-pickup-section" id="wcPaymentSection">
    <div class="wc-card">
      <div class="wc-card-head"><h3><i class="fas fa-wallet"></i> Payment</h3></div>
      <div class="wc-card-body">
        <p class="wc-pickup-intro">Choose how you'd like to pay. You can pay now (full or partial) or pay later when picking up.</p>

        <div class="wc-field" style="margin-bottom:16px;">
          <label><i class="fas fa-check-circle"></i> Pay now?</label>
          <div class="wc-pay-toggle" style="display:flex;gap:10px;margin-top:8px;">
            <button type="button" id="wcPayNowBtn" class="wc-pay-toggle-btn" onclick="togglePayMode(true)" style="flex:1;padding:10px;border-radius:10px;border:2px solid var(--border);background:#fff;font-family:'Josefin Sans',sans-serif;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;cursor:pointer;transition:all .2s;">
              <i class="fas fa-credit-card"></i> Pay Now
            </button>
            <button type="button" id="wcPayLaterBtn" class="wc-pay-toggle-btn active" onclick="togglePayMode(false)" style="flex:1;padding:10px;border-radius:10px;border:2px solid var(--forest);background:var(--forest);color:#fff;font-family:'Josefin Sans',sans-serif;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;cursor:pointer;transition:all .2s;">
              <i class="fas fa-hand-holding-usd"></i> Pay Later
            </button>
          </div>
        </div>

        <div id="wcPayNowFields" style="display:none;">
          <div class="wc-pickup-grid">
            <div class="wc-field">
              <label><i class="fas fa-university"></i> Payment Method <span class="wc-req">*</span></label>
              <select id="wcPaymentMethod">
                <option value="">Select method</option>
                <?php
                $pm_stmt = $pdo->query("SELECT method_name, description FROM payment_methods WHERE is_active = 1 ORDER BY payment_method_id");
                foreach ($pm_stmt->fetchAll() as $pm): ?>
                  <option value="<?php echo htmlspecialchars($pm['method_name']); ?>">
                    <?php echo htmlspecialchars($pm['method_name']); ?>
                    <?php if ($pm['description']) echo ' — ' . htmlspecialchars($pm['description']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="wc-field">
              <label><i class="fas fa-coins"></i> Amount to Pay <span class="wc-req">*</span></label>
              <div style="display:flex;align-items:center;gap:8px;">
                <input type="number" id="wcPayAmount" min="0" step="1" placeholder="Enter amount" style="flex:1;">
                <button type="button" onclick="setFullPayment()" style="padding:8px 14px;border-radius:8px;border:1.5px solid var(--forest);background:transparent;color:var(--forest);font-family:'Josefin Sans',sans-serif;font-size:10px;font-weight:700;letter-spacing:1px;text-transform:uppercase;cursor:pointer;white-space:nowrap;">Pay Full</button>
              </div>
              <div id="wcPayAmountHint" style="font-size:11px;color:var(--text-soft);margin-top:4px;"></div>
            </div>
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
    <div id="wcSuccessActions" style="margin-top:16px;"></div>
  </div>

  <!-- ═══ Place Order ═══ -->
  <section class="wc-place-section">
    <button class="wc-place-btn" id="wcPlaceBtn" disabled onclick="placeOrder()">
      <i class="fas fa-paper-plane"></i> Place Order
    </button>
    <div class="wc-place-hint" id="wcPlaceHint"><i class="fas fa-info-circle"></i> Add at least 0.5 kg and select a pickup date to enable ordering.</div>
  </section>

  <!-- ═══ My Orders (logged-in customers) ═══ -->
  <?php if ($is_logged_in):
    // Check if payment columns exist (migration may not have been run yet)
    $has_payment_cols = false;
    try {
        $pdo->query("SELECT amount_paid FROM crayfish_orders LIMIT 1");
        $has_payment_cols = true;
    } catch (PDOException $e) {
        $has_payment_cols = false;
    }

    if ($has_payment_cols) {
        $stmt_my = $pdo->prepare("
            SELECT co.order_id, co.quantity_kg, co.price_per_kg, co.total_amount,
                   co.amount_paid, co.pickup_date, co.ordered_at,
                   os.status_name AS order_status,
                   ps.status_name AS payment_status,
                   pm.method_name AS payment_method
            FROM crayfish_orders co
            JOIN order_statuses os ON co.status_id = os.status_id
            JOIN payment_statuses ps ON co.payment_status_id = ps.payment_status_id
            LEFT JOIN payment_methods pm ON co.payment_method_id = pm.payment_method_id
            WHERE co.customer_id = ?
            ORDER BY co.ordered_at DESC
            LIMIT 20
        ");
    } else {
        $stmt_my = $pdo->prepare("
            SELECT co.order_id, co.quantity_kg, co.price_per_kg, co.total_amount,
                   0 AS amount_paid, co.pickup_date, co.ordered_at,
                   os.status_name AS order_status,
                   'Unpaid' AS payment_status,
                   NULL AS payment_method
            FROM crayfish_orders co
            JOIN order_statuses os ON co.status_id = os.status_id
            WHERE co.customer_id = ?
            ORDER BY co.ordered_at DESC
            LIMIT 20
        ");
    }
    $stmt_my->execute([$_SESSION['user_id']]);
    $my_orders = $stmt_my->fetchAll();
  ?>
  <section class="wc-orders-section" style="margin-top:40px;">
    <div class="wc-card">
      <div class="wc-card-head"><h3><i class="fas fa-clipboard-list"></i> My Crayfish Orders</h3></div>
      <div class="wc-card-body" style="padding:0;">
        <?php if (empty($my_orders)): ?>
          <div style="text-align:center;padding:2.5rem 1rem;color:#ccc;">
            <i class="fas fa-inbox" style="font-size:36px;margin-bottom:10px;color:#e0e0e0;"></i>
            <p style="font-size:13px;">No crayfish orders yet.</p>
          </div>
        <?php else: ?>
          <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;">
              <thead>
                <tr style="border-bottom:2px solid var(--border);">
                  <th style="padding:12px 14px;text-align:left;font-family:'Josefin Sans',sans-serif;font-size:9px;letter-spacing:1.5px;text-transform:uppercase;color:#999;">Order</th>
                  <th style="padding:12px 14px;text-align:left;font-family:'Josefin Sans',sans-serif;font-size:9px;letter-spacing:1.5px;text-transform:uppercase;color:#999;">Weight</th>
                  <th style="padding:12px 14px;text-align:left;font-family:'Josefin Sans',sans-serif;font-size:9px;letter-spacing:1.5px;text-transform:uppercase;color:#999;">Total</th>
                  <th style="padding:12px 14px;text-align:left;font-family:'Josefin Sans',sans-serif;font-size:9px;letter-spacing:1.5px;text-transform:uppercase;color:#999;">Status</th>
                  <th style="padding:12px 14px;text-align:left;font-family:'Josefin Sans',sans-serif;font-size:9px;letter-spacing:1.5px;text-transform:uppercase;color:#999;">Payment</th>
                  <th style="padding:12px 14px;text-align:right;font-family:'Josefin Sans',sans-serif;font-size:9px;letter-spacing:1.5px;text-transform:uppercase;color:#999;">Actions</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($my_orders as $mo):
                $mo_remaining = $mo['total_amount'] - $mo['amount_paid'];
                $badge_map = ['Pending Order' => '#fef3c7,#92400e', 'Harvesting & Purging' => '#ede9fe,#5b21b6', 'Live & Packed' => '#dbeafe,#1e40af', 'Completed' => '#dcfce7,#166534', 'Cancelled' => '#fee2e2,#991b1b'];
                $badge_parts = explode(',', ($badge_map[$mo['order_status']] ?? '#f3f4f4,#374151'));
              ?>
                <tr style="border-bottom:1px solid #f5f5f5;">
                  <td style="padding:14px;">
                    <strong style="font-family:'Josefin Sans',sans-serif;color:#2F3D2E;">#<?php echo $mo['order_id']; ?></strong><br>
                    <span style="font-size:11px;color:#999;"><?php echo date('M d, Y', strtotime($mo['ordered_at'])); ?></span>
                  </td>
                  <td style="padding:14px;font-size:13px;"><strong><?php echo number_format($mo['quantity_kg'], 1); ?> kg</strong></td>
                  <td style="padding:14px;font-size:13px;">
                    <strong>₱<?php echo number_format($mo['total_amount'], 0); ?></strong>
                    <?php if ($mo['amount_paid'] > 0): ?><br><span style="font-size:11px;color:var(--green);">Paid: ₱<?php echo number_format($mo['amount_paid'], 0); ?></span><?php endif; ?>
                    <?php if ($mo_remaining > 0 && $mo['order_status'] !== 'Cancelled'): ?><br><span style="font-size:11px;color:var(--red);">Bal: ₱<?php echo number_format($mo_remaining, 0); ?></span><?php endif; ?>
                  </td>
                  <td style="padding:14px;"><span style="display:inline-block;padding:3px 10px;border-radius:20px;font-family:'Josefin Sans',sans-serif;font-size:9px;font-weight:700;letter-spacing:1px;text-transform:uppercase;background:<?php echo $badge_parts[0]; ?>;color:<?php echo $badge_parts[1]; ?>;"><?php echo htmlspecialchars($mo['order_status']); ?></span></td>
                  <td style="padding:14px;">
                    <span style="font-size:12px;font-weight:600;color:<?php echo $mo['payment_status'] === 'Paid' ? 'var(--green)' : ($mo['payment_status'] === 'Partial' ? '#d97706' : 'var(--red)'); ?>;"><?php echo htmlspecialchars($mo['payment_status']); ?></span>
                    <?php if ($mo['payment_method']): ?><br><span style="font-size:11px;color:#999;"><?php echo htmlspecialchars($mo['payment_method']); ?></span><?php endif; ?>
                  </td>
                  <td style="padding:14px;text-align:right;white-space:nowrap;">
                    <a href="../pages/crayfish_receipt.php?order_id=<?php echo $mo['order_id']; ?>" style="display:inline-flex;align-items:center;gap:5px;padding:6px 14px;border-radius:50px;border:1px solid var(--border);background:transparent;font-family:'Josefin Sans',sans-serif;font-size:10px;font-weight:600;letter-spacing:1px;text-transform:uppercase;cursor:pointer;color:var(--text-soft);text-decoration:none;transition:all .2s;margin-bottom:4px;">
                      <i class="fas fa-receipt"></i> Receipt
                    </a>
                    <?php if ($mo['order_status'] === 'Pending Order' || $mo['order_status'] === 'Harvesting & Purging'): ?>
                      <?php if ($mo_remaining > 0): ?>
                        <br>
                        <button type="button" onclick="openPayModal(<?php echo $mo['order_id']; ?>, <?php echo $mo_remaining; ?>)" style="display:inline-flex;align-items:center;gap:5px;padding:6px 14px;border-radius:50px;border:none;background:var(--forest);color:#fff;font-family:'Josefin Sans',sans-serif;font-size:10px;font-weight:700;letter-spacing:1px;text-transform:uppercase;cursor:pointer;margin-bottom:4px;">
                          <i class="fas fa-credit-card"></i> Pay
                        </button>
                      <?php endif; ?>
                      <br>
                      <button type="button" onclick="cancelOrder(<?php echo $mo['order_id']; ?>)" style="display:inline-flex;align-items:center;gap:5px;padding:6px 14px;border-radius:50px;border:1px solid #fecaca;background:#fef2f2;font-family:'Josefin Sans',sans-serif;font-size:10px;font-weight:600;letter-spacing:1px;text-transform:uppercase;cursor:pointer;color:var(--red);">
                        <i class="fas fa-ban"></i> Cancel
                      </button>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

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

<!-- ═══ Pay Modal (for existing orders) ═══ -->
<div id="payModalBg" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; padding:20px;" onclick="if(event.target===this) closePayModal();">
  <div style="background:#fff; border-radius:10px; width:100%; max-width:440px; box-shadow:0 10px 40px rgba(0,0,0,0.15);">
    <div style="display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid #eee;">
      <h3 style="margin:0; font-family:'Josefin Sans',sans-serif; font-size:16px; font-weight:700; color:#1a3a1a;"><i class="fas fa-credit-card"></i> Make Payment</h3>
      <button type="button" onclick="closePayModal()" style="background:none; border:none; font-size:22px; color:#999; cursor:pointer; line-height:1; padding:0;">&times;</button>
    </div>
    <div style="padding:20px;">
      <div style="background:#f9fafb; padding:12px; border-radius:8px; margin-bottom:16px; border:1px solid #e5e7eb;">
        <p style="margin:0 0 4px 0; font-size:13px; color:#6b7280;">Order #<strong id="payModalOrderId"></strong></p>
        <p style="margin:0; font-size:13px; color:#6b7280;">Remaining balance: <strong id="payModalRemaining" style="color:#2F3D2E;"></strong></p>
      </div>
      <div style="margin-bottom:12px;">
        <label style="display:block; margin-bottom:5px; font-weight:500;">Payment Method <span style="color:#dc2626;">*</span></label>
        <select id="payModalMethod" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; font-family:'Lora',serif; font-size:14px;">
          <option value="">Select method</option>
          <?php
          $pm_stmt2 = $pdo->query("SELECT method_name FROM payment_methods WHERE is_active = 1 ORDER BY payment_method_id");
          foreach ($pm_stmt2->fetchAll() as $pm): ?>
            <option value="<?php echo htmlspecialchars($pm['method_name']); ?>"><?php echo htmlspecialchars($pm['method_name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="margin-bottom:6px;">
        <label style="display:block; margin-bottom:5px; font-weight:500;">Amount (₱) <span style="color:#dc2626;">*</span></label>
        <input type="number" id="payModalAmount" min="1" step="1" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; font-family:'Lora',serif; font-size:14px;">
      </div>
    </div>
    <div style="display:flex; justify-content:flex-end; gap:10px; padding:14px 20px; border-top:1px solid #eee; background:#fafafa;">
      <button type="button" onclick="closePayModal()" style="background:#e5e7eb; color:#374151; border:none; border-radius:8px; padding:10px 24px; font-family:'Josefin Sans',sans-serif; font-weight:700; font-size:0.75rem; letter-spacing:1.5px; text-transform:uppercase; cursor:pointer;">Cancel</button>
      <button type="button" id="payModalSubmitBtn" onclick="submitPayModal()" style="background:#c0392b; color:#fff; border:none; border-radius:8px; padding:10px 24px; font-family:'Josefin Sans',sans-serif; font-weight:700; font-size:0.75rem; letter-spacing:1.5px; text-transform:uppercase; cursor:pointer;">
        <i class="fas fa-lock"></i> Confirm Payment
      </button>
    </div>
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
