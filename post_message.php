<?php
session_start();
require_once 'database_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['userId'])) {
    $userId = $_SESSION['userId'];
    $content = trim($_POST['content']);

    if (empty($content) || strlen($content) > 280) {
        $errorMessage = "Message must be between 1 and 280 characters.";
        header("Location: index.php?error=" . urlencode($errorMessage));
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO messages (user_id, content) VALUES (:userId, :content)");
        $stmt->execute(['userId' => $userId, 'content' => $content]);
        header("Location: index.php");
        exit;
    } catch (PDOException $e) {
        $errorMessage = "Error posting message: " . $e->getMessage();
        header("Location: index.php?error=" . urlencode($errorMessage));
        exit;
    }
}
?>