<?php
// Example of inheritance for class assignment
require_once __DIR__ . '/Database.php';

class BaseManager {
    protected $pdo;
    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }
    
    // Allow child classes to access the PDO connection when needed
    public function getConnection() {
        return $this->pdo;
    }
} 