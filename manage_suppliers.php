<?php
session_start();
require_once __DIR__ . '/classes/Supplier.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$supplierManager = new SupplierManager();
$error = '';
$success = '';

// Handle delete supplier
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['delete_supplier_id'])
) {
    $supplierId = intval($_POST['delete_supplier_id']);
    if ($supplierManager->deleteSupplier($supplierId)) {
        $success = 'Supplier deleted successfully!';
    } else {
        $error = 'Failed to delete supplier.';
    }
    header('Location: manage_suppliers.php');
    exit();
}

// Handle edit supplier
$editSupplier = null;
if (isset($_GET['edit'])) {
    $supplierId = intval($_GET['edit']);
    $editSupplier = $supplierManager->getSupplierById($supplierId);
    if (!$editSupplier) {
        $error = 'Supplier not found.';
    }
}

// Handle update supplier
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_supplier_id'], $_POST['supplier_name'], $_POST['address'], $_POST['phone'], $_POST['email'])
) {
    $supplierId = intval($_POST['update_supplier_id']);
    $supplierName = trim($_POST['supplier_name']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    
    // Basic validation
    if (empty($supplierName) || empty($address) || empty($phone) || empty($email)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        if ($supplierManager->updateSupplier($supplierId, $supplierName, $address, $phone, $email)) {
            $success = 'Supplier updated successfully!';
            header('Location: manage_suppliers.php');
            exit();
        } else {
            $error = 'Failed to update supplier.';
        }
    }
}

// Add Supplier form logic
$showAddForm = isset($_POST['show_add_form']) || isset($_POST['add_supplier']);
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['add_supplier'], $_POST['new_supplier_name'], $_POST['new_address'], $_POST['new_phone'], $_POST['new_email'])
) {
    $newSupplierName = trim($_POST['new_supplier_name']);
    $newAddress = trim($_POST['new_address']);
    $newPhone = trim($_POST['new_phone']);
    $newEmail = trim($_POST['new_email']);
    $newSupplierId = time(); // Simple unique ID, replace with better logic if needed
    if (empty($newSupplierName) || empty($newAddress) || empty($newPhone) || empty($newEmail)) {
        $error = 'All fields are required to add a supplier.';
        $showAddForm = true;
    } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
        $showAddForm = true;
    } else {
        if ($supplierManager->addSupplier($newSupplierId, $newSupplierName, $newAddress, $newPhone, $newEmail)) {
            $success = 'Supplier added successfully!';
            header('Location: manage_suppliers.php');
            exit();
        } else {
            $error = 'Failed to add supplier.';
            $showAddForm = true;
        }
    }
}

// Handle search
$searchTerm = '';
$suppliers = $supplierManager->getAllSuppliers();
if (isset($_GET['search'])) {
    $searchTerm = trim($_GET['search']);
    if ($searchTerm !== '') {
        $suppliers = array_filter($suppliers, function($supplier) use ($searchTerm) {
            return stripos($supplier->supplier_name, $searchTerm) !== false;
        });
    }
}

