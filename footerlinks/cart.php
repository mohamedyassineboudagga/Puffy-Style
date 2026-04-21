<?php
// pages/cart.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/Database.php';

// Initialize Database
$database = new Database();
$db = $database->getConnection();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Normalize session format
foreach ($_SESSION['cart'] as $k => $v) {
    if (is_array($v)) {
        $flavor = !empty($v['flavor']) ? $v['flavor'] : 'Standard';
        $new_key = $v['product_id'] . '||' . $flavor;
        $_SESSION['cart'][$new_key] = $v['quantity'];
        unset($_SESSION['cart'][$k]);
    }
}

// --- 1. HANDLE CART ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // -- NEW: Handle Add to Cart (From shop.php) --
    if (isset($_POST['action']) && $_POST['action'] === 'add' && isset($_POST['product_id'])) {
        $product_id = $_POST['product_id'];
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
        $flavor = !empty($_POST['flavor']) ? $_POST['flavor'] : 'Standard';
        
        $cart_key = $product_id . '||' . $flavor;
        
        // Add to existing quantity if already in cart, otherwise set it
        if (isset($_SESSION['cart'][$cart_key])) {
            $_SESSION['cart'][$cart_key] += $quantity;
        } else {
            $_SESSION['cart'][$cart_key] = $quantity;
        }
        
        // Remove coupon if cart changes
        if(isset($_SESSION['applied_coupon'])) {
             unset($_SESSION['discount_amount']);
             unset($_SESSION['applied_coupon']);
             $_SESSION['promo_error'] = "Cart updated. Please re-apply your coupon.";
        }
        
        header("Location: cart.php");
        exit();
    }
    
    // -- Handle Item Increase, Decrease, Remove (Inside Cart) --
    if (isset($_POST['action']) && isset($_POST['cart_key'])) {
        $cart_key_post = $_POST['cart_key'];

        switch ($_POST['action']) {
            case 'increase':
                if (isset($_SESSION['cart'][$cart_key_post])) {
                    $_SESSION['cart'][$cart_key_post] += 1;
                }
                break;
            case 'decrease':
                if (isset($_SESSION['cart'][$cart_key_post])) {
                    if ($_SESSION['cart'][$cart_key_post] > 1) {
                        $_SESSION['cart'][$cart_key_post] -= 1;
                    } else {
                        unset($_SESSION['cart'][$cart_key_post]);
                    }
                }
                break;
            case 'remove':
                unset($_SESSION['cart'][$cart_key_post]); 
                break;
        }
        
        // Recalculate discount if cart changes
        if(isset($_SESSION['applied_coupon'])) {
             unset($_SESSION['discount_amount']);
             unset($_SESSION['applied_coupon']);
             $_SESSION['promo_error'] = "Cart updated. Please re-apply your coupon.";
        }
        
        header("Location: cart.php");
        exit();
    }

    // -- Handle Promo Code Application --
    if (isset($_POST['apply_promo'])) {
        $promo_code = trim($_POST['promo_code'] ?? '');

        if (empty($promo_code)) {
            unset($_SESSION['discount_amount']);
            unset($_SESSION['applied_coupon']);
        } else {
            // 1. Calculate current cart total to check for 'min_order'
            $total_amount_check = 0;
            foreach ($_SESSION['cart'] as $key => $quantity) {
                $parts = explode('||', $key);
                $product_id = $parts[0];
                // MISE À JOUR : On récupère aussi le prix promotionnel
                $stmt = $db->prepare("SELECT price, promotional_price FROM products WHERE id = :id LIMIT 1");
                $stmt->execute([':id' => $product_id]);
                if ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $effective_price = (!empty($item['promotional_price']) && $item['promotional_price'] > 0) ? $item['promotional_price'] : $item['price'];
                    $total_amount_check += ($effective_price * $quantity);
                }
            }

            // 2. Fetch the coupon from the database
            $stmt = $db->prepare("SELECT * FROM coupons WHERE code = :code LIMIT 1");
            $stmt->execute([':code' => $promo_code]);
            $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$coupon) {
                $_SESSION['promo_error'] = "Invalid coupon code.";
                unset($_SESSION['discount_amount']);
                unset($_SESSION['applied_coupon']);
            } else {
                // 3. Validate the coupon
                $current_date = date('Y-m-d');
                $is_valid = true;

                if ($coupon['is_active'] != 1) {
                    $_SESSION['promo_error'] = "This coupon is no longer active.";
                    $is_valid = false;
                } elseif ($current_date < $coupon['start_date']) {
                    $_SESSION['promo_error'] = "This coupon is not valid yet.";
                    $is_valid = false;
                } elseif ($current_date > $coupon['end_date']) {
                    $_SESSION['promo_error'] = "This coupon has expired.";
                    $is_valid = false;
                } elseif ($coupon['max_uses'] !== null && $coupon['used_count'] >= $coupon['max_uses']) {
                    $_SESSION['promo_error'] = "This coupon has reached its maximum usage limit.";
                    $is_valid = false;
                } elseif ($total_amount_check < $coupon['min_order']) {
                    $_SESSION['promo_error'] = "Your cart total must be at least " . number_format($coupon['min_order'], 3) . " TND to use this coupon.";
                    $is_valid = false;
                }

                // 4. Calculate discount
                if ($is_valid) {
                    $discount = 0;
                    if ($coupon['type'] === 'percentage') {
                        $discount = $total_amount_check * ($coupon['value'] / 100);
                    } elseif ($coupon['type'] === 'fixed') {
                        $discount = $coupon['value'];
                    }

                    if ($discount > $total_amount_check) {
                        $discount = $total_amount_check;
                    }

                    $_SESSION['discount_amount'] = $discount;
                    $_SESSION['applied_coupon'] = $coupon['code'];
                    unset($_SESSION['promo_error']); 
                } else {
                    unset($_SESSION['discount_amount']);
                    unset($_SESSION['applied_coupon']);
                }
            }
        }
        header("Location: cart.php");
        exit();
    }

    // -- Handle Promo Code Removal --
    if (isset($_POST['remove_promo'])) {
        unset($_SESSION['discount_amount']);
        unset($_SESSION['applied_coupon']);
        unset($_SESSION['promo_error']);
        header("Location: cart.php");
        exit();
    }
}

