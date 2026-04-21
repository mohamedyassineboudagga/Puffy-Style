<style>
    footer {
        background-color: #2D2D2D;
        color: #FFFFFF;
        padding: 50px 5%;
        margin-top: 40px;
    }
    .footer-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 30px;
        max-width: 1200px;
        margin: 0 auto;
    }
    .footer-col h4 {
        font-size: 16px;
        margin-bottom: 20px;
        font-weight: 600;
    }
    .footer-col ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .footer-col ul li {
        margin-bottom: 10px;
    }
    .footer-col ul li a {
        color: #B0B0B0;
        text-decoration: none;
        font-size: 14px;
        transition: color 0.3s ease;
    }
    .footer-col ul li a:hover {
        color: #7B61FF; 
    }
    .footer-bottom {
        text-align: center;
        border-top: 1px solid #444;
        padding-top: 20px;
        margin-top: 40px;
        font-size: 12px;
        color: #777;
    }
    .map-placeholder {
        width: 100%;
        height: 120px;
        background: #eee;
        border-radius: 8px;
        overflow: hidden;
    }
    .map-iframe {
        width: 100%;
        height: 100%;
        border: none;
    }
</style>

<footer>
    <div class="footer-container">
        <div class="footer-col">
            <h4>Terms and Policies</h4>
            <ul>
                <li><a href="../footerlinks/terms.php">Terms of Use</a></li>
                <li><a href="../footerlinks/privacy.php">Privacy Policy</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>About The Store</h4>
            <ul>
                <li><a href="../footerlinks/about.php">About Us</a></li>
                <li><a href="../footerlinks/payment.php">Payment Methods</a></li>
                <li><a href="../footerlinks/shipping.php">Shipping and Handling</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Contact Us</h4>
            <ul>
                <li><a href="../footerlinks/contact.php">Contact Us</a></li>
                <li><a href="../footerlinks/faq.php">Help & FAQ</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Our Location</h4>
            <div class="map-placeholder">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3237.107847237599!2d10.586985!3d35.826273!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x12fd8b3a0237010f%3A0x4418fc1f1a3cb73f!2sPolytechnique%20Sousse!5e0!3m2!1sfr!2stn!4v1234567890123" 
                    class="map-iframe"
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>

            </div>
        </div>
    </div>
    <div class="footer-bottom">
        Puffy Style Store® 2026 - All rights reserved
    </div>
</footer>
</body>
</html>