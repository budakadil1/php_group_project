<?php
/**
 * Mass Import Page
 * Import products or suppliers from file
 */

session_start();
require_once __DIR__ . '/classes/Importer.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

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
        $importer = new Importer();
        $content = file_get_contents($uploadedFile['tmp_name']);
        if ($content === false) {
            $error = 'Failed to read uploaded file.';
        } else {
            $lines = explode("\n", trim($content));
            $lines = array_filter($lines, function($line) {
                return !empty(trim($line));
            });
            
            if ($importType === 'product') {
                $importResults = $importer->importProducts($lines);
            } else {
                $importResults = $importer->importSuppliers($lines);
            }
            
            if (isset($importResults['error'])) {
                $error = $importResults['error'];
            } else {
                $success = "Import completed! {$importResults['success_count']} records imported successfully. {$importResults['skip_count']} records skipped. {$importResults['error_count']} records failed.";
            }
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
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .file-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: #666;
        }
        
        .error-details,
        .skip-details {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
            margin-top: 1rem;
        }
        
        .error-list,
        .skip-list {
            max-height: 200px;
            overflow-y: auto;
            margin-top: 0.5rem;
        }
        
        .error-item,
        .skip-item {
            padding: 0.25rem 0;
            font-size: 0.9rem;
            color: #666;
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
        <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
        
        <div class="import-container">
            <h1>Mass Import</h1>
            <p>Import products or suppliers from a text or CSV file.</p>
            
            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <?php if (isset($importResults['errors']) && count($importResults['errors']) > 0): ?>
                <div class="error-details">
                    <h3>Import Errors (showing first 10):</h3>
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
            
            <?php if (isset($importResults['skipped']) && count($importResults['skipped']) > 0): ?>
                <div class="skip-details">
                    <h3>Skipped Records (showing first 10):</h3>
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
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="import_type">Import Type <span class="required">*</span></label>
                    <select id="import_type" name="import_type" required>
                        <option value="">Select Import Type</option>
                        <option value="supplier" <?= ($_POST['import_type'] ?? '') === 'supplier' ? 'selected' : '' ?>>Suppliers</option>
                        <option value="product" <?= ($_POST['import_type'] ?? '') === 'product' ? 'selected' : '' ?>>Products</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="import_file">File Upload <span class="required">*</span></label>
                    <input type="file" id="import_file" name="import_file" accept=".txt,.csv" required>
                    <div class="file-info">
                        <strong>File Format:</strong><br>
                        <strong>Suppliers:</strong> supplier_id, supplier_name, address, phone, email<br>
                        <strong>Products:</strong> product_id, product_name, description, price, quantity, status, supplier_id<br>
                        <strong>Status values:</strong> A (Available), B (Backordered), C (Discontinued)<br>
                        <strong>Max file size:</strong> 5MB
                    </div>
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