$username = $_SESSION['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Suppliers - CP476 Inventory Manager</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            padding: 0 1rem;
        }
        
        .suppliers-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 1rem; 
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .suppliers-table th, 
        .suppliers-table td { 
            padding: 0.75rem; 
            border-bottom: 1px solid #ddd; 
            text-align: left; 
        }
        
        .suppliers-table th { 
            background: #f8f9fa; 
            font-weight: 600;
            color: #333;
        }
        
        .suppliers-table tr:hover {
            background-color: #f5f5f5;
        }
        
        .action-btn { 
            padding: 0.4rem 0.8rem; 
            border-radius: 4px; 
            border: none; 
            cursor: pointer; 
            text-decoration: none;
            display: inline-block;
            margin-right: 0.5rem;
            font-size: 0.9rem;
        }
        
        .edit-btn { 
            background: #ffc107; 
            color: #333; 
        }
        
        .edit-btn:hover {
            background: #e0a800;
        }
        
        .delete-btn { 
            background: #dc3545; 
            color: white; 
        }
        
        .delete-btn:hover {
            background: #c82333;
        }
        
        .edit-form { 
            background: #f8f9fa; 
            padding: 1.5rem; 
            border-radius: 6px; 
            margin-top: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .edit-form label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .edit-form input {
            width: 100%;
            padding: 0.75rem;
            margin-bottom: 1rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .edit-form input:focus {
            outline: none;
            border-color: #004080;
            box-shadow: 0 0 0 2px rgba(0, 64, 128, 0.1);
        }
        
        .search-form {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .search-form input {
            padding: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-right: 0.5rem;
            min-width: 200px;
        }
        
        .search-form button {
            background: #007bff;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .search-form button:hover {
            background: #0056b3;
        }
        
        .page-header {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .page-header h1 {
            margin: 0;
            color: #333;
        }
        
        .error {
            color: #d32f2f;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            padding: 0.75rem;
            background-color: #ffebee;
            border-radius: 4px;
            border-left: 4px solid #d32f2f;
        }
        
        .success {
            color: #388e3c;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            padding: 0.75rem;
            background-color: #e8f5e8;
            border-radius: 4px;
            border-left: 4px solid #388e3c;
        }
        
        @media (max-width: 768px) {
            .suppliers-table {
                font-size: 0.9rem;
            }
            
            .suppliers-table th,
            .suppliers-table td {
                padding: 0.5rem 0.25rem;
            }
            
            .action-btn {
                padding: 0.3rem 0.6rem;
                font-size: 0.8rem;
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
                <a href="dashboard.php" style="color: white; text-decoration: none; padding: 0.5rem 1rem; background-color: rgba(255,255,255,0.2); border-radius: 4px;">Dashboard</a>
                <a href="?logout=1" style="color: white; text-decoration: none; padding: 0.5rem 1rem; background-color: rgba(255,255,255,0.2); border-radius: 4px;">Logout</a>
            </div>
        </div>
    </header>
    
    <div class="container" style="margin-top: 3rem;">
        <div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h1>Manage Suppliers</h1>
            <form method="POST" style="margin:0;">
                <input type="hidden" name="show_add_form" value="1">
                <button type="submit" style="background: #007bff; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 1rem;">Add Supplier</button>
            </form>
        </div>
        <?php if ($showAddForm): ?>
            <div class="edit-form" style="margin-bottom:2rem;">
                <h2>Add Supplier</h2>
                <form method="POST">
                    <input type="hidden" name="add_supplier" value="1">
                    <label>Name: <input type="text" name="new_supplier_name" value="<?= htmlspecialchars($_POST['new_supplier_name'] ?? '') ?>" required></label>
                    <label>Address: <input type="text" name="new_address" value="<?= htmlspecialchars($_POST['new_address'] ?? '') ?>" required></label>
                    <label>Phone: <input type="text" name="new_phone" value="<?= htmlspecialchars($_POST['new_phone'] ?? '') ?>" required></label>
                    <label>Email: <input type="email" name="new_email" value="<?= htmlspecialchars($_POST['new_email'] ?? '') ?>" required></label>
                    <div style="margin-top: 1rem;">
                        <button type="submit" style="background: #007bff; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; margin-right: 0.5rem;">Add Supplier</button>
                        <a href="manage_suppliers.php" style="background: #6c757d; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; text-decoration: none;">Cancel</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <!-- Edit Supplier Form -->
        <?php if ($editSupplier): ?>
            <div class="edit-form">
                <h2>Edit Supplier</h2>
                <form method="POST">
                    <input type="hidden" name="update_supplier_id" value="<?= htmlspecialchars($editSupplier->supplier_id) ?>">
                    <label>Name: <input type="text" name="supplier_name" value="<?= htmlspecialchars($editSupplier->supplier_name) ?>" required></label>
                    <label>Address: <input type="text" name="address" value="<?= htmlspecialchars($editSupplier->address) ?>" required></label>
                    <label>Phone: <input type="text" name="phone" value="<?= htmlspecialchars($editSupplier->phone) ?>" required></label>
                    <label>Email: <input type="email" name="email" value="<?= htmlspecialchars($editSupplier->email) ?>" required></label>
                    <div style="margin-top: 1rem;">
                        <button type="submit" style="background: #28a745; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; margin-right: 0.5rem;">Update Supplier</button>
                        <a href="manage_suppliers.php" style="background: #6c757d; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; text-decoration: none;">Cancel</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
        
        <div class="search-form">
            <form method="GET" style="display: flex; gap: 0.5rem; align-items: center;">
                <input type="text" name="search" placeholder="Search by supplier name" value="<?= htmlspecialchars($searchTerm) ?>">
                <button type="submit">Search</button>
            </form>
        </div>
        
        <!-- Supplier Table -->
        <table class="suppliers-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Address</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($suppliers)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2rem; color: #666;">No suppliers found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($suppliers as $supplier): ?>
                        <tr>
                            <td><?= htmlspecialchars($supplier->supplier_id) ?></td>
                            <td><?= htmlspecialchars($supplier->supplier_name) ?></td>
                            <td><?= htmlspecialchars($supplier->address) ?></td>
                            <td><?= htmlspecialchars($supplier->phone) ?></td>
                            <td><?= htmlspecialchars($supplier->email) ?></td>
                            <td>
                                <a href="manage_suppliers.php?edit=<?= htmlspecialchars($supplier->supplier_id) ?>" class="action-btn edit-btn">Edit</a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this supplier?');">
                                    <input type="hidden" name="delete_supplier_id" value="<?= htmlspecialchars($supplier->supplier_id) ?>">
                                    <button type="submit" class="action-btn delete-btn">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html> 