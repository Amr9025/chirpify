<?php
require_once 'database_connection.php';

$admin_handle = 'admin';
$admin_password = 'admin123'; // Change this to your desired password
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

try {
    // Check if admin exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE handle = ?");
    $stmt->execute([$admin_handle]);
    $exists = $stmt->fetch();

    if ($exists) {
        // Update existing admin
        $stmt = $pdo->prepare("UPDATE users SET password = ?, is_admin = 1 WHERE handle = ?");
        $stmt->execute([$hashed_password, $admin_handle]);
        echo "Admin password updated successfully!";
    } else {
        // Create new admin
        $stmt = $pdo->prepare("INSERT INTO users (handle, password, is_admin, created_at) VALUES (?, ?, 1, NOW())");
        $stmt->execute([$admin_handle, $hashed_password]);
        echo "Admin account created successfully!";
    }
} catch (PDOException $e) {
    echo "Error managing admin account: " . $e->getMessage();
}