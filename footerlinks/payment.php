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
        max-width: 800px; margin: -30px auto 60px; background: white;
        border-radius: 16px; padding: 50px; box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        position: relative; line-height: 1.7; color: #555; font-size: 14px;
    }

    .intro-text { text-align: center; margin-bottom: 40px; font-size: 15px; }
    .intro-text strong { color: #333; }

    .payment-method { margin-bottom: 30px; }
    .payment-method h4 { display: flex; align-items: center; gap: 10px; color: #333; font-size: 16px; margin-bottom: 8px; margin-top: 0; }
    .payment-method p { margin: 0; margin-left: 30px; }

    .important-info { margin-top: 40px; }
    .important-info h3 { color: #7B61FF; font-size: 18px; margin-bottom: 15px; font-weight: 700; }
    .important-info ul { padding-left: 20px; }
    .important-info ul li { margin-bottom: 10px; }
    .important-info ul li strong { color: #333; }

    .btn-container { text-align: center; margin-top: 40px; }
    .btn-primary {
        background-color: #7B61FF; color: white; padding: 12px 30px;
        border-radius: 25px; text-decoration: none; font-weight: 600;
        display: inline-block; transition: 0.3s;
    }
    .btn-primary:hover { background-color: #634bc4; }
</style>

<div class="page-hero">
    <h1>Payment Methods</h1>
    <p>Simple, Secure, and Flexible.</p>
</div>

<div class="content-container">
    <div class="intro-text">
        <p>At <strong>Puffy Style</strong>, we want to make your shopping easy. Choose the method that best suits you from our secure options.</p>
    </div>

    <div class="payment-method">
        <h4>🚚 Cash on Delivery</h4>
        <p>The safest way! Pay in cash directly to the delivery person when you receive your package at home.</p>
    </div>

    <div class="payment-method">
        <h4>📱 D17 & Post</h4>
        <p>Pay quickly via the D17 app or by postal order. Contact us to get the bank details (RIB).</p>
    </div>

    <div class="payment-method">
        <h4>🏦 Bank Transfer</h4>
        <p>Ideal for large orders. Your order will be shipped upon receipt of the transfer.</p>
    </div>

    <div class="important-info">
        <h3>Important Information</h3>
        <ul>
            <li>All transactions are secure.</li>
            <li>Cash on delivery is available <strong>all over Tunisia</strong>.</li>
            <li>For any questions regarding a payment, contact support.</li>
        </ul>
    </div>

    <div class="btn-container">
        <a href="../pages/shop.php" class="btn-primary">Start shopping</a>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>