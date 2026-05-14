<?php
session_start();

// SECURITY: Protect dashboard - only logged-in owners can access
if (!isset($_SESSION['user_id']) || $_SESSION['user_type_id'] != 3) {
    header("Location: ../pages/login.php");
    exit();
}

require_once '../config/db_connection.php';

// Get current user info
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT u.email, up.first_name, up.last_name 
    FROM users u
    LEFT JOIN user_profiles up ON u.user_id = up.user_id
    WHERE u.user_id = ?
");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();
$user_name = $current_user['first_name'] . ' ' . $current_user['last_name'];

// Get facilities for this dashboard.
// Current database schema does not include an owner_id field on facilities,
// so we load all facilities instead of filtering by owner.
$stmt = $pdo->query(
    "SELECT facility_id, name FROM facilities"
);
$owner_facilities = $stmt->fetchAll();
$facility_ids = array_column($owner_facilities, 'facility_id');

// KPI: Total Revenue
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(total_amount), 0) as total_revenue
    FROM bookings b
    JOIN booking_items bi ON b.booking_id = bi.booking_id
    WHERE bi.facility_id IN (" . implode(',', $facility_ids ?: [0]) . ")
");
$stmt->execute();
$total_revenue = $stmt->fetch()['total_revenue'] ?? 0;

// KPI: Confirmed Bookings
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT b.booking_id) as count
    FROM bookings b
    JOIN booking_items bi ON b.booking_id = bi.booking_id
    WHERE b.booking_status_id = 2 AND bi.facility_id IN (" . implode(',', $facility_ids ?: [0]) . ")
");
$stmt->execute();
$confirmed_bookings = $stmt->fetch()['count'] ?? 0;

// KPI: Pending Bookings
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT b.booking_id) as count
    FROM bookings b
    JOIN booking_items bi ON b.booking_id = bi.booking_id
    WHERE b.booking_status_id = 1 AND bi.facility_id IN (" . implode(',', $facility_ids ?: [0]) . ")
");
$stmt->execute();
$pending_bookings = $stmt->fetch()['count'] ?? 0;

// KPI: Completed Stays
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT b.booking_id) as count
    FROM bookings b
    JOIN booking_items bi ON b.booking_id = bi.booking_id
    WHERE b.booking_status_id = 4 AND bi.facility_id IN (" . implode(',', $facility_ids ?: [0]) . ")
");
$stmt->execute();
$completed_stays = $stmt->fetch()['count'] ?? 0;

// Mini Cards: Pending, Confirmed, Completed, Cancelled counts
$stmt = $pdo->prepare("
    SELECT b.booking_status_id, COUNT(DISTINCT b.booking_id) as count
    FROM bookings b
    JOIN booking_items bi ON b.booking_id = bi.booking_id
    WHERE bi.facility_id IN (" . implode(',', $facility_ids ?: [0]) . ")
    GROUP BY b.booking_status_id
");
$stmt->execute();
$booking_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$pending_count = $booking_counts[1] ?? 0;
$confirmed_count = $booking_counts[2] ?? 0;
$completed_count = $booking_counts[4] ?? 0;
$cancelled_count = $booking_counts[3] ?? 0;

// Revenue by month (last 6 months)
$revenue_data = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(total_amount), 0) as revenue
        FROM bookings b
        JOIN booking_items bi ON b.booking_id = bi.booking_id
        WHERE DATE_FORMAT(b.created_at, '%Y-%m') = ? 
        AND bi.facility_id IN (" . implode(',', $facility_ids ?: [0]) . ")
    ");
    $stmt->execute([$month]);
    $revenue_data[] = $stmt->fetch()['revenue'];
}

// Bookings by facility
$bookings_by_facility = [];
foreach ($owner_facilities as $facility) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM bookings b
        JOIN booking_items bi ON b.booking_id = bi.booking_id
        WHERE bi.facility_id = ?
    ");
    $stmt->execute([$facility['facility_id']]);
    $count = $stmt->fetch()['count'];
    $bookings_by_facility[] = [
        'name' => $facility['name'],
        'bookings' => $count
    ];
}

