<?php
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Supplier.php';
require_once __DIR__ . '/../classes/Product.php';
require_once __DIR__ . '/../classes/ProductSupplier.php';

function assertEqual($a, $b, $msg) {
    if ($a === $b) {
        echo "[PASS] $msg\n";
    } else {
        echo "[FAIL] $msg (Expected: " . var_export($b, true) . ", Got: " . var_export($a, true) . ")\n";
    }
}

function assertTrue($cond, $msg) {
    if ($cond) {
        echo "[PASS] $msg\n";
    } else {
        echo "[FAIL] $msg\n";
    }
}

function assertFalse($cond, $msg) {
    if (!$cond) {
        echo "[PASS] $msg\n";
    } else {
        echo "[FAIL] $msg\n";
    }
}

echo "Running Supplier Tests...\n";

$supplierManager = new SupplierManager();
$productManager = new ProductManager();
$productSupplierManager = new ProductSupplierManager();

// Clean up any existing test data first
$testSupplierId = 9999;
$testProductId = 8888;

echo "Cleaning up any existing test data...\n";
$productSupplierManager->deleteProductOffering($testProductId, $testSupplierId);
$supplierManager->deleteSupplier($testSupplierId);
$productManager->deleteProduct($testProductId);

$supplierId = $testSupplierId;
$name = 'Test Supplier';
$address = '123 Test St';
$phone = '555-1234';
$email = 'test@example.com';

try {
    assertTrue($supplierManager->addSupplier($supplierId, $name, $address, $phone, $email), "addSupplier should return true");
    $supplier = $supplierManager->getSupplierById($supplierId);
    assertEqual($supplier->supplier_name, $name, "getSupplierById should return correct name");

    // Test updateSupplier
    $newName = 'Updated Supplier';
    $newAddress = '456 New St';
    $newPhone = '555-5678';
    $newEmail = 'updated@example.com';
    assertTrue($supplierManager->updateSupplier($supplierId, $newName, $newAddress, $newPhone, $newEmail), "updateSupplier should return true");
    $supplier = $supplierManager->getSupplierById($supplierId);
    assertEqual($supplier->supplier_name, $newName, "Supplier name should be updated");
    assertEqual($supplier->address, $newAddress, "Supplier address should be updated");

    // Test getAllSuppliers
    $suppliers = $supplierManager->getAllSuppliers();
    assertTrue(is_array($suppliers) && count($suppliers) > 0, "getAllSuppliers should return a non-empty array");

    // Test addProductOffering (add new product and link to supplier)
    echo "\nRunning Product & Product Offering Tests...\n";

    $productId = $testProductId;
    $productName = 'Test Product';
    $description = 'A product for testing';
    $price = 123.45;
    $quantity = 10;
    $status = 'A';

    // Add product first
    $productManager->addProduct($productId, $productName, $description);
    assertTrue($productSupplierManager->addProductOffering($productId, $supplierId, $price, $quantity, $status), "addProductOffering should return true");

    // Test getAllProducts (we need to combine data from multiple managers)
    $allProducts = $productManager->getAllProducts();
    $allSuppliers = $supplierManager->getAllSuppliers();
    $allProductOfferings = $productSupplierManager->getAllProductOfferings();

    $products = [];
    foreach ($allProductOfferings as $offering) {
        $product = $productManager->getProductById($offering->product_id);
        $supplier = $supplierManager->getSupplierById($offering->supplier_id);
        
        if ($product && $supplier) {
            $products[] = [
                'product_id' => $product->product_id,
                'product_name' => $product->product_name,
                'description' => $product->description,
                'price' => $offering->price,
                'quantity' => $offering->quantity,
                'status' => $offering->status,
                'supplier_id' => $supplier->supplier_id,
                'supplier_name' => $supplier->supplier_name
            ];
        }
    }

    $found = false;
    foreach ($products as $product) {
        if ($product['product_id'] == $productId && $product['supplier_id'] == $supplierId) {
            $found = true;
            assertEqual($product['product_name'], $productName, "getAllProducts should return correct product name");
            assertEqual($product['quantity'], $quantity, "getAllProducts should return correct quantity");
        }
    }
    assertTrue($found, "getAllProducts should include the test product offering");

    // Test productSupplierLinkExists
    assertTrue($productSupplierManager->productSupplierLinkExists($productId, $supplierId), "productSupplierLinkExists should return true for existing link");

    // Test update product offering (change price/quantity/status)
    $newPrice = 222.22;
    $newQuantity = 5;
    $newStatus = 'B';
    assertTrue($productSupplierManager->addProductOffering($productId, $supplierId, $newPrice, $newQuantity, $newStatus), "addProductOffering should update existing offering");

    $allProductOfferings = $productSupplierManager->getAllProductOfferings();
    $found = false;
    foreach ($allProductOfferings as $offering) {
        if ($offering->product_id == $productId && $offering->supplier_id == $supplierId) {
            $found = true;
            assertEqual((float)$offering->price, (float)$newPrice, "Updated price should be correct");
            assertEqual($offering->quantity, $newQuantity, "Updated quantity should be correct");
            assertEqual($offering->status, $newStatus, "Updated status should be correct");
        }
    }
    assertTrue($found, "getAllProductOfferings should include the updated product offering");

    // Test deleteProductOffering
    assertTrue($productSupplierManager->deleteProductOffering($productId, $supplierId), "deleteProductOffering should return true");
    assertFalse($productSupplierManager->productSupplierLinkExists($productId, $supplierId), "productSupplierLinkExists should return false after deletion");

    // Test deleteSupplier (should also delete related product offerings if any)
    assertTrue($supplierManager->deleteSupplier($supplierId), "deleteSupplier should return true");
    assertFalse($supplierManager->getSupplierById($supplierId), "getSupplierById should return false after deletion");

    // Test getProductsBySupplier (should be empty for deleted supplier)
    $productsBySupplier = $productSupplierManager->getProductsBySupplier($supplierId);
    assertTrue(is_array($productsBySupplier) && count($productsBySupplier) === 0, "getProductsBySupplier should return empty array for deleted supplier");

    // Clean up test product
    $productManager->deleteProduct($productId);

    echo "\nAll DB operation tests complete.\n";

} catch (Exception $e) {
    echo "\n[ERROR] Test failed with exception: " . $e->getMessage() . "\n";
    echo "Cleaning up test data...\n";
    
    // Clean up test data even if test fails
    $productSupplierManager->deleteProductOffering($testProductId, $testSupplierId);
    $supplierManager->deleteSupplier($testSupplierId);
    $productManager->deleteProduct($testProductId);
    
    echo "Cleanup complete.\n";
    throw $e;
}
?>