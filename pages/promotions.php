<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Connect to the database
require_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

// Fetch reduced products directly using SQL
$discounted_products = [];

try {
    // The corrected SQL filter: fetch only if promotional_price is greater than 0
    $product_query = "SELECT * FROM products WHERE promotional_price > 0";
    
    $product_stmt = $db->prepare($product_query);
    $product_stmt->execute();
    
    // SQL has done the work, all fetched products are on promotion!
    $discounted_products = $product_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    echo "<div style='background:#FF4D6D; color:white; padding:20px; text-align:center;'><strong>DATABASE ERROR:</strong> " . $e->getMessage() . "</div>";
}

// --- MAGIC DEBUG TRICK ---
// Check your browser console (F12 -> Console) to see the results
echo "<script>";
echo "console.log('--- PHP DEBUGGING ---');";
echo "console.log('1. Total Promo Products Found:', " . json_encode(count($discounted_products)) . ");";
echo "console.log('2. Product Data:', " . json_encode($discounted_products) . ");";
echo "</script>";
// -------------------------
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promotions - Puffy Style</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-purple: #7B61FF; 
            --dark-purple: #5A4FCF;
            --accent-pink: #FFB6C1;
            --badge-pink: #FF4D6D;
            --bg-light: #F8F9FA;
            --text-dark: #333333;
            --border-light: #EAEAEA;
            --text-muted: #888;
            --card-bg: #FFFFFF;
        }
        
        body { margin: 0; font-family: 'Poppins', sans-serif; background-color: var(--bg-light); color: var(--text-dark); }
        .container { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }
        .section-title { text-align: center; color: var(--primary-purple); font-size: 32px; margin-bottom: 10px; font-weight: 700; }
        .section-subtitle { text-align: center; color: #777; font-size: 16px; margin-bottom: 40px; }

        /* Hero Banner */
        .promo-banner {
            background: linear-gradient(135deg, var(--primary-purple), var(--accent-pink));
            border-radius: 20px;
            padding: 60px 40px;
            text-align: center;
            color: white;
            box-shadow: 0 10px 30px rgba(123, 97, 255, 0.2);
            margin-bottom: 50px;
        }
        .btn-banner {
            background-color: white; color: var(--primary-purple); padding: 12px 30px;
            border-radius: 25px; text-decoration: none; font-weight: 600; display: inline-block;
        }

        /* Products Grid */
        .products-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px; margin-bottom: 60px;
        }
        .product-card {
            background: var(--card-bg); border-radius: 16px; padding: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); position: relative;
            display: flex; flex-direction: column; transition: 0.3s;
        }
        .product-card:hover { transform: translateY(-5px); }
        .discount-badge {
            position: absolute; top: 15px; right: 15px; background: var(--badge-pink);
            color: white; padding: 6px 12px; border-radius: 8px; font-weight: 700;
        }
        .product-image { width: 100%; height: 230px; object-fit: contain; border-radius: 12px; margin-bottom: 15px; }
        .product-title { font-size: 18px; font-weight: 600; margin: 0 0 10px 0; }
        .tag { background: var(--bg-light); padding: 5px 10px; border-radius: 6px; font-size: 13px; color: #555; }
        .price-section { display: flex; align-items: center; gap: 10px; margin: 15px 0; }
        .old-price { text-decoration: line-through; color: #A0A0A0; font-size: 15px; }
        .new-price { color: var(--badge-pink); font-size: 20px; font-weight: 700; }
        .card-actions { display: flex; gap: 10px; margin-top: auto; }
        .btn-details { flex: 1; border: 1px solid var(--primary-purple); color: var(--primary-purple); text-align: center; text-decoration: none; border-radius: 8px; padding: 8px; font-weight: 600; }
        .btn-cart { flex: 1; background: var(--primary-purple); color: white; border: none; border-radius: 8px; padding: 8px; font-weight: 600; cursor: pointer; }

        .no-data { text-align: center; grid-column: 1 / -1; color: var(--text-muted); padding: 40px; }
    </style>
</head>
<body>

    <?php include '../includes/header.php'; ?>

    <div class="container">
        <div class="promo-banner">
            <h1>Special Offers Just For You!</h1>
            <p>Save big on your favorite puff brands with our exclusive deals.</p>
            <a href="shop.php" class="btn-banner">Shop All Products</a>
        </div>

        <h2 class="section-title">Flash Sales</h2>
        <p class="section-subtitle">Grab these reduced items before they're gone!</p>

        <div class="products-grid">
            <?php if (count($discounted_products) > 0): ?>
                <?php foreach ($discounted_products as $product): ?>
                    <?php 
                        // Map the regular price to "old" and promotional_price to "new"
                        $old = (float)$product['price'];
                        $new = (float)$product['promotional_price'];
                        
                        // Prevent division by zero if regular price is 0
                        $discount = ($old > 0) ? round((($old - $new) / $old) * 100) : 0;
                    ?>
                    <div class="product-card">
                        <div class="discount-badge">-<?php echo $discount; ?>%</div>
                        <img src="../photos/<?php echo htmlspecialchars($product['image_main'] ?? 'default.jpg'); ?>" class="product-image">
                        <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <div style="display:flex; gap:5px;">
                            <span class="tag">💨 <?php echo htmlspecialchars($product['puff_count'] ?? '0'); ?> Puffs</span>
                        </div>
                        <div class="price-section">
                            <span class="old-price"><?php echo number_format($old, 3); ?> TND</span>
                            <span class="new-price"><?php echo number_format($new, 3); ?> TND</span>
                        </div>
                        <div class="card-actions">
                            <a href="product_details.php?id=<?php echo $product['id']; ?>" class="btn-details">Details</a>
                            <button class="btn-cart" onclick="addToCart(<?php echo $product['id']; ?>)">Add to Cart</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-data">
                    <p>No items on sale right now. Check back soon!</p>
                </div>
            <?php endif; ?>
        </div>

        <hr style="border:0; height:1px; background:var(--border-light); margin:60px 0;">
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        function addToCart(productId) {
            window.location.href = '../actions/add_to_cart.php?id=' + productId; 
        }
    </script>
</body>
</html>