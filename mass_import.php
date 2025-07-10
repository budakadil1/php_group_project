<?php
/**
 * Mass Import Page
 * Import products or suppliers from file
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
$importResults = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $importType = $_POST['import_type'] ?? '';
    $uploadedFile = $_FILES['import_file'] ?? null;
    
    // Basic validation
    if (empty($importType)) {
        $error = 'Please select an import type.';
    } elseif (!$uploadedFile || $uploadedFile['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please select a valid file to upload.';
    } else {
        // Process the file
        $importResults = processImportFile($uploadedFile, $importType);
        
        if (isset($importResults['error'])) {
            $error = $importResults['error'];
        } else {
            $success = "Import completed! {$importResults['success_count']} records imported successfully. {$importResults['skip_count']} records skipped. {$importResults['error_count']} records failed.";
        }
    }
}

/**
 * Process the uploaded file
 * @param array $file
 * @param string $type
 * @return array
 */
function processImportFile($file, $type) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return ['error' => 'Database connection failed.'];
    }
    
    // Initialize database
    initializeDatabase();
    
    $successCount = 0;
    $errorCount = 0;
    $skipCount = 0;
    $errors = [];
    $skipped = [];
    
    // Check file size (5MB limit)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['error' => 'File size exceeds 5MB limit.'];
    }
    
    // Read file content
    $content = file_get_contents($file['tmp_name']);
    if ($content === false) {
        return ['error' => 'Failed to read uploaded file.'];
    }
    
    // Split into lines
    $lines = explode("\n", trim($content));
    $lineNumber = 0;
    
    foreach ($lines as $line) {
        $lineNumber++;
        $line = trim($line);
        
        // Skip empty lines
        if (empty($line)) {
            continue;
        }
        
        // Parse line based on type
        if ($type === 'product') {
            $result = processProductLine($line, $lineNumber, $pdo);
        } else {
            $result = processSupplierLine($line, $lineNumber, $pdo);
        }
        
        if ($result['success'] === true && empty($result['skipped'])) {
            $successCount++;
        } elseif (!empty($result['skipped'])) {
            $skipCount++;
            $skipped[] = "Line {$lineNumber}: {$result['skip_reason']}";
        } else {
            $errorCount++;
            $errors[] = "Line {$lineNumber}: {$result['error']}";
        }
    }
    
    return [
        'success_count' => $successCount,
        'skip_count' => $skipCount,
        'error_count' => $errorCount,
        'errors' => $errors,
        'skipped' => $skipped
    ];
}

/**
 * Process a single product line
 * @param string $line
 * @param int $lineNumber
 * @param PDO $pdo
 * @return array
 */
function processProductLine($line, $lineNumber, $pdo) {
    // Split by comma and trim whitespace
    $fields = array_map('trim', explode(',', $line));
    
    // Validate field count (7 fields: product_id, product_name, description, price, quantity, status, supplier_id)
    if (count($fields) !== 7) {
        return ['success' => false, 'error' => 'Invalid field count. Expected 7 fields.'];
    }
    
    list($productId, $productName, $description, $price, $quantity, $status, $supplierId) = $fields;
    
    // Validate data
    if (!is_numeric($productId) || $productId <= 0) return ['success' => false, 'error' => 'Invalid product ID.'];
    if (empty($productName)) return ['success' => false, 'error' => 'Product name is required.'];
    if (!is_numeric($price) || $price < 0) return ['success' => false, 'error' => 'Invalid price.'];
    if (!is_numeric($quantity) || $quantity < 0) return ['success' => false, 'error' => 'Invalid quantity.'];
    if (!in_array($status, ['A', 'B', 'C'])) return ['success' => false, 'error' => 'Invalid status. Must be A, B, or C.'];
    if (!is_numeric($supplierId) || $supplierId <= 0) return ['success' => false, 'error' => 'Invalid supplier ID.'];
    
    // Check if supplier exists
    if (!supplierExists($supplierId)) {
        return ['success' => false, 'error' => "Supplier ID {$supplierId} does not exist."];
    }
    
    // Check if this specific product-supplier link already exists
    if (productSupplierLinkExists($productId, $supplierId)) {
        return ['success' => true, 'skipped' => true, 'skip_reason' => "Product {$productId} from supplier {$supplierId} already exists."];
    }
    
    // Add the product offering
    if (addProductOffering($productId, $productName, $description, $price, $quantity, $status, $supplierId)) {
        return ['success' => true];
    } else {
        return ['success' => false, 'error' => 'Database error while adding product offering.'];
    }
}

/**
 * Process a single supplier line
 * @param string $line
 * @param int $lineNumber
 * @param PDO $pdo
 * @return array
 */
