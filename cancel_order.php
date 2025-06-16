<?php
session_start();
require_once 'db_conn/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id = (int)$_POST['order_id'];
    $user_id = $_SESSION['user_id'];

    // Verify the order belongs to the user and is pending
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($order) {
        // Update the order status to cancelled
        $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$order_id]);

        $_SESSION['success_message'] = 'Order has been successfully cancelled.';
    } else {
        $_SESSION['error_message'] = 'Unable to cancel the order. Either the order does not exist or it is not pending.';
    }
}

header('Location: orders.php');
exit;