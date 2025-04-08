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

// Debug: Controleer of $lang correct is geladen
if (!isset($lang[$_SESSION['language']])) {
    error_log("profile.php: \$lang[\$_SESSION['language']] is niet ingesteld voor taal: " . $_SESSION['language']);
    $_SESSION['language'] = 'en';
}

$userId = $_SESSION['userId'];
$errorMessage = '';
$successMessage = '';

// Haal profielgegevens op
try {
    $stmt = $pdo->prepare("SELECT handle, bio, like_count, banner, profile_picture FROM users WHERE id = :id");
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) throw new Exception("Gebruiker niet gevonden");
    $user['like_count'] = $user['like_count'] ?? 0;
    $user['banner'] = $user['banner'] ?? 'https://via.placeholder.com/1500x500';
    $user['profile_picture'] = $user['profile_picture'] ?? 'https://via.placeholder.com/32';
} catch (Exception $e) {
    $errorMessage = $_SESSION['language'] === 'nl' ? "Fout bij ophalen profiel: " . $e->getMessage() : "Error fetching profile: " . $e->getMessage();
}

// Haal berichten op
try {
    $stmt = $pdo->prepare("
        SELECT m.id, m.content, m.created_at, m.image, COUNT(l.id) as like_count,
               EXISTS(SELECT 1 FROM likes WHERE user_id = :userId AND message_id = m.id) as user_liked
        FROM messages m
        LEFT JOIN likes l ON m.id = l.message_id
        WHERE m.user_id = :userId
        GROUP BY m.id
        ORDER BY m.created_at DESC
    ");
    $stmt->execute(['userId' => $userId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = $_SESSION['language'] === 'nl' ? "Fout bij ophalen berichten: " . $e->getMessage() : "Error fetching messages: " . $e->getMessage();
    $messages = [];
}

// Verwerk profielupdate (bio, banner, profielfoto)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $bio = trim($_POST['bio'] ?? '');
    $uploadDir = 'uploads/';
    
    // Zorg ervoor dat de uploadmap bestaat
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Banner upload
    if (!empty($_FILES['banner']['name'])) {
        $bannerFile = $uploadDir . basename($_FILES['banner']['name']);
        if (move_uploaded_file($_FILES['banner']['tmp_name'], $bannerFile)) {
            $user['banner'] = $bannerFile;
        } else {
            $errorMessage = $_SESSION['language'] === 'nl' ? "Fout bij uploaden banner" : "Error uploading banner";
        }
    }

    // Profielfoto upload
    if (!empty($_FILES['profile_picture']['name'])) {
        $profilePictureFile = $uploadDir . basename($_FILES['profile_picture']['name']);
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $profilePictureFile)) {
            $user['profile_picture'] = $profilePictureFile;
        } else {
            $errorMessage = $_SESSION['language'] === 'nl' ? "Fout bij uploaden profielfoto" : "Error uploading profile picture";
        }
    }

    // Update database
    if (!$errorMessage) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET bio = :bio, banner = :banner, profile_picture = :profile_picture WHERE id = :id");
            $stmt->execute([
                ':bio' => $bio,
                ':banner' => $user['banner'],
                ':profile_picture' => $user['profile_picture'],
                ':id' => $userId
            ]);
            $successMessage = $_SESSION['language'] === 'nl' ? "Profiel bijgewerkt!" : "Profile updated!";
        } catch (PDOException $e) {
            $errorMessage = $_SESSION['language'] === 'nl' ? "Fout bij opslaan: " . $e->getMessage() : "Error saving: " . $e->getMessage();
        }
    }
}

// Verwerk nieuwe tweet met afbeelding
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_tweet'])) {
    $content = trim($_POST['content']);
    $uploadDir = 'uploads/';
    $imageFile = '';

    // Zorg ervoor dat de uploadmap bestaat
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if (!empty($_FILES['tweet_image']['name'])) {
        $imageFile = $uploadDir . basename($_FILES['tweet_image']['name']);
        if (!move_uploaded_file($_FILES['tweet_image']['tmp_name'], $imageFile)) {
            $errorMessage = $_SESSION['language'] === 'nl' ? "Fout bij uploaden afbeelding" : "Error uploading image";
            $imageFile = '';
        }
    }

    if (!$errorMessage && $content) {
        try {
            $stmt = $pdo->prepare("INSERT INTO messages (user_id, content, image) VALUES (:userId, :content, :image)");
            $stmt->execute([':userId' => $userId, ':content' => $content, ':image' => $imageFile]);
            header("Location: profile.php");
            exit;
        } catch (PDOException $e) {
            $errorMessage = $_SESSION['language'] === 'nl' ? "Fout bij plaatsen chirp: " . $e->getMessage() : "Error posting chirp: " . $e->getMessage();
        }
    }
}

$errorMessage = $_GET['error'] ?? $errorMessage ?? '';

renderHeader('profile');
?>

<header class="feed-header <?php echo $theme; ?>">
    <h1><?php echo isset($lang[$_SESSION['language']]['profile']) ? $lang[$_SESSION['language']]['profile'] : 'Profile'; ?></h1>
</header>

