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

// Controleer of de gebruiker een admin is
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

require_once 'database_connection.php';
require_once 'header.php';

$successMessage = '';
$errorMessage = '';

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_user'])) {
        $userIdToDelete = $_POST['user_id'];
        if ($userIdToDelete == $_SESSION['userId']) {
            $errorMessage = $_SESSION['language'] === 'nl' ? "Je kunt je eigen account niet verwijderen." : "You cannot delete your own account.";
        } else {
            try {
                $pdo->beginTransaction();
                
                // Verwijder gerelateerde gegevens
                $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = :userId");
                $stmt->execute([':userId' => $userIdToDelete]);
                
                $stmt = $pdo->prepare("DELETE FROM messages WHERE user_id = :userId");
                $stmt->execute([':userId' => $userIdToDelete]);
                
                // Verwijder comments als de tabel bestaat
                if ($pdo->query("SHOW TABLES LIKE 'comments'")->rowCount() > 0) {
                    $stmt = $pdo->prepare("DELETE FROM comments WHERE user_id = :userId");
                    $stmt->execute([':userId' => $userIdToDelete]);
                }
                
                // Verwijder de gebruiker
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = :userId");
                $stmt->execute([':userId' => $userIdToDelete]);
                
                $pdo->commit();
                $successMessage = $_SESSION['language'] === 'nl' ? "Gebruiker succesvol verwijderd." : "User successfully deleted.";
            } catch (PDOException $e) {
                $pdo->rollBack();
                $errorMessage = $_SESSION['language'] === 'nl' ? "Fout bij verwijderen gebruiker: " . $e->getMessage() : "Error deleting user: " . $e->getMessage();
            }
        }
    }

    if (isset($_POST['delete_tweet'])) {
        try {
            $pdo->beginTransaction();
            
            // Verwijder gerelateerde likes
            $stmt = $pdo->prepare("DELETE FROM likes WHERE message_id = :messageId");
            $stmt->execute([':messageId' => $_POST['tweet_id']]);
            
            // Verwijder comments als de tabel bestaat
            if ($pdo->query("SHOW TABLES LIKE 'comments'")->rowCount() > 0) {
                $stmt = $pdo->prepare("DELETE FROM comments WHERE message_id = :messageId");
                $stmt->execute([':messageId' => $_POST['tweet_id']]);
            }
            
            // Verwijder de tweet (message)
            $stmt = $pdo->prepare("DELETE FROM messages WHERE id = :messageId");
            $stmt->execute([':messageId' => $_POST['tweet_id']]);
            
            $pdo->commit();
            $successMessage = $_SESSION['language'] === 'nl' ? "Bericht succesvol verwijderd." : "Tweet deleted successfully.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errorMessage = $_SESSION['language'] === 'nl' ? "Fout bij verwijderen bericht: " . $e->getMessage() : "Error deleting tweet: " . $e->getMessage();
        }
    }

    if (isset($_POST['delete_comment'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM comments WHERE id = :commentId");
            $stmt->execute([':commentId' => $_POST['comment_id']]);
            $successMessage = $_SESSION['language'] === 'nl' ? "Reactie succesvol verwijderd." : "Comment deleted successfully.";
        } catch (PDOException $e) {
            $errorMessage = $_SESSION['language'] === 'nl' ? "Fout bij verwijderen reactie: " . $e->getMessage() : "Error deleting comment: " . $e->getMessage();
        }
    }

    if (isset($_POST['update_user'])) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET handle = :handle, bio = :bio WHERE id = :userId");
            $stmt->execute([
                ':handle' => $_POST['handle'],
                ':bio' => $_POST['bio'],
                ':userId' => $_POST['user_id']
            ]);
            $successMessage = $_SESSION['language'] === 'nl' ? "Gebruiker succesvol bijgewerkt." : "User updated successfully.";
        } catch (PDOException $e) {
            $errorMessage = $_SESSION['language'] === 'nl' ? "Fout bij bijwerken gebruiker: " . $e->getMessage() : "Error updating user: " . $e->getMessage();
        }
    }
}

