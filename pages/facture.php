<?php
// pages/invoice.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/Database.php';

// Check if an order ID was passed in the URL
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    die("Error: No order specified.");
}

$order_id = $_GET['order_id'];

$database = new Database();
$db = $database->getConnection();

// 1. Fetch order and user information (UPDATED to fetch subtotal, shipping, discount)
try {
    $order_query = "
        SELECT o.id as order_id, o.order_number, o.created_at, o.total, o.subtotal, o.shipping_cost, o.discount_amount, 
               u.first_name, u.last_name, u.email, u.phone 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.id = :order_id
    ";
    $stmt = $db->prepare($order_query);
    $stmt->bindParam(':order_id', $order_id);
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        die("Error: Order not found.");
    }

    // 2. Fetch the products (items) of this order 
    // UPDATED: Now fetches oi.unit_price and oi.total_price to get the exact price paid at the time
    $items_query = "
        SELECT oi.quantity, oi.flavor, oi.unit_price, oi.total_price, p.name 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = :order_id
    ";
    $stmt_items = $db->prepare($items_query);
    $stmt_items->bindParam(':order_id', $order_id);
    $stmt_items->execute();
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - <?php echo htmlspecialchars($order['order_number']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-purple: #7B61FF;
            --text-main: #333333;
            --text-muted: #666666;
            --border-color: #E5E7EB;
            --bg-light: #F9FAFB;
            --success-green: #28a745;
        }
        
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            color: var(--text-main);
            margin: 0;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
        }

        .invoice-box {
            background: #ffffff;
            max-width: 850px;
            width: 100%;
            padding: 50px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }

        /* Header: Logo and Invoice Title */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--primary-purple);
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .header .logo h1 {
            color: var(--primary-purple);
            margin: 0;
            font-size: 32px;
            letter-spacing: -0.5px;
        }
        
        .header .invoice-title {
            text-align: right;
        }
        .header .invoice-title h2 {
            margin: 0;
            font-size: 28px;
            color: var(--text-main);
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .header .invoice-title p {
            margin: 5px 0 0 0;
            color: var(--text-muted);
            font-size: 14px;
        }

        /* Address Section (Issuer / Client) */
        .address-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            background: var(--bg-light);
            padding: 20px;
            border-radius: 8px;
        }

        .address-block {
            width: 48%;
        }

        .address-block h3 {
            font-size: 12px;
            text-transform: uppercase;
            color: var(--text-muted);
            margin: 0 0 10px 0;
            letter-spacing: 1px;
        }

        .address-block p {
            margin: 0;
            line-height: 1.6;
            font-size: 15px;
        }

        .address-block strong {
            color: var(--text-main);
            font-size: 16px;
        }

        /* Products Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        th {
            background-color: var(--bg-light);
            color: var(--text-muted);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 15px;
            border-bottom: 2px solid var(--border-color);
        }

        td {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
            font-size: 15px;
        }

        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }

        /* Totals Section */
        .totals-container {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 40px;
        }

        .totals-box {
            width: 350px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 20px;
            background: #fff;
            border-bottom: 1px solid var(--border-color);
        }

        .totals-row.discount-row {
            color: var(--success-green);
        }

        .totals-row.grand-total {
            background: var(--primary-purple);
            color: white;
            font-size: 18px;
            font-weight: 700;
            border-bottom: none;
        }

        /* Footer */
        .footer {
            text-align: center;
            color: var(--text-muted);
            font-size: 13px;
            border-top: 1px solid var(--border-color);
            padding-top: 20px;
            margin-top: 40px;
        }

        /* Buttons (Hidden on print) */
        .action-buttons {
            text-align: center;
            margin-top: 30px;
        }

        .btn {
            background: var(--primary-purple);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 16px;
            transition: 0.3s;
            box-shadow: 0 4px 6px rgba(123, 97, 255, 0.2);
        }

        .btn:hover {
            background: #6045e6;
            box-shadow: 0 6px 12px rgba(123, 97, 255, 0.3);
        }

        @media print {
            body { background: white; padding: 0; }
            .invoice-box { box-shadow: none; padding: 0; max-width: 100%; }
            .action-buttons { display: none; }
            .address-container { background: transparent; border: 1px solid var(--border-color); }
        }
    </style>
</head>
<body>

    <div class="invoice-box">
        
        <div class="header">
            <div class="logo">
                <h1>Puffy Style</h1> 
            </div>
            <div class="invoice-title">
                <h2>E-Facture</h2>
                <p># <?php echo htmlspecialchars($order['order_number']); ?></p>
                <p>Date: <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></p>
            </div>
        </div>

        <div class="address-container">
            <div class="address-block">
                <h3>From</h3>
                <p>
                    <strong>Puffy Style Store</strong><br>
                    <br>Ecole Polytechnique Privee de Sousse<br>
                    contact@puffystyle.tn
                </p>
            </div>
            <div class="address-block" style="text-align: right;">
                <h3>Billed To</h3>
                <p>
                    <strong><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></strong><br>
                    Phone: <?php echo htmlspecialchars($order['phone']); ?><br>
                    Email: <?php echo htmlspecialchars($order['email']); ?>
                </p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th class="text-left">Product Description</th>
                    <th class="text-center">Unit Price</th>
                    <th class="text-center">Quantity</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td class="text-left">
                            <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                            <?php if (!empty($item['flavor'])): ?>
                                <br><small style="color: var(--text-muted); font-size: 13px;">Flavor: <?php echo htmlspecialchars($item['flavor']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><?php echo number_format($item['unit_price'], 3); ?> TND</td>
                        <td class="text-center"><?php echo $item['quantity']; ?></td>
                        <td class="text-right"><?php echo number_format($item['total_price'], 3); ?> TND</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="totals-container">
            <div class="totals-box">
                <?php if(isset($order['subtotal']) && $order['subtotal'] > 0): ?>
                <div class="totals-row">
                    <span>Subtotal</span>
                    <span><?php echo number_format($order['subtotal'], 3); ?> TND</span>
                </div>
                <?php endif; ?>

                <?php if(isset($order['shipping_cost']) && $order['shipping_cost'] > 0): ?>
                <div class="totals-row">
                    <span>Shipping Fee</span>
                    <span>+<?php echo number_format($order['shipping_cost'], 3); ?> TND</span>
                </div>
                <?php endif; ?>

                <?php if(isset($order['discount_amount']) && $order['discount_amount'] > 0): ?>
                <div class="totals-row discount-row">
                    <span>Discount</span>
                    <span>-<?php echo number_format($order['discount_amount'], 3); ?> TND</span>
                </div>
                <?php endif; ?>

                <div class="totals-row grand-total">
                    <span>Total Amount</span>
                    <span><?php echo number_format($order['total'], 3); ?> TND</span>
                </div>
            </div>
        </div>

        <div class="footer">
            <p><strong>Thank you for your business!</strong></p>
            <p>If you have any questions concerning this invoice, please contact us at contact@puffystyle.tn</p>
        </div>

        <div class="action-buttons">
            <button onclick="window.print()" class="btn">🖨️ Download / Print Invoice</button>
        </div>

    </div>

</body>
</html>