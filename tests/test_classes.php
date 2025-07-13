<?php
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Supplier.php';
require_once __DIR__ . '/../classes/Product.php';
require_once __DIR__ . '/../classes/ProductSupplier.php';
require_once __DIR__ . '/../classes/Importer.php';

// Test utilities
function assertEqual($a, $b, $msg) {
    if ($a === $b) {
        echo "[PASS] $msg\n";
        return true;
    } else {
        echo "[FAIL] $msg (Expected: " . var_export($b, true) . ", Got: " . var_export($a, true) . ")\n";
        return false;
    }
}

function assertTrue($cond, $msg) {
    if ($cond) {
        echo "[PASS] $msg\n";
        return true;
    } else {
        echo "[FAIL] $msg\n";
        return false;
    }
}

function assertFalse($cond, $msg) {
    if (!$cond) {
        echo "[PASS] $msg\n";
        return true;
    } else {
        echo "[FAIL] $msg\n";
        return false;
    }
}

function assertNotNull($value, $msg) {
    if ($value !== null) {
        echo "[PASS] $msg\n";
        return true;
    } else {
        echo "[FAIL] $msg (Value is null)\n";
        return false;
    }
}

function assertIsArray($value, $msg) {
    if (is_array($value)) {
        echo "[PASS] $msg\n";
        return true;
    } else {
        echo "[FAIL] $msg (Expected array, got " . gettype($value) . ")\n";
        return false;
    }
}

// Test data constants
const TEST_USER_ID = 9999;
const TEST_SUPPLIER_ID = 8888;
const TEST_PRODUCT_ID = 7777;
$uniqueSuffix = uniqid();
const TEST_PASSWORD = 'testpass123';
$TEST_USERNAME = 'testuser_' . $uniqueSuffix;
$TEST_EMAIL = 'test_' . $uniqueSuffix . '@example.com';

// Cleanup function
function cleanupTestData() {
    $userManager = new UserManager();
    $supplierManager = new SupplierManager();
    $productManager = new ProductManager();
    $productSupplierManager = new ProductSupplierManager();
    
    echo "Cleaning up any existing test data...\n";
    $productSupplierManager->deleteProductOffering(TEST_PRODUCT_ID, TEST_SUPPLIER_ID);
    $supplierManager->deleteSupplier(TEST_SUPPLIER_ID);
    $productManager->deleteProduct(TEST_PRODUCT_ID);
    // Note: UserManager doesn't have deleteUser method, so we skip user cleanup
    echo "Cleanup complete.\n";
}

// Run cleanup at start
cleanupTestData();

echo "\n==========================================\n";
echo "Running Comprehensive Class Tests...\n";
echo "==========================================\n";

// ==========================================
// TEST 1: Database Class
// ==========================================
echo "\n[Database Class Test]\n";
try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    assertNotNull($pdo, "Database connection should be established");
    assertTrue($pdo instanceof PDO, "Connection should be a PDO instance");
    echo "[PASS] Database class works correctly\n";
} catch (Exception $e) {
    echo "[FAIL] Database class failed: " . $e->getMessage() . "\n";
}

echo "\n------------------------------------------\n";

// ==========================================
// TEST 2: User Class and UserManager
// ==========================================
echo "[User Class and UserManager Test]\n";
$userManager = new UserManager();
$passed = 0;
$total = 0;

try {
    // Test user creation
    $total++;
    if (assertTrue($userManager->createUser($TEST_USERNAME, TEST_PASSWORD, $TEST_EMAIL), "createUser should return true")) {
        $passed++;
    }
    
    // Test user retrieval by username
    $total++;
    $user = $userManager->getUserByUsername($TEST_USERNAME);
    if (assertNotNull($user, "getUserByUsername should return user object")) {
        $passed++;
    }
    
    // Test user properties
    if ($user) {
        $total++;
        if (assertEqual($user->username, $TEST_USERNAME, "Username should match")) {
            $passed++;
        }
        
        $total++;
        if (assertEqual($user->email, $TEST_EMAIL, "Email should match")) {
            $passed++;
        }
    }
    
    // Test user authentication
    $total++;
    $authResult = $userManager->authenticateUser($TEST_USERNAME, TEST_PASSWORD);
    if (assertTrue($authResult !== false, "authenticateUser should return user object for valid credentials")) {
        $passed++;
    }
    
    // Test invalid authentication
    $total++;
    if (assertFalse($userManager->authenticateUser($TEST_USERNAME, 'wrongpassword'), "authenticateUser should return false for invalid password")) {
        $passed++;
    }
    
    // Test username exists
    $total++;
    if (assertTrue($userManager->usernameExists($TEST_USERNAME), "usernameExists should return true for existing username")) {
        $passed++;
    }
    
    // Test username doesn't exist
    $total++;
    if (assertFalse($userManager->usernameExists('nonexistentuser'), "usernameExists should return false for non-existent username")) {
        $passed++;
    }
    
    // Test getAllUsers
    $total++;
    $users = $userManager->getAllUsers();
    if (assertIsArray($users, "getAllUsers should return array")) {
        $passed++;
    }
    
    $total++;
    if (assertTrue(count($users) > 0, "getAllUsers should return non-empty array")) {
        $passed++;
    }
    
    echo "User tests: $passed/$total passed\n";
    
    // Clean up test user
    $userManager->deleteUser($TEST_USERNAME);
} catch (Exception $e) {
    echo "[ERROR] User tests failed: " . $e->getMessage() . "\n";
}

