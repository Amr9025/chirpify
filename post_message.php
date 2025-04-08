<?php
session_start();
require_once 'database_connection.php';

if (!isset($_SESSION['userId']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$userId = $_SESSION['userId'];
$content = trim($_POST['content'] ?? '');

if (strlen($content) < 1 || strlen($content) > 1600) {
    header("Location: index.php?error=" . urlencode("Message must be between 1 and 1600 characters."));
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
?>