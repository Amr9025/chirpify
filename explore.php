<?php
require_once 'database_connection.php';
require_once 'header.php';

// Haal alle berichten op, samen met de handle van de gebruiker
try {
    $stmt = $pdo->query("
        SELECT m.id, m.content, m.created_at, u.handle, 
               (SELECT COUNT(*) FROM likes l WHERE l.message_id = m.id) as like_count,
               (SELECT COUNT(*) FROM likes l WHERE l.message_id = m.id AND l.user_id = :userId) as user_liked
        FROM messages m
        JOIN users u ON m.user_id = u.id
        ORDER BY m.created_at DESC
    ");
    $stmt->execute([':userId' => $_SESSION['userId']]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = $_SESSION['language'] === 'nl' ? "Fout bij ophalen berichten: " . $e->getMessage() : "Error fetching messages: " . $e->getMessage();
}

renderHeader('explore');
?>

<header class="feed-header <?php echo $theme; ?>">
    <h1><?php echo $_SESSION['language'] === 'nl' ? 'Ontdekken' : 'Explore'; ?></h1>
</header>

<?php if (isset($errorMessage)): ?>
    <div class="message error"><?php echo htmlspecialchars($errorMessage); ?></div>
<?php endif; ?>

<div class="feed-content">
    <?php if (empty($messages)): ?>
        <p><?php echo $_SESSION['language'] === 'nl' ? 'Geen berichten om te tonen.' : 'No messages to display.'; ?></p>
    <?php else: ?>
        <?php foreach ($messages as $message): ?>
            <div class="tweet <?php echo $theme; ?>">
                <div class="tweet-header">
                    <span class="tweet-user"><?php echo htmlspecialchars($message['handle']); ?></span>
                    <span class="tweet-time"><?php echo $message['created_at']; ?></span>
                </div>
                <p class="tweet-content"><?php echo htmlspecialchars($message['content']); ?></p>
                <div class="tweet-actions">
                    <form method="POST" action="like_message.php" style="display: inline;">
                        <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                        <button type="submit" class="like-btn <?php echo $message['user_liked'] ? 'liked' : ''; ?>">
                            <span class="like-count"><?php echo $message['like_count']; ?></span>
                            <?php echo $message['user_liked'] ? ($_SESSION['language'] === 'nl' ? 'Unlike' : 'Unlike') : ($_SESSION['language'] === 'nl' ? 'Like' : 'Like'); ?>
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</main>
</div>
</body>
</html>