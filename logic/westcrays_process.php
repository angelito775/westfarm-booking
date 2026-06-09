<?php
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit();
}

require_once '../config/db_connection.php';

// ── Collect input ──
$weight_kg       = floatval($_POST['weight_kg'] ?? 0);
$price_per_kg    = floatval($_POST['price_per_kg'] ?? 120);
$pickup_date_raw = trim($_POST['pickup_date'] ?? '');
$pickup_time_raw = trim($_POST['pickup_time'] ?? '');
$payment_method  = trim($_POST['payment_method'] ?? '');       // GCash, Maya, Cash, etc.
$pay_now         = isset($_POST['pay_now']) ? true : false;    // whether customer is paying now
$amount_paid     = floatval($_POST['amount_paid'] ?? 0);       // partial or full amount

// ── Validate ──
$errors = [];

if ($weight_kg <= 0) {
    $errors[] = 'Weight must be greater than 0 kg.';
}
if ($weight_kg > 100) {
    $errors[] = 'Maximum order is 100 kg. For bulk orders, please contact the resort directly.';
}
if (empty($pickup_date_raw)) {
    $errors[] = 'Please select a pickup date.';
}
if (empty($pickup_time_raw)) {
    $errors[] = 'Please select a pickup time window.';
}

// If paying now, validate payment method and amount
if ($pay_now) {
    if (empty($payment_method)) {
        $errors[] = 'Please select a payment method.';
    }
    if ($amount_paid <= 0) {
        $errors[] = 'Payment amount must be greater than 0.';
    }
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit();
}

// ── Calculate total ──
$total_amount = round($weight_kg * $price_per_kg, 2);

// If paying, amount_paid cannot exceed total
if ($pay_now && $amount_paid > $total_amount) {
    $amount_paid = $total_amount;
}

// ── Resolve customer_id ──
$customer_id = null;

if (isset($_SESSION['user_id']) && $_SESSION['user_type_id'] == 2) {
    $customer_id = $_SESSION['user_id'];
} else {
    try {
        $stmt_g = $pdo->prepare("SELECT user_id FROM users WHERE email = 'guest@westfarm.local' LIMIT 1");
        $stmt_g->execute();
        $guest = $stmt_g->fetch();
        if ($guest) {
            $customer_id = $guest['user_id'];
        } else {
            $stmt_c = $pdo->prepare("INSERT INTO users (user_type_id, user_status_id, email, password) VALUES (2, 1, 'guest@westfarm.local', ?)");
            $stmt_c->execute([password_hash('guest_no_login', PASSWORD_DEFAULT)]);
            $customer_id = $pdo->lastInsertId();
            $stmt_p = $pdo->prepare("INSERT INTO user_profiles (user_id, first_name, last_name) VALUES (?, 'Guest', 'Customer')");
            $stmt_p->execute([$customer_id]);
        }
    } catch (PDOException $e) {
        error_log('westcrays_process guest user error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'System error. Please try again.']);
        exit();
    }
}

// ── Resolve status_id ──
$status_id = 1;
$stmt_s = $pdo->prepare("SELECT status_id FROM order_statuses ORDER BY status_id ASC LIMIT 1");
$stmt_s->execute();
$row_s = $stmt_s->fetch();
if ($row_s) {
    $status_id = $row_s['status_id'];
}

// ── Determine payment status based on amount paid ──
// Payment status IDs: 1=Unpaid, 2=Partial, 3=Paid, 4=Refunded
if (!$pay_now || $amount_paid <= 0) {
    $payment_status_id = 1; // Unpaid
} elseif ($amount_paid >= $total_amount) {
    $payment_status_id = 3; // Paid
} else {
    $payment_status_id = 2; // Partial
}

// ── Resolve payment_method_id ──
$payment_method_id = null;
if ($pay_now && !empty($payment_method)) {
    $stmt_pm = $pdo->prepare("SELECT payment_method_id FROM payment_methods WHERE method_name = ? AND is_active = 1 LIMIT 1");
    $stmt_pm->execute([$payment_method]);
    $pm_row = $stmt_pm->fetch();
    if ($pm_row) {
        $payment_method_id = $pm_row['payment_method_id'];
    } else {
        // Fallback to Cash (id=1)
        $payment_method_id = 1;
    }
}

// ── Build pickup datetime ──
$time_map = [
    'Morning (7AM – 11AM)'  => '09:00:00',
    'Noon (11AM – 2PM)'     => '12:00:00',
    'Afternoon (2PM – 6PM)' => '15:00:00',
    'Evening (6PM – 9PM)'   => '19:00:00',
];
$time_sql = $time_map[$pickup_time_raw] ?? '09:00:00';
$pickup_datetime = $pickup_date_raw . ' ' . $time_sql;

try {
    $pdo->beginTransaction();

    // ── Check if payment columns exist ──
    $has_payment_cols = false;
    try {
        $pdo->query("SELECT amount_paid FROM crayfish_orders LIMIT 1");
        $has_payment_cols = true;
    } catch (PDOException $e) {
        $has_payment_cols = false;
    }

    // ── Insert order ──
    if ($has_payment_cols) {
        $stmt = $pdo->prepare(
            "INSERT INTO crayfish_orders
                (customer_id, status_id, quantity_kg, price_per_kg, total_amount,
                 payment_status_id, payment_method_id, amount_paid, pickup_date)
             VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $customer_id, $status_id, $weight_kg, $price_per_kg, $total_amount,
            $payment_status_id, $payment_method_id, $pay_now ? $amount_paid : 0, $pickup_datetime
        ]);
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO crayfish_orders
                (customer_id, status_id, quantity_kg, price_per_kg, total_amount, pickup_date)
             VALUES
                (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $customer_id, $status_id, $weight_kg, $price_per_kg, $total_amount, $pickup_datetime
        ]);
    }

    $order_id = $pdo->lastInsertId();

    // If paying now, create a crayfish_payments record
    if ($pay_now && $amount_paid > 0 && $payment_method_id) {
        $stmt_pay = $pdo->prepare(
            "INSERT INTO crayfish_payments (order_id, payment_method_id, amount_paid, transaction_id)
             VALUES (?, ?, ?, ?)"
        );
        $txn_id = 'WC-' . $order_id . '-' . time();
        $stmt_pay->execute([$order_id, $payment_method_id, $amount_paid, $txn_id]);
    }

    $pdo->commit();

    echo json_encode([
        'success'         => true,
        'order_id'        => $order_id,
        'total'           => $total_amount,
        'amount_paid'     => $pay_now ? $amount_paid : 0,
        'remaining'       => $pay_now ? ($total_amount - $amount_paid) : $total_amount,
        'payment_status'  => $pay_now ? ($amount_paid >= $total_amount ? 'Paid' : ($amount_paid > 0 ? 'Partial' : 'Unpaid')) : 'Unpaid',
        'weight_kg'       => $weight_kg,
        'pickup_date'     => $pickup_date_raw,
        'pickup_time'     => $pickup_time_raw,
        'customer_id'     => $customer_id,
    ]);
    exit();

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('westcrays_process insert error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to place order. Please try again.']);
    exit();
}
