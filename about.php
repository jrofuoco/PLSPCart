<?php
require_once 'includes/header.php';
?>

<main>
    <div class="container">
        <h1 class="section__header">About PLSPCART</h1>
        
        <div class="about__container">
            <section class="about__section">
                <h2>Our Mission</h2>
                <p>PLSPCART is dedicated to providing a seamless and secure marketplace platform for the PLSP community. We aim to facilitate easy buying and selling of products within our campus, fostering a sustainable and convenient shopping experience for all students, faculty, and staff.</p>
            </section>
            
            <section class="about__section">
                <h2>What We Offer</h2>
                <div class="features__grid">
                    <div class="feature__card">
                        <i class="ri-store-2-line"></i>
                        <h3>Student Marketplace</h3>
                        <p>A dedicated platform for students to buy and sell products within the campus community.</p>
                    </div>
                    
                    <div class="feature__card">
                        <i class="ri-shield-check-line"></i>
                        <h3>Secure Transactions</h3>
                        <p>Safe and secure payment processing with buyer and seller protection.</p>
                    </div>
                    
                    <div class="feature__card">
                        <i class="ri-customer-service-2-line"></i>
                        <h3>24/7 Support</h3>
                        <p>Round-the-clock customer support to assist with any queries or issues.</p>
                    </div>
                    
                    <div class="feature__card">
                        <i class="ri-truck-line"></i>
                        <h3>Campus Delivery</h3>
                        <p>Convenient delivery options within the campus premises.</p>
                    </div>
                </div>
            </section>
            
            <section class="about__section">
                <h2>Our Team</h2>
                <div class="team__grid">
                    <div class="team__member">
                        <img src="<?php echo asset_url('images/team/placeholder.jpg'); ?>" alt="Team Member">
                        <h3>John Doe</h3>
                        <p>Project Manager</p>
                    </div>
                    
                    <div class="team__member">
                        <img src="<?php echo asset_url('images/team/placeholder.jpg'); ?>" alt="Team Member">
                        <h3>Jane Smith</h3>
                        <p>Lead Developer</p>
                    </div>
                    
                    <div class="team__member">
                        <img src="<?php echo asset_url('images/team/placeholder.jpg'); ?>" alt="Team Member">
                        <h3>Mike Johnson</h3>
                        <p>UI/UX Designer</p>
                    </div>
                </div>
            </section>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?> 