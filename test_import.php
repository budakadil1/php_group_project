<?php
require_once 'config/database.php';

// Initialize database
initializeDatabase();

// Test supplier import
echo "Testing supplier import...\n";
$supplierData = "9512, Acme Corporation, 123 Main St, 205-288-8591, info@acme-corp.com\n";
$supplierData .= "8642, Xerox Inc., 456 High St, 505-398-8414, info@xrx.com\n";

$lines = explode("\n", trim($supplierData));
$pdo = getDBConnection();
$successCount = 0;
$errorCount = 0;

foreach ($lines as $line) {
    if (empty($line)) continue;
    
    $result = processSupplierLine($line, 1, $pdo);
    if ($result['success']) {
        $successCount++;
        echo "✓ Supplier imported successfully\n";
    } else {
        $errorCount++;
        echo "✗ Error: " . $result['error'] . "\n";
    }
}

echo "Supplier import results: $successCount successful, $errorCount failed\n\n";

// Test product import
echo "Testing product import...\n";
$productData = "2591, Camera, Camera, 799.9, 50, B, 9512\n";
$productData .= "3374, Laptop, MacBook Pro, 1799.9, 30, A, 8642\n";

$lines = explode("\n", trim($productData));
$successCount = 0;
$errorCount = 0;

foreach ($lines as $line) {
    if (empty($line)) continue;
    
    $result = processProductLine($line, 1, $pdo);
    if ($result['success']) {
        $successCount++;
        echo "✓ Product imported successfully\n";
    } else {
        $errorCount++;
        echo "✗ Error: " . $result['error'] . "\n";
    }
}

echo "Product import results: $successCount successful, $errorCount failed\n";

// Show current data
echo "\nCurrent suppliers:\n";
$suppliers = getAllSuppliers();
foreach ($suppliers as $supplier) {
    echo "- {$supplier['supplier_id']}: {$supplier['supplier_name']}\n";
}

echo "\nCurrent products:\n";
$products = getAllProducts();
foreach ($products as $product) {
    echo "- {$product['product_id']}: {$product['product_name']} (Supplier: {$product['supplier_id']})\n";
}
?> 