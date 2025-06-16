<?php
session_start();
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../db_conn/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /PLSPCART/auth/login.php');
    exit;
}

// Get user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

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
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $_SESSION['user_id']]);
    if ($stmt->fetch()) {
        $errors[] = 'Email already exists';
    }
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, phone = ?, address = ? WHERE id = ?");
        $stmt->execute([$name, $email, $phone, $address, $_SESSION['user_id']]);

        // Refetch updated user information
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        // Update session data
        $_SESSION['username'] = $name;
        $_SESSION['email'] = $email;

        $success = 'Profile updated successfully';
    }
}
?>

<link rel="stylesheet" href="../assets/css/account.css">

<div class="container">
    <h2 class="section__header">My Account</h2>

    <?php if ($success): ?>
        <div class="alert alert-success">Profile updated successfully.</div>
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
            <h3><?php echo htmlspecialchars($user['username']); ?></h3>
            <p><?php echo htmlspecialchars($user['email']); ?></p>

            <ul class="account-menu">
                <li class="active"><a href="account.php">My Profile</a></li>
                <li><a href="orders.php">My Orders</a></li>
                <li><a href="cart.php">My Cart</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </div>

        <div class="account-content">
            <div class="account-section">
                <h3>Profile Information</h3>
                <form method="post">
                    <div class="form-group">
                        <label for="name">Username</label>
                        <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($user['username']); ?>" required>
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
        </div>
    </div>
</div>

