<?php
session_start();
require_once '../config/db_connection.php';

// Security: Only logged-in owners can manage gallery
if (!isset($_SESSION['user_id']) || $_SESSION['user_type_id'] != 3) {
    header("Location: ../pages/login.php");
    exit();
}

$redirect_page = "../owner/gallery.php";

// Helper to find table and column names dynamically
function getGallerySchema($pdo) {
    $galleryTable = null;
    $galleryCandidates = ['gallery', 'gallery_images', 'facility_gallery', 'facility_images', 'images', 'photos'];
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
        'pk_col' => $find(['id', 'gallery_id', 'image_id', $galleryTable.'_id']),
        'image_path_col' => $find(['image_path', 'photo_path', 'file_path', 'image_url', 'path']),
        'caption_col' => $find(['caption', 'title', 'description', 'name']),
        'facility_id_col' => $find(['facility_id', 'room_id']),
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $schema = getGallerySchema($pdo);
    if (!$schema || !$schema['pk_col'] || !$schema['image_path_col']) {
        header("Location: {$redirect_page}?error=db_error");
        exit();
    }

    $uploadDir = '../uploads/gallery/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // ==========================================
    // UPLOAD/ADD PHOTO
    // ==========================================
    if ($_POST['action'] === 'upload_photo' || $_POST['action'] === 'edit_photo') {
        $isEdit = $_POST['action'] === 'edit_photo';
        $caption = trim($_POST['caption'] ?? '');
        $facility_id = filter_input(INPUT_POST, 'facility_id', FILTER_VALIDATE_INT) ?: null;
        $gallery_item_id = $isEdit ? filter_input(INPUT_POST, 'gallery_item_id', FILTER_VALIDATE_INT) : null;

        if ($isEdit && !$gallery_item_id) {
            header("Location: {$redirect_page}?error=db_error");
            exit();
        }

        $imagePath = null;
        // Handle file upload
        if (isset($_FILES['gallery_image']) && $_FILES['gallery_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['gallery_image'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($file['type'], $allowedTypes) || $file['size'] > 5000000) { // 5MB limit
                header("Location: {$redirect_page}?error=upload_failed");
                exit();
            }
            $fileName = uniqid('gallery_', true) . '.' . strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $uploadPath = $uploadDir . $fileName;
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $imagePath = 'uploads/gallery/' . $fileName; // Path to store in DB
            } else {
                header("Location: {$redirect_page}?error=upload_failed");
                exit();
            }
        }

        if (!$isEdit && !$imagePath) { // New photo must have an image
            header("Location: {$redirect_page}?error=upload_failed");
            exit();
        }

        try {
            if ($isEdit) {
                // Build update query
                $setParts = [];
                $params = [':id' => $gallery_item_id];
                if ($schema['caption_col']) { $setParts[] = "{$schema['caption_col']} = :caption"; $params[':caption'] = $caption; }
                if ($schema['facility_id_col']) { $setParts[] = "{$schema['facility_id_col']} = :facility_id"; $params[':facility_id'] = $facility_id; }
                
                if ($imagePath) { // If a new image was uploaded
                    // Get old image path to delete it
                    $stmtOld = $pdo->prepare("SELECT {$schema['image_path_col']} FROM {$schema['table']} WHERE {$schema['pk_col']} = :id");
                    $stmtOld->execute([':id' => $gallery_item_id]);
                    $oldImagePath = $stmtOld->fetchColumn();

                    $setParts[] = "{$schema['image_path_col']} = :image_path";
                    $params[':image_path'] = $imagePath;
                }

                if (!empty($setParts)) {
                    $sql = "UPDATE {$schema['table']} SET " . implode(', ', $setParts) . " WHERE {$schema['pk_col']} = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                }

                // Delete old file after DB update is successful
                if ($imagePath && $oldImagePath && file_exists('../' . $oldImagePath)) {
                    @unlink('../' . $oldImagePath);
                }
                header("Location: {$redirect_page}?success=photo_updated");

            } else {
                // Build insert query
                $sqlCols = [$schema['image_path_col']];
                $sqlVals = [':image_path'];
                $params = [':image_path' => $imagePath];

                if ($schema['caption_col']) { $sqlCols[] = $schema['caption_col']; $sqlVals[] = ':caption'; $params[':caption'] = $caption; }
                if ($schema['facility_id_col']) { $sqlCols[] = $schema['facility_id_col']; $sqlVals[] = ':facility_id'; $params[':facility_id'] = $facility_id; }

                $sql = "INSERT INTO {$schema['table']} (".implode(', ', $sqlCols).") VALUES (".implode(', ', $sqlVals).")";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                header("Location: {$redirect_page}?success=photo_uploaded");
            }
        } catch (PDOException $e) {
            error_log("Gallery process error: " . $e->getMessage());
            // If upload was successful but DB failed, delete the orphaned file
            if ($imagePath && file_exists('../' . $imagePath)) {
                @unlink('../' . $imagePath);
            }
            header("Location: {$redirect_page}?error=db_error");
        }
        exit();
    }

    // ==========================================
    // DELETE PHOTO
    // ==========================================
    elseif ($_POST['action'] === 'delete_photo') {
        $gallery_item_id = filter_input(INPUT_POST, 'gallery_item_id', FILTER_VALIDATE_INT);
        if (!$gallery_item_id) {
            header("Location: {$redirect_page}?error=db_error");
            exit();
        }

        try {
            // Get image path to delete the file
            $stmt = $pdo->prepare("SELECT {$schema['image_path_col']} FROM {$schema['table']} WHERE {$schema['pk_col']} = :id");
            $stmt->execute([':id' => $gallery_item_id]);
            $imagePath = $stmt->fetchColumn();

            // Delete DB record
            $stmtDelete = $pdo->prepare("DELETE FROM {$schema['table']} WHERE {$schema['pk_col']} = :id");
            $stmtDelete->execute([':id' => $gallery_item_id]);

            // Delete file from server
            if ($imagePath && file_exists('../' . $imagePath)) {
                @unlink('../' . $imagePath);
            }

            header("Location: {$redirect_page}?success=photo_deleted");
        } catch (PDOException $e) {
            error_log("Delete photo error: " . $e->getMessage());
            header("Location: {$redirect_page}?error=db_error");
        }
        exit();
    }
}

header("Location: {$redirect_page}");
exit();
?>