// Fetch all data for admin panel
try {
    // Fetch users
    $stmt = $pdo->query("SELECT id, handle, bio, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch messages (tweets)
    $stmt = $pdo->query("SELECT m.*, u.handle as user_handle 
                        FROM messages m 
                        JOIN users u ON m.user_id = u.id 
                        ORDER BY m.created_at DESC");
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch comments if you have a comments table
    $comments = [];
    if ($pdo->query("SHOW TABLES LIKE 'comments'")->rowCount() > 0) {
        $stmt = $pdo->query("SELECT c.*, u.handle as user_handle 
                            FROM comments c 
                            JOIN users u ON c.user_id = u.id 
                            ORDER BY c.created_at DESC");
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $errorMessage = $_SESSION['language'] === 'nl' ? "Fout bij ophalen gegevens: " . $e->getMessage() : "Error fetching data: " . $e->getMessage();
}

renderHeader('admin');
?>

<header class="feed-header <?php echo $theme; ?>">
    <h1><?php echo isset($lang[$_SESSION['language']]['admin']) ? $lang[$_SESSION['language']]['admin'] : 'Admin'; ?></h1>
</header>

<?php if ($errorMessage): ?>
    <div class="message error"><?php echo htmlspecialchars($errorMessage); ?></div>
<?php endif; ?>

<?php if ($successMessage): ?>
    <div class="message success"><?php echo htmlspecialchars($successMessage); ?></div>
<?php endif; ?>

<div class="admin-section">
    <h2><?php echo $_SESSION['language'] === 'nl' ? 'Gebruikersbeheer' : 'Users Management'; ?></h2>
    <table class="admin-table">
        <thead>
            <tr>
                <th><?php echo $_SESSION['language'] === 'nl' ? 'Gebruikersnaam' : 'Handle'; ?></th>
                <th>Bio</th>
                <th><?php echo $_SESSION['language'] === 'nl' ? 'Aangemaakt op' : 'Created At'; ?></th>
                <th><?php echo $_SESSION['language'] === 'nl' ? 'Acties' : 'Actions'; ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo htmlspecialchars($user['handle']); ?></td>
                <td><?php echo htmlspecialchars($user['bio'] ?? ''); ?></td>
                <td><?php echo $user['created_at']; ?></td>
                <td>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="delete_user" value="1">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <button type="submit" class="btn-delete" onclick="return confirm('<?php echo $_SESSION['language'] === 'nl' ? 'Weet je zeker dat je deze gebruiker wilt verwijderen?' : 'Are you sure you want to delete this user?'; ?>')">
                            <?php echo $_SESSION['language'] === 'nl' ? 'Verwijderen' : 'Delete'; ?>
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="admin-section">
    <h2><?php echo $_SESSION['language'] === 'nl' ? 'Berichtenbeheer' : 'Messages Management'; ?></h2>
    <table class="admin-table">
        <thead>
            <tr>
                <th><?php echo $_SESSION['language'] === 'nl' ? 'Gebruiker' : 'User'; ?></th>
                <th><?php echo $_SESSION['language'] === 'nl' ? 'Inhoud' : 'Content'; ?></th>
                <th><?php echo $_SESSION['language'] === 'nl' ? 'Aangemaakt op' : 'Created At'; ?></th>
                <th><?php echo $_SESSION['language'] === 'nl' ? 'Acties' : 'Actions'; ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($messages as $message): ?>
            <tr>
                <td><?php echo htmlspecialchars($message['user_handle']); ?></td>
                <td><?php echo htmlspecialchars($message['content']); ?></td>
                <td><?php echo $message['created_at']; ?></td>
                <td>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="delete_tweet" value="1">
                        <input type="hidden" name="tweet_id" value="<?php echo $message['id']; ?>">
                        <button type="submit" class="btn-delete" onclick="return confirm('<?php echo $_SESSION['language'] === 'nl' ? 'Weet je zeker dat je dit bericht wilt verwijderen?' : 'Are you sure you want to delete this message?'; ?>')">
                            <?php echo $_SESSION['language'] === 'nl' ? 'Verwijderen' : 'Delete'; ?>
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (!empty($comments)): ?>
<div class="admin-section">
    <h2><?php echo $_SESSION['language'] === 'nl' ? 'Reactiebeheer' : 'Comments Management'; ?></h2>
    <table class="admin-table">
        <thead>
            <tr>
                <th><?php echo $_SESSION['language'] === 'nl' ? 'Gebruiker' : 'User'; ?></th>
                <th><?php echo $_SESSION['language'] === 'nl' ? 'Inhoud' : 'Content'; ?></th>
                <th><?php echo $_SESSION['language'] === 'nl' ? 'Aangemaakt op' : 'Created At'; ?></th>
                <th><?php echo $_SESSION['language'] === 'nl' ? 'Acties' : 'Actions'; ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($comments as $comment): ?>
            <tr>
                <td><?php echo htmlspecialchars($comment['user_handle']); ?></td>
                <td><?php echo htmlspecialchars($comment['content']); ?></td>
                <td><?php echo $comment['created_at']; ?></td>
                <td>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="delete_comment" value="1">
                        <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                        <button type="submit" class="btn-delete" onclick="return confirm('<?php echo $_SESSION['language'] === 'nl' ? 'Weet je zeker dat je deze reactie wilt verwijderen?' : 'Are you sure you want to delete this comment?'; ?>')">
                            <?php echo $_SESSION['language'] === 'nl' ? 'Verwijderen' : 'Delete'; ?>
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

</main>
</div>
</body>
</html>