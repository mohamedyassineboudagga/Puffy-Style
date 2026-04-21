<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- NEW FIX: Restrict access to logged-in users only ---
if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/login.php");
    exit();
}
// --------------------------------------------------------

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: ../pages/shop.php");
    exit();
}

require_once '../config/Database.php';
$database = new Database();
$db = $database->getConnection();
$subtotal = 0;
$checkout_items = [];

// 1. PROCESS CART ITEMS
foreach ($_SESSION['cart'] as $key => $quantity) {
    // Split the key to get the Product ID and the Flavor
    $parts = explode('||', $key);
    $product_id = $parts[0];
    $flavor = isset($parts[1]) ? $parts[1] : 'Standard';

    // MISE À JOUR : On récupère le prix promotionnel
    $stmt = $db->prepare("SELECT id, name, price, promotional_price FROM products WHERE id = :id LIMIT 1");
    $stmt->bindParam(':id', $product_id);
    $stmt->execute();
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($item) {
        // MISE À JOUR : Détermination du prix final (remisé ou normal)
        $effective_price = (!empty($item['promotional_price']) && $item['promotional_price'] > 0) ? $item['promotional_price'] : $item['price'];
        
        $item_subtotal = $effective_price * $quantity;
        $subtotal += $item_subtotal;
        
        // Add quantity, subtotal, effective price and flavor to the item array for the loop below
        $item['qty'] = $quantity;
        $item['effective_price'] = $effective_price; // On garde le bon prix pour la BDD
        $item['total'] = $item_subtotal;
        $item['flavor'] = $flavor;
        $checkout_items[] = $item;
    }
}

// --- SHIPPING LOGIC ---
$discount = $_SESSION['discount_amount'] ?? 0;

// If subtotal is strictly greater than 100, shipping is free (0). Otherwise, it's 7 TND.
if ($subtotal > 100.000) {
    $shipping_cost = 0.000;
} else {
    $shipping_cost = 7.000;
}

// Calculate the final total
$total_final = max(0, ($subtotal + $shipping_cost) - $discount);

