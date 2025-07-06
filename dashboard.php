<?php
/**
 * Dashboard Page
 * Protected page for authenticated users
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}

$username = $_SESSION['username'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CP476 Inventory Manager</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <header class="navbar">
        <div class="container">
            <a href="index.php" class="home-link">CP476 Inventory Manager</a>
            <div style="margin-left: auto; display: flex; align-items: center; gap: 1rem;">
                <span style="color: white;">Welcome, <?= htmlspecialchars($username) ?></span>
                <a href="?logout=1" style="color: white; text-decoration: none; padding: 0.5rem 1rem; background-color: rgba(255,255,255,0.2); border-radius: 4px;">Logout</a>
            </div>
        </div>
    </header>

    <div class="container" style="margin-top: 3rem;">
        <h1>Dashboard</h1>
        <p>Welcome to your inventory management dashboard!</p>
        
        <div style="background: white; padding: 2rem; border-radius: 8px; margin-top: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h2>Quick Actions</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
                <div style="padding: 1rem; background: #f8f9fa; border-radius: 6px; text-align: center;">
                    <h3>Add Item</h3>
                    <p>Add new inventory items</p>
                    <a href="add_product.php" style="background: #007bff; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block;">Add Item</a>
                </div>
                <div style="padding: 1rem; background: #f8f9fa; border-radius: 6px; text-align: center;">
                    <h3>View Inventory</h3>
                    <p>Browse your inventory</p>
                    <a href="inventory.php" style="background: #28a745; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block;">View Inventory</a>
                </div>
                <div style="padding: 1rem; background: #f8f9fa; border-radius: 6px; text-align: center;">
                    <h3>Mass Import</h3>
                    <p>Add Products/Suppliers from File</p>
                    <a href="mass_import.php" style="background: #ffc107; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block;">Mass Import</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 