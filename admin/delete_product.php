<?php
session_start();
require_once '../db_conn/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $view = $_POST['view'];

    // Determine the table based on the view
    $table_name = $view === 'buy-sell' ? 'buy_and_sell' : 'products';

    // Delete the product from the database
    $stmt = $pdo->prepare("DELETE FROM $table_name WHERE id = ?");
    $stmt->execute([$id]);

    $_SESSION['success_message'] = 'Item has been successfully deleted.';
}

header('Location: edit_product.php?view=' . $view);
exit;