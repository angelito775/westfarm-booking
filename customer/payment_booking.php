<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type_id'] != 2) {
    header("Location: ../pages/login.php");
    exit();
}

require_once '../config/db_connection.php';
$user_id = $_SESSION['user_id'];

// Load payment methods
$payment_methods = $pdo->query("SELECT * FROM payment_methods WHERE is_active = 1 ORDER BY payment_method_id")->fetchAll();

// Load cancellation reasons
$cancellation_reasons = $pdo->query("SELECT reason_id, reason_name, description FROM cancellation_reasons ORDER BY reason_id")->fetchAll();

// If a specific booking_id is provided, show payment form for it
$selected_booking = null;
$selected_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
if ($selected_id > 0) {
    $stmt = $pdo->prepare(
        "SELECT b.booking_id, b.total_amount, b.created_at,
                bs.status_name AS booking_status, ps.status_name AS payment_status,
                f.name AS facility_name, f.base_price,
                bi.check_in_date, bi.check_out_date,
                (SELECT COALESCE(SUM(amount_paid), 0) FROM payments WHERE booking_id = b.booking_id) AS paid_so_far
         FROM bookings b
         JOIN booking_items bi ON b.booking_id = bi.booking_id
         JOIN facilities f ON bi.facility_id = f.facility_id
         JOIN booking_statuses bs ON b.booking_status_id = bs.booking_status_id
         JOIN payment_statuses ps ON b.payment_status_id = ps.payment_status_id
         WHERE b.booking_id = ? AND b.customer_id = ?"
    );
    $stmt->execute([$selected_id, $user_id]);
    $selected_booking = $stmt->fetch();
}

// Load all bookings with their payment info for the list
$stmt = $pdo->prepare(
    "SELECT b.booking_id, b.total_amount, b.created_at,
            bs.status_name AS booking_status, ps.status_name AS payment_status,
            f.name AS facility_name, f.base_price,
            bi.check_in_date, bi.check_out_date,
            (SELECT COALESCE(SUM(amount_paid), 0) FROM payments WHERE booking_id = b.booking_id) AS paid_so_far,
            (SELECT p2.payment_id FROM payments p2 WHERE p2.booking_id = b.booking_id ORDER BY p2.payment_date DESC LIMIT 1) AS latest_payment_id,
            (SELECT bc.cancellation_id FROM booking_cancellations bc WHERE bc.booking_id = b.booking_id ORDER BY bc.cancelled_at DESC LIMIT 1) AS cancellation_id
     FROM bookings b
     JOIN booking_items bi ON b.booking_id = bi.booking_id
     JOIN facilities f ON bi.facility_id = f.facility_id
     JOIN booking_statuses bs ON b.booking_status_id = bs.booking_status_id
     JOIN payment_statuses ps ON b.payment_status_id = ps.payment_status_id
     WHERE b.customer_id = ?
     ORDER BY b.created_at DESC
     LIMIT 20"
);
$stmt->execute([$user_id]);
$all_bookings = $stmt->fetchAll();

