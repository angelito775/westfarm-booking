<?php
session_start();
require_once '../config/db_connection.php';

// Security: Only logged-in owners can manage facilities
// In this system, owners have user_type_id = 3
if (!isset($_SESSION['user_id']) || $_SESSION['user_type_id'] != 3) {
    header("Location: ../pages/login.php");
    exit();
}

// Define the redirect page for convenience
$redirect_page = "../owner/facilities.php";

function findGalleryInfo($pdo) {
    $galleryTable = null;
    $galleryCandidates = ['facility_images', 'gallery_images', 'facility_gallery', 'gallery', 'images', 'photos'];
    foreach ($galleryCandidates as $candidate) {
        try {
            $pdo->query("SELECT 1 FROM $candidate LIMIT 1");
            $galleryTable = $candidate;
            break;
        } catch (PDOException $e) { /* table doesn't exist */ }
    }

    if (!$galleryTable) return null;

    $columns = $pdo->query("SHOW COLUMNS FROM $galleryTable")->fetchAll(PDO::FETCH_COLUMN);

    $find = function($candidates) use ($columns) {
        foreach ($candidates as $field) {
            if (in_array($field, $columns, true)) return $field;
        }
        return null;
    };

    return [
        'table' => $galleryTable,
        'facility_id_col' => $find(['facility_id', 'room_id']),
        'image_path_col' => $find(['image_path', 'photo_path', 'file_path', 'image_url', 'path']),
        'is_primary_col' => $find(['is_primary', 'is_cover', 'is_main']),
    ];
}

