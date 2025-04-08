<?php
session_start();
require_once 'database_connection.php';

if (!isset($_SESSION['userId'])) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->query("
    SELECT m.id, m.content, m.created_at, u.handle, COUNT(l.id) as like_count
    FROM messages m
    JOIN users u ON m.user_id = u.id
    LEFT JOIN likes l ON m.id = l.message_id
    GROUP BY m.id
    ORDER BY m.created_at DESC
");
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$errorMessage = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chirpify - Home</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<aside class="sidebar">
            <div class="logo">
                <h2>Chirpify</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item active">Home</a>
                <a href="#" class="nav-item">Explore</a>
                <a href="#" class="nav-item">Notifications</a>
                <a href="#" class="nav-item">Messages</a>
                <a href="profile.php" class="nav-item">Profile</a>
                <a href="logout.php" class="nav-item">Logout</a>
            </nav>
            <button class="tweet-btn">Tweet</button>
        </aside>
    <div class="twitter-container">
        <main class="feed">
            <!-- Feed content -->
            <header class="feed-header">
                <h1>Home</h1>
            </header>
            <?php if ($errorMessage): ?>
                <p class="error"><?php echo htmlspecialchars($errorMessage); ?></p>
            <?php endif; ?>
            <div class="tweet-form">
                <form action="post_message.php" method="POST">
                    <div class="form-group">
                        <textarea name="content" placeholder="What's chirping?" required maxlength="280"></textarea>
                    </div>
                    <button type="submit" class="save-profile-btn">Chirp</button>
                </form>
            </div>
            <div class="tweets">
                <?php foreach ($messages as $message): ?>
                    <div class="tweet">
                        <div class="tweet-avatar">
                            <img src="https://via.placeholder.com/50" alt="User Avatar">
                        </div>
                        <div class="tweet-content">
                            <div class="tweet-header">
                                <span class="username"><?php echo htmlspecialchars($message['handle']); ?></span>
                                <span class="handle">@<?php echo htmlspecialchars($message['handle']); ?> · <?php echo timeAgo($message['created_at']); ?></span>
                            </div>
                            <p><?php echo htmlspecialchars($message['content']); ?></p>
                            <div class="tweet-actions">
                                <span class="reply">Reply</span>
                                <span class="retweet">Retweet</span>
                                <form action="like_message.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="messageId" value="<?php echo $message['id']; ?>">
                                    <button type="submit" class="like">Like (<?php echo $message['like_count']; ?>)</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</body>
</html>

<?php
function timeAgo($timestamp) {
    $diff = time() - strtotime($timestamp);
    if ($diff < 60) return "$diff sec ago";
    if ($diff < 3600) return round($diff / 60) . " min ago";
    if ($diff < 86400) return round($diff / 3600) . " hours ago";
    return date('d M', strtotime($timestamp));
}
?>