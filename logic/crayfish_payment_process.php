<?php
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit();
}

require_once '../config/db_connection.php';

$action = $_POST['action'] ?? '';

// ── Make a payment on an existing order ──
if ($action === 'make_payment') {
    $order_id       = intval($_POST['order_id'] ?? 0);
    $payment_method = trim($_POST['payment_method'] ?? '');
    $amount_paid    = floatval($_POST['amount_paid'] ?? 0);
    $customer_id    = $_SESSION['user_id'] ?? 0;

    if ($order_id <= 0 || $amount_paid <= 0 || empty($payment_method)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'Invalid payment details.']);
        exit();
    }

    try {
        $pdo->beginTransaction();

        // Verify order belongs to this customer
        $stmt = $pdo->prepare("SELECT order_id, total_amount, COALESCE(amount_paid, 0) AS amount_paid, COALESCE(payment_status_id, 1) AS payment_status_id FROM crayfish_orders WHERE order_id = ? AND customer_id = ?");
        $stmt->execute([$order_id, $customer_id]);
        $order = $stmt->fetch();

        if (!$order) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Order not found.']);
            exit();
        }

        // Check if already fully paid
        if ($order['payment_status_id'] == 3) {
            $pdo->rollBack();
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'This order is already fully paid.']);
            exit();
        }

        // Calculate remaining
        $remaining = $order['total_amount'] - $order['amount_paid'];
        if ($amount_paid > $remaining) {
            $amount_paid = $remaining;
        }

        // Resolve payment_method_id
        $stmt_pm = $pdo->prepare("SELECT payment_method_id FROM payment_methods WHERE method_name = ? AND is_active = 1 LIMIT 1");
        $stmt_pm->execute([$payment_method]);
        $pm_row = $stmt_pm->fetch();
        $payment_method_id = $pm_row ? $pm_row['payment_method_id'] : 1;

        // Insert payment record
        $stmt_pay = $pdo->prepare(
            "INSERT INTO crayfish_payments (order_id, payment_method_id, amount_paid, transaction_id)
             VALUES (?, ?, ?, ?)"
        );
        $txn_id = 'WC-' . $order_id . '-' . time();
        $stmt_pay->execute([$order_id, $payment_method_id, $amount_paid, $txn_id]);

        // Update order totals
        $new_paid = $order['amount_paid'] + $amount_paid;
        $new_status = ($new_paid >= $order['total_amount']) ? 3 : 2; // 3=Paid, 2=Partial

        $stmt_upd = $pdo->prepare("UPDATE crayfish_orders SET amount_paid = ?, payment_status_id = ?, payment_method_id = ? WHERE order_id = ?");
        $stmt_upd->execute([$new_paid, $new_status, $payment_method_id, $order_id]);

        $pdo->commit();

        echo json_encode([
            'success'     => true,
            'order_id'    => $order_id,
            'amount_paid' => $amount_paid,
            'total_paid'  => $new_paid,
            'remaining'   => $order['total_amount'] - $new_paid,
            'status'      => $new_status == 3 ? 'Paid' : 'Partial',
        ]);
        exit();

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('crayfish_payment_process error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Payment failed. Please try again.']);
        exit();
    }
}

// ── Cancel an order ──
if ($action === 'cancel_order') {
    $order_id    = intval($_POST['order_id'] ?? 0);
    $customer_id = $_SESSION['user_id'] ?? 0;

    if ($order_id <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'Invalid order.']);
        exit();
    }

    try {
        $pdo->beginTransaction();

        // Verify order belongs to this customer and is not already completed/cancelled
        $stmt = $pdo->prepare("SELECT co.order_id, co.status_id, co.amount_paid, os.status_name
                               FROM crayfish_orders co
                               JOIN order_statuses os ON co.status_id = os.status_id
                               WHERE co.order_id = ? AND co.customer_id = ?");
        $stmt->execute([$order_id, $customer_id]);
        $order = $stmt->fetch();

        if (!$order) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Order not found.']);
            exit();
        }

        if ($order['status_name'] === 'Completed') {
            $pdo->rollBack();
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Completed orders cannot be cancelled.']);
            exit();
        }

        if ($order['status_name'] === 'Cancelled') {
            $pdo->rollBack();
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'This order is already cancelled.']);
            exit();
        }

        // Find the Cancelled status_id
        $stmt_cs = $pdo->prepare("SELECT status_id FROM order_statuses WHERE status_name = 'Cancelled' LIMIT 1");
        $stmt_cs->execute();
        $cancelled_status = $stmt_cs->fetch();
        $cancelled_id = $cancelled_status ? $cancelled_status['status_id'] : 5;

        // Update order status to Cancelled
        $stmt_upd = $pdo->prepare("UPDATE crayfish_orders SET status_id = ? WHERE order_id = ?");
        $stmt_upd->execute([$cancelled_id, $order_id]);

        // If there was any payment, mark as Refunded
        if ($order['amount_paid'] > 0) {
            $stmt_refund = $pdo->prepare("UPDATE crayfish_orders SET payment_status_id = 4 WHERE order_id = ?");
            $stmt_refund->execute([$order_id]);
        }

        $pdo->commit();

        echo json_encode([
            'success'      => true,
            'order_id'     => $order_id,
            'refund_amount' => $order['amount_paid'],
        ]);
        exit();

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('crayfish_payment_process cancel error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Cancellation failed. Please try again.']);
        exit();
    }
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Unknown action.']);
exit();
