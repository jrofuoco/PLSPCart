<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../db_conn/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/footer.php';

// Check if viewing a specific order
if (isset($_GET['order_id'])) {
    $order_id = (int)$_GET['order_id'];
    
    // Get order details
    $stmt = $pdo->prepare("SELECT o.*, u.name as customer_name 
                          FROM orders o 
                          JOIN users u ON o.user_id = u.user_id 
                          WHERE o.order_id = ? AND o.user_id = ?");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch();
    
    if (!$order) {
        redirect('/PLSPCART/customer/orders.php');
    }
    
    // Get order items
    $stmt = $pdo->prepare("SELECT oi.*, p.name, p.image_path 
                          FROM order_items oi 
                          JOIN products p ON oi.product_id = p.product_id 
                          WHERE oi.order_id = ?");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll();
    
    // Calculate totals
    $subtotal = 0;
    foreach ($order_items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    $tax = $subtotal * 0.08; // Example 8% tax
    $total = $subtotal + $tax;
    
    // Display single order view
    ?>
    <div class="container">
        <div class="order-header">
            <h2 class="section__header">Order #<?php echo $order['order_id']; ?></h2>
            <a href="/PLSPCART/customer/orders.php" class="btn btn-secondary">Back to Orders</a>
        </div>
        
        <div class="order-details">
            <div class="order-info">
                <div class="info-row">
                    <span>Order Date:</span>
                    <span><?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?></span>
                </div>
                <div class="info-row">
                    <span>Status:</span>
                    <span class="status-badge <?php echo strtolower($order['status']); ?>">
                        <?php echo $order['status']; ?>
                    </span>
                </div>
                <div class="info-row">
                    <span>Payment Method:</span>
                    <span><?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></span>
                </div>
                <div class="info-row">
                    <span>Shipping Address:</span>
                    <span><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></span>
                </div>
            </div>
            
            <div class="order-items">
                <h3>Order Items</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                            <tr>
                                <td class="product-info">
                                    <img src="/PLSPCART/assets/images/products/<?php echo htmlspecialchars($item['image_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>" width="50">
                                    <span><?php echo htmlspecialchars($item['name']); ?></span>
                                </td>
                                <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="order-summary">
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>₱<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Tax (8%):</span>
                    <span>₱<?php echo number_format($tax, 2); ?></span>
                </div>
                <div class="summary-row total">
                    <span>Total:</span>
                    <span>₱<?php echo number_format($total, 2); ?></span>
                </div>
            </div>
        </div>
    </div>
    <?php
} else {
    // Display order list
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll();
    ?>
    <div class="container">
        <h2 class="section__header">My Orders</h2>
        
        <?php if (empty($orders)): ?>
            <div class="empty-orders">
                <p>You haven't placed any orders yet.</p>
                <a href="/PLSPCART/products/" class="btn">Start Shopping</a>
            </div>
        <?php else: ?>
            <div class="orders-table">
                <table>
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): 
                            // Get item count for this order
                            $stmt = $pdo->prepare("SELECT COUNT(*) as item_count FROM order_items WHERE order_id = ?");
                            $stmt->execute([$order['order_id']]);
                            $item_count = $stmt->fetch()['item_count'];
                            ?>
                            <tr>
                                <td>#<?php echo $order['order_id']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td><?php echo $item_count; ?> item<?php echo $item_count != 1 ? 's' : ''; ?></td>
                                <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge <?php echo strtolower($order['status']); ?>">
                                        <?php echo $order['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="/PLSPCART/customer/orders.php?order_id=<?php echo $order['order_id']; ?>" 
                                       class="btn btn-sm">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

require_once __DIR__ . '/../includes/footer.php';