// Include Header
include_once '../includes/header.php'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Shopping Cart - Puffy Style</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { 
            --primary-purple: #7B61FF; 
            --border-light: #EAEAEA; 
            --text-muted: #888; 
            --accent-pink: #FF61A6;
        }
        .cart-container { max-width: 1000px; margin: 40px auto; padding: 0 20px; font-family: 'Poppins', sans-serif; }
        .cart-header { color: var(--primary-purple); margin-bottom: 30px; border-bottom: 2px solid var(--border-light); padding-bottom: 10px; }
        
        /* Table Styles */
        .cart-table { width: 100%; border-collapse: collapse; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .cart-table th, .cart-table td { padding: 15px; text-align: left; border-bottom: 1px solid var(--border-light); vertical-align: middle; }
        .cart-table th { background-color: #F8F9FA; color: var(--text-muted); font-weight: 600; }
        .cart-item-img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; vertical-align: middle; margin-right: 15px; }
        
        /* Quantity Controls */
        .qty-container { display: flex; align-items: center; gap: 12px; }
        .qty-btn { background: var(--border-light); border: none; width: 28px; height: 28px; border-radius: 50%; cursor: pointer; font-weight: bold; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
        .qty-btn:hover { background: var(--primary-purple); color: white; }
        
        /* Buttons */
        .btn-remove { background: #FF4D4D; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 12px; }
        .btn-checkout { background-color: var(--accent-pink); color: white; padding: 12px 0px; border-radius: 25px; text-decoration: none; font-weight: 600; display: inline-block; transition: 0.3s; border: none; width: 100%; text-align: center; box-sizing: border-box; }
        .btn-checkout:hover { background-color: #e05592; box-shadow: 0 4px 12px rgba(255, 97, 166, 0.3); color: white;}
        
        /* Summary Layout */
        .cart-summary-wrapper { display: flex; justify-content: space-between; align-items: flex-start; margin-top: 30px; gap: 20px; flex-wrap: wrap; }
        .promo-section, .cart-summary-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); flex: 1; min-width: 300px; }
        
        .total-price { font-size: 24px; font-weight: 700; color: var(--primary-purple); margin-top: 15px; margin-bottom: 20px; border-top: 1px solid var(--border-light); padding-top: 15px; }
        .empty-cart { text-align: center; padding: 50px; background: white; border-radius: 12px; }
    </style>
</head>
<body>

<div class="cart-container">
    <h1 class="cart-header">Your Shopping Cart</h1>

    <?php if (!empty($_SESSION['cart'])): ?>
        <table class="cart-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total_amount = 0;
                foreach ($_SESSION['cart'] as $key => $quantity): 
                    $parts = explode('||', $key);
                    $product_id = $parts[0];
                    $flavor = isset($parts[1]) ? $parts[1] : 'Standard';

                    // MISE À JOUR : On récupère promotional_price pour l'affichage
                    $stmt = $db->prepare("SELECT name, price, promotional_price, image_main FROM products WHERE id = :id LIMIT 1");
                    $stmt->bindParam(':id', $product_id);
                    $stmt->execute();
                    $db_item = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($db_item): 
                        $effective_price = (!empty($db_item['promotional_price']) && $db_item['promotional_price'] > 0) ? $db_item['promotional_price'] : $db_item['price'];
                        $subtotal = $effective_price * $quantity;
                        $total_amount += $subtotal;
                ?>
                    <tr>
                        <td>
                            <img src="../photos/<?php echo htmlspecialchars($db_item['image_main']); ?>" class="cart-item-img" onerror="this.src='../photos/default_puff.png'">
                            <div style="display: inline-block; vertical-align: middle;">
                                <strong><?php echo htmlspecialchars($db_item['name']); ?></strong><br>
                                <?php if (!empty($flavor)): ?>
                                    <small style="color: var(--text-muted); background: #f1f1f1; padding: 2px 6px; border-radius: 4px; font-size: 12px;">Flavor: <?php echo htmlspecialchars($flavor); ?></small>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php if (!empty($db_item['promotional_price']) && $db_item['promotional_price'] > 0): ?>
                                <del style="color: var(--text-muted); font-size: 12px;"><?php echo number_format($db_item['price'], 3); ?> TND</del><br>
                                <span style="color: var(--accent-pink); font-weight: 600;"><?php echo number_format($db_item['promotional_price'], 3); ?> TND</span>
                            <?php else: ?>
                                <?php echo number_format($db_item['price'], 3); ?> TND
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="qty-container">
                                <form method="POST" action="cart.php" style="margin:0;">
                                    <input type="hidden" name="cart_key" value="<?php echo htmlspecialchars($key); ?>">
                                    <input type="hidden" name="action" value="decrease">
                                    <button type="submit" class="qty-btn">-</button>
                                </form>
                                <span class="qty-number"><?php echo (int)$quantity; ?></span>
                                <form method="POST" action="cart.php" style="margin:0;">
                                    <input type="hidden" name="cart_key" value="<?php echo htmlspecialchars($key); ?>">
                                    <input type="hidden" name="action" value="increase">
                                    <button type="submit" class="qty-btn">+</button>
                                </form>
                            </div>
                        </td>
                        <td><strong><?php echo number_format($subtotal, 3); ?> TND</strong></td>
                        <td>
                            <form method="POST" action="cart.php" style="margin:0;">
                                <input type="hidden" name="cart_key" value="<?php echo htmlspecialchars($key); ?>">
                                <input type="hidden" name="action" value="remove">
                                <button type="submit" class="btn-remove">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endif; endforeach; ?>
            </tbody>
        </table>

        <div class="cart-summary-wrapper">
            
            <div class="promo-section">
                <h3 style="margin-top: 0; font-size: 18px; display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 20px;">🎫</span> Promo Code
                </h3>
                
                <?php if(isset($_SESSION['applied_coupon'])): ?>
                    <div style="background: #E8F5E9; padding: 15px; border-radius: 8px; border: 1px solid #C8E6C9; margin-top: 15px;">
                        <p style="margin: 0 0 10px 0; color: #2E7D32; font-weight: 600;">Code Applied: <?php echo htmlspecialchars($_SESSION['applied_coupon']); ?></p>
                        <form method="POST" action="cart.php" style="margin: 0;">
                            <button type="submit" name="remove_promo" style="background: transparent; color: #C62828; border: 1px solid #FFCDD2; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 13px;">Remove Coupon</button>
                        </form>
                    </div>
                <?php else: ?>
                    <form method="POST" action="cart.php" style="display: flex; gap: 10px; margin-top: 15px;">
                        <input type="text" name="promo_code" placeholder="Enter code" 
                            style="flex: 1; padding: 12px; border: 1px solid var(--border-light); border-radius: 8px; font-family: 'Poppins'; outline: none;">
                        <button type="submit" name="apply_promo" style="background: var(--primary-purple); color: white; border: none; padding: 0 20px; border-radius: 8px; cursor: pointer; font-weight: 600;">
                            Apply
                        </button>
                    </form>
                <?php endif; ?>

                <?php if(isset($_SESSION['promo_error'])): ?>
                    <p style="color: #FF4D4D; font-size: 13px; margin-top: 8px;"><?php echo htmlspecialchars($_SESSION['promo_error']); unset($_SESSION['promo_error']); ?></p>
                <?php endif; ?>
            </div>

            <div class="cart-summary-card">
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; color: #666;">
                    <span>Subtotal:</span>
                    <span><?php echo number_format($total_amount, 3); ?> TND</span>
                </div>
                
                <?php if(isset($_SESSION['discount_amount'])): ?>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px; color: #28a745; font-weight: 500;">
                        <span>Discount:</span>
                        <span>- <?php echo number_format($_SESSION['discount_amount'], 3); ?> TND</span>
                    </div>
                    <?php $total_amount -= $_SESSION['discount_amount']; ?>
                <?php endif; ?>

                <div class="total-price">
                    <div style="display: flex; justify-content: space-between;">
                        <span>Total:</span>
                        <span><?php echo number_format(max(0, $total_amount), 3); ?> TND</span>
                    </div>
                </div>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="checkout.php" class="btn-checkout">Proceed to Checkout</a>
                <?php else: ?>
                    <a href="../pages/login.php" class="btn-checkout" style="background-color: #FFFF00;">Log in to Checkout</a>
                    <p style="text-align: center; font-size: 13px; margin-top: 15px; color: var(--text-muted);">
                        Don't have an account? <a href="../pages/register.php" style="color: var(--accent-pink); font-weight: 600;">Register here</a>.
                    </p>
                <?php endif; ?>
                
            </div>
        </div>

    <?php else: ?>
        <div class="empty-cart">
            <h2>Your cart is empty! 🛒</h2>
            <p style="color: var(--text-muted); margin-bottom: 25px;">Go grab some Puffs!</p>
            <a href="../pages/shop.php" class="btn-checkout" style="width: auto; padding: 12px 40px;">Back to Shop</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>