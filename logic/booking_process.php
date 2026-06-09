<?php
session_start();

// Security check — only owners allowed
if (!isset($_SESSION['user_id']) || $_SESSION['user_type_id'] != 3) {
    header("Location: ../pages/login.php");
    exit();
}

require_once '../config/db_connection.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../owner/facilities.php?error=invalid_request");
    exit();
}

$action = $_POST['action'] ?? '';

if ($action === 'owner_add_booking') {
    // ── Collect & validate input ──────────────────────────────────
    $guest_name     = trim($_POST['guest_name'] ?? '');
    $guest_phone    = trim($_POST['guest_phone'] ?? '');
    $num_guests     = (int)($_POST['num_guests'] ?? 1);
    $check_in_date  = $_POST['check_in_date'] ?? '';
    $check_out_date = $_POST['check_out_date'] ?? '';
    $facility_id    = (int)($_POST['facility_id'] ?? 0);
    $booking_status = $_POST['booking_status'] ?? 'Pending';
    $payment_status = $_POST['payment_status'] ?? 'Unpaid';

    // Basic validation
    if ($guest_name === '') {
        header("Location: ../owner/facilities.php?error=empty_guest_name");
        exit();
    }
    if ($facility_id <= 0) {
        header("Location: ../owner/facilities.php?error=invalid_facility");
        exit();
    }
    if (empty($check_in_date) || empty($check_out_date)) {
        header("Location: ../owner/facilities.php?error=empty_dates");
        exit();
    }

    // Validate date order (allow same-day for cottages/pools/events)
    $check_in_ts  = strtotime($check_in_date);
    $check_out_ts = strtotime($check_out_date);
    if ($check_in_ts === false || $check_out_ts === false || $check_out_ts < $check_in_ts) {
        header("Location: ../owner/facilities.php?error=invalid_dates");
        exit();
    }

    // Validate payment status matches booking status
    // Completed → must be Paid
    if ($booking_status === 'Completed' && $payment_status !== 'Paid') {
        header("Location: ../owner/facilities.php?error=invalid_payment_status");
        exit();
    }
    // Pending → cannot be Paid
    if ($booking_status === 'Pending' && $payment_status === 'Paid') {
        header("Location: ../owner/facilities.php?error=invalid_payment_status");
        exit();
    }
    // Cancelled → cannot be Paid
    if ($booking_status === 'Cancelled' && $payment_status === 'Paid') {
        header("Location: ../owner/facilities.php?error=invalid_payment_status");
        exit();
    }

    try {
        $pdo->beginTransaction();

        // ── Resolve or create the customer ─────────────────────────
        // Try to find an existing customer by matching the guest name
        // against user_profiles. If not found, create a new customer user.
        $customer_id = null;

        // Split guest name into first/last for matching
        $name_parts = explode(' ', $guest_name, 2);
        $first_name = $name_parts[0];
        $last_name  = $name_parts[1] ?? '';

        // Look for an existing customer (user_type_id = 2) matching name
        $stmt = $pdo->prepare(
            "SELECT u.user_id
             FROM users u
             JOIN user_profiles up ON u.user_id = up.user_id
             WHERE u.user_type_id = 2
               AND up.first_name = ?
               AND up.last_name = ?
             LIMIT 1"
        );
        $stmt->execute([$first_name, $last_name]);
        $existing = $stmt->fetch();

        if ($existing) {
            $customer_id = $existing['user_id'];

            // Update phone if provided
            if ($guest_phone !== '') {
                $stmt = $pdo->prepare("UPDATE user_profiles SET phone_number = ? WHERE user_id = ?");
                $stmt->execute([$guest_phone, $customer_id]);
            }
        } else {
            // Create a new customer account
            // Generate a unique email placeholder
            $email = 'guest_' . uniqid() . '@westfarm.local';
            $hashed_password = password_hash('password', PASSWORD_DEFAULT);

            $stmt = $pdo->prepare(
                "INSERT INTO users (user_type_id, user_status_id, email, password) VALUES (2, 1, ?, ?)"
            );
            $stmt->execute([$email, $hashed_password]);
            $customer_id = $pdo->lastInsertId();

            // Create user profile
            $stmt = $pdo->prepare(
                "INSERT INTO user_profiles (user_id, first_name, last_name, phone_number) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$customer_id, $first_name, $last_name, $guest_phone ?: null]);
        }

        // ── Get facility price ─────────────────────────────────────
        $stmt = $pdo->prepare("SELECT base_price FROM facilities WHERE facility_id = ?");
        $stmt->execute([$facility_id]);
        $facility = $stmt->fetch();

        if (!$facility) {
            $pdo->rollBack();
            header("Location: ../owner/facilities.php?error=invalid_facility");
            exit();
        }

        $price_per_night = (float)$facility['base_price'];

        // Calculate number of nights
        $check_in_dt  = new DateTime($check_in_date);
        $check_out_dt = new DateTime($check_out_date);
        $interval     = $check_in_dt->diff($check_out_dt);
        $nights       = $interval->days;
        if ($nights < 1) {
            $nights = 1; // Same-day bookings count as 1
        }

        $total_amount = $price_per_night * $nights;

        // ── Resolve booking status ID ──────────────────────────────
        $stmt = $pdo->prepare("SELECT booking_status_id FROM booking_statuses WHERE status_name = ?");
        $stmt->execute([$booking_status]);
        $bs = $stmt->fetch();
        $booking_status_id = $bs ? $bs['booking_status_id'] : 1; // default Pending

        // ── Resolve payment status ID ──────────────────────────────
        $stmt = $pdo->prepare("SELECT payment_status_id FROM payment_statuses WHERE status_name = ?");
        $stmt->execute([$payment_status]);
        $ps = $stmt->fetch();
        $payment_status_id = $ps ? $ps['payment_status_id'] : 1; // default Unpaid

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
            header("Location: ../owner/facilities.php?error=facility_unavailable");
            exit();
        }

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

        // ── If payment status is Paid, create a payment record ─────
        if ($payment_status === 'Paid') {
            // Default to Cash (payment_method_id = 1) for manual bookings
            $stmt = $pdo->prepare(
                "INSERT INTO payments (booking_id, payment_method_id, amount_paid, transaction_id)
                 VALUES (?, 1, ?, ?)"
            );
            $transaction_id = 'MANUAL-' . $booking_id . '-' . time();
            $stmt->execute([$booking_id, $total_amount, $transaction_id]);
        } elseif ($payment_status === 'Partial') {
            // For partial, record half as paid via Cash
            $partial_amount = $total_amount / 2;
            $stmt = $pdo->prepare(
                "INSERT INTO payments (booking_id, payment_method_id, amount_paid, transaction_id)
                 VALUES (?, 1, ?, ?)"
            );
            $transaction_id = 'MANUAL-' . $booking_id . '-' . time();
            $stmt->execute([$booking_id, $partial_amount, $transaction_id]);
        }

        $pdo->commit();

        // Redirect to bookings page with success
        header("Location: ../owner/bookings.php?success=booking_created");
        exit();

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('booking_process error: ' . $e->getMessage());
        header("Location: ../owner/facilities.php?error=booking_failed");
        exit();
    }
}

