<?php
session_start();
require_once __DIR__ . '/../config/db_connection.php';

function redirect_user($user_type_id) {
    if ($user_type_id == 1) {
        header("Location: ../admin/index.php");
    } elseif ($user_type_id == 3) {
        header("Location: ../owner/index.php");
    } elseif ($user_type_id == 2) {
        header("Location: ../pages/index.php");
    } else {
        session_destroy();
        header("Location: ../pages/login.php?error=invalid_credentials");
    }
    exit();
}

if (isset($_SESSION['user_id'])) {
    redirect_user($_SESSION['user_type_id']);
}

if (isset($_POST['login_btn'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $login_page = "../pages/login.php";

    if (empty($email) || empty($password)) {
        header("Location: {$login_page}?error=empty_fields");
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT user_id, password, user_type_id, user_status_id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        // Email not found
        if (!$user) {
            header("Location: {$login_page}?error=invalid_email&email=" . urlencode($email));
            exit();
        }

        // Email found but password is wrong
        if (!password_verify($password, $user['password'])) {
            header("Location: {$login_page}?error=invalid_password&email=" . urlencode($email));
            exit();
        }

        // Account disabled check
        if ($user['user_status_id'] != 1) {
            header("Location: {$login_page}?error=account_disabled&email=" . urlencode($email));
            exit();
        }

        // Login successful
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_type_id'] = $user['user_type_id'];
        redirect_user($user['user_type_id']);
        
    } catch (PDOException $e) {
        error_log("Database Login Error: " . $e->getMessage());
        header("Location: {$login_page}?error=system_error");
        exit();
    }
} else {
    header("Location: ../pages/login.php");
    exit();
}
?>