function processSupplierLine($line, $lineNumber, $pdo) {
    // Split by comma and trim whitespace
    $fields = array_map('trim', explode(',', $line));
    
    // Validate field count (5 fields: supplier_id, supplier_name, address, phone, email)
    if (count($fields) !== 5) {
        return ['success' => false, 'error' => 'Invalid field count. Expected 5 fields.'];
    }
    
    $supplierId = $fields[0];
    $supplierName = $fields[1];
    $address = $fields[2];
    $phone = $fields[3];
    $email = $fields[4];
    
    // Validate data
    if (!is_numeric($supplierId) || $supplierId <= 0) {
        return ['success' => false, 'error' => 'Invalid supplier ID.'];
    }
    
    if (empty($supplierName)) {
        return ['success' => false, 'error' => 'Supplier name is required.'];
    }
    
    if (empty($address)) {
        return ['success' => false, 'error' => 'Address is required.'];
    }
    
    if (empty($phone)) {
        return ['success' => false, 'error' => 'Phone is required.'];
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Invalid email address.'];
    }
    
    // Check if supplier already exists
    if (supplierExists($supplierId)) {
        return ['success' => true, 'skipped' => true, 'skip_reason' => 'Supplier ID already exists'];
    }
    
    // Add the supplier
    try {
        $sql = "INSERT INTO supplier (supplier_id, supplier_name, address, phone, email) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$supplierId, $supplierName, $address, $phone, $email]);
        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
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
    <title>Mass Import - CP476 Inventory Manager</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .import-container {
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
        
        .form-group select,
        .form-group input[type="file"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-group select:focus,
        .form-group input[type="file"]:focus {
            outline: none;
            border-color: #004080;
            box-shadow: 0 0 0 2px rgba(0, 64, 128, 0.1);
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
            background: #ffc107;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            background: #e0a800;
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
        
        .file-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
            margin-top: 1rem;
            border-left: 4px solid #ffc107;
        }
        
        .file-info h4 {
            margin: 0 0 0.5rem 0;
            color: #333;
        }
        
        .file-info ul {
            margin: 0;
            padding-left: 1.5rem;
        }
        
        .file-info li {
            margin-bottom: 0.25rem;
        }
        
        .error-details {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .error-details h4 {
            margin: 0 0 0.5rem 0;
            color: #856404;
        }
        
        .error-list {
            max-height: 200px;
            overflow-y: auto;
        }
        
        .error-item {
            background: #f8d7da;
            color: #721c24;
            padding: 0.5rem;
            margin-bottom: 0.25rem;
            border-radius: 3px;
            font-size: 14px;
            border-left: 3px solid #dc3545;
        }
        
        .skip-details {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .skip-details h4 {
            margin: 0 0 0.5rem 0;
            color: #155724;
        }
        
        .skip-list {
            margin: 0;
            padding-left: 1.5rem;
        }
        
        .skip-item {
            margin-bottom: 0.25rem;
        }
        
        @media (max-width: 768px) {
            .import-container {
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
        <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
        
        <div class="import-container">
            <h1>Mass Import</h1>
            <p>Import products or suppliers from a text or CSV file.</p>
            
            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success"><?= htmlspecialchars($success) ?></div>
                <?php if (!empty($importResults['errors'])): ?>
                    <div class="error-details">
                        <h4>Import Errors:</h4>
                        <div class="error-list">
                            <?php foreach (array_slice($importResults['errors'], 0, 10) as $error): ?>
                                <div class="error-item"><?= htmlspecialchars($error) ?></div>
                            <?php endforeach; ?>
                            <?php if (count($importResults['errors']) > 10): ?>
                                <div class="error-item">... and <?= count($importResults['errors']) - 10 ?> more errors</div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if (!empty($importResults['skipped'])): ?>
                    <div class="skip-details">
                        <h4>Skipped Records:</h4>
                        <div class="skip-list">
                            <?php foreach (array_slice($importResults['skipped'], 0, 10) as $skip): ?>
                                <div class="skip-item"><?= htmlspecialchars($skip) ?></div>
                            <?php endforeach; ?>
                            <?php if (count($importResults['skipped']) > 10): ?>
                                <div class="skip-item">... and <?= count($importResults['skipped']) - 10 ?> more skipped records</div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="import_type">Import Type <span class="required">*</span></label>
                    <select id="import_type" name="import_type" required>
                        <option value="">Select Import Type</option>
                        <option value="product" <?= ($_POST['import_type'] ?? '') === 'product' ? 'selected' : '' ?>>Product</option>
                        <option value="supplier" <?= ($_POST['import_type'] ?? '') === 'supplier' ? 'selected' : '' ?>>Supplier</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="import_file">File Upload <span class="required">*</span></label>
                    <input type="file" id="import_file" name="import_file" accept=".txt,.csv" required>
                </div>
                
                <div class="file-info">
                    <h4>File Requirements:</h4>
                    <ul>
                        <li>Accepted formats: .txt or .csv files</li>
                        <li>Maximum file size: 5MB</li>
                        <li>File should contain one record per line</li>
                        <li>Fields should be separated by commas</li>
                        <li><strong>Product format:</strong> product_id, product_name, description, price, quantity, status, supplier_id</li>
                        <li><strong>Supplier format:</strong> supplier_id, supplier_name, address, phone, email</li>
                        <li>Status must be A, B, or C for products</li>
                        <li>Products with non-valid supplier IDs will not be added</li>
                        <li>Duplicate IDs will be skipped</li>
                    </ul>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Import Data</button>
                    <a href="dashboard.php" class="btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 