<?php
session_start();
require_once 'database_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $handle = trim($_POST['handle']);
    $password = $_POST['password'];

    if (empty($handle) || empty($password) || !preg_match('/^[a-zA-Z0-9_]+$/', $handle)) {
        $errorMessage = "Invalid input. Handle and password are required, and handle must be alphanumeric.";
        header("Location: signup.php?error=" . urlencode($errorMessage));
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT); // Hash het wachtwoord

    try {
        $stmt = $pdo->prepare("INSERT INTO users (handle, password) VALUES (:handle, :password)");
        $stmt->execute(['handle' => $handle, 'password' => $hashedPassword]);
        $_SESSION['userId'] = $pdo->lastInsertId();
        $_SESSION['userHandle'] = $handle;
        header("Location: index.php");
        exit;
    } catch (PDOException $e) {
        $errorMessage = "Error creating account: " . $e->getMessage();
        header("Location: signup.php?error=" . urlencode($errorMessage));
        exit;
    }
}
?>