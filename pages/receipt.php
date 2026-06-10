<?php
session_start();

// Allow both customers and admins/owners to view
$is_customer = isset($_SESSION['user_id']) && $_SESSION['user_type_id'] == 2;
$is_admin    = isset($_SESSION['user_id']) && $_SESSION['user_type_id'] == 1;
$is_owner    = isset($_SESSION['user_id']) && $_SESSION['user_type_id'] == 3;

if (!$is_customer && !$is_admin && !$is_owner) {
    header("Location: ../pages/login.php");
    exit();
}

require_once '../config/db_connection.php';

$payment_id = isset($_GET['payment_id']) ? intval($_GET['payment_id']) : 0;
if ($payment_id <= 0) {
    header("Location: ../pages/login.php");
    exit();
}

// Fetch payment with all related info
// Dynamically build the SELECT for booking_items to handle optional columns
$biColCheck = $pdo->query("SHOW COLUMNS FROM booking_items")->fetchAll(PDO::FETCH_COLUMN);
$biGuestSelect = '';
if (in_array('num_adults', $biColCheck)) $biGuestSelect .= ', bi.num_adults';
if (in_array('num_kids', $biColCheck)) $biGuestSelect .= ', bi.num_kids';
if (in_array('num_guests', $biColCheck)) $biGuestSelect .= ', bi.num_guests';
if ($biGuestSelect === '') $biGuestSelect = ', NULL AS num_adults, NULL AS num_kids, NULL AS num_guests';

