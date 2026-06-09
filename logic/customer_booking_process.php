<?php
session_start();

// Security check — only logged-in customers allowed
if (!isset($_SESSION['user_id']) || $_SESSION['user_type_id'] != 2) {
    header("Location: ../public/booking.php?booking_error=not_logged_in");
    exit();
}

require_once '../config/db_connection.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../public/booking.php?booking_error=invalid_request");
    exit();
}

$action = $_POST['action'] ?? '';

if ($action !== 'customer_create_booking') {
    header("Location: ../public/booking.php?booking_error=invalid_action");
    exit();
}

// ── Collect & validate input ──────────────────────────────────
$facility_id    = (int)($_POST['facility_id'] ?? 0);
$check_in_date  = $_POST['check_in_date'] ?? '';
$check_out_date = $_POST['check_out_date'] ?? '';
$num_guests     = (int)($_POST['num_guests'] ?? 1);
$customer_id    = $_SESSION['user_id'];

// Basic validation
if ($facility_id <= 0) {
    header("Location: ../public/booking.php?booking_error=invalid_facility");
    exit();
}
if (empty($check_in_date) || empty($check_out_date)) {
    header("Location: ../public/booking.php?booking_error=empty_dates");
    exit();
}

// Validate date order
$check_in_ts  = strtotime($check_in_date);
$check_out_ts = strtotime($check_out_date);
if ($check_in_ts === false || $check_out_ts === false || $check_out_ts < $check_in_ts) {
    header("Location: ../public/booking.php?booking_error=invalid_dates");
    exit();
}

// Validate category restrictions for same-day bookings
// Get facility's category
$stmt = $pdo->prepare("SELECT f.category_id, c.name FROM facilities f LEFT JOIN categories c ON f.category_id = c.category_id WHERE f.facility_id = ?");
$stmt->execute([$facility_id]);
$facilityCategory = $stmt->fetch();

if ($check_in_date === $check_out_date) {
    // Same-day bookings only allowed for specific categories
    $allowedSameDayCategories = ['Cottage', 'Pool', 'Event Hall'];
    
    if ($facilityCategory && !in_array($facilityCategory['name'], $allowedSameDayCategories)) {
        header("Location: ../public/booking.php?booking_error=invalid_dates");
        exit();
    }
}

try {
    $pdo->beginTransaction();

    // ── Verify facility exists and get price ────────────────────
    $stmt = $pdo->prepare("SELECT base_price, name FROM facilities WHERE facility_id = ?");
    $stmt->execute([$facility_id]);
    $facility = $stmt->fetch();

    if (!$facility) {
        $pdo->rollBack();
        header("Location: ../public/booking.php?booking_error=invalid_facility");
        exit();
    }

    $price_per_night = (float)$facility['base_price'];

    // Calculate number of nights
    $check_in_dt  = new DateTime($check_in_date);
    $check_out_dt = new DateTime($check_out_date);
    $interval     = $check_in_dt->diff($check_out_dt);
    $nights       = $interval->days;

    if ($nights < 1) {
        $nights = 1;
    }

    $total_amount = $price_per_night * $nights;

    // ── Double-check no overlapping booking exists ─────────────
    $stmt = $pdo->prepare(
        "SELECT 1 FROM booking_items bi
         JOIN bookings b ON bi.booking_id = b.booking_id
         WHERE bi.facility_id = ?
           AND DATE(bi.check_in_date) < ?
           AND DATE(bi.check_out_date) > ?
           AND b.booking_status_id NOT IN (
               SELECT booking_status_id FROM booking_statuses WHERE status_name IN ('Cancelled', 'Refunded')
           )
         LIMIT 1"
    );
    $stmt->execute([
        $facility_id,
        $check_out_date,
        $check_in_date
    ]);
    if ($stmt->fetch()) {
        $pdo->rollBack();
        header("Location: ../public/booking.php?booking_error=facility_unavailable");
        exit();
    }

    // ── Resolve status IDs ──────────────────────────────────────
    // Booking status: Pending (1)
    $booking_status_id = 1;
    // Payment status: Unpaid (1)
    $payment_status_id = 1;

    // ── Insert booking ─────────────────────────────────────────
    $stmt = $pdo->prepare(
        "INSERT INTO bookings (customer_id, booking_status_id, payment_status_id, total_amount)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$customer_id, $booking_status_id, $payment_status_id, $total_amount]);
    $booking_id = $pdo->lastInsertId();

    // ── Insert booking item ────────────────────────────────────
    $stmt = $pdo->prepare(
        "INSERT INTO booking_items (booking_id, facility_id, check_in_date, check_out_date, price_at_booking)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $booking_id,
        $facility_id,
        $check_in_date,
        $check_out_date,
        $price_per_night
    ]);

    $pdo->commit();

    // Redirect back with success
    header("Location: ../public/booking.php?booking_success=1");
    exit();

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('customer_booking_process error: ' . $e->getMessage());
    header("Location: ../public/booking.php?booking_error=booking_failed");
    exit();
}
