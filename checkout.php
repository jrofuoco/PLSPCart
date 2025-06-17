<?php
// Start session and handle all redirects before any output
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

// Handle POST request before any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    require_once 'db_conn/database.php';

    $user_id = $_SESSION['user_id'];
    $payment_method = $_POST['payment_method'];
    $shipping_address = $_POST['shipping_address'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $status = 'pending';
    $payment_status = 'pending';
    $created_at = date('Y-m-d H:i:s');
    $updated_at = $created_at;
    $order_total = 0;
    $order_items = [];

    if (isset($_GET['buy_now'])) {
        $product_id = (int)$_GET['buy_now'];
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($product) {
            $order_total = $product['price'];
            $order_items[] = [
                'product_id' => $product['id'],
                'quantity' => 1,
                'price' => $product['price']
            ];
        }
    } else {
        $cart = $_SESSION['cart'] ?? [];
        foreach ($cart as $item) {
            $order_total += $item['price'] * $item['quantity'];
            $order_items[] = [
                'product_id' => $item['id'],
                'quantity' => $item['quantity'],
                'price' => $item['price']
            ];
        }
    }

    if ($order_total > 0 && !empty($order_items)) {
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, status, payment_method, payment_status, address, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $order_total, $status, $payment_method, $payment_status, $shipping_address, $created_at, $updated_at]);
        $order_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, created_at) VALUES (?, ?, ?, ?, ?)");
        foreach ($order_items as $item) {
            $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price'], $created_at]);
            $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?")->execute([$item['quantity'], $item['product_id']]);
        }

        // Remove cart items from the database after successful checkout
        if (!isset($_GET['buy_now'])) {
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);
            unset($_SESSION['cart']);
        }

        header('Location: order_success.php?order_id=' . $order_id);
        exit;
    }
}

// Include header after processing
require_once 'includes/header.php';
require_once 'db_conn/database.php';

$buy_now_product = null;
$total = 0;

if (isset($_GET['buy_now'])) {
    $product_id = (int)$_GET['buy_now'];
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $buy_now_product = $stmt->fetch(PDO::FETCH_ASSOC);
    $total = $buy_now_product ? $buy_now_product['price'] : 0;
} else {
    $cart = $_SESSION['cart'] ?? [];
    foreach ($cart as $item) {
        $total += $item['price'] * $item['quantity'];
    }
}

function getImagePath($imageName) {
    $productImagePath = "assets/images/product_images/$imageName";
    $buyAndSellImagePath = "assets/images/buy_and_sell/$imageName";

    if (file_exists($productImagePath)) {
        return $productImagePath;
    } elseif (file_exists($buyAndSellImagePath)) {
        return $buyAndSellImagePath;
    } else {
        return "assets/images/product_images/default.jpg"; // Default image
    }
}
?>

<style>
    .cart__item img {
    width: 80px;
    height: auto;
    border-radius: 6px;
    object-fit: contain;
}

</style>

<main class="main">
    <div class="container">
        <h1 class="page__title">Checkout</h1>

        <div class="checkout__container">
            <div class="checkout__summary">
                <h2>Order Summary</h2>
                <div class="cart__items">
                    <?php if ($buy_now_product): ?>
                        <div class="cart__item">
                            <img src="<?= getImagePath(htmlspecialchars($buy_now_product['image'])) ?>" alt="<?= htmlspecialchars($buy_now_product['name']) ?>">
                            <div class="cart__item-details">
                                <h3><?= htmlspecialchars($buy_now_product['name']) ?></h3>
                                <p class="cart__item-price">₱<?= number_format($buy_now_product['price'], 2) ?></p>
                                <p class="cart__item-quantity">Quantity: 1</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($cart as $item): ?>
                            <div class="cart__item">
                                <img src="<?= getImagePath(htmlspecialchars($item['image'])) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                                <div class="cart__item-details">
                                    <h3><?= htmlspecialchars($item['name']) ?></h3>
                                    <p class="cart__item-price">₱<?= number_format($item['price'], 2) ?></p>
                                    <p class="cart__item-quantity">Quantity: <?= $item['quantity'] ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="cart__total">
                    <h3>Total: ₱<?= number_format($total, 2) ?></h3>
                </div>
            </div>

            <div class="checkout__form">
                <h2>Payment Method</h2>
                <form id="payment-form" method="POST">
                    <div class="payment-methods">
                        <div class="payment-options">
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="cash" checked>
                                <span class="payment-icon">💵</span>
                                <span class="payment-label">Cash on Delivery</span>
                            </label>
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="pay_on_counter" id="school-pickup-radio">
                                <span class="payment-icon">🏦</span>
                                <span class="payment-label">Pay on the Counter (School Pick Up)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Credit Card Fields (optional/future feature) -->
                    <div id="credit-card-details" class="payment-details" style="display: none;">
                        <div class="form__group">
                            <label for="card_number">Card Number</label>
                            <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456">
                        </div>
                        <div class="form__row">
                            <div class="form__group">
                                <label for="expiry">Expiry Date</label>
                                <input type="text" id="expiry" name="expiry" placeholder="MM/YY">
                            </div>
                            <div class="form__group">
                                <label for="cvv">CVV</label>
                                <input type="text" id="cvv" name="cvv" placeholder="123">
                            </div>
                        </div>
                    </div>

                    <div class="form__group" id="shipping-address-group">
                        <label for="shipping_address">Shipping Address</label>
                        <textarea id="shipping_address" name="shipping_address"></textarea>
                    </div>

                    <div class="form__group">
                        <label for="notes">Order Notes (Optional)</label>
                        <textarea id="notes" name="notes"></textarea>
                    </div>

                    <button type="submit" name="place_order" class="btn btn--primary">Place Order</button>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    const creditCardDetails = document.getElementById('credit-card-details');
    const shippingAddressField = document.getElementById('shipping_address');
    const shippingAddressGroup = document.getElementById('shipping-address-group');

    function toggleShippingAddress() {
        const isSchoolPickup = document.querySelector('input[name="payment_method"]:checked')?.value === 'pay_on_counter';
        shippingAddressGroup.style.display = isSchoolPickup ? 'none' : '';
        if (isSchoolPickup) {
            shippingAddressField.removeAttribute('required');
            shippingAddressField.value = 'School Pick Up';
        } else {
            shippingAddressField.setAttribute('required', 'required');
            if (shippingAddressField.value === 'School Pick Up') {
                shippingAddressField.value = '';
            }
        }
    }

    paymentMethods.forEach(radio => {
        radio.addEventListener('change', function() {
            creditCardDetails.style.display = (this.value === 'credit_card') ? 'block' : 'none';
            toggleShippingAddress();
        });
    });

    document.getElementById('payment-form').addEventListener('submit', function(e) {
        const isSchoolPickup = document.querySelector('input[name="payment_method"]:checked')?.value === 'pay_on_counter';
        if (!isSchoolPickup && !shippingAddressField.value.trim()) {
            e.preventDefault();
            alert('Please enter a shipping address');
            shippingAddressField.focus();
        }
    });

    toggleShippingAddress(); // initialize on page load
});
</script>

<?php require_once 'includes/footer.php'; ?>
