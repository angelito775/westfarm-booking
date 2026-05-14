<?php
session_start();
// Make sure this points to your config folder from inside the logic folder
require_once '../config/db_connection.php';

// Process the registration form when the button is clicked
if (isset($_POST['register_btn'])) {
    
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phone_number = trim($_POST['phone_number']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // The path to redirect back to if there is an error
    $register_page = "../pages/register.php";

    // 1. Basic Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        header("Location: {$register_page}?error=empty_fields");
        exit();
    }

    if ($password !== $confirm_password) {
        header("Location: {$register_page}?error=password_mismatch");
        exit();
    }

    try {
        // 2. Check if email already exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        
        if ($stmt->fetch()) {
            header("Location: {$register_page}?error=email_taken");
            exit();
        }

        // 3. Hash the password securely
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // ========================================================
        // START TRANSACTION: We are inserting into two tables securely
        // ========================================================
        $pdo->beginTransaction();

        // 4. Insert into `users` table (Type 2 = Customer, Status 1 = Active)
        $insertUser = $pdo->prepare("INSERT INTO users (user_type_id, user_status_id, email, password) VALUES (2, 1, :email, :password)");
        $insertUser->execute([
            'email' => $email,
            'password' => $hashed_password
        ]);

        // Get the ID of the user we just created
        $new_user_id = $pdo->lastInsertId();

        // 5. Insert into `user_profiles` table
        $insertProfile = $pdo->prepare("INSERT INTO user_profiles (user_id, first_name, last_name, phone_number) VALUES (:user_id, :first_name, :last_name, :phone_number)");
        $insertProfile->execute([
            'user_id' => $new_user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'phone_number' => $phone_number
        ]);

        // 6. Commit the transaction (Save everything to the DB)
        $pdo->commit();

        // Success! Send them to the login page
        header("Location: ../pages/login.php?success=registered");
        exit();

    } catch (PDOException $e) {
        // If anything fails, rollback the transaction so we don't have partial data
        $pdo->rollBack();
        error_log("Registration Error: " . $e->getMessage());
        header("Location: {$register_page}?error=system_error");
        exit();
    }
} else {
    // If they tried to access this file without clicking the button, send them back
    header("Location: ../pages/register.php");
    exit();
}
?>