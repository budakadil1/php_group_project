<?php
require_once __DIR__ . '/../config/database.php';

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

$supplierId = 9999;
$name = 'Test Supplier';
$address = '123 Test St';
$phone = '555-1234';
$email = 'test@example.com';

assertTrue(addSupplier($supplierId, $name, $address, $phone, $email), "addSupplier should return true");
$supplier = getSupplierById($supplierId);
assertEqual($supplier['supplier_name'], $name, "getSupplierById should return correct name");

// Test updateSupplier
$newName = 'Updated Supplier';
$newAddress = '456 New St';
$newPhone = '555-5678';
$newEmail = 'updated@example.com';
assertTrue(updateSupplier($supplierId, $newName, $newAddress, $newPhone, $newEmail), "updateSupplier should return true");
$supplier = getSupplierById($supplierId);
assertEqual($supplier['supplier_name'], $newName, "Supplier name should be updated");
assertEqual($supplier['address'], $newAddress, "Supplier address should be updated");

// Test getAllSuppliers
$suppliers = getAllSuppliers();
assertTrue(is_array($suppliers) && count($suppliers) > 0, "getAllSuppliers should return a non-empty array");

// Test addProductOffering (add new product and link to supplier)
echo "\nRunning Product & Product Offering Tests...\n";
$productId = 8888;
$productName = 'Test Product';
$description = 'A product for testing';
$price = 123.45;
$quantity = 10;
$status = 'A';

assertTrue(addProductOffering($productId, $productName, $description, $price, $quantity, $status, $supplierId), "addProductOffering should return true");

// Test getAllProducts
$products = getAllProducts();
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
assertTrue(productSupplierLinkExists($productId, $supplierId), "productSupplierLinkExists should return true for existing link");

// Test update product offering (change price/quantity/status)
$newPrice = 222.22;
$newQuantity = 5;
$newStatus = 'B';
assertTrue(addProductOffering($productId, $productName, $description, $newPrice, $newQuantity, $newStatus, $supplierId), "addProductOffering should update existing offering");
$products = getAllProducts();
$found = false;
foreach ($products as $product) {
    if ($product['product_id'] == $productId && $product['supplier_id'] == $supplierId) {
        $found = true;
        assertEqual((float)$product['price'], (float)$newPrice, "Updated price should be correct");
        assertEqual($product['quantity'], $newQuantity, "Updated quantity should be correct");
        assertEqual($product['status'], $newStatus, "Updated status should be correct");
    }
}
assertTrue($found, "getAllProducts should include the updated product offering");

// Test deleteProductOffering
assertTrue(deleteProductOffering($productId, $supplierId), "deleteProductOffering should return true");
assertFalse(productSupplierLinkExists($productId, $supplierId), "productSupplierLinkExists should return false after deletion");

// Clean up test supplier
deleteSupplier($supplierId);

// Test deleteSupplier (should also delete related product offerings if any)
assertFalse(getSupplierById($supplierId), "getSupplierById should return false after deletion");

// Test getProductsBySupplier (should be empty for deleted supplier)
$productsBySupplier = getProductsBySupplier($supplierId);
assertTrue(is_array($productsBySupplier) && count($productsBySupplier) === 0, "getProductsBySupplier should return empty array for deleted supplier");

echo "\nAll DB operation tests complete.\n";
?>