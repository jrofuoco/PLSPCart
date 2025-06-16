<?php
session_start();
require_once 'includes/header.php';
require_once 'db_conn/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';

// Build the base query
$query = "
    SELECT o.*, 
           COUNT(oi.id) as total_items,
           GROUP_CONCAT(
               CONCAT(
                   p.name, '|',
                   p.image, '|',
                   oi.quantity, '|',
                   oi.price
               ) 
               SEPARATOR '||'
           ) as product_details
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE o.user_id = ?
";

$params = [$user_id];

// Add status filter if specified
if ($status_filter && $status_filter !== 'all') {
    $query .= " AND o.status = ?";
    $params[] = $status_filter;
}

// Add date filter if specified
if ($date_filter) {
    switch ($date_filter) {
        case 'today':
            $query .= " AND DATE(o.created_at) = CURDATE()";
            break;
        case 'week':
            $query .= " AND o.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
            break;
        case 'month':
            $query .= " AND o.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            break;
    }
}

// Group by order to avoid duplicates
$query .= " GROUP BY o.id ORDER BY o.created_at DESC";

// Execute the query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="main">
    <div class="container">
        <div class="orders-page">
            <h1 class="page__title">My Orders</h1>

            <!-- Filters -->
            <div class="orders-filters">
                <form method="GET" class="filters-form">
                    <div class="filter-group">
                        <label for="status">Filter by Status:</label>
                        <select name="status" id="status" onchange="this.form.submit()">
                            <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Orders</option>
                            <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="processing" <?= $status_filter === 'processing' ? 'selected' : '' ?>>Processing</option>
                            <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="date">Filter by Date:</label>
                        <select name="date" id="date" onchange="this.form.submit()">
                            <option value="" <?= $date_filter === '' ? 'selected' : '' ?>>All Time</option>
                            <option value="today" <?= $date_filter === 'today' ? 'selected' : '' ?>>Today</option>
                            <option value="week" <?= $date_filter === 'week' ? 'selected' : '' ?>>Last 7 Days</option>
                            <option value="month" <?= $date_filter === 'month' ? 'selected' : '' ?>>Last 30 Days</option>
                        </select>
                    </div>
                </form>
            </div>

            <?php if (empty($orders)): ?>
                <div class="no-orders">
                    <p>You haven't placed any orders yet.</p>
                    <a href="index.php" class="btn btn--primary">Start Shopping</a>
                </div>
            <?php else: ?>
                <div class="orders-list">
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card">
                            <div class="order-card__header">
                                <div class="order-card__info">
                                    <h3>Order #<?= str_pad($order['id'], 8, '0', STR_PAD_LEFT) ?></h3>
                                    <p class="order-date"><?= date('F j, Y', strtotime($order['created_at'])) ?></p>
                                </div>
                                <div class="order-card__status">
                                    <span class="status-badge status-<?= $order['status'] ?>">
                                        <?= ucfirst($order['status']) ?>
                                    </span>
                                </div>
                            </div>

                            <div class="order-card__body">
                                <div class="order-card__details">
                                    <div class="detail-item">
                                        <span class="label">Total Items:</span>
                                        <span class="value"><?= $order['total_items'] ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Total Amount:</span>
                                        <span class="value">₱<?= number_format($order['total_amount'], 2) ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Payment Method:</span>
                                        <span class="value"><?= ucwords(str_replace('_', ' ', $order['payment_method'])) ?></span>
                                    </div>
                                </div>

                                <div class="order-card__products">
                                    <p class="products-label">Ordered Items:</p>
                                    <div class="products-grid">
                                        <?php 
                                        if ($order['product_details']) {
                                            $products = explode('||', $order['product_details']);
                                            foreach ($products as $product) {
                                                list($name, $image, $quantity, $price) = explode('|', $product);
                                        ?>
                                            <div class="product-item">
                                                <img src="assets/images/product_images/<?= htmlspecialchars($image) ?>" 
                                                     alt="<?= htmlspecialchars($name) ?>" 
                                                     class="product-item__image">
                                                <div class="product-item__details">
                                                    <h4 class="product-item__name"><?= htmlspecialchars($name) ?></h4>
                                                    <p class="product-item__quantity">Qty: <?= $quantity ?></p>
                                                    <p class="product-item__price">₱<?= number_format($price, 2) ?></p>
                                                </div>
                                            </div>
                                        <?php 
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>

                            <div class="order-card__footer">
                                <a href="order_success.php?order_id=<?= $order['id'] ?>" class="btn btn--secondary">View Details</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<style>
.orders-page {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.orders-filters {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 2rem;
}

.filters-form {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-group label {
    font-weight: 500;
    color: #495057;
}

.filter-group select {
    padding: 0.5rem;
    border: 1px solid #ced4da;
    border-radius: 4px;
    background-color: white;
}

.no-orders {
    text-align: center;
    padding: 3rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.no-orders p {
    margin-bottom: 1rem;
    color: #6c757d;
    font-size: 1.1rem;
}

.orders-list {
    display: grid;
    gap: 1.5rem;
}

.order-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}

.order-card__header {
    padding: 1rem;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.order-card__info h3 {
    margin: 0;
    color: #212529;
    font-size: 1.1rem;
}

.order-date {
    margin: 0.25rem 0 0;
    color: #6c757d;
    font-size: 0.9rem;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-processing { background: #cce5ff; color: #004085; }
.status-completed { background: #d4edda; color: #155724; }
.status-cancelled { background: #f8d7da; color: #721c24; }

.order-card__body {
    padding: 1rem;
}

.order-card__details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.detail-item {
    display: flex;
    flex-direction: column;
}

.detail-item .label {
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
}

.detail-item .value {
    font-weight: 500;
    color: #212529;
}

.order-card__products {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 4px;
}

.products-label {
    font-weight: 500;
    color: #495057;
    margin: 0 0 0.5rem;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.product-item {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.product-item:hover {
    transform: translateY(-2px);
}

.product-item__image {
    width: 100%;
    height: 150px;
    object-fit: cover;
    border-bottom: 1px solid #eee;
}

.product-item__details {
    padding: 0.75rem;
}

.product-item__name {
    margin: 0 0 0.5rem;
    font-size: 0.9rem;
    color: #212529;
    line-height: 1.3;
    /* Truncate long names */
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.product-item__quantity {
    margin: 0.25rem 0;
    font-size: 0.85rem;
    color: #6c757d;
}

.product-item__price {
    margin: 0.25rem 0 0;
    font-weight: 500;
    color: #2c3e50;
    font-size: 0.9rem;
}

.order-card__footer {
    padding: 1rem;
    border-top: 1px solid #dee2e6;
    text-align: right;
}

@media (max-width: 768px) {
    .filters-form {
        flex-direction: column;
    }
    
    .filter-group {
        width: 100%;
    }
    
    .filter-group select {
        flex: 1;
    }
    
    .order-card__header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .order-card__details {
        grid-template-columns: 1fr;
    }
    
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    }
    
    .product-item__image {
        height: 120px;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?> 