echo "\n------------------------------------------\n";

// ==========================================
// TEST 3: Supplier Class and SupplierManager
// ==========================================
echo "[Supplier Class and SupplierManager Test]\n";
$supplierManager = new SupplierManager();
$passed = 0;
$total = 0;

try {
    // Test supplier creation
    $total++;
    $supplierName = 'Test Supplier';
    $address = '123 Test St';
    $phone = '555-1234';
    $email = 'supplier@example.com';
    
    if (assertTrue($supplierManager->addSupplier(TEST_SUPPLIER_ID, $supplierName, $address, $phone, $email), "addSupplier should return true")) {
        $passed++;
    }
    
    // Test supplier retrieval
    $total++;
    $supplier = $supplierManager->getSupplierById(TEST_SUPPLIER_ID);
    if (assertNotNull($supplier, "getSupplierById should return supplier object")) {
        $passed++;
    }
    
    // Test supplier properties
    if ($supplier) {
        $total++;
        if (assertEqual($supplier->supplier_id, TEST_SUPPLIER_ID, "Supplier ID should match")) {
            $passed++;
        }
        
        $total++;
        if (assertEqual($supplier->supplier_name, $supplierName, "Supplier name should match")) {
            $passed++;
        }
        
        $total++;
        if (assertEqual($supplier->address, $address, "Supplier address should match")) {
            $passed++;
        }
    }
    
    // Test supplier update
    $total++;
    $newName = 'Updated Supplier';
    $newAddress = '456 New St';
    $newPhone = '555-5678';
    $newEmail = 'updated@example.com';
    
    if (assertTrue($supplierManager->updateSupplier(TEST_SUPPLIER_ID, $newName, $newAddress, $newPhone, $newEmail), "updateSupplier should return true")) {
        $passed++;
    }
    
    // Verify update
    $updatedSupplier = $supplierManager->getSupplierById(TEST_SUPPLIER_ID);
    $total++;
    if (assertEqual($updatedSupplier->supplier_name, $newName, "Supplier name should be updated")) {
        $passed++;
    }
    
    // Test getAllSuppliers
    $total++;
    $suppliers = $supplierManager->getAllSuppliers();
    if (assertIsArray($suppliers, "getAllSuppliers should return array")) {
        $passed++;
    }
    
    $total++;
    if (assertTrue(count($suppliers) > 0, "getAllSuppliers should return non-empty array")) {
        $passed++;
    }
    
    // Test supplier deletion
    $total++;
    if (assertTrue($supplierManager->deleteSupplier(TEST_SUPPLIER_ID), "deleteSupplier should return true")) {
        $passed++;
    }
    
    // Verify deletion
    $total++;
    if (assertFalse($supplierManager->getSupplierById(TEST_SUPPLIER_ID), "getSupplierById should return false after deletion")) {
        $passed++;
    }
    
    echo "Supplier tests: $passed/$total passed\n";
    
} catch (Exception $e) {
    echo "[ERROR] Supplier tests failed: " . $e->getMessage() . "\n";
}

echo "\n------------------------------------------\n";

// ==========================================
// TEST 4: Product Class and ProductManager
// ==========================================
echo "[Product Class and ProductManager Test]\n";
$productManager = new ProductManager();
$passed = 0;
$total = 0;

