<?php
session_start(); // Start the session at the very beginning
require_once '../config/db_connection.php';

// Security: Kick out anyone who isn't an Admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type_id'] != 1) {
    header("Location: ../pages/access_denied.php");
    exit();
}

// Check if a POST request was made
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // ==========================================
    // DELETE CATEGORY LOGIC
    // ==========================================
    if ($_POST['action'] === 'delete_category') {
        $target_category_id = $_POST['category_id'];

        if (empty($target_category_id)) {
            header("Location: ../admin/categories.php?error=delete_failed");
            exit();
        }

        try {
            // Note: If you have facilities tied to this category, your database 
            // constraints (foreign keys) will prevent deletion to protect data integrity.
            $stmt = $pdo->prepare("DELETE FROM categories WHERE category_id = :id");
            $stmt->execute(['id' => $target_category_id]);

            header("Location: ../admin/categories.php?success=category_deleted");
            exit();

        } catch (PDOException $e) {
            error_log("Delete Category Error: " . $e->getMessage());
            header("Location: ../admin/categories.php?error=delete_failed");
            exit();
        }
    }
    
    // ==========================================
    // EDIT CATEGORY LOGIC
    // ==========================================
    elseif ($_POST['action'] === 'edit_category') {
        
        // 1. Grab and sanitize the data from the form
        $category_id = $_POST['category_id'];
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);

        if (empty($category_id) || empty($name)) {
            header("Location: ../admin/categories.php?error=update_failed");
            exit();
        }

        try {
            // 2. Check if the new category name is already taken by a DIFFERENT category
            $check_name = $pdo->prepare("SELECT category_id FROM categories WHERE name = :name AND category_id != :id");
            $check_name->execute(['name' => $name, 'id' => $category_id]);
            if ($check_name->fetch()) {
                header("Location: ../admin/categories.php?error=name_taken");
                exit();
            }

            // 3. Update the `categories` table
            $updateCategory = $pdo->prepare("
                UPDATE categories 
                SET name = :name, description = :description 
                WHERE category_id = :id
            ");
            $updateCategory->execute([
                'name' => $name,
                'description' => $description,
                'id' => $category_id
            ]);

            // Success! Send them back to the category list
            header("Location: ../admin/categories.php?success=category_updated");
            exit();

        } catch (PDOException $e) {
            error_log("Edit Category Error: " . $e->getMessage());
            header("Location: ../admin/categories.php?error=update_failed");
            exit();
        }
    }
    
    // ==========================================
    // ADD CATEGORY LOGIC
    // ==========================================
    elseif ($_POST['action'] === 'add_category') {
        
        // 1. Grab and sanitize data
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);

        // 2. Validation
        if (empty($name)) {
            header("Location: ../admin/categories.php?error=add_failed");
            exit();
        }

        try {
            // 3. Check if category name already exists
            $stmt = $pdo->prepare("SELECT category_id FROM categories WHERE name = :name");
            $stmt->execute(['name' => $name]);
            if ($stmt->fetch()) {
                header("Location: ../admin/categories.php?error=name_taken");
                exit();
            }

            // 4. Insert into `categories`
            $insertCategory = $pdo->prepare("INSERT INTO categories (name, description) VALUES (:name, :description)");
            $insertCategory->execute([
                'name' => $name, 
                'description' => $description
            ]);

            header("Location: ../admin/categories.php?success=category_added");
            exit();
            
        } catch (PDOException $e) {
            error_log("Add Category Error: " . $e->getMessage());
            header("Location: ../admin/categories.php?error=add_failed");
            exit();
        }
    }
} else {
    // If accessed without a form submission, send them back
    header("Location: ../admin/categories.php");
    exit();
}
?>