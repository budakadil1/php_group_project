<?php
/**
 * Database Configuration
 * MySQL connection with username/password authentication
 */

// Config
define('DB_HOST', 'localhost');
define('DB_NAME', 'cp476_inventory');
define('DB_USER', 'root');   
define('DB_PASS', '4642');     
define('DB_CHARSET', 'utf8mb4');

/**
 * Get conn
 * @return PDO|null
 */
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return null;
    }
}

/**
 * Init tables
 * Creates users and supplier tables if they don't exist
 */
function initializeDatabase() {
    $pdo = getDBConnection();
    if (!$pdo) {
        return false;
    }
    
    try {
        // Create users table
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            email VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $pdo->exec($sql);
        
        // Create supplier table
        $sql = "CREATE TABLE IF NOT EXISTS supplier (
            supplier_id INT PRIMARY KEY,
            supplier_name VARCHAR(100) NOT NULL,
            address VARCHAR(255) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            email VARCHAR(100) NOT NULL
        )";
        
        $pdo->exec($sql);
        
        // Create product table
        $sql = "CREATE TABLE IF NOT EXISTS product (
            product_id INT PRIMARY KEY,
            product_name VARCHAR(100) NOT NULL,
            description TEXT
        )";
        
        $pdo->exec($sql);
        
        // Create product_supplier junction table
        $sql = "CREATE TABLE IF NOT EXISTS product_supplier (
            product_id INT,
            supplier_id INT,
            price DECIMAL(10,2) NOT NULL,
            quantity INT NOT NULL DEFAULT 0,
            status CHAR(1) NOT NULL,
            PRIMARY KEY (product_id, supplier_id),
            FOREIGN KEY (product_id) REFERENCES product(product_id) ON DELETE CASCADE,
            FOREIGN KEY (supplier_id) REFERENCES supplier(supplier_id) ON DELETE CASCADE
        )";
        
        $pdo->exec($sql);
        
        return true;
    } catch (PDOException $e) {
        error_log("Database initialization failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Hash 
 * @param string $password
 * @return string
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify 
 * @param string $password
 * @param string $hash
 * @return bool
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Create new user
 * @param string $username
 * @param string $password
 * @param string $email
 * @return bool
 */
function createUser($username, $password, $email = null) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return false;
    }
    
    try {
        $hashedPassword = hashPassword($password);
        
        $sql = "INSERT INTO users (username, password_hash, email) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username, $hashedPassword, $email]);
        
        return true;
    } catch (PDOException $e) {
        error_log("User creation failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Authenticate user
 * @param string $username
 * @param string $password
 * @return array|false
 */
function authenticateUser($username, $password) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return false;
    }
    
    try {
        $sql = "SELECT id, username, password_hash, email FROM users WHERE username = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && verifyPassword($password, $user['password_hash'])) {
            return $user;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("User authentication failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Username exists?
 * @param string $username
 * @return bool
 */
function usernameExists($username) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return false;
    }
    
    try {
        $sql = "SELECT COUNT(*) FROM users WHERE username = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username]);
        
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("Username check failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all suppliers
 * @return array|false
 */
function getAllSuppliers() {
    $pdo = getDBConnection();
    if (!$pdo) {
        return false;
    }
    
    try {
        $sql = "SELECT * FROM supplier ORDER BY supplier_name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to get suppliers: " . $e->getMessage());
        return false;
    }
}

/**
 * Get supplier by ID
 * @param int $supplierId
 * @return array|false
 */
function getSupplierById($supplierId) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return false;
    }
    
    try {
        $sql = "SELECT * FROM supplier WHERE supplier_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$supplierId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Failed to get supplier: " . $e->getMessage());
        return false;
    }
}

/**
 * Add new supplier
 * @param int $supplierId
 * @param string $supplierName
 * @param string $address
 * @param string $phone
 * @param string $email
 * @return bool
 */
function addSupplier($supplierId, $supplierName, $address, $phone, $email) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return false;
    }
    
    try {
        $sql = "INSERT INTO supplier (supplier_id, supplier_name, address, phone, email) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$supplierId, $supplierName, $address, $phone, $email]);
        return true;
    } catch (PDOException $e) {
        error_log("Failed to add supplier: " . $e->getMessage());
        return false;
    }
}

/**
 * Update supplier
 * @param int $supplierId
 * @param string $supplierName
 * @param string $address
 * @param string $phone
 * @param string $email
 * @return bool
 */
