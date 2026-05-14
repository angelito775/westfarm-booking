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
$ownerNavActive = 'bookings-reservations';

// Load owner facilities
$stmt = $pdo->query("SELECT facility_id, name FROM facilities ORDER BY name ASC");
$owner_facilities = $stmt->fetchAll();
$facility_ids = array_column($owner_facilities, 'facility_id');

$bookings = [];
$pending_count = $confirmed_count = $checked_in_count = $completed_count = 0;

if (!empty($facility_ids)) {
    $placeholders = implode(',', array_fill(0, count($facility_ids), '?'));
    $stmt = $pdo->prepare(
        "SELECT b.booking_id, up.first_name, up.last_name, f.name AS facility_name, bi.check_in_date, bi.check_out_date, b.total_amount, bs.status_name, b.created_at
         FROM bookings b
         JOIN booking_items bi ON b.booking_id = bi.booking_id
         JOIN facilities f ON bi.facility_id = f.facility_id
         JOIN booking_statuses bs ON b.booking_status_id = bs.booking_status_id
         JOIN users u ON b.customer_id = u.user_id
         LEFT JOIN user_profiles up ON u.user_id = up.user_id
         WHERE bi.facility_id IN ($placeholders)
         ORDER BY b.created_at DESC
         LIMIT 50"
    );
    $stmt->execute($facility_ids);
    $bookings = $stmt->fetchAll();

    $statusCounts = $pdo->prepare(
        "SELECT b.booking_status_id, COUNT(DISTINCT b.booking_id) AS count
         FROM bookings b
         JOIN booking_items bi ON b.booking_id = bi.booking_id
         WHERE bi.facility_id IN ($placeholders)
         GROUP BY b.booking_status_id"
    );
    $statusCounts->execute($facility_ids);
    $counts = $statusCounts->fetchAll(PDO::FETCH_KEY_PAIR);
    $pending_count = $counts[1] ?? 0;
    $confirmed_count = $counts[2] ?? 0;
    $checked_in_count = $counts[3] ?? 0;
    $completed_count = $counts[4] ?? 0;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings & Reservations | Owner Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600&family=Pinyon+Script&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'sidebar.php'; ?>
        <div class="main-wrapper">
            <header class="topbar">
                <div class="topbar-left">
                    <h2 class="topbar-title">Bookings & Reservations</h2>
                    <div class="search-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" id="bookingSearchInput" placeholder="Search bookings, guests, or facilities...">
                    </div>
                </div>
                <div class="topbar-right">
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
                        <h3 class="section-title">Current Booking Activity</h3>
                    </div>
                    <div class="section-body">
                        <div class="grid-2-3" style="gap: 16px; margin-bottom: 24px;">
                            <div class="status-mini-card">
                                <div class="status-icon pending"><i class="fas fa-clock"></i></div>
                                <div class="status-content"><p>Pending</p><p><?php echo $pending_count; ?></p></div>
                            </div>
                            <div class="status-mini-card">
                                <div class="status-icon confirmed"><i class="fas fa-check-circle"></i></div>
                                <div class="status-content"><p>Confirmed</p><p><?php echo $confirmed_count; ?></p></div>
                            </div>
                            <div class="status-mini-card">
                                <div class="status-icon pending"><i class="fas fa-door-open"></i></div>
                                <div class="status-content"><p>Checked In</p><p><?php echo $checked_in_count; ?></p></div>
                            </div>
                            <div class="status-mini-card">
                                <div class="status-icon completed"><i class="fas fa-star"></i></div>
                                <div class="status-content"><p>Completed</p><p><?php echo $completed_count; ?></p></div>
                            </div>
                        </div>
                        <div class="table-wrapper">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Booking</th>
                                        <th>Guest</th>
                                        <th>Facility</th>
                                        <th>Dates</th>
                                        <th>Amount</th>
                                        <th style="text-align: right;">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="bookingTableBody">
                                    <?php if (empty($bookings)): ?>
                                        <tr><td colspan="6" style="text-align:center; color: rgba(47, 61, 46, 0.6); padding: 32px 0;">No bookings found for your facilities yet.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($bookings as $booking): ?>
                                            <tr>
                                                <td><span class="booking-id">#<?php echo htmlspecialchars($booking['booking_id']); ?></span></td>
                                                <td><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($booking['facility_name']); ?></td>
                                                <td><?php echo date('M d', strtotime($booking['check_in_date'])); ?> - <?php echo date('M d', strtotime($booking['check_out_date'])); ?></td>
                                                <td>₱ <?php echo number_format($booking['total_amount'], 0); ?></td>
                                                <td style="text-align: right;"><span class="status-pill <?php echo strtolower(str_replace(' ', '-', $booking['status_name'])); ?>"><?php echo htmlspecialchars($booking['status_name']); ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
            <div class="dashboard-footer">© 2026 West Farm Resort and Hotel · Basista, Pangasinan</div>
        </div>
    </div>
    <script>
        const bookingSearchInput = document.getElementById('bookingSearchInput');
        const bookRows = document.querySelectorAll('#bookingTableBody tr');
        bookingSearchInput.addEventListener('keyup', function() {
            const term = this.value.toLowerCase();
            bookRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });
        });
        document.getElementById('openLogoutModalBtn').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('logoutConfirmModal').style.display = 'flex';
        });
        function closeModal(modalId) { document.getElementById(modalId).style.display = 'none'; }
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
