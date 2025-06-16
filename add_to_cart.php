<?php
session_start();
require_once 'db_conn/database.php';

header('Content-Type: application/json');

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

// Validate product ID
if (!isset($_POST['product_id']) || !is_numeric($_POST['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = (int) $_POST['product_id'];

// Check if the product is already in the cart
$sql = "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id, $product_id]);
$existing = $stmt->fetch();

if ($existing) {
    // Update quantity by +1
    $new_quantity = $existing['quantity'] + 1;
    $updateSql = "UPDATE cart SET quantity = ?, created_at = NOW() WHERE id = ?";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([$new_quantity, $existing['id']]);
} else {
    // Insert new row
    $insertSql = "INSERT INTO cart (user_id, product_id, quantity, created_at) VALUES (?, ?, ?, NOW())";
    $insertStmt = $pdo->prepare($insertSql);
    $insertStmt->execute([$user_id, $product_id, 1]);
}

echo json_encode(['success' => true]);
exit;
