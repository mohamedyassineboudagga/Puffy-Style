<?php
// pages/order_details.php
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
        SELECT o.id as order_id, o.order_number, o.created_at, o.total, o.subtotal, o.shipping_cost, o.discount_amount, o.status,
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

    // Handle default status if it's missing or empty
    $status = !empty($order['status']) ? $order['status'] : 'Processing';

    // 2. Fetch the products (items) of this order 
    // UPDATED: Now fetches oi.unit_price and oi.total_price
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
    <title>Order Details - <?php echo htmlspecialchars($order['order_number']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-purple: #7B61FF;
            --text-main: #333333;
            --text-muted: #666666;
            --border-color: #E5E7EB;
            --bg-light: #F9FAFB;
            --status-bg: #D1FAE5;
            --status-text: #065F46;
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
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .back-link {
            color: var(--primary-purple);
            text-decoration: none;
            font-weight: 500;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            overflow: hidden;
            margin-bottom: 25px;
        }

        .card-header {
            background: var(--bg-light);
            padding: 20px 25px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h2 {
            margin: 0;
            font-size: 18px;
            color: var(--text-main);
        }

        .status-badge {
            background: var(--status-bg);
            color: var(--status-text);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .card-body {
            padding: 25px;
        }

        .info-grid {
            display: flex;
            gap: 40px;
            flex-wrap: wrap;
        }

        .info-block {
            flex: 1;
            min-width: 250px;
        }

        .info-block h3 {
            font-size: 14px;
            text-transform: uppercase;
            color: var(--text-muted);
            margin: 0 0 12px 0;
            letter-spacing: 0.5px;
        }

        .info-block p {
            margin: 0 0 8px 0;
            line-height: 1.5;
            font-size: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background-color: var(--bg-light);
            color: var(--text-muted);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 15px;
            border-bottom: 1px solid var(--border-color);
            text-align: left;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
            font-size: 15px;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .summary-box {
            display: flex;
            justify-content: flex-end;
            padding-top: 20px;
        }

        .summary-content {
            width: 320px; /* Slightly wider for the new rows */
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 15px;
            color: var(--text-muted);
        }

        .summary-row.discount-row {
            color: var(--success-green);
        }

        .summary-row.total {
            border-top: 2px solid var(--border-color);
            margin-top: 10px;
            padding-top: 15px;
            font-size: 18px;
            font-weight: 700;
            color: var(--primary-purple);
        }

    </style>
</head>
<body>

    <div class="container">
        
        <div class="header-actions">
            <a href="javascript:history.back()" class="back-link">← Back to Order History</a>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Order #<?php echo htmlspecialchars($order['order_number']); ?></h2>
                <span class="status-badge"><?php echo htmlspecialchars($status); ?></span>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-block">
                        <h3>Customer Details</h3>
                        <p><strong><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></strong></p>
                        <p>📧 <?php echo htmlspecialchars($order['email']); ?></p>
                        <p>📞 <?php echo htmlspecialchars($order['phone']); ?></p>
                    </div>
                    <div class="info-block">
                        <h3>Order Info</h3>
                        <p><strong>Date:</strong> <?php echo date('M d, Y - H:i', strtotime($order['created_at'])); ?></p>
                        <p><strong>Payment Method:</strong> Cash on Delivery</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Items Ordered</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="text-center">Price</th>
                        <th class="text-center">Qty</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                <?php if (!empty($item['flavor'])): ?>
                                    <br><small style="color: var(--text-muted); background: #eee; padding: 2px 6px; border-radius: 4px; font-size: 12px; margin-top: 5px; display: inline-block;">Flavor: <?php echo htmlspecialchars($item['flavor']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><?php echo number_format($item['unit_price'], 3); ?> TND</td>
                            <td class="text-center"><?php echo $item['quantity']; ?></td>
                            <td class="text-right"><?php echo number_format($item['total_price'], 3); ?> TND</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="card-body">
                <div class="summary-box">
                    <div class="summary-content">
                        
                        <?php if(isset($order['subtotal']) && $order['subtotal'] > 0): ?>
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span><?php echo number_format($order['subtotal'], 3); ?> TND</span>
                        </div>
                        <?php endif; ?>

                        <?php if(isset($order['shipping_cost']) && $order['shipping_cost'] > 0): ?>
                        <div class="summary-row">
                            <span>Shipping Fee</span>
                            <span>+<?php echo number_format($order['shipping_cost'], 3); ?> TND</span>
                        </div>
                        <?php endif; ?>

                        <?php if(isset($order['discount_amount']) && $order['discount_amount'] > 0): ?>
                        <div class="summary-row discount-row">
                            <span>Discount</span>
                            <span>-<?php echo number_format($order['discount_amount'], 3); ?> TND</span>
                        </div>
                        <?php endif; ?>

                        <div class="summary-row total">
                            <span>Total Amount</span>
                            <span><?php echo number_format($order['total'], 3); ?> TND</span>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>

    </div>

</body>
</html>s