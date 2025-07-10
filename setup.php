<?php
/**
 * Database Setup Script
 * Use this to test your MySQL connection and initialize the database
 */

// Check if config file exists
if (!file_exists('config/database.php')) {
    die("Error: Database configuration file not found. Please create config/database.php first.");
}

require_once 'config/database.php';

echo "<h1>CP476 Inventory Manager - Database Setup</h1>";

// Test database connection
echo "<h2>Testing Database Connection</h2>";
$pdo = getDBConnection();

if ($pdo) {
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Test database initialization
    echo "<h2>Initializing Database Tables</h2>";
    if (initializeDatabase()) {
        echo "<p style='color: green;'>✅ Database tables created successfully!</p>";
        
        // Show table structure
        echo "<h2>Database Structure</h2>";
        try {
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            echo "<ul>";
            foreach ($tables as $table) {
                echo "<li><strong>$table</strong></li>";
            }
            echo "</ul>";
        } catch (PDOException $e) {
            echo "<p style='color: red;'>Error showing tables: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Failed to create database tables.</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Database connection failed!</p>";
    echo "<h3>Troubleshooting Steps:</h3>";
    echo "<ol>";
    echo "<li>Make sure MySQL server is running</li>";
    echo "<li>Check your database credentials in config/database.php</li>";
    echo "<li>Verify the database 'cp476_inventory' exists</li>";
    echo "<li>Ensure your MySQL user has proper permissions</li>";
    echo "</ol>";
    
    echo "<h3>MySQL Commands to Create Database:</h3>";
    echo "<pre>";
    echo "mysql -u root -p\n";
    echo "CREATE DATABASE cp476_inventory;\n";
    echo "CREATE USER 'your_username'@'localhost' IDENTIFIED BY 'your_password';\n";
    echo "GRANT ALL PRIVILEGES ON cp476_inventory.* TO 'your_username'@'localhost';\n";
    echo "FLUSH PRIVILEGES;\n";
    echo "EXIT;\n";
    echo "</pre>";
}

echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>Update config/database.php with your actual MySQL credentials</li>";
echo "<li>Run this setup script again to test the connection</li>";
echo "<li>Visit <a href='signup.php'>signup.php</a> to create your first user account</li>";
echo "<li>Visit <a href='login.php'>login.php</a> to test authentication</li>";
echo "</ol>";

echo "<p><strong>Note:</strong> Delete this setup.php file after successful configuration for security.</p>";
?> 