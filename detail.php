<?php
require_once 'db_conn/database.php';
require_once 'includes/functions.php';

session_start();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$view = isset($_GET['view']) ? $_GET['view'] : 'products';

if (!$id) {
    header('Location: products.php');
    exit;
}

// Determine which table to use
$table_name = $view === 'buy-sell' ? 'buy_and_sell' : 'products';

// Get item details
$sql = "SELECT p.*, c.name as category_name, u.username as seller_name, u.email as seller_email, u.phone as seller_phone 
        FROM $table_name p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN users u ON p.seller_id = u.id 
        WHERE p.id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    header('Location: products.php');
    exit;
}

// Get related items from the same category
$sql = "SELECT p.*, c.name as category_name 
        FROM $table_name p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.category_id = ? AND p.id != ? 
        ORDER BY p.created_at DESC 
        LIMIT 3";

$stmt = $pdo->prepare($sql);
$stmt->execute([$item['category_id'], $id]);
$related_items = $stmt->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<main class="container py-5">

    <div class="row g-4">
        <!-- Item Image -->
        <div class="col-md-6">
            <div class="card h-100 border-0 shadow-sm">
                <img src="assets/images/<?= $view === 'buy-sell' ? 'buy_and_sell' : 'product_images' ?>/<?= htmlspecialchars($item['image']) ?>"
                     class="img-fluid rounded-top w-100"
                     style="object-fit: cover; max-height: 400px;"
                     alt="<?= htmlspecialchars($item['name']) ?>"
                     onerror="this.src='assets/images/<?= $view === 'buy-sell' ? 'buy_and_sell' : 'product_images' ?>/default.jpg'">
            </div>
        </div>

        <!-- Item Details (all in one card) -->
        <div class="col-md-6">
            <div class="card h-100 border-0 shadow-sm d-flex flex-column">
                <div class="card-body d-flex flex-column">
                    <h1 class="h2 mb-3 fw-bold"><?= htmlspecialchars($item['name']) ?></h1>
                    <div class="d-flex align-items-center mb-3">
                        <span class="badge bg-light text-dark me-2"><?= htmlspecialchars($item['category_name']) ?></span>
                        <small class="text-muted">Posted <?= date('M d, Y', strtotime($item['created_at'])) ?></small>
                    </div>
                    <h2 class="text-success mb-4 fw-bold"><?= formatPrice($item['price']) ?></h2>
                    <div class="mb-4">
                        <h5 class="mb-2"><i class="fas fa-user-circle me-2"></i>Seller Information</h5>
                        <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($item['seller_name']) ?></p>
                        <?php if ($item['seller_email']): ?>
                            <p class="mb-1"><strong>Email:</strong> <a href="mailto:<?= htmlspecialchars($item['seller_email']) ?>" class="text-decoration-none"><?= htmlspecialchars($item['seller_email']) ?></a></p>
                        <?php endif; ?>
                        <?php if ($item['seller_phone']): ?>
                            <p class="mb-1"><strong>Phone:</strong> <a href="tel:<?= htmlspecialchars($item['seller_phone']) ?>" class="text-decoration-none"><?= htmlspecialchars($item['seller_phone']) ?></a></p>
                        <?php endif; ?>
                    </div>
                    <div class="mb-4">
                        <h5 class="mb-2"><i class="fas fa-info-circle "></i>Description</h5>
                        <p><?= nl2br(htmlspecialchars($item['description'])) ?></p>
                    </div>
                    <div class="mt-auto">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <div class="row gy-2 gx-3 flex-nowrap flex-md-wrap justify-content-center">
                                <?php if ($view === 'products'): ?>
                                    <!-- Add to Cart Button -->
                                    <div class="col-12 col-md-4 d-flex">
                                        <button type="button" class="btn btn-success w-100 py-3 add-to-cart d-flex align-items-center justify-content-center"
                                                data-product-id="<?= $item['id'] ?>"
                                                <?= ($item['stock'] ?? 0) <= 0 ? 'disabled' : '' ?>>
                                            <i class="ri-shopping-cart-line me-2"></i> Add to Cart
                                        </button>
                                    </div>
                                    <!-- Buy Now Button -->
                                    <div class="col-12 col-md-4 d-flex">
                                        <a href="checkout.php?buy_now=<?= $item['id'] ?>"
                                           class="btn btn-outline-success w-100 py-3 d-flex align-items-center justify-content-center <?= ($item['stock'] ?? 0) <= 0 ? 'disabled' : '' ?>">
                                            <i class="ri-flashlight-line me-2"></i> Buy Now
                                        </a>
                                    </div>
                                    <!-- Contact Seller Button -->
                                    <div class="col-12 col-md-4 d-flex">
                                        <a href="https://mail.google.com/mail/?view=cm&to=<?= htmlspecialchars($item['seller_email'] ?? '') ?>&su=Inquiry about <?= rawurlencode($item['name']) ?>"
                                           target="_blank"
                                           class="btn btn-outline-primary w-100 py-3 d-flex align-items-center justify-content-center">
                                            <i class="ri-customer-service-2-line me-2"></i> Contact Seller
                                        </a>
                                    </div>
                                    <?php if (($item['stock'] ?? 0) <= 0): ?>
                                        <div class="col-12">
                                            <div class="alert alert-warning mb-0 mt-2 text-center">
                                                <i class="ri-error-warning-line me-2"></i>Out of Stock
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <!-- Add to Cart Button (Disabled for Buy & Sell) -->
                                    <div class="col-12 col-md-4 d-flex">
                                        <button type="button" class="btn btn-secondary w-100 py-3 d-flex align-items-center justify-content-center" disabled>
                                            <i class="ri-shopping-cart-line me-2"></i> Add to Cart
                                        </button>
                                    </div>
                                    <!-- Buy Now Button (Disabled for Buy & Sell) -->
                                    <div class="col-12 col-md-4 d-flex">
                                        <button type="button" class="btn btn-secondary w-100 py-3 d-flex align-items-center justify-content-center" disabled>
                                    <div class="col">
                                        <button type="button" class="btn btn-secondary w-100 py-3" disabled>
                                            <i class="ri-flashlight-line me-2"></i>Buy Now
                                        </button>
                                    </div>
                                    <!-- Contact Seller Button (Primary for Buy & Sell) -->
                                    <div class="col">
                                        <a href="https://mail.google.com/mail/?view=cm&to=<?= htmlspecialchars($item['seller_email']) ?>&su=Inquiry about <?= rawurlencode($item['name']) ?>" 
                                           target="_blank" 
                                           class="btn btn-success w-100 py-3">
                                            <i class="ri-customer-service-2-line me-2"></i>Contact Seller
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info d-flex align-items-center">
                                <i class="fas fa-info-circle me-2"></i>
                                <div>Please <a href="auth/login.php" class="alert-link">login</a> to <?= $view === 'products' ? 'purchase this item' : 'contact the seller' ?>.</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($related_items)): ?>
        <div class="mt-5">
            <h3 class="h4 mb-4 fw-bold">Related Items</h3>
            <div class="row g-4">
                <?php foreach ($related_items as $related): ?>
                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <img src="assets/images/<?= $view === 'buy-sell' ? 'buy_and_sell' : 'product_images' ?>/<?= htmlspecialchars($related['image']) ?>" 
                                 class="card-img-top" 
                                 style="height: 200px; object-fit: cover;"
                                 alt="<?= htmlspecialchars($related['name']) ?>"
                                 onerror="this.src='assets/images/<?= $view === 'buy-sell' ? 'buy_and_sell' : 'product_images' ?>/default.jpg'">
                            <div class="card-body">
                                <h5 class="card-title h6 fw-bold"><?= htmlspecialchars($related['name']) ?></h5>
                                <p class="card-text text-muted small"><?= htmlspecialchars($related['category_name']) ?></p>
                                <p class="card-text text-success fw-bold mb-3"><?= formatPrice($related['price']) ?></p>
                                <a href="detail.php?id=<?= $related['id'] ?>&view=<?= $view ?>" class="btn btn-outline-success w-100">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</main>

