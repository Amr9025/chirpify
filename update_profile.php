<?php
session_start();
require_once 'database_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['userId'])) {
    $userId = $_SESSION['userId'];
    $bio = trim($_POST['bio']);

    try {
        $stmt = $pdo->prepare("UPDATE users SET bio = :bio WHERE id = :userId");
        $stmt->execute(['bio' => $bio, 'userId' => $userId]);
        header("Location: profile.php");
        exit;
    } catch (PDOException $e) {
        $errorMessage = "Error updating profile: " . $e->getMessage();
        header("Location: profile.php?error=" . urlencode($errorMessage));
        exit;
    }
}
?>