<?php if ($errorMessage): ?>
    <p class="error"><?php echo htmlspecialchars($errorMessage); ?></p>
<?php endif; ?>
<?php if ($successMessage): ?>
    <p class="success"><?php echo htmlspecialchars($successMessage); ?></p>
<?php endif; ?>

<div class="profile-header">
    <div class="profile-banner">
        <img src="<?php echo htmlspecialchars($user['banner']); ?>" alt="Banner">
    </div>
    <div class="profile-info">
        <div class="profile-avatar">
            <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture">
        </div>
        <button class="edit-profile-btn"><?php echo $_SESSION['language'] === 'nl' ? 'Profiel bewerken' : 'Edit profile'; ?></button>
        <div class="profile-details">
            <h2 class="profile-name <?php echo $theme; ?>"><?php echo htmlspecialchars($user['handle']); ?></h2>
            <span class="profile-handle <?php echo $theme; ?>">@<?php echo htmlspecialchars($user['handle']); ?></span>
            <p class="profile-bio"><?php echo htmlspecialchars($user['bio'] ?? ($_SESSION['language'] === 'nl' ? 'Nog geen bio' : 'No bio yet')); ?></p>
            <div class="profile-meta">
                <span class="<?php echo $theme; ?>"><strong>250</strong> <?php echo $_SESSION['language'] === 'nl' ? 'Volgend' : 'Following'; ?></span>
                <span class="<?php echo $theme; ?>"><strong>180</strong> <?php echo $_SESSION['language'] === 'nl' ? 'Volgers' : 'Followers'; ?></span>
                <span class="<?php echo $theme; ?>"><strong><?php echo $user['like_count']; ?></strong> Likes</span>
            </div>
        </div>
    </div>
</div>

<div class="profile-edit-form" style="display: none;">
    <form action="profile.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="update_profile" value="1">
        <div class="form-group">
            <label for="bio"><?php echo $_SESSION['language'] === 'nl' ? 'Bio' : 'Bio'; ?></label>
            <textarea name="bio" id="bio" class="<?php echo $theme; ?>"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
        </div>
        <div class="form-group">
            <label for="banner"><?php echo $_SESSION['language'] === 'nl' ? 'Achtergrondfoto' : 'Banner'; ?></label>
            <input type="file" name="banner" id="banner" accept="image/*" class="<?php echo $theme; ?>">
        </div>
        <div class="form-group">
            <label for="profile_picture"><?php echo $_SESSION['language'] === 'nl' ? 'Profielfoto' : 'Profile Picture'; ?></label>
            <input type="file" name="profile_picture" id="profile_picture" accept="image/*" class="<?php echo $theme; ?>">
        </div>
        <button type="submit" class="save-profile-btn"><?php echo isset($lang[$_SESSION['language']]['save']) ? $lang[$_SESSION['language']]['save'] : 'Save'; ?></button>
        <button type="button" class="cancel-edit-btn"><?php echo $_SESSION['language'] === 'nl' ? 'Annuleren' : 'Cancel'; ?></button>
    </form>
</div>

<div class="tweet-form">
    <form action="profile.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="post_tweet" value="1">
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
                    <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="User Avatar" style="width: 64px; height: 64px; border-radius: 50%;">
                </div>
                <div class="tweet-content">
                    <div class="tweet-header">
                        <span class="username <?php echo $theme; ?>"><?php echo htmlspecialchars($user['handle']); ?></span>
                        <span class="handle <?php echo $theme; ?>">@<?php echo htmlspecialchars($user['handle']); ?> · <?php echo timeAgo($message['created_at']); ?></span>
                    </div>
                    <p><?php echo htmlspecialchars($message['content']); ?></p>
                    <?php if ($message['image']): ?>
                        <img src="<?php echo htmlspecialchars($message['image']); ?>" alt="Tweet Image" style="max-width: 100%; border-radius: 8px; margin-top: 10px;">
                    <?php endif; ?>
                    <div class="tweet-actions">
                        <span class="reply <?php echo $theme; ?>"><?php echo $lang[$_SESSION['language']]['reply']; ?></span>
                        <span class="retweet <?php echo $theme; ?>"><?php echo $lang[$_SESSION['language']]['retweet']; ?></span>
                        <form action="like_message.php" method="POST" style="display:inline;">
                            <input type="hidden" name="messageId" value="<?php echo $message['id']; ?>">
                            <button type="submit" class="like <?php echo $theme; ?>" style="color: <?php echo $message['user_liked'] ? '#E0245E' : ''; ?>">
                                <?php echo $message['user_liked'] ? $lang[$_SESSION['language']]['unlike'] : $lang[$_SESSION['language']]['like']; ?> (<?php echo $message['like_count']; ?>)
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
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
    if ($diff < 60) return $_SESSION['language'] === 'nl' ? "$diff sec geleden" : "$diff sec ago";
    if ($diff < 3600) return round($diff / 60) . ($_SESSION['language'] === 'nl' ? " min geleden" : " min ago");
    if ($diff < 86400) return round($diff / 3600) . ($_SESSION['language'] === 'nl' ? " uur geleden" : " hours ago");
    return date('d M', strtotime($timestamp));
}
?>