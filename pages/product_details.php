<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/Database.php';
$database = new Database();
$db = $database->getConnection();

// Get the product ID from the URL
$product_id = isset($_GET['id']) ? $_GET['id'] : die('Product ID not provided.');

// Fetch product details
$query = "SELECT * FROM products WHERE id = :id AND is_active = 1 LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $product_id);
$stmt->execute();
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Product not found or is currently inactive.");
}

// Convert comma-separated flavors from DB into an array
$flavors_available = [];
if (!empty($product['flavor'])) {
    $flavors_available = array_map('trim', explode(',', $product['flavor']));
}

$success_msg = '';
$error_msg = '';

// --- Handle Add to Cart ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $qty_to_add = (int)$_POST['quantity'];
    $selected_flavor = isset($_POST['flavor']) ? trim($_POST['flavor']) : 'Standard';
    
    $cart_key = $product['id'] . '||' . $selected_flavor;

    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

    $current_cart_qty = isset($_SESSION['cart'][$cart_key]) ? $_SESSION['cart'][$cart_key] : 0;
    
    if (($current_cart_qty + $qty_to_add) > $product['stock_quantity']) {
        $error_msg = "You cannot add that many. We only have " . $product['stock_quantity'] . " in stock.";
    } else {
        if (isset($_SESSION['cart'][$cart_key])) {
            $_SESSION['cart'][$cart_key] += $qty_to_add;
        } else {
            $_SESSION['cart'][$cart_key] = $qty_to_add;
        }
        $success_msg = "Successfully added to your cart!";
    }
}

// --- Handle Review Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isset($_SESSION['user_id'])) {
        $error_msg = "You must be logged in to leave a review.";
    } else {
        $r_user_id = $_SESSION['user_id'];
        $r_rating = (int)$_POST['rating'];
        $r_comment = htmlspecialchars($_POST['comment'] ?? '');

        if ($r_rating >= 1 && $r_rating <= 5 && !empty($r_comment)) {
            try {
                $is_unique = false;
                $new_review_id = '';
                
                while (!$is_unique) {
                    $new_review_id = 'RVW' . str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
                    $check_stmt = $db->prepare("SELECT COUNT(*) FROM reviews WHERE id = :check_id");
                    $check_stmt->execute([':check_id' => $new_review_id]);
                    if ($check_stmt->fetchColumn() == 0) {
                        $is_unique = true; 
                    }
                }

                $ins_rev = $db->prepare("INSERT INTO reviews (id, product_id, user_id, rating, comment) VALUES (:id, :p_id, :u_id, :rating, :comment)");
                $ins_rev->execute([
                    ':id' => $new_review_id,
                    ':p_id' => $product_id,
                    ':u_id' => $r_user_id, 
                    ':rating' => $r_rating,
                    ':comment' => $r_comment
                ]);
                $success_msg = "Thank you! Your review has been submitted.";
            } catch (Exception $e) {
                $error_msg = "Failed to submit review. Error: " . $e->getMessage();
            }
        } else {
            $error_msg = "Please provide a valid rating and a comment.";
        }
    }
}

// --- Fetch Existing Reviews ---
$rev_query = "
    SELECT r.*, u.first_name, u.last_name 
    FROM reviews r
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.product_id = :id 
    ORDER BY r.created_at DESC
";
$rev_stmt = $db->prepare($rev_query);
$rev_stmt->bindParam(':id', $product_id);
$rev_stmt->execute();
$reviews = $rev_stmt->fetchAll(PDO::FETCH_ASSOC);


// --- FINAL BOSS IMAGE HARVESTER (UPGRADED) ---
$raw_images = [];

// 1. Get the Main Image
if (!empty($product['image_main'])) {
    $raw_images[] = trim($product['image_main']);
}

// 2. Get additional and gallery images safely
$gallery_cols = ['additional_images', 'image_gallery'];

foreach ($gallery_cols as $col) {
    if (!empty($product[$col])) {
        $val = trim($product[$col]);
        
        // Try decoding as JSON first
        $decoded_images = json_decode($val, true);
        
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_images)) {
            // It's a valid JSON array
            $raw_images = array_merge($raw_images, $decoded_images);
        } else {
            // Fallback: If it's a comma-separated string (e.g. "img1.png, img2.png")
            $split_images = explode(',', $val);
            $raw_images = array_merge($raw_images, $split_images);
        }
    }
}

