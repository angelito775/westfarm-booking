<?php
session_start();

// Security check — only logged-in customers allowed
if (!isset($_SESSION['user_id']) || $_SESSION['user_type_id'] != 2) {
    header("Location: ../pages/login.php");
    exit();
}

require_once '../config/db_connection.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../customer/payment_booking.php?error=invalid_request");
    exit();
}

$booking_id = (int)($_POST['booking_id'] ?? 0);
$reason_id  = (int)($_POST['reason_id'] ?? 0);
$notes      = trim($_POST['notes'] ?? '');
$customer_id = $_SESSION['user_id'];

if ($booking_id <= 0) {
    header("Location: ../customer/payment_booking.php?error=invalid_booking");
    exit();
}

try {
    $pdo->beginTransaction();

    // ── Verify the booking belongs to this customer ─────────────
    $stmt = $pdo->prepare(
        "SELECT b.booking_id, b.booking_status_id, b.payment_status_id, b.total_amount,
                bs.status_name AS booking_status_name, ps.status_name AS payment_status_name
         FROM bookings b
         JOIN booking_statuses bs ON b.booking_status_id = bs.booking_status_id
         JOIN payment_statuses ps ON b.payment_status_id = ps.payment_status_id
         WHERE b.booking_id = ? AND b.customer_id = ?"
    );
    $stmt->execute([$booking_id, $customer_id]);
    $booking = $stmt->fetch();

    if (!$booking) {
        $pdo->rollBack();
        header("Location: ../customer/payment_booking.php?error=booking_not_found");
        exit();
    }

    // ── Only Pending, Confirmed, or Paid bookings can be cancelled
    //    Completed bookings cannot be cancelled
    if ($booking['booking_status_name'] === 'Completed') {
        $pdo->rollBack();
        header("Location: ../customer/payment_booking.php?error=already_completed");
        exit();
    }

    // Already cancelled?
    if ($booking['booking_status_name'] === 'Cancelled') {
        $pdo->rollBack();
        header("Location: ../customer/payment_booking.php?error=already_cancelled");
        exit();
    }

    // ── Calculate refund amount ─────────────────────────────────
    // If the booking was Paid or Partial, the paid amount is eligible for refund
    $paid_so_far = 0;
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount_paid), 0) FROM payments WHERE booking_id = ?");
    $stmt->execute([$booking_id]);
    $paid_so_far = (float)$stmt->fetchColumn();

    // Refund = amount already paid (customer gets back what they paid)
    $refund_amount = $paid_so_far;

    // ── Resolve reason_id (default to 6 "Other" if not provided) ──
    if ($reason_id <= 0) {
        $reason_id = 6; // "Other"
    }
    // Validate reason exists
    $stmt = $pdo->prepare("SELECT reason_id FROM cancellation_reasons WHERE reason_id = ?");
    $stmt->execute([$reason_id]);
    if (!$stmt->fetch()) {
        $reason_id = 6;
    }

    // ── Record the cancellation ────────────────────────────────
    $stmt = $pdo->prepare(
        "INSERT INTO booking_cancellations (booking_id, reason_id, cancelled_by_user_id, refund_amount, additional_notes)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([$booking_id, $reason_id, $customer_id, $refund_amount, $notes ?: null]);
    $cancellation_id = $pdo->lastInsertId();

    // ── Update booking status to Cancelled (3) ─────────────────
    $stmt = $pdo->prepare("UPDATE bookings SET booking_status_id = 3 WHERE booking_id = ?");
    $stmt->execute([$booking_id]);

    // ── Update payment status ──────────────────────────────────
    // If there was any payment, mark as Refunded (4); otherwise Unpaid (1)
    if ($paid_so_far > 0) {
        $stmt = $pdo->prepare("UPDATE bookings SET payment_status_id = 4 WHERE booking_id = ?");
        $stmt->execute([$booking_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE bookings SET payment_status_id = 1 WHERE booking_id = ?");
        $stmt->execute([$booking_id]);
    }

    $pdo->commit();

    // If there was a payment, redirect to refund receipt; otherwise back to bookings
    if ($paid_so_far > 0) {
        header("Location: ../pages/refund_receipt.php?cancellation_id=" . $cancellation_id);
    } else {
        header("Location: ../customer/payment_booking.php?success=booking_cancelled");
    }
    exit();

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('cancel_booking_process error: ' . $e->getMessage());
    header("Location: ../customer/payment_booking.php?error=cancel_failed");
    exit();
}
