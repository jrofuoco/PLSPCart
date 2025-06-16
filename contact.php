<?php
require_once 'includes/header.php';
?>

<main>
    <div class="container">
        <h1 class="section__header">Contact Us</h1>
        
        <div class="contact__container">
            <div class="contact__info">
                <h2>Get in Touch</h2>
                <p>Have questions about our products or services? We're here to help!</p>
                
                <div class="contact__details">
                    <div class="contact__item">
                        <i class="ri-map-pin-line"></i>
                        <div>
                            <h3>Address</h3>
                            <p>Pamantasan ng Lungsod ng San Pablo</p>
                            <p>San Pablo City, Laguna</p>
                        </div>
                    </div>
                    
                    <div class="contact__item">
                        <i class="ri-mail-line"></i>
                        <div>
                            <h3>Email</h3>
                            <p>plspcart@gmail.com</p>
                        </div>
                    </div>
                    
                    <div class="contact__item">
                        <i class="ri-phone-line"></i>
                        <div>
                            <h3>Phone</h3>
                            <p>(049) 555-5555</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="contact__form">
                <h2>Send us a Message</h2>
                <form action="includes/process_contact.php" method="POST">
                    <div class="form__group">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    
                    <div class="form__group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form__group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" required>
                    </div>
                    
                    <div class="form__group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" rows="5" required></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Send Message</button>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?> 