// 3. Clean and deduplicate (ensure we only have unique filenames)
$all_filenames = [];
foreach ($raw_images as $img) {
    $cleaned = basename(trim($img)); // Get just "image.jpg"
    // Ignore completely empty strings, brackets, or quotes that might have slipped through
    $cleaned = str_replace(['[', ']', '"', "'"], '', $cleaned);
    
    if (!empty($cleaned) && !in_array($cleaned, $all_filenames)) {
        $all_filenames[] = $cleaned;
    }
}

// Setup Main Image and Thumbnail Paths
$final_photo_folder = "../photos/";

// Get the main display path
$main_display_path = (count($all_filenames) > 0) ? $final_photo_folder . $all_filenames[0] : "https://via.placeholder.com/500x500?text=No+Image+Found";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Puffy Style</title>
    <style>
        :root { --p-purple: #7B61FF; --p-pink: #FF61A6; --bg: #F9F9FF; }
        body { background-color: var(--bg); font-family: 'Poppins', sans-serif; color: #333; margin: 0; padding: 20px; }
        
        .container { max-width: 1100px; margin: 40px auto; background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(123, 97, 255, 0.05); }
        .product-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 50px; align-items: start; }
        
        /* Image Gallery Styles */
        .product-image-section { display: flex; flex-direction: column; gap: 15px; }
        
        .product-image { position: relative; width: 100%; min-height: 400px; display: flex; justify-content: center; align-items: center; background: #fdfdfd; border-radius: 15px; border: 1px solid #eee; overflow: hidden; }
        .product-image img { max-width: 100%; max-height: 400px; border-radius: 15px; object-fit: contain; transition: opacity 0.3s ease-in-out; }
        
        .promo-badge { position: absolute; top: 15px; right: 15px; background-color: #FF4D6D; color: white; padding: 8px 16px; border-radius: 10px; font-size: 16px; font-weight: bold; box-shadow: 0 4px 15px rgba(255, 77, 109, 0.4); z-index: 10; }

        .gallery-thumbnails { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 5px; flex-wrap: wrap; justify-content: center; }
        .gallery-thumb { width: 80px; height: 80px; border-radius: 10px; border: 2px solid #ddd; cursor: pointer; object-fit: cover; background: #fdfdfd; transition: all 0.3s ease; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .gallery-thumb:hover { border-color: var(--p-pink); transform: translateY(-2px); }
        .gallery-thumb.active { border-color: var(--p-purple); box-shadow: 0 4px 10px rgba(123, 97, 255, 0.2); }
        
        .gallery-thumbnails::-webkit-scrollbar { height: 6px; }
        .gallery-thumbnails::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
        .gallery-thumbnails::-webkit-scrollbar-thumb { background: #d1c7ff; border-radius: 10px; }

        .product-info h1 { color: var(--p-purple); margin-top: 0; font-size: 28px; }
        
        .price-wrapper { margin-bottom: 25px; }
        .price-standard { font-size: 28px; font-weight: bold; color: var(--p-purple); }
        .price-old { font-size: 20px; text-decoration: line-through; color: #999; margin-right: 12px; }
        .price-promo { font-size: 28px; font-weight: bold; color: #FF4D6D; }
        
        .description { color: #666; line-height: 1.6; margin-bottom: 25px; }
        
        .specs { display: flex; gap: 15px; margin-bottom: 25px; flex-wrap: wrap; }
        .spec-badge { background: #f0edff; color: var(--p-purple); padding: 8px 15px; border-radius: 50px; font-size: 14px; font-weight: 600; }
        
        .stock-status { margin-bottom: 20px; font-weight: bold; }
        .in-stock { color: #28a745; }
        .out-of-stock { color: #dc3545; }

        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; }
        select, input[type="text"], input[type="number"], textarea { width: 100%; padding: 12px 15px; border: 1.5px solid #EAEAEA; border-radius: 10px; font-family: inherit; box-sizing: border-box; }
        select:focus, input:focus, textarea:focus { border-color: var(--p-purple); outline: none; }

        .btn-add { background: linear-gradient(135deg, var(--p-purple), var(--p-pink)); color: white; border: none; width: 100%; padding: 16px; border-radius: 50px; font-weight: 700; font-size: 16px; cursor: pointer; transition: 0.3s; box-shadow: 0 8px 20px rgba(123, 97, 255, 0.3); }
        .btn-add:hover { transform: translateY(-3px); box-shadow: 0 12px 25px rgba(123, 97, 255, 0.4); }
        .btn-add:disabled { background: #ccc; cursor: not-allowed; box-shadow: none; transform: none; }

        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-weight: bold; text-align: center; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .reviews-section { border-top: 2px solid #f0f0f0; padding-top: 40px; }
        .reviews-section h2 { color: var(--p-purple); font-size: 22px; margin-bottom: 20px; }
        
        .review-card { background: #fdfdfd; border: 1px solid #eee; padding: 20px; border-radius: 12px; margin-bottom: 15px; }
        .review-header { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .reviewer-name { font-weight: bold; color: #333; }
        .review-rating { color: #FFC107; font-size: 18px; }
        .review-date { font-size: 12px; color: --p-pink; }
        
        .review-form-container { background: #f9f9ff; padding: 25px; border-radius: 15px; margin-top: 30px; border: 1px dashed #d1c7ff; }

        .back-link { display: inline-block; margin-bottom: 25px; color: var(--p-purple); text-decoration: none; font-weight: 600; font-size: 15px; transition: all 0.3s ease; }
        .back-link:hover { color: var(--p-pink); transform: translateX(-5px); }

        .login-prompt { background: #fff3cd; color: #856404; padding: 15px; border-radius: 10px; border: 1px solid #ffeeba; text-align: center; margin-top: 20px; }
        .login-prompt a { color: #856404; font-weight: bold; text-decoration: underline; }

        @media (max-width: 768px) { .product-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="container">
    
    <a href="shop.php" class="back-link">← Back to Shop</a>
    
    <?php if ($success_msg): ?> <div class="alert alert-success"><?php echo $success_msg; ?></div> <?php endif; ?>
    <?php if ($error_msg): ?> <div class="alert alert-error"><?php echo $error_msg; ?></div> <?php endif; ?>

    <div class="product-grid">
        
        <div class="product-image-section">
            <div class="product-image">
                <?php if (!empty($product['promo_type']) && $product['promo_type'] !== 'none' && isset($product['promotional_price']) && $product['promotional_price'] > 0): ?>
                    <span class="promo-badge">
                        <?php 
                        if ($product['promo_type'] === 'percentage') {
                            echo "-" . floatval($product['promo_value']) . "%";
                        } elseif ($product['promo_type'] === 'amount') {
                            echo "-" . floatval($product['promo_value']) . " TND";
                        }
                        ?>
                    </span>
                <?php endif; ?>

                <img id="main-display-img" src="<?php echo htmlspecialchars($main_display_path); ?>" 
                     onerror="this.src='https://via.placeholder.com/500x500?text=Image+Not+Found'"
                     alt="<?php echo htmlspecialchars($product['name']); ?>">
            </div>
            
            <?php if (count($all_filenames) > 1): ?>
            <div class="gallery-thumbnails">
                <?php foreach ($all_filenames as $index => $img_name): 
                    $current_thumb_path = $final_photo_folder . $img_name;
                ?>
                    <img src="<?php echo htmlspecialchars($current_thumb_path); ?>" 
                         class="gallery-thumb <?php echo $index === 0 ? 'active' : ''; ?>" 
                         onclick="swapImage(this, '<?php echo htmlspecialchars($current_thumb_path); ?>')"
                         onerror="this.style.display='none'"
                         alt="Thumbnail">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="product-info">
            <h1><?php echo htmlspecialchars($product['name']); ?></h1>
            
            <div class="price-wrapper">
                <?php if (!empty($product['promo_type']) && $product['promo_type'] !== 'none' && isset($product['promotional_price']) && $product['promotional_price'] > 0): ?>
                    <span class="price-old"><?php echo number_format($product['price'], 3); ?> TND</span>
                    <span class="price-promo"><?php echo number_format($product['promotional_price'], 3); ?> TND</span>
                <?php else: ?>
                    <span class="price-standard"><?php echo number_format($product['price'], 3); ?> TND</span>
                <?php endif; ?>
            </div>

            <div class="specs">
                <?php if(!empty($product['puff_count'])): ?>
                    <div class="spec-badge">💨 <?php echo number_format($product['puff_count']); ?> Puffs</div>
                <?php endif; ?>
                <?php if(!empty($product['nicotine_strength'])): ?>
                    <div class="spec-badge">💧 <?php echo htmlspecialchars($product['nicotine_strength']); ?> Nicotine</div>
                <?php endif; ?>
            </div>

            <div class="description">
                <?php echo nl2br(htmlspecialchars($product['description'])); ?>
            </div>

            <div class="stock-status <?php echo $product['stock_quantity'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                <?php 
                    if ($product['stock_quantity'] > 0) {
                        echo "✅ In Stock (" . $product['stock_quantity'] . " available)";
                    } else {
                        echo "❌ Out of Stock";
                    }
                ?>
            </div>

            <form method="POST">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                
                <?php if (!empty($flavors_available)): ?>
                <div class="form-group">
                    <label for="flavor">Choose Flavor</label>
                    <select name="flavor" id="flavor" required>
                        <?php foreach ($flavors_available as $flav): ?>
                            <option value="<?php echo htmlspecialchars($flav); ?>"><?php echo htmlspecialchars($flav); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="quantity">Quantity</label>
                    <input type="number" name="quantity" id="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>" required>
                </div>

                <button type="submit" name="add_to_cart" class="btn-add" <?php echo $product['stock_quantity'] <= 0 ? 'disabled' : ''; ?>>
                    <?php echo $product['stock_quantity'] > 0 ? '🛒 Add to Cart' : 'Out of Stock'; ?>
                </button>
            </form>
            
            <div style="text-align: center; margin-top: 15px;">
                <a href="../footerlinks/cart.php" style="color: var(--p-purple); font-weight: bold; text-decoration: none;">View Cart ➔</a>
            </div>
        </div>
    </div>

    <div class="reviews-section">
        <h2>Customer Reviews (<?php echo count($reviews); ?>)</h2>

        <?php if (empty($reviews)): ?>
            <p style="color: #777; font-style: italic;">No reviews yet. Be the first to review this product!</p>
        <?php else: ?>
            <?php foreach ($reviews as $rev): ?>
                <div class="review-card">
                    <div class="review-header">
                        <?php 
                            $full_name = trim(($rev['first_name'] ?? '') . ' ' . ($rev['last_name'] ?? ''));
                            if (empty($full_name)) $full_name = 'Anonymous';
                        ?>
                        <span class="reviewer-name">👤 <?php echo htmlspecialchars($full_name); ?></span>
                        <span class="review-date"><?php echo isset($rev['created_at']) ? date('M d, Y', strtotime($rev['created_at'])) : ''; ?></span>
                    </div>
                    <div class="review-rating">
                        <?php 
                            $rating = (int)($rev['rating'] ?? 5);
                            echo str_repeat('★', $rating) . str_repeat('☆', 5 - $rating); 
                        ?>
                    </div>
                    <p style="margin-top: 10px; color: #555;"><?php echo nl2br(htmlspecialchars($rev['comment'] ?? '')); ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="review-form-container">
                <h3 style="margin-top: 0; color: var(--p-purple);">Write a Review</h3>
                <form method="POST">
                    <div class="form-group">
                        <label>Rating</label>
                        <select name="rating" required>
                            <option value="5">★★★★★ (5/5) - Excellent!</option>
                            <option value="4">★★★★☆ (4/5) - Very Good</option>
                            <option value="3">★★★☆☆ (3/5) - Average</option>
                            <option value="2">★★☆☆☆ (2/5) - Poor</option>
                            <option value="1">★☆☆☆☆ (1/5) - Terrible</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Your Review</label>
                        <textarea name="comment" rows="4" placeholder="What did you think about this flavor?" required></textarea>
                    </div>
                    <button type="submit" name="submit_review" class="btn-add" style="padding: 12px; width: auto; min-width: 200px;">Submit Review</button>
                </form>
            </div>
        <?php else: ?>
            <div class="login-prompt">
                Please <a href="login.php">log in</a> to write a review for this product.
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
    function swapImage(thumbnailElement, newImageSrc) {
        document.getElementById('main-display-img').src = newImageSrc;
        
        let thumbnails = document.querySelectorAll('.gallery-thumb');
        thumbnails.forEach(function(thumb) {
            thumb.classList.remove('active');
        });
        
        thumbnailElement.classList.add('active');
    }
</script>

</body>
</html>