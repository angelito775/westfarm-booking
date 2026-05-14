<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type_id'] != 1) {
    header("Location: ../pages/login.php");
    exit();
}

require_once '../config/db_connection.php';

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT u.email, up.first_name, up.last_name FROM users u LEFT JOIN user_profiles up ON u.user_id = up.user_id WHERE u.user_id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();
$user_name = $current_user['first_name'] . ' ' . $current_user['last_name'];

// Admin statistics
$total_users = $pdo->query("SELECT COUNT(*) AS count FROM users")->fetch()['count'] ?? 0;
$total_customers = $pdo->query("SELECT COUNT(*) AS count FROM users WHERE user_type_id = 2")->fetch()['count'] ?? 0;
$total_owners = $pdo->query("SELECT COUNT(*) AS count FROM users WHERE user_type_id = 3")->fetch()['count'] ?? 0;
$total_facilities = $pdo->query("SELECT COUNT(*) AS count FROM facilities")->fetch()['count'] ?? 0;
$total_bookings = $pdo->query("SELECT COUNT(*) AS count FROM bookings")->fetch()['count'] ?? 0;
$total_revenue = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) AS total_revenue FROM bookings")->fetch()['total_revenue'] ?? 0;
$total_categories = $pdo->query("SELECT COUNT(*) AS count FROM categories")->fetch()['count'] ?? 0;
$total_payment_methods = $pdo->query("SELECT COUNT(*) AS count FROM payment_methods")->fetch()['count'] ?? 0;

$confirmed_bookings = $pdo->query("SELECT COUNT(*) AS count FROM bookings WHERE booking_status_id = 2")->fetch()['count'] ?? 0;
$pending_bookings = $pdo->query("SELECT COUNT(*) AS count FROM bookings WHERE booking_status_id = 1")->fetch()['count'] ?? 0;
$completed_stays = $pdo->query("SELECT COUNT(*) AS count FROM bookings WHERE booking_status_id = 4")->fetch()['count'] ?? 0;

$stmt = $pdo->prepare("SELECT bs.status_name, COUNT(*) AS count FROM bookings b JOIN booking_statuses bs ON b.booking_status_id = bs.booking_status_id GROUP BY b.booking_status_id");
$stmt->execute();
$status_counts = $stmt->fetchAll();
$status_labels = array_column($status_counts, 'status_name');
$status_values = array_column($status_counts, 'count');

$revenue_labels = [];
$revenue_data = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $revenue_labels[] = date('M', strtotime("-$i months"));
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) AS revenue FROM bookings WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
    $stmt->execute([$month]);
    $revenue_data[] = $stmt->fetch()['revenue'];
}

$stmt = $pdo->prepare("SELECT b.booking_id, u.email, up.first_name, up.last_name, f.name AS facility_name, bs.status_name, b.total_amount, b.created_at FROM bookings b JOIN users u ON b.customer_id = u.user_id LEFT JOIN user_profiles up ON u.user_id = up.user_id JOIN booking_statuses bs ON b.booking_status_id = bs.booking_status_id LEFT JOIN booking_items bi ON b.booking_id = bi.booking_id LEFT JOIN facilities f ON bi.facility_id = f.facility_id ORDER BY b.created_at DESC LIMIT 10");
$stmt->execute();
$recent_bookings = $stmt->fetchAll();

$today = date('l, F j, Y');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | West Farm Resort</title>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600&family=Pinyon+Script&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>

