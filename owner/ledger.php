<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type_id'] != 3) {
    header("Location: ../pages/login.php");
    exit();
}
require_once '../config/db_connection.php';
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT u.email, up.first_name, up.last_name FROM users u LEFT JOIN user_profiles up ON u.user_id = up.user_id WHERE u.user_id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();
$user_name = ($current_user && $current_user['first_name']) ? ($current_user['first_name'] . ' ' . $current_user['last_name']) : 'Owner';
$ownerNavActive = 'income-ledger';

$facilityRows = $pdo->query("SELECT facility_id FROM facilities ORDER BY facility_id ASC")->fetchAll(PDO::FETCH_COLUMN);
$facilityIds = $facilityRows ?: [0];
$placeholders = implode(',', array_fill(0, count($facilityIds), '?'));

$hasLedgerData = false;
$payments = [];
$totalRevenue = 0;
$paymentMethodsSummary = [];
$paymentStatusSummary = [];
$monthlyRevenue = [];

try {
    // Main payments query — join through bookings → booking_items → facilities to get facility name
    $paymentsStmt = $pdo->prepare(
        "SELECT p.payment_id, p.booking_id, p.amount_paid, p.transaction_id,
                COALESCE(p.payment_date, b.created_at) AS payment_date,
                pm.method_name, ps.status_name,
                up.first_name, up.last_name,
                f.name AS facility_name
         FROM payments p
         JOIN bookings b ON p.booking_id = b.booking_id
         JOIN booking_items bi ON b.booking_id = bi.booking_id
         JOIN facilities f ON bi.facility_id = f.facility_id
         JOIN payment_methods pm ON p.payment_method_id = pm.payment_method_id
         JOIN payment_statuses ps ON b.payment_status_id = ps.payment_status_id
         LEFT JOIN users u ON b.customer_id = u.user_id
         LEFT JOIN user_profiles up ON u.user_id = up.user_id
         WHERE bi.facility_id IN ($placeholders)
         GROUP BY p.payment_id
         ORDER BY COALESCE(p.payment_date, b.created_at) DESC"
    );
    $paymentsStmt->execute($facilityIds);
    $payments = $paymentsStmt->fetchAll();
    $hasLedgerData = true;

    // Total revenue
    $totalRevenueStmt = $pdo->prepare(
        "SELECT COALESCE(SUM(amount_paid), 0) AS total_revenue
         FROM (
            SELECT p.payment_id, p.amount_paid
            FROM payments p
            JOIN bookings b ON p.booking_id = b.booking_id
            JOIN booking_items bi ON b.booking_id = bi.booking_id
            WHERE bi.facility_id IN ($placeholders)
            GROUP BY p.payment_id
         ) payment_summary"
    );
    $totalRevenueStmt->execute($facilityIds);
    $totalRevenue = $totalRevenueStmt->fetch()['total_revenue'] ?? 0;

    // Payment methods summary
    $methodSummaryStmt = $pdo->prepare(
        "SELECT pm.method_name, COUNT(*) AS count
         FROM (
            SELECT p.payment_id, p.payment_method_id
            FROM payments p
            JOIN bookings b ON p.booking_id = b.booking_id
            JOIN booking_items bi ON b.booking_id = bi.booking_id
            WHERE bi.facility_id IN ($placeholders)
            GROUP BY p.payment_id
         ) payment_summary
         JOIN payment_methods pm ON payment_summary.payment_method_id = pm.payment_method_id
         GROUP BY pm.method_name"
    );
    $methodSummaryStmt->execute($facilityIds);
    $paymentMethodsSummary = $methodSummaryStmt->fetchAll();

    // Payment statuses summary
    $statusSummaryStmt = $pdo->prepare(
        "SELECT ps.status_name, COUNT(*) AS count
         FROM (
            SELECT p.payment_id, b.payment_status_id
            FROM payments p
            JOIN bookings b ON p.booking_id = b.booking_id
            JOIN booking_items bi ON b.booking_id = bi.booking_id
            WHERE bi.facility_id IN ($placeholders)
            GROUP BY p.payment_id
         ) payment_summary
         JOIN payment_statuses ps ON payment_summary.payment_status_id = ps.payment_status_id
         GROUP BY ps.status_name"
    );
    $statusSummaryStmt->execute($facilityIds);
    $paymentStatusSummary = $statusSummaryStmt->fetchAll();

    // Monthly revenue (last 6 months)
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $monthLabel = date('M Y', strtotime("-$i months"));
        $monthlyStmt = $pdo->prepare(
            "SELECT COALESCE(SUM(p.amount_paid), 0) AS revenue
             FROM payments p
             JOIN bookings b ON p.booking_id = b.booking_id
             JOIN booking_items bi ON b.booking_id = bi.booking_id
             WHERE DATE_FORMAT(COALESCE(p.payment_date, b.created_at), '%Y-%m') = ?
             AND bi.facility_id IN ($placeholders)
             GROUP BY bi.facility_id"
        );
        $monthlyStmt->execute(array_merge([$month], $facilityIds));
        $rows = $monthlyStmt->fetchAll();
        $sum = 0;
        foreach ($rows as $row) {
            $sum += $row['revenue'];
        }
        $monthlyRevenue[] = [
            'label' => $monthLabel,
            'revenue' => $sum
        ];
    }
} catch (Exception $e) {
    $hasLedgerData = false;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Income & Ledger | Owner Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600&family=Pinyon+Script&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* ── Under Study overlay ── */
        .under-study-banner {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 1px solid #f59e0b;
            border-radius: 10px;
            padding: 14px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .under-study-banner .us-icon {
            font-size: 22px;
            color: #d97706;
            flex-shrink: 0;
        }
        .under-study-banner .us-text {
            font-family: 'Josefin Sans', sans-serif;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #92400e;
        }
        .under-study-banner .us-sub {
            font-size: 11px;
            color: #a16207;
            font-family: 'Lora', serif;
            margin-top: 2px;
        }
        .ledger-content-blur {
            filter: blur(3px);
            pointer-events: none;
            user-select: none;
            position: relative;
        }
        .ledger-content-blur::after {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(255,255,255,0.15);
            border-radius: 8px;
        }
    </style>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'sidebar.php'; ?>
        <div class="main-wrapper">
            <header class="topbar">
                <div class="topbar-left">
                    <h2 class="topbar-title">Income & Ledger</h2>
                    <div class="search-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" id="paymentSearchInput" placeholder="Search payments, ledger entries, or revenue...">
                    </div>
                </div>
                <div class="topbar-right">
                    <div class="role-switcher">
                        <button class="role-btn active">Owner</button>
                        <button class="role-btn" onclick="goToAdmin()">Admin</button>
                    </div>
                    <button class="notification-btn"><i class="fas fa-bell"></i><span class="notification-dot"></span></button>
                    <div class="user-section">
                        <div class="user-info">
                            <p class="user-name"><?php echo htmlspecialchars($user_name); ?></p>
                            <p class="user-role">Owner</p>
                        </div>
                        <div class="user-avatar"><i class="fas fa-user"></i></div>
                    </div>
                </div>
            </header>
            <main class="main-content">
                <div class="section-card">
                    <div class="section-header">
                        <h3 class="section-title">Income & Ledger</h3>
                        <button class="section-action" onclick="window.print();"><i class="fas fa-print"></i> Print</button>
                    </div>
                    <div class="section-body">
                        <!-- Under Study Banner -->
                        <div class="under-study-banner">
                            <div class="us-icon"><i class="fas fa-flask"></i></div>
                            <div>
                                <div class="us-text">Under Study</div>
                                <div class="us-sub">This module is currently being refined. Some data may be incomplete or subject to change.</div>
                            </div>
                        </div>
                        <div class="ledger-content-blur">
                        <?php if (!$hasLedgerData): ?>
                            <div class="notification-card">
                                <p>Ledger data is not available yet.</p>
                                <p>Make sure payments, bookings, and booking items are configured in the database.</p>
                            </div>
                        <?php else: ?>
                            <!-- Summary Cards -->
                            <div class="grid-2-3" style="gap: 16px; margin-bottom: 24px;">
                                <div class="status-mini-card">
                                    <div class="status-icon confirmed"><i class="fas fa-money-bill-wave"></i></div>
                                    <div class="status-content"><p>Total Revenue</p><p>₱ <?php echo number_format($totalRevenue, 2); ?></p></div>
                                </div>
                                <div class="status-mini-card">
                                    <div class="status-icon confirmed"><i class="fas fa-receipt"></i></div>
                                    <div class="status-content"><p>Transactions</p><p><?php echo count($payments); ?></p></div>
                                </div>
                            </div>

                            <!-- Payment Method & Status Summaries -->
                            <?php if (!empty($paymentMethodsSummary) || !empty($paymentStatusSummary)): ?>
                                <div class="grid-2-3" style="gap: 16px; margin-bottom: 24px;">
                                    <?php foreach ($paymentMethodsSummary as $methodRow): ?>
                                        <div class="status-mini-card">
                                            <div class="status-icon confirmed"><i class="fas fa-credit-card"></i></div>
                                            <div class="status-content"><p><?php echo htmlspecialchars($methodRow['method_name']); ?></p><p><?php echo number_format($methodRow['count']); ?></p></div>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php foreach ($paymentStatusSummary as $statusRow): ?>
                                        <div class="status-mini-card">
                                            <div class="status-icon confirmed"><i class="fas fa-info-circle"></i></div>
                                            <div class="status-content"><p><?php echo htmlspecialchars($statusRow['status_name']); ?></p><p><?php echo number_format($statusRow['count']); ?></p></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Monthly Revenue Breakdown -->
                            <div class="section-header" style="margin-bottom: 12px;">
                                <h3 class="section-title" style="font-size: 14px;">Monthly Revenue (Last 6 Months)</h3>
                            </div>
                            <div class="grid-2-3" style="gap: 12px; margin-bottom: 24px; grid-template-columns: repeat(6, 1fr);">
                                <?php foreach ($monthlyRevenue as $month): ?>
                                    <div class="status-mini-card" style="flex-direction: column; align-items: center; text-align: center; padding: 12px 8px;">
                                        <div class="status-content" style="text-align: center;">
                                            <p style="font-size: 11px; color: rgba(47,61,46,0.6); margin: 0;"><?php echo $month['label']; ?></p>
                                            <p style="font-size: 16px; font-weight: 700; color: #2F3D2E; margin: 4px 0 0;">₱ <?php echo number_format($month['revenue'], 0); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Ledger Table -->
                            <div class="table-wrapper">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Customer</th>
                                            <th>Facility</th>
                                            <th>Booking Ref</th>
                                            <th>Method</th>
                                            <th>Transaction ID</th>
                                            <th>Amount</th>
                                            <th style="text-align: right;">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="paymentTableBody">
                                        <?php if (empty($payments)): ?>
                                            <tr><td colspan="8" style="text-align:center; color: rgba(47, 61, 46, 0.6); padding: 32px 0;">No ledger entries were found for your facility bookings.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($payments as $payment): ?>
                                                <tr>
                                                    <td style="font-size: 13px; color: rgba(47, 61, 46, 0.7);">
                                                        <?php echo $payment['payment_date'] ? date('M d, Y', strtotime($payment['payment_date'])) : '—'; ?>
                                                    </td>
                                                    <td style="font-weight: 500; color: #2F3D2E;">
                                                        <?php echo htmlspecialchars(trim(($payment['first_name'] ?? '') . ' ' . ($payment['last_name'] ?? ''))) ?: 'Guest'; ?>
                                                    </td>
                                                    <td style="color: rgba(47, 61, 46, 0.8);">
                                                        <?php echo htmlspecialchars($payment['facility_name'] ?? '—'); ?>
                                                    </td>
                                                    <td style="color: rgba(47, 61, 46, 0.8);">#<?php echo str_pad($payment['booking_id'], 5, '0', STR_PAD_LEFT); ?></td>
                                                    <td><span class="status-pill <?php echo strtolower(str_replace(' ', '-', $payment['method_name'])); ?>"><?php echo htmlspecialchars($payment['method_name']); ?></span></td>
                                                    <td style="font-size: 12px; color: rgba(47, 61, 46, 0.5); font-family: monospace;">
                                                        <?php echo htmlspecialchars($payment['transaction_id'] ?: '—'); ?>
                                                    </td>
                                                    <td style="font-weight: 600; color: #16a34a;">₱ <?php echo number_format($payment['amount_paid'], 2); ?></td>
                                                    <td style="text-align: right;"><span class="status-pill <?php echo strtolower(str_replace(' ', '-', $payment['status_name'])); ?>"><?php echo htmlspecialchars($payment['status_name']); ?></span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                        </div><!-- end .ledger-content-blur -->
                    </div>
                </div>
            </main>
            <div class="dashboard-footer">&copy; 2026 West Farm Resort and Hotel &middot; Basista, Pangasinan</div>
        </div>
    </div>
    <script>
        // Search filter
        const paymentSearchInput = document.getElementById('paymentSearchInput');
        const paymentRows = document.querySelectorAll('#paymentTableBody tr');
        paymentSearchInput.addEventListener('keyup', function() {
            const term = this.value.toLowerCase();
            paymentRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });
        });

        // Role switcher
        function goToAdmin() {
            window.location.href = '../admin/index.php';
        }

        // Logout confirmation
        document.getElementById('openLogoutModalBtn').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('logoutConfirmModal').style.display = 'flex';
        });
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
    </script>
    <div id="logoutConfirmModal" class="modal-overlay" style="display: none;">
        <div class="modal" style="max-width: 400px;">
            <div class="modal-header">
                <h3 class="modal-title">Confirm Sign Out</h3>
                <button class="modal-close" onclick="closeModal('logoutConfirmModal')">&times;</button>
            </div>
            <div class="modal-body"><p>Are you sure you want to sign out of your account?</p></div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('logoutConfirmModal')">Stay</button>
                <a href="../logic/logout.php" class="btn-danger">Sign Out</a>
            </div>
        </div>
    </div>
</body>
</html>
