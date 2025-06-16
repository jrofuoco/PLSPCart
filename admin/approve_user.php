<?php
require_once '../db_conn/database.php';
require_once '../includes/functions.php';
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

// Check if user ID is provided
if (!isset($_GET['id'])) {
    header('Location: user_approval.php');
    exit();
}

$user_id = intval($_GET['id']);

// Approve the user
$stmt = $pdo->prepare("UPDATE users SET approval_status = 'approved' WHERE id = ?");
$stmt->execute([$user_id]);

header('Location: user_approval.php');
exit();
?>