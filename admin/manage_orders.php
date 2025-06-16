<?php
session_start();
require_once '../includes/header.php';
require_once '../db_conn/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Process status update if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $order_id = $_POST['order_id'];
        $new_status = $_POST['new_status'];
        
        $update_stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
        $update_stmt->execute([$new_status, $order_id]);
        
        // Redirect immediately
        echo '<meta http-equiv="refresh" content="0;url=/PLSPcart/admin/manage_orders.php">';
        exit();
    }
    
    if (isset($_POST['delete_order'])) {
        $order_id = $_POST['order_id'];
        
        // First delete order items
        $delete_items_stmt = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
        $delete_items_stmt->execute([$order_id]);
        
        // Then delete the order
        $delete_order_stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        $delete_order_stmt->execute([$order_id]);
        
        // Redirect to avoid form resubmission
        echo '<meta http-equiv="refresh" content="0;url=/PLSPcart/admin/manage_orders.php">';
        exit();
    }
}

// Build the base query
$query = "
    SELECT 
        o.*, 
        u.username as customer_name,
        u.email as customer_email,
        COUNT(oi.id) as total_items,
        SUM(oi.quantity * oi.price) as calculated_total,
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
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE 1=1
";

$params = [];

// Add search filter if specified
if ($search_query) {
    $query .= " AND (o.id LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR o.payment_method LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

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
            <h1 class="page__title">Order Management</h1>

            <!-- Filters -->
            <div class="orders-filters">
                <form method="GET" class="filters-form">
                    <div class="filter-group">
                        <label for="search">Search:</label>
                        <input type="text" name="search" id="search" placeholder="Order ID, Customer, Payment" 
                               value="<?= htmlspecialchars($search_query) ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="status">Status:</label>
                        <select name="status" id="status">
                            <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                            <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="processing" <?= $status_filter === 'processing' ? 'selected' : '' ?>>Processing</option>
                            <option value="shipped" <?= $status_filter === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                            <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="date">Date Range:</label>
                        <select name="date" id="date">
                            <option value="" <?= $date_filter === '' ? 'selected' : '' ?>>All Time</option>
                            <option value="today" <?= $date_filter === 'today' ? 'selected' : '' ?>>Today</option>
                            <option value="week" <?= $date_filter === 'week' ? 'selected' : '' ?>>Last 7 Days</option>
                            <option value="month" <?= $date_filter === 'month' ? 'selected' : '' ?>>Last 30 Days</option>
                        </select>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn btn--primary">Apply Filters</button>
                        <a href="manage_orders.php" class="btn btn--secondary">Reset</a>
                    </div>
                </form>
            </div>

            <?php if (empty($orders)): ?>
                <div class="no-orders">
                    <p>No orders found matching your criteria.</p>
                </div>
            <?php else: ?>
                <div class="orders-list">
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card">
                            <div class="order-card__header">
                                <div class="order-card__info">
                                    <h3>Order #<?= str_pad($order['id'], 8, '0', STR_PAD_LEFT) ?></h3>
                                    <p class="order-meta">
                                        <span class="order-date"><?= date('M j, Y g:i A', strtotime($order['created_at'])) ?></span>
                                        <?php if ($order['created_at'] != $order['updated_at']): ?>
                                            <span class="order-updated">(updated <?= date('M j, Y g:i A', strtotime($order['updated_at'])) ?>)</span>
                                        <?php endif; ?>
                                    </p>
                                    <p class="customer-info">
                                        <strong>Customer:</strong> 
                                        <?= htmlspecialchars($order['customer_name']) ?> 
                                        (<?= htmlspecialchars($order['customer_email']) ?>)
                                    </p>
                                </div>
                                <div class="order-card__status">
                                    <form method="POST" class="status-form">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <select name="new_status" class="status-select">
                                            <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                                            <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                            <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                            <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                        <button type="submit" name="update_status" class="btn btn--small">Update</button>
                                    </form>
                                    <span class="status-badge status-<?= $order['status'] ?>">
                                        Current: <?= ucfirst($order['status']) ?>
                                    </span>
                                </div>
                            </div>

                            <div class="order-card__body">
                                <div class="order-card__details">
                                    <div class="detail-item">
                                        <span class="label">Order ID:</span>
                                        <span class="value"><?= $order['id'] ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">User ID:</span>
                                        <span class="value"><?= $order['user_id'] ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Total Items:</span>
                                        <span class="value"><?= $order['total_items'] ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Order Total:</span>
                                        <span class="value">₱<?= number_format($order['total_amount'], 2) ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Calculated Total:</span>
                                        <span class="value">₱<?= number_format($order['calculated_total'], 2) ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Payment Method:</span>
                                        <span class="value"><?= ucwords(str_replace('_', ' ', $order['payment_method'])) ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Payment Status:</span>
                                        <span class="value"><?= ucfirst($order['payment_status']) ?></span>
                                    </div>
                                    <div class="detail-item full-width">
                                        <span class="label">Shipping Address:</span>
                                        <span class="value"><?= nl2br(htmlspecialchars($order['address'])) ?></span>
                                    </div>
                                </div>

                                <div class="order-card__products">
                                    <p class="products-label">Ordered Items (<?= $order['total_items'] ?>):</p>
                                    <div class="products-grid">
                                        <?php 
                                        if ($order['product_details']) {
                                            $products = explode('||', $order['product_details']);
                                            foreach ($products as $product) {
                                                list($name, $image, $quantity, $price) = explode('|', $product);
                                        ?>
                                            <div class="product-item">
                                                <img src="../assets/images/product_images/<?= htmlspecialchars($image) ?>" 
                                                     alt="<?= htmlspecialchars($name) ?>" 
                                                     class="product-item__image">
                                                <div class="product-item__details">
                                                    <h4 class="product-item__name"><?= htmlspecialchars($name) ?></h4>
                                                    <p class="product-item__quantity">Qty: <?= $quantity ?></p>
                                                    <p class="product-item__price">₱<?= number_format($price, 2) ?></p>
                                                    <p class="product-item__subtotal">Subtotal: ₱<?= number_format($quantity * $price, 2) ?></p>
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
                                <div class="order-actions">
                                    <form method="POST" class="delete-form">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <button type="submit" name="delete_order" class="btn btn--danger" 
                                                onclick="return confirm('Are you sure you want to delete this order? This cannot be undone.');">
                                            Delete Order
                                        </button>
                                    </form>
                                    <a href="order_details.php?order_id=<?= $order['id'] ?>" class="btn btn--secondary">View Details</a>
                                    <!-- <a href="generate_invoice.php?order_id=<?= $order['id'] ?>" class="btn btn--primary">Generate Invoice</a> -->
                                </div>
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
    max-width: 1400px;
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
    align-items: flex-end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-group label {
    font-weight: 500;
    color: #495057;
    font-size: 0.9rem;
}

.filter-group input,
.filter-group select {
    padding: 0.5rem;
    border: 1px solid #ced4da;
    border-radius: 4px;
    background-color: white;
    min-width: 200px;
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
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 1rem;
}

.order-card__info h3 {
    margin: 0;
    color: #212529;
    font-size: 1.1rem;
}

.order-date, .customer-info {
    margin: 0.25rem 0 0;
    color: #6c757d;
    font-size: 0.9rem;
}

.order-card__status {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    align-items: flex-end;
}

.status-form {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.status-select {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    border: 1px solid #ced4da;
}

.btn--small {
    padding: 0.25rem 0.75rem;
    font-size: 0.8rem;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-processing { background: #cce5ff; color: #004085; }
.status-shipped { background: #e2e3e5; color: #383d41; }
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
    word-break: break-word;
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
}

.order-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}

@media (max-width: 768px) {
    .filters-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-group {
        width: 100%;
    }
    
    .order-card__header {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .order-card__status {
        align-items: stretch;
    }
    
    .status-form {
        flex-direction: column;
        align-items: stretch;
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
    
    .order-actions {
        flex-direction: column;
    }
    
    .order-actions .btn {
        width: 100%;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?>