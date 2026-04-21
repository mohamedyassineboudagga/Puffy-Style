<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/Database.php';
require_once '../includes/header.php';
?>

<style>
    .hero {
        background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.6)), url('../photos/index1.JPG') center/cover no-repeat;
        color: white;
        text-align: center;
        padding: 20px; 
        min-height: calc(100vh - 75px); 
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }
    .hero h1 { font-size: 42px; margin-bottom: 10px; font-weight: 700; }
    .hero p { font-size: 18px; margin-bottom: 30px; }
    .hero .btn-purple { padding: 12px 30px; font-size: 16px; border-radius: 25px; text-decoration: none; color: white; background: var(--primary-purple); font-weight: 600;}
    .hero .btn-purple:hover { background: var(--dark-purple); }

    .grid-4 { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
    .grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; }

    .feature-card img { width: 50px; margin-bottom: 15px; }
    .feature-card h3 { font-size: 16px; color: var(--text-dark); margin-bottom: 5px; }
    .feature-card p { font-size: 13px; color: var(--text-muted); margin: 0; }

    .product-card img { width: 100%; height: 200px; object-fit: contain; margin-bottom: 15px; }
    .product-card h4 { font-size: 18px; margin: 10px 0 5px 0; }
    .product-card .details { font-size: 12px; color: var(--text-muted); margin-bottom: 15px; }
    .product-card .price { color: var(--primary-purple); font-weight: 600; margin-bottom: 15px; }
    .product-card .btn-light-pink { background: #FDF2F8; color: var(--dark-purple); border: none; padding: 8px 20px; border-radius: 20px; font-size: 13px; font-weight: 600; cursor: pointer; width: 100%; margin-bottom: 10px; transition: 0.3s;}
    .product-card .btn-solid-pink { background: var(--accent-pink); color: white; border: none; padding: 8px 20px; border-radius: 20px; font-size: 13px; font-weight: 600; cursor: pointer; width: 100%; transition: 0.3s;}
    .product-card .btn-light-pink:hover { background: #FCE7F3; }
    .product-card .btn-solid-pink:hover { background: var(--dark-pink); }
    /* --- Dynamic Info Slider Section --- */
    .dynamic-info-slider {
        padding: 60px 0;
        background: #ffffff;
        overflow: hidden;
        font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    }

    /* Wrapper to clip the overflow */
    .slider-wrapper {
        max-width: 1200px;
        margin: 0 auto;
        overflow: hidden;
        position: relative;
        padding: 10px 0;
    }

    /* The moving track */
    .slider-track {
        display: flex;
        transition: transform 0.8s cubic-bezier(0.45, 0.05, 0.55, 0.95);
        will-change: transform;
    }

    /* Slide item sizing */
    .info-slide {
        min-width: 50%; /* Shows 2 cards on desktop */
        padding: 0 15px;
        box-sizing: border-box;
    }

    /* Card Styling */
    .info-inner {
        display: flex;
        align-items: flex-start;
        gap: 20px;
        background: #fdfbff; 
        padding: 30px;
        border-radius: 15px;
        height: 100%;
        border: 1px solid #f0ecff;
        box-shadow: 0 4px 15px rgba(123, 97, 255, 0.05);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .info-inner:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(123, 97, 255, 0.12);
    }

    /* Icon Styling */
    .info-inner img {
        width: 70px;
        height: auto;
        flex-shrink: 0;
        object-fit: contain;
    }

    /* Text Content Styling */
    .info-content h3 {
        font-size: 16px;
        color: #7B61FF;
        margin-top: 0;
        margin-bottom: 12px;
        font-weight: 800;
        line-height: 1.3;
        text-transform: uppercase;
    }

    .info-content p {
        font-size: 13.5px;
        color: #666666;
        margin-bottom: 15px;
        line-height: 1.6;
    }

    /* Action Link */
    .info-content a {
        color: #7B61FF;
        font-weight: 700;
        text-decoration: none;
        font-size: 12px;
        letter-spacing: 0.5px;
        display: inline-block;
        transition: color 0.3s ease;
    }

    .info-content a:hover {
        color: #5E43DA;
        text-decoration: underline;
    }

    /* --- Responsive Layout --- */
    @media (max-width: 992px) {
        .info-slide {
            min-width: 50%; /* Still 2 cards for tablets */
        }
    }

    @media (max-width: 768px) {
        .info-slide {
            min-width: 100%; /* 1 card for mobile */
        }
        
        .info-inner {
            flex-direction: column;
            text-align: center;
            align-items: center;
            padding: 25px;
        }
        
        .info-inner img {
            margin-bottom: 10px;
            width: 60px;
        }
    }
    .flavor-categories-grid { 
        display: grid; 
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
        gap: 20px; 
        text-align: center; 
        margin-bottom: 40px; 
    }
    .flavor-cat-img { 
        width: 100%; 
        height: 90px; 
        object-fit: cover; 
        border-radius: 12px; 
        margin-bottom: 10px; 
        box-shadow: 0 2px 8px rgba(0,0,0,0.1); 
    }
    .flavor-cat-title { 
        font-weight: 500; 
        font-size: 15px; 
        color: var(--text-dark); 
    }

    .flavor-cards-grid { 
        display: grid; 
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); 
        gap: 25px; 
    }
    .flavor-card { 
        background: white; 
        border-radius: 12px; 
        padding: 30px 20px; 
        text-align: center; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
        border: 1px solid #EAEAEA; 
        transition: transform 0.2s;
    }
    .flavor-card:hover { transform: translateY(-5px); }
    
    .flavor-icon { font-size: 40px; margin-bottom: 15px; }
    .flavor-title { font-size: 16px; font-weight: 700; margin: 0 0 10px 0; }
    .flavor-desc { font-size: 12px; color: #666; min-height: 40px; margin-bottom: 20px; line-height: 1.5; }
    
    .intensity-bar-bg { 
        background: #EAEAEA; 
        height: 6px; 
        border-radius: 3px; 
        margin-bottom: 15px; 
        overflow: hidden; 
    }
    .intensity-bar-fill { height: 100%; border-radius: 3px; }
    
    .rating { font-size: 13px; margin-bottom: 25px; color: #FFC107; }
    .rating-text { color: #333; font-weight: 600; margin-left: 5px; font-size: 12px;}
    
    .btn-buy-flavor { 
        background: #FFD6E0; 
        color: var(--dark-purple); 
        border: none; 
        padding: 10px; 
        width: 100%; 
        border-radius: 20px; 
        font-weight: 600; 
        cursor: pointer; 
        transition: 0.3s; 
    }
    .btn-buy-flavor:hover { background: #FF9EBD; color: white; }

    /* Brands Section Styles - Updated to match Puffy Style */
    .brands-section {
        text-align: center;
        margin: 60px auto;
        padding: 40px 20px;
        background-color: #FAFAFB; /* Added subtle background */
        border-radius: 12px;
    }
    .brands-title {
        position: relative;
        display: inline-block;
        color: #7B61FF; /* Changed to theme purple */
        font-size: 24px; /* Slightly larger to match other sections */
        font-weight: 700;
        text-transform: capitalize; /* Changed from uppercase to match other titles */
        margin-bottom: 40px;
    }
    .brands-title::before,
    .brands-title::after {
        content: "";
        position: absolute;
        top: 50%;
        width: 100px; /* Shortened slightly for better look */
        height: 2px;
        background-color: #DCD6FF; /* Lighter purple for the lines */
    }
    .brands-title::before { right: 100%; margin-right: 20px; }
    .brands-title::after { left: 100%; margin-left: 20px; }
    
    .brands-grid {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        align-items: center;
        gap: 40px;
        max-width: 1000px;
        margin: 0 auto 30px auto;
    }
    .brands-grid img {
        max-width: 120px;
        max-height: 80px;
        object-fit: contain;
        transition: transform 0.3s;
        filter: grayscale(100%); /* Optional: makes logos grey until hover for a cleaner look */
        opacity: 0.7;
    }
    .brands-grid img:hover {
        transform: scale(1.05);
        filter: grayscale(0%);
        opacity: 1;
    }
    .btn-view-brands {
        background: #7B61FF; /* Changed to theme purple */
        color: white;
        padding: 12px 30px;
        text-decoration: none;
        font-weight: 600;
        border-radius: 25px; /* Rounded corners */
        font-size: 16px;
        display: inline-block;
        transition: 0.3s;
    }
    .btn-view-brands:hover {
        background: #5E43DA; /* Darker purple on hover */
        color: white;
    }

    /* --- NOUVELLE SECTION BANDE IMAGE --- */
    .banner-strip {
        width: 100%;
        height: 500px;
        background: url('../photos/index2.png') center/cover no-repeat fixed;
        margin: 50px 0;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    .banner-strip::before {
        content: "";
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.25); /* Voile sombre léger */
        z-index: 1;
    }
    .banner-content {
        position: relative;
        z-index: 2;
        text-align: center;
    }
    .banner-content h2 {
        color: white;
        font-size: 36px;
        font-weight: 800;
        text-transform: uppercase;
        text-shadow: 2px 2px 10px rgba(0,0,0,0.5);
    }
    @media (max-width: 768px) {
        .banner-strip { height: 200px; background-attachment: scroll; }
        .banner-content h2 { font-size: 24px; }
    }
</style>

<div class="hero">
    <h1>Discover the best Puffs with unique flavors</h1>
    <p>+25 flavors & Fast delivery & Guaranteed quality</p>
    <a href="shop.php" class="btn-purple">View Products</a>
</div>

<div class="container" style="background-color: #FAFAFB; border-radius: 12px; padding: 40px; margin-bottom: 40px;">
    <h2 class="section-title" style="color: #7B61FF; text-align: center;">Popular Flavors</h2>

    <div class="flavor-categories-grid">
        <div>
            <img src="../photos/savfraise.JPG" class="flavor-cat-img" alt="Strawberry" onerror="this.src='https://via.placeholder.com/250x90/FF4D6D/fff?text=Strawberry'">
            <div class="flavor-cat-title">Strawberry</div>
        </div>
        <div>
            <img src="../photos/peche.PNG" class="flavor-cat-img" alt="Peach" onerror="this.src='https://via.placeholder.com/250x90/FF9F1C/fff?text=Peach'">
            <div class="flavor-cat-title">Peach</div>
        </div>
        <div>
            <img src="../photos/bluebarry2.PNG" class="flavor-cat-img" alt="Blueberry" onerror="this.src='https://via.placeholder.com/250x90/3A86FF/fff?text=Blueberry'">
            <div class="flavor-cat-title">Blueberry</div>
        </div>
        <div>
            <img src="../photos/citron.JPG" class="flavor-cat-img" alt="Lemon" onerror="this.src='https://via.placeholder.com/250x90/FFD166/fff?text=Lemon'">
            <div class="flavor-cat-title">Lemon</div>
        </div>
    </div>

    <div class="flavor-cards-grid">
        
        <div class="flavor-card">
            <div class="flavor-icon">🍓</div>
            <h4 class="flavor-title" style="color: #FF4D6D;">ELECTRIC STRAWBERRY</h4>
            <p class="flavor-desc">Intense red strawberry with a fresh finish</p>
            <div class="intensity-bar-bg">
                <div class="intensity-bar-fill" style="width: 75%; background-color: #FF4D6D;"></div>
            </div>
            <div class="rating">
                ★★★★★ <span class="rating-text">(4.8/5)</span>
            </div>
        </div>

        <div class="flavor-card">
            <div class="flavor-icon">🍑</div>
            <h4 class="flavor-title" style="color: #F8961E;">PASSION PEACH</h4>
            <p class="flavor-desc">Ripe and juicy peach with a sunny taste</p>
            <div class="intensity-bar-bg">
                <div class="intensity-bar-fill" style="width: 65%; background-color: #F8961E;"></div>
            </div>
            <div class="rating">
                ★★★★★ <span class="rating-text">(4.5/5)</span>
            </div>
        </div>

        <div class="flavor-card">
            <div class="flavor-icon">🫐</div>
            <h4 class="flavor-title" style="color: #3A86FF;">BLUEBERRY BLAST</h4>
            <p class="flavor-desc">Explosive blueberries with a sweet touch</p>
            <div class="intensity-bar-bg">
                <div class="intensity-bar-fill" style="width: 85%; background-color: #3A86FF;"></div>
            </div>
            <div class="rating">
                ★★★★★ <span class="rating-text">(4.9/5)</span>
            </div>
        </div>

        <div class="flavor-card">
            <div class="flavor-icon">🍋</div>
            <h4 class="flavor-title" style="color: #FFD166;">FRESH LEMON</h4>
            <p class="flavor-desc">Zesty lemon with a refreshing sensation</p>
            <div class="intensity-bar-bg">
                <div class="intensity-bar-fill" style="width: 70%; background-color: #FFD166;"></div>
            </div>
            <div class="rating">
                ★★★★★ <span class="rating-text">(4.6/5)</span>
            </div>
        </div>

    </div>
</div>

<div class="container brands-section">
    <h2 class="brands-title">Our Brands</h2>
    <div class="brands-grid">
        <img src="../photos/b1.png" alt="Le Petit Vapoteur" onerror="this.src='https://via.placeholder.com/120x80/fff/000?text=Le+Petit+Vapoteur'">
        <img src="../photos/b2.png" alt="Vaporesso" onerror="this.src='https://via.placeholder.com/120x80/fff/000?text=Vaporesso'">
        <img src="../photos/b3.png" alt="Aspire" onerror="this.src='https://via.placeholder.com/120x80/fff/000?text=Aspire'">
        <img src="../photos/b4.png" alt="Full Moon" onerror="this.src='https://via.placeholder.com/120x80/fff/000?text=Full+Moon'">
        <img src="../photos/b5.png" alt="FUU" onerror="this.src='https://via.placeholder.com/120x80/fff/000?text=FUU'">
        <img src="../photos/b6.png" alt="VAPE 47" onerror="this.src='https://via.placeholder.com/120x80/fff/000?text=VAPE+47'">
        <img src="../photos/b7.png" alt="Geekvape" onerror="this.src='https://via.placeholder.com/120x80/fff/000?text=Geekvape'">
        <img src="../photos/b8.png" alt="Innokin" onerror="this.src='https://via.placeholder.com/120x80/fff/000?text=Innokin'">
    </div>
</div>

<section class="banner-strip">
    <div class="banner-content">
    </div>
</section>

<div class="container" style="text-align: center; padding: 60px 20px; max-width: 1000px; margin: 0 auto;">
    <h2 style="font-size: 32px; color: #db4469; font-weight: 700; margin-bottom: 20px;">
        PuffyStyle Tunisia – Authentic Disposable Puffs & Fast Delivery
    </h2>
    
    <p style="font-size: 20px; color: #7B61FF; font-weight: 600; margin-bottom: 30px;">
        Discover the best brands of 100% authentic disposable puffs in Tunisia, with delivery everywhere in the country.
    </p>

    <div style="font-size: 16px; color: #666; line-height: 1.8;">
        <p style="margin-bottom: 15px;">
            Welcome to <strong>PuffyStyle</strong>, your specialized online store for electronic cigarettes, disposable puffs, and high-end vape products.
        </p>
        <p style="margin-bottom: 15px;">
            We offer a rigorous selection of reliable brands such as Elf Bar, Vozol, and Geek Bar, guaranteed authentic and delivered quickly throughout Tunisia.
        </p>
        <p>
            Order with confidence with cash on delivery and responsive customer service.
        </p>
    </div>
</div>
<div class="slider-wrapper">
    <div class="slider-track" id="infoSlider">
        <div class="info-slide">
            <div class="info-inner">
                <img src="../photos/vape_icon.png" alt="Icon" onerror="this.src='https://cdn-icons-png.flaticon.com/512/2668/2668962.png'">
                <div class="info-content">
                    <h3>E-CIGARETTES & GEAR FOR ALL PROFILES</h3>
                    <p>Our shop offers a selection of electronic cigarettes adapted to every user. From compact pods to powerful boxes for maximum vapor.</p>
                </div>
            </div>
        </div>

        <div class="info-slide">
            <div class="info-inner">
                <img src="../photos/liquid_icon.png" alt="Icon" onerror="this.src='https://cdn-icons-png.flaticon.com/512/3014/3014494.png'">
                <div class="info-content">
                    <h3>CHOOSING YOUR E-LIQUID FOR OPTIMAL VAPE</h3>
                    <p>Composed of PG/VG and nicotine, it influences the throat hit and flavor. Available in classic, fruity, menthol, and dessert formats.</p>
                </div>
            </div>
        </div>

        <div class="info-slide">
            <div class="info-inner">
                <img src="../photos/puff_icon.png" alt="Icon" onerror="this.src='https://cdn-icons-png.flaticon.com/512/4812/4812604.png'">
                <div class="info-content">
                    <h3>PUFFS & SIMPLE SOLUTIONS TO VAPE</h3>
                    <p>Rechargeable puffs require no settings, functioning with an auto-draw system. Available in various puff counts from 9K to 100K.</p>
                </div>
            </div>
        </div>

        <div class="info-slide">
            <div class="info-inner">
                <img src="../photos/diy_icon.png" alt="Icon" onerror="this.src='https://cdn-icons-png.flaticon.com/512/3132/3132845.png'">
                <div class="info-content">
                    <h3>MAKE YOUR OWN E-LIQUIDS (DIY)</h3>
                    <p>DIY allows you to create your own liquids by combining PG/VG bases, concentrated aromas, and nicotine boosters for total control.</p>
                </div>
            </div>
        </div>

        <div class="info-slide">
            <div class="info-inner">
                <img src="../photos/tools_icon.png" alt="Icon" onerror="this.src='https://cdn-icons-png.flaticon.com/512/3059/3059441.png'">
                <div class="info-content">
                    <h3>VAPE ACCESSORIES & MAINTENANCE</h3>
                    <p>Find everything you need to maintain your gear: replacement coils, batteries, chargers, and tanks to ensure a perfect vape every day.</p>
                </div>
            </div>
        </div>

        <div class="info-slide">
            <div class="info-inner">
                <img src="../photos/cbd_icon.png" alt="Icon" onerror="this.src='https://cdn-icons-png.flaticon.com/512/3021/3021575.png'">
                <div class="info-content">
                    <h3>PREMIUM CBD PRODUCTS</h3>
                    <p>Explore our selection of CBD e-liquids, boosters, and oils. Relax with high-quality products strictly controlled for your well-being.</p>
                </div>
            </div>
        </div>

        <div class="info-slide">
            <div class="info-inner">
                <img src="../photos/salt_icon.png" alt="Icon" onerror="this.src='https://cdn-icons-png.flaticon.com/512/4163/4163679.png'">
                <div class="info-content">
                    <h3>NICOTINE SALTS FOR A SMOOTH HIT</h3>
                    <p>Perfect for beginners, nicotine salts provide a faster satisfaction and a smoother throat hit, making the transition from smoking easier.</p>
                </div>
            </div>
        </div>

        <div class="info-slide">
            <div class="info-inner">
                <img src="../photos/pack_icon.png" alt="Icon" onerror="this.src='https://cdn-icons-png.flaticon.com/512/2850/2850385.png'">
                <div class="info-content">
                    <h3>READY-TO-VAPE BUNDLES</h3>
                    <p>Save time and money with our exclusive packs. We combine the best devices with matching e-liquids so you can start vaping immediately.</p>
                </div>
            </div>
        </div>

        <div class="info-slide">
            <div class="info-inner">
                <img src="../photos/bottle_icon.png" alt="Icon" onerror="this.src='https://cdn-icons-png.flaticon.com/512/944/944948.png'">
                <div class="info-content">
                    <h3>ECONOMICAL LARGE FORMATS</h3>
                    <p>Choose 50ml or 100ml nicotine-free bottles and add your own boosters. A more eco-friendly and cost-effective way to enjoy your favorite flavors.</p>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    const track = document.getElementById('infoSlider');
    let index = 0;

    function autoScroll() {
        const slides = document.querySelectorAll('.info-slide');
        const visibleSlides = window.innerWidth > 768 ? 2 : 1;
        const maxIndex = slides.length - visibleSlides;

        index++;
        if (index > maxIndex) {
            index = 0;
        }

        const slideWidth = slides[0].clientWidth;
        track.style.transform = `translateX(-${index * slideWidth}px)`;
    }

    setInterval(autoScroll, 2000); // 2.0 Seconds Interval
</script>
<div class="container">
    <h2 class="section-title" style="color: #7B61FF; text-align: center;">Why choose us?</h2>
    <div class="grid-4">
        <div class="card feature-card">
            <h3>✓ Original Products</h3>
            <p>Guaranteed and authentic quality.</p>
        </div>
        <div class="card feature-card">
            <h3>🚚 Fast Delivery</h3>
            <p>Shipping within 24h.</p>
        </div>
        <div class="card feature-card">
            <h3>🔒 Secure Payment</h3>
            <p>Protected transactions.</p>
        </div>
        <div class="card feature-card">
            <h3>🍓 Large Choice</h3>
            <p>+25 available flavors.</p>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>