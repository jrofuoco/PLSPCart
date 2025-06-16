// Navigation scroll effect
window.addEventListener('scroll', function() {
    const nav = document.querySelector('nav');
    if (window.scrollY > 50) {
        nav.classList.add('scrolled');
    } else {
        nav.classList.remove('scrolled');
    }
});

// Mobile Menu Toggle
document.addEventListener('DOMContentLoaded', function() {
    const menuBtn = document.getElementById('menu-btn');
    const navLinks = document.getElementById('nav-links');

    if (menuBtn && navLinks) {
        menuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            navLinks.classList.toggle('show');
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!navLinks.contains(e.target) && !menuBtn.contains(e.target)) {
                navLinks.classList.remove('show');
            }
        });

        // Close menu when clicking a link
        navLinks.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function() {
                navLinks.classList.remove('show');
            });
        });
    }

    // Add active class to current page link
    const currentPath = window.location.pathname;
    document.querySelectorAll('.nav__links a').forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            link.classList.add('active');
        }
    });
});

// Toast notification system
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);

    // Trigger animation
    setTimeout(() => toast.classList.add('show'), 100);

    // Remove toast after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Add to cart animation
document.querySelectorAll('.add-to-cart').forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        const productId = this.getAttribute('data-product-id');
        
        // Add loading state
        this.classList.add('loading');
        
        fetch(`customer/cart.php?action=add&id=${productId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update cart count
                    const cartCount = document.querySelector('.cart__count');
                    if (cartCount) {
                        cartCount.textContent = data.cartCount;
                        cartCount.style.animation = 'none';
                        cartCount.offsetHeight; // Trigger reflow
                        cartCount.style.animation = 'bounce 0.5s ease';
                    }
                    
                    showToast('Product added to cart!');
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error adding product to cart', 'error');
            })
            .finally(() => {
                this.classList.remove('loading');
            });
    });
});

// Quantity input validation
document.querySelectorAll('.cart-quantity input').forEach(input => {
    input.addEventListener('change', function() {
        const max = parseInt(this.getAttribute('max'));
        const value = parseInt(this.value);
        
        if (value > max) {
            this.value = max;
            showToast(`Only ${max} items available`, 'warning');
        } else if (value < 1) {
            this.value = 1;
        }
    });
});

// Form validation
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        let valid = true;
        const inputs = this.querySelectorAll('input[required], textarea[required]');
        
        inputs.forEach(input => {
            if (!input.value.trim()) {
                valid = false;
                input.classList.add('error');
                
                // Add error message
                let errorMsg = input.nextElementSibling;
                if (!errorMsg || !errorMsg.classList.contains('error-message')) {
                    errorMsg = document.createElement('div');
                    errorMsg.className = 'error-message';
                    input.parentNode.insertBefore(errorMsg, input.nextSibling);
                }
                errorMsg.textContent = 'This field is required';
            } else {
                input.classList.remove('error');
                const errorMsg = input.nextElementSibling;
                if (errorMsg && errorMsg.classList.contains('error-message')) {
                    errorMsg.remove();
                }
            }
        });
        
        if (!valid) {
            e.preventDefault();
            showToast('Please fill in all required fields', 'error');
        }
    });
});

// Image lazy loading
document.addEventListener('DOMContentLoaded', function() {
    const images = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                observer.unobserve(img);
            }
        });
    });

    images.forEach(img => imageObserver.observe(img));
});

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Product image zoom
document.querySelectorAll('.product__image').forEach(container => {
    container.addEventListener('mousemove', function(e) {
        const img = this.querySelector('img');
        const rect = this.getBoundingClientRect();
        const x = (e.clientX - rect.left) / rect.width;
        const y = (e.clientY - rect.top) / rect.height;
        
        img.style.transformOrigin = `${x * 100}% ${y * 100}%`;
    });
    
    container.addEventListener('mouseleave', function() {
        const img = this.querySelector('img');
        img.style.transformOrigin = 'center';
    });
});

// Initialize Swiper for testimonials
const testimonialSwiper = new Swiper('.testimonials-swiper', {
    loop: true,
    autoplay: {
        delay: 5000,
        disableOnInteraction: false,
    },
    pagination: {
        el: '.swiper-pagination',
        clickable: true,
    },
    breakpoints: {
        768: {
            slidesPerView: 2,
            spaceBetween: 20,
        },
        992: {
            slidesPerView: 3,
            spaceBetween: 30,
        }
    }
});

// Lightbox functionality
function initLightbox() {
    const galleryItems = document.querySelectorAll('.gallery-item img, .image-grid-item img');
    const lightbox = document.createElement('div');
    lightbox.className = 'lightbox';
    document.body.appendChild(lightbox);

    galleryItems.forEach(img => {
        img.addEventListener('click', function() {
            const lightboxImg = document.createElement('img');
            lightboxImg.src = this.src;
            lightbox.innerHTML = '';
            lightbox.appendChild(lightboxImg);
            lightbox.classList.add('active');
        });
    });

    lightbox.addEventListener('click', function() {
        this.classList.remove('active');
    });
}

// Initialize lightbox when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initLightbox();
});

// Image lazy loading with Intersection Observer
function initLazyLoading() {
    const images = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.add('image-loading');
                img.onload = () => {
                    img.classList.remove('image-loading');
                };
                observer.unobserve(img);
            }
        });
    });

    images.forEach(img => imageObserver.observe(img));
}

// Initialize lazy loading
document.addEventListener('DOMContentLoaded', function() {
    initLazyLoading();
});

// Image hover effects
function initImageHoverEffects() {
    const imageContainers = document.querySelectorAll('.image-container');
    
    imageContainers.forEach(container => {
        const img = container.querySelector('img');
        if (!img) return;

        container.addEventListener('mousemove', function(e) {
            const rect = this.getBoundingClientRect();
            const x = (e.clientX - rect.left) / rect.width;
            const y = (e.clientY - rect.top) / rect.height;
            
            img.style.transformOrigin = `${x * 100}% ${y * 100}%`;
        });
        
        container.addEventListener('mouseleave', function() {
            img.style.transformOrigin = 'center';
        });
    });
}

// Initialize image hover effects
document.addEventListener('DOMContentLoaded', function() {
    initImageHoverEffects();
});

// FAQ Accordion
function initFAQAccordion() {
    const faqItems = document.querySelectorAll('.faq__item');
    
    faqItems.forEach(item => {
        const question = item.querySelector('.faq__question');
        
        question.addEventListener('click', () => {
            // Close other items
            faqItems.forEach(otherItem => {
                if (otherItem !== item && otherItem.classList.contains('active')) {
                    otherItem.classList.remove('active');
                }
            });
            
            // Toggle current item
            item.classList.toggle('active');
        });
    });
}

// Initialize FAQ accordion when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    initFAQAccordion();
});