// Flash messages
$success_msg = '';
$error_msg = '';
$receipt_payment_id = 0;
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'payment_complete') {
        $success_msg = 'Payment processed successfully! Thank you for your payment.';
        // Get the latest payment_id for this customer's most recent booking for receipt link
        $stmt_r = $pdo->prepare("SELECT p.payment_id FROM payments p JOIN bookings b ON p.booking_id = b.booking_id WHERE b.customer_id = ? ORDER BY p.payment_date DESC LIMIT 1");
        $stmt_r->execute([$user_id]);
        $r = $stmt_r->fetch();
        if ($r) $receipt_payment_id = $r['payment_id'];
    } elseif ($_GET['success'] === 'booking_cancelled') {
        $success_msg = 'Your booking has been cancelled successfully.';
    }
}
if (isset($_GET['error'])) {
    $errors = [
        'not_logged_in'      => 'Please sign in to make a payment.',
        'invalid_booking'    => 'Invalid booking selected.',
        'booking_not_found'  => 'Booking not found or does not belong to your account.',
        'invalid_method'     => 'Please select a valid payment method.',
        'already_paid'       => 'This booking has already been fully paid.',
        'refunded'           => 'This booking has been refunded and cannot be paid.',
        'invalid_amount'     => 'Please enter a valid payment amount.',
        'amount_exceeds'     => 'Payment amount cannot exceed the total booking amount.',
        'system_error'       => 'An error occurred processing your payment. Please try again.',
        'already_completed'  => 'This booking has already been completed and cannot be cancelled.',
        'already_cancelled'  => 'This booking has already been cancelled.',
        'cancel_failed'      => 'Unable to cancel booking. Please try again.',
    ];
    $error_msg = $errors[$_GET['error']] ?? 'An error occurred.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments | West Farm Resort and Hotel</title>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;600;700&family=Josefin+Sans:wght@300;400;600;700&family=Lora:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/customer.css">
</head>
<body>

<!-- NAV -->
<nav>
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
        <li><a href="../public/booking.php">BOOK NOW</a></li>
        <li><a href="profile.php">MY PROFILE</a></li>
        <li><a href="payment_booking.php" class="active">PAYMENTS</a></li>
        <li><a href="../logic/logout_customer.php" class="nav-book-btn" style="background: transparent; border: 1px solid rgba(255,255,255,0.4);">
            <i class="fas fa-sign-out-alt"></i> SIGN OUT
        </a></li>
    </ul>
</nav>

<!-- PAGE HERO -->
<div class="page-hero">
    <h1>Booking & Payments</h1>
    <p>View your reservations and manage payments</p>
</div>

<!-- MAIN CONTENT -->
<div class="cust-container">

    <?php if ($success_msg): ?>
        <div class="alert alert-success" style="justify-content:space-between;flex-wrap:wrap;gap:10px;">
            <span><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_msg); ?></span>
            <?php if ($receipt_payment_id): ?>
                <a href="../pages/receipt.php?payment_id=<?php echo $receipt_payment_id; ?>" class="btn btn-sm" style="background:var(--forest);color:#fff;border:none;padding:6px 16px;border-radius:20px;font-family:'Josefin Sans',sans-serif;font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;text-decoration:none;display:inline-flex;align-items:center;gap:5px;">
                    <i class="fas fa-receipt"></i> View Receipt
                </a>
            <?php endif; ?>
        </div>
    <?php elseif ($error_msg): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <!-- ═══ PAYMENT FORM (shown when a booking is selected) ═══ -->
    <?php if ($selected_booking): ?>
    <div class="cust-card" style="margin-bottom: 1.5rem;">
        <div class="cust-card-header">
            <h2><i class="fas fa-credit-card"></i> Make Payment</h2>
            <a href="payment_booking.php" class="btn btn-sm btn-outline"><i class="fas fa-arrow-left"></i> Back to All Bookings</a>
        </div>
        <div class="cust-card-body">

            <!-- Booking Summary -->
            <div style="background: var(--cream); border: 1px solid var(--border); border-radius: 10px; padding: 1.25rem; margin-bottom: 1.5rem;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <div style="font-family: 'Josefin Sans', sans-serif; font-size: 0.6rem; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: var(--muted);">Booking #</div>
                        <div style="font-size: 1.1rem; font-weight: 700; color: var(--forest);"><?php echo $selected_booking['booking_id']; ?></div>
                    </div>
                    <div>
                        <div style="font-family: 'Josefin Sans', sans-serif; font-size: 0.6rem; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: var(--muted);">Facility</div>
                        <div style="font-size: 1.1rem; font-weight: 700; color: var(--forest);"><?php echo htmlspecialchars($selected_booking['facility_name']); ?></div>
                    </div>
                    <div>
                        <div style="font-family: 'Josefin Sans', sans-serif; font-size: 0.6rem; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: var(--muted);">Check-in</div>
                        <div style="font-size: 0.95rem; font-weight: 600;"><?php echo date('M d, Y', strtotime($selected_booking['check_in_date'])); ?></div>
                    </div>
                    <div>
                        <div style="font-family: 'Josefin Sans', sans-serif; font-size: 0.6rem; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: var(--muted);">Check-out</div>
                        <div style="font-size: 0.95rem; font-weight: 600;"><?php echo date('M d, Y', strtotime($selected_booking['check_out_date'])); ?></div>
                    </div>
                    <div>
                        <div style="font-family: 'Josefin Sans', sans-serif; font-size: 0.6rem; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: var(--muted);">Total Amount</div>
                        <div style="font-size: 1.2rem; font-weight: 700; color: var(--forest);">₱<?php echo number_format($selected_booking['total_amount'], 2); ?></div>
                    </div>
                    <div>
                        <div style="font-family: 'Josefin Sans', sans-serif; font-size: 0.6rem; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: var(--muted);">Already Paid</div>
                        <div style="font-size: 1.2rem; font-weight: 700; color: var(--green);">₱<?php echo number_format($selected_booking['paid_so_far'], 2); ?></div>
                    </div>
                </div>
                <?php
                    $remaining = $selected_booking['total_amount'] - $selected_booking['paid_so_far'];
                    if ($remaining > 0):
                ?>
                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border); text-align: right;">
                    <span style="font-family: 'Josefin Sans', sans-serif; font-size: 0.65rem; letter-spacing: 2px; text-transform: uppercase; color: var(--muted);">Remaining Balance:</span>
                    <span style="font-size: 1.4rem; font-weight: 700; color: var(--red); margin-left: 8px;">₱<?php echo number_format($remaining, 2); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($selected_booking['payment_status'] === 'Paid'): ?>
                <div class="alert alert-success" style="justify-content: center;">
                    <i class="fas fa-check-circle" style="font-size: 1.5rem;"></i>
                    <div>
                        <strong>Fully Paid</strong><br>
                        <span style="font-size: 0.85rem;">This booking has been completely paid. No further payment needed.</span>
                    </div>
                </div>
            <?php elseif ($selected_booking['booking_status'] === 'Cancelled'): ?>
                <div class="alert alert-error" style="justify-content: center;">
                    <i class="fas fa-times-circle" style="font-size: 1.5rem;"></i>
                    <div>
                        <strong>Booking Cancelled</strong><br>
                        <span style="font-size: 0.85rem;">This booking has been cancelled and cannot be paid.</span>
                    </div>
                </div>
            <?php else: ?>
            <!-- Payment Form -->
            <form method="POST" action="../logic/payment_process.php" id="paymentForm">
                <input type="hidden" name="booking_id" value="<?php echo $selected_booking['booking_id']; ?>">

                <!-- Payment Method Selection -->
                <h3 style="font-family: 'Josefin Sans', sans-serif; font-size: 0.75rem; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: var(--forest); margin-bottom: 1rem;">
                    <i class="fas fa-wallet"></i> Select Payment Method
                </h3>
                <div class="payment-methods" style="margin-bottom: 1.5rem;">
                    <?php
                    $pm_icons = ['Cash' => 'fa-money-bill-wave', 'GCash' => 'fa-mobile-alt', 'Maya' => 'fa-mobile-alt', 'MariBank (Formerly SeaBank)' => 'fa-university'];
                    foreach ($payment_methods as $pm):
                        $icon = $pm_icons[$pm['method_name']] ?? 'fa-credit-card';
                    ?>
                    <div class="payment-method-card" onclick="selectPayment(<?php echo $pm['payment_method_id']; ?>)">
                        <div class="pm-check"><i class="fas fa-check-circle"></i></div>
                        <div class="pm-icon"><i class="fas <?php echo $icon; ?>"></i></div>
                        <div class="pm-name"><?php echo htmlspecialchars($pm['method_name']); ?></div>
                        <?php if ($pm['description']): ?>
                            <div class="pm-desc"><?php echo htmlspecialchars($pm['description']); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="payment_method" id="paymentMethodInput" value="">

                <!-- Amount -->
                <h3 style="font-family: 'Josefin Sans', sans-serif; font-size: 0.75rem; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: var(--forest); margin-bottom: 1rem;">
                    <i class="fas fa-coins"></i> Payment Amount
                </h3>
                <div class="cust-field" style="max-width: 300px; margin-bottom: 1rem;">
                    <label>Amount (₱)</label>
                    <input type="number" name="amount" id="paymentAmount" step="0.01" min="1" max="<?php echo $remaining; ?>"
                           value="<?php echo number_format($remaining, 2, '.', ''); ?>" required
                           placeholder="Enter amount">
                    <div style="font-size: 0.75rem; color: var(--muted); margin-top: 4px;">
                        Max: ₱<?php echo number_format($remaining, 2); ?>
                        <span style="margin-left: 12px; cursor: pointer; color: var(--forest); font-weight: 600;" onclick="document.getElementById('paymentAmount').value='<?php echo number_format($remaining, 2, '.', ''); ?>'">[Pay Full]</span>
                    </div>
                </div>

                <div style="display: flex; gap: 12px; margin-top: 1.5rem;">
                    <a href="payment_booking.php" class="btn btn-outline"><i class="fas fa-times"></i> Cancel</a>
                    <button type="submit" class="btn btn-gold" id="payBtn" disabled>
                        <i class="fas fa-lock"></i> Confirm & Pay
                    </button>
                </div>
            </form>
            <?php endif; ?>

        </div>
    </div>
    <?php endif; ?>

    <!-- ═══ ALL BOOKINGS LIST ═══ -->
    <div class="cust-card">
        <div class="cust-card-header">
            <h2><i class="fas fa-list"></i> My Bookings</h2>
            <span style="font-size: 0.85rem; color: var(--muted);"><?php echo count($all_bookings); ?> booking(s)</span>
        </div>
        <div class="cust-card-body" style="padding: 0;">
            <?php if (empty($all_bookings)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-calendar-times"></i></div>
                    <h3>No Bookings Yet</h3>
                    <p>You haven't made any reservations yet. Start by booking your stay!</p>
                    <a href="../public/booking.php" class="btn btn-gold" style="margin-top: 1rem;">
                        <i class="fas fa-calendar-plus"></i> Book Now
                    </a>
                </div>
            <?php else: ?>
                <table class="cust-table">
                    <thead>
                        <tr>
                            <th>Booking</th>
                            <th>Facility</th>
                            <th>Dates</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_bookings as $b):
                            $remaining = $b['total_amount'] - $b['paid_so_far'];
                        ?>
                        <tr>
                            <td>
                                <strong>#<?php echo $b['booking_id']; ?></strong><br>
                                <span style="font-size: 0.75rem; color: var(--muted);"><?php echo date('M d, Y', strtotime($b['created_at'])); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($b['facility_name']); ?></td>
                            <td>
                                <?php echo date('M d', strtotime($b['check_in_date'])); ?> – <?php echo date('M d, Y', strtotime($b['check_out_date'])); ?>
                            </td>
                            <td>
                                <strong>₱<?php echo number_format($b['total_amount'], 0); ?></strong>
                                <?php if ($b['paid_so_far'] > 0): ?>
                                    <br><span style="font-size: 0.75rem; color: var(--green);">Paid: ₱<?php echo number_format($b['paid_so_far'], 0); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><span class="pill pill-<?php echo strtolower($b['booking_status']); ?>"><?php echo $b['booking_status']; ?></span></td>
                            <td><span class="pill pill-<?php echo strtolower($b['payment_status']); ?>"><?php echo $b['payment_status']; ?></span></td>
                            <td>
                                <?php if ($b['booking_status'] === 'Cancelled'): ?>
                                    <?php if (!empty($b['cancellation_id'])): ?>
                                        <a href="../pages/refund_receipt.php?cancellation_id=<?php echo (int)$b['cancellation_id']; ?>" class="btn btn-sm btn-outline" style="margin-bottom:4px;color:var(--red);border-color:#fecaca;">
                                            <i class="fas fa-file-invoice-dollar"></i> Refund Receipt
                                        </a><br>
                                    <?php endif; ?>
                                    <span style="font-size: 0.75rem; color: var(--red);"><i class="fas fa-ban"></i> Cancelled</span>
                                <?php elseif ($b['booking_status'] === 'Completed'): ?>
                                    <?php if ($b['latest_payment_id']): ?>
                                        <a href="../pages/receipt.php?payment_id=<?php echo $b['latest_payment_id']; ?>" class="btn btn-sm btn-outline" style="margin-bottom:4px;">
                                            <i class="fas fa-receipt"></i> Receipt
                                        </a><br>
                                    <?php endif; ?>
                                    <span style="font-size: 0.75rem; color: var(--green);"><i class="fas fa-check-circle"></i> Completed</span>
                                <?php elseif ($b['payment_status'] === 'Paid'): ?>
                                    <?php if ($b['latest_payment_id']): ?>
                                        <a href="../pages/receipt.php?payment_id=<?php echo $b['latest_payment_id']; ?>" class="btn btn-sm btn-outline" style="margin-bottom:4px;">
                                            <i class="fas fa-receipt"></i> Receipt
                                        </a><br>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-sm btn-outline cancel-booking-btn"
                                            style="color:var(--red);border-color:#fecaca;background:#fef2f2;"
                                            data-booking-id="<?php echo $b['booking_id']; ?>"
                                            data-facility="<?php echo htmlspecialchars($b['facility_name']); ?>"
                                            data-dates="<?php echo date('M d', strtotime($b['check_in_date'])); ?> – <?php echo date('M d, Y', strtotime($b['check_out_date'])); ?>"
                                            data-amount="<?php echo number_format($b['total_amount'], 0); ?>"
                                            data-paid="<?php echo number_format($b['paid_so_far'], 0); ?>">
                                        <i class="fas fa-ban"></i> Cancel
                                    </button>
                                <?php elseif ($b['paid_so_far'] > 0): ?>
                                    <?php if ($b['latest_payment_id']): ?>
                                        <a href="../pages/receipt.php?payment_id=<?php echo $b['latest_payment_id']; ?>" class="btn btn-sm btn-outline" style="margin-bottom:4px;">
                                            <i class="fas fa-receipt"></i> Receipt
                                        </a><br>
                                    <?php endif; ?>
                                    <a href="payment_booking.php?booking_id=<?php echo $b['booking_id']; ?>" class="btn btn-sm btn-gold" style="margin-bottom:4px;">
                                        <i class="fas fa-credit-card"></i> Pay
                                    </a><br>
                                    <button type="button" class="btn btn-sm btn-outline cancel-booking-btn"
                                            style="color:var(--red);border-color:#fecaca;background:#fef2f2;"
                                            data-booking-id="<?php echo $b['booking_id']; ?>"
                                            data-facility="<?php echo htmlspecialchars($b['facility_name']); ?>"
                                            data-dates="<?php echo date('M d', strtotime($b['check_in_date'])); ?> – <?php echo date('M d, Y', strtotime($b['check_out_date'])); ?>"
                                            data-amount="<?php echo number_format($b['total_amount'], 0); ?>"
                                            data-paid="<?php echo number_format($b['paid_so_far'], 0); ?>">
                                        <i class="fas fa-ban"></i> Cancel
                                    </button>
                                <?php else: ?>
                                    <a href="payment_booking.php?booking_id=<?php echo $b['booking_id']; ?>" class="btn btn-sm btn-gold" style="margin-bottom:4px;">
                                        <i class="fas fa-credit-card"></i> Pay
                                    </a><br>
                                    <button type="button" class="btn btn-sm btn-outline cancel-booking-btn"
                                            style="color:var(--red);border-color:#fecaca;background:#fef2f2;"
                                            data-booking-id="<?php echo $b['booking_id']; ?>"
                                            data-facility="<?php echo htmlspecialchars($b['facility_name']); ?>"
                                            data-dates="<?php echo date('M d', strtotime($b['check_in_date'])); ?> – <?php echo date('M d, Y', strtotime($b['check_out_date'])); ?>"
                                            data-amount="<?php echo number_format($b['total_amount'], 0); ?>"
                                            data-paid="0">
                                        <i class="fas fa-ban"></i> Cancel
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Cancel Booking Modal -->
<div id="cancelBookingModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; padding:20px;">
    <div style="background:#fff; border-radius:10px; width:100%; max-width:500px; max-height:90vh; overflow-y:auto; box-shadow:0 10px 40px rgba(0,0,0,0.15);">
        <!-- Header -->
        <div style="display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid #eee;">
            <h3 style="margin:0; font-family:'Josefin Sans',sans-serif; font-size:16px; font-weight:700; color:#1a3a1a;">Cancel Booking</h3>
            <button type="button" onclick="document.getElementById('cancelBookingModal').style.display='none';" style="background:none; border:none; font-size:22px; color:#999; cursor:pointer; line-height:1; padding:0;">&times;</button>
        </div>
        <form action="../logic/cancel_booking_process.php" method="POST" id="cancelBookingForm">
            <div style="padding:20px;">
                <input type="hidden" name="booking_id" id="cancel_booking_id" value=""/>

                <!-- Booking Summary -->
                <div style="background:#f9fafb; padding:12px; border-radius:8px; margin-bottom:16px; border:1px solid #e5e7eb;">
                    <p style="margin:0 0 4px 0; font-size:13px; color:#6b7280;">Booking Summary</p>
                    <p style="margin:0; font-weight:600; color:#2F3D2E;" id="cancel_booking_label"></p>
                    <p style="margin:2px 0 0 0; font-size:13px; color:#6b7280;" id="cancel_booking_dates"></p>
                    <div style="display:flex; gap:24px; margin-top:8px; font-size:13px;">
                        <span style="color:#6b7280;">Total: <strong id="cancel_booking_amount" style="color:#2F3D2E;"></strong></span>
                        <span style="color:#6b7280;">Paid: <strong id="cancel_booking_paid" style="color:#16a34a;"></strong></span>
                    </div>
                </div>

                <!-- Refund notice (shown when customer has paid) -->
                <div id="cancel_refund_warning" style="display:none; background:#fef3c7; border:1px solid #fde68a; border-radius:8px; padding:12px; margin-bottom:16px;">
                    <p style="margin:0; font-size:13px; color:#92400e; line-height:1.5;">
                        <i class="fas fa-exclamation-triangle"></i> <strong>Refund Notice:</strong> Since you have already paid, the paid amount will be marked as <strong>refunded</strong> and a refund receipt will be generated. Please contact the resort to arrange your refund.
                    </p>
                </div>

                <!-- No-refund info (shown when nothing was paid) -->
                <div id="cancel_no_refund_info" style="display:none; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:12px; margin-bottom:16px;">
                    <p style="margin:0; font-size:13px; color:#166534; line-height:1.5;">
                        <i class="fas fa-info-circle"></i> <strong>No refund needed:</strong> This booking has no payments, so no refund will be processed.
                    </p>
                </div>

                <h4 style="margin:0 0 10px 0; color:#2F3D2E; border-bottom:1px solid #eee; padding-bottom:5px;">Cancellation Details</h4>

                <div style="margin-bottom:10px;">
                    <label style="display:block; margin-bottom:5px; font-weight:500;">Reason <span style="color:#dc2626;">*</span></label>
                    <select name="reason_id" required style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; font-family:'Lora',serif; font-size:14px;">
                        <option value="">-- Select a reason --</option>
                        <?php foreach ($cancellation_reasons as $reason): ?>
                            <option value="<?php echo $reason['reason_id']; ?>"><?php echo htmlspecialchars($reason['reason_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label style="display:block; margin-bottom:5px; font-weight:500;">Additional Notes <span style="color:#9ca3af; font-weight:400;">(optional)</span></label>
                    <textarea name="notes" rows="2" placeholder="Any additional details..." style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; resize:vertical; font-family:'Lora',serif; font-size:14px;"></textarea>
                </div>
            </div>
            <!-- Footer -->
            <div style="display:flex; justify-content:flex-end; gap:10px; padding:14px 20px; border-top:1px solid #eee; background:#fafafa;">
                <button type="button" onclick="document.getElementById('cancelBookingModal').style.display='none';" style="background:#e5e7eb; color:#374151; border:none; border-radius:8px; padding:10px 24px; font-family:'Josefin Sans',sans-serif; font-weight:700; font-size:0.75rem; letter-spacing:1.5px; text-transform:uppercase; cursor:pointer;">Keep Booking</button>
                <button type="submit" id="cancelSubmitBtn" style="background:#c0392b; color:#fff; border:none; border-radius:8px; padding:10px 24px; font-family:'Josefin Sans',sans-serif; font-weight:700; font-size:0.75rem; letter-spacing:1.5px; text-transform:uppercase; cursor:pointer;">
                    <i class="fas fa-ban"></i> Cancel Booking
                </button>
            </div>
        </form>
    </div>
</div>

<!-- FOOTER -->
<footer>
    <div class="footer-image">
        <img src="../assets/images/westfarm1.jpg" alt="WestFarm">
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
        <a href="../public/booking.php">Book Now</a>
        <a href="profile.php">My Profile</a>
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
function selectPayment(id) {
    document.querySelectorAll('.payment-method-card').forEach(c => c.classList.remove('selected'));
    event.currentTarget.classList.add('selected');
    document.getElementById('paymentMethodInput').value = id;
    document.getElementById('payBtn').disabled = false;
}

// Form validation
document.getElementById('paymentForm')?.addEventListener('submit', function(e) {
    const method = document.getElementById('paymentMethodInput').value;
    const amount = parseFloat(document.getElementById('paymentAmount').value);
    if (!method) {
        e.preventDefault();
        alert('Please select a payment method.');
        return;
    }
    if (!amount || amount <= 0) {
        e.preventDefault();
        alert('Please enter a valid payment amount.');
        return;
    }
    document.getElementById('payBtn').disabled = true;
    document.getElementById('payBtn').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
});

// ── Cancel Booking Modal ──────────────────────────────────────
(function() {
    var cancelModal = document.getElementById('cancelBookingModal');
    if (!cancelModal) return;

    // Open modal
    document.querySelectorAll('.cancel-booking-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var d = this.dataset;
            var paidVal = parseFloat(d.paid.replace(/,/g, '')) || 0;

            document.getElementById('cancel_booking_id').value = d.bookingId;
            document.getElementById('cancel_booking_label').textContent = 'Booking #' + d.bookingId + ' (' + d.facility + ')';
            document.getElementById('cancel_booking_dates').textContent = d.dates;
            document.getElementById('cancel_booking_amount').textContent = '₱' + d.amount;
            document.getElementById('cancel_booking_paid').textContent = '₱' + d.paid;

            document.getElementById('cancel_refund_warning').style.display = paidVal > 0 ? 'block' : 'none';
            document.getElementById('cancel_no_refund_info').style.display = paidVal > 0 ? 'none' : 'block';

            document.getElementById('cancelBookingForm').reset();
            document.getElementById('cancel_booking_id').value = d.bookingId;

            cancelModal.style.display = 'flex';
        });
    });

    // Close modal on overlay click (outside the modal panel)
    cancelModal.addEventListener('click', function(e) {
        if (e.target === cancelModal) {
            cancelModal.style.display = 'none';
        }
    });

    // Loading state on submit
    var cancelForm = document.getElementById('cancelBookingForm');
    if (cancelForm) {
        cancelForm.addEventListener('submit', function() {
            var btn = document.getElementById('cancelSubmitBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        });
    }
})();
</script>

</body>
</html>
