<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/Database.php';
require_once '../includes/header.php';
?>

<style>
    .page-hero {
        background-color: #7B61FF;
        color: white;
        text-align: center;
        padding: 60px 20px;
    }
    .page-hero h1 { font-size: 36px; margin-bottom: 10px; font-weight: 700; }
    .page-hero p { font-size: 16px; opacity: 0.9; }
    
    .content-container {
        max-width: 800px;
        margin: -30px auto 60px;
        background: white;
        border-radius: 16px;
        padding: 40px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        position: relative;
    }

    .faq-section { margin-bottom: 30px; }
    .faq-section h3 { font-size: 18px; color: #333; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }
    
    /* Updated FAQ Item Styling */
    .faq-item {
        background: #F9F9FB;
        border: 1px solid #EAEAEA;
        border-radius: 8px;
        margin-bottom: 10px;
        cursor: pointer;
        overflow: hidden;
        transition: border-color 0.3s;
    }
    
    .faq-item:hover, .faq-item.active { 
        border-color: #7B61FF; 
    }

    .faq-question {
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 14px;
        color: #555;
        font-weight: 500;
        user-select: none;
    }

    .faq-answer {
        max-height: 0;
        opacity: 0;
        padding: 0 20px;
        color: #777;
        font-size: 13px;
        line-height: 1.6;
        transition: all 0.3s ease-in-out;
    }

    /* Active State (When Opened) */
    .faq-item.active .faq-answer {
        max-height: 200px; /* Expands container */
        opacity: 1;
        padding-bottom: 15px; /* Adds space at the bottom */
    }

    .faq-item .plus { 
        color: #7B61FF; 
        font-weight: bold;
        font-size: 18px;
        display: inline-block;
        transition: transform 0.3s ease;
    }
    
    /* Rotate the plus to an 'x' when active */
    .faq-item.active .plus {
        transform: rotate(45deg); 
    }

    .faq-contact { margin-top: 40px; }
    .faq-contact p { font-size: 14px; color: #666; margin-bottom: 15px; }
    .btn-primary {
        background-color: #7B61FF;
        color: white;
        padding: 10px 25px;
        border-radius: 25px;
        text-decoration: none;
        font-weight: 600;
        font-size: 14px;
        display: inline-block;
        transition: 0.3s;
        border: none;
        cursor: pointer;
    }
    .btn-primary:hover { background-color: #634bc4; }
</style>

<div class="page-hero">
    <h1>Help & FAQ</h1>
    <p>Answers to your most frequently asked questions.</p>
</div>

<div class="content-container">
    <div class="faq-section">
        <h3>📦 Orders & Payment</h3>
        <div class="faq-item">
            <div class="faq-question">How do I place an order? <span class="plus">+</span></div>
            <div class="faq-answer">
                To place an order, simply browse our shop, click "Add to Cart" on the items you want, and proceed to checkout by clicking the cart icon at the top of the page.
            </div>
        </div>
        
        <div class="faq-item">
            <div class="faq-question">Can I pay on delivery? <span class="plus">+</span></div>
            <div class="faq-answer">
                Yes, we offer Cash on Delivery (COD) as a payment method for all orders within Tunisia.
            </div>
        </div>
        
        <div class="faq-item">
            <div class="faq-question">There is an error in my order, what should I do? <span class="plus">+</span></div>
            <div class="faq-answer">
                If you notice an error in your order, please contact us immediately through the "Contact Us" page or send us an email. We will fix it before it ships.
            </div>
        </div>
    </div>

    <div class="faq-section">
        <h3>🚚 Shipping</h3>
        <div class="faq-item">
            <div class="faq-question">What are the delivery times? <span class="plus">+</span></div>
            <div class="faq-answer">
                Standard delivery usually takes between 24 to 48 hours depending on your exact location.
            </div>
        </div>
        
        <div class="faq-item">
            <div class="faq-question">Do you deliver everywhere in Tunisia? <span class="plus">+</span></div>
            <div class="faq-answer">
                Yes, our delivery partners cover all regions and governorates across Tunisia.
            </div>
        </div>
    </div>

    <div class="faq-section">
        <h3>✨ Products & Usage</h3>
        <div class="faq-item">
            <div class="faq-question">Are your products original? <span class="plus">+</span></div>
            <div class="faq-answer">
                100%. We only source our products directly from the official brands or authorized premium distributors.
            </div>
        </div>
        
        <div class="faq-item">
            <div class="faq-question">How long does a Puff 9000 last? <span class="plus">+</span></div>
            <div class="faq-answer">
                A 9000 puff device can last anywhere from 1 to 3 weeks depending entirely on your personal usage habits and frequency.
            </div>
        </div>
    </div>

    <div class="faq-contact">
        <p>Couldn't find your answer?</p>
        <a href="contact.php" class="btn-primary">Contact Us</a>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const faqItems = document.querySelectorAll('.faq-item');

    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        
        question.addEventListener('click', () => {
            // Check if the clicked item is already active
            const isActive = item.classList.contains('active');
            
            // Optional: Close all other open FAQ items automatically
            faqItems.forEach(otherItem => {
                otherItem.classList.remove('active');
            });

            // If it wasn't active, open it
            if (!isActive) {
                item.classList.add('active');
            }
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>