<?php
require_once 'classes/Supplier.php';
require_once 'classes/Product.php';
require_once 'classes/ProductSupplier.php';

try {
    // Use the new OOP classes
    $supplierManager = new SupplierManager();
    $productManager = new ProductManager();
    $productSupplierManager = new ProductSupplierManager();
    
    echo "Clearing all inventory data...\n";
    
    // Start a transaction to ensure all operations are atomic
    $pdo = $supplierManager->getConnection();
    $pdo->beginTransaction();
    
    try {
        // First, delete all product-supplier relationships
        $pdo->exec('DELETE FROM InventoryTable');
        echo "Deleted all product-supplier relationships.\n";
        
        // Then delete all products
        $pdo->exec('DELETE FROM product');
        echo "Deleted all products.\n";
        
        // Finally delete all suppliers
        $pdo->exec('DELETE FROM supplier');
        echo "Deleted all suppliers.\n";
        
        // Commit the transaction
        $pdo->commit();
        echo "All inventory data has been cleared successfully!\n";
        
    } catch (Exception $e) {
        // Rollback on error
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 