<?php
session_start();
require_once 'database_connection.php';

if (!isset($_SESSION['userId'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['userId'];
$stmt = $pdo->prepare("SELECT handle, bio FROM users WHERE id = :id");
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT m.id, m.content, m.created_at, COUNT(l.id) as like_count
    FROM messages m
    LEFT JOIN likes l ON m.id = l.message_id
    WHERE m.user_id = :userId
    GROUP BY m.id
    ORDER BY m.created_at DESC
");
$stmt->execute(['userId' => $userId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$errorMessage = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chirpify - Profile</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<aside class="sidebar">
            <div class="logo">
                <h2>Chirpify</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item">Home</a>
                <a href="#" class="nav-item">Explore</a>
                <a href="#" class="nav-item">Notifications</a>
                <a href="#" class="nav-item">Messages</a>
                <a href="profile.php" class="nav-item active">Profile</a>
                <a href="logout.php" class="nav-item">Logout</a>
            </nav>
            <button class="tweet-btn">Tweet</button>
        </aside>
    <div class="twitter-container">
        <main class="feed profile-main">
            <header class="feed-header">
                <h1>Profile</h1>
            </header>

            <?php if ($errorMessage): ?>
                <p class="error"><?php echo htmlspecialchars($errorMessage); ?></p>
            <?php endif; ?>

            <div class="profile-header">
                <div class="profile-banner">
                    <img src="https://via.placeholder.com/1500x500" alt="Banner">
                </div>
                <div class="profile-info">
                    <div class="profile-avatar">
                        <img src="https://via.placeholder.com/100" alt="Profile Picture">
                    </div>
                    <button class="edit-profile-btn">Edit profile</button>
                    <div class="profile-details">
                        <h2 class="profile-name"><?php echo htmlspecialchars($user['handle']); ?></h2>
                        <span class="profile-handle">@<?php  echo htmlspecialchars($user['handle']); ?></span>
                        <p class="profile-bio"><?php echo htmlspecialchars($user['bio'] ?? 'No bio yet'); ?></p>
                        <div class="profile-meta">
                            <span><strong>250</strong> Following</span>
                            <span><strong>180</strong> Followers</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="profile-edit-form" style="display: none;">
                <form action="update_profile.php" method="POST">
                    <div class="form-group">
                        <label for="bio">Bio</label>
                        <textarea name="bio" id="bio"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" class="save-profile-btn">Save</button>
                    <button type="button" class="cancel-edit-btn">Cancel</button>
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
                                <span class="username"><?php echo htmlspecialchars($user['handle']); ?></span>
                                <span class="handle">@<?php echo htmlspecialchars($user['handle']); ?> · <?php echo timeAgo($message['created_at']); ?></span>
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

    <script>
        const editBtn = document.querySelector('.edit-profile-btn');
        const editForm = document.querySelector('.profile-edit-form');
        const cancelBtn = document.querySelector('.cancel-edit-btn');

        editBtn.addEventListener('click', () => {
            editForm.style.display = 'block';
            editBtn.style.display = 'none';
        });

        cancelBtn.addEventListener('click', () => {
            editForm.style.display = 'none';
            editBtn.style.display = 'block';
        });
    </script>
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