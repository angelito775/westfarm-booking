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

// Fetch all facilities for dropdowns
$allFacilities = [];
if ($facilityField) {
    try {
        $allFacilities = $pdo->query("SELECT facility_id, name FROM facilities ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $allFacilities = [];
    }
}
$pkField = findField($galleryColumns, ['id', 'gallery_id', 'image_id', $galleryTable.'_id']);

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
    <style>
        .gallery-card { position: relative; }
        .gallery-card .gallery-overlay {
            position: absolute;
            top: 0; right: 0; bottom: 0; left: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            opacity: 0;
            transition: opacity 0.2s ease-in-out;
        }
        .gallery-card:hover .gallery-overlay { opacity: 1; }
    </style>
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
                        <h3 class="section-title">Gallery Photos (<?php echo count($galleryItems); ?>)</h3>
                        <button id="uploadPhotoBtn" class="section-action" style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-upload"></i> Upload Photo
                        </button>
                    </div>
                    <div class="section-body">
                        <?php if (isset($_GET['success'])): ?>
                            <div class="alert success">
                                <?php if ($_GET['success'] === 'photo_uploaded') echo "Photo uploaded successfully."; ?>
                                <?php if ($_GET['success'] === 'photo_updated') echo "Photo details updated successfully."; ?>
                                <?php if ($_GET['success'] === 'photo_deleted') echo "Photo has been deleted."; ?>
                                <button class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
                            </div>
                        <?php elseif (isset($_GET['error'])): ?>
                            <div class="alert error">
                                <?php if ($_GET['error'] === 'upload_failed') echo "Image upload failed. Please check file type and size."; ?>
                                <?php if ($_GET['error'] === 'db_error') echo "A database error occurred."; ?>
                                <button class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
                            </div>
                        <?php endif; ?>

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
                                        $itemId = $pkField ? ($item[$pkField] ?? '') : '';
                                        $imagePath = $imageField && !empty($item[$imageField]) ? $item[$imageField] : '';
                                        $caption = $captionField ? trim($item[$captionField]) : '';
                                        $facilityId = $facilityField && isset($item[$facilityField]) ? $item[$facilityField] : '';
                                        $facilityLabel = $facilityId ? ($facilityNames[$facilityId] ?? 'Facility #'.$facilityId) : '';
                                    ?>
                                    <div class="gallery-card">
                                        <?php if ($imagePath && file_exists('../' . $imagePath)): ?>
                                            <img src="../<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($caption ?: 'Gallery image'); ?>" class="gallery-image-previewable" style="cursor: pointer;">
                                            <div class="gallery-overlay">
                                                <button class="action-btn edit-btn" title="Edit Photo"
                                                    data-id="<?php echo $itemId; ?>"
                                                    data-caption="<?php echo htmlspecialchars($caption); ?>"
                                                    data-facility-id="<?php echo $facilityId; ?>"
                                                    data-image-path="<?php echo htmlspecialchars($imagePath); ?>">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </button>
                                                <button class="action-btn delete-btn" title="Delete Photo"
                                                    data-id="<?php echo $itemId; ?>"
                                                    data-caption="<?php echo htmlspecialchars($caption ?: 'this image'); ?>">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <div class="gallery-placeholder"><i class="fas fa-image"></i></div>
                                        <?php endif; ?>
                                        <div class="gallery-caption">
                                            <p class="gallery-title"><?php echo htmlspecialchars($caption ?: 'Untitled Image'); ?></p>
                                            <?php if ($facilityLabel): ?><p class="gallery-meta"><?php echo htmlspecialchars($facilityLabel); ?></p><?php endif; ?>
                                            <?php if (!empty($item['created_at'])): ?><p class="gallery-meta">Uploaded: <?php echo date('M d, Y', strtotime($item['created_at'])); ?></p><?php endif; ?>
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

    <!-- Modals -->
    <div id="galleryModal" class="modal-overlay" style="display: none;">
        <div class="modal" style="max-width: 500px;">
            <div class="modal-header">
                <h3 id="galleryModalTitle" class="modal-title">Upload New Photo</h3>
                <button class="modal-close" onclick="closeModal('galleryModal')">&times;</button>
            </div>
            <form id="galleryForm" action="../logic/gallery_process.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" id="galleryAction" value="upload_photo">
                    <input type="hidden" name="gallery_item_id" id="galleryItemId" value="">

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom:5px; font-weight:500;">Photo File</label>
                        <div id="imagePreview" style="width: 150px; height: 100px; border: 1px dashed #ccc; border-radius: 4px; background: #f9f9f9; display: flex; align-items: center; justify-content: center; margin-bottom: 10px; overflow: hidden;">
                            <span style="color: #999; font-size: 12px; text-align: center; padding: 5px;">Image Preview</span>
                        </div>
                        <input type="file" name="gallery_image" id="galleryImageInput" accept="image/jpeg, image/png, image/gif" style="display: block; font-size: 13px;">
                        <small id="imageUploadHelp" style="display: block; margin-top: 5px; color: #6b7280;">Required for new uploads. Max 5MB.</small>
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label for="caption" style="display:block; margin-bottom:5px; font-weight:500;">Caption / Description</label>
                        <input type="text" id="caption" name="caption" placeholder="e.g. Sunset view from the main pool" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>

                    <?php if ($facilityField && !empty($allFacilities)): ?>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label for="facility_id" style="display:block; margin-bottom:5px; font-weight:500;">Link to Facility (Optional)</label>
                        <select name="facility_id" id="facility_id" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                            <option value="">-- No specific facility --</option>
                            <?php foreach ($allFacilities as $facility): ?>
                                <option value="<?php echo $facility['facility_id']; ?>"><?php echo htmlspecialchars($facility['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('galleryModal')">Cancel</button>
                    <button type="submit" class="btn-primary" id="gallerySubmitBtn">Upload Photo</button>
                </div>
            </form>
        </div>
    </div>

    <div id="deleteModal" class="modal-overlay" style="display: none;">
        <div class="modal" style="max-width: 400px;">
            <div class="modal-header">
                <h3 class="modal-title">Confirm Deletion</h3>
                <button class="modal-close" onclick="closeModal('deleteModal')">&times;</button>
            </div>
            <form action="../logic/gallery_process.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete_photo">
                    <input type="hidden" name="gallery_item_id" id="deleteItemId">
                    <p>Are you sure you want to permanently delete <strong id="deleteItemName"></strong>? This cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
                    <button type="submit" class="btn-danger">Delete Photo</button>
                </div>
            </form>
        </div>
    </div>

    <div id="imagePreviewModal" class="modal-overlay" style="display: none; background-color: rgba(0,0,0,0.85); z-index: 1001;">
        <div class="modal" style="background: transparent; box-shadow: none; max-width: 90vw; max-height: 90vh; padding: 0; display: flex; align-items: center; justify-content: center;">
            <button class="modal-close" onclick="closeModal('imagePreviewModal')" style="position: absolute; top: 15px; right: 25px; font-size: 2.5rem; color: white; text-shadow: 0 1px 4px black; line-height: 1;">&times;</button>
            <img id="previewModalImage" src="" alt="Image Preview" style="max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 8px;">
        </div>
    </div>

    <script>
        function openModal(modalId) { document.getElementById(modalId).style.display = 'flex'; }
        function closeModal(modalId) { document.getElementById(modalId).style.display = 'none'; }
        window.onclick = function(event) { if (event.target.classList.contains('modal-overlay')) closeModal(event.target.id); }

        const gallerySearchInput = document.getElementById('gallerySearchInput');
        const galleryCards = document.querySelectorAll('.gallery-card');
        gallerySearchInput.addEventListener('keyup', function() {
            const term = this.value.toLowerCase();
            galleryCards.forEach(card => {
                const text = card.textContent.toLowerCase();
                card.style.display = text.includes(term) ? '' : 'none';
            });
        });

        // Upload button
        document.getElementById('uploadPhotoBtn').addEventListener('click', function() {
            const form = document.getElementById('galleryForm');
            form.reset();
            document.getElementById('galleryModalTitle').innerText = 'Upload New Photo';
            document.getElementById('galleryAction').value = 'upload_photo';
            document.getElementById('galleryItemId').value = '';
            document.getElementById('gallerySubmitBtn').innerText = 'Upload Photo';
            document.getElementById('galleryImageInput').required = true;
            document.getElementById('imageUploadHelp').innerText = 'Required for new uploads. Max 5MB.';
            document.getElementById('imagePreview').innerHTML = '<span style="color: #999; font-size: 12px; text-align: center; padding: 5px;">Image Preview</span>';
            openModal('galleryModal');
        });

        // Edit buttons
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                const data = this.dataset;
                const form = document.getElementById('galleryForm');
                form.reset();

                document.getElementById('galleryModalTitle').innerText = 'Edit Photo Details';
                document.getElementById('galleryAction').value = 'edit_photo';
                document.getElementById('gallerySubmitBtn').innerText = 'Save Changes';
                document.getElementById('galleryItemId').value = data.id;
                document.getElementById('caption').value = data.caption;
                if (document.getElementById('facility_id')) {
                    document.getElementById('facility_id').value = data.facilityId;
                }
                
                document.getElementById('galleryImageInput').required = false;
                document.getElementById('imageUploadHelp').innerText = 'Optional: Upload a new file to replace the existing one.';
                
                const imagePreview = document.getElementById('imagePreview');
                if (data.imagePath) {
                    imagePreview.innerHTML = `<img src="../${data.imagePath}" style="width: 100%; height: 100%; object-fit: cover;" alt="Preview">`;
                } else {
                    imagePreview.innerHTML = '<span style="color: #999; font-size: 12px; text-align: center; padding: 5px;">Image Preview</span>';
                }

                openModal('galleryModal');
            });
        });

        // Delete buttons
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const data = this.dataset;
                document.getElementById('deleteItemId').value = data.id;
                document.getElementById('deleteItemName').innerText = `"${data.caption}"`;
                openModal('deleteModal');
            });
        });

        // Image Preview in Form
        document.getElementById('galleryImageInput').addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imagePreview').innerHTML = `<img src="${e.target.result}" style="width: 100%; height: 100%; object-fit: cover;" alt="New Preview">`;
                }
                reader.readAsDataURL(file);
            }
        });

        // Large Image Preview Modal
        document.querySelectorAll('.gallery-image-previewable').forEach(image => {
            image.addEventListener('click', function(e) {
                e.stopPropagation();
                document.getElementById('previewModalImage').src = this.src;
                openModal('imagePreviewModal');
            });
        });


        // Logout Modal
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
