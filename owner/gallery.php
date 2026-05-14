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
$ownerNavActive = 'gallery-management';

function tableExists($pdo, $tableName)
{
    $tableName = str_replace("'", "\\'", $tableName);
    $result = $pdo->query("SHOW TABLES LIKE '$tableName'");
    return (bool)$result && $result->fetch();
}

$galleryTable = null;
$galleryCandidates = ['gallery', 'gallery_images', 'facility_gallery', 'facility_images', 'images', 'photos'];
foreach ($galleryCandidates as $candidate) {
    if (tableExists($pdo, $candidate)) {
        $galleryTable = $candidate;
        break;
    }
}

$galleryItems = [];
$galleryColumns = [];
if ($galleryTable) {
    $galleryColumns = $pdo->query("SHOW COLUMNS FROM $galleryTable")->fetchAll(PDO::FETCH_COLUMN);
    $orderField = in_array('created_at', $galleryColumns) ? 'created_at' : (in_array('uploaded_at', $galleryColumns) ? 'uploaded_at' : (in_array('updated_at', $galleryColumns) ? 'updated_at' : ''));
    $sql = "SELECT * FROM $galleryTable" . ($orderField ? " ORDER BY $orderField DESC" : "") . " LIMIT 40";
    $galleryItems = $pdo->query($sql)->fetchAll();
}

$imageFieldCandidates = ['image_path', 'photo_path', 'file_path', 'image_url', 'photo_url', 'path', 'filename', 'file_name'];
$captionFieldCandidates = ['caption', 'title', 'name', 'description'];
$facilityFieldCandidates = ['facility_id', 'room_id'];

function findField(array $columns, array $candidates)
{
    foreach ($candidates as $field) {
        if (in_array($field, $columns, true)) {
            return $field;
        }
    }
    return null;
}

$imageField = findField($galleryColumns, $imageFieldCandidates);
$captionField = findField($galleryColumns, $captionFieldCandidates);
$facilityField = findField($galleryColumns, $facilityFieldCandidates);

$facilityNames = [];
if ($facilityField && !empty($galleryItems)) {
    $facilityIds = array_unique(array_filter(array_column($galleryItems, $facilityField)));
    if (!empty($facilityIds)) {
        $placeholders = implode(',', array_fill(0, count($facilityIds), '?'));
        $stmt = $pdo->prepare("SELECT facility_id, name FROM facilities WHERE facility_id IN ($placeholders)");
        $stmt->execute($facilityIds);
        $facilityNames = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery Management | Owner Dashboard</title>
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
                    <h2 class="topbar-title">Gallery Management</h2>
                    <div class="search-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" id="gallerySearchInput" placeholder="Search gallery images or facility photos...">
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
                        <h3 class="section-title">Gallery Management</h3>
                        <button class="section-action" onclick="alert('Image upload will be added later');">Upload Photo</button>
                    </div>
                    <div class="section-body">
                        <?php if (!$galleryTable): ?>
                            <div class="notification-card">
                                <p>No gallery table found in the database yet.</p>
                                <p>Expected tables include <strong>gallery</strong>, <strong>gallery_images</strong>, <strong>facility_gallery</strong>, or <strong>facility_images</strong>.</p>
                            </div>
                        <?php elseif (empty($galleryItems)): ?>
                            <div class="notification-card">
                                <p>You have not added any gallery pictures yet.</p>
                                <p>Upload photos of rooms, resort grounds, and facilities to make the booking experience more visual.</p>
                            </div>
                        <?php else: ?>
                            <div class="grid-3-3" style="gap: 18px;">
                                <?php foreach ($galleryItems as $item): ?>
                                    <?php
                                        $imageSrc = $imageField && !empty($item[$imageField]) ? $item[$imageField] : '';
                                        $caption = $captionField ? trim($item[$captionField]) : '';
                                        $facilityLabel = $facilityField && isset($item[$facilityField]) ? ($facilityNames[$item[$facilityField]] ?? 'Facility #'.$item[$facilityField]) : '';
                                    ?>
                                    <div class="gallery-card">
                                        <?php if ($imageSrc): ?>
                                            <img src="<?php echo htmlspecialchars($imageSrc); ?>" alt="<?php echo htmlspecialchars($caption ?: 'Gallery image'); ?>">
                                        <?php else: ?>
                                            <div class="gallery-placeholder"><i class="fas fa-image"></i></div>
                                        <?php endif; ?>
                                        <div class="gallery-caption">
                                            <p class="gallery-title"><?php echo htmlspecialchars($caption ?: 'Untitled Image'); ?></p>
                                            <?php if ($facilityLabel): ?><p class="gallery-meta"><?php echo htmlspecialchars($facilityLabel); ?></p><?php endif; ?>
                                            <?php if (!empty($item['created_at'])): ?><p class="gallery-meta"><?php echo date('M d, Y', strtotime($item['created_at'])); ?></p><?php endif; ?>
                                        </div>
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
        const gallerySearchInput = document.getElementById('gallerySearchInput');
        const galleryCards = document.querySelectorAll('.gallery-card');
        gallerySearchInput.addEventListener('keyup', function() {
            const term = this.value.toLowerCase();
            galleryCards.forEach(card => {
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
