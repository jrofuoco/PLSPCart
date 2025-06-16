<?php
require_once '../db_conn/database.php';
require_once '../includes/functions.php';
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

// Approve or reject sell requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $action = $_POST['action'];

    if ($action === 'approve') {
        $query = "UPDATE sell_requests SET status = 'approved' WHERE id = ?";
    } elseif ($action === 'reject') {
        $query = "UPDATE sell_requests SET status = 'rejected' WHERE id = ?";
    }

    if (isset($query)) {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$id]);
    }
}

// Fetch pending sell requests
$query = "SELECT id, product_name, seller_name, status FROM sell_requests WHERE status = 'pending'";
$result = $pdo->query($query);

// Fetch products pending approval
$query = "SELECT * FROM buy_and_sell WHERE approval_status = 'pending'";
$pending_products = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../includes/header.php'; ?>

<main class="container mt-5">
    <h1 class="mb-4">Sell Approval</h1>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Request ID</th>
                    <th>Seller Name</th>
                    <th>Product Name</th>
                    <th>Quantity</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch()): ?>
                    <tr>
                        <td>#<?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['seller_name']) ?></td>
                        <td><?= htmlspecialchars($row['product_name']) ?></td>
                        <td><?= $row['quantity'] ?></td>
                        <td><span class="badge bg-warning">Pending</span></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>">
                                <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">Approve</button>
                            </form>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>">
                                <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">Reject</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <h1 class="mb-4">Products Pending Approval</h1>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Product ID</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Seller ID</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pending_products as $product): ?>
                    <tr>
                        <td>#<?= $product['id'] ?></td>
                        <td><?= htmlspecialchars($product['name']) ?></td>
                        <td><?= htmlspecialchars($product['description']) ?></td>
                        <td>₱<?= number_format($product['price'], 2) ?></td>
                        <td><?= $product['stock'] ?></td>
                        <td><?= $product['seller_id'] ?></td>
                        <td>
                            <a href="approve_product.php?id=<?= $product['id'] ?>" class="btn btn-success btn-sm">Approve</a>
                            <a href="reject_product.php?id=<?= $product['id'] ?>" class="btn btn-danger btn-sm">Reject</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<?php include '../includes/footer.php'; ?>