// ── Update booking ─────────────────────────────────────────────
if ($action === 'owner_update_booking') {
    $booking_id     = (int)($_POST['booking_id'] ?? 0);
    $booking_status = $_POST['booking_status'] ?? 'Pending';
    $payment_status = $_POST['payment_status'] ?? 'Unpaid';
    $guest_name     = trim($_POST['guest_name'] ?? '');
    $guest_phone    = trim($_POST['guest_phone'] ?? '');

    if ($booking_id <= 0) {
        header("Location: ../owner/bookings.php?error=invalid_booking");
        exit();
    }

    // Validate payment status matches booking status
    // Completed → must be Paid
    if ($booking_status === 'Completed' && $payment_status !== 'Paid') {
        header("Location: ../owner/bookings.php?error=invalid_payment_status");
        exit();
    }
    // Pending → cannot be Paid
    if ($booking_status === 'Pending' && $payment_status === 'Paid') {
        header("Location: ../owner/bookings.php?error=invalid_payment_status");
        exit();
    }
    // Cancelled → cannot be Paid
    if ($booking_status === 'Cancelled' && $payment_status === 'Paid') {
        header("Location: ../owner/bookings.php?error=invalid_payment_status");
        exit();
    }

    try {
        $pdo->beginTransaction();

        // Verify the booking belongs to a facility owned by this owner
        $stmt = $pdo->prepare(
            "SELECT b.booking_id, b.customer_id
             FROM bookings b
             JOIN booking_items bi ON b.booking_id = bi.booking_id
             JOIN facilities f ON bi.facility_id = f.facility_id
             WHERE b.booking_id = ?
             LIMIT 1"
        );
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch();

        if (!$booking) {
            $pdo->rollBack();
            header("Location: ../owner/bookings.php?error=booking_not_found");
            exit();
        }

        // Resolve booking status ID
        $stmt = $pdo->prepare("SELECT booking_status_id FROM booking_statuses WHERE status_name = ?");
        $stmt->execute([$booking_status]);
        $bs = $stmt->fetch();
        $booking_status_id = $bs ? $bs['booking_status_id'] : 1;

        // Resolve payment status ID
        $stmt = $pdo->prepare("SELECT payment_status_id FROM payment_statuses WHERE status_name = ?");
        $stmt->execute([$payment_status]);
        $ps = $stmt->fetch();
        $payment_status_id = $ps ? $ps['payment_status_id'] : 1;

        // Update booking
        $stmt = $pdo->prepare(
            "UPDATE bookings SET booking_status_id = ?, payment_status_id = ? WHERE booking_id = ?"
        );
        $stmt->execute([$booking_status_id, $payment_status_id, $booking_id]);

        // Update guest name/phone if provided
        if ($guest_name !== '') {
            $name_parts = explode(' ', $guest_name, 2);
            $first_name = $name_parts[0];
            $last_name  = $name_parts[1] ?? '';

            $stmt = $pdo->prepare(
                "UPDATE user_profiles SET first_name = ?, last_name = ? WHERE user_id = ?"
            );
            $stmt->execute([$first_name, $last_name, $booking['customer_id']]);
        }

        if ($guest_phone !== '') {
            $stmt = $pdo->prepare("UPDATE user_profiles SET phone_number = ? WHERE user_id = ?");
            $stmt->execute([$guest_phone, $booking['customer_id']]);
        }

        // Handle payment record changes
        // If status changed to Paid and no payment record exists, create one
        if ($payment_status === 'Paid') {
            $stmt = $pdo->prepare("SELECT payment_id FROM payments WHERE booking_id = ? LIMIT 1");
            $stmt->execute([$booking_id]);
            if (!$stmt->fetch()) {
                // Get total amount from booking
                $stmt = $pdo->prepare("SELECT total_amount FROM bookings WHERE booking_id = ?");
                $stmt->execute([$booking_id]);
                $amount = $stmt->fetchColumn();

                $stmt = $pdo->prepare(
                    "INSERT INTO payments (booking_id, payment_method_id, amount_paid, transaction_id)
                     VALUES (?, 1, ?, ?)"
                );
                $transaction_id = 'MANUAL-' . $booking_id . '-' . time();
                $stmt->execute([$booking_id, $amount, $transaction_id]);
            }
        }

        $pdo->commit();
        header("Location: ../owner/bookings.php?success=status_updated");
        exit();

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('booking_process update error: ' . $e->getMessage());
        header("Location: ../owner/bookings.php?error=update_failed");
        exit();
    }
}

