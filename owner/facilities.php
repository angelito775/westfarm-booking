<?php
session_start();

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['user_type_id'] != 3) {
    header("Location: ../pages/login.php");
    exit();
}

require_once '../config/db_connection.php';
$user_id = $_SESSION['user_id'];

// Get current owner info
$stmt = $pdo->prepare("SELECT u.email, up.first_name, up.last_name FROM users u LEFT JOIN user_profiles up ON u.user_id = up.user_id WHERE u.user_id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();
$user_name = ($current_user && $current_user['first_name']) ? ($current_user['first_name'] . ' ' . $current_user['last_name']) : 'Owner';
$ownerNavActive = 'facilities-rooms';

// Dynamically fetch facilities based on what columns actually exist in the DB
$columns = $pdo->query("SHOW COLUMNS FROM facilities")->fetchAll(PDO::FETCH_COLUMN);
$selectFields = ['facility_id', 'name'];
$hasCapacity = in_array('capacity', $columns);
$hasDescription = in_array('description', $columns);
$hasCategory = in_array('category_id', $columns);

$galleryInfo = null;
$galleryTable = null;
$galleryCandidates = ['facility_images', 'gallery_images', 'facility_gallery', 'gallery', 'images', 'photos'];
foreach ($galleryCandidates as $candidate) {
    try {
        $pdo->query("SELECT 1 FROM $candidate LIMIT 1");
        $galleryTable = $candidate;
        break;
    } catch (PDOException $e) { /* table doesn't exist */ }
}

if ($galleryTable) {
    $galleryColumns = $pdo->query("SHOW COLUMNS FROM $galleryTable")->fetchAll(PDO::FETCH_COLUMN);
    $find = function($candidates) use ($galleryColumns) {
        foreach ($candidates as $field) {
            if (in_array($field, $galleryColumns, true)) return $field;
        }
        return null;
    };
    $galleryInfo = [
        'table' => $galleryTable,
        'facility_id_col' => $find(['facility_id', 'room_id']),
        'image_path_col' => $find(['image_path', 'photo_path', 'file_path', 'image_url', 'path']),
        'is_primary_col' => $find(['is_primary', 'is_cover', 'is_main']),
    ];
}

$priceColumn = null;
if (in_array('price', $columns)) $priceColumn = 'price';
elseif (in_array('base_price', $columns)) $priceColumn = 'base_price';
$hasPrice = !is_null($priceColumn);

$statusColumn = null;
if (in_array('status', $columns)) $statusColumn = 'status';
elseif (in_array('is_available', $columns)) $statusColumn = 'is_available';
elseif (in_array('is_active', $columns)) $statusColumn = 'is_active';

if ($hasCapacity) $selectFields[] = 'capacity';
if ($hasDescription) $selectFields[] = 'description';
if ($priceColumn) $selectFields[] = "$priceColumn AS price";
if ($statusColumn) $selectFields[] = $statusColumn;
if ($hasCategory) $selectFields[] = 'category_id';

$sql = 'SELECT f.' . implode(', f.', $selectFields) . ' FROM facilities f';

// Join with gallery table to get primary image if it exists
if ($galleryInfo && $galleryInfo['table'] && $galleryInfo['facility_id_col'] && $galleryInfo['image_path_col']) {
    $sql = 'SELECT f.' . implode(', f.', $selectFields) . ", MAX(fi.{$galleryInfo['image_path_col']}) AS primary_image_path FROM facilities f";
    $sql .= " LEFT JOIN {$galleryInfo['table']} fi ON f.facility_id = fi.{$galleryInfo['facility_id_col']}";
    if ($galleryInfo['is_primary_col']) {
        $sql .= " AND fi.{$galleryInfo['is_primary_col']} = 1";
    }
    $sql .= " GROUP BY f.facility_id"; // Group to ensure one row per facility
}

$sql .= ' ORDER BY f.name ASC';
$facilities = $pdo->query($sql)->fetchAll();

// Fetch Categories for mapping
$categoryMap = [];
$categoryRows = [];
try {
    $categoryRows = $pdo->query("SELECT category_id, name FROM categories ORDER BY name")->fetchAll();
    foreach ($categoryRows as $category) {
        $categoryMap[$category['category_id']] = $category['name'];
    }
} catch (PDOException $e) {
    // Categories table might not exist yet, fail silently
}

// Fetch Booking Counts
$facility_ids = array_column($facilities, 'facility_id');
$bookingCounts = [];
if (!empty($facility_ids)) {
    try {
        $placeholders = implode(',', array_fill(0, count($facility_ids), '?'));
        $stmt = $pdo->prepare("SELECT facility_id, COUNT(DISTINCT booking_id) AS booking_count FROM booking_items WHERE facility_id IN ($placeholders) GROUP BY facility_id");
        $stmt->execute($facility_ids);
        $bookingCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (PDOException $e) {
        // Booking items table might not exist yet
    }
}

function facilityStatusIsOpen($value) {
    $normalized = trim((string)$value);
    $openValues = ['1', 'active', 'open', 'available', 'yes', 'true', 'on'];
    return $normalized === '' || in_array(strtolower($normalized), $openValues, true);
}

$openFacilities = 0;
$closedFacilities = 0;
foreach ($facilities as $facility) {
    if ($statusColumn) {
        $value = $facility[$statusColumn] ?? '';
        if (facilityStatusIsOpen($value)) $openFacilities++;
        else $closedFacilities++;
    }
}
if (!$statusColumn) {
    $openFacilities = count($facilities);
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
    <style>
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    </style>
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
                        <input type="text" id="facilitySearchInput" placeholder="Search rooms, villas, or facilities...">
                    </div>
                </div>
                <div class="topbar-right">
                    <button class="notification-btn"><i class="fas fa-bell"></i><span class="notification-dot"></span></button>
                    <div class="user-section">
                        <div class="user-info">
                            <p class="user-name"><?php echo htmlspecialchars($user_name); ?></p>
                            <p class="user-role">Owner</p>
                        </div>
                        <div class="user-avatar"><i class="fas fa-user-tie"></i></div>
                    </div>
                </div>
            </header>

            <main class="main-content">
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert success">
                        <?php 
                        if ($_GET['success'] === 'facility_added') echo "Facility added successfully.";
                        elseif ($_GET['success'] === 'facility_updated') echo "Facility details updated successfully.";
                        elseif ($_GET['success'] === 'facility_deleted') echo "Facility has been deleted.";
                        elseif ($_GET['success'] === 'booking_created') echo "Booking created successfully.";
                        ?>
                        <button class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
                    </div>
                <?php elseif (isset($_GET['error'])): ?>
                    <div class="alert error">
                        <?php 
                        if ($_GET['error'] === 'empty_name') echo "Facility name is required.";
                        elseif ($_GET['error'] === 'empty_price') echo "Price is required.";
                        elseif ($_GET['error'] === 'invalid_price') echo "Price must be a valid number.";
                        elseif ($_GET['error'] === 'invalid_category') echo "A valid category must be selected.";
                        elseif ($_GET['error'] === 'add_failed') echo "Unable to save facility to database.";
                        elseif ($_GET['error'] === 'update_failed') echo "Unable to update facility details.";
                        elseif ($_GET['error'] === 'delete_failed') echo "Unable to delete facility.";
                        elseif ($_GET['error'] === 'delete_failed_has_bookings') echo "Cannot delete facility as it has existing bookings attached.";
                        elseif ($_GET['error'] === 'upload_failed') echo "Image upload failed. Check file type (JPG, PNG) and size (max 5MB).";
                        else echo "An error occurred. Please check your inputs.";
                        ?>
                        <button class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
                    </div>
                <?php endif; ?>

                <div class="section-card">
                    <div class="section-header">
                        <h3 class="section-title">Facility Inventory</h3>
                        <div style="display: flex; gap: 12px;">
                            <button id="addFacilityBtn" class="section-action" style="display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-plus"></i> Add Facility
                            </button>
                            <button id="newBookingBtn" class="section-action" style="display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-calendar-plus"></i> Manual Booking
                            </button>
                        </div>
                    </div>
                    
                    <div class="section-body">
                        <div class="grid-2-3" style="gap: 16px; margin-bottom: 24px; display: grid; grid-template-columns: repeat(3, 1fr);">
                            <div class="status-mini-card" style="background: #fff; padding: 15px; border-radius: 8px; border: 1px solid #eee; display: flex; align-items: center; gap: 15px;">
                                <div class="status-icon" style="background: #eef2ff; color: #4f46e5; padding: 15px; border-radius: 8px; font-size: 20px;"><i class="fas fa-building"></i></div>
                                <div><p style="margin: 0; color: #6b7280; font-size: 13px;">Total Facilities</p><p style="margin: 0; font-size: 20px; font-weight: bold;"><?php echo count($facilities); ?></p></div>
                            </div>
                            <div class="status-mini-card" style="background: #fff; padding: 15px; border-radius: 8px; border: 1px solid #eee; display: flex; align-items: center; gap: 15px;">
                                <div class="status-icon" style="background: #dcfce7; color: #16a34a; padding: 15px; border-radius: 8px; font-size: 20px;"><i class="fas fa-door-open"></i></div>
                                <div><p style="margin: 0; color: #6b7280; font-size: 13px;">Open / Available</p><p style="margin: 0; font-size: 20px; font-weight: bold;"><?php echo $openFacilities; ?></p></div>
                            </div>
                            <div class="status-mini-card" style="background: #fff; padding: 15px; border-radius: 8px; border: 1px solid #eee; display: flex; align-items: center; gap: 15px;">
                                <div class="status-icon" style="background: #fee2e2; color: #dc2626; padding: 15px; border-radius: 8px; font-size: 20px;"><i class="fas fa-tools"></i></div>
                                <div><p style="margin: 0; color: #6b7280; font-size: 13px;">Closed / Maint.</p><p style="margin: 0; font-size: 20px; font-weight: bold;"><?php echo $closedFacilities; ?></p></div>
                            </div>
                        </div>

                        <div class="card-grid" id="facilityGrid">
                            <?php if (empty($facilities)): ?>
                                <div style="grid-column: 1 / -1; text-align: center; color: #6b7280; padding: 40px 0;">
                                    No facilities are currently in the system. Click "Add Facility" to begin.
                                </div>
                            <?php else: ?>
                                <?php foreach ($facilities as $facility): ?>
                                    <?php
                                        $rawStatus = $statusColumn ? ($facility[$statusColumn] ?? '') : '';
                                        $isOpen = $statusColumn ? facilityStatusIsOpen($rawStatus) : true;
                                        $statusLabel = $isOpen ? 'Active' : 'Maintenance';
                                        $statusColor = $isOpen ? '#16a34a' : '#dc2626';
                                        $statusBg = $isOpen ? '#dcfce7' : '#fee2e2';
                                        $bookingCount = $bookingCounts[$facility['facility_id']] ?? 0;
                                        $imagePath = $facility['primary_image_path'] ?? '';
                                    ?>
                                    <div class="facility-card" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
                                        <div style="height: 140px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 32px; overflow: hidden;">
                                            <?php if ($imagePath && file_exists('../' . $imagePath)): ?>
                                                <img class="facility-image-previewable" src="../<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($facility['name']); ?>" style="width: 100%; height: 100%; object-fit: cover; cursor: pointer;">
                                            <?php else: ?>
                                                <i class="fas fa-bed"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div style="padding: 16px;">
                                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                                <h4 style="margin: 0; color: #2F3D2E; font-size: 16px;"><?php echo htmlspecialchars($facility['name']); ?></h4>
                                                <span style="background: <?php echo $statusBg; ?>; color: <?php echo $statusColor; ?>; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: bold;">
                                                    <?php echo $statusLabel; ?>
                                                </span>
                                            </div>
                                            
                                            <div style="font-size: 13px; color: #6b7280; margin-bottom: 12px;">
                                                <?php if ($hasCategory): ?>
                                                    <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($categoryMap[$facility['category_id']] ?? 'Uncategorized'); ?></span><br>
                                                <?php endif; ?>
                                                <?php if ($hasPrice): ?>
                                                    <span style="color: #2F3D2E; font-weight: bold; margin-top: 4px; display: inline-block;">₱ <?php echo number_format($facility['price'], 2); ?> / night</span>
                                                <?php endif; ?>
                                            </div>

                                            <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 15px; border-top: 1px solid #eee; padding-top: 12px;">
                                                <button class="action-btn edit-btn" title="Edit Facility" style="color: #3b82f6; border: 1px solid #bfdbfe; background: #eff6ff; padding: 4px 8px; border-radius: 4px; cursor: pointer;"
                                                    data-id="<?php echo $facility['facility_id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($facility['name']); ?>"
                                                    data-category-id="<?php echo $facility['category_id'] ?? ''; ?>"
                                                    data-price="<?php echo $facility['price'] ?? '0'; ?>"
                                                    data-capacity="<?php echo $facility['capacity'] ?? ''; ?>"
                                                    data-status="<?php echo $isOpen ? 'active' : 'inactive'; ?>"
                                                    data-description="<?php echo htmlspecialchars($facility['description'] ?? ''); ?>"
                                                    data-image-path="<?php echo htmlspecialchars($imagePath); ?>"
                                                >
                                                    <i class="fas fa-pencil-alt"></i>
                                                </button>
                                                <button class="action-btn delete-btn" title="Delete Facility" style="color: #ef4444; border: 1px solid #fecaca; background: #fef2f2; padding: 4px 8px; border-radius: 4px; cursor: pointer;"
                                                    data-id="<?php echo $facility['facility_id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($facility['name']); ?>"
                                                >
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <div id="facilityModal" class="modal-overlay" style="display: none;">
        <div class="modal" style="max-width: 500px;">
            <div class="modal-header">
                <h3 id="facilityModalTitle" class="modal-title">Add New Facility</h3>
                <button class="modal-close" onclick="closeModal('facilityModal')">&times;</button>
            </div>
            <form id="facilityForm" action="../logic/facility_process.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_facility">
                    <input type="hidden" name="facility_id" id="facilityId" value="">

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label for="facility_name" style="display:block; margin-bottom:5px; font-weight:500;">Facility Name <span style="color:red;">*</span></label>
                        <input type="text" id="facility_name" name="name" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label style="display:block; margin-bottom:5px; font-weight:500;">Category <span style="color:red;">*</span></label>
                            <select name="category_id" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                                <option value="">Select Category</option>
                                <?php foreach ($categoryRows as $cat): ?>
                                    <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label style="display:block; margin-bottom:5px; font-weight:500;">Price / Night (₱) <span style="color:red;">*</span></label>
                            <input type="number" name="price" min="0" step="0.01" required placeholder="e.g. 1500" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                        </div>
                    </div>

                    <div class="form-grid" style="margin-top: 15px;">
                        <div class="form-group">
                            <label style="display:block; margin-bottom:5px; font-weight:500;">Max Capacity (Pax)</label>
                            <input type="number" name="capacity" min="1" placeholder="e.g. 4" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                        </div>
                        <div class="form-group">
                            <label style="display:block; margin-bottom:5px; font-weight:500;">Status</label>
                            <select name="status" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                                <option value="active">Active / Open</option>
                                <option value="inactive">Maintenance / Closed</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 15px;">
                        <label style="display:block; margin-bottom:5px; font-weight:500;">Description</label>
                        <textarea name="description" rows="3" placeholder="Detail the amenities, bed types, etc." style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; resize: vertical;"></textarea>
                    </div>

                    <div class="form-group" style="margin-top: 15px;">
                        <label style="display:block; margin-bottom:5px; font-weight:500;">Primary Photo</label>
                        <div id="imagePreview" style="width: 150px; height: 100px; border: 1px dashed #ccc; border-radius: 4px; background: #f9f9f9; display: flex; align-items: center; justify-content: center; margin-bottom: 10px; overflow: hidden; position: relative;">
                            <span style="color: #999; font-size: 12px; text-align: center; padding: 5px;">Image Preview</span>
                        </div>
                        <input type="file" name="facility_image" id="facilityImageInput" accept="image/jpeg, image/png, image/gif" style="display: block; font-size: 13px;">
                        <input type="hidden" name="existing_image_path" id="existingImagePath">
                        <small style="display: block; margin-top: 5px; color: #6b7280;">Max file size: 5MB. Recommended: JPG, PNG.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('facilityModal')">Cancel</button>
                    <button type="submit" class="btn-primary" id="facilitySubmitBtn">Save Facility</button>
                </div>
            </form>
        </div>
    </div>

    <div id="reservationModal" class="modal-overlay" style="display: none;">
        <div class="modal" style="max-width: 600px;">
            <div class="modal-header">
                <h3 class="modal-title">Create Manual Booking</h3>
                <button class="modal-close" onclick="closeModal('reservationModal')">&times;</button>
            </div>
            <form id="reservationForm" action="../logic/booking_process.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="owner_add_booking">
                    
                    <h4 style="margin: 0 0 10px 0; color: #2F3D2E; border-bottom: 1px solid #eee; padding-bottom: 5px;">Guest Details</h4>
                    <div class="form-group" style="margin-bottom: 10px;">
                        <label style="display:block; margin-bottom:5px; font-weight:500;">Guest Full Name <span style="color:red;">*</span></label>
                        <input type="text" name="guest_name" required placeholder="e.g. Juan Dela Cruz" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label style="display:block; margin-bottom:5px; font-weight:500;">Contact Number</label>
                            <input type="text" name="guest_phone" placeholder="09XX..." style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                        </div>
                        <div class="form-group">
                            <label style="display:block; margin-bottom:5px; font-weight:500;">Number of Guests (Pax) <span style="color:red;">*</span></label>
                            <input type="number" name="num_guests" min="1" value="1" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                        </div>
                    </div>

                    <h4 style="margin: 20px 0 10px 0; color: #2F3D2E; border-bottom: 1px solid #eee; padding-bottom: 5px;">Stay Details</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label style="display:block; margin-bottom:5px; font-weight:500;">Check-in Date <span style="color:red;">*</span></label>
                            <input type="date" id="booking_checkin" name="check_in_date" required min="<?php echo date('Y-m-d'); ?>" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                        </div>
                        <div class="form-group">
                            <label style="display:block; margin-bottom:5px; font-weight:500;">Check-out Date <span style="color:red;">*</span></label>
                            <input type="date" id="booking_checkout" name="check_out_date" required min="<?php echo date('Y-m-d'); ?>" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                        </div>
                    </div>
                    
                    <div class="form-grid" style="margin-top: 10px;">
                        <div class="form-group">
                            <label style="display:block; margin-bottom:5px; font-weight:500;">Category</label>
                            <select id="booking_category" name="category_id" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                                <option value="">Any Category</option>
                                <?php foreach ($categoryRows as $cat): ?>
                                    <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label style="display:block; margin-bottom:5px; font-weight:500;">Select Unit <span style="color:red;">*</span></label>
                            <select id="booking_unit" name="facility_id" required disabled style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                                <option value="">-- Set dates first --</option>
                            </select>
                        </div>
                    </div>

                    <div id="bookingPricePreview" style="background: #eef2ff; color: #4f46e5; padding: 12px; border-radius: 8px; margin-top: 10px; font-size: 14px; display: none; text-align: right; border: 1px solid #c7d2fe;">
                        <strong>Calculated Total:</strong> <span id="pricePreviewAmount" style="font-size: 18px; font-weight: bold;">₱ 0</span>
                    </div>

                    <h4 style="margin: 20px 0 10px 0; color: #2F3D2E; border-bottom: 1px solid #eee; padding-bottom: 5px;">Status Settings</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label style="display:block; margin-bottom:5px; font-weight:500;">Booking Status</label>
                            <select name="booking_status" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                                <option value="Confirmed">Confirmed (Walk-in / Approved)</option>
                                <option value="Pending">Pending (Waiting for payment)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label style="display:block; margin-bottom:5px; font-weight:500;">Payment Status</label>
                            <select name="payment_status" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                                <option value="Paid">Paid (Cash / Transferred)</option>
                                <option value="Unpaid" selected>Unpaid</option>
                                <option value="Partial">Downpayment Only</option>
                            </select>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('reservationModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Create Booking</button>
                </div>
            </form>
        </div>
    </div>

    <div id="deleteFacilityModal" class="modal-overlay" style="display: none;">
        <div class="modal" style="max-width: 400px;">
            <div class="modal-header">
                <h3 class="modal-title">Confirm Deletion</h3>
                <button class="modal-close" onclick="closeModal('deleteFacilityModal')">&times;</button>
            </div>
            <form action="../logic/facility_process.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete_facility">
                    <input type="hidden" name="facility_id" id="deleteFacilityId">
                    <p>Are you sure you want to permanently delete the facility <strong id="deleteFacilityName"></strong>? This may also affect existing bookings and cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('deleteFacilityModal')">Cancel</button>
                    <button type="submit" class="btn-danger">Delete Facility</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Image Preview Modal -->
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

        // Live Search
        document.getElementById('facilitySearchInput').addEventListener('keyup', function() {
            const term = this.value.toLowerCase();
            document.querySelectorAll('.facility-card').forEach(card => {
                card.style.display = card.textContent.toLowerCase().includes(term) ? '' : 'none';
            });
        });

        // Add Facility Modal trigger
        document.getElementById('addFacilityBtn').addEventListener('click', function() {
            const form = document.getElementById('facilityForm');
            form.reset();
            document.getElementById('facilityModalTitle').innerText = 'Add New Facility';
            form.querySelector('[name="action"]').value = 'add_facility';
            form.querySelector('[name="facility_id"]').value = '';
            document.getElementById('facilitySubmitBtn').innerText = 'Save Facility';
            
            // Reset image preview
            document.getElementById('imagePreview').innerHTML = '<span style="color: #999; font-size: 12px; text-align: center; padding: 5px;">Image Preview</span>';
            document.getElementById('facilityImageInput').value = '';
            document.getElementById('existingImagePath').value = '';

            openModal('facilityModal');
        });

        // Edit Facility Modal trigger
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                const form = document.getElementById('facilityForm');
                const data = this.dataset;

                document.getElementById('facilityModalTitle').innerText = 'Edit Facility';
                form.querySelector('[name="action"]').value = 'edit_facility';
                form.querySelector('[name="facility_id"]').value = data.id;
                form.querySelector('[name="name"]').value = data.name;
                form.querySelector('[name="category_id"]').value = data.categoryId;
                form.querySelector('[name="price"]').value = data.price;
                form.querySelector('[name="capacity"]').value = data.capacity;
                form.querySelector('[name="status"]').value = data.status;
                form.querySelector('[name="description"]').value = data.description;
                
                // Handle image preview
                const imagePath = data.imagePath;
                const imagePreview = document.getElementById('imagePreview');
                document.getElementById('existingImagePath').value = imagePath;
                document.getElementById('facilityImageInput').value = ''; // Clear file input
                if (imagePath) {
                    imagePreview.innerHTML = `<img src="../${imagePath}" style="width: 100%; height: 100%; object-fit: cover;" alt="Preview">`;
                } else {
                    imagePreview.innerHTML = '<span style="color: #999; font-size: 12px; text-align: center; padding: 5px;">Image Preview</span>';
                }

                document.getElementById('facilitySubmitBtn').innerText = 'Save Changes';
                openModal('facilityModal');
            });
        });

        // Delete Facility Modal trigger
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                const name = this.dataset.name;
                document.getElementById('deleteFacilityId').value = id;
                document.getElementById('deleteFacilityName').innerText = name;
                openModal('deleteFacilityModal');
            });
        });

        // New Booking Modal trigger
        document.getElementById('newBookingBtn').addEventListener('click', function() {
            document.getElementById('reservationForm').reset();
            document.getElementById('booking_unit').disabled = true;
            document.getElementById('booking_unit').innerHTML = '<option value="">-- Set dates first --</option>';
            document.getElementById('bookingPricePreview').style.display = 'none';
            openModal('reservationModal');
        });

        // ========== BOOKING MODAL LOGIC ==========
        // Define category restrictions at the top level
        const singleDayCategories = ['Cottage', 'Pool', 'Event Hall'];
        const allDayCategories = ['Cottage', 'Pool', 'Event Hall', 'Private Villa', 'Glamping'];

        // Get references to booking form elements
        const checkinInput = document.getElementById('booking_checkin');
        const checkoutInput = document.getElementById('booking_checkout');
        const categoryInput = document.getElementById('booking_category');
        const unitSelect = document.getElementById('booking_unit');
        const priceDiv = document.getElementById('bookingPricePreview');
        const priceAmount = document.getElementById('pricePreviewAmount');

        const today = new Date().toISOString().split('T')[0];

        function resetBookingDateLimits() {
            checkinInput.min = today;
            checkoutInput.min = today;
        }

        document.getElementById('newBookingBtn').addEventListener('click', function() {
            resetBookingDateLimits();
            checkinInput.value = '';
            checkoutInput.value = '';
            unitSelect.disabled = true;
            unitSelect.innerHTML = '<option value="">-- Set dates first --</option>';
            priceDiv.style.display = 'none';
            openModal('reservationModal');
        });

        checkinInput.addEventListener('change', function() {
            checkoutInput.min = this.value || today;
            if (checkoutInput.value && checkoutInput.value < checkoutInput.min) {
                checkoutInput.value = '';
            }
            fetchAvailableUnits();
        });

        function fetchAvailableUnits() {
            const categoryId = categoryInput.value;
            const checkIn = checkinInput.value;
            const checkOut = checkoutInput.value;

            // Determine if same-day booking
            const isSameDay = checkIn === checkOut && checkIn !== '';

            // Get the selected category name from the dropdown
            const categoryOption = categoryInput.options[categoryInput.selectedIndex];
            const selectedCategoryName = categoryOption ? categoryOption.text : '';

            // Validate category availability based on dates
            if (categoryId) {
                if (isSameDay && !singleDayCategories.includes(selectedCategoryName)) {
                    unitSelect.innerHTML = '<option value="">⚠️ ' + selectedCategoryName + ' requires multiple nights</option>';
                    unitSelect.disabled = true;
                    priceDiv.style.display = 'none';
                    return;
                }
            }

            // Disable unit select if no category selected
            unitSelect.disabled = !categoryId;

            // Clear units if category not selected
            if (!categoryId) {
                unitSelect.innerHTML = '<option value="">-- First select a category --</option>';
                priceDiv.style.display = 'none';
                return;
            }

            // Both dates required to fetch units
            if (!checkIn || !checkOut) {
                unitSelect.innerHTML = '<option value="">-- Select both dates first --</option>';
                priceDiv.style.display = 'none';
                return;
            }

            fetch(`../logic/get_available_units.php?category_id=${categoryId}&check_in=${checkIn}&check_out=${checkOut}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        unitSelect.innerHTML = '<option value="">-- Select Unit --</option>';
                        data.units.forEach(unit => {
                            const opt = document.createElement('option');
                            opt.value = unit.id;
                            opt.textContent = `${unit.name} (₱${unit.price})`;
                            opt.dataset.price = unit.price;
                            unitSelect.appendChild(opt);
                        });
                    } else {
                        unitSelect.innerHTML = `<option value="">No units available: ${data.message}</option>`;
                    }
                })
                .catch(err => {
                    unitSelect.innerHTML = '<option value="">Error loading units</option>';
                    console.error(err);
                });
        }

        function calculatePrice() {
            const option = unitSelect.options[unitSelect.selectedIndex];
            if (!option || !option.dataset.price) {
                priceDiv.style.display = 'none';
                return;
            }
            const price = parseFloat(option.dataset.price);
            const checkInDate = new Date(checkinInput.value);
            const checkOutDate = new Date(checkoutInput.value);
            
            // For same-day bookings, count as 1 night
            const nights = checkInDate.toDateString() === checkOutDate.toDateString() 
                ? 1 
                : Math.ceil((checkOutDate - checkInDate) / (1000 * 60 * 60 * 24));
            
            if (nights > 0) {
                priceAmount.textContent = `₱ ${(price * nights).toLocaleString()}`;
                priceDiv.style.display = 'block';
            }
        }

        // Event listeners
        checkinInput.addEventListener('change', fetchAvailableUnits);
        checkoutInput.addEventListener('change', fetchAvailableUnits);
        
        // When category changes, reset unit selection
        categoryInput.addEventListener('change', function() {
            unitSelect.value = '';  // Reset unit selection
            unitSelect.innerHTML = '<option value="">-- Loading units... --</option>';
            priceDiv.style.display = 'none';
            fetchAvailableUnits();
        });
        
        unitSelect.addEventListener('change', calculatePrice);

        // Image Preview Handler
        const imageInput = document.getElementById('facilityImageInput');
        const imagePreview = document.getElementById('imagePreview');

        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.innerHTML = `<img src="${e.target.result}" style="width: 100%; height: 100%; object-fit: cover;" alt="New Preview">`;
                }
                reader.readAsDataURL(file);
            }
        });

        // Facility Card Image Preview Modal
        document.querySelectorAll('.facility-image-previewable').forEach(image => {
            image.addEventListener('click', function(e) {
                e.stopPropagation();
                document.getElementById('previewModalImage').src = this.src;
                openModal('imagePreviewModal');
            });
        });
    </script>
</body>
</html>