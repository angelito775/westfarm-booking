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
$weight_kg    = floatval($_POST['weight_kg'] ?? 0);
$price_per_kg = floatval($_POST['price_per_kg'] ?? 120);
$pickup_date_raw = trim($_POST['pickup_date'] ?? '');
$pickup_time_raw = trim($_POST['pickup_time'] ?? '');

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

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit();
}

// ── Calculate total ──
$total_amount = round($weight_kg * $price_per_kg, 2);

// ── Resolve customer_id ──
// If logged in as customer, use their ID. Otherwise we need a guest placeholder.
// The DB requires customer_id NOT NULL, so we find or create a guest user.
$customer_id = null;

if (isset($_SESSION['user_id']) && $_SESSION['user_type_id'] == 2) {
    $customer_id = $_SESSION['user_id'];
} else {
    // Find or create a guest user account for guest orders
    try {
        $stmt_g = $pdo->prepare("SELECT user_id FROM users WHERE email = 'guest@westfarm.local' LIMIT 1");
        $stmt_g->execute();
        $guest = $stmt_g->fetch();
        if ($guest) {
            $customer_id = $guest['user_id'];
        } else {
            // Create guest user (type 2 = customer, status 1 = active)
            $stmt_c = $pdo->prepare("INSERT INTO users (user_type_id, user_status_id, email, password) VALUES (2, 1, 'guest@westfarm.local', ?)");
            $stmt_c->execute([password_hash('guest_no_login', PASSWORD_DEFAULT)]);
            $customer_id = $pdo->lastInsertId();
            // Create profile
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

// ── Resolve status_id from order_statuses ──
$status_id = 1;
try {
    $stmt_s = $pdo->prepare("SELECT status_id FROM order_statuses ORDER BY status_id ASC LIMIT 1");
    $stmt_s->execute();
    $row_s = $stmt_s->fetch();
    if ($row_s) {
        $status_id = $row_s['status_id'];
    }
} catch (PDOException $e) {
    // fall back to 1
}

// ── Build proper datetime for pickup_date ──
// pickup_date_raw = "2026-06-09", pickup_time_raw = "Morning (7AM – 11AM)"
// Map time window to a representative hour for the datetime field
$time_map = [
    'Morning (7AM – 11AM)'    => '09:00:00',
    'Noon (11AM – 2PM)'       => '12:00:00',
    'Afternoon (2PM – 6PM)'   => '15:00:00',
    'Evening (6PM – 9PM)'     => '19:00:00',
];
$time_sql = $time_map[$pickup_time_raw] ?? '09:00:00';
$pickup_datetime = $pickup_date_raw . ' ' . $time_sql;

try {
    $stmt = $pdo->prepare(
        "INSERT INTO crayfish_orders
            (customer_id, status_id, quantity_kg, price_per_kg, total_amount, pickup_date)
         VALUES
            (?, ?, ?, ?, ?, ?)"
    );

    $stmt->execute([
        $customer_id,
        $status_id,
        $weight_kg,
        $price_per_kg,
        $total_amount,
        $pickup_datetime
    ]);

    $order_id = $pdo->lastInsertId();

    echo json_encode([
        'success'       => true,
        'order_id'      => $order_id,
        'total'         => $total_amount,
        'weight_kg'     => $weight_kg,
        'pickup_date'   => $pickup_date_raw,
        'pickup_time'   => $pickup_time_raw,
        'customer_id'   => $customer_id,
    ]);
    exit();

} catch (PDOException $e) {
    error_log('westcrays_process insert error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to place order. Please try again.']);
    exit();
}
