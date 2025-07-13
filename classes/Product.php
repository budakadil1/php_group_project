<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/BaseManager.php'; // Inheritance example

class Product {
    public $product_id;
    public $product_name;
    public $description;

    public function __construct($data) {
        $this->product_id = $data['product_id'] ?? null;
        $this->product_name = $data['product_name'] ?? null;
        $this->description = $data['description'] ?? null;
    }
}

// ProductManager now inherits from BaseManager (example of inheritance)
class ProductManager extends BaseManager {
    // No need to redefine constructor, inherits $this->pdo from BaseManager

    public function addProduct($productId, $productName, $description) {
        $sql = "INSERT INTO product (product_id, product_name, description) VALUES (?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$productId, $productName, $description]);
    }

    public function getProductById($productId) {
        $sql = "SELECT * FROM product WHERE product_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        return $product ? new Product($product) : false;
    }

    public function getAllProducts() {
        $sql = "SELECT * FROM product ORDER BY product_name";
        $stmt = $this->pdo->query($sql);
        $products = [];
        while ($row = $stmt->fetch()) {
            $products[] = new Product($row);
        }
        return $products;
    }

    public function updateProduct($productId, $productName, $description) {
        $sql = "UPDATE product SET product_name = ?, description = ? WHERE product_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$productName, $description, $productId]);
    }

    public function deleteProduct($productId) {
        $sql = "DELETE FROM product WHERE product_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$productId]);
    }

    // Added for import validation: check if product exists
    public function productExists($productId) {
        $sql = "SELECT COUNT(*) FROM product WHERE product_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$productId]);
        return $stmt->fetchColumn() > 0;
    }
} 