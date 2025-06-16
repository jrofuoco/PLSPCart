<?php
require_once __DIR__ . '/../db_conn/database.php';

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Get featured products
 */
function getFeaturedProducts($limit = 4) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM products WHERE featured = TRUE AND stock > 0 LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get cart items for a user
 */
function getCartItems($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT c.*, p.name, p.price, p.image 
                          FROM cart c 
                          JOIN products p ON c.product_id = p.id 
                          WHERE c.user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Format price with peso symbol
 */
function format_price($price) {
    return '₱' . number_format($price, 2);
}
function formatPrice($price) {
    return '₱' . number_format($price, 2);
}

/**
 * Redirect to a URL
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Sanitize input data
 */
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Get status color for order status
 */
function get_status_color($status) {
    return match($status) {
        'pending' => 'warning',
        'processing' => 'info',
        'shipped' => 'primary',
        'delivered' => 'success',
        'cancelled' => 'danger',
        default => 'secondary'
    };
}

/**
 * Generate a random string
 */
function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $random_string = '';
    for ($i = 0; $i < $length; $i++) {
        $random_string .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $random_string;
}

/**
 * Upload an image file
 */
function upload_image($file, $type = 'products') {
    if (!isset($file['error']) || is_array($file['error'])) {
        throw new RuntimeException('Invalid parameters.');
    }

    // Debug information
    error_log("Starting image upload process");
    error_log("File info: " . print_r($file, true));

    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            throw new RuntimeException('Exceeded filesize limit.');
        default:
            throw new RuntimeException('Unknown errors.');
    }

    if ($file['size'] > 5000000) { // 5MB limit
        throw new RuntimeException('Exceeded filesize limit.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file['tmp_name']);
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];

    if (!in_array($mime_type, $allowed_types)) {
        throw new RuntimeException('Invalid file format: ' . $mime_type);
    }

    // Set target directory based on type
    $base_dir = dirname(dirname(__FILE__)); // Go up one level from includes directory
    $target_dir = $base_dir . '/assets/images/';
    if ($type === 'buy_and_sell') {
        $target_dir .= 'buy_and_sell/';
    } else {
        $target_dir .= 'products/';
    }

    error_log("Target directory: " . $target_dir);

    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        error_log("Creating directory: " . $target_dir);
        if (!mkdir($target_dir, 0777, true)) {
            throw new RuntimeException('Failed to create directory: ' . $target_dir);
        }
    }

    // Check if directory is writable
    if (!is_writable($target_dir)) {
        error_log("Directory is not writable: " . $target_dir);
        throw new RuntimeException('Upload directory is not writable');
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = generate_random_string() . '.' . $extension;
    $target_path = $target_dir . $filename;

    error_log("Attempting to move file to: " . $target_path);

    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        $upload_error = error_get_last();
        error_log("Failed to move uploaded file. Error: " . print_r($upload_error, true));
        throw new RuntimeException('Failed to move uploaded file.');
    }

    error_log("File successfully uploaded to: " . $target_path);
    return $filename;
}

/**
 * Get cart total
 */
function get_cart_total($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT SUM(c.quantity * p.price) as total
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn() ?: 0;
}

/**
 * Get cart items count
 */
function get_cart_count($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT SUM(quantity) as count
        FROM cart
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn() ?: 0;
}
?>