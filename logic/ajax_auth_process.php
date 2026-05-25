<?php
session_start();
require_once __DIR__ . '/../config/db_connection.php';

header('Content-Type: application/json');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

// Only handle ajax login
if (!isset($_POST['ajax_login'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT user_id, password, user_type_id, user_status_id FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Email not found. Please check and try again.']);
        exit();
    }

    if (!password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Incorrect password. Please try again.']);
        exit();
    }

    if ($user['user_status_id'] != 1) {
        echo json_encode(['success' => false, 'message' => 'Your account has been disabled or suspended.']);
        exit();
    }

    // Only allow customers (user_type_id = 2) to login via booking modal
    if ($user['user_type_id'] != 2) {
        echo json_encode(['success' => false, 'message' => 'Please use the appropriate login portal for your account type.']);
        exit();
    }

    // Login successful
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_type_id'] = $user['user_type_id'];

    echo json_encode(['success' => true, 'message' => 'Login successful.']);
    exit();

} catch (PDOException $e) {
    error_log("AJAX Login Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A system error occurred. Please try again.']);
    exit();
}
