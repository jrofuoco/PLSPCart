<?php
session_start();
require_once 'includes/header.php';
require_once 'db_conn/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Check if order_id is provided
if (!isset($_GET['order_id'])) {
    header('Location: index.php');
    exit;
}

$order_id = (int)$_GET['order_id'];
$user_id = $_SESSION['user_id'];

// Get order details
$stmt = $pdo->prepare("
    SELECT o.*, u.email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

// If order doesn't exist or doesn't belong to user, redirect
if (!$order) {
    header('Location: index.php');
    exit;
}

// Get order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.name, p.image 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="main">
    <div class="container">
        <div class="order-success">
            <div class="order-success__header">
                <h1 class="page__title">Order Confirmed!</h1>
                <div class="order-success__icon">✓</div>
                <p class="order-success__message">Thank you for your order. Your order has been received.</p>
            </div>

            <div class="order-details">
                <div class="order-details__section">
                    <h2>Order Information</h2>
                    <div class="order-details__grid">
                        <div class="order-details__item">
                            <span class="label">Order Number:</span>
                            <span class="value">#<?= str_pad($order['id'], 8, '0', STR_PAD_LEFT) ?></span>
                        </div>
                        <div class="order-details__item">
                            <span class="label">Date:</span>
                            <span class="value"><?= date('F j, Y', strtotime($order['created_at'])) ?></span>
                        </div>
                        <div class="order-details__item">
                            <span class="label">Total Amount:</span>
                            <span class="value">₱<?= number_format($order['total_amount'], 2) ?></span>
                        </div>
                        <div class="order-details__item">
                            <span class="label">Payment Method:</span>
                            <span class="value"><?= ucwords(str_replace('_', ' ', $order['payment_method'])) ?></span>
                        </div>
                        <div class="order-details__item">
                            <span class="label">Status:</span>
                            <span class="value status-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span>
                        </div>
                    </div>
                </div>

                <div class="order-details__section">
                    <h2>Order Items</h2>
                    <div class="order-items">
                        <?php foreach ($order_items as $item): ?>
                            <div class="order-item">
                                <img src="<?= asset_url('uploads/products/' . $item['image']) ?>" 
                                     alt="<?= htmlspecialchars($item['name']) ?>" 
                                     class="order-item__image">
                                <div class="order-item__details">
                                    <h3 class="order-item__name"><?= htmlspecialchars($item['name']) ?></h3>
                                    <p class="order-item__quantity">Quantity: <?= $item['quantity'] ?></p>
                                    <p class="order-item__price">₱<?= number_format($item['price'], 2) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="order-details__section">
                    <h2>What's Next?</h2>
                    <div class="next-steps">
                        <?php if ($order['payment_method'] === 'cash'): ?>
                            <p>Please prepare the exact amount for cash on delivery.</p>
                        <?php elseif ($order['payment_method'] === 'pay_on_counter'): ?>
                            <p>Please proceed to the counter to pay for your order.</p>
                        <?php endif; ?>
                        <p>You will receive updates about your order status via email.</p>
                    </div>
                </div>

                <div class="order-actions">
                    <a href="index.php" class="btn btn--secondary">Continue Shopping</a>
                    <a href="orders.php" class="btn btn--primary">View All Orders</a>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.order-success {
    max-width: 800px;
    margin: 2rem auto;
    padding: 2rem;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.order-success__header {
    text-align: center;
    margin-bottom: 2rem;
}

.order-success__icon {
    font-size: 4rem;
    color: #4CAF50;
    margin: 1rem 0;
}

.order-success__message {
    font-size: 1.2rem;
    color: #666;
}

.order-details__section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #f9f9f9;
    border-radius: 4px;
}

.order-details__grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.order-details__item {
    display: flex;
    flex-direction: column;
}

.order-details__item .label {
    font-weight: bold;
    color: #666;
    margin-bottom: 0.25rem;
}

.order-items {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.order-item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: #fff;
    border-radius: 4px;
    border: 1px solid #eee;
}

.order-item__image {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 4px;
}

.order-item__details {
    flex: 1;
}

.order-item__name {
    margin: 0 0 0.5rem 0;
    font-size: 1.1rem;
}

.order-item__quantity {
    color: #666;
    margin: 0.25rem 0;
}

.order-item__price {
    font-weight: bold;
    color: #2c3e50;
    margin: 0.25rem 0;
}

.next-steps {
    background: #e8f5e9;
    padding: 1rem;
    border-radius: 4px;
    margin-top: 1rem;
}

.next-steps p {
    margin: 0.5rem 0;
    color: #2c3e50;
}

.order-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 2rem;
}

.status-pending {
    color: #f39c12;
}

.status-processing {
    color: #3498db;
}

.status-completed {
    color: #2ecc71;
}

.status-cancelled {
    color: #e74c3c;
}
</style>

<?php require_once 'includes/footer.php'; ?> 