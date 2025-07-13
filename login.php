<?php
/**
 * Login Page
 * MySQL-based user authentication
 */

session_start();
require_once __DIR__ . '/classes/User.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $userManager = new UserManager();
        $user = $userManager->authenticateUser($username, $password);
        if ($user) {
            $_SESSION['user_id'] = $user->id;
            $_SESSION['username'] = $user->username;
            $_SESSION['email'] = $user->email;
            $_SESSION['logged_in'] = true;
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CP476 Inventory Manager</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <header class="navbar">
        <div class="container">
            <a href="index.php" class="home-link">Home</a>
        </div>
    </header>

    <div class="login-container">
        <h2>Login</h2>
        <?php if ($error) : ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="text" name="username" placeholder="Username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
            <input type="password" name="password" placeholder="Password" required>
            <button class="btn-submit" type="submit">Log In</button>
        </form>
        <p class="form-footer">
            Don't have an account?
            <a href="signup.php" class="btn secondary-btn">Sign Up</a>
        </p>
    </div>
</body>
</html>