$stmt = $pdo->prepare("
    SELECT p.payment_id, p.booking_id, p.amount_paid, p.transaction_id, p.payment_date,
           pm.method_name, pm.description AS method_description,
           b.total_amount, b.created_at AS booking_date,
           bs.status_name AS booking_status,
           ps.status_name AS payment_status,
           f.name AS facility_name, f.base_price, f.facility_id,
           c.name AS category_name,
           bi.check_in_date, bi.check_out_date {$biGuestSelect},
           cust.user_id AS cust_id, cust.email AS cust_email,
           up.first_name, up.last_name, up.phone_number,
           (SELECT COALESCE(SUM(amount_paid), 0) FROM payments WHERE booking_id = b.booking_id) AS total_paid
    FROM payments p
    JOIN bookings b ON p.booking_id = b.booking_id
    JOIN booking_items bi ON b.booking_id = bi.booking_id
    JOIN facilities f ON bi.facility_id = f.facility_id
    LEFT JOIN categories c ON f.category_id = c.category_id
    JOIN payment_methods pm ON p.payment_method_id = pm.payment_method_id
    JOIN booking_statuses bs ON b.booking_status_id = bs.booking_status_id
    JOIN payment_statuses ps ON b.payment_status_id = ps.payment_status_id
    JOIN users cust ON b.customer_id = cust.user_id
    LEFT JOIN user_profiles up ON cust.user_id = up.user_id
    WHERE p.payment_id = ?
");
$stmt->execute([$payment_id]);
$receipt = $stmt->fetch();

if (!$receipt) {
    echo "Receipt not found.";
    exit();
}

// Security: customers can only see their own receipts
if ($is_customer && $receipt['cust_id'] != $_SESSION['user_id']) {
    echo "Access denied.";
    exit();
}

// Generate OR number
$or_number = 'WF-OR-' . str_pad($receipt['payment_id'], 6, '0', STR_PAD_LEFT);

// Calculate amounts
$total_amount    = floatval($receipt['total_amount']);
$paid_so_far     = floatval($receipt['total_paid']);
$this_payment    = floatval($receipt['amount_paid']);
$remaining       = $total_amount - $paid_so_far;
$prev_paid       = $paid_so_far - $this_payment;

// Format helpers
$customer_name = trim(($receipt['first_name'] ?? '') . ' ' . ($receipt['last_name'] ?? ''));
if (empty($customer_name)) $customer_name = $receipt['cust_email'];

$receipt['category_name'] = $receipt['category_name'] ?? null;
$isPoolCategory = (isset($receipt['category_name']) && $receipt['category_name'] === 'Pool');
$numAdults = isset($receipt['num_adults']) ? (int)$receipt['num_adults'] : 0;
$numKids = isset($receipt['num_kids']) ? (int)$receipt['num_kids'] : 0;
// Fallback: if both are 0, use num_guests from the query or default to 1
if ($numAdults === 0 && $numKids === 0) {
    if (!empty($receipt['num_guests']) && (int)$receipt['num_guests'] > 0) {
        $numAdults = (int)$receipt['num_guests'];
    } else {
        $numAdults = 1; // Last resort: default to 1 guest
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Receipt <?php echo $or_number; ?> | West Farm Resort and Hotel</title>
  <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;600;700&family=Josefin+Sans:wght@300;400;600;700&family=Lora:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    :root {
      --forest: #1a3a1a; --gold: #c8973f; --gold-lt: #d4a373;
      --cream: #f5f2eb; --text: #2c2c2c; --text-soft: #888;
      --green: #16a34a; --red: #c0392b; --border: #e8e4dc;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Lora', serif; background: var(--cream); color: var(--text); min-height: 100vh; }

    /* ── Top bar ── */
    .receipt-topbar {
      background: var(--forest); color: #fff; padding: 14px 0;
      display: flex; justify-content: space-between; align-items: center;
    }
    .receipt-topbar-inner { max-width: 800px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; width: 100%; }
    .receipt-topbar a { color: rgba(255,255,255,0.7); text-decoration: none; font-family: 'Josefin Sans', sans-serif; font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; transition: color 0.2s; }
    .receipt-topbar a:hover { color: #fff; }
    .receipt-topbar .print-btn { background: var(--gold); color: #fff; border: none; padding: 7px 20px; border-radius: 20px; font-family: 'Josefin Sans', sans-serif; font-size: 11px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; cursor: pointer; transition: background 0.2s; }
    .receipt-topbar .print-btn:hover { background: #b07a2a; }

    /* ── Receipt paper ── */
    .receipt-paper {
      max-width: 800px; margin: 2.5rem auto; background: #fff;
      border-radius: 4px; box-shadow: 0 2px 20px rgba(0,0,0,0.08);
      overflow: hidden;
    }

    /* ── Header ── */
    .receipt-header {
      background: linear-gradient(135deg, #0f2a0f 0%, #1a3a1a 60%, #2c552c 100%);
      padding: 32px 40px 28px; color: #fff; position: relative; overflow: hidden;
    }
    .receipt-header::after { content: none; }
    .receipt-header-row { display: flex; justify-content: space-between; align-items: flex-start; }
    .receipt-brand h1 { font-family: 'Josefin Sans', sans-serif; font-size: 20px; font-weight: 700; letter-spacing: 5px; }
    .receipt-brand p { font-size: 10px; font-weight: 300; letter-spacing: 3px; text-transform: uppercase; color: rgba(255,255,255,0.5); margin-top: 2px; }
    .receipt-brand .address { font-size: 11px; color: rgba(255,255,255,0.45); margin-top: 8px; line-height: 1.6; }
    .receipt-brand .category-tag { display: inline-block; margin-top: 6px; padding: 3px 10px; border: 1px solid rgba(255,255,255,0.25); border-radius: 3px; font-family: 'Josefin Sans', sans-serif; font-size: 8px; letter-spacing: 2px; text-transform: uppercase; color: rgba(255,255,255,0.55); }
    .receipt-ident { text-align: right; }
    .receipt-ident .or-label { font-family: 'Josefin Sans', sans-serif; font-size: 10px; letter-spacing: 3px; text-transform: uppercase; color: var(--mint, #7ed87e); }
    .receipt-ident .or-number { font-family: 'Josefin Sans', sans-serif; font-size: 22px; font-weight: 700; letter-spacing: 2px; margin-top: 2px; }
    .receipt-ident .or-date { font-size: 11px; color: rgba(255,255,255,0.5); margin-top: 6px; }

    /* ── Divider ── */
    .receipt-divider { height: 3px; background: repeating-linear-gradient(90deg, var(--gold) 0px, var(--gold) 8px, transparent 8px, transparent 12px); }

    /* ── Body ── */
    .receipt-body { padding: 28px 40px; }

    .receipt-section-title { font-family: 'Josefin Sans', sans-serif; font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: var(--gold); margin-bottom: 12px; }

    /* ── Info grid ── */
    .receipt-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; }
    .receipt-info-grid .full { grid-column: 1 / -1; }
    .receipt-field label { font-family: 'Josefin Sans', sans-serif; font-size: 9px; letter-spacing: 1.5px; text-transform: uppercase; color: #aaa; display: block; margin-bottom: 3px; }
    .receipt-field .val { font-size: 14px; color: var(--text); font-weight: 500; }

    /* ── Amount table ── */
    .receipt-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
    .receipt-table th { font-family: 'Josefin Sans', sans-serif; font-size: 9px; letter-spacing: 1.5px; text-transform: uppercase; color: #888; padding: 10px 12px; border-bottom: 2px solid var(--border); text-align: left; }
    .receipt-table th:last-child { text-align: right; }
    .receipt-table td { padding: 12px; border-bottom: 1px solid #f5f5f5; font-size: 13px; }
    .receipt-table td:last-child { text-align: right; font-weight: 600; }
    .receipt-table .total-row td { font-size: 16px; font-weight: 700; color: var(--forest); border-top: 2px solid var(--forest); border-bottom: none; padding-top: 14px; }
    .receipt-table .paid-row td { color: var(--green); }
    .receipt-table .balance-row td { color: var(--red); }

    /* ── Payment details ── */
    .receipt-payment-box { background: #f8faf5; border: 1px solid #e8f0e0; border-radius: 10px; padding: 18px 20px; margin-bottom: 24px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
    .receipt-payment-box .rpb-field label { font-family: 'Josefin Sans', sans-serif; font-size: 9px; letter-spacing: 1.5px; text-transform: uppercase; color: #aaa; display: block; margin-bottom: 3px; }
    .receipt-payment-box .rpb-field .val { font-size: 14px; font-weight: 600; color: var(--text); }

    /* ── Stamp / Status ── */
    .receipt-stamp { text-align: center; margin: 28px 0 20px; }
    .receipt-stamp .stamp { display: inline-block; padding: 8px 24px; border: 2px solid; border-radius: 4px; font-family: 'Josefin Sans', sans-serif; font-size: 11px; font-weight: 700; letter-spacing: 3px; text-transform: uppercase; transform: rotate(-5deg); }
    .stamp-paid { color: var(--green); border-color: var(--green); background: rgba(22,163,74,0.06); }
    .stamp-partial { color: #d97706; border-color: #d97706; background: rgba(217,119,6,0.06); }
    .stamp-unpaid { color: var(--red); border-color: var(--red); background: rgba(192,57,43,0.06); }

    /* ── Footer ── */
    .receipt-footer { padding: 20px 40px 28px; border-top: 1px dashed var(--border); text-align: center; }
    .receipt-footer p { font-size: 11px; color: #aaa; line-height: 1.8; }
    .receipt-footer .thankyou { font-family: 'Dancing Script', cursive; font-size: 20px; color: var(--forest); margin-bottom: 6px; }

    /* ── Print styles ── */
    @media print {
      .receipt-topbar { display: none; }
      body { background: #fff; }
      .receipt-paper { box-shadow: none; margin: 0; max-width: 100%; }
      .receipt-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }

    @media (max-width: 640px) {
      .receipt-header { padding: 24px 20px; }
      .receipt-header-row { flex-direction: column; gap: 16px; }
      .receipt-ident { text-align: left; }
      .receipt-body { padding: 20px; }
      .receipt-info-grid { grid-template-columns: 1fr; }
      .receipt-payment-box { grid-template-columns: 1fr 1fr; }
      .receipt-footer { padding: 16px 20px; }
    }
  </style>
</head>
<body>

<!-- Top bar -->
<div class="receipt-topbar">
  <div class="receipt-topbar-inner">
    <div>
      <?php if ($is_customer): ?>
        <a href="../customer/payment_booking.php"><i class="fas fa-arrow-left"></i> Back to Payments</a>
      <?php elseif ($is_admin): ?>
        <a href="../admin/payments.php"><i class="fas fa-arrow-left"></i> Back to Payment Ledger</a>
      <?php elseif ($is_owner): ?>
        <a href="../owner/bookings.php"><i class="fas fa-arrow-left"></i> Back to Bookings</a>
      <?php endif; ?>
    </div>
    <div>
      <button class="print-btn" onclick="window.print()"><i class="fas fa-print"></i> Print Receipt</button>
    </div>
  </div>
</div>

<!-- Receipt -->
<div class="receipt-paper">

  <!-- Header -->
  <div class="receipt-header">
    <div class="receipt-header-row">
      <div class="receipt-brand">
        <h1>WEST FARM</h1>
        <p>Resort and Hotel</p>
        <?php if (!empty($receipt['category_name'])): ?>
        <span class="category-tag"><?php echo htmlspecialchars($receipt['category_name']); ?></span>
        <?php endif; ?>
        <div class="address">Dumpay West, Basista, Pangasinan, Philippines<br>📞 0910-730-5969 &nbsp;·&nbsp; 0963-011-3868<br>✉️ westfarmresort@gmail.com</div>
      </div>
      <div class="receipt-ident">
        <div class="or-label">Official Receipt</div>
        <div class="or-number"><?php echo $or_number; ?></div>
        <div class="or-date"><?php echo date('F d, Y', strtotime($receipt['payment_date'])); ?><br><?php echo date('h:i A', strtotime($receipt['payment_date'])); ?></div>
      </div>
    </div>
  </div>

  <div class="receipt-divider"></div>

  <!-- Body -->
  <div class="receipt-body">

    <!-- Customer & Booking Info -->
    <div class="receipt-section-title"><i class="fas fa-info-circle"></i> Transaction Details</div>
    <div class="receipt-info-grid">
      <div class="receipt-field">
        <label>Received From</label>
        <div class="val"><?php echo htmlspecialchars($customer_name); ?></div>
      </div>
      <div class="receipt-field">
        <label>Contact</label>
        <div class="val"><?php echo $receipt['phone_number'] ? htmlspecialchars($receipt['phone_number']) : '—'; ?></div>
      </div>
      <div class="receipt-field">
        <label>Booking Reference</label>
        <div class="val">#<?php echo str_pad($receipt['booking_id'], 5, '0', STR_PAD_LEFT); ?></div>
      </div>
      <div class="receipt-field">
        <label>Facility</label>
        <div class="val"><?php echo htmlspecialchars($receipt['facility_name']); ?></div>
      </div>
      <div class="receipt-field">
        <label>Stay / Visit Period</label>
        <div class="val"><?php echo date('M d, Y', strtotime($receipt['check_in_date'])); ?> — <?php echo date('M d, Y', strtotime($receipt['check_out_date'])); ?></div>
      </div>
      <div class="receipt-field">
        <label>Guests</label>
        <div class="val">
          <?php
            $guestParts = [];
            if ($numAdults > 0) $guestParts[] = $numAdults . ' Adult' . ($numAdults > 1 ? 's' : '');
            if ($numKids > 0) $guestParts[] = $numKids . ' Kid' . ($numKids > 1 ? 's' : '');
            echo !empty($guestParts) ? implode(', ', $guestParts) : '1 Guest';
          ?>
        </div>
      </div>
    </div>

    <!-- Amount Breakdown -->
    <div class="receipt-section-title"><i class="fas fa-receipt"></i> Payment Breakdown</div>
    <table class="receipt-table">
      <thead>
        <tr>
          <th>Description</th>
          <th style="text-align:right;">Amount</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($isPoolCategory): ?>
        <tr>
          <td><?php echo htmlspecialchars($receipt['facility_name']); ?> — Adults (₱<?php echo number_format($receipt['base_price'], 2); ?> × <?php echo $numAdults; ?>)</td>
          <td style="text-align:right;">₱<?php echo number_format($receipt['base_price'] * $numAdults, 2); ?></td>
        </tr>
        <?php if ($numKids > 0): ?>
        <tr>
          <td><?php echo htmlspecialchars($receipt['facility_name']); ?> — Kids (₱100.00 × <?php echo $numKids; ?>)</td>
          <td style="text-align:right;">₱<?php echo number_format(100 * $numKids, 2); ?></td>
        </tr>
        <?php endif; ?>
        <tr>
          <td><strong>Subtotal</strong></td>
          <td style="text-align:right;"><strong>₱<?php echo number_format($total_amount, 2); ?></strong></td>
        </tr>
        <?php else: ?>
        <tr>
          <td><?php echo htmlspecialchars($receipt['facility_name']); ?> — Booking #<?php echo str_pad($receipt['booking_id'], 5, '0', STR_PAD_LEFT); ?></td>
          <td style="text-align:right;">₱<?php echo number_format($total_amount, 2); ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($prev_paid > 0): ?>
        <tr>
          <td>Previous payments</td>
          <td style="text-align:right;color:var(--green);">−₱<?php echo number_format($prev_paid, 2); ?></td>
        </tr>
        <?php endif; ?>
        <tr>
          <td><strong>This payment</strong> (<?php echo htmlspecialchars($receipt['method_name']); ?>)</td>
          <td style="text-align:right;color:var(--green);"><strong>₱<?php echo number_format($this_payment, 2); ?></strong></td>
        </tr>
        <tr class="total-row">
          <td>Total Paid</td>
          <td style="text-align:right;">₱<?php echo number_format($paid_so_far, 2); ?></td>
        </tr>
        <?php if ($remaining > 0): ?>
        <tr class="balance-row">
          <td>Remaining Balance</td>
          <td style="text-align:right;">₱<?php echo number_format($remaining, 2); ?></td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>>

    <!-- Payment Method Details -->
    <div class="receipt-section-title"><i class="fas fa-wallet"></i> Payment Method</div>
    <div class="receipt-payment-box">
      <div class="rpb-field">
        <label>Method</label>
        <div class="val"><?php echo htmlspecialchars($receipt['method_name']); ?></div>
      </div>
      <div class="rpb-field">
        <label>Transaction Ref</label>
        <div class="val" style="font-family:monospace;font-size:13px;"><?php echo $receipt['transaction_id'] ? htmlspecialchars($receipt['transaction_id']) : 'N/A (Cash)'; ?></div>
      </div>
      <div class="rpb-field">
        <label>Amount Paid</label>
        <div class="val" style="color:var(--green);font-size:16px;font-weight:700;">₱<?php echo number_format($this_payment, 2); ?></div>
      </div>
      <div class="rpb-field">
        <label>Payment Date</label>
        <div class="val"><?php echo date('M d, Y h:i A', strtotime($receipt['payment_date'])); ?></div>
      </div>
    </div>

    <!-- Status Stamp -->
    <div class="receipt-stamp">
      <?php if ($remaining <= 0): ?>
        <span class="stamp stamp-paid"><i class="fas fa-check-circle"></i> Fully Paid</span>
      <?php elseif ($paid_so_far > 0): ?>
        <span class="stamp stamp-partial"><i class="fas fa-adjust"></i> Partial Payment</span>
      <?php else: ?>
        <span class="stamp stamp-unpaid"><i class="fas fa-exclamation-circle"></i> Unpaid</span>
      <?php endif; ?>
    </div>

  </div>

  <!-- Footer -->
  <div class="receipt-footer">
    <div class="thankyou">Thank you for your payment!</div>
    <p>This is an electronic official receipt.<br>
    For inquiries, contact us at westfarmresort@gmail.com or call 0910-730-5969.<br>
    &copy; <?php echo date('Y'); ?> West Farm Resort and Hotel. All rights reserved.</p>
  </div>

  <!-- Zigzag tear edge -->
  <div style="height: 12px; background: linear-gradient(135deg, var(--cream) 33.333%, transparent 33.333%) -6px 0, linear-gradient(225deg, var(--cream) 33.333%, transparent 33.333%) -6px 0; background-size: 12px 12px; margin: 0 4px;"></div>

</div>

</body>
</html>
