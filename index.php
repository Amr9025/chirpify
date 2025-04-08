<?php
// Start de sessie
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

// Stel een standaardtaal in als deze niet is ingesteld of ongeldig is
if (!isset($_SESSION['language']) || !in_array($_SESSION['language'], ['en', 'nl'])) {
    $_SESSION['language'] = 'en';
}

// Stel een standaardwaarde voor dark_mode in als deze niet is ingesteld
if (!isset($_SESSION['dark_mode'])) {
    $_SESSION['dark_mode'] = false;
}

require_once 'database_connection.php';
require_once 'header.php';

renderHeader('home');

$errorMessage = '';
$successMessage = '';

// Genereer een unieke nonce voor het formulier om dubbele inzendingen te voorkomen
if (!isset($_SESSION['tweet_nonce'])) {
    $_SESSION['tweet_nonce'] = bin2hex(random_bytes(16));
}
$tweetNonce = $_SESSION['tweet_nonce'];

// Verwerk nieuwe tweet met afbeelding
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_tweet'])) {
    // Controleer de nonce
    if (!isset($_POST['tweet_nonce']) || $_POST['tweet_nonce'] !== $_SESSION['tweet_nonce']) {
        $errorMessage = $_SESSION['language'] === 'nl' ? "Ongeldige formulierinzending." : "Invalid form submission.";
    } else {
        $content = trim($_POST['content']);
        $uploadDir = __DIR__ . '/uploads/';
        $imageFile = '';

        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (!empty($_FILES['tweet_image']['name'])) {
            $imageFile = $uploadDir . basename($_FILES['tweet_image']['name']);
            if (!move_uploaded_file($_FILES['tweet_image']['tmp_name'], $imageFile)) {
                $errorMessage = $_SESSION['language'] === 'nl' ? 
                    "Fout bij uploaden afbeelding: Kan niet verplaatsen naar $imageFile" : 
                    "Error uploading image: Cannot move to $imageFile";
                $imageFile = '';
            } else {
                $successMessage = $_SESSION['language'] === 'nl' ? "Afbeelding succesvol geüpload!" : "Image uploaded successfully!";
            }
        }

        if (!$errorMessage && $content) {
            try {
                $stmt = $pdo->prepare("INSERT INTO messages (user_id, content, image, created_at) VALUES (:userId, :content, :image, NOW())");
                $stmt->execute([':userId' => $_SESSION['userId'], ':content' => $content, ':image' => $imageFile ? 'uploads/' . basename($imageFile) : '']);
                // Vernieuw de nonce na een succesvolle inzending
                $_SESSION['tweet_nonce'] = bin2hex(random_bytes(16));
                header("Location: index.php");
                exit;
            } catch (PDOException $e) {
                $errorMessage = $_SESSION['language'] === 'nl' ? "Fout bij plaatsen chirp: " . $e->getMessage() : "Error posting chirp: " . $e->getMessage();
            }
        }
    }
}

