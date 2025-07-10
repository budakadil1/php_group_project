<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';

// Handle delete supplier
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['delete_supplier_id'])
) {
    $supplierId = intval($_POST['delete_supplier_id']);
    deleteSupplier($supplierId);
    header('Location: manage_suppliers.php');
    exit();
}

// Handle edit supplier
$editSupplier = null;
if (isset($_GET['edit'])) {
    $editSupplier = getSupplierById(intval($_GET['edit']));
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
    updateSupplier($supplierId, $supplierName, $address, $phone, $email);
    header('Location: manage_suppliers.php');
    exit();
}

// Handle search
$searchTerm = '';
$suppliers = getAllSuppliers();
if (isset($_GET['search'])) {
    $searchTerm = trim($_GET['search']);
    if ($searchTerm !== '') {
        $suppliers = array_filter($suppliers, function($supplier) use ($searchTerm) {
            return stripos($supplier['supplier_name'], $searchTerm) !== false;
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
        .container { max-width: 900px; margin: 0 auto; }
        .suppliers-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .suppliers-table th, .suppliers-table td { padding: 0.75rem; border-bottom: 1px solid #ddd; text-align: left; }
        .suppliers-table th { background: #f8f9fa; }
        .action-btn { padding: 0.4rem 0.8rem; border-radius: 4px; border: none; cursor: pointer; }
        .edit-btn { background: #ffc107; color: #333; }
        .delete-btn { background: #dc3545; color: white; }
        .edit-form { background: #f8f9fa; padding: 1rem; border-radius: 6px; margin-bottom: 1rem; }
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
        <h1>Manage Suppliers</h1>
        <form method="GET" style="margin-bottom: 1rem; display: flex; gap: 0.5rem;">
            <input type="text" name="search" placeholder="Search by supplier name" value="<?= htmlspecialchars($searchTerm) ?>" style="padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px;">
            <button type="submit" style="background: #007bff; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer;">Search</button>
        </form>

        <?php if ($editSupplier): ?>
            <form method="POST" class="edit-form">
                <h2>Edit Supplier</h2>
                <input type="hidden" name="update_supplier_id" value="<?= htmlspecialchars($editSupplier['supplier_id']) ?>">
                <div style="margin-bottom: 0.5rem;">
                    <label>Supplier Name:</label><br>
                    <input type="text" name="supplier_name" value="<?= htmlspecialchars($editSupplier['supplier_name']) ?>" required style="width: 100%; padding: 0.5rem;">
                </div>
                <div style="margin-bottom: 0.5rem;">
                    <label>Address:</label><br>
                    <input type="text" name="address" value="<?= htmlspecialchars($editSupplier['address']) ?>" required style="width: 100%; padding: 0.5rem;">
                </div>
                <div style="margin-bottom: 0.5rem;">
                    <label>Phone:</label><br>
                    <input type="text" name="phone" value="<?= htmlspecialchars($editSupplier['phone']) ?>" required style="width: 100%; padding: 0.5rem;">
                </div>
                <div style="margin-bottom: 0.5rem;">
                    <label>Email:</label><br>
                    <input type="email" name="email" value="<?= htmlspecialchars($editSupplier['email']) ?>" required style="width: 100%; padding: 0.5rem;">
                </div>
                <button type="submit" style="background: #28a745; color: white; border: none; padding: 0.5rem 1.5rem; border-radius: 4px; cursor: pointer;">Update Supplier</button>
                <a href="manage_suppliers.php" style="margin-left: 1rem; color: #333; text-decoration: underline;">Cancel</a>
            </form>
        <?php endif; ?>

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
                <?php foreach ($suppliers as $supplier): ?>
                    <tr>
                        <td><?= htmlspecialchars($supplier['supplier_id']) ?></td>
                        <td><?= htmlspecialchars($supplier['supplier_name']) ?></td>
                        <td><?= htmlspecialchars($supplier['address']) ?></td>
                        <td><?= htmlspecialchars($supplier['phone']) ?></td>
                        <td><?= htmlspecialchars($supplier['email']) ?></td>
                        <td>
                            <a href="manage_suppliers.php?edit=<?= htmlspecialchars($supplier['supplier_id']) ?>" class="action-btn edit-btn">Edit</a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this supplier?');">
                                <input type="hidden" name="delete_supplier_id" value="<?= htmlspecialchars($supplier['supplier_id']) ?>">
                                <button type="submit" class="action-btn delete-btn">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html> 