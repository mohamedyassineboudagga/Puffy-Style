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

    .terms-section { margin-bottom: 30px; }
    .terms-section h3 { color: #7B61FF; font-size: 18px; margin-bottom: 10px; font-weight: 700; }
    .terms-section ul { padding-left: 20px; margin-top: 10px;}
    .terms-section a { color: #7B61FF; text-decoration: none; font-weight: 600; }
</style>

<div class="page-hero">
    <h1>Terms of Use</h1>
    <p>Last updated: January 29, 2026</p>
</div>

<div class="content-container">
    <div class="terms-section">
        <h3>1. Introduction</h3>
        <p>Welcome to the Puffy Style website. By accessing this website, you agree to be bound by these terms of use, all applicable laws and regulations, and agree that you are responsible for compliance with any applicable local laws.</p>
    </div>

    <div class="terms-section">
        <h3>2. Use of the Site</h3>
        <p>The content of this site is provided for informational and commercial purposes. You agree not to use this site for any unlawful purpose or any purpose prohibited by these terms (e.g., hacking, spamming, data collection).</p>
    </div>

    <div class="terms-section">
        <h3>3. Products and Orders</h3>
        <p>Our product offers are valid as long as they are visible on the site, subject to stock availability. Prices are indicated in Tunisian Dinar (TND).</p>
        <ul>
            <li>Orders are firm and final after validation.</li>
            <li>We reserve the right to refuse any abnormal order or order placed in bad faith.</li>
        </ul>
    </div>

    <div class="terms-section">
        <h3>4. Intellectual Property</h3>
        <p>All elements of the Puffy Style site (texts, images, logos) are protected by copyright. Any total or partial reproduction is strictly prohibited without our consent.</p>
    </div>

    <div class="terms-section">
        <h3>5. Limitation of Liability</h3>
        <p>Puffy Style cannot be held liable for any direct or indirect damage resulting from the use of this site. The products sold comply with current legislation.</p>
    </div>

    <div class="terms-section">
        <h3>6. Applicable Law</h3>
        <p>Any dispute relating to the use of the Puffy Style site is subject to Tunisian law.</p>
    </div>

    <div class="terms-section">
        <h3>7. Contact</h3>
        <p>For any questions regarding these terms, please contact us via the Contact page or by email at <a href="mailto:support@puffystyle.com">support@puffystyle.com</a>.</p>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>