// --- 2. PROCESS ORDER SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    $full_name = htmlspecialchars($_POST['full_name'] ?? '');
    $phone     = htmlspecialchars($_POST['phone'] ?? '');
    $city      = htmlspecialchars($_POST['city'] ?? '');
    $address   = htmlspecialchars($_POST['address'] ?? '');
    $user_id   = $_SESSION['user_id']; // This is now guaranteed to be set because of the check at the top

    if(empty($full_name) || empty($phone)) {
        $error_msg = "Please fill in all required fields.";
    } else {
        try {
            $db->beginTransaction();

            // Generate unique Order ID
            $order_id = 'ORD' . str_pad(mt_rand(1, 999999999), 9, '0', STR_PAD_LEFT);
            $notes = "Phone: $phone | City: $city | Address: $address";

            // Insert into 'orders' table
            $query = "INSERT INTO orders (id, user_id, order_number, status, subtotal, shipping_cost, discount_amount, total, shipping_method, payment_method, notes) 
                      VALUES (:id, :u_id, :num, 'pending', :sub, :ship, :disc, :tot, 'Standard', 'COD', :notes)";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':id'    => $order_id, 
                ':u_id'  => $user_id, 
                ':num'   => $order_id,
                ':sub'   => $subtotal, 
                ':ship'  => $shipping_cost, 
                ':disc'  => $discount,
                ':tot'   => $total_final, 
                ':notes' => $notes
            ]);

            // Insert into 'order_items' table (Includes flavor)
            $item_query = "INSERT INTO order_items (id, order_id, product_id, product_name, quantity, unit_price, total_price, flavor) 
                           VALUES (:id, :o_id, :p_id, :p_name, :qty, :u_p, :t_p, :flavor)";
            $item_stmt = $db->prepare($item_query);

            // Using 'stock_quantity' based on your database screenshot
            $stock_query = "UPDATE products SET stock_quantity = stock_quantity - :qty WHERE id = :p_id";
            $stock_stmt = $db->prepare($stock_query);

            foreach ($checkout_items as $ci) {
                $item_id = 'ORDI' . str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
                $item_stmt->execute([
                    ':id'      => $item_id, 
                    ':o_id'    => $order_id, 
                    ':p_id'    => $ci['id'],
                    ':p_name'  => $ci['name'], 
                    ':qty'     => $ci['qty'], 
                    ':u_p'     => $ci['effective_price'], // MISE À JOUR: Utilisation du prix effectif
                    ':t_p'     => $ci['total'],
                    ':flavor'  => $ci['flavor'] // Insert the flavor
                ]);

                // Execute stock reduction
                $stock_stmt->execute([
                    ':qty'  => $ci['qty'],
                    ':p_id' => $ci['id']
                ]);
            }

            // --- FIXED: UPDATE COUPON USAGE COUNT ---
            if (isset($_SESSION['applied_coupon']) && !empty($_SESSION['applied_coupon'])) {
                $coupon_code = $_SESSION['applied_coupon'];
                $update_coupon_stmt = $db->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE code = :code");
                $update_coupon_stmt->execute([':code' => $coupon_code]);
            }

            $db->commit();
            unset($_SESSION['cart']);
            unset($_SESSION['discount_amount']);
            unset($_SESSION['applied_coupon']); // Clear the coupon from the session

            echo "<script>alert('Order Successful! Your Order ID is: $order_id'); window.location.href='../pages/index.php';</script>";
            exit();
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            $error_msg = "Order failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout - Puffy Style</title>
    <style>
        :root { --p-purple: #7B61FF; --p-pink: #FF61A6; --bg: #F9F9FF; }
        body { background-color: var(--bg); font-family: 'Poppins', sans-serif; color: #333; }
        .checkout-grid { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 30px; max-width: 1100px; margin: 40px auto; padding: 0 20px; }
        
        .card { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(123, 97, 255, 0.05); }
        h2 { color: var(--p-purple); margin-top: 0; font-size: 22px; margin-bottom: 25px; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; }

        /* Form Styling */
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; color: #555; }
        input, select { width: 100%; padding: 12px 15px; border: 1.5px solid #EAEAEA; border-radius: 10px; font-family: inherit; transition: 0.3s; box-sizing: border-box; }
        input:focus { border-color: var(--p-purple); outline: none; box-shadow: 0 0 0 4px rgba(123, 97, 255, 0.1); }

        /* Order Summary */
        .summary-item { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 15px; }
        .total-row { border-top: 2px dashed #EEE; margin-top: 15px; padding-top: 15px; font-weight: 700; font-size: 20px; color: var(--p-purple); }
        
        .btn-order { background: linear-gradient(135deg, var(--p-pink), #e05592); color: white; border: none; width: 100%; padding: 18px; border-radius: 50px; font-weight: 700; font-size: 16px; cursor: pointer; transition: 0.3s; margin-top: 20px; box-shadow: 0 8px 20px rgba(255, 97, 166, 0.3); }
        .btn-order:hover { transform: translateY(-3px); box-shadow: 0 12px 25px rgba(255, 97, 166, 0.4); }

        @media (max-width: 850px) { .checkout-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="checkout-grid">
    <div class="card">
        <h2>Shipping Information</h2>
        <?php if (!empty($error_msg)): ?>
            <div style="color: red; margin-bottom: 15px; text-align: center; font-weight: bold;"><?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>
        <form id="checkout-form" method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" placeholder="Enter your full name" required>
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" name="phone" placeholder="Ex: 21 000 000" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label>City</label>
                    <input type="text" name="city" placeholder="Tunis, Sousse, etc." required>
                </div>
                <div class="form-group">
                    <label>Postal Code</label>
                    <input type="text" name="zip" placeholder="1000">
                </div>
            </div>
            <div class="form-group">
                <label>Full Address</label>
                <input type="text" name="address" placeholder="Street, Building, Apartment..." required>
            </div>
            <input type="hidden" name="place_order" value="1">
        </form>
    </div>

    <div class="card">
        <h2>Order Summary</h2>
        <?php foreach ($checkout_items as $item): ?>
            <div class="summary-item">
                <span>
                    <?php echo htmlspecialchars($item['name']); ?> <strong>x<?php echo $item['qty']; ?></strong>
                    <?php if (!empty($item['flavor'])): ?>
                        <br><small style="color: #888;">Flavor: <?php echo htmlspecialchars($item['flavor']); ?></small>
                    <?php endif; ?>
                </span>
                <span><?php echo number_format($item['total'], 3); ?> TND</span>
            </div>
        <?php endforeach; ?>

        <div class="summary-item" style="margin-top: 20px;">
            <span>Subtotal</span>
            <span><?php echo number_format($subtotal, 3); ?> TND</span>
        </div>
        <div class="summary-item">
            <span>Shipping Fee</span>
            <span>
                <?php if ($shipping_cost == 0): ?>
                    <span style="color: #28a745; font-weight: bold;">FREE</span>
                <?php else: ?>
                    +<?php echo number_format($shipping_cost, 3); ?> TND
                <?php endif; ?>
            </span>
        </div>
        
        <?php if($discount > 0): ?>
            <div class="summary-item" style="color: #28a745;">
                <span>Promo Discount</span>
                <span>-<?php echo number_format($discount, 3); ?> TND</span>
            </div>
        <?php endif; ?>

        <div class="total-row">
            <span>Total</span>
            <span><?php echo number_format($total_final, 3); ?> TND</span>
        </div>

        <button type="submit" form="checkout-form" class="btn-order">Confirm & Place Order</button>
        <p style="text-align: center; font-size: 12px; color: #888; margin-top: 15px;">
            💳 Payment Method: <strong>Cash on Delivery</strong>
        </p>
    </div>
</div>
</body>
</html>