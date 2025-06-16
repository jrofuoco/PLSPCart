<?php
session_start();
ob_start(); // Start output buffering to prevent header issues
// ✅ Now safe to include header.php (after any redirect attempts)
require_once '../includes/header.php';
require_once '../db_conn/database.php';
require_once '../includes/functions.php'; // make sure asset_url(), url(), etc. still work

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $category_id = (int)$_POST['category_id'];
    $featured = isset($_POST['featured']) ? 1 : 0;
    $seller_id = $_SESSION['user_id'];
    $created_at = date('Y-m-d H:i:s');
    $updated_at = $created_at;

    // Handle image upload
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $new_filename = uniqid() . '.' . $ext;
            $upload_path = '../assets/images/product_images/' . $new_filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image = $new_filename;
            }
        }
    }

    if ($image && $category_id && $seller_id) {
        $stmt = $pdo->prepare("
            INSERT INTO products 
                (name, description, price, stock, category_id, seller_id, image, featured, created_at, updated_at) 
            VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $name, $description, $price, $stock, $category_id, $seller_id, $image, $featured, $created_at, $updated_at
        ]);


        // Redirect with success message
        $_SESSION['toast_message'] = 'Product added successfully!';
        header('Location: http://localhost/PLSPCart/admin/add_product.php');
        exit;
    }
}

ob_end_flush(); // End output buffering
?>

<main class="admin-dashboard">
    <div class="admin-container">
        <div class="admin-content">
            <header class="admin-header">
                <h1>Add New Product</h1>
                <div class="button-wrapper">
                    <a href="products.php" class="btn btn--secondary">
                        <i class="fas fa-arrow-left"></i> Back to Products
                    </a>
                </div>
            </header>

            <div class="admin-section">
                <form method="POST" enctype="multipart/form-data" class="admin-form">
                    <div class="form-group">
                        <label for="name">Product Name</label>
                        <input type="text" id="name" name="name" required 
                               class="form-control" value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" required 
                                  class="form-control" rows="4"><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="price">Price (₱)</label>
                            <input type="number" id="price" name="price" required 
                                   class="form-control" step="0.01" min="0"
                                   value="<?= isset($_POST['price']) ? htmlspecialchars($_POST['price']) : '' ?>">
                        </div>

                        <div class="form-group">
                            <label for="stock">Stock</label>
                            <input type="number" id="stock" name="stock" required 
                                   class="form-control" min="0"
                                   value="<?= isset($_POST['stock']) ? htmlspecialchars($_POST['stock']) : '' ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id" class="form-control" required>
                            <option value="">Select a category</option>
                            <?php
                            $categories = $pdo->query("SELECT id, name FROM categories")->fetchAll();
                            foreach ($categories as $category) {
                                $selected = (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : '';
                                echo "<option value=\"{$category['id']}\" $selected>{$category['name']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="featured">
                            <input type="checkbox" id="featured" name="featured" <?= isset($_POST['featured']) ? 'checked' : '' ?>>
                            Mark as Featured
                        </label>
                    </div>

                    <div class="form-group">
                        <label for="image">Product Image</label>
                        <input type="file" id="image" name="image" required 
                               class="form-control" accept="image/*">
                        <small class="form-text">Allowed formats: JPG, JPEG, PNG, GIF</small>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn--primary">
                            <i class="fas fa-save"></i> Save Product
                        </button>
                        <a href="products.php" class="btn btn--secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<style>
.button-wrapper {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    margin-bottom: 1.5rem;
}
.admin-dashboard {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 2rem;
}
.admin-form {
    max-width: 800px;
    width: 100%;
}
.form-group {
    margin-bottom: 1.5rem;
}
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}
.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #495057;
}
.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 1rem;
}
.form-control:focus {
    border-color: #80bdff;
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}
.form-text {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.875rem;
    color: #6c757d;
}
.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}
@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?>
