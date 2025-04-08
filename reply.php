<?php
session_start();

// Voorkom caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Controleer of de gebruiker is ingelogd
if (!isset($_SESSION['userId'])) {
    header("Location: login.php");
    exit;
}

// Controleer of message_id is meegegeven
if (!isset($_GET['message_id']) || !is_numeric($_GET['message_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'database_connection.php';
require_once 'header.php';

$userId = $_SESSION['userId'];
$messageId = $_GET['message_id'];
$successMessage = '';
$errorMessage = '';

// Haal het oorspronkelijke bericht op
try {
    $stmt = $pdo->prepare("
        SELECT m.*, u.handle as user_handle
        FROM messages m
        JOIN users u ON m.user_id = u.id
        WHERE m.id = :messageId
    ");
    $stmt->execute([':messageId' => $messageId]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$message) {
        header("Location: index.php");
        exit;
    }
} catch (PDOException $e) {
    $errorMessage = $_SESSION['language'] === 'nl' ? "Fout bij ophalen bericht: " . $e->getMessage() : "Error fetching message: " . $e->getMessage();
}

// Verwerk het plaatsen van een reactie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_comment'])) {
    $content = trim($_POST['comment_content']);
    if (empty($content)) {
        $errorMessage = $_SESSION['language'] === 'nl' ? "Reactie mag niet leeg zijn." : "Comment cannot be empty.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO comments (user_id, message_id, content, created_at) VALUES (:userId, :messageId, :content, NOW())");
            $result = $stmt->execute([
                ':userId' => $userId,
                ':messageId' => $messageId,
                ':content' => $content
            ]);
            if ($result) {
                $successMessage = $_SESSION['language'] === 'nl' ? "Reactie geplaatst!" : "Comment posted!";
                // Redirect naar index.php na succesvol plaatsen
                header("Location: index.php");
                exit;
            } else {
                $errorMessage = $_SESSION['language'] === 'nl' ? "Fout bij plaatsen reactie." : "Error posting comment.";
            }
        } catch (PDOException $e) {
            $errorMessage = $_SESSION['language'] === 'nl' ? "Fout bij plaatsen reactie: " . $e->getMessage() : "Error posting comment: " . $e->getMessage();
        }
    }
}

renderHeader('home');
?>

<header class="feed-header <?php echo $theme; ?>">
    <h1><?php echo $_SESSION['language'] === 'nl' ? 'Reageren' : 'Reply'; ?></h1>
</header>

<?php if ($errorMessage): ?>
    <div class="message error"><?php echo htmlspecialchars($errorMessage); ?></div>
<?php endif; ?>

<?php if ($successMessage): ?>
    <div class="message success"><?php echo htmlspecialchars($successMessage); ?></div>
<?php endif; ?>

<div class="feed">
    <!-- Toon het oorspronkelijke bericht -->
    <div class="message">
        <div class="message-header">
            <span class="username"><?php echo htmlspecialchars($message['user_handle']); ?></span>
            <span class="timestamp"><?php echo $message['created_at']; ?></span>
        </div>
        <div class="message-content">
            <p><?php echo htmlspecialchars($message['content']); ?></p>
        </div>
    </div>

    <!-- Reply formulier -->
    <div class="reply-form">
        <h2><?php echo $_SESSION['language'] === 'nl' ? 'Schrijf een reactie' : 'Write a reply'; ?></h2>
        <form action="reply.php?message_id=<?php echo $messageId; ?>" method="POST">
            <input type="hidden" name="post_comment" value="1">
            <textarea name="comment_content" placeholder="<?php echo $_SESSION['language'] === 'nl' ? 'Schrijf een reactie...' : 'Write a reply...'; ?>" required></textarea>
            <button type="submit" class="reply-btn"><?php echo $lang[$_SESSION['language']]['reply']; ?></button>
        </form>
    </div>
</div>

</main>
</div>
</body>
</html>