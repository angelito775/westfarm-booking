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
$ownerNavActive = 'guest-reviews';

function tableExists($pdo, $tableName)
{
    $tableName = str_replace("'", "\\'", $tableName);
    $result = $pdo->query("SHOW TABLES LIKE '$tableName'");
    return (bool)$result && $result->fetch();
}

$reviewTable = null;
$reviewCandidates = ['reviews', 'guest_reviews', 'booking_reviews', 'feedback', 'customer_reviews', 'comments'];
foreach ($reviewCandidates as $candidate) {
    if (tableExists($pdo, $candidate)) {
        $reviewTable = $candidate;
        break;
    }
}

$reviews = [];
$reviewColumns = [];
if ($reviewTable) {
    $reviewColumns = $pdo->query("SHOW COLUMNS FROM $reviewTable")->fetchAll(PDO::FETCH_COLUMN);
    $orderField = in_array('created_at', $reviewColumns) ? 'created_at' : (in_array('submitted_at', $reviewColumns) ? 'submitted_at' : (in_array('updated_at', $reviewColumns) ? 'updated_at' : ''));
    $sql = "SELECT * FROM $reviewTable" . ($orderField ? " ORDER BY $orderField DESC" : "") . " LIMIT 40";
    $reviews = $pdo->query($sql)->fetchAll();
}

function findField(array $columns, array $candidates)
{
    foreach ($candidates as $field) {
        if (in_array($field, $columns, true)) {
            return $field;
        }
    }
    return null;
}

$customerField = findField($reviewColumns, ['customer_id', 'user_id', 'guest_id']);
$ratingField = findField($reviewColumns, ['rating', 'stars', 'score']);
$commentField = findField($reviewColumns, ['comment', 'feedback', 'review_text', 'message']);
$bookingField = findField($reviewColumns, ['booking_id']);
$facilityField = findField($reviewColumns, ['facility_id', 'room_id']);

$customerProfiles = [];
if ($customerField && !empty($reviews)) {
    $customerIds = array_filter(array_unique(array_column($reviews, $customerField)));
    if (!empty($customerIds)) {
        $placeholders = implode(',', array_fill(0, count($customerIds), '?'));
        $stmt = $pdo->prepare("SELECT u.user_id, up.first_name, up.last_name FROM users u LEFT JOIN user_profiles up ON u.user_id = up.user_id WHERE u.user_id IN ($placeholders)");
        $stmt->execute($customerIds);
        $customerProfiles = $stmt->fetchAll(PDO::FETCH_UNIQUE);
    }
}

$facilityNames = [];
if ($facilityField && !empty($reviews)) {
    $facilityIds = array_filter(array_unique(array_column($reviews, $facilityField)));
    if (!empty($facilityIds)) {
        $placeholders = implode(',', array_fill(0, count($facilityIds), '?'));
        $stmt = $pdo->prepare("SELECT facility_id, name FROM facilities WHERE facility_id IN ($placeholders)");
        $stmt->execute($facilityIds);
        $facilityNames = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}

$totalRating = 0;
$ratingCount = 0;
foreach ($reviews as $row) {
    if ($ratingField && isset($row[$ratingField]) && is_numeric($row[$ratingField])) {
        $totalRating += $row[$ratingField];
        $ratingCount++;
    }
}
$averageRating = $ratingCount ? round($totalRating / $ratingCount, 1) : null;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Reviews | Owner Dashboard</title>
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
                    <h2 class="topbar-title">Guest Reviews</h2>
                    <div class="search-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" id="reviewSearchInput" placeholder="Search reviews, ratings, or feedback...">
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
                        <h3 class="section-title">Guest Reviews</h3>
                        <button class="section-action" onclick="alert('Review moderation will be added later');">Refresh Reviews</button>
                    </div>
                    <div class="section-body">
                        <?php if (!$reviewTable): ?>
                            <div class="notification-card">
                                <p>No reviews table was found in the database.</p>
                                <p>Expected tables include <strong>reviews</strong>, <strong>guest_reviews</strong>, or <strong>booking_reviews</strong>.</p>
                            </div>
                        <?php elseif (empty($reviews)): ?>
                            <div class="notification-card">
                                <p>No guest reviews have been posted yet.</p>
                                <p>Once guests complete their stay, their feedback and ratings will appear here.</p>
                            </div>
                        <?php else: ?>
                            <div class="review-summary" style="margin-bottom: 24px; display: flex; gap: 16px; flex-wrap: wrap;">
                                <div class="status-mini-card">
                                    <div class="status-icon confirmed"><i class="fas fa-star"></i></div>
                                    <div class="status-content"><p>Average Rating</p><p><?php echo $averageRating !== null ? $averageRating . '/5' : 'N/A'; ?></p></div>
                                </div>
                                <div class="status-mini-card">
                                    <div class="status-icon confirmed"><i class="fas fa-comments"></i></div>
                                    <div class="status-content"><p>Total Reviews</p><p><?php echo count($reviews); ?></p></div>
                                </div>
                            </div>
                            <div class="reviews-list">
                                <?php foreach ($reviews as $review): ?>
                                    <?php
                                        $customerId = $customerField ? ($review[$customerField] ?? null) : null;
                                        $customerName = 'Guest';
                                        if ($customerId && isset($customerProfiles[$customerId])) {
                                            $profile = $customerProfiles[$customerId];
                                            $customerName = trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? '')) ?: 'Guest';
                                        }
                                        $ratingValue = $ratingField && isset($review[$ratingField]) ? $review[$ratingField] : null;
                                        $commentText = $commentField && isset($review[$commentField]) ? $review[$commentField] : '';
                                        $facilityLabel = '';
                                        if ($facilityField && isset($review[$facilityField])) {
                                            $facilityLabel = $facilityNames[$review[$facilityField]] ?? 'Facility #' . $review[$facilityField];
                                        }
                                        $postedAt = $review['created_at'] ?? ($review['submitted_at'] ?? ($review['updated_at'] ?? null));
                                    ?>
                                    <div class="review-card">
                                        <div class="review-card-header">
                                            <div>
                                                <p class="review-card-user"><?php echo htmlspecialchars($customerName); ?></p>
                                                <p class="review-card-meta"><?php echo htmlspecialchars($facilityLabel ?: 'Guest review'); ?></p>
                                            </div>
                                            <?php if ($ratingValue !== null): ?>
                                                <span class="status-pill confirmed"><?php echo htmlspecialchars($ratingValue); ?>/5</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="review-card-text"><?php echo htmlspecialchars($commentText ?: 'No written feedback provided.'); ?></p>
                                        <?php if ($postedAt): ?>
                                            <p class="review-card-date"><?php echo date('M d, Y', strtotime($postedAt)); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
            <div class="dashboard-footer">© 2026 West Farm Resort and Hotel · Basista, Pangasinan</div>
        </div>
    </div>
    <script>
        const reviewSearchInput = document.getElementById('reviewSearchInput');
        const reviewCards = document.querySelectorAll('.review-card');
        reviewSearchInput.addEventListener('keyup', function() {
            const term = this.value.toLowerCase();
            reviewCards.forEach(card => {
                const text = card.textContent.toLowerCase();
                card.style.display = text.includes(term) ? '' : 'none';
            });
        });

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
