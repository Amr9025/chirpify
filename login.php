<?php
session_start();
require_once 'database_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $handle = trim($_POST['handle']);
    $password = $_POST['password'];

    if (empty($handle) || empty($password)) {
        $errorMessage = "Handle and password are required.";
        header("Location: login.php?error=" . urlencode($errorMessage));
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id, handle, password, is_admin FROM users WHERE handle = :handle");
        $stmt->execute(['handle' => $handle]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['userId'] = $user['id'];
            $_SESSION['userHandle'] = $user['handle'];
            $_SESSION['isAdmin'] = $user['is_admin'];
            
            if ($user['is_admin'] == 1) {
                header("Location: admin.php");
            } else {
                header("Location: index.php");
            }
            exit;
        } else {
            $errorMessage = "Invalid handle or password.";
            header("Location: login.php?error=" . urlencode($errorMessage));
            exit;
        }
    } catch (PDOException $e) {
        $errorMessage = "Error logging in: " . $e->getMessage();
        header("Location: login.php?error=" . urlencode($errorMessage));
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chirpify - Login</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="signup-container">
        <h2>Login</h2>
        <?php if (isset($_GET['error'])): ?>
            <p class="error"><?php echo htmlspecialchars($_GET['error']); ?></p>
        <?php endif; ?>
        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="handle">Username</label>
                <input type="text" name="handle" id="handle" placeholder="@username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>
            </div>
            <button type="submit" class="save-profile-btn">Login</button>
        </form>
        <p class="signup-link">Not registered? <a href="signup.php">Sign up here</a></p>
    </div>
</body>
</html>