// Haal alle berichten op (niet alleen van de huidige gebruiker)
try {
    $sql = "
        SELECT m.id, m.content, m.created_at, m.image, u.handle, u.profile_picture, 
               COUNT(l.id) as like_count,
               EXISTS(SELECT 1 FROM likes WHERE user_id = :userId AND message_id = m.id) as user_liked
        FROM messages m
        JOIN users u ON m.user_id = u.id
        LEFT JOIN likes l ON m.id = l.message_id
        GROUP BY m.id, m.content, m.created_at, m.image, u.handle, u.profile_picture
        ORDER BY m.created_at DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':userId', $_SESSION['userId'], PDO::PARAM_INT);
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug: Log de opgehaalde berichten
    error_log("Aantal opgehaalde berichten: " . count($messages));
    foreach ($messages as $msg) {
        error_log("Bericht ID: {$msg['id']}, Gebruiker: {$msg['handle']}, Inhoud: {$msg['content']}");
    }

    // Haal reacties op voor elk bericht
    foreach ($messages as &$message) {
        try {
            $stmt = $pdo->prepare("
                SELECT c.*, u.handle AS user_handle
                FROM comments c
                JOIN users u ON c.user_id = u.id
                WHERE c.message_id = :messageId
                ORDER BY c.created_at ASC
            ");
            $stmt->execute([':messageId' => $message['id']]);
            $message['comments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $errorMessage .= " " . ($_SESSION['language'] === 'nl' ? "Fout bij ophalen reacties voor bericht {$message['id']}: " . $e->getMessage() : "Error fetching comments for message {$message['id']}: " . $e->getMessage());
            $message['comments'] = [];
        }
    }
} catch (PDOException $e) {
    $errorMessage = $_SESSION['language'] === 'nl' ? "Fout bij ophalen berichten: " . $e->getMessage() : "Error fetching messages: " . $e->getMessage();
    $messages = [];
}

$errorMessage = $_GET['error'] ?? $errorMessage ?? '';
?>

<header class="feed-header <?php echo $theme; ?>">
    <h1><?php echo isset($lang[$_SESSION['language']]['home']) ? $lang[$_SESSION['language']]['home'] : 'Home'; ?></h1>
</header>

<?php if ($errorMessage): ?>
    <div class="message error"><?php echo htmlspecialchars($errorMessage); ?></div>
<?php endif; ?>
<?php if ($successMessage): ?>
    <div class="message success"><?php echo htmlspecialchars($successMessage); ?></div>
<?php endif; ?>

<div class="tweet-form">
    <form action="index.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="post_tweet" value="1">
        <input type="hidden" name="tweet_nonce" value="<?php echo htmlspecialchars($tweetNonce); ?>">
        <div class="form-group">
            <textarea name="content" placeholder="<?php echo $_SESSION['language'] === 'nl' ? 'Wat tjilpt er?' : 'What\'s chirping?'; ?>" required maxlength="1600" class="<?php echo $theme; ?>"></textarea>
        </div>
        <div class="form-group">
            <label for="tweet_image"><?php echo $_SESSION['language'] === 'nl' ? 'Afbeelding toevoegen' : 'Add Image'; ?></label>
            <input type="file" name="tweet_image" id="tweet_image" accept="image/*" class="<?php echo $theme; ?>">
        </div>
        <button type="submit" class="save-profile-btn"><?php echo isset($lang[$_SESSION['language']]['tweet']) ? $lang[$_SESSION['language']]['tweet'] : 'Tweet'; ?></button>
    </form>
</div>

<div class="tweets">
    <?php if (empty($messages)): ?>
        <p><?php echo $_SESSION['language'] === 'nl' ? 'Je hebt nog geen berichten geplaatst.' : 'You haven\'t posted any messages yet.'; ?></p>
    <?php else: ?>
        <?php foreach ($messages as $message): ?>
            <div class="tweet <?php echo $theme; ?>">
                <div class="tweet-avatar">
                    <img src="<?php echo htmlspecialchars($message['profile_picture'] ?? 'https://via.placeholder.com/32'); ?>" alt="User Avatar" style="width: 32px; height: 32px; border-radius: 50%;">
                </div>
                <div class="tweet-content">
                    <div class="tweet-header">
                        <span class="username <?php echo $theme; ?>"><?php echo htmlspecialchars($message['handle']); ?></span>
                        <span class="handle <?php echo $theme; ?>">@<?php echo htmlspecialchars($message['handle']); ?> · <?php echo timeAgo($message['created_at']); ?></span>
                    </div>
                    <p><?php echo htmlspecialchars($message['content']); ?></p>
                    <?php if (!empty($message['image'])): ?>
                        <img src="<?php echo htmlspecialchars($message['image']); ?>" alt="Tweet Image" style="max-width: 100%; border-radius: 8px; margin-top: 10px;">
                    <?php endif; ?>
                    <div class="tweet-actions">
                        <a href="reply.php?message_id=<?php echo $message['id']; ?>" class="reply <?php echo $theme; ?>">
                            <?php echo $lang[$_SESSION['language']]['reply']; ?>
                        </a>
                        <span class="retweet <?php echo $theme; ?>"><?php echo $lang[$_SESSION['language']]['retweet']; ?></span>
                        <form action="like_message.php" method="POST" style="display:inline;">
                            <input type="hidden" name="messageId" value="<?php echo $message['id']; ?>">
                            <button type="submit" class="like <?php echo $theme; ?>" style="color: <?php echo $message['user_liked'] ? '#E0245E' : ''; ?>">
                                <?php echo $message['user_liked'] ? $lang[$_SESSION['language']]['unlike'] : $lang[$_SESSION['language']]['like']; ?> (<?php echo $message['like_count']; ?>)
                            </button>
                        </form>
                    </div>
                    <!-- Toon reacties -->
                    <?php if (!empty($message['comments'])): ?>
                        <div class="comments">
                            <?php foreach ($message['comments'] as $comment): ?>
                                <div class="comment">
                                    <div class="comment-header">
                                        <span class="username <?php echo $theme; ?>"><?php echo htmlspecialchars($comment['user_handle']); ?></span>
                                        <span class="handle <?php echo $theme; ?>">@<?php echo htmlspecialchars($comment['user_handle']); ?> · <?php echo timeAgo($comment['created_at']); ?></span>
                                    </div>
                                    <div class="comment-content">
                                        <p><?php echo htmlspecialchars($comment['content']); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</main>
</div>

<?php
function timeAgo($timestamp) {
    $diff = time() - strtotime($timestamp);
    if ($diff < 60) return $_SESSION['language'] === 'nl' ? "$diff sec geleden" : "$diff sec ago";
    if ($diff < 3600) return round($diff / 60) . ($_SESSION['language'] === 'nl' ? " min geleden" : " min ago");
    if ($diff < 86400) return round($diff / 3600) . ($_SESSION['language'] === 'nl' ? " uur geleden" : " hours ago");
    return date('d M', strtotime($timestamp));
}
?>
</body>
</html>
