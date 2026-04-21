<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/Database.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['promo_code'])) {
    $promo_code = trim($_POST['promo_code']);

    // If the user submits an empty code, just remove the current discount
    if (empty($promo_code)) {
        unset($_SESSION['discount_amount']);
        unset($_SESSION['applied_coupon']);
        header("Location: cart.php");
        exit();
    }

    $database = new Database();
    $db = $database->getConnection();

    // 1. Calculate the current cart total to check for 'min_order' and calculate percentages
    $total_amount = 0;
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $key => $quantity) {
            $parts = explode('||', $key);
            $product_id = $parts[0];

            $stmt = $db->prepare("SELECT price FROM products WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $product_id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($item) {
                $total_amount += ($item['price'] * $quantity);
            }
        }
    }

    // 2. Fetch the coupon from the database
    $stmt = $db->prepare("SELECT * FROM coupons WHERE code = :code LIMIT 1");
    $stmt->execute([':code' => $promo_code]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$coupon) {
        // Coupon doesn't exist
        $_SESSION['promo_error'] = "Invalid coupon code.";
        unset($_SESSION['discount_amount']);
        unset($_SESSION['applied_coupon']);
    } else {
        // 3. Validate the coupon (Dates, Active Status, Limits, Min Order)
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
        } elseif ($total_amount < $coupon['min_order']) {
            $_SESSION['promo_error'] = "Your cart total must be at least " . number_format($coupon['min_order'], 3) . " TND to use this coupon.";
            $is_valid = false;
        }

        // 4. If everything is valid, calculate the discount!
        if ($is_valid) {
            $discount = 0;
            
            if ($coupon['type'] === 'percentage') {
                $discount = $total_amount * ($coupon['value'] / 100);
            } elseif ($coupon['type'] === 'fixed') {
                $discount = $coupon['value'];
            }

            // Make sure the discount isn't somehow greater than the cart total
            if ($discount > $total_amount) {
                $discount = $total_amount;
            }

            // Store success info in session
            $_SESSION['discount_amount'] = $discount;
            $_SESSION['applied_coupon'] = $coupon['code'];
            
            // Clear any old error messages
            unset($_SESSION['promo_error']); 
        } else {
            // Ensure no discount is applied if validity fails
            unset($_SESSION['discount_amount']);
            unset($_SESSION['applied_coupon']);
        }
    }

    // Redirect back to cart to show the results
    header("Location: cart.php");
    exit();
} else {
    // If accessed directly without POST data, redirect to cart
    header("Location: cart.php");
    exit();
}
?>