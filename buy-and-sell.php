<?php
require_once 'db_conn/database.php';
require_once 'includes/functions.php';
session_start();

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_product') {
        try {
            $name = sanitize($_POST['name']);
            $description = sanitize($_POST['description']);
            $price = floatval($_POST['price']);
            $stock = intval($_POST['stock']);
            $category_id = intval($_POST['category_id']);
            
            // Validate inputs
            if (empty($name) || empty($description) || $price <= 0 || $stock < 0) {
                $_SESSION['error_message'] = 'Please fill in all required fields correctly';
                header('Location: buy-and-sell.php');
                exit();
            }

            // Handle image upload
            $image = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                try {
                    $image = upload_image($_FILES['image'], 'buy_and_sell');
                } catch (Exception $e) {
                    $_SESSION['error_message'] = 'Failed to upload image: ' . $e->getMessage();
                    header('Location: buy-and-sell.php');
                    exit();
                }
            }

            // Insert product
            $stmt = $pdo->prepare("
                INSERT INTO buy_and_sell (name, description, price, stock, category_id, seller_id, image, approval_status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([$name, $description, $price, $stock, $category_id, $user_id, $image]);
            
            $_SESSION['success_message'] = 'Product added successfully! Pending admin approval.';
            header('Location: buy-and-sell.php');
            exit();
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            header('Location: buy-and-sell.php');
            exit();
        }
    } elseif ($_POST['action'] === 'delete_product') {
        try {
            $product_id = intval($_POST['product_id']);
            
            // Verify that the product belongs to the current user
            $stmt = $pdo->prepare("SELECT image FROM buy_and_sell WHERE id = ? AND seller_id = ?");
            $stmt->execute([$product_id, $user_id]);
            $product = $stmt->fetch();
            
            if ($product) {
                // Delete the product
                $stmt = $pdo->prepare("DELETE FROM buy_and_sell WHERE id = ? AND seller_id = ?");
                $stmt->execute([$product_id, $user_id]);
                
                // Delete the image file if it exists
                if ($product['image']) {
                    $image_path = dirname(__FILE__) . "/assets/images/buy_and_sell/" . $product['image'];
                    if (file_exists($image_path)) {
                        unlink($image_path);
                    }
                }
                
                $_SESSION['success_message'] = 'Product removed successfully!';
            } else {
                $_SESSION['error_message'] = 'Product not found or you do not have permission to delete it.';
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = 'Failed to remove product: ' . $e->getMessage();
        }
        header('Location: buy-and-sell.php');
        exit();
    }
}

// Get messages from session and clear them
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Get user's products
$stmt = $pdo->prepare("
    SELECT b.*, c.name as category_name 
    FROM buy_and_sell b 
    JOIN categories c ON b.category_id = c.id 
    WHERE b.seller_id = ? 
    ORDER BY b.created_at DESC
");
$stmt->execute([$user_id]);
$products = $stmt->fetchAll();

// Get categories for the form
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<main class="container">
    <div class="row">
        <!-- Product Form -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Post a New Product</h5>
                </div>
                <div class="card-body">
                    <?php if ($success_message): ?>
                        <div class="alert alert-success"><?= $success_message ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger"><?= $error_message ?></div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add_product">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="price" class="form-label">Price (₱)</label>
                            <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                        </div>

                        <div class="mb-3">
                            <label for="stock" class="form-label">Stock</label>
                            <input type="number" class="form-control" id="stock" name="stock" min="0" required>
                        </div>

                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-control" id="category_id" name="category_id" required>
                                <option value="">Select a category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="image" class="form-label">Product Image</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            <div class="form-text">Max file size: 5MB. Allowed formats: JPG, PNG, GIF</div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Post Product</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Product List -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">My Products</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($products)): ?>
                        <p class="text-center">You haven't posted any products yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Status</th>
                                        <th>Posted</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td>
                                                <?php if ($product['image']): ?>
                                                    <img src="assets/images/buy_and_sell/<?= htmlspecialchars($product['image']) ?>" 
                                                         alt="<?= htmlspecialchars($product['name']) ?>" 
                                                         class="img-thumbnail" 
                                                         style="max-width: 50px;">
                                                <?php else: ?>
                                                    <div class="bg-light text-center" style="width: 50px; height: 50px;">
                                                        <i class="ri-image-line"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($product['name']) ?></td>
                                            <td><?= htmlspecialchars($product['category_name']) ?></td>
                                            <td>₱<?= number_format($product['price'], 2) ?></td>
                                            <td><?= $product['stock'] ?></td>
                                            <td>
                                                <?php if ($product['stock'] > 0): ?>
                                                    <span class="badge bg-success">In Stock</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Out of Stock</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($product['created_at'])) ?></td>
                                            <td>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to remove this product?');">
                                                    <input type="hidden" name="action" value="delete_product">
                                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">
                                                        <i class="ri-delete-bin-line"></i> Remove
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