try {
    // Test product creation
    $total++;
    $productName = 'Test Product';
    $description = 'A product for testing';
    
    if (assertTrue($productManager->addProduct(TEST_PRODUCT_ID, $productName, $description), "addProduct should return true")) {
        $passed++;
    }
    
    // Test product retrieval
    $total++;
    $product = $productManager->getProductById(TEST_PRODUCT_ID);
    if (assertNotNull($product, "getProductById should return product object")) {
        $passed++;
    }
    
    // Test product properties
    if ($product) {
        $total++;
        if (assertEqual($product->product_id, TEST_PRODUCT_ID, "Product ID should match")) {
            $passed++;
        }
        
        $total++;
        if (assertEqual($product->product_name, $productName, "Product name should match")) {
            $passed++;
        }
        
        $total++;
        if (assertEqual($product->description, $description, "Product description should match")) {
            $passed++;
        }
    }
    
    // Test product update
    $total++;
    $newName = 'Updated Product';
    $newDescription = 'Updated description';
    
    if (assertTrue($productManager->updateProduct(TEST_PRODUCT_ID, $newName, $newDescription), "updateProduct should return true")) {
        $passed++;
    }
    
    // Verify update
    $updatedProduct = $productManager->getProductById(TEST_PRODUCT_ID);
    $total++;
    if (assertEqual($updatedProduct->product_name, $newName, "Product name should be updated")) {
        $passed++;
    }
    
    // Test getAllProducts
    $total++;
    $products = $productManager->getAllProducts();
    if (assertIsArray($products, "getAllProducts should return array")) {
        $passed++;
    }
    
    $total++;
    if (assertTrue(count($products) > 0, "getAllProducts should return non-empty array")) {
        $passed++;
    }
    
    // Test product deletion
    $total++;
    if (assertTrue($productManager->deleteProduct(TEST_PRODUCT_ID), "deleteProduct should return true")) {
        $passed++;
    }
    
    // Verify deletion
    $total++;
    if (assertFalse($productManager->getProductById(TEST_PRODUCT_ID), "getProductById should return false after deletion")) {
        $passed++;
    }
    
    echo "Product tests: $passed/$total passed\n";
    
} catch (Exception $e) {
    echo "[ERROR] Product tests failed: " . $e->getMessage() . "\n";
}

echo "\n------------------------------------------\n";

// ==========================================
// TEST 5: ProductSupplier Class and ProductSupplierManager
// ==========================================
echo "[ProductSupplier Class and ProductSupplierManager Test]\n";
$productSupplierManager = new ProductSupplierManager();
$supplierManager = new SupplierManager();
$productManager = new ProductManager();
$passed = 0;
$total = 0;

try {
    // Create test supplier and product first
    $supplierManager->addSupplier(TEST_SUPPLIER_ID, 'Test Supplier', '123 Test St', '555-1234', 'test@example.com');
    $productManager->addProduct(TEST_PRODUCT_ID, 'Test Product', 'Test description');
    
    // Test product offering creation
    $total++;
    $price = 99.99;
    $quantity = 50;
    $status = 'A';
    
    if (assertTrue($productSupplierManager->addProductOffering(TEST_PRODUCT_ID, TEST_SUPPLIER_ID, $price, $quantity, $status), "addProductOffering should return true")) {
        $passed++;
    }
    
    // Test product offering retrieval
    $total++;
    $offerings = $productSupplierManager->getAllProductOfferings();
    if (assertIsArray($offerings, "getAllProductOfferings should return array")) {
        $passed++;
    }
    
    // Test productSupplierLinkExists
    $total++;
    if (assertTrue($productSupplierManager->productSupplierLinkExists(TEST_PRODUCT_ID, TEST_SUPPLIER_ID), "productSupplierLinkExists should return true for existing link")) {
        $passed++;
    }
    
    // Test getProductsBySupplier
    $total++;
    $productsBySupplier = $productSupplierManager->getProductsBySupplier(TEST_SUPPLIER_ID);
    if (assertIsArray($productsBySupplier, "getProductsBySupplier should return array")) {
        $passed++;
    }
    
    $total++;
    if (assertTrue(count($productsBySupplier) > 0, "getProductsBySupplier should return non-empty array")) {
        $passed++;
    }
    
    // Test product offering update
    $total++;
    $newPrice = 149.99;
    $newQuantity = 25;
    $newStatus = 'B';
    
    if (assertTrue($productSupplierManager->addProductOffering(TEST_PRODUCT_ID, TEST_SUPPLIER_ID, $newPrice, $newQuantity, $newStatus), "addProductOffering should update existing offering")) {
        $passed++;
    }
    
    // Verify update
    $updatedOfferings = $productSupplierManager->getAllProductOfferings();
    $found = false;
    foreach ($updatedOfferings as $offering) {
        if ($offering->product_id == TEST_PRODUCT_ID && $offering->supplier_id == TEST_SUPPLIER_ID) {
            $found = true;
            $total++;
            if (assertEqual((float)$offering->price, (float)$newPrice, "Updated price should be correct")) {
                $passed++;
            }
            break;
        }
    }
    
    if (!$found) {
        $total++;
        echo "[FAIL] Updated offering not found\n";
    }
    
    // Test product offering deletion
    $total++;
    if (assertTrue($productSupplierManager->deleteProductOffering(TEST_PRODUCT_ID, TEST_SUPPLIER_ID), "deleteProductOffering should return true")) {
        $passed++;
    }
    
    // Verify deletion
    $total++;
    if (assertFalse($productSupplierManager->productSupplierLinkExists(TEST_PRODUCT_ID, TEST_SUPPLIER_ID), "productSupplierLinkExists should return false after deletion")) {
        $passed++;
    }
    
    // Clean up test data
    $supplierManager->deleteSupplier(TEST_SUPPLIER_ID);
    $productManager->deleteProduct(TEST_PRODUCT_ID);
    
    echo "ProductSupplier tests: $passed/$total passed\n";
    
} catch (Exception $e) {
    echo "[ERROR] ProductSupplier tests failed: " . $e->getMessage() . "\n";
}

