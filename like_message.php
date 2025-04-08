<?php
session_start();
require_once 'database_connection.php';

if (!isset($_SESSION['userId']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$userId = $_SESSION['userId'];
$messageId = $_POST['messageId'];

try {
    $pdo->beginTransaction();

    // Check of de gebruiker het bericht al heeft geliket
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = :userId AND message_id = :messageId");
    $stmt->execute(['userId' => $userId, 'messageId' => $messageId]);
    $like = $stmt->fetch();

    if ($like) {
        // Unlike: Verwijder de like
        $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = :userId AND message_id = :messageId");
        $stmt->execute(['userId' => $userId, 'messageId' => $messageId]);
        
        // Verminder users.like_count van de bericht-eigenaar
        $stmt = $pdo->prepare("UPDATE users SET like_count = like_count - 1 WHERE id = (SELECT user_id FROM messages WHERE id = :messageId)");
        $stmt->execute(['messageId' => $messageId]);
    } else {
        // Like: Voeg toe
        $stmt = $pdo->prepare("INSERT INTO likes (user_id, message_id) VALUES (:userId, :messageId)");
        $stmt->execute(['userId' => $userId, 'messageId' => $messageId]);
        
        // Verhoog users.like_count van de bericht-eigenaar
        $stmt = $pdo->prepare("UPDATE users SET like_count = like_count + 1 WHERE id = (SELECT user_id FROM messages WHERE id = :messageId)");
        $stmt->execute(['messageId' => $messageId]);
    }

    $pdo->commit();
    header("Location: " . $_SERVER['HTTP_REFERER']); // Terug naar vorige pagina
    exit;
} catch (PDOException $e) {
    $pdo->rollBack();
    $errorMessage = "Error liking message: " . $e->getMessage();
    header("Location: index.php?error=" . urlencode($errorMessage));
    exit;
}
?>