<?php
require_once 'config/database.php';

$pdo = getDBConnection();
if (!$pdo) {
    die('Database connection failed.');
}

echo "=== DATABASE DEBUG ===\n\n";

// Check if tables exist
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "Tables in database: " . implode(', ', $tables) . "\n\n";

// Check product table
echo "=== PRODUCT TABLE ===\n";
$stmt = $pdo->query("SELECT COUNT(*) as count FROM product");
$count = $stmt->fetch()['count'];
echo "Total products: $count\n";

if ($count > 0) {
    $products = $pdo->query("SELECT * FROM product LIMIT 5")->fetchAll();
    foreach ($products as $product) {
        echo "- ID: {$product['product_id']}, Name: {$product['product_name']}, Supplier: {$product['supplier_id']}\n";
    }
}

// Check supplier table
echo "\n=== SUPPLIER TABLE ===\n";
$stmt = $pdo->query("SELECT COUNT(*) as count FROM supplier");
$count = $stmt->fetch()['count'];
echo "Total suppliers: $count\n";

if ($count > 0) {
    $suppliers = $pdo->query("SELECT * FROM supplier LIMIT 5")->fetchAll();
    foreach ($suppliers as $supplier) {
        echo "- ID: {$supplier['supplier_id']}, Name: {$supplier['supplier_name']}\n";
    }
}

echo "\n=== END DEBUG ===\n";
?> 