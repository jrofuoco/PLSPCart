<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../db_conn/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/footer.php';

// Get user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get recent orders
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

$success = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle profile update
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    
    // Validate inputs
    if (empty($name)) {
        $errors[] = 'Name is required';
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    // Check if email already exists (excluding current user)
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $stmt->execute([$email, $_SESSION['user_id']]);
    if ($stmt->fetch()) {
        $errors[] = 'Email already exists';
    }
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE user_id = ?");
        $stmt->execute([$name, $email, $phone, $address, $_SESSION['user_id']]);
        
        // Update session data
        $_SESSION['name'] = $name;
        $_SESSION['email'] = $email;
        
        $success = 'Profile updated successfully';
    }
}
?>

<div class="container">
    <h2 class="section__header">My Account</h2>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="account-grid">
        <div class="account-sidebar">
            <div class="account-avatar">
                <i class="ri-user-line"></i>
            </div>
            <h3><?php echo htmlspecialchars($user['name']); ?></h3>
            <p><?php echo htmlspecialchars($user['email']); ?></p>
            
            <ul class="account-menu">
                <li class="active"><a href="/PLSPCART/customer/account.php">My Profile</a></li>
                <li><a href="/PLSPCART/customer/orders.php">My Orders</a></li>
                <li><a href="/PLSPCART/customer/cart.php">My Cart</a></li>
                <li><a href="/PLSPCART/auth/logout.php">Logout</a></li>
            </ul>
        </div>
        
        <div class="account-content">
            <div class="account-section">
                <h3>Profile Information</h3>
                <form method="post">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea name="address" id="address" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn">Update Profile</button>
                </form>
            </div>
            
            <div class="account-section">
                <h3>Recent Orders</h3>
                <?php if (empty($orders)): ?>
                    <p>You haven't placed any orders yet.</p>
                <?php else: ?>
                    <div class="orders-list">
                        <?php foreach ($orders as $order): ?>
                            <div class="order-card">
                                <div class="order-header">
                                    <span class="order-id">Order #<?php echo $order['order_id']; ?></span>
                                    <span class="order-date"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
                                    <span class="order-status <?php echo strtolower($order['status']); ?>">
                                        <?php echo $order['status']; ?>
                                    </span>
                                </div>
                                <div class="order-footer">
                                    <span class="order-total">₱<?php echo number_format($order['total_amount'], 2); ?></span>
                                    <a href="/PLSPCART/customer/orders.php?order_id=<?php echo $order['order_id']; ?>" class="btn btn-sm">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="view-all">
                        <a href="/PLSPCART/customer/orders.php" class="btn">View All Orders</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>