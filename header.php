<?php
// Start de sessie als deze nog niet is gestart
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Stel een standaardtaal in als deze niet is ingesteld of ongeldig is
if (!isset($_SESSION['language']) || !in_array($_SESSION['language'], ['en', 'nl'])) {
    $_SESSION['language'] = 'en';
}

// Debug: Log de waarde van $_SESSION['language']
error_log("header.php: \$_SESSION['language'] = " . ($_SESSION['language'] ?? 'not set'));

// Definieer de $lang array globaal
global $lang;
$language = $_SESSION['language'];
$lang = [
    'en' => [
        'home' => 'Home',
        'profile' => 'Profile',
        'settings' => 'Settings',
        'admin' => 'Admin',
        'logout' => 'Logout',
        'tweet' => 'Tweet',
        'reply' => 'Reply',
        'retweet' => 'Retweet',
        'like' => 'Like',
        'unlike' => 'Unlike',
        'save' => 'Save' // Toegevoegd
    ],
    'nl' => [
        'home' => 'Home',
        'profile' => 'Profiel',
        'settings' => 'Instellingen',
        'admin' => 'Admin',
        'logout' => 'Uitloggen',
        'tweet' => 'Chirpen',
        'reply' => 'Antwoorden',
        'retweet' => 'Retweeten',
        'like' => 'Like',
        'unlike' => 'Unlike',
        'save' => 'Opslaan' // Toegevoegd
    ]
];

function renderHeader($activePage) {
    global $lang;
    $theme = isset($_SESSION['dark_mode']) && $_SESSION['dark_mode'] ? 'dark' : '';
    $language = $_SESSION['language'];
    $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
?>
<!DOCTYPE html>
<html lang="<?php echo $language; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chirpify</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="<?php echo $theme; ?>">
    <div class="twitter-container">
        <aside class="sidebar <?php echo $theme; ?>">
            <div class="logo">
                <h2>Chirpify</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item <?php echo $activePage === 'home' ? 'active' : ''; ?> <?php echo $theme; ?>">
                    <?php echo $lang[$language]['home']; ?>
                </a>
                <?php if ($isAdmin): ?>
                    <a href="admin.php" class="nav-item <?php echo $activePage === 'admin' ? 'active' : ''; ?> <?php echo $theme; ?>">
                        <?php echo $lang[$language]['admin']; ?>
                    </a>
                <?php endif; ?>
                <a href="profile.php" class="nav-item <?php echo $activePage === 'profile' ? 'active' : ''; ?> <?php echo $theme; ?>">
                    <?php echo $lang[$language]['profile']; ?>
                </a>
                <a href="settings.php" class="nav-item <?php echo $activePage === 'settings' ? 'active' : ''; ?> <?php echo $theme; ?>">
                    <?php echo $lang[$language]['settings']; ?>
                </a>
                <a href="logout.php" class="nav-item <?php echo $theme; ?>">
                    <?php echo $lang[$language]['logout']; ?>
                </a>
            </nav>
            <button class="tweet-btn"><?php echo $lang[$language]['tweet']; ?></button>
        </aside>
        <main class="feed">
<?php
}
?>