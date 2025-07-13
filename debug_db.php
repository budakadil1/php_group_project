<?php
require_once 'classes/Database.php';

echo "=== DATABASE CONNECTION DEBUG ===\n";

try {
    // Check the Database class connection
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Get database info
    $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();
    echo "Connected to database: " . $dbName . "\n";
    
    // Check if tables exist
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables in database: " . implode(', ', $tables) . "\n";
    
    // Check product table structure
    $productColumns = $pdo->query("DESCRIBE product")->fetchAll(PDO::FETCH_ASSOC);
    echo "Product table columns:\n";
    foreach ($productColumns as $column) {
        echo "  - " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    // Check InventoryTable structure
    $inventoryColumns = $pdo->query("DESCRIBE InventoryTable")->fetchAll(PDO::FETCH_ASSOC);
    echo "InventoryTable columns:\n";
    foreach ($inventoryColumns as $column) {
        echo "  - " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    // Check actual product count
    $productCount = $pdo->query("SELECT COUNT(*) FROM product")->fetchColumn();
    echo "Actual product count in database: " . $productCount . "\n";
    
    // Show first few products if any exist
    if ($productCount > 0) {
        $products = $pdo->query("SELECT * FROM product LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        echo "First few products:\n";
        foreach ($products as $product) {
            echo "  - ID: " . $product['product_id'] . ", Name: " . $product['product_name'] . "\n";
        }
    }
    
    // Check if there are any foreign key constraints
    $foreignKeys = $pdo->query("
        SELECT 
            TABLE_NAME,
            COLUMN_NAME,
            CONSTRAINT_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE REFERENCED_TABLE_SCHEMA = DATABASE()
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Foreign key constraints:\n";
    foreach ($foreignKeys as $fk) {
        echo "  - " . $fk['TABLE_NAME'] . "." . $fk['COLUMN_NAME'] . " -> " . $fk['REFERENCED_TABLE_NAME'] . "." . $fk['REFERENCED_COLUMN_NAME'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "=== END DEBUG ===\n";
?> 