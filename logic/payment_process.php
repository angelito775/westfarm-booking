<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type_id'] != 2) {
    header("Location: ../customer/payment_booking.php?error=not_logged_in");
    exit();
}

require_once '../config/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../customer/payment_booking.php?error=invalid_request");
    exit();
}

$booking_id      = (int)($_POST['booking_id'] ?? 0);
$payment_method  = (int)($_POST['payment_method'] ?? 0);
$amount_input    = trim($_POST['amount'] ?? '');

if ($booking_id <= 0) {
    header("Location: ../customer/payment_booking.php?error=invalid_booking");
    exit();
}

if ($payment_method <= 0) {
    header("Location: ../customer/payment_booking.php?booking_id={$booking_id}&error=invalid_method");
    exit();
}

try {
    // Verify booking belongs to this customer and is in a payable state
    $stmt = $pdo->prepare(
        "SELECT b.booking_id, b.total_amount, b.booking_status_id, b.payment_status_id,
                bs.status_name AS booking_status_name, ps.status_name AS payment_status_name
         FROM bookings b
         JOIN booking_statuses bs ON b.booking_status_id = bs.booking_status_id
         JOIN payment_statuses ps ON b.payment_status_id = ps.payment_status_id
         WHERE b.booking_id = ? AND b.customer_id = ?"
    );
    $stmt->execute([$booking_id, $_SESSION['user_id']]);
    $booking = $stmt->fetch();

    if (!$booking) {
        header("Location: ../customer/payment_booking.php?error=booking_not_found");
        exit();
    }

    if ($booking['payment_status_name'] === 'Paid') {
        header("Location: ../customer/payment_booking.php?booking_id={$booking_id}&error=already_paid");
        exit();
    }

    if ($booking['payment_status_name'] === 'Refunded') {
        header("Location: ../customer/payment_booking.php?booking_id={$booking_id}&error=refunded");
        exit();
    }

    // Validate amount
    $max_amount = (float)$booking['total_amount'];
    if ($amount_input === '' || !is_numeric($amount_input) || $amount_input <= 0) {
        header("Location: ../customer/payment_booking.php?booking_id={$booking_id}&error=invalid_amount");
        exit();
    }
    $amount = (float)$amount_input;
    if ($amount > $max_amount) {
        header("Location: ../customer/payment_booking.php?booking_id={$booking_id}&error=amount_exceeds");
        exit();
    }

    // Determine new payment status based on amount
    $new_payment_status = 'Unpaid';
    if ($amount >= $max_amount) {
        $new_payment_status = 'Paid';
    } elseif ($amount > 0) {
        $new_payment_status = 'Partial';
    }

    $pdo->beginTransaction();

    // Resolve payment status ID
    $stmt = $pdo->prepare("SELECT payment_status_id FROM payment_statuses WHERE status_name = ?");
    $stmt->execute([$new_payment_status]);
    $ps_id = $stmt->fetchColumn();

    // Resolve booking status — if confirmed and partial/paid, keep confirmed
    $new_booking_status = $booking['booking_status_id'];
    if ($new_payment_status === 'Paid' && $booking['booking_status_name'] === 'Pending') {
        // Auto-confirm on full payment
        $stmt = $pdo->prepare("SELECT booking_status_id FROM booking_statuses WHERE status_name = 'Confirmed'");
        $stmt->execute();
        $new_booking_status = $stmt->fetchColumn();
    }

    $transaction_id = 'PAY-' . $booking_id . '-' . time();

    // Insert payment record
    $stmt = $pdo->prepare(
        "INSERT INTO payments (booking_id, payment_method_id, amount_paid, transaction_id)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$booking_id, $payment_method, $amount, $transaction_id]);

    // Update booking payment status and booking status
    $stmt = $pdo->prepare(
        "UPDATE bookings SET booking_status_id = ?, payment_status_id = ? WHERE booking_id = ?"
    );
    $stmt->execute([$new_booking_status, $ps_id, $booking_id]);

    $pdo->commit();

    header("Location: ../customer/payment_booking.php?booking_id={$booking_id}&success=payment_complete");
    exit();

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Payment error: ' . $e->getMessage());
    header("Location: ../customer/payment_booking.php?booking_id={$booking_id}&error=system_error");
    exit();
}
