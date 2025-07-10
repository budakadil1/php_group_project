<?php
/**
 * Sign Up Page
 * MySQL-based user registration
 */

session_start();

// Include database configuration
require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // Validate input
    if (empty($username) || empty($password) || empty($confirm)) {
        $error = 'Please fill in all fields.';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters long.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Initialize database if needed
        initializeDatabase();
        
        // Check if username already exists
        if (usernameExists($username)) {
            $error = 'Username already exists. Please choose a different username.';
        } else {
            // Create new user
            if (createUser($username, $password)) {
                $success = 'Account created successfully! You can now log in.';
                // Clear form data
                $_POST = array();
            } else {
                $error = 'Failed to create account. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - CP476 Inventory Manager</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <header class="navbar">
        <div class="container">
            <a href="index.php" class="home-link">CP476 Inventory Manager</a>
        </div>
    </header>

    <div class="signup-container">
        <h2>Create Account</h2>
        <?php if ($error) : ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php elseif ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="text" name="username" placeholder="Username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            <button class="btn-submit" type="submit">Sign Up</button>
        </form>
        <p class="form-footer">
            Already have an account?
            <a href="login.php" class="btn secondary-btn">Log In</a>
        </p>
    </div>
</body>
</html>