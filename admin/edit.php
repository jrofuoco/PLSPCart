<?php
require_once '../db_conn/database.php';
require_once '../includes/functions.php';

session_start();


// Get the view type (products or buy-sell)
$view = isset($_POST['view']) ? $_POST['view'] : 'products';
$table_name = $view === 'buy-sell' ? 'buy_and_sell' : 'products';

// Get form data
$id = $_POST['id'];
$name = $_POST['name'];
$price = $_POST['price'];
$description = $_POST['description'];
$category_name = $_POST['category_id'];

try {
    // Get category ID
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
    $stmt->execute([$category_name]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$category) {
        throw new Exception("Category not found");
    }
    
    $category_id = $category['id'];

    // Handle file upload if a new image was provided
    $image_path = null;
    if (!empty($_FILES['image']['name'])) {
        $upload_dir = '../assets/images/' . ($view === 'buy-sell' ? 'buy_and_sell' : 'product_images') . '/';
        $image_path = uploadImage($_FILES['image'], $upload_dir);
        
        // Get old image to delete it later
        $stmt = $pdo->prepare("SELECT image FROM $table_name WHERE id = ?");
        $stmt->execute([$id]);
        $old_image = $stmt->fetchColumn();
    }

    // Update the item in database
    if (isset($image_path)) {
        $sql = "UPDATE $table_name SET 
                name = ?, price = ?, description = ?, category_id = ?, image = ?
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$name, $price, $description, $category_id, $image_path, $id]);
        
        // Delete old image if update was successful and new image was uploaded
        if ($success && isset($old_image) && $old_image !== 'default.jpg') {
            @unlink($upload_dir . $old_image);
        }
    } else {
        $sql = "UPDATE $table_name SET 
                name = ?, price = ?, description = ?, category_id = ?
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $price, $description, $category_id, $id]);
    }

    // Redirect back with success message
    $_SESSION['success_message'] = "Item updated successfully!";
    header("Location: http://localhost/PLSPcart/admin/edit_product.php");
    exit();
    
} catch (Exception $e) {
    // Redirect back with error message
    $_SESSION['error_message'] = "Error updating item: " . $e->getMessage();
    header("Location: http://localhost/PLSPcart/admin/edit_product.php");
    exit();
    
}