// Recent bookings
$stmt = $pdo->prepare("
    SELECT b.booking_id, up.first_name, up.last_name, f.name as facility_name,
           bi.check_in_date, bi.check_out_date, b.total_amount, bs.status_name,
           ROW_NUMBER() OVER (ORDER BY b.created_at DESC) as rn
    FROM bookings b
    JOIN booking_items bi ON b.booking_id = bi.booking_id
    JOIN facilities f ON bi.facility_id = f.facility_id
    JOIN booking_statuses bs ON b.booking_status_id = bs.booking_status_id
    JOIN users u ON b.customer_id = u.user_id
    LEFT JOIN user_profiles up ON u.user_id = up.user_id
    WHERE bi.facility_id IN (" . implode(',', $facility_ids ?: [0]) . ")
    ORDER BY b.created_at DESC
    LIMIT 10
");
$stmt->execute();
$recent_bookings = $stmt->fetchAll();

// Upcoming bookings
$stmt = $pdo->prepare("
    SELECT up.first_name, up.last_name, f.name as facility_name,
           bi.check_in_date, b.total_amount
    FROM bookings b
    JOIN booking_items bi ON b.booking_id = bi.booking_id
    JOIN facilities f ON bi.facility_id = f.facility_id
    JOIN users u ON b.customer_id = u.user_id
    LEFT JOIN user_profiles up ON u.user_id = up.user_id
    WHERE bi.facility_id IN (" . implode(',', $facility_ids ?: [0]) . ")
    AND bi.check_in_date >= CURDATE()
    AND b.booking_status_id IN (1, 2)
    ORDER BY bi.check_in_date ASC
    LIMIT 4
");
$stmt->execute();
$upcoming_bookings = $stmt->fetchAll();

// Current date
$today = date('l, F j, Y');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard | West Farm Resort</title>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600&family=Pinyon+Script&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- SIDEBAR -->
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
                <span class="portal-badge">Owner Portal</span>
            </div>

            <nav class="sidebar-nav">
                <button class="nav-item active" onclick="navigateTo('dashboard')">
                    <i class="fas fa-th-large"></i>
                    <span>Dashboard</span>
                </button>
                <button class="nav-item" onclick="navigateTo('bookings')">
                    <i class="fas fa-calendar"></i>
                    <span>My Bookings</span>
                </button>
                <button class="nav-item" onclick="navigateTo('facilities')">
                    <i class="fas fa-building"></i>
                    <span>My Facilities</span>
                </button>
                <button class="nav-item" onclick="navigateTo('payments')">
                    <i class="fas fa-wallet"></i>
                    <span>Payments</span>
                </button>
                <button class="nav-item" onclick="navigateTo('reports')">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </button>
                <button class="nav-item" onclick="navigateTo('settings')">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </button>
            </nav>

            <div class="sidebar-footer">
                <a href="#" class="logout-btn" id="openLogoutModalBtn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Sign Out</span>
                </a>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <div class="main-wrapper">
            <!-- TOPBAR -->
            <header class="topbar">
                <div class="topbar-left">
                    <h2 class="topbar-title">Overview</h2>
                    <div class="search-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search bookings, orders, or guests...">
                    </div>
                </div>

                <div class="topbar-right">
                    <div class="role-switcher">
                        <button class="role-btn active">Owner</button>
                        <button class="role-btn" onclick="goToAdmin()">Admin</button>
                    </div>

                    <button class="notification-btn">
                        <i class="fas fa-bell"></i>
                        <span class="notification-dot"></span>
                    </button>

                    <div class="user-section">
                        <div class="user-info">
                            <p class="user-name"><?php echo htmlspecialchars($user_name); ?></p>
                            <p class="user-role">Owner</p>
                        </div>
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                    </div>
                </div>
            </header>

            <!-- PAGE CONTENT -->
            <main class="main-content">
                <!-- WELCOME -->
                <div class="welcome-section" style="margin-bottom: 32px;">
                    <h1>Good morning, Owner</h1>
                    <p><?php echo $today; ?></p>
                </div>

                <!-- KPI CARDS -->
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-title">Total Revenue</div>
                        <div class="kpi-value">
                            <span>₱ <?php echo number_format($total_revenue, 0); ?></span>
                            <div class="kpi-trend positive">
                                <i class="fas fa-arrow-up"></i>
                                <span>12.5%</span>
                            </div>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-title">Confirmed Bookings</div>
                        <div class="kpi-value">
                            <span><?php echo $confirmed_bookings; ?></span>
                            <div class="kpi-trend positive">
                                <i class="fas fa-arrow-up"></i>
                                <span>8.3%</span>
                            </div>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-title">Pending Bookings</div>
                        <div class="kpi-value">
                            <span><?php echo $pending_bookings; ?></span>
                            <div class="kpi-trend negative">
                                <i class="fas fa-arrow-down"></i>
                                <span>2.1%</span>
                            </div>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-title">Completed Stays</div>
                        <div class="kpi-value">
                            <span><?php echo $completed_stays; ?></span>
                            <div class="kpi-trend positive">
                                <i class="fas fa-arrow-up"></i>
                                <span>18.4%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STATUS MINI CARDS -->
                <div class="status-grid">
                    <div class="status-mini-card">
                        <div class="status-icon pending">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="status-content">
                            <p>Pending</p>
                            <p><?php echo $pending_count; ?></p>
                        </div>
                    </div>
                    <div class="status-mini-card">
                        <div class="status-icon confirmed">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="status-content">
                            <p>Confirmed</p>
                            <p><?php echo $confirmed_count; ?></p>
                        </div>
                    </div>
                    <div class="status-mini-card">
                        <div class="status-icon completed">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="status-content">
                            <p>Completed</p>
                            <p><?php echo $completed_count; ?></p>
                        </div>
                    </div>
                    <div class="status-mini-card">
                        <div class="status-icon cancelled">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="status-content">
                            <p>Cancelled</p>
                            <p><?php echo $cancelled_count; ?></p>
                        </div>
                    </div>
                </div>

                <!-- CHARTS ROW -->
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
                            <h3 class="section-title">Bookings by Facility</h3>
                        </div>
                        <div class="section-body">
                            <div class="chart-container">
                                <canvas id="facilityChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BOOKINGS + UPCOMING ROW -->
                <div class="grid-2-3" style="margin-top: 24px;">
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">My Bookings</h3>
                            <button class="section-action">View All</button>
                        </div>
                        <div class="section-body">
                            <div class="table-wrapper">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Customer</th>
                                            <th>Facility</th>
                                            <th>Dates</th>
                                            <th>Amount</th>
                                            <th style="text-align: right;">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_bookings as $booking): ?>
                                        <tr>
                                            <td style="font-weight: 500; color: #2F3D2E;">
                                                <?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?>
                                            </td>
                                            <td style="color: rgba(47, 61, 46, 0.8);">
                                                <?php echo htmlspecialchars($booking['facility_name']); ?>
                                            </td>
                                            <td style="font-size: 12px; color: rgba(47, 61, 46, 0.6);">
                                                <?php echo date('M d', strtotime($booking['check_in_date'])) . ' - ' . date('M d', strtotime($booking['check_out_date'])); ?>
                                            </td>
                                            <td style="color: #2F3D2E; font-weight: 500;">
                                                ₱ <?php echo number_format($booking['total_amount'], 0); ?>
                                            </td>
                                            <td style="text-align: right;">
                                                <span class="status-pill <?php echo strtolower($booking['status_name']); ?>">
                                                    <?php echo htmlspecialchars($booking['status_name']); ?>
                                                </span>
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
                            <h3 class="section-title">Upcoming Bookings</h3>
                        </div>
                        <div class="section-body">
                            <?php if (!empty($upcoming_bookings)): ?>
                                <?php foreach ($upcoming_bookings as $booking): ?>
                                <div class="upcoming-item">
                                    <div class="upcoming-icon">
                                        <i class="fas fa-building"></i>
                                    </div>
                                    <div class="upcoming-content">
                                        <p class="upcoming-customer">
                                            <?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?>
                                        </p>
                                        <p class="upcoming-facility">
                                            <?php echo htmlspecialchars($booking['facility_name']); ?>
                                        </p>
                                        <div class="upcoming-meta">
                                            <span class="upcoming-date">
                                                <?php echo date('M d', strtotime($booking['check_in_date'])); ?>
                                            </span>
                                            <span class="upcoming-amount">
                                                ₱ <?php echo number_format($booking['total_amount'], 0); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="color: rgba(47, 61, 46, 0.6); text-align: center; padding: 24px 0;">
                                    No upcoming bookings
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>

            <!-- FOOTER -->
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

    <!-- Chart.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <script>
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Revenue',
                    data: <?php echo json_encode($revenue_data); ?>,
                    borderColor: '#2F3D2E',
                    backgroundColor: 'rgba(47, 61, 46, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#2F3D2E',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4
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
                            callback: function(value) {
                                return '₱' + (value / 1000).toFixed(0) + 'k';
                            }
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

        // Facility Bookings Chart
        const facilityCtx = document.getElementById('facilityChart').getContext('2d');
        new Chart(facilityCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($bookings_by_facility, 'name')); ?>,
                datasets: [{
                    label: 'Bookings',
                    data: <?php echo json_encode(array_column($bookings_by_facility, 'bookings')); ?>,
                    backgroundColor: '#C9A961',
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            color: '#ECE8DF'
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        function navigateTo(page) {
            alert('Navigation to ' + page + ' coming soon!');
        }

        function goToAdmin() {
            window.location.href = '../admin/index.php';
        }

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