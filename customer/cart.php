<?php
session_start();
require_once '../db_conn/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_quantity'])) {
        $product_id = $_POST['product_id'];
        $quantity = (int)$_POST['quantity'];

        if ($quantity > 0) {
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$quantity, $user_id, $product_id]);
        }
    } elseif (isset($_POST['remove_item'])) {
        $product_id = $_POST['product_id'];
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
    }

    header("Location: cart.php");
    exit();
}

// Get cart items with product details
$stmt = $pdo->prepare("
    SELECT c.*, p.name, p.price, p.image, p.stock, p.description 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Save cart items to session before redirecting to checkout
$_SESSION['cart'] = [];
foreach ($cart_items as $item) {
    $_SESSION['cart'][] = [
        'id' => $item['product_id'],
        'name' => $item['name'],
        'price' => $item['price'],
        'quantity' => $item['quantity'],
        'image' => $item['image']
    ];
}
?>

<?php include '../includes/header.php'; ?>

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

<style>
    .cart-item {
        border-bottom: 1px solid #eee;
        padding: 20px 0;
    }
    .product-image {
        max-width: 100px;
        height: auto;
        border-radius: 5px;
    }
    .quantity-input {
        width: 60px;
        text-align: center;
    }
    .subtotal {
        font-weight: bold;
        color: #198754;
    }
    .empty-cart {
        text-align: center;
        padding: 50px 0;
    }
</style>

<main class="container py-5">
    <h1 class="mb-4">Your Shopping Cart</h1>

    <?php if (isset($_SESSION['cart_error'])): ?>
        <div class="alert alert-danger">
            <?= $_SESSION['cart_error']; unset($_SESSION['cart_error']); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($cart_items)): ?>
        <div class="empty-cart">
            <i class="bi bi-cart-x" style="font-size: 3rem; color: #6c757d;"></i>
            <h3 class="mt-3">Your cart is empty</h3>
            <p class="text-muted">Browse our products and add items to your cart</p>
            <a href="../products.php" class="btn btn-success mt-3">Continue Shopping</a>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-md-8">
                <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item row">
                        <div class="col-3 col-md-2">
                            <img src="../assets/images/product_images/<?= htmlspecialchars($item['image']) ?>" 
                                 alt="<?= htmlspecialchars($item['name']) ?>" 
                                 class="product-image img-fluid"
                                 onerror="this.src='../assets/images/product_images/default.jpg'">
                        </div>
                        <div class="col-9 col-md-6">
                            <h5><?= htmlspecialchars($item['name']) ?></h5>
                            <p class="text-muted"><?= htmlspecialchars($item['description']) ?></p>
                            <p class="text-success fw-bold"><?= formatPrice($item['price']) ?></p>
                            <p class="text-muted small">Added on <?= date('M j, Y', strtotime($item['created_at'])) ?></p>
                        </div>
                        <div class="col-12 col-md-4 mt-3 mt-md-0">
                            <form method="POST" class="d-flex align-items-center justify-content-end gap-3">
                                <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                <div>
                                    <label for="quantity_<?= $item['product_id'] ?>" class="form-label visually-hidden">Quantity</label>
                                    <input type="number" 
                                           name="quantity" 
                                           id="quantity_<?= $item['product_id'] ?>" 
                                           class="form-control quantity-input" 
                                           min="1" 
                                           max="<?= $item['stock'] ?>" 
                                           value="<?= $item['quantity'] ?>">
                                </div>
                                <button type="submit" name="update_quantity" class="btn btn-outline-success btn-sm">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
                                <button type="submit" name="remove_item" class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            <div class="text-end mt-2">
                                <span class="subtotal"><?= formatPrice($item['price'] * $item['quantity']) ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body"> 
                        <h5 class="card-title">Order Summary</h5>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span><?= formatPrice($total) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping:</span>
                            <span>Free</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Total:</span>
                            <span><?= formatPrice($total) ?></span>
                        </div>
                        <form method="POST" action="../checkout.php">
                            <!-- Include all cart items as hidden inputs -->
                            <?php foreach ($cart_items as $item): ?>
                                <input type="hidden" name="cart_items[<?= $item['product_id'] ?>][product_id]" value="<?= $item['product_id'] ?>">
                                <input type="hidden" name="cart_items[<?= $item['product_id'] ?>][quantity]" value="<?= $item['quantity'] ?>">
                                <input type="hidden" name="cart_items[<?= $item['product_id'] ?>][price]" value="<?= $item['price'] ?>">
                                <input type="hidden" name="cart_items[<?= $item['product_id'] ?>][name]" value="<?= htmlspecialchars($item['name']) ?>">
                                <input type="hidden" name="cart_items[<?= $item['product_id'] ?>][image]" value="<?= htmlspecialchars($item['image']) ?>">
                            <?php endforeach; ?>
                            <input type="hidden" name="total" value="<?= $total ?>">
                            <button type="submit" name="proceed_checkout" class="btn btn-success w-100 mt-3">Proceed to Checkout</button>
                        </form>
                        <a href="../products.php" class="btn btn-outline-secondary w-100 mt-2">Continue Shopping</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php include '../includes/footer.php'; ?>
