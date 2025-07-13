<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/BaseManager.php'; // Inheritance example

class Supplier {
    public $supplier_id;
    public $supplier_name;
    public $address;
    public $phone;
    public $email;

    public function __construct($data) {
        $this->supplier_id = $data['supplier_id'] ?? null;
        $this->supplier_name = $data['supplier_name'] ?? null;
        $this->address = $data['address'] ?? null;
        $this->phone = $data['phone'] ?? null;
        $this->email = $data['email'] ?? null;
    }
}

// SupplierManager now inherits from BaseManager (example of inheritance)
class SupplierManager extends BaseManager {
    // No need to redefine constructor, inherits $this->pdo from BaseManager

    public function addSupplier($supplierId, $supplierName, $address, $phone, $email) {
        $sql = "INSERT INTO supplier (supplier_id, supplier_name, address, phone, email) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$supplierId, $supplierName, $address, $phone, $email]);
    }

    public function updateSupplier($supplierId, $supplierName, $address, $phone, $email) {
        $sql = "UPDATE supplier SET supplier_name = ?, address = ?, phone = ?, email = ? WHERE supplier_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$supplierName, $address, $phone, $email, $supplierId]);
    }

    public function deleteSupplier($supplierId) {
        $sql = "DELETE FROM supplier WHERE supplier_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$supplierId]);
    }

    public function supplierExists($supplierId) {
        $sql = "SELECT COUNT(*) FROM supplier WHERE supplier_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$supplierId]);
        return $stmt->fetchColumn() > 0;
    }

    public function getSupplierById($supplierId) {
        $sql = "SELECT * FROM supplier WHERE supplier_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$supplierId]);
        $supplier = $stmt->fetch();
        return $supplier ? new Supplier($supplier) : false;
    }

    public function getAllSuppliers() {
        $sql = "SELECT * FROM supplier ORDER BY supplier_name";
        $stmt = $this->pdo->query($sql);
        $suppliers = [];
        while ($row = $stmt->fetch()) {
            $suppliers[] = new Supplier($row);
        }
        return $suppliers;
    }
} 