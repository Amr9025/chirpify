<?php
session_start();
if (isset($_SESSION['userId'])) {
    header("Location: index.php");
    exit;
}
$errorMessage = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chirpify - Sign Up</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="signup-container">
        <h2>Create Account</h2>
        <?php if ($errorMessage): ?>
            <p class="error"><?php echo htmlspecialchars($errorMessage); ?></p>
        <?php endif; ?>
        <form action="create_account.php" method="POST">
            <div class="form-group">
                <label for="handle">Username</label>
                <input type="text" name="handle" id="handle" placeholder="@username" required pattern="[a-zA-Z0-9_]+">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>
            </div>
            <button type="submit" class="save-profile-btn">Sign Up</button>
        </form>
        <p class="signup-link">Already have an account? <a href="login.php">Login here</a></p>
    </div>
</body>
</html>