<?php
session_start();
// Make sure this file actually exists and contains your $pdo connection!
require_once __DIR__ . '/../config/db_connection.php';

function redirect_user($user_type_id) {
    if ($user_type_id == 1) {
        header("Location: ../admin/index.php");
    } elseif ($user_type_id == 3) {
        header("Location: ../owner/index.php");
    } elseif ($user_type_id == 2) {
        header("Location: ../pages/index.php");
    }
    else {
        // If user type is unknown, log them out for safety
        session_destroy();
        header("Location: ../pages/login.php?error=invalid_credentials");
    }
    exit();
}

// If already logged in, redirect them immediately
if (isset($_SESSION['user_id'])) {
    redirect_user($_SESSION['user_type_id']);
}

// Process the login form
if (isset($_POST['login_btn'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Define where to send errors
    $login_page = "../pages/login.php"; 

    if (empty($email) || empty($password)) {
        header("Location: {$login_page}?error=empty_fields");
        exit();
    }

    try {
        // FIX: Changed 'id' to 'user_id' and added 'user_status_id'
        $stmt = $pdo->prepare("SELECT user_id, password, user_type_id, user_status_id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        // Verify user exists and the password matches securely
        if ($user && password_verify($password, $user['password'])) {
            
            // SECURITY CHECK: Make sure the account is Active (Status ID 1)
            if ($user['user_status_id'] != 1) {
                header("Location: {$login_page}?error=account_disabled");
                exit();
            }

            // FIX: Use user_id instead of id
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_type_id'] = $user['user_type_id'];
            
            redirect_user($user['user_type_id']);
            
        } else {
            header("Location: {$login_page}?error=invalid_credentials");
            exit();
        }
    } catch (PDOException $e) {
        // Log the actual error for yourself, but show a generic error to the user
        error_log("Database Login Error: " . $e->getMessage());
        header("Location: {$login_page}?error=system_error");
        exit();
    }
} else {
    // If they accessed this file without submitting the form, kick them back
    header("Location: ../pages/login.php");
    exit();
}
?>