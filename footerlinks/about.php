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
        border-radius: 16px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        position: relative; line-height: 1.6; color: #444; font-size: 14px;
    }

    .content-container h3 { color: #7B61FF; margin-top: 30px; margin-bottom: 15px; font-size: 20px; }
    .content-container h3:first-child { margin-top: 0; }
    
    /* --- UPDATED VIDEO CSS --- */
    .video-placeholder {
        width: 100%; 
        max-width: 320px; /* Narrower width for vertical videos */
        margin: 30px auto; 
        border-radius: 12px; 
        overflow: hidden; /* Keeps the video neatly inside the borders */
        background: #000;
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }

    .video-wrapper {
        width: 100%;
        display: flex;
    }

    .video-wrapper video {
        width: 100%;
        height: auto; /* Lets the video scale naturally without stretching or overflowing */
        display: block;
    }
    /* ------------------------- */

    ul.custom-list { list-style: none; padding-left: 0; }
    ul.custom-list li { margin-bottom: 10px; }
    ul.custom-list li strong { color: #333; }

    .btn-primary {
        background-color: #7B61FF; color: white; padding: 12px 30px;
        border-radius: 25px; text-decoration: none; font-weight: 600;
        display: inline-block; margin-top: 20px; transition: 0.3s;
    }
    .btn-primary:hover { background-color: #634bc4; }
</style>

<div class="page-hero">
    <h1>About Us</h1>
    <p>Discover the story behind Puffy Style</p>
</div>

<div class="content-container">
    <h3>Our Story</h3>
    <p>Founded in 2024, <strong>Puffy Style</strong> was born from a simple passion: to offer the best vaping experience in Tunisia. We noticed it was difficult to find authentic, varied, and affordable products. That's why we decided to launch our own platform.</p>
    <p>What started as a small local shop has become an online reference for lovers of fruity and icy flavors.</p>

    <div class="video-placeholder">
        <div class="video-wrapper">
            <video controls>
                <source src="../photos/vid.mp4" type="video/mp4">
            </video>
        </div>
    </div>

    <h3>Our Mission</h3>
    <p>Our goal is simple: <strong>satisfaction above all</strong>. We strictly select our suppliers to ensure that every Puff sold on our site meets quality and safety standards.</p>
    <ul class="custom-list">
        <li>🚀 <strong>Speed:</strong> Express delivery everywhere in Tunisia.</li>
        <li>💎 <strong>Quality:</strong> Certified and authentic products.</li>
        <li>🎧 <strong>Service:</strong> A responsive team available 7 days a week.</li>
    </ul>

    <h3>Why choose us?</h3>
    <p>At Puffy Style, you are not just an order number. We treat every customer with care. We believe in a relationship of trust and we are committed to providing you with the latest market releases before anyone else.</p>
    
    <div style="text-align: center;">
        <a href="../pages/shop.php" class="btn-primary">View our products</a>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>