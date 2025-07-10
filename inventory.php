<?php
/**
 * View Inventory Page
 * Displays all products with supplier information
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Include database configuration
require_once 'config/database.php';

// Initialize database and get products
initializeDatabase();
$products = getAllProducts();

// Handle search
$searchTerm = '';
if (isset($_GET['search'])) {
    $searchTerm = trim($_GET['search']);
    if ($searchTerm !== '') {
        $products = array_filter($products, function($product) use ($searchTerm) {
            return (
                stripos($product['product_name'], $searchTerm) !== false ||
                stripos($product['supplier_name'], $searchTerm) !== false ||
                stripos((string)$product['product_id'], $searchTerm) !== false
            );
        });
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}

// Handle delete product offering
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['delete_product_id'], $_POST['delete_supplier_id'])
) {
    $productId = intval($_POST['delete_product_id']);
    $supplierId = intval($_POST['delete_supplier_id']);
    deleteProductOffering($productId, $supplierId);
    // Refresh to avoid resubmission
    header('Location: inventory.php');
    exit();
}

$username = $_SESSION['username'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Inventory - CP476 Inventory Manager</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .inventory-container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            margin-top: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .inventory-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .inventory-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .inventory-table th,
        .inventory-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .inventory-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .inventory-table tr:hover {
            background-color: #f5f5f5;
        }
        
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-a {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-b {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-c {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .price {
            font-weight: 600;
            color: #28a745;
        }
        
        .quantity {
            font-weight: 600;
        }
        
        .low-stock {
            color: #dc3545;
        }
        
        .no-data {
            text-align: center;
            padding: 3rem;
            color: #666;
            font-style: italic;
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
            .inventory-table {
                font-size: 0.9rem;
            }
            
            .inventory-table th,
            .inventory-table td {
                padding: 0.5rem 0.25rem;
            }
            
            .inventory-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
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
        <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
        
        <div class="inventory-container">
            <div class="inventory-header">
                <div>
                    <h1>Inventory Management</h1>
                    <p>View and manage your product inventory</p>
                </div>
                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.5rem;">
                    <form method="GET" style="display: flex; gap: 0.5rem; align-items: center;">
                        <input type="text" name="search" placeholder="Search by product, supplier, or ID" value="<?= htmlspecialchars($searchTerm) ?>" style="padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px;">
                        <button type="submit" style="background: #007bff; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer;">Search</button>
                    </form>
                    <a href="add_product.php" style="background: #007bff; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block;">Add New Product</a>
                </div>
            </div>
            
            <?php if ($products && count($products) > 0): ?>
                <table class="inventory-table">
                    <thead>
                        <tr>
                            <th>Product ID</th>
                            <th>Product Name</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Status</th>
                            <th>Supplier</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($product['product_id']) ?></strong></td>
                                <td><?= htmlspecialchars($product['product_name']) ?></td>
                                <td><?= htmlspecialchars($product['description']) ?></td>
                                <td class="price">$<?= number_format($product['price'], 2) ?></td>
                                <td class="quantity <?= $product['quantity'] < 20 ? 'low-stock' : '' ?>">
                                    <?= htmlspecialchars($product['quantity']) ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= strtolower($product['status']) ?>">
                                        <?= htmlspecialchars($product['status']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($product['supplier_name']) ?></td>
                                <td>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this product offering?');">
                                        <input type="hidden" name="delete_product_id" value="<?= htmlspecialchars($product['product_id']) ?>">
                                        <input type="hidden" name="delete_supplier_id" value="<?= htmlspecialchars($product['supplier_id']) ?>">
                                        <button type="submit" style="background: #dc3545; color: white; border: none; padding: 0.4rem 0.8rem; border-radius: 4px; cursor: pointer;">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
            <?php else: ?>
                <div class="no-data">
                    <h3>No Products Found</h3>
                    <p>Your inventory is empty. Add some products to get started!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 