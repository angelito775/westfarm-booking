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
    // DELETE USER LOGIC
    // ==========================================
    if ($_POST['action'] === 'delete_user') {
        $target_user_id = $_POST['user_id'];

        // Extra layer of protection: Don't allow self-deletion
        if ($target_user_id == $_SESSION['user_id']) {
            header("Location: ../admin/users.php?error=cannot_delete_self");
            exit();
        }

        try {
            // Because your database uses ON DELETE CASCADE for user_profiles, 
            // deleting the user here will automatically delete their profile too!
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = :id");
            $stmt->execute(['id' => $target_user_id]);

            header("Location: ../admin/users.php?success=user_deleted");
            exit();

        } catch (PDOException $e) {
            error_log("Delete User Error: " . $e->getMessage());
            header("Location: ../admin/users.php?error=delete_failed");
            exit();
        }
    }
    
    // ==========================================
    // EDIT USER LOGIC
    // ==========================================
    elseif ($_POST['action'] === 'edit_user') {
        
        // 1. Grab and sanitize the data from the form
        $edit_user_id = $_POST['user_id'];
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $phone_number = trim($_POST['phone_number']);
        $user_type_id = $_POST['user_type_id'];
        $user_status_id = $_POST['user_status_id'];
        $password = $_POST['password'];

        // Prevent the Admin from accidentally changing their own role to Customer/Owner
        if ($edit_user_id == $_SESSION['user_id'] && $user_type_id != 1) {
            header("Location: ../admin/users.php?error=cannot_demote_self");
            exit();
        }

        try {
            // 2. Check if the new email is already taken by a DIFFERENT user
            $check_email = $pdo->prepare("SELECT user_id FROM users WHERE email = :email AND user_id != :id");
            $check_email->execute(['email' => $email, 'id' => $edit_user_id]);
            if ($check_email->fetch()) {
                header("Location: ../admin/users.php?error=email_taken_modal");
                exit();
            }

            // ========================================================
            // START TRANSACTION: We are updating two tables safely
            // ========================================================
            $pdo->beginTransaction();

            // 3. Update the `users` table
            if (!empty($password)) {
                // If password is provided, update it
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $updateUser = $pdo->prepare("
                    UPDATE users 
                    SET email = :email, user_type_id = :type_id, user_status_id = :status_id, password = :password
                    WHERE user_id = :id
                ");
                $updateUser->execute([
                    'email' => $email,
                    'type_id' => $user_type_id,
                    'status_id' => $user_status_id,
                    'password' => $hashed_password,
                    'id' => $edit_user_id
                ]);
            } else {
                // If password is empty, don't update it
                $updateUser = $pdo->prepare("
                    UPDATE users 
                    SET email = :email, user_type_id = :type_id, user_status_id = :status_id 
                    WHERE user_id = :id
                ");
                $updateUser->execute(['email' => $email, 'type_id' => $user_type_id, 'status_id' => $user_status_id, 'id' => $edit_user_id]);
            }

            // 4. Update the `user_profiles` table (First Name, Last Name)
            $updateProfile = $pdo->prepare("
                UPDATE user_profiles 
                SET first_name = :fname, last_name = :lname, phone_number = :phone
                WHERE user_id = :id
            ");
            $updateProfile->execute([
                'fname' => $first_name,
                'lname' => $last_name,
                'phone' => $phone_number,
                'id'    => $edit_user_id
            ]);

            // 5. Commit the changes
            $pdo->commit();

            // Success! Send them back to the user list
            header("Location: ../admin/users.php?success=user_updated");
            exit();

        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Edit User Error: " . $e->getMessage());
            header("Location: ../admin/users.php?error=update_failed");
            exit();
        }
    }
    // ==========================================
    // ADD USER LOGIC
    // ==========================================
    elseif ($_POST['action'] === 'add_user') {
        // 1. Grab and sanitize data
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $phone_number = trim($_POST['phone_number']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $user_type_id = $_POST['user_type_id'];
        $user_status_id = $_POST['user_status_id'];

        // 2. Validation
        if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($user_type_id) || empty($user_status_id)) {
            header("Location: ../admin/users.php?error=empty_fields_modal");
            exit();
        }
        if ($password !== $confirm_password) {
            header("Location: ../admin/users.php?error=password_mismatch_modal");
            exit();
        }

        try {
            // 3. Check if email already exists
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            if ($stmt->fetch()) {
                header("Location: ../admin/users.php?error=email_taken_modal");
                exit();
            }

            // 4. Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // 5. Transaction
            $pdo->beginTransaction();

            // Insert into `users`
            $insertUser = $pdo->prepare("INSERT INTO users (user_type_id, user_status_id, email, password) VALUES (:type, :status, :email, :password)");
            $insertUser->execute(['type' => $user_type_id, 'status' => $user_status_id, 'email' => $email, 'password' => $hashed_password]);
            $new_user_id = $pdo->lastInsertId();

            // Insert into `user_profiles`
            $insertProfile = $pdo->prepare("INSERT INTO user_profiles (user_id, first_name, last_name, phone_number) VALUES (:user_id, :fname, :lname, :phone)");
            $insertProfile->execute(['user_id' => $new_user_id, 'fname' => $first_name, 'lname' => $last_name, 'phone' => $phone_number]);

            $pdo->commit();

            header("Location: ../admin/users.php?success=user_added");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Add User Error: " . $e->getMessage());
            header("Location: ../admin/users.php?error=add_failed");
            exit();
        }
    }
} else {
    // If accessed without a form submission, send them back
    header("Location: ../admin/users.php");
    exit();
}
?>