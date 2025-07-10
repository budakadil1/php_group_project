<?php
require_once 'config/database.php';

$pdo = getDBConnection();
if (!$pdo) {
    die('Database connection failed.');
}

try {
    // Disable foreign key checks to allow truncating both tables
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    $pdo->exec('TRUNCATE TABLE product');
    $pdo->exec('TRUNCATE TABLE supplier');
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    echo "All product and supplier records have been deleted.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 