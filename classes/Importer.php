<?php
require_once __DIR__ . '/Supplier.php';
require_once __DIR__ . '/Product.php';
require_once __DIR__ . '/ProductSupplier.php';

class Importer {
    private $supplierManager;
    private $productManager;
    private $productSupplierManager;

    public function __construct() {
        $this->supplierManager = new SupplierManager();
        $this->productManager = new ProductManager();
        $this->productSupplierManager = new ProductSupplierManager();
    }

    public function importSuppliers($lines) {
        $successCount = 0;
        $errorCount = 0;
        $skipCount = 0;
        $errors = [];
        $skipped = [];
        foreach ($lines as $lineNumber => $line) {
            $fields = array_map('trim', explode(',', $line));
            if (count($fields) !== 5) {
                $errorCount++;
                $errors[] = "Line " . ($lineNumber+1) . ": Invalid field count. Expected 5 fields.";
                continue;
            }
            list($supplierId, $supplierName, $address, $phone, $email) = $fields;
            if (!is_numeric($supplierId) || $supplierId <= 0) {
                $errorCount++;
                $errors[] = "Line " . ($lineNumber+1) . ": Invalid supplier ID.";
                continue;
            }
            if ($this->supplierManager->supplierExists($supplierId)) {
                $skipCount++;
                $skipped[] = "Line " . ($lineNumber+1) . ": Supplier ID already exists.";
                continue;
            }
            if ($this->supplierManager->addSupplier($supplierId, $supplierName, $address, $phone, $email)) {
                $successCount++;
            } else {
                $errorCount++;
                $errors[] = "Line " . ($lineNumber+1) . ": Database error while adding supplier.";
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

    public function importProducts($lines) {
        $successCount = 0;
        $errorCount = 0;
        $skipCount = 0;
        $errors = [];
        $skipped = [];
        foreach ($lines as $lineNumber => $line) {
            $fields = array_map('trim', explode(',', $line));
            if (count($fields) !== 7) {
                $errorCount++;
                $errors[] = "Line " . ($lineNumber+1) . ": Invalid field count. Expected 7 fields.";
                continue;
            }
            list($productId, $productName, $description, $price, $quantity, $status, $supplierId) = $fields;
            if (!is_numeric($productId) || $productId <= 0) {
                $errorCount++;
                $errors[] = "Line " . ($lineNumber+1) . ": Invalid product ID.";
                continue;
            }
            if (!is_numeric($supplierId) || $supplierId <= 0) {
                $errorCount++;
                $errors[] = "Line " . ($lineNumber+1) . ": Invalid supplier ID.";
                continue;
            }
            if (!$this->supplierManager->supplierExists($supplierId)) {
                $errorCount++;
                $errors[] = "Line " . ($lineNumber+1) . ": Supplier ID $supplierId does not exist.";
                continue;
            }
            
            // Check if this specific product-supplier combination already exists
            if ($this->productSupplierManager->productSupplierLinkExists($productId, $supplierId)) {
                $skipCount++;
                $skipped[] = "Line " . ($lineNumber+1) . ": Product $productId from supplier $supplierId already exists.";
                continue;
            }
            
            // Add the product if it doesn't exist yet (same product ID can be used by multiple suppliers)
            if (!$this->productManager->productExists($productId)) {
                if (!$this->productManager->addProduct($productId, $productName, $description)) {
                    $errorCount++;
                    $errors[] = "Line " . ($lineNumber+1) . ": Failed to add product $productId.";
                    continue;
                }
            }
            
            // Then add the product offering
            if ($this->productSupplierManager->addProductOffering($productId, $supplierId, $price, $quantity, $status)) {
                $successCount++;
            } else {
                $errorCount++;
                $errors[] = "Line " . ($lineNumber+1) . ": Database error while adding product offering.";
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
} 