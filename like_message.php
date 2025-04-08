<?php
session_start();
require_once 'database_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['userId'])) {
    $userId = $_SESSION['userId'];
    $messageId = $_POST['messageId'];
    $referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';

    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO likes (user_id, message_id) VALUES (:userId, :messageId)");
        $stmt->execute(['userId' => $userId, 'messageId' => $messageId]);
        header("Location: $referer");
        exit;
    } catch (PDOException $e) {
        $errorMessage = "Error liking message: " . $e->getMessage();
        header("Location: $referer?error=" . urlencode($errorMessage));
        exit;
    }
}
?>