// ── Delete booking ─────────────────────────────────────────────
if ($action === 'owner_delete_booking') {
    $booking_id = (int)($_POST['booking_id'] ?? 0);

    if ($booking_id <= 0) {
        header("Location: ../owner/bookings.php?error=invalid_booking");
        exit();
    }

    try {
        $pdo->beginTransaction();

        // Verify the booking exists and belongs to an owner's facility
        $stmt = $pdo->prepare(
            "SELECT b.booking_id
             FROM bookings b
             JOIN booking_items bi ON b.booking_id = bi.booking_id
             JOIN facilities f ON bi.facility_id = f.facility_id
             WHERE b.booking_id = ?
             LIMIT 1"
        );
        $stmt->execute([$booking_id]);
        if (!$stmt->fetch()) {
            $pdo->rollBack();
            header("Location: ../owner/bookings.php?error=booking_not_found");
            exit();
        }

        // Delete payment records first
        $stmt = $pdo->prepare("DELETE FROM payments WHERE booking_id = ?");
        $stmt->execute([$booking_id]);

        // Delete booking items
        $stmt = $pdo->prepare("DELETE FROM booking_items WHERE booking_id = ?");
        $stmt->execute([$booking_id]);

        // Delete booking
        $stmt = $pdo->prepare("DELETE FROM bookings WHERE booking_id = ?");
        $stmt->execute([$booking_id]);

        $pdo->commit();
        header("Location: ../owner/bookings.php?success=booking_deleted");
        exit();

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('booking_process delete error: ' . $e->getMessage());
        header("Location: ../owner/bookings.php?error=delete_failed");
        exit();
    }
}

// Unknown action
header("Location: ../owner/facilities.php?error=invalid_action");
exit();
