<?php
require_once '../db_conn/database.php';

// Get product ID from query string
$id = intval($_GET['id']);

if ($id) {
    // Approve the product
    $stmt = $pdo->prepare("UPDATE buy_and_sell SET approval_status = 'approved' WHERE id = ?");
    $stmt->execute([$id]);

    // Redirect back to sell approval page
    header('Location: sell_approval.php');
    exit();
} else {
    // Redirect back with error
    header('Location: sell_approval.php?error=InvalidProductID');
    exit();
}
?>