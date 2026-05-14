<?php
session_start();
require_once '../config/db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type_id'] != 3) {
    header("Location: ../pages/access_denied.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // ==================== ADD NEW FACILITY ====================
    if ($_POST['action'] === 'add_facility') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = isset($_POST['price']) ? trim($_POST['price']) : '';
        $category_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? $_POST['category_id'] : null;
        $status = trim($_POST['status'] ?? 'active');

        try {
            $columns = $pdo->query("SHOW COLUMNS FROM facilities")->fetchAll(PDO::FETCH_COLUMN);

            if ($name === '') {
                header("Location: ../owner/facilities.php?error=empty_name");
                exit();
            }

            if ($price === '' || !is_numeric($price)) {
                header("Location: ../owner/facilities.php?error=invalid_price");
                exit();
            }

            $price = (float) $price;

            if (in_array('category_id', $columns) && $category_id === null) {
                header("Location: ../owner/facilities.php?error=empty_category");
                exit();
            }

            $fields = ['name'];
            $params = ['name' => $name];

            if (in_array('description', $columns)) {
                $fields[] = 'description';
                $params['description'] = $description;
            }

            if (in_array('price', $columns)) {
                $fields[] = 'price';
                $params['price'] = $price;
            }

            if (in_array('category_id', $columns)) {
                $fields[] = 'category_id';
                $params['category_id'] = $category_id;
            }

            if (in_array('status', $columns)) {
                $fields[] = 'status';
                $params['status'] = $status === 'active' ? 'active' : 'inactive';
            } elseif (in_array('is_available', $columns)) {
                $fields[] = 'is_available';
                $params['is_available'] = $status === 'active' ? 1 : 0;
            } elseif (in_array('is_active', $columns)) {
                $fields[] = 'is_active';
                $params['is_active'] = $status === 'active' ? 1 : 0;
            }

            $placeholders = array_map(fn($field) => ':' . $field, $fields);
            $sql = 'INSERT INTO facilities (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $placeholders) . ')';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            header("Location: ../owner/facilities.php?success=facility_added");
            exit();
        } catch (PDOException $e) {
            error_log('Add Facility Error: ' . $e->getMessage());
            header("Location: ../owner/facilities.php?error=add_failed");
            exit();
        }
    }
    
    // ==================== CREATE NEW BOOKING / RESERVATION ====================
    if ($_POST['action'] === 'add_reservation') {
        $guest_name = trim($_POST['guest_name'] ?? '');
        $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
        $facility_id = isset($_POST['facility_id']) ? (int)$_POST['facility_id'] : 0;
        $check_in = $_POST['check_in_date'] ?? '';
        $check_out = $_POST['check_out_date'] ?? '';
        $user_id = $_SESSION['user_id'];

        // Validate inputs
        if (empty($guest_name) || $category_id <= 0 || $facility_id <= 0 || empty($check_in) || empty($check_out)) {
            header("Location: ../owner/facilities.php?error=booking_failed");
            exit();
        }

        // Validate date range
        $check_in_ts = strtotime($check_in);
        $check_out_ts = strtotime($check_out);
        if ($check_in_ts === false || $check_out_ts === false || $check_out_ts <= $check_in_ts) {
            header("Location: ../owner/facilities.php?error=invalid_dates");
            exit();
        }

        // Get facility details (price, name, status)
        $stmt = $pdo->prepare("SELECT name, price, status FROM facilities WHERE facility_id = ?");
        $stmt->execute([$facility_id]);
        $facility = $stmt->fetch();
        if (!$facility) {
            header("Location: ../owner/facilities.php?error=booking_failed");
            exit();
        }

        // Check if facility is open for booking
        $facilityStatus = $facility['status'] ?? '';
        $isOpen = in_array(strtolower(trim($facilityStatus)), ['active', 'open', 'available', '1', 'yes', 'true']);
        if (!$isOpen) {
            header("Location: ../owner/facilities.php?error=unit_unavailable");
            exit();
        }

        $price_per_night = (float)$facility['price'];
        $nights = ceil(($check_out_ts - $check_in_ts) / (60 * 60 * 24));
        $total_amount = $price_per_night * $nights;

        // Verify that the unit is still available for the requested dates (prevent race conditions)
        $check_sql = "SELECT COUNT(*) as cnt FROM booking_items bi 
                      JOIN bookings b ON bi.booking_id = b.booking_id 
                      WHERE bi.facility_id = ? 
                      AND b.check_in_date <= ? 
                      AND b.check_out_date >= ?
                      AND b.status NOT IN ('cancelled', 'refunded')";
        $stmt = $pdo->prepare($check_sql);
        $stmt->execute([$facility_id, $check_out, $check_in]);
        $overlap = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($overlap['cnt'] > 0) {
            header("Location: ../owner/facilities.php?error=unit_unavailable");
            exit();
        }

        // Begin transaction
        try {
            $pdo->beginTransaction();

            // Insert into bookings table
            // Check which columns exist in bookings table
            $bookingColumns = $pdo->query("SHOW COLUMNS FROM bookings")->fetchAll(PDO::FETCH_COLUMN);
            
            $bookingFields = ['user_id', 'check_in_date', 'check_out_date', 'total_amount', 'status', 'created_at'];
            $bookingParams = [
                'user_id' => $user_id,
                'check_in_date' => $check_in,
                'check_out_date' => $check_out,
                'total_amount' => $total_amount,
                'status' => 'confirmed',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Add guest_name if column exists
            if (in_array('guest_name', $bookingColumns)) {
                $bookingFields[] = 'guest_name';
                $bookingParams['guest_name'] = $guest_name;
            }
            // Add booking_reference if column exists (generate unique ref)
            if (in_array('booking_reference', $bookingColumns)) {
                $bookingFields[] = 'booking_reference';
                $bookingParams['booking_reference'] = 'BKG-' . strtoupper(uniqid());
            }
            
            $placeholders = array_map(fn($f) => ':' . $f, $bookingFields);
            $insertBooking = "INSERT INTO bookings (" . implode(', ', $bookingFields) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $pdo->prepare($insertBooking);
            $stmt->execute($bookingParams);
            $booking_id = $pdo->lastInsertId();
            
            if (!$booking_id) {
                throw new Exception("Failed to create booking record");
            }
            
            // Insert into booking_items
            $bookingItemFields = ['booking_id', 'facility_id', 'quantity', 'price_per_night', 'subtotal'];
            $bookingItemParams = [
                'booking_id' => $booking_id,
                'facility_id' => $facility_id,
                'quantity' => 1,
                'price_per_night' => $price_per_night,
                'subtotal' => $total_amount
            ];
            
            $itemPlaceholders = array_map(fn($f) => ':' . $f, $bookingItemFields);
            $insertItem = "INSERT INTO booking_items (" . implode(', ', $bookingItemFields) . ") VALUES (" . implode(', ', $itemPlaceholders) . ")";
            $stmt = $pdo->prepare($insertItem);
            $stmt->execute($bookingItemParams);
            
            $pdo->commit();
            
            header("Location: ../owner/facilities.php?success=booking_created");
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Create Booking Error: ' . $e->getMessage());
            header("Location: ../owner/facilities.php?error=booking_failed");
            exit();
        }
    }
}

header("Location: ../owner/facilities.php");
exit();