echo "\n------------------------------------------\n";

// ==========================================
// TEST 6: Importer Class
// ==========================================
echo "[Importer Class Test]\n";
$importer = new Importer();
$passed = 0;
$total = 0;

try {
    // Test supplier import
    $total++;
    $supplierLines = [
        "1001,Test Supplier 1,123 Test St,555-1234,test1@example.com",
        "1002,Test Supplier 2,456 Test St,555-5678,test2@example.com"
    ];
    
    $supplierResult = $importer->importSuppliers($supplierLines);
    if (assertIsArray($supplierResult, "importSuppliers should return array")) {
        $passed++;
    }
    
    $total++;
    if (assertEqual($supplierResult['success_count'], 2, "importSuppliers should import 2 suppliers successfully")) {
        $passed++;
    }
    
    // Test product import
    $total++;
    $productLines = [
        "2001,Test Product 1,Description 1,10.99,100,A,1001",
        "2002,Test Product 2,Description 2,20.99,200,B,1002"
    ];
    
    $productResult = $importer->importProducts($productLines);
    if (assertIsArray($productResult, "importProducts should return array")) {
        $passed++;
    }
    
    $total++;
    if (assertEqual($productResult['success_count'], 2, "importProducts should import 2 products successfully")) {
        $passed++;
    }
    
    // Test duplicate supplier import (should skip)
    $total++;
    $duplicateSupplierLines = [
        "1001,Duplicate Supplier,123 Duplicate St,555-9999,duplicate@example.com"
    ];
    
    $duplicateResult = $importer->importSuppliers($duplicateSupplierLines);
    if (assertEqual($duplicateResult['skip_count'], 1, "importSuppliers should skip duplicate suppliers")) {
        $passed++;
    }
    
    // Test duplicate product import (should skip)
    $total++;
    $duplicateProductLines = [
        "2001,Duplicate Product,Duplicate Description,30.99,300,C,1001"
    ];
    
    $duplicateProductResult = $importer->importProducts($duplicateProductLines);
    if (assertEqual($duplicateProductResult['skip_count'], 1, "importProducts should skip duplicate products")) {
        $passed++;
    }
    
    // Clean up test data
    $supplierManager = new SupplierManager();
    $productManager = new ProductManager();
    $productSupplierManager = new ProductSupplierManager();
    
    $productSupplierManager->deleteProductOffering(2001, 1001);
    $productSupplierManager->deleteProductOffering(2002, 1002);
    $productManager->deleteProduct(2001);
    $productManager->deleteProduct(2002);
    $supplierManager->deleteSupplier(1001);
    $supplierManager->deleteSupplier(1002);
    
    echo "Importer tests: $passed/$total passed\n";
    
} catch (Exception $e) {
    echo "[ERROR] Importer tests failed: " . $e->getMessage() . "\n";
}

echo "\n==========================================\n";
echo "All class tests complete.\n";
echo "==========================================\n";
?> 