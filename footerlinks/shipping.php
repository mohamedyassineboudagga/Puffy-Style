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

    .shipping-section { margin-bottom: 35px; }
    .shipping-section h3 { color: #7B61FF; font-size: 18px; margin-bottom: 15px; font-weight: 700; }
    
    .process-grid {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 30px 0;
        text-align: center;
        flex-wrap: wrap;
        gap: 20px;
    }
    .process-step {
        flex: 1;
        min-width: 120px;
    }
    .process-icon {
        font-size: 32px;
        margin-bottom: 10px;
        display: inline-block;
        background: #F9F9FB;
        width: 70px;
        height: 70px;
        line-height: 70px;
        border-radius: 50%;
        color: #7B61FF;
    }
    .process-step h4 { font-size: 14px; color: #333; margin: 0 0 5px 0; }
    .process-step p { font-size: 12px; margin: 0; color: #888; }
    
    .process-arrow { color: #DDD; font-size: 20px; }

    .shipping-list { padding-left: 20px; }
    .shipping-list li { margin-bottom: 10px; }
    .shipping-list li strong { color: #333; }
    .badge-free { color: #28a745; font-weight: 700; }
</style>

<div class="page-hero">
    <h1>Shipping & Delivery</h1>
    <p>Fast, reliable, and everywhere in Tunisia.</p>
</div>

<div class="content-container">
    <div class="shipping-section">
        <h3>Delivery Process</h3>
        <p>As soon as you confirm your order, our team takes care of everything. Here is how it works:</p>
        
        <div class="process-grid">
            <div class="process-step">
                <div class="process-icon">📝</div>
                <h4>Validation</h4>
                <p>Order received</p>
            </div>
            <div class="process-arrow">➔</div>
            <div class="process-step">
                <div class="process-icon">📦</div>
                <h4>Preparation</h4>
                <p>Packed</p>
            </div>
            <div class="process-arrow">➔</div>
            <div class="process-step">
                <div class="process-icon">🚀</div>
                <h4>Shipping</h4>
                <p>Handed to courier</p>
            </div>
            <div class="process-arrow">➔</div>
            <div class="process-step">
                <div class="process-icon">🏠</div>
                <h4>Delivery</h4>
                <p>At your door!</p>
            </div>
        </div>
    </div>

    <div class="shipping-section">
        <h3>⚡ Delivery Times</h3>
        <p>We generally deliver within <strong>24 to 48 working hours</strong> in Greater Tunis and coastal areas, and up to 72 hours for inland areas.</p>
    </div>

    <div class="shipping-section">
        <h3>Shipping Rates</h3>
        <p>We work with the best delivery companies to guarantee you the best price.</p>
        <ul class="shipping-list">
            <li>Standard Order: <strong>7 TND</strong></li>
            <li>Orders over 100 TND: <span class="badge-free">FREE</span></li>
        </ul>
    </div>

    <div class="shipping-section">
        <h3>Covered Areas</h3>
        <p>We deliver <strong>all over the Tunisian territory</strong>. Whether you are in Tunis, Sousse, Sfax, Gabès, or Bizerte, your Puffy will arrive safely.</p>
    </div>

    <div class="shipping-section">
        <h3>Returns and Issues</h3>
        <p>If your package arrives damaged or if the product does not match your order, please refuse the package or contact us immediately via the Contact page.</p>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>