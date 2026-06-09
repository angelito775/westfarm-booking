<?php
session_start();

$is_customer = isset($_SESSION['user_id']) && $_SESSION['user_type_id'] == 2;
$is_admin    = isset($_SESSION['user_id']) && $_SESSION['user_type_id'] == 1;
$is_owner    = isset($_SESSION['user_id']) && $_SESSION['user_type_id'] == 3;

if (!$is_customer && !$is_admin && !$is_owner) {
    header("Location: ../pages/login.php");
    exit();
}

require_once '../config/db_connection.php';

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
if ($order_id <= 0) {
    header("Location: ../pages/login.php");
    exit();
}

// Fetch order with all related info
$stmt = $pdo->prepare("
    SELECT co.order_id, co.customer_id, co.quantity_kg, co.price_per_kg,
           co.total_amount, co.amount_paid, co.pickup_date, co.ordered_at,
           co.payment_method_id, co.payment_status_id,
           os.status_name AS order_status,
           ps.status_name AS payment_status,
           pm.method_name AS payment_method,
           cust.email AS cust_email,
           up.first_name, up.last_name, up.phone_number
    FROM crayfish_orders co
    JOIN order_statuses os ON co.status_id = os.status_id
    JOIN payment_statuses ps ON co.payment_status_id = ps.payment_status_id
    JOIN users cust ON co.customer_id = cust.user_id
    LEFT JOIN user_profiles up ON cust.user_id = up.user_id
    LEFT JOIN payment_methods pm ON co.payment_method_id = pm.payment_method_id
    WHERE co.order_id = ?
");
$stmt->execute([$order_id]);
$receipt = $stmt->fetch();

if (!$receipt) {
    echo "Receipt not found.";
    exit();
}

// Security: customers can only see their own receipts
if ($is_customer && $receipt['customer_id'] != $_SESSION['user_id']) {
    echo "Access denied.";
    exit();
}

// Fetch payment history for this order
$stmt_pay = $pdo->prepare("
    SELECT cp.payment_id, cp.amount_paid, cp.payment_date, cp.transaction_id,
           pm.method_name
    FROM crayfish_payments cp
    JOIN payment_methods pm ON cp.payment_method_id = pm.payment_method_id
    WHERE cp.order_id = ?
    ORDER BY cp.payment_date ASC
");
$stmt_pay->execute([$order_id]);
$payments = $stmt_pay->fetchAll();

// Generate receipt number
$cr_number = 'WF-CR-' . str_pad($receipt['order_id'], 6, '0', STR_PAD_LEFT);

// Calculate amounts
$total_amount  = floatval($receipt['total_amount']);
$total_paid    = floatval($receipt['amount_paid']);
$remaining     = $total_amount - $total_paid;

// Format helpers
$customer_name = trim(($receipt['first_name'] ?? '') . ' ' . ($receipt['last_name'] ?? ''));
if (empty($customer_name)) $customer_name = $receipt['cust_email'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Crayfish Receipt <?php echo $cr_number; ?> | West Farm Resort and Hotel</title>
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

    .receipt-topbar {
      background: var(--forest); color: #fff; padding: 14px 0;
      display: flex; justify-content: space-between; align-items: center;
    }
    .receipt-topbar-inner { max-width: 800px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; width: 100%; }
    .receipt-topbar a { color: rgba(255,255,255,0.7); text-decoration: none; font-family: 'Josefin Sans', sans-serif; font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; transition: color 0.2s; }
    .receipt-topbar a:hover { color: #fff; }
    .receipt-topbar .print-btn { background: var(--gold); color: #fff; border: none; padding: 7px 20px; border-radius: 20px; font-family: 'Josefin Sans', sans-serif; font-size: 11px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; cursor: pointer; transition: background 0.2s; }
    .receipt-topbar .print-btn:hover { background: #b07a2a; }

    .receipt-paper {
      max-width: 800px; margin: 2.5rem auto; background: #fff;
      border-radius: 4px; box-shadow: 0 2px 20px rgba(0,0,0,0.08);
      overflow: hidden;
    }

    .receipt-header {
      background: linear-gradient(135deg, #0f2a0f 0%, #1a3a1a 60%, #2c552c 100%);
      padding: 32px 40px 28px; color: #fff; position: relative; overflow: hidden;
    }
    .receipt-header::after { content: '🦞'; position: absolute; right: 30px; top: 50%; transform: translateY(-50%); font-size: 64px; opacity: 0.07; }
    .receipt-header-row { display: flex; justify-content: space-between; align-items: flex-start; }
    .receipt-brand h1 { font-family: 'Josefin Sans', sans-serif; font-size: 20px; font-weight: 700; letter-spacing: 5px; }
    .receipt-brand p { font-size: 10px; font-weight: 300; letter-spacing: 3px; text-transform: uppercase; color: rgba(255,255,255,0.5); margin-top: 2px; }
    .receipt-brand .address { font-size: 11px; color: rgba(255,255,255,0.45); margin-top: 8px; line-height: 1.6; }
    .receipt-ident { text-align: right; }
    .receipt-ident .or-label { font-family: 'Josefin Sans', sans-serif; font-size: 10px; letter-spacing: 3px; text-transform: uppercase; color: #7ed87e; }
    .receipt-ident .or-number { font-family: 'Josefin Sans', sans-serif; font-size: 22px; font-weight: 700; letter-spacing: 2px; margin-top: 2px; }
    .receipt-ident .or-date { font-size: 11px; color: rgba(255,255,255,0.5); margin-top: 6px; }

    .receipt-divider { height: 3px; background: repeating-linear-gradient(90deg, var(--gold) 0px, var(--gold) 8px, transparent 8px, transparent 12px); }

    .receipt-body { padding: 28px 40px; }

    .receipt-section-title { font-family: 'Josefin Sans', sans-serif; font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: var(--gold); margin-bottom: 12px; }

    .receipt-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; }
    .receipt-field label { font-family: 'Josefin Sans', sans-serif; font-size: 9px; letter-spacing: 1.5px; text-transform: uppercase; color: #aaa; display: block; margin-bottom: 3px; }
    .receipt-field .val { font-size: 14px; color: var(--text); font-weight: 500; }

    .receipt-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
    .receipt-table th { font-family: 'Josefin Sans', sans-serif; font-size: 9px; letter-spacing: 1.5px; text-transform: uppercase; color: #888; padding: 10px 12px; border-bottom: 2px solid var(--border); text-align: left; }
    .receipt-table th:last-child { text-align: right; }
    .receipt-table td { padding: 12px; border-bottom: 1px solid #f5f5f5; font-size: 13px; }
    .receipt-table td:last-child { text-align: right; font-weight: 600; }
    .receipt-table .total-row td { font-size: 16px; font-weight: 700; color: var(--forest); border-top: 2px solid var(--forest); border-bottom: none; padding-top: 14px; }
    .receipt-table .paid-row td { color: var(--green); }
    .receipt-table .balance-row td { color: var(--red); }

    .receipt-stamp { text-align: center; margin: 28px 0 20px; }
    .receipt-stamp .stamp { display: inline-block; padding: 8px 24px; border: 2px solid; border-radius: 4px; font-family: 'Josefin Sans', sans-serif; font-size: 11px; font-weight: 700; letter-spacing: 3px; text-transform: uppercase; transform: rotate(-5deg); }
    .stamp-paid { color: var(--green); border-color: var(--green); background: rgba(22,163,74,0.06); }
    .stamp-partial { color: #d97706; border-color: #d97706; background: rgba(217,119,6,0.06); }
    .stamp-unpaid { color: var(--red); border-color: var(--red); background: rgba(192,57,43,0.06); }
    .stamp-cancelled { color: #991b1b; border-color: #991b1b; background: rgba(153,27,27,0.06); }

    .receipt-footer { padding: 20px 40px 28px; border-top: 1px dashed var(--border); text-align: center; }
    .receipt-footer p { font-size: 11px; color: #aaa; line-height: 1.8; }
    .receipt-footer .thankyou { font-family: 'Dancing Script', cursive; font-size: 20px; color: var(--forest); margin-bottom: 6px; }

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
        <a href="../public/westcrays.php"><i class="fas fa-arrow-left"></i> Back to West Cray</a>
      <?php elseif ($is_admin): ?>
        <a href="../admin/payments.php"><i class="fas fa-arrow-left"></i> Back to Payment Ledger</a>
      <?php elseif ($is_owner): ?>
        <a href="../owner/crayfish_orders.php"><i class="fas fa-arrow-left"></i> Back to Crayfish Orders</a>
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
        <div class="address">Dumpay West, Basista, Pangasinan, Philippines<br>📞 0910-730-5969 &nbsp;·&nbsp; 0963-011-3868<br>✉️ westfarmresort@gmail.com</div>
      </div>
      <div class="receipt-ident">
        <div class="or-label">Purchase Receipt</div>
        <div class="or-number"><?php echo $cr_number; ?></div>
        <div class="or-date"><?php echo date('F d, Y', strtotime($receipt['ordered_at'])); ?><br><?php echo date('h:i A', strtotime($receipt['ordered_at'])); ?></div>
      </div>
    </div>
  </div>

  <div class="receipt-divider"></div>

  <!-- Body -->
  <div class="receipt-body">

    <!-- Customer & Order Info -->
    <div class="receipt-section-title"><i class="fas fa-info-circle"></i> Order Details</div>
    <div class="receipt-info-grid">
      <div class="receipt-field">
        <label>Customer</label>
        <div class="val"><?php echo htmlspecialchars($customer_name); ?></div>
      </div>
      <div class="receipt-field">
        <label>Contact</label>
        <div class="val"><?php echo $receipt['phone_number'] ? htmlspecialchars($receipt['phone_number']) : '—'; ?></div>
      </div>
      <div class="receipt-field">
        <label>Order #</label>
        <div class="val">#<?php echo str_pad($receipt['order_id'], 5, '0', STR_PAD_LEFT); ?></div>
      </div>
      <div class="receipt-field">
        <label>Order Status</label>
        <div class="val"><?php echo htmlspecialchars($receipt['order_status']); ?></div>
      </div>
      <div class="receipt-field">
        <label>Product</label>
        <div class="val">Fresh Live Crayfish</div>
      </div>
      <div class="receipt-field">
        <label>Quantity</label>
        <div class="val"><?php echo number_format($receipt['quantity_kg'], 1); ?> kg @ ₱<?php echo number_format($receipt['price_per_kg'], 0); ?>/kg</div>
      </div>
      <div class="receipt-field">
        <label>Pickup Date</label>
        <div class="val"><?php echo $receipt['pickup_date'] ? date('M d, Y h:i A', strtotime($receipt['pickup_date'])) : '—'; ?></div>
      </div>
      <div class="receipt-field">
        <label>Payment Method</label>
        <div class="val"><?php echo $receipt['payment_method'] ? htmlspecialchars($receipt['payment_method']) : 'Not selected'; ?></div>
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
        <tr>
          <td>Fresh Live Crayfish — Order #<?php echo str_pad($receipt['order_id'], 5, '0', STR_PAD_LEFT); ?> (<?php echo number_format($receipt['quantity_kg'], 1); ?> kg × ₱<?php echo number_format($receipt['price_per_kg'], 0); ?>)</td>
          <td style="text-align:right;">₱<?php echo number_format($total_amount, 2); ?></td>
        </tr>
        <?php foreach ($payments as $p): ?>
        <tr>
          <td>Payment — <?php echo htmlspecialchars($p['method_name']); ?> (<?php echo htmlspecialchars($p['transaction_id']); ?>)</td>
          <td style="text-align:right;color:var(--green);">₱<?php echo number_format($p['amount_paid'], 2); ?></td>
        </tr>
        <?php endforeach; ?>
        <tr class="total-row">
          <td>Total Paid</td>
          <td style="text-align:right;">₱<?php echo number_format($total_paid, 2); ?></td>
        </tr>
        <?php if ($remaining > 0): ?>
        <tr class="balance-row">
          <td>Remaining Balance</td>
          <td style="text-align:right;">₱<?php echo number_format($remaining, 2); ?></td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>

    <!-- Status Stamp -->
    <div class="receipt-stamp">
      <?php
        $stamp_class = 'stamp-unpaid';
        if ($receipt['order_status'] === 'Cancelled') {
            $stamp_class = 'stamp-cancelled';
        } elseif ($receipt['payment_status'] === 'Paid') {
            $stamp_class = 'stamp-paid';
        } elseif ($receipt['payment_status'] === 'Partial') {
            $stamp_class = 'stamp-partial';
        }
      ?>
      <span class="stamp <?php echo $stamp_class; ?>">
        <?php if ($receipt['order_status'] === 'Cancelled'): ?>
          <i class="fas fa-ban"></i> Cancelled
        <?php elseif ($receipt['payment_status'] === 'Paid'): ?>
          <i class="fas fa-check-circle"></i> Fully Paid
        <?php elseif ($receipt['payment_status'] === 'Partial'): ?>
          <i class="fas fa-adjust"></i> Partial Payment
        <?php else: ?>
          <i class="fas fa-exclamation-circle"></i> Unpaid
        <?php endif; ?>
      </span>
    </div>

  </div>

  <!-- Footer -->
  <div class="receipt-footer">
    <div class="thankyou">Thank you for your order!</div>
    <p>This is an electronic purchase receipt for your crayfish order.<br>
    Present this receipt when picking up your order.<br>
    For inquiries, contact us at westfarmresort@gmail.com or call 0910-730-5969.<br>
    &copy; <?php echo date('Y'); ?> West Farm Resort and Hotel. All rights reserved.</p>
  </div>

</div>

</body>
</html>
