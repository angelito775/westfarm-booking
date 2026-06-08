<?php
session_start();

// Only owners allowed
if (!isset($_SESSION['user_id']) || $_SESSION['user_type_id'] != 3) {
    header("Location: ../pages/login.php");
    exit();
}

$settings_file = __DIR__ . '/../config/crayfish_settings.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_settings') {
    $price_per_kg   = floatval($_POST['price_per_kg'] ?? 120);
    $min_order_kg   = floatval($_POST['min_order_kg'] ?? 0.5);
    $max_order_kg   = floatval($_POST['max_order_kg'] ?? 100);
    $product_name   = trim($_POST['product_name'] ?? 'Fresh Live Crayfish');

    // Validation
    if ($price_per_kg <= 0) $price_per_kg = 120;
    if ($price_per_kg > 99999) $price_per_kg = 99999;
    if ($min_order_kg <= 0) $min_order_kg = 0.5;
    if ($max_order_kg <= 0) $max_order_kg = 100;
    if ($min_order_kg > $max_order_kg) $min_order_kg = $max_order_kg;
    if (empty($product_name)) $product_name = 'Fresh Live Crayfish';

    $settings = [
        'price_per_kg'  => $price_per_kg,
        'min_order_kg'  => $min_order_kg,
        'max_order_kg'  => $max_order_kg,
        'product_name'  => $product_name,
        'is_active'     => true,
    ];

    file_put_contents($settings_file, json_encode($settings, JSON_PRETTY_PRINT));

    header("Location: ../owner/crayfish_ordering.php?success=settings_updated");
    exit();
}

// If not POST, redirect back
header("Location: ../owner/crayfish_ordering.php");
exit();
