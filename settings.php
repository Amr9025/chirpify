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
    error_log("settings.php: \$lang[\$_SESSION['language']] is niet ingesteld voor taal: " . $_SESSION['language']);
    $_SESSION['language'] = 'en';
}

$userId = $_SESSION['userId'];
$errorMessage = '';
$successMessage = '';

// Debug: Haal de rol direct uit de database
try {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = :userId");
    $stmt->execute([':userId' => $userId]);
    $userRole = $stmt->fetchColumn();
    error_log("settings.php: Database role for userId $userId = " . $userRole);
    // Update de sessie met de juiste rol
    $_SESSION['role'] = $userRole;
} catch (PDOException $e) {
    error_log("settings.php: Fout bij ophalen rol: " . $e->getMessage());
}

// Haal huidige instellingen op (alleen handle uit de database)
try {
    $stmt = $pdo->prepare("SELECT handle FROM users WHERE id = :userId");
    $stmt->execute([':userId' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) throw new Exception("Gebruiker niet gevonden");
} catch (Exception $e) {
    $errorMessage = $_SESSION['language'] === 'nl' ? "Fout bij ophalen instellingen: " . $e->getMessage() : "Error fetching settings: " . $e->getMessage();
}

// Verwerk instellingenupdate
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $handle = trim($_POST['handle']);
    $password = trim($_POST['password'] ?? '');
    $darkMode = $_POST['dark_mode'] === '1' ? 1 : 0; // Gebruik de exacte waarde van de selectie
    $language = $_POST['language'] ?? 'en';

    // Update database (alleen handle en password)
    try {
        $sql = "UPDATE users SET handle = :handle" . (!empty($password) ? ", password = :password" : "") . " WHERE id = :userId";
        $stmt = $pdo->prepare($sql);
        $params = [
            ':handle' => $handle,
            ':userId' => $userId
        ];
        if (!empty($password)) {
            $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        $stmt->execute($params);

        // Update sessie
        $_SESSION['dark_mode'] = (bool)$darkMode;
        $_SESSION['language'] = $language;
        $user['handle'] = $handle;

        $successMessage = $_SESSION['language'] === 'nl' ? "Instellingen bijgewerkt!" : "Settings updated!";
    } catch (PDOException $e) {
        $errorMessage = $_SESSION['language'] === 'nl' ? "Fout bij opslaan: " . $e->getMessage() : "Error saving: " . $e->getMessage();
    }
}

renderHeader('settings');
?>

<header class="feed-header <?php echo $theme; ?>">
    <h1><?php echo isset($lang[$_SESSION['language']]['settings']) ? $lang[$_SESSION['language']]['settings'] : 'Settings'; ?></h1>
</header>

<?php if ($errorMessage): ?>
    <p class="error"><?php echo htmlspecialchars($errorMessage); ?></p>
<?php endif; ?>
<?php if ($successMessage): ?>
    <p class="success"><?php echo htmlspecialchars($successMessage); ?></p>
<?php endif; ?>

<div class="settings-form">
    <form action="settings.php" method="POST" class="settings-container">
        <input type="hidden" name="update_settings" value="1">
        <div class="form-group">
            <label><?php echo $_SESSION['language'] === 'nl' ? 'Gebruikersnaam' : 'Username'; ?></label>
            <input type="text" name="handle" class="<?php echo $theme; ?>" value="<?php echo htmlspecialchars($user['handle']); ?>" required>
        </div>
        <div class="form-group">
            <label><?php echo $_SESSION['language'] === 'nl' ? 'Wachtwoord' : 'Password'; ?></label>
            <input type="password" name="password" class="<?php echo $theme; ?>">
        </div>
        <div class="form-group">
            <label><?php echo $_SESSION['language'] === 'nl' ? 'Taal' : 'Language'; ?></label>
            <select name="language" class="<?php echo $theme; ?>">
                <option value="en" <?php echo $_SESSION['language'] === 'en' ? 'selected' : ''; ?>>English</option>
                <option value="nl" <?php echo $_SESSION['language'] === 'nl' ? 'selected' : ''; ?>>Nederlands</option>
            </select>
        </div>
        <div class="form-group">
            <label><?php echo $_SESSION['language'] === 'nl' ? 'Donkere modus' : 'Dark Mode'; ?></label>
            <select name="dark_mode" class="<?php echo $theme; ?>">
                <option value="0" <?php echo !$_SESSION['dark_mode'] ? 'selected' : ''; ?>>Off</option>
                <option value="1" <?php echo $_SESSION['dark_mode'] ? 'selected' : ''; ?>>On</option>
            </select>
        </div>
        <button type="submit" class="save-profile-btn"><?php echo isset($lang[$_SESSION['language']]['save']) ? $lang[$_SESSION['language']]['save'] : 'Save'; ?></button>
    </form>
</div>

</main>
</div>
</body>
</html>