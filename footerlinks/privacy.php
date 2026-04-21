<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/Database.php';
require_once '../includes/header.php';
?>

<style>
    .page-hero { background-color: #7B61FF; color: white; text-align: center; padding: 60px 20px; }
    .page-hero h1 { font-size: 36px; margin-bottom: 10px; font-weight: 700; }
    .page-hero p { font-size: 16px; opacity: 0.9; }
    
    .content-container {
        max-width: 900px; margin: -30px auto 60px; background: white;
        border-radius: 16px; padding: 50px; box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        position: relative; line-height: 1.7; color: #555; font-size: 14px;
    }

    .privacy-section { margin-bottom: 30px; }
    .privacy-section h3 { color: #7B61FF; font-size: 18px; margin-bottom: 10px; font-weight: 700; }
    .privacy-section ul { padding-left: 20px; margin-top: 10px;}
    .privacy-section a { color: #7B61FF; text-decoration: none; font-weight: 600; }
    .privacy-section a:hover { text-decoration: underline; }
</style>

<div class="page-hero">
    <h1>Privacy Policy</h1>
    <p>Your data is safe with us.</p>
</div>

<div class="content-container">
    <div class="privacy-section">
        <h3>1. Information Collection</h3>
        <p>We collect information when you register on our site, log into your account, make a purchase, or participate in a contest. The collected information includes your name, email address, phone number, and shipping address.</p>
    </div>

    <div class="privacy-section">
        <h3>2. Use of Information</h3>
        <p>Any of the information we collect from you may be used to:</p>
        <ul>
            <li>Personalize your experience and meet your individual needs.</li>
            <li>Improve our website.</li>
            <li>Improve customer service and your support needs.</li>
            <li>Contact you via email (order tracking, newsletter).</li>
        </ul>
    </div>

    <div class="privacy-section">
        <h3>3. E-Commerce Privacy</h3>
        <p>We are the sole owners of the information collected on this site. Your personal information will not be sold, exchanged, transferred, or given to any other company for any reason whatsoever, without your consent, other than what is necessary to fulfill a request and/or a transaction, such as to ship an order.</p>
    </div>

    <div class="privacy-section">
        <h3>4. Information Protection</h3>
        <p>We implement a variety of security measures to maintain the safety of your personal information. We use state-of-the-art encryption to protect sensitive information transmitted online (SSL Certificate).</p>
    </div>

    <div class="privacy-section">
        <h3>5. Cookies</h3>
        <p>Our cookies improve access to our site and identify repeat visitors. Furthermore, our cookies enhance the user experience by tracking and targeting their interests. However, this use of cookies is in no way linked to any personally identifiable information on our site.</p>
    </div>

    <div class="privacy-section">
        <h3>6. Your Rights</h3>
        <p>In accordance with current regulations, you have the right to access, rectify, and delete data concerning you. You can exercise this right by contacting us.</p>
    </div>

    <div class="privacy-section">
        <h3>7. Contact</h3>
        <p>For any questions regarding this privacy policy, you can contact us by email at <a href="mailto:support@puffystyle.com">support@puffystyle.com</a>.</p>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>