<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo-container">
                    <svg class="logo-svg" viewBox="0 0 40 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M20 0L32 24H8L20 0Z" fill="#2F3D2E" />
                        <path d="M20 4L28 20H12L20 4Z" fill="#FAF8F4" />
                        <path d="M8 26H32V28H8V26Z" fill="#2F3D2E" />
                        <path d="M12 30H28V31H12V30Z" fill="#2F3D2E" />
                    </svg>
                    <div class="logo-text">
                        <h1>West Farm</h1>
                        <p>Resort and Hotel</p>
                    </div>
                </div>
                <span class="portal-badge">Admin Portal</span>
            </div>
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="users.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
                <a href="categories.php" class="nav-item">
                    <i class="fas fa-layer-group"></i>
                    <span>Categories</span>
                </a>
                <a href="payment_methods.php" class="nav-item">
                    <i class="fas fa-wallet"></i>
                    <span>Payment Methods</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </nav>
            <div class="sidebar-footer">
                <a href="#" class="logout-btn" id="openLogoutModalBtn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Sign Out</span>
                </a>
            </div>
        </aside>
        <div class="main-wrapper">
            <header class="topbar">
                <div class="topbar-left">
                    <h2 class="topbar-title">Admin Overview</h2>
                    <div class="search-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search users, bookings, or facilities...">
                    </div>
                </div>
                <div class="topbar-right">
                    <button class="notification-btn">
                        <i class="fas fa-bell"></i>
                        <span class="notification-dot"></span>
                    </button>
                    <div class="user-section">
                        <div class="user-info">
                            <p class="user-name"><?php echo htmlspecialchars($user_name); ?></p>
                            <p class="user-role">Administrator</p>
                        </div>
                        <div class="user-avatar">
                            <i class="fas fa-user-shield"></i>
                        </div>
                    </div>
                </div>
            </header>
            <main class="main-content">
                <div class="welcome-section" style="margin-bottom: 32px;">
                    <h1>Welcome back, Administrator</h1>
                    <p><?php echo $today; ?></p>
                </div>
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-title">Total Revenue</div>
                        <div class="kpi-value">
                            <span>₱ <?php echo number_format($total_revenue, 0); ?></span>
                            <div class="kpi-trend positive">
                                <i class="fas fa-arrow-up"></i>
                                <span>16.9%</span>
                            </div>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-title">Total Bookings</div>
                        <div class="kpi-value">
                            <span><?php echo $total_bookings; ?></span>
                            <div class="kpi-trend positive">
                                <i class="fas fa-arrow-up"></i>
                                <span>9.4%</span>
                            </div>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-title">Total Users</div>
                        <div class="kpi-value">
                            <span><?php echo $total_users; ?></span>
                            <div class="kpi-trend positive">
                                <i class="fas fa-arrow-up"></i>
                                <span>4.2%</span>
                            </div>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-title">Total Categories</div>
                        <div class="kpi-value">
                            <span><?php echo $total_categories; ?></span>
                            <div class="kpi-trend positive">
                                <i class="fas fa-arrow-up"></i>
                                <span>5.7%</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="status-grid">
                    <div class="status-mini-card">
                        <div class="status-icon pending">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="status-content">
                            <p>Pending</p>
                            <p><?php echo $pending_bookings; ?></p>
                        </div>
                    </div>
                    <div class="status-mini-card">
                        <div class="status-icon confirmed">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="status-content">
                            <p>Confirmed</p>
                            <p><?php echo $confirmed_bookings; ?></p>
                        </div>
                    </div>
                    <div class="status-mini-card">
                        <div class="status-icon completed">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="status-content">
                            <p>Completed</p>
                            <p><?php echo $completed_stays; ?></p>
                        </div>
                    </div>
                    <div class="status-mini-card">
                        <div class="status-icon" style="background: #f3e8ff; color: #9333ea;">
                            <i class="fas fa-money-check-alt"></i>
                        </div>
                        <div class="status-content">
                            <p>Payment Methods</p>
                            <p><?php echo $total_payment_methods; ?></p>
                        </div>
                    </div>
                </div>
                <div class="grid-2-3">
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">Revenue (Last 6 Months)</h3>
                        </div>
                        <div class="section-body">
                            <div class="chart-container">
                                <canvas id="revenueChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">Bookings by Status</h3>
                        </div>
                        <div class="section-body">
                            <div class="chart-container">
                                <canvas id="statusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="grid-2-3" style="margin-top: 24px;">
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">Recent Bookings</h3>
                            <button class="section-action">View All</button>
                        </div>
                        <div class="section-body">
                            <div class="table-wrapper">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Customer</th>
                                            <th>Facility</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_bookings as $booking): ?>
                                            <tr>
                                                <td style="font-weight: 500; color: #2F3D2E;">
                                                    <?php echo htmlspecialchars(($booking['first_name'] ?? '') . ' ' . ($booking['last_name'] ?? '') ?: $booking['email']); ?>
                                                </td>
                                                <td style="color: rgba(47, 61, 46, 0.8);">
                                                    <?php echo htmlspecialchars($booking['facility_name'] ?? 'N/A'); ?>
                                                </td>
                                                <td style="color: #2F3D2E; font-weight: 500;">
                                                    ₱ <?php echo number_format($booking['total_amount'], 0); ?>
                                                </td>
                                                <td style="text-align: right;">
                                                    <span class="status-pill <?php echo strtolower($booking['status_name']); ?>">
                                                        <?php echo htmlspecialchars($booking['status_name']); ?>
                                                    </span>
                                                </td>
                                                <td style="font-size: 12px; color: rgba(47, 61, 46, 0.6);">
                                                    <?php echo date('M d, Y', strtotime($booking['created_at'])); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">Quick Actions</h3>
                        </div>
                        <div class="section-body" style="display: grid; gap: 14px;">
                            <a href="#" class="action-card">Manage Users</a>
                            <a href="#" class="action-card">Review Bookings</a>
                            <a href="#" class="action-card">Approve Facilities</a>
                            <a href="#" class="action-card">View Reports</a>
                        </div>
                    </div>
                </div>
            </main>
            <div class="dashboard-footer">
                © 2026 West Farm Resort and Hotel · Basista, Pangasinan
            </div>
        </div>
    </div>
    <!-- Logout Confirmation Modal -->
    <div id="logoutConfirmModal" class="modal-overlay" style="display: none;">
        <div class="modal" style="max-width: 400px;">
            <div class="modal-header">
                <h3 class="modal-title">Confirm Sign Out</h3>
                <button class="modal-close" onclick="closeModal('logoutConfirmModal')">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to sign out of your account?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('logoutConfirmModal')">Stay</button>
                <a href="../logic/logout.php" class="btn-danger">Sign Out</a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <script>
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($revenue_labels); ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?php echo json_encode($revenue_data); ?>,
                    borderColor: '#2F3D2E',
                    backgroundColor: 'rgba(47, 61, 46, 0.12)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#2F3D2E'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#ECE8DF'
                        },
                        ticks: {
                            callback: v => '₱' + (v / 1000).toFixed(0) + 'k'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($status_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($status_values); ?>,
                    backgroundColor: ['#F59E0B', '#10B981', '#22C55E', '#EF4444'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        window.onclick = function(event) {
            if (event.target.classList.contains('modal-overlay')) {
                closeModal(event.target.id);
            }
        }

        // Logout confirmation
        document.getElementById('openLogoutModalBtn').addEventListener('click', function(e) {
            e.preventDefault();
            openModal('logoutConfirmModal');
        });
    </script>
</body>

</html>