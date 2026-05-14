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
$ownerNavActive = 'facilities-rooms';

$columns = $pdo->query("SHOW COLUMNS FROM facilities")->fetchAll(PDO::FETCH_COLUMN);
$selectFields = ['facility_id', 'name'];
$hasDescription = in_array('description', $columns);
$hasPrice = in_array('price', $columns);
$hasStatus = in_array('status', $columns) || in_array('is_available', $columns) || in_array('is_active', $columns);
$statusColumn = null;
$hasCategory = in_array('category_id', $columns);
$hasCreated = in_array('created_at', $columns);

if ($hasDescription) {
    $selectFields[] = 'description';
}
if ($hasPrice) {
    $selectFields[] = 'price';
}
if ($hasStatus) {
    if (in_array('status', $columns)) {
        $statusColumn = 'status';
    } elseif (in_array('is_available', $columns)) {
        $statusColumn = 'is_available';
    } else {
        $statusColumn = 'is_active';
    }
    $selectFields[] = $statusColumn;
}
if ($hasCategory) {
    $selectFields[] = 'category_id';
}
if ($hasCreated) {
    $selectFields[] = 'created_at';
}

$sql = 'SELECT ' . implode(', ', $selectFields) . ' FROM facilities ORDER BY name ASC';
$stmt = $pdo->query($sql);
$facilities = $stmt->fetchAll();
$categoryMap = [];
if ($hasCategory) {
    $categoryRows = $pdo->query("SELECT category_id, name FROM categories")->fetchAll();
    foreach ($categoryRows as $category) {
        $categoryMap[$category['category_id']] = $category['name'];
    }
}

$facility_ids = array_column($facilities, 'facility_id');
$bookingCounts = [];
if (!empty($facility_ids)) {
    $placeholders = implode(',', array_fill(0, count($facility_ids), '?'));
    $stmt = $pdo->prepare("SELECT facility_id, COUNT(DISTINCT booking_id) AS booking_count FROM booking_items WHERE facility_id IN ($placeholders) GROUP BY facility_id");
    $stmt->execute($facility_ids);
    $bookingCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

function facilityStatusIsOpen($value)
{
    $normalized = trim((string)$value);
    $openValues = ['1', 'active', 'open', 'available', 'yes', 'true', 'on'];
    return $normalized === '' || in_array(strtolower($normalized), $openValues, true);
}

$openFacilities = 0;
$closedFacilities = 0;
foreach ($facilities as $facility) {
    if ($hasStatus) {
        $value = $facility[$statusColumn] ?? '';
        if (facilityStatusIsOpen($value)) {
            $openFacilities++;
        } else {
            $closedFacilities++;
        }
    }
}
if (!$hasStatus) {
    $openFacilities = count($facilities);
    $closedFacilities = 0;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facilities & Rooms | Owner Dashboard</title>
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
                    <h2 class="topbar-title">Facilities & Rooms</h2>
                    <div class="search-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" id="facilitySearchInput" placeholder="Search rooms, villas, or facility products...">
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
                        <h3 class="section-title">Facility Inventory</h3>
                        <button class="section-action" onclick="alert('Facility creation will be added later');">Add Facility</button>
                    </div>
                    <div class="section-body">
                        <div class="grid-2-3" style="gap: 16px; margin-bottom: 24px;">
                            <div class="status-mini-card">
                                <div class="status-icon confirmed"><i class="fas fa-building"></i></div>
                                <div class="status-content"><p>Total Facilities</p><p><?php echo count($facilities); ?></p></div>
                            </div>
                            <div class="status-mini-card">
                                <div class="status-icon confirmed"><i class="fas fa-door-open"></i></div>
                                <div class="status-content"><p>Open for Booking</p><p><?php echo $openFacilities; ?></p></div>
                            </div>
                            <div class="status-mini-card">
                                <div class="status-icon cancelled"><i class="fas fa-ban"></i></div>
                                <div class="status-content"><p>Closed / Maintenance</p><p><?php echo $closedFacilities; ?></p></div>
                            </div>
                        </div>
                        <div class="table-wrapper">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Facility</th>
                                        <?php if ($hasCategory): ?><th>Category</th><?php endif; ?>
                                        <?php if ($hasPrice): ?><th>Price</th><?php endif; ?>
                                        <?php if ($hasDescription): ?><th>Description</th><?php endif; ?>
                                        <th>Bookings</th>
                                        <th>Status</th>
                                        <th style="text-align: right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="facilityTableBody">
                                    <?php if (empty($facilities)): ?>
                                        <tr><td colspan="7" style="text-align:center; color: rgba(47, 61, 46, 0.6); padding: 32px 0;">No facilities are available yet. Add rooms or products to open them for reservations.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($facilities as $facility): ?>
                                            <?php
                                                $rawStatus = $hasStatus ? ($facility[$statusColumn] ?? '') : '';
                                                $isOpen = $hasStatus ? facilityStatusIsOpen($rawStatus) : true;
                                                $statusLabel = $hasStatus ? ($isOpen ? 'Open' : 'Closed') : 'Available';
                                                $statusClass = $isOpen ? 'active' : 'inactive';
                                                $bookingCount = $bookingCounts[$facility['facility_id']] ?? 0;
                                            ?>
                                            <tr>
                                                <td style="font-weight: 600; color: #2F3D2E;"><?php echo htmlspecialchars($facility['name']); ?></td>
                                                <?php if ($hasCategory): ?><td><?php echo htmlspecialchars($categoryMap[$facility['category_id']] ?? 'Uncategorized'); ?></td><?php endif; ?>
                                                <?php if ($hasPrice): ?><td>₱ <?php echo number_format($facility['price'], 0); ?></td><?php endif; ?>
                                                <?php if ($hasDescription): ?><td style="color: rgba(47, 61, 46, 0.7); max-width: 280px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($facility['description']); ?>"><?php echo htmlspecialchars($facility['description']); ?></td><?php endif; ?>
                                                <td><?php echo number_format($bookingCount); ?></td>
                                                <td><span class="status-pill <?php echo $statusClass; ?>"><?php echo htmlspecialchars($statusLabel); ?></span></td>
                                                <td style="text-align: right; white-space: nowrap;">
                                                    <button class="action-btn edit-btn" title="Edit Facility"><i class="fas fa-pencil-alt"></i></button>
                                                    <button class="action-btn delete-btn" title="Setup 360 Tour"><i class="fas fa-camera"></i></button>
                                                </td>
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
        const facilitySearchInput = document.getElementById('facilitySearchInput');
        const facilityRows = document.querySelectorAll('#facilityTableBody tr');
        facilitySearchInput.addEventListener('keyup', function() {
            const term = this.value.toLowerCase();
            facilityRows.forEach(row => {
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
