<?php
require_once 'db_conn/database.php';
require_once 'includes/functions.php';

session_start();

// Get featured products
$stmt = $pdo->query("SELECT * FROM products WHERE featured = TRUE ORDER BY RAND() LIMIT 4");
$featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories
$stmt = $pdo->query("SELECT * FROM categories LIMIT 6");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'includes/header.php'; ?>

<main>
    <header class="section__container header__container" style="margin-bottom: 1rem;" id="home">
        <div class="header__content" style="display: flex; align-items: center; justify-content: space-between; gap: 2rem;">
            <div class="header__image" style="flex: 1; display: flex; justify-content: center;">
                <img src="assets/images/PLSPCART_LOGO_NOBG.png" alt="header" style="max-width: 100%; height: auto;" />
            </div>
            <div class="header__text" style="flex: 1;">
                <h1>Your campus marketplace, anytime, anywhere.</h1>
                <p>Discover amazing products and services from your fellow PLSPnians</p>
                <a href="#products" class="btn">Shop Now</a>
            </div>
        </div>
    </header>

    <section class="section__container deals__container">
        <div class="deals__card">
            <h2 class="section__header">Hot 🔥 deals for you</h2>
            <p class="section__description">Online shopping for retail sales direct to consumers</p>
        </div>
        <div class="deals__card">
            <span><i class="ri-cash-line"></i></span>
            <h4>1.5% cashback</h4>
            <p>Earn a 5% cashback reward on every purchase you make!</p>
        </div>
        <div class="deals__card">
            <span><i class="ri-calendar-schedule-line"></i></span>
            <h4>30 day terms</h4>
            <p>Take advantage of our 30-day payment terms, completely interest-free!</p>
        </div>
        <div class="deals__card">
            <span><i class="ri-money-rupee-circle-line"></i></span>
            <h4>Save money</h4>
            <p>Discover unbeatable prices and save big money on our products!</p>
        </div>
    </section>

    <section class="section__container about__container" id="about">
        <div class="about__header">
            <div>
                <h2 class="section__header">About us</h2>
                <p class="section__description">
                    PLSPcart is the official e-commerce platform proudly created by and for the students of Pamantasan ng Lungsod ng San Pablo (PLSP). Our mission is to bring a smart, convenient, and community-driven shopping experience to the fingertips of every PLSPnian.
                </p>
            </div>
            <button class="btn about__btn">Learn More</button>
        </div>
        <div class="about__content">
            <div class="about__image">
            <div class="header__image" style="flex: 1; display: flex; justify-content: center;">
                <img src="assets/images/PLSPCART_LOGO_NOBG.png" alt="header" style="max-width: 100%; height: auto;" />
            </div>
            </div>
            <div class="about__grid">
                <div class="about__card">
                    <h3>1.</h3>
                    <h4>Who we are</h4>
                    <p>A community-driven marketplace for PLSP students</p>
                </div>
                <div class="about__card">
                    <h3>2.</h3>
                    <h4>What do we do</h4>
                    <p>Connect buyers and sellers within the PLSP community</p>
                </div>
                <div class="about__card">
                    <h3>3.</h3>
                    <h4>How do we help</h4>
                    <p>Provide a safe and convenient platform for campus commerce</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section__container category__container" id="categories">
        <h2 class="section__header">Categories</h2>
        <div class="category__grid">
            <?php foreach ($categories as $category): ?>
                <a href="products.php?category=<?php echo urlencode($category['name']); ?>" class="category__card" style="text-decoration: none; color: inherit; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;">
                    <div class="category__icon">
                        <i class="ri-book-2-line"></i>
                    </div>
                    <h4><?php echo htmlspecialchars($category['name']); ?></h4>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="section__container product__container" id="products">
        <h2 class="section__header">Featured Products</h2>

        <?php
        // Get sort parameter
        $sort = isset($_GET['sort']) ? $_GET['sort'] : 'random';

        // Get sort order
        $order_by = match($sort) {
            'price_low' => 'price ASC',
            'price_high' => 'price DESC',
            'name' => 'name ASC',
            default => 'RAND()'
        };

        // Get products with sorting
        $sql = "SELECT * FROM products WHERE featured = TRUE ORDER BY $order_by LIMIT 4";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <div class="product__grid">
            <?php foreach ($featured_products as $product): ?>
                <div class="product__card" style="display: flex; flex-direction: column; height: 100%; border: 1px solid #eee; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <div style="position: relative; width: 100%; padding-top: 100%; overflow: hidden;">
                        <img src="assets/images/product_images/<?php echo htmlspecialchars($product['image']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             onerror="this.src='assets/images/product_images/CAS BOOK.jpg'"
                             style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;" />
                    </div>
                    <div style="padding: 1rem; flex-grow: 1; display: flex; flex-direction: column;">
                        <h4 style="margin: 0 0 0.5rem 0; font-size: 1.1rem; line-height: 1.4;"><?php echo htmlspecialchars($product['name']); ?></h4>
                        <p style="margin: 0 0 1rem 0; font-weight: bold; color: #2c3e50;">₱<?php echo number_format($product['price'], 2); ?></p>
                        <a href="detail.php?id=<?php echo $product['id']; ?>" class="btn" style="margin-top: auto;">View Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="section__container client__container" id="testimonials">
        <div class="client__content">
            <h2 class="section__header">What Our Customers Say</h2>
            <p class="section__description">Read testimonials from our satisfied customers</p>
        </div>
        <div class="swiper">
            <div class="swiper-wrapper">
                <div class="swiper-slide">
                    <div class="client__card">
                        <img src="assets/testimonials/user1.jpg" alt="user" />
                        <p>"Great platform for buying and selling within our campus community!"</p>
                        <h4>John Doe</h4>
                        <h5>BSIT Student</h5>
                    </div>
                </div>
                <div class="swiper-slide">
                    <div class="client__card">
                        <img src="assets/testimonials/user2.jpg" alt="user" />
                        <p>"Convenient and secure transactions. Highly recommended!"</p>
                        <h4>Jane Smith</h4>
                        <h5>BSBA Student</h5>
                    </div>
                </div>
            </div>
            <div class="swiper-pagination"></div>
        </div>
    </section>

    <section class="section__container newsletter__container">
        <div class="newsletter__box">
            <h2 class="section__header">Subscribe to Our Newsletter</h2>
            <p class="section__description">Stay updated with our latest products and offers</p>
            <form class="newsletter__form">
                <input type="email" placeholder="Enter your email" required />
                <button type="submit" class="btn">Subscribe</button>
            </form>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>     