// Check if a POST request was made with an action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // ==========================================
    // ADD NEW FACILITY
    // ==========================================
    if ($_POST['action'] === 'add_facility') {
        // 1. Sanitize and retrieve data from the form
        $name = trim($_POST['name']);
        $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
        $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
        $capacity = filter_input(INPUT_POST, 'capacity', FILTER_VALIDATE_INT);
        $status = $_POST['status'] ?? 'active'; // Default to active
        $description = trim($_POST['description']);

        // 2. Basic Validation
        if (empty($name)) {
            header("Location: {$redirect_page}?error=empty_name");
            exit();
        }
        if ($price === false || $price < 0) {
            header("Location: {$redirect_page}?error=invalid_price");
            exit();
        }
        if (empty($category_id) || $category_id === false) {
            header("Location: {$redirect_page}?error=invalid_category");
            exit();
        }

        try {
            // 3. Get table columns to build a dynamic query
            $columnsResult = $pdo->query("SHOW COLUMNS FROM facilities");
            $tableColumns = $columnsResult->fetchAll(PDO::FETCH_COLUMN);

            $galleryInfo = findGalleryInfo($pdo);
            $imagePath = null;
            if (isset($_FILES['facility_image']) && $_FILES['facility_image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../uploads/facilities/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $file = $_FILES['facility_image'];
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($file['type'], $allowedTypes) || $file['size'] > 5000000) { // 5MB limit
                    header("Location: {$redirect_page}?error=upload_failed");
                    exit();
                }
                $fileName = uniqid('facility_', true) . '.' . strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $uploadPath = $uploadDir . $fileName;
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    $imagePath = 'uploads/facilities/' . $fileName; // Path to store in DB
                }
            }

            // Find the correct price column name
            $priceColumn = 'price'; // default
            if (in_array('base_price', $tableColumns) && !in_array('price', $tableColumns)) {
                $priceColumn = 'base_price';
            }

            // 4. Prepare data and columns for insertion
            $insertData = [];
            $sqlColumns = [];
            $sqlPlaceholders = [];

            // Always include name, category_id, price
            $sqlColumns = ['name', 'category_id', $priceColumn];
            $sqlPlaceholders = [':name', ':category_id', ':price_val'];
            $insertData = [
                ':name' => $name,
                ':category_id' => $category_id,
                ':price_val' => $price,
            ];

            // Conditionally add other fields if the column exists
            if (in_array('capacity', $tableColumns)) {
                $sqlColumns[] = 'capacity';
                $sqlPlaceholders[] = ':capacity';
                $insertData[':capacity'] = $capacity ?: null;
            }
            if (in_array('description', $tableColumns)) {
                $sqlColumns[] = 'description';
                $sqlPlaceholders[] = ':description';
                $insertData[':description'] = $description;
            }

            // Handle the status column dynamically
            if (in_array('status', $tableColumns)) {
                $sqlColumns[] = 'status';
                $sqlPlaceholders[] = ':status';
                $insertData[':status'] = $status;
            } elseif (in_array('is_available', $tableColumns)) {
                $sqlColumns[] = 'is_available';
                $sqlPlaceholders[] = ':is_available';
                $insertData[':is_available'] = ($status === 'active') ? 1 : 0;
            } elseif (in_array('is_active', $tableColumns)) {
                $sqlColumns[] = 'is_active';
                $sqlPlaceholders[] = ':is_active';
                $insertData[':is_active'] = ($status === 'active') ? 1 : 0;
            }

            // 5. Build the final SQL statement and execute
            $sql = sprintf(
                "INSERT INTO facilities (%s) VALUES (%s)",
                implode(', ', $sqlColumns),
                implode(', ', $sqlPlaceholders)
            );
            $stmt = $pdo->prepare($sql);
            $stmt->execute($insertData);
            $new_facility_id = $pdo->lastInsertId();

            // 6. If an image was uploaded, insert it into the gallery table
            if ($imagePath && $galleryInfo && $galleryInfo['table'] && $galleryInfo['facility_id_col'] && $galleryInfo['image_path_col']) {
                $sqlGallery = "INSERT INTO {$galleryInfo['table']} ({$galleryInfo['facility_id_col']}, {$galleryInfo['image_path_col']}";
                $paramsGallery = [':facility_id' => $new_facility_id, ':image_path' => $imagePath];

                if ($galleryInfo['is_primary_col']) {
                    $sqlGallery .= ", {$galleryInfo['is_primary_col']}) VALUES (:facility_id, :image_path, 1)";
                } else {
                    $sqlGallery .= ") VALUES (:facility_id, :image_path)";
                }
                
                $stmtGallery = $pdo->prepare($sqlGallery);
                $stmtGallery->execute($paramsGallery);
            }

            header("Location: {$redirect_page}?success=facility_added");
            exit();

        } catch (PDOException $e) {
            error_log("Add Facility Error: " . $e->getMessage());
            header("Location: {$redirect_page}?error=add_failed");
            exit();
        }
    }

    // ==========================================
    // EDIT FACILITY
    // ==========================================
    elseif ($_POST['action'] === 'edit_facility') {
        $facility_id = filter_input(INPUT_POST, 'facility_id', FILTER_VALIDATE_INT);
        $name = trim($_POST['name']);
        $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
        $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
        $capacity = filter_input(INPUT_POST, 'capacity', FILTER_VALIDATE_INT);
        $status = $_POST['status'] ?? 'active';
        $description = trim($_POST['description']);

        if (!$facility_id || !$name || $price === false || $price < 0 || !$category_id) {
            header("Location: {$redirect_page}?error=update_failed");
            exit();
        }

        try {
            $columnsResult = $pdo->query("SHOW COLUMNS FROM facilities");
            $tableColumns = $columnsResult->fetchAll(PDO::FETCH_COLUMN);

            $galleryInfo = findGalleryInfo($pdo);
            $newImagePath = null;
            // Handle new image upload
            if (isset($_FILES['facility_image']) && $_FILES['facility_image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../uploads/facilities/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $file = $_FILES['facility_image'];
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($file['type'], $allowedTypes) || $file['size'] > 5000000) { // 5MB limit
                    header("Location: {$redirect_page}?error=upload_failed");
                    exit();
                }
                $fileName = uniqid('facility_', true) . '.' . strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $uploadPath = $uploadDir . $fileName;
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    // The old image becomes a regular gallery image. We don't delete it here.
                    $newImagePath = 'uploads/facilities/' . $fileName; // New path
                }
            }

            // Find the correct price column name
            $priceColumn = 'price'; // default
            if (in_array('base_price', $tableColumns) && !in_array('price', $tableColumns)) {
                $priceColumn = 'base_price';
            }

            $updateData = [':facility_id' => $facility_id, ':name' => $name, ':category_id' => $category_id, ':price_val' => $price];
            $sqlSetParts = ['name = :name', 'category_id = :category_id', "$priceColumn = :price_val"];

            if (in_array('capacity', $tableColumns)) { $sqlSetParts[] = 'capacity = :capacity'; $updateData[':capacity'] = $capacity ?: null; }
            if (in_array('description', $tableColumns)) { $sqlSetParts[] = 'description = :description'; $updateData[':description'] = $description; }
            
            // Handle status column
            if (in_array('status', $tableColumns)) { 
                $sqlSetParts[] = 'status = :status'; $updateData[':status'] = $status; 
            } elseif (in_array('is_available', $tableColumns)) { 
                $sqlSetParts[] = 'is_available = :is_available'; $updateData[':is_available'] = ($status === 'active') ? 1 : 0; 
            } elseif (in_array('is_active', $tableColumns)) { 
                $sqlSetParts[] = 'is_active = :is_active'; $updateData[':is_active'] = ($status === 'active') ? 1 : 0; 
            }

            $sql = sprintf("UPDATE facilities SET %s WHERE facility_id = :facility_id", implode(', ', $sqlSetParts));
            $stmt = $pdo->prepare($sql);
            $stmt->execute($updateData);

            // Handle image update in the gallery table
            if ($newImagePath && $galleryInfo && $galleryInfo['table'] && $galleryInfo['facility_id_col'] && $galleryInfo['image_path_col']) {
                
                // If there's a concept of a "primary" image, set all others to not-primary first
                if ($galleryInfo['is_primary_col']) {
                    $stmtUnset = $pdo->prepare("UPDATE {$galleryInfo['table']} SET {$galleryInfo['is_primary_col']} = 0 WHERE {$galleryInfo['facility_id_col']} = :facility_id");
                    $stmtUnset->execute([':facility_id' => $facility_id]);
                }

                // Insert the new primary image
                $sqlGallery = "INSERT INTO {$galleryInfo['table']} ({$galleryInfo['facility_id_col']}, {$galleryInfo['image_path_col']}";
                $paramsGallery = [':facility_id' => $facility_id, ':image_path' => $newImagePath];

                if ($galleryInfo['is_primary_col']) {
                    $sqlGallery .= ", {$galleryInfo['is_primary_col']}) VALUES (:facility_id, :image_path, 1)";
                } else {
                    $sqlGallery .= ") VALUES (:facility_id, :image_path)";
                }
                $stmtGallery = $pdo->prepare($sqlGallery);
                $stmtGallery->execute($paramsGallery);
            }

            header("Location: {$redirect_page}?success=facility_updated");
            exit();
        } catch (PDOException $e) {
            error_log("Edit Facility Error: " . $e->getMessage());
            header("Location: {$redirect_page}?error=update_failed");
            exit();
        }
    }

    // ==========================================
    // DELETE FACILITY
    // ==========================================
    elseif ($_POST['action'] === 'delete_facility') {
        $facility_id = filter_input(INPUT_POST, 'facility_id', FILTER_VALIDATE_INT);
        if (!$facility_id) { header("Location: {$redirect_page}?error=delete_failed"); exit(); }
        try {
            $galleryInfo = findGalleryInfo($pdo);

            // If a gallery table exists, find and delete all associated image files
            if ($galleryInfo && $galleryInfo['table'] && $galleryInfo['facility_id_col'] && $galleryInfo['image_path_col']) {
                $stmt = $pdo->prepare("SELECT {$galleryInfo['image_path_col']} FROM {$galleryInfo['table']} WHERE {$galleryInfo['facility_id_col']} = :id");
                $stmt->execute([':id' => $facility_id]);
                $imagePaths = $stmt->fetchAll(PDO::FETCH_COLUMN);
                foreach ($imagePaths as $path) {
                    if ($path && file_exists('../' . $path)) {
                        @unlink('../' . $path);
                    }
                }
                // Delete the records from the gallery table (if no ON DELETE CASCADE)
                $stmtDelete = $pdo->prepare("DELETE FROM {$galleryInfo['table']} WHERE {$galleryInfo['facility_id_col']} = :id");
                $stmtDelete->execute([':id' => $facility_id]);
            }

            $stmt = $pdo->prepare("DELETE FROM facilities WHERE facility_id = :id");
            $stmt->execute([':id' => $facility_id]);
            header("Location: {$redirect_page}?success=facility_deleted");
            exit();
        } catch (PDOException $e) {
            error_log("Delete Facility Error: " . $e->getMessage());
            header("Location: {$redirect_page}?error=delete_failed_has_bookings");
            exit();
        }
    }
} else {
    // If accessed without a POST request, just redirect back
    header("Location: {$redirect_page}");
    exit();
}