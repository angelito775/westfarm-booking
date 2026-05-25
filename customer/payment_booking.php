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
            (SELECT COALESCE(SUM(amount_paid), 0) FROM payments WHERE booking_id = b.booking_id) AS paid_so_far
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
if (isset($_GET['success']) && $_GET['success'] === 'payment_complete') {
    $success_msg = 'Payment processed successfully! Thank you for your payment.';
}
if (isset($_GET['error'])) {
    $errors = [
        'not_logged_in'    => 'Please sign in to make a payment.',
        'invalid_booking'  => 'Invalid booking selected.',
        'booking_not_found'=> 'Booking not found or does not belong to your account.',
        'invalid_method'   => 'Please select a valid payment method.',
        'already_paid'     => 'This booking has already been fully paid.',
        'refunded'         => 'This booking has been refunded and cannot be paid.',
        'invalid_amount'   => 'Please enter a valid payment amount.',
        'amount_exceeds'   => 'Payment amount cannot exceed the total booking amount.',
        'system_error'     => 'An error occurred processing your payment. Please try again.',
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
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_msg); ?></div>
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
                                <?php if ($b['payment_status'] !== 'Paid' && $b['booking_status'] !== 'Cancelled'): ?>
                                    <a href="payment_booking.php?booking_id=<?php echo $b['booking_id']; ?>" class="btn btn-sm btn-gold">
                                        <i class="fas fa-credit-card"></i> Pay
                                    </a>
                                <?php elseif ($b['payment_status'] === 'Paid'): ?>
                                    <span style="font-size: 0.75rem; color: var(--green);"><i class="fas fa-check-circle"></i> Settled</span>
                                <?php else: ?>
                                    <span style="font-size: 0.75rem; color: var(--muted);">—</span>
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
</script>

</body>
</html>
