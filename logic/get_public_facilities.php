<?php
require_once '../config/db_connection.php';

header('Content-Type: application/json');

try {
    // Dynamically detect columns in facilities table
    $columns = $pdo->query("SHOW COLUMNS FROM facilities")->fetchAll(PDO::FETCH_COLUMN);

    $selectFields = ['f.facility_id', 'f.name'];
    $hasCapacity = in_array('capacity', $columns);
    $hasDescription = in_array('description', $columns);
    $hasCategory = in_array('category_id', $columns);

    // Price column
    $priceColumn = null;
    if (in_array('price', $columns)) $priceColumn = 'price';
    elseif (in_array('base_price', $columns)) $priceColumn = 'base_price';

    // Status column
    $statusColumn = null;
    if (in_array('status', $columns)) $statusColumn = 'status';
    elseif (in_array('is_available', $columns)) $statusColumn = 'is_available';
    elseif (in_array('is_active', $columns)) $statusColumn = 'is_active';

    if ($hasCapacity) $selectFields[] = 'f.capacity';
    if ($hasDescription) $selectFields[] = 'f.description';
    if ($priceColumn) $selectFields[] = "f.{$priceColumn} AS price";
    if ($hasCategory) $selectFields[] = 'f.category_id';

    // Try to join with gallery table for primary image
    $galleryTable = null;
    $galleryCandidates = ['facility_images', 'gallery_images', 'facility_gallery', 'gallery', 'images', 'photos'];
    foreach ($galleryCandidates as $candidate) {
        try {
            $pdo->query("SELECT 1 FROM {$candidate} LIMIT 1");
            $galleryTable = $candidate;
            break;
        } catch (PDOException $e) { /* table doesn't exist */ }
    }

    $imageSelect = '';
    $joinClause = '';
    if ($galleryTable) {
        $galleryColumns = $pdo->query("SHOW COLUMNS FROM {$galleryTable}")->fetchAll(PDO::FETCH_COLUMN);
        $findCol = function($candidates) use ($galleryColumns) {
            foreach ($candidates as $field) {
                if (in_array($field, $galleryColumns, true)) return $field;
            }
            return null;
        };
        $facilityIdCol = $findCol(['facility_id', 'room_id']);
        $imagePathCol = $findCol(['image_path', 'photo_path', 'file_path', 'image_url', 'path']);
        $isPrimaryCol = $findCol(['is_primary', 'is_cover', 'is_main']);

        if ($facilityIdCol && $imagePathCol) {
            $imageSelect = ", fi.{$imagePathCol} AS image_path";
            $joinClause = " LEFT JOIN {$galleryTable} fi ON f.facility_id = fi.{$facilityIdCol}";
            if ($isPrimaryCol) {
                $joinClause .= " AND fi.{$isPrimaryCol} = 1";
            }
        }
    }

    $sql = 'SELECT ' . implode(', ', $selectFields) . $imageSelect . ' FROM facilities f' . $joinClause;

    // Only show active/open facilities to the public
    if ($statusColumn === 'status') {
        $sql .= " WHERE f.status IN ('active', 'open', 'available')";
    } elseif ($statusColumn === 'is_available') {
        $sql .= " WHERE f.is_available = 1";
    } elseif ($statusColumn === 'is_active') {
        $sql .= " WHERE f.is_active = 1";
    }

    $sql .= ' ORDER BY f.name ASC';

    $stmt = $pdo->query($sql);
    $facilities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch category names for mapping
    $categoryMap = [];
    try {
        $catRows = $pdo->query("SELECT category_id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($catRows as $cat) {
            $categoryMap[$cat['category_id']] = $cat['name'];
        }
    } catch (PDOException $e) {
        // categories table might not exist
    }

    // Build response
    $items = [];
    foreach ($facilities as $f) {
        $item = [
            'id' => (int)$f['facility_id'],
            'name' => $f['name'],
            'price' => isset($f['price']) ? (float)$f['price'] : 0,
            'description' => $f['description'] ?? '',
            'capacity' => isset($f['capacity']) ? (int)$f['capacity'] : null,
            'category' => ($hasCategory && isset($f['category_id']) && isset($categoryMap[$f['category_id']])) ? $categoryMap[$f['category_id']] : 'Uncategorized',
            'image' => $f['image_path'] ?? '',
        ];
        $items[] = $item;
    }

    // Collect unique categories for filter tabs
    $categories = [];
    foreach ($items as $item) {
        if (!in_array($item['category'], $categories)) {
            $categories[] = $item['category'];
        }
    }
    sort($categories);

    echo json_encode([
        'success' => true,
        'items' => $items,
        'categories' => $categories,
    ]);

} catch (PDOException $e) {
    error_log('get_public_facilities error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred.'
    ]);
}
