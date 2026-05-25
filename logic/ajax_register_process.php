<?php
session_start();
require_once __DIR__ . '/../config/db_connection.php';

header('Content-Type: application/json');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

$first_name = trim($_POST['first_name'] ?? '');
$last_name  = trim($_POST['last_name'] ?? '');
$phone_number = trim($_POST['phone_number'] ?? '');
$email      = trim($_POST['email'] ?? '');
$password   = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validation
if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit();
}

if ($password !== $confirm_password) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
    exit();
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
    exit();
}

try {
    // Check email uniqueness
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'This email is already registered.']);
        exit();
    }

    // Check phone uniqueness (if provided)
    if (!empty($phone_number)) {
        $stmt = $pdo->prepare("SELECT user_id FROM user_profiles WHERE phone_number = :phone");
        $stmt->execute(['phone' => $phone_number]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'This phone number is already registered.']);
            exit();
        }
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert user + profile in transaction
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO users (user_type_id, user_status_id, email, password) VALUES (2, 1, :email, :password)");
    $stmt->execute([
        'email' => $email,
        'password' => $hashed_password
    ]);
    $new_user_id = $pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id, first_name, last_name, phone_number) VALUES (:user_id, :first_name, :last_name, :phone_number)");
    $stmt->execute([
        'user_id' => $new_user_id,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'phone_number' => $phone_number ?: null
    ]);

    $pdo->commit();

    // Auto-login the new user
    $_SESSION['user_id'] = $new_user_id;
    $_SESSION['user_type_id'] = 2;

    echo json_encode(['success' => true, 'message' => 'Account created successfully!']);
    exit();

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("AJAX Registration Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A system error occurred. Please try again.']);
    exit();
}