<!-- Contact Modal -->
<?php if (isset($_SESSION['user_id'])): ?>
<div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold" id="contactModalLabel">Contact Seller</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="contactForm">
                    <div class="mb-3">
                        <label for="message" class="form-label">Your Message</label>
                        <textarea class="form-control" id="message" rows="4" placeholder="Write your message to the seller..." required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success px-4" onclick="sendMessage()">Send Message</button>
            </div>
        </div>
    </div>
</div>

<script>
function sendMessage() {
    const message = document.getElementById('message').value;
    if (!message.trim()) {
        alert('Please enter a message');
        return;
    }
    
    // Here you would typically send the message to the server
    // For now, we'll just show an alert
    alert('Message sent! The seller will contact you soon.');
    bootstrap.Modal.getInstance(document.getElementById('contactModal')).hide();
}
</script>
<?php endif; ?>
<script>
document.querySelectorAll('.add-to-cart').forEach(button => {
    button.addEventListener('click', function () {
        const productId = this.getAttribute('data-product-id');

        fetch('add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `product_id=${productId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Item added to cart!');
            } else {
                alert('Failed to add to cart: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Something went wrong.');
        });
    });
});
</script>

<style>
.toast {
    z-index: 1050;
}
.btn.loading {
    position: relative;
    pointer-events: none;
    opacity: 0.8;
}
.btn.loading::after {
    content: '';
    position: absolute;
    width: 1rem;
    height: 1rem;
    top: calc(50% - 0.5rem);
    right: 1rem;
    border: 2px solid #fff;
    border-right-color: transparent;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
@keyframes bounce {
    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
    40% { transform: translateY(-10px); }
    60% { transform: translateY(-5px); }
}
</style>

<?php include 'includes/footer.php'; ?> 