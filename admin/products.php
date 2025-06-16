<?php
session_start();
require_once '../includes/header.php';
require_once '../db_conn/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== 1) {
    header('Location: ../login.php');
    exit;
}

// Handle product deletion
if (isset($_POST['delete_product'])) {
    $product_id = (int)$_POST['product_id'];
    // Delete product image first
    $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    if ($product && $product['image']) {
        $image_path = "../assets/images/product_images/" . $product['image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    // Delete product
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    header('Location: products.php?message=deleted');
    exit;
}

// Get all products
$products = $pdo->query("
    SELECT * FROM products 
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="admin-dashboard">
    <div class="admin-container">
        <aside class="admin-sidebar">
            <div class="admin-sidebar__header">
                <h2>Admin Panel</h2>
            </div>
            <nav class="admin-nav">
                <a href="index.php" class="admin-nav__item">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="orders.php" class="admin-nav__item">
                    <i class="fas fa-shopping-cart"></i> Orders
                </a>
                <a href="products.php" class="admin-nav__item active">
                    <i class="fas fa-box"></i> Products
                </a>
                <a href="posts.php" class="admin-nav__item">
                    <i class="fas fa-bullhorn"></i> Buy & Sell Posts
                </a>
                <a href="../logout.php" class="admin-nav__item">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </aside>

        <div class="admin-content">
            <header class="admin-header">
                <h1>Manage Products</h1>
                <a href="add_product.php" class="btn btn--primary">
                    <i class="fas fa-plus"></i> Add New Product
                </a>
            </header>

            <?php if (isset($_GET['message'])): ?>
                <div class="alert alert--success">
                    <?php
                    switch ($_GET['message']) {
                        case 'added':
                            echo 'Product added successfully!';
                            break;
                        case 'updated':
                            echo 'Product updated successfully!';
                            break;
                        case 'deleted':
                            echo 'Product deleted successfully!';
                            break;
                    }
                    ?>
                </div>
            <?php endif; ?>

            <div class="admin-section">
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <img src="../assets/images/product_images/<?= htmlspecialchars($product['image']) ?>" 
                                             alt="<?= htmlspecialchars($product['name']) ?>"
                                             class="product-thumbnail">
                                    </td>
                                    <td><?= htmlspecialchars($product['name']) ?></td>
                                    <td>₱<?= number_format($product['price'], 2) ?></td>
                                    <td><?= $product['stock'] ?></td>
                                    <td>
                                        <span class="status-badge status-<?= $product['stock'] > 0 ? 'active' : 'inactive' ?>">
                                            <?= $product['stock'] > 0 ? 'In Stock' : 'Out of Stock' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit_product.php?id=<?= $product['id'] ?>" 
                                               class="btn btn--small btn--secondary">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <form method="POST" class="delete-form" 
                                                  onsubmit="return confirm('Are you sure you want to delete this product?');">
                                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                                <button type="submit" name="delete_product" class="btn btn--small btn--danger">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
/* Include the admin dashboard styles from index.php */

.product-thumbnail {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 4px;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.delete-form {
    display: inline;
}

.btn--danger {
    background: #dc3545;
    color: white;
}

.btn--danger:hover {
    background: #c82333;
}

.alert {
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
}

.alert--success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.status-active {
    background: #d4edda;
    color: #155724;
}

.status-inactive {
    background: #f8d7da;
    color: #721c24;
}
</style>

<?php require_once '../includes/footer.php'; ?> 