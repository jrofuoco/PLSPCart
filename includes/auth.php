<?php
session_start();
require_once __DIR__ . '/../db_conn/database.php';

class Auth {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Register new user
    public function register($username, $email, $password, $firstname, $lastname) {
        try {
            // Check if email already exists
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                return ['status' => 'error', 'message' => 'Email already exists'];
            }

            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user
            $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password, firstname, lastname, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $result = $stmt->execute([$username, $email, $hashed_password, $firstname, $lastname]);

            if ($result) {
                return ['status' => 'success', 'message' => 'Registration successful'];
            } else {
                return ['status' => 'error', 'message' => 'Registration failed'];
            }
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Login user
    public function login($email, $password) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                return ['status' => 'success', 'message' => 'Login successful'];
            } else {
                return ['status' => 'error', 'message' => 'Invalid email or password'];
            }
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Check if user is logged in
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    // Get current user data
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            try {
                $stmt = $this->pdo->prepare("SELECT id, username, email, firstname, lastname, role FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                return null;
            }
        }
        return null;
    }

    // Logout user
    public function logout() {
        session_unset();
        session_destroy();
        return ['status' => 'success', 'message' => 'Logged out successfully'];
    }

    // Update user profile
    public function updateProfile($user_id, $data) {
        try {
            $allowed_fields = ['username', 'firstname', 'lastname', 'email'];
            $updates = [];
            $values = [];

            foreach ($data as $key => $value) {
                if (in_array($key, $allowed_fields)) {
                    $updates[] = "$key = ?";
                    $values[] = $value;
                }
            }

            if (empty($updates)) {
                return ['status' => 'error', 'message' => 'No valid fields to update'];
            }

            $values[] = $user_id;
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($values);

            if ($result) {
                return ['status' => 'success', 'message' => 'Profile updated successfully'];
            } else {
                return ['status' => 'error', 'message' => 'Failed to update profile'];
            }
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Change password
    public function changePassword($user_id, $current_password, $new_password) {
        try {
            // Verify current password
            $stmt = $this->pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($current_password, $user['password'])) {
                return ['status' => 'error', 'message' => 'Current password is incorrect'];
            }

            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $result = $stmt->execute([$hashed_password, $user_id]);

            if ($result) {
                return ['status' => 'success', 'message' => 'Password changed successfully'];
            } else {
                return ['status' => 'error', 'message' => 'Failed to change password'];
            }
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}

// Initialize Auth class
$auth = new Auth($pdo);
?>
