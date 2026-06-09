<?php
session_start();

// Allow customers, admins, and owners to view
$is_customer = isset($_SESSION['user_id']) && $_SESSION['user_type_id'] == 2;
$is_admin    = isset($_SESSION['user_id']) && $_SESSION['user_type_id'] == 1;
$is_owner    = isset($_SESSION['user_id']) && $_SESSION['user_type_id'] == 3;

if (!$is_customer && !$is_admin && !$is_owner) {
    header("Location: ../pages/login.php");
    exit();
}

require_once '../config/db_connection.php';

$cancellation_id = isset($_GET['cancellation_id']) ? intval($_GET['cancellation_id']) : 0;
if ($cancellation_id <= 0) {
    header("Location: ../pages/login.php");
    exit();
}

// Fetch cancellation with all related info
$stmt = $pdo->prepare("
    SELECT bc.cancellation_id, bc.booking_id, bc.refund_amount, bc.additional_notes, bc.cancelled_at,
           bc.reason_id, bc.cancelled_by_user_id,
           cr.reason_name,
           b.total_amount, b.created_at AS booking_date,
           bs.status_name AS booking_status,
           ps.status_name AS payment_status,
           f.name AS facility_name, f.base_price,
           bi.check_in_date, bi.check_out_date,
           cust.user_id AS cust_id, cust.email AS cust_email,
           up.first_name, up.last_name, up.phone_number,
           (SELECT COALESCE(SUM(amount_paid), 0) FROM payments WHERE booking_id = b.booking_id) AS total_paid,
           (SELECT GROUP_CONCAT(DISTINCT pm.method_name SEPARATOR ', ')
            FROM payments p JOIN payment_methods pm ON p.payment_method_id = pm.payment_method_id
            WHERE p.booking_id = b.booking_id) AS payment_methods
    FROM booking_cancellations bc
    JOIN bookings b ON bc.booking_id = b.booking_id
    JOIN booking_items bi ON b.booking_id = bi.booking_id
    JOIN facilities f ON bi.facility_id = f.facility_id
    JOIN booking_statuses bs ON b.booking_status_id = bs.booking_status_id
    JOIN payment_statuses ps ON b.payment_status_id = ps.payment_status_id
    JOIN cancellation_reasons cr ON bc.reason_id = cr.reason_id
    JOIN users cust ON b.customer_id = cust.user_id
    LEFT JOIN user_profiles up ON cust.user_id = up.user_id
    WHERE bc.cancellation_id = ?
");
$stmt->execute([$cancellation_id]);
$receipt = $stmt->fetch();

if (!$receipt) {
    echo "Refund receipt not found.";
    exit();
}

// Security: customers can only see their own refund receipts
if ($is_customer && $receipt['cust_id'] != $_SESSION['user_id']) {
    echo "Access denied.";
    exit();
}

// Generate refund receipt number
$rr_number = 'WF-RR-' . str_pad($receipt['cancellation_id'], 6, '0', STR_PAD_LEFT);

// Calculate amounts
$total_amount = floatval($receipt['total_amount']);
$total_paid   = floatval($receipt['total_paid']);
$refund_amt   = floatval($receipt['refund_amount']);

// Format helpers
$customer_name = trim(($receipt['first_name'] ?? '') . ' ' . ($receipt['last_name'] ?? ''));
if (empty($customer_name)) $customer_name = $receipt['cust_email'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Refund Receipt <?php echo $rr_number; ?> | West Farm Resort and Hotel</title>
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

    /* ── Header — red tint for refund ── */
    .receipt-header {
      background: linear-gradient(135deg, #2a0f0f 0%, #3a1a1a 60%, #552c2c 100%);
      padding: 32px 40px 28px; color: #fff; position: relative; overflow: hidden;
    }
    .receipt-header::after { content: '↩️'; position: absolute; right: 30px; top: 50%; transform: translateY(-50%); font-size: 64px; opacity: 0.07; }
    .receipt-header-row { display: flex; justify-content: space-between; align-items: flex-start; }
    .receipt-brand h1 { font-family: 'Josefin Sans', sans-serif; font-size: 20px; font-weight: 700; letter-spacing: 5px; }
    .receipt-brand p { font-size: 10px; font-weight: 300; letter-spacing: 3px; text-transform: uppercase; color: rgba(255,255,255,0.5); margin-top: 2px; }
    .receipt-brand .address { font-size: 11px; color: rgba(255,255,255,0.45); margin-top: 8px; line-height: 1.6; }
    .receipt-ident { text-align: right; }
    .receipt-ident .or-label { font-family: 'Josefin Sans', sans-serif; font-size: 10px; letter-spacing: 3px; text-transform: uppercase; color: #fca5a5; }
    .receipt-ident .or-number { font-family: 'Josefin Sans', sans-serif; font-size: 22px; font-weight: 700; letter-spacing: 2px; margin-top: 2px; }
    .receipt-ident .or-date { font-size: 11px; color: rgba(255,255,255,0.5); margin-top: 6px; }

    /* ── Divider ── */
    .receipt-divider { height: 3px; background: repeating-linear-gradient(90deg, var(--red) 0px, var(--red) 8px, transparent 8px, transparent 12px); }

    /* ── Body ── */
    .receipt-body { padding: 28px 40px; }

    .receipt-section-title { font-family: 'Josefin Sans', sans-serif; font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: var(--red); margin-bottom: 12px; }

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
    .receipt-table .total-row td { font-size: 16px; font-weight: 700; color: var(--red); border-top: 2px solid var(--red); border-bottom: none; padding-top: 14px; }
    .receipt-table .refund-row td { color: var(--red); }

    /* ── Refund highlight box ── */
    .refund-highlight {
      background: linear-gradient(135deg, #fef2f2, #fff1f2);
      border: 1px solid #fecaca;
      border-radius: 12px;
      padding: 20px 24px;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
    }
    .refund-highlight .rh-label {
      font-family: 'Josefin Sans', sans-serif;
      font-size: 10px;
      letter-spacing: 2px;
      text-transform: uppercase;
      color: #991b1b;
    }
    .refund-highlight .rh-amount {
      font-family: 'Josefin Sans', sans-serif;
      font-size: 28px;
      font-weight: 700;
      color: var(--red);
    }
    .refund-highlight .rh-icon {
      font-size: 36px;
      color: #fca5a5;
    }

    /* ── Cancellation details box ── */
    .cancel-details-box {
      background: #fafaf8;
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 18px 20px;
      margin-bottom: 24px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
    }
    .cancel-details-box .cdb-field label {
      font-family: 'Josefin Sans', sans-serif;
      font-size: 9px;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      color: #aaa;
      display: block;
      margin-bottom: 3px;
    }
    .cancel-details-box .cdb-field .val { font-size: 14px; font-weight: 600; color: var(--text); }

    /* ── Stamp ── */
    .receipt-stamp { text-align: center; margin: 28px 0 20px; }
    .receipt-stamp .stamp {
      display: inline-block;
      padding: 8px 24px;
      border: 2px solid;
      border-radius: 4px;
      font-family: 'Josefin Sans', sans-serif;
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 3px;
      text-transform: uppercase;
      transform: rotate(-5deg);
    }
    .stamp-refund { color: var(--red); border-color: var(--red); background: rgba(192,57,43,0.06); }
    .stamp-cancelled { color: #991b1b; border-color: #991b1b; background: rgba(153,27,27,0.06); }

    /* ── Notice box ── */
    .refund-notice {
      background: #fef3c7;
      border: 1px solid #fde68a;
      border-radius: 10px;
      padding: 14px 18px;
      margin-bottom: 24px;
      font-size: 12px;
      color: #92400e;
      line-height: 1.6;
    }
    .refund-notice strong { display: block; margin-bottom: 4px; }

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
      .cancel-details-box { grid-template-columns: 1fr; }
      .refund-highlight { flex-direction: column; text-align: center; }
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
      <button class="print-btn" onclick="window.print()"><i class="fas fa-print"></i> Print Refund Receipt</button>
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
        <div class="address">Dumpay West, Basista, Pangasinan, Philippines<br>📞 0910-730-5969 &nbsp;·&nbsp; 0963-011-3868<br>✉️ westfarmresort@gmail.com</div>
      </div>
      <div class="receipt-ident">
        <div class="or-label">Refund Receipt</div>
        <div class="or-number"><?php echo $rr_number; ?></div>
        <div class="or-date"><?php echo date('F d, Y', strtotime($receipt['cancelled_at'])); ?><br><?php echo date('h:i A', strtotime($receipt['cancelled_at'])); ?></div>
      </div>
    </div>
  </div>

  <div class="receipt-divider"></div>

  <!-- Body -->
  <div class="receipt-body">

    <!-- Refund Amount Highlight -->
    <?php if ($refund_amt > 0): ?>
    <div class="refund-highlight">
      <div>
        <div class="rh-label"><i class="fas fa-undo"></i> Refund Amount</div>
        <div class="rh-amount">₱<?php echo number_format($refund_amt, 2); ?></div>
      </div>
      <div class="rh-icon"><i class="fas fa-file-invoice-dollar"></i></div>
    </div>

    <div class="refund-notice">
      <strong><i class="fas fa-info-circle"></i> Refund Notice</strong>
      This amount has been marked as refunded. Please contact West Farm Resort to arrange your refund pickup or bank transfer. Bring this receipt when claiming.
    </div>
    <?php endif; ?>

    <!-- Customer & Booking Info -->
    <div class="receipt-section-title"><i class="fas fa-info-circle"></i> Cancellation Details</div>
    <div class="receipt-info-grid">
      <div class="receipt-field">
        <label>Customer Name</label>
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
        <label>Original Booking Date</label>
        <div class="val"><?php echo date('M d, Y', strtotime($receipt['booking_date'])); ?></div>
      </div>
      <div class="receipt-field">
        <label>Facility</label>
        <div class="val"><?php echo htmlspecialchars($receipt['facility_name']); ?></div>
      </div>
      <div class="receipt-field">
        <label>Original Stay Period</label>
        <div class="val"><?php echo date('M d, Y', strtotime($receipt['check_in_date'])); ?> — <?php echo date('M d, Y', strtotime($receipt['check_out_date'])); ?></div>
      </div>
    </div>

    <!-- Cancellation Info -->
    <div class="receipt-section-title"><i class="fas fa-clipboard-list"></i> Cancellation Info</div>
    <div class="cancel-details-box">
      <div class="cdb-field">
        <label>Cancellation Reason</label>
        <div class="val"><?php echo htmlspecialchars($receipt['reason_name']); ?></div>
      </div>
      <div class="cdb-field">
        <label>Cancelled At</label>
        <div class="val"><?php echo date('M d, Y h:i A', strtotime($receipt['cancelled_at'])); ?></div>
      </div>
      <div class="cdb-field">
        <label>Payment Method(s) Used</label>
        <div class="val"><?php echo $receipt['payment_methods'] ? htmlspecialchars($receipt['payment_methods']) : 'N/A'; ?></div>
      </div>
      <div class="cdb-field">
        <label>Additional Notes</label>
        <div class="val"><?php echo $receipt['additional_notes'] ? htmlspecialchars($receipt['additional_notes']) : '—'; ?></div>
      </div>
    </div>

    <!-- Amount Breakdown -->
    <div class="receipt-section-title"><i class="fas fa-receipt"></i> Refund Breakdown</div>
    <table class="receipt-table">
      <thead>
        <tr>
          <th>Description</th>
          <th style="text-align:right;">Amount</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><?php echo htmlspecialchars($receipt['facility_name']); ?> — Booking #<?php echo str_pad($receipt['booking_id'], 5, '0', STR_PAD_LEFT); ?></td>
          <td style="text-align:right;">₱<?php echo number_format($total_amount, 2); ?></td>
        </tr>
        <tr>
          <td>Total amount paid</td>
          <td style="text-align:right;">₱<?php echo number_format($total_paid, 2); ?></td>
        </tr>
        <?php if ($refund_amt > 0): ?>
        <tr class="refund-row">
          <td><strong>Refund amount</strong></td>
          <td style="text-align:right;"><strong>−₱<?php echo number_format($refund_amt, 2); ?></strong></td>
        </tr>
        <?php endif; ?>
        <tr class="total-row">
          <td>Refund Due</td>
          <td style="text-align:right;">₱<?php echo number_format($refund_amt, 2); ?></td>
        </tr>
      </tbody>
    </table>

    <!-- Status Stamp -->
    <div class="receipt-stamp">
      <?php if ($refund_amt > 0): ?>
        <span class="stamp stamp-refund"><i class="fas fa-undo"></i> Refunded</span>
      <?php else: ?>
        <span class="stamp stamp-cancelled"><i class="fas fa-ban"></i> Cancelled</span>
      <?php endif; ?>
    </div>

  </div>

  <!-- Footer -->
  <div class="receipt-footer">
    <div class="thankyou">We hope to see you again soon!</div>
    <p>This is an electronic refund receipt.<br>
    For refund inquiries, contact us at westfarmresort@gmail.com or call 0910-730-5969.<br>
    &copy; <?php echo date('Y'); ?> West Farm Resort and Hotel. All rights reserved.</p>
  </div>

</div>

</body>
</html>
