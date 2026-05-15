<?php
session_start();
require_once '../config/db_connection.php';

// Only allow logged-in owners
if (!isset($_SESSION['user_id']) || $_SESSION['user_type_id'] != 3) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

// Get parameters
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$check_in = $_GET['check_in'] ?? '';
$check_out = $_GET['check_out'] ?? '';

if (empty($check_in) || empty($check_out)) {
    echo json_encode(['success' => false, 'message' => 'Please select both check-in and check-out dates']);
    exit();
}

// Validate date order
$check_in_ts = strtotime($check_in);
$check_out_ts = strtotime($check_out);
if ($check_in_ts === false || $check_out_ts === false || $check_out_ts <= $check_in_ts) {
    echo json_encode(['success' => false, 'message' => 'Check-out date must be after check-in date']);
    exit();
}

try {
    // Get columns from facilities table to determine status column name
    $columns = $pdo->query("SHOW COLUMNS FROM facilities")->fetchAll(PDO::FETCH_COLUMN);
    
    // Determine price column
    $priceColumn = 'price';
    if (in_array('base_price', $columns) && !in_array('price', $columns)) {
        $priceColumn = 'base_price';
    }
    
    // Determine status column
    $statusColumn = 'status';
    if (!in_array('status', $columns)) {
        if (in_array('is_available', $columns)) {
            $statusColumn = 'is_available';
        } elseif (in_array('is_active', $columns)) {
            $statusColumn = 'is_active';
        } else {
            $statusColumn = null;
        }
    }
    
    // Build query to get available facilities (units) for the given category and date range
    // A facility is available if:
    // 1. It belongs to the selected category
    // 2. It is marked as open/active
    // 3. There is NO overlapping booking (existing booking that overlaps with requested date range)
    
    $params = [
        'check_in' => $check_in,
        'check_out' => $check_out
    ];
    $sql = "SELECT f.facility_id, f.name, f.{$priceColumn} AS price 
            FROM facilities f 
            WHERE 1=1";
    
    if ($category_id > 0) {
        $sql .= " AND f.category_id = :category_id";
        $params['category_id'] = $category_id;
    }
    // Add status condition if status column exists
    if ($statusColumn === 'status') {
        $sql .= " AND f.status IN ('active', 'open', 'available')";
    } elseif ($statusColumn === 'is_available') {
        $sql .= " AND f.is_available = 1";
    } elseif ($statusColumn === 'is_active') {
        $sql .= " AND f.is_active = 1";
    }
    
    // Exclude facilities that have overlapping bookings (not cancelled/refunded)
    $sql .= " AND NOT EXISTS (
        SELECT 1 FROM booking_items bi 
        JOIN bookings b ON bi.booking_id = b.booking_id 
        WHERE bi.facility_id = f.facility_id 
        AND bi.check_in_date < :check_out 
        AND bi.check_out_date > :check_in
        AND b.booking_status_id NOT IN (SELECT booking_status_id FROM booking_statuses WHERE status_name IN ('Cancelled', 'Refunded'))
    )";
    
    $sql .= " ORDER BY f.name ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    $units = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format units for JSON response
    $formattedUnits = [];
    foreach ($units as $unit) {
        $formattedUnits[] = [
            'id' => $unit['facility_id'],
            'name' => $unit['name'],
            'price' => (float)$unit['price']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'units' => $formattedUnits,
        'count' => count($formattedUnits)
    ]);
    
} catch (PDOException $e) {
    error_log('get_available_units error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred. Please try again.'
    ]);
}
?>