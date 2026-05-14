<?php
session_start();
require_once '../config/db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type_id'] != 3) {
    header("Location: ../pages/access_denied.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
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
}

header("Location: ../owner/facilities.php");
exit();
