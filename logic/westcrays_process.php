<?php
session_start();

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit();
}

require_once '../config/db_connection.php';

// ── Collect input ──
$guest_name       = trim($_POST['guest_name'] ?? '');
$guest_phone      = trim($_POST['guest_phone'] ?? '');
$delivery_address = trim($_POST['delivery_address'] ?? '');
$weight_kg        = floatval($_POST['weight_kg'] ?? 0);
$price_per_kg     = floatval($_POST['price_per_kg'] ?? 120);
$delivery_time    = trim($_POST['delivery_time'] ?? '');
$preparation      = trim($_POST['preparation'] ?? '');
$notes            = trim($_POST['notes'] ?? '');

// ── Validate ──
$errors = [];

if (empty($guest_name)) {
    $errors[] = 'Full name is required.';
}
if (empty($guest_phone)) {
    $errors[] = 'Contact number is required.';
}
if (empty($delivery_address)) {
    $errors[] = 'Delivery address is required.';
}
if ($weight_kg <= 0) {
    $errors[] = 'Weight must be greater than 0 kg.';
}
if ($weight_kg > 100) {
    $errors[] = 'Maximum order is 100 kg. For bulk orders, please contact the resort directly.';
}
if (empty($delivery_time)) {
    $errors[] = 'Preferred delivery time is required.';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit();
}

// ── Calculate total ──
$total_amount = round($weight_kg * $price_per_kg, 2);

// ── Get customer ID if logged in ──
$customer_id = null;
if (isset($_SESSION['user_id']) && $_SESSION['user_type_id'] == 2) {
    $customer_id = $_SESSION['user_id'];
}

try {
    $stmt = $pdo->prepare(
        "INSERT INTO crayfish_orders
            (customer_id, guest_name, guest_phone, delivery_address, weight_kg, price_per_kg, total_amount, delivery_time, preparation, notes, order_status)
         VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
    );

    $stmt->execute([
        $customer_id,
        $guest_name,
        $guest_phone,
        $delivery_address,
        $weight_kg,
        $price_per_kg,
        $total_amount,
        $delivery_time,
        $preparation,
        $notes
    ]);

    $order_id = $pdo->lastInsertId();

    echo json_encode([
        'success'     => true,
        'order_id'    => $order_id,
        'total'       => $total_amount,
        'weight_kg'   => $weight_kg,
        'guest_name'  => $guest_name,
        'address'     => $delivery_address,
        'delivery_time' => $delivery_time,
        'preparation' => $preparation,
        'phone'       => $guest_phone,
        'notes'       => $notes
    ]);
    exit();

} catch (PDOException $e) {
    error_log('westcrays_process error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to place order. Please try again.']);
    exit();
}
