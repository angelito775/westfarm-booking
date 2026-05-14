<?php
session_start(); // Start the session at the very beginning
require_once '../config/db_connection.php';

// Security check: Only Admins can process these changes
if (!isset($_SESSION['user_id']) || $_SESSION['user_type_id'] != 1) {
    header("Location: ../pages/access_denied.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    $action = $_POST['action'];

    // ==========================================
    // ADD NEW PAYMENT METHOD
    // ==========================================
    if ($action === 'add_method') {
        $name = trim($_POST['method_name']);
        $is_active = $_POST['is_active'];

        // 1. Validate Input
        if (empty($name)) {
            header("Location: ../admin/payment_methods.php?error=add_failed");
            exit();
        }

        try {
            // 2. Prevent Duplicate Names
            $stmt = $pdo->prepare("SELECT payment_method_id FROM payment_methods WHERE method_name = :name");
            $stmt->execute(['name' => $name]);
            if ($stmt->fetch()) {
                header("Location: ../admin/payment_methods.php?error=name_taken");
                exit();
            }

            // 3. Insert into Database
            $insert = $pdo->prepare("INSERT INTO payment_methods (method_name, is_active) VALUES (:name, :active)");
            $insert->execute(['name' => $name, 'active' => $is_active]);

            // 4. Success Redirect
            header("Location: ../admin/payment_methods.php?success=method_added");
            exit();
            
        } catch (PDOException $e) {
            error_log("Add Payment Method Error: " . $e->getMessage());
            header("Location: ../admin/payment_methods.php?error=add_failed");
            exit();
        }
    }

    // ==========================================
    // EDIT METHOD (TOGGLE AVAILABILITY)
    // ==========================================
    elseif ($action === 'edit_method') {
        $id = $_POST['payment_method_id'];
        $name = trim($_POST['method_name']);
        $is_active = $_POST['is_active'];

        if (empty($id) || empty($name)) {
            header("Location: ../admin/payment_methods.php?error=update_failed");
            exit();
        }

        try {
            // Check for duplicate name collision (excluding the current method being edited)
            $check = $pdo->prepare("SELECT payment_method_id FROM payment_methods WHERE method_name = :name AND payment_method_id != :id");
            $check->execute(['name' => $name, 'id' => $id]);
            if ($check->fetch()) {
                header("Location: ../admin/payment_methods.php?error=name_taken");
                exit();
            }

            $update = $pdo->prepare("UPDATE payment_methods SET method_name = :name, is_active = :active WHERE payment_method_id = :id");
            $update->execute(['name' => $name, 'active' => $is_active, 'id' => $id]);

            header("Location: ../admin/payment_methods.php?success=method_updated");
            exit();
            
        } catch (PDOException $e) {
            error_log("Edit Payment Method Error: " . $e->getMessage());
            header("Location: ../admin/payment_methods.php?error=update_failed");
            exit();
        }
    }

    // ==========================================
    // DELETE METHOD
    // ==========================================
    elseif ($action === 'delete_method') {
        $id = $_POST['payment_method_id'];

        if (empty($id)) {
            header("Location: ../admin/payment_methods.php?error=delete_failed");
            exit();
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM payment_methods WHERE payment_method_id = :id");
            $stmt->execute(['id' => $id]);

            header("Location: ../admin/payment_methods.php?success=method_deleted");
            exit();
            
        } catch (PDOException $e) {
            // This will fail automatically to protect your data if a customer has already used 
            // this payment method for a booking in the past.
            error_log("Delete Payment Method Error: " . $e->getMessage());
            header("Location: ../admin/payment_methods.php?error=delete_failed");
            exit();
        }
    }

} else {
    // Kick back to the dashboard if accessed without clicking a submit button
    header("Location: ../admin/payment_methods.php");
    exit();
}
?>