function updateSupplier($supplierId, $supplierName, $address, $phone, $email) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return false;
    }
    
    try {
        $sql = "UPDATE supplier SET supplier_name = ?, address = ?, phone = ?, email = ? WHERE supplier_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$supplierName, $address, $phone, $email, $supplierId]);
        return true;
    } catch (PDOException $e) {
        error_log("Failed to update supplier: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete supplier
 * @param int $supplierId
 * @return bool
 */
function deleteSupplier($supplierId) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return false;
    }
    
    try {
        $sql = "DELETE FROM supplier WHERE supplier_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$supplierId]);
        return true;
    } catch (PDOException $e) {
        error_log("Failed to delete supplier: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if supplier exists
 * @param int $supplierId
 * @return bool
 */
function supplierExists($supplierId) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return false;
    }
    
    try {
        $sql = "SELECT COUNT(*) FROM supplier WHERE supplier_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$supplierId]);
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("Failed to check supplier existence: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all products
 * @return array|false
 */
function getAllProducts() {
    $pdo = getDBConnection();
    if (!$pdo) {
        return false;
    }

    try {
        $sql = "SELECT 
                    p.product_id, 
                    p.product_name, 
                    p.description,
                    ps.price,
                    ps.quantity,
                    ps.status,
                    s.supplier_id,
                    s.supplier_name
                FROM product p
                JOIN product_supplier ps ON p.product_id = ps.product_id
                JOIN supplier s ON ps.supplier_id = s.supplier_id
                ORDER BY p.product_name, s.supplier_name";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to get products: " . $e->getMessage());
        return false;
    }
}

/**
 * Get product by ID
 * @param int $productId
 * @return array|false
 */
function getProductById($productId) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return false;
    }
    
    try {
        $sql = "SELECT p.*, s.supplier_name FROM product p 
                LEFT JOIN supplier s ON p.supplier_id = s.supplier_id 
                WHERE p.product_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$productId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Failed to get product: " . $e->getMessage());
        return false;
    }
}

/**
 * Add or update a product offering (product-supplier link).
 * Creates the product if it doesn't exist.
 * @param int $productId
 * @param string $productName
 * @param string $description
 * @param float $price
 * @param int $quantity
 * @param string $status
 * @param int $supplierId
 * @return bool
 */
function addProductOffering($productId, $productName, $description, $price, $quantity, $status, $supplierId) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return false;
    }

    try {
        $pdo->beginTransaction();

        // Step 1: Check if product exists, if not create it.
        $sql = "INSERT INTO product (product_id, product_name, description) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE product_name = VALUES(product_name), description = VALUES(description)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$productId, $productName, $description]);
        
        // Step 2: Insert or update the product-supplier link.
        $sql = "INSERT INTO product_supplier (product_id, supplier_id, price, quantity, status) 
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE price = VALUES(price), quantity = VALUES(quantity), status = VALUES(status)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$productId, $supplierId, $price, $quantity, $status]);

        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Failed to add product offering: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete a specific product offering from a supplier.
 * @param int $productId
 * @param int $supplierId
 * @return bool
 */
function deleteProductOffering($productId, $supplierId) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return false;
    }

    try {
        $sql = "DELETE FROM product_supplier WHERE product_id = ? AND supplier_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$productId, $supplierId]);
        
        // Optional: Check if the product has any other suppliers left. If not, delete the product itself.
        $sql = "SELECT COUNT(*) FROM product_supplier WHERE product_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$productId]);
        if ($stmt->fetchColumn() == 0) {
            $sql = "DELETE FROM product WHERE product_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$productId]);
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Failed to delete product offering: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if product exists
 * @param int $productId
 * @return bool
 */
function productSupplierLinkExists($productId, $supplierId) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return false;
    }
    
    try {
        $sql = "SELECT COUNT(*) FROM product_supplier WHERE product_id = ? AND supplier_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$productId, $supplierId]);
        
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("Product-supplier link check failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get products by supplier
 * @param int $supplierId
 * @return array|false
 */
function getProductsBySupplier($supplierId) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return false;
    }
    
    try {
        $sql = "SELECT 
                    p.product_id, 
                    p.product_name, 
                    p.description,
                    ps.price,
                    ps.quantity,
                    ps.status
                FROM product p
                JOIN product_supplier ps ON p.product_id = ps.product_id
                WHERE ps.supplier_id = ?
                ORDER BY p.product_name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$supplierId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to get products by supplier: " . $e->getMessage());
        return false;
    }
}

/**
 * Get products by status
 * @param string $status
 * @return array|false
 */
function getProductsByStatus($status) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return false;
    }

    try {
        $sql = "SELECT 
                    p.product_id, 
                    p.product_name, 
                    p.description,
                    ps.price,
                    ps.quantity,
                    ps.status,
                    s.supplier_name
                FROM product p
                JOIN product_supplier ps ON p.product_id = ps.product_id
                JOIN supplier s ON ps.supplier_id = s.supplier_id
                WHERE ps.status = ?
                ORDER BY p.product_name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$status]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to get products by status: " . $e->getMessage());
        return false;
    }
}
?> 