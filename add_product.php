<?php
/**
 * Add Product Page
 * Form to add new products to inventory
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Include database configuration
require_once 'config/database.php';

$error = '';
$success = '';

// Initialize database and get suppliers
initializeDatabase();
$suppliers = getAllSuppliers();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = $_POST['product_id'] ?? '';
    $productName = $_POST['product_name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? '';
    $quantity = $_POST['quantity'] ?? '';
    $status = $_POST['status'] ?? '';
    $supplierId = $_POST['supplier_id'] ?? '';
    
    // Basic validation
    if (empty($productId) || empty($productName) || empty($price) || empty($quantity) || empty($status) || empty($supplierId)) {
        $error = 'Please fill in all required fields.';
    } elseif (!is_numeric($productId) || !is_numeric($price) || !is_numeric($quantity) || !is_numeric($supplierId)) {
        $error = 'Product ID, Price, Quantity, and Supplier ID must be numbers.';
    } elseif (productSupplierLinkExists($productId, $supplierId)) {
        $error = 'This product from this supplier already exists in the inventory.';
    } else {
        // Add the product offering
        if (addProductOffering($productId, $productName, $description, $price, $quantity, $status, $supplierId)) {
            $success = 'Product offering added successfully!';
        } else {
            $error = 'Failed to add product offering. Please try again.';
        }
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}

$username = $_SESSION['username'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - CP476 Inventory Manager</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .add-product-container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            margin-top: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #004080;
            box-shadow: 0 0 0 2px rgba(0, 64, 128, 0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .required {
            color: #dc3545;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .back-btn {
            background: #6c757d;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        .back-btn:hover {
            background: #5a6268;
        }
        
        @media (max-width: 768px) {
            .add-product-container {
                margin: 1rem;
                padding: 1.5rem;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <header class="navbar">
        <div class="container">
            <a href="index.php" class="home-link">CP476 Inventory Manager</a>
            <div style="margin-left: auto; display: flex; align-items: center; gap: 1rem;">
                <span style="color: white;">Welcome, <?= htmlspecialchars($username) ?></span>
                <a href="?logout=1" style="color: white; text-decoration: none; padding: 0.5rem 1rem; background-color: rgba(255,255,255,0.2); border-radius: 4px;">Logout</a>
            </div>
        </div>
    </header>

    <div class="container" style="margin-top: 3rem;">
        <a href="inventory.php" class="back-btn">← Back to Inventory</a>
        
        <div class="add-product-container">
            <h1>Add New Product</h1>
            <p>Fill in the details below to add a new product to your inventory.</p>
            
            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="product_id">Product ID <span class="required">*</span></label>
                    <input type="number" id="product_id" name="product_id" value="<?= htmlspecialchars($_POST['product_id'] ?? '') ?>" required min="1">
                </div>
                
                <div class="form-group">
                    <label for="product_name">Product Name <span class="required">*</span></label>
                    <input type="text" id="product_name" name="product_name" value="<?= htmlspecialchars($_POST['product_name'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="price">Price <span class="required">*</span></label>
                    <input type="number" id="price" name="price" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" required min="0.01" step="0.01">
                </div>
                
                <div class="form-group">
                    <label for="quantity">Quantity <span class="required">*</span></label>
                    <input type="number" id="quantity" name="quantity" value="<?= htmlspecialchars($_POST['quantity'] ?? '') ?>" required min="0">
                </div>
                
                <div class="form-group">
                    <label for="status">Status <span class="required">*</span></label>
                    <select id="status" name="status" required>
                        <option value="">Select Status</option>
                        <option value="A" <?= ($_POST['status'] ?? '') === 'A' ? 'selected' : '' ?>>A - Active</option>
                        <option value="B" <?= ($_POST['status'] ?? '') === 'B' ? 'selected' : '' ?>>B - Pending</option>
                        <option value="C" <?= ($_POST['status'] ?? '') === 'C' ? 'selected' : '' ?>>C - Discontinued</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="supplier_id">Supplier <span class="required">*</span></label>
                    <select id="supplier_id" name="supplier_id" required>
                        <option value="">Select Supplier</option>
                        <?php if ($suppliers): ?>
                            <?php foreach ($suppliers as $supplier): ?>
                                <option value="<?= $supplier['supplier_id'] ?>" <?= ($_POST['supplier_id'] ?? '') == $supplier['supplier_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($supplier['supplier_name']) ?> (ID: <?= $supplier['supplier_id'] ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Add Product</button>
                    <a href="inventory.php" class="btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 