<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/BaseManager.php'; // Inheritance example

class User {
    public $id;
    public $username;
    public $password_hash;
    public $email;
    public $created_at;
    public $updated_at;

    public function __construct($data) {
        $this->id = $data['id'] ?? null;
        $this->username = $data['username'] ?? null;
        $this->password_hash = $data['password_hash'] ?? null;
        $this->email = $data['email'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
    }
}

// UserManager now inherits from BaseManager (example of inheritance)
class UserManager extends BaseManager {
    // No need to redefine constructor, inherits $this->pdo from BaseManager

    public function createUser($username, $password, $email = null) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, password_hash, email) VALUES (?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$username, $hashedPassword, $email]);
    }

    public function authenticateUser($username, $password) {
        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            return new User($user);
        }
        return false;
    }

    public function usernameExists($username) {
        $sql = "SELECT COUNT(*) FROM users WHERE username = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$username]);
        return $stmt->fetchColumn() > 0;
    }

    public function getUserById($id) {
        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ? new User($user) : false;
    }

    public function getUserByUsername($username) {
        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        return $user ? new User($user) : false;
    }

    public function getAllUsers() {
        $sql = "SELECT * FROM users";
        $stmt = $this->pdo->query($sql);
        $users = [];
        while ($row = $stmt->fetch()) {
            $users[] = new User($row);
        }
        return $users;
    }

    // Added for test cleanup: delete a user by username
    public function deleteUser($username) {
        $sql = "DELETE FROM users WHERE username = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$username]);
    }
} 