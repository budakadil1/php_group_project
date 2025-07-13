<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/BaseManager.php'; // Inheritance example

class ProductSupplier {
    public $product_id;
    public $supplier_id;
    public $price;
    public $quantity;
    public $status;

    public function __construct($data) {
        $this->product_id = $data['product_id'] ?? null;
        $this->supplier_id = $data['supplier_id'] ?? null;
        $this->price = $data['price'] ?? null;
        $this->quantity = $data['quantity'] ?? null;
        $this->status = $data['status'] ?? null;
    }
}

// ProductSupplierManager now inherits from BaseManager (example of inheritance)
class ProductSupplierManager extends BaseManager {
    // No need to redefine constructor, inherits $this->pdo from BaseManager

    public function addProductOffering($productId, $supplierId, $price, $quantity, $status) {
        $sql = "INSERT INTO InventoryTable (product_id, supplier_id, price, quantity, status) VALUES (?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE price = VALUES(price), quantity = VALUES(quantity), status = VALUES(status)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$productId, $supplierId, $price, $quantity, $status]);
    }

    public function deleteProductOffering($productId, $supplierId) {
        $sql = "DELETE FROM InventoryTable WHERE product_id = ? AND supplier_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$productId, $supplierId]);
    }

    public function productSupplierLinkExists($productId, $supplierId) {
        $sql = "SELECT COUNT(*) FROM InventoryTable WHERE product_id = ? AND supplier_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$productId, $supplierId]);
        return $stmt->fetchColumn() > 0;
    }

    public function getAllProductOfferings() {
        $sql = "SELECT * FROM InventoryTable";
        $stmt = $this->pdo->query($sql);
        $offerings = [];
        while ($row = $stmt->fetch()) {
            $offerings[] = new ProductSupplier($row);
        }
        return $offerings;
    }

    public function getProductsBySupplier($supplierId) {
        $sql = "SELECT * FROM InventoryTable WHERE supplier_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$supplierId]);
        $offerings = [];
        while ($row = $stmt->fetch()) {
            $offerings[] = new ProductSupplier($row);
        }
        return $offerings;
    }

    public function getProductOffering($productId, $supplierId) {
        $sql = "SELECT * FROM InventoryTable WHERE product_id = ? AND supplier_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$productId, $supplierId]);
        $row = $stmt->fetch();
        return $row ? new ProductSupplier($row) : false;
    }
} 