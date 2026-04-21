<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security Check: Make sure only logged-in admins can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo "<script>alert('Access Denied. Admins only.'); window.location.href='../pages/index.php';</script>";
    exit();
}

require_once '../config/Database.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: indexA.php");
    exit();
}

$order_id = $_GET['id'];

// Initialize Database
$database = new Database();
$db = $database->getConnection();

// --- HANDLE STATUS UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    
    try {
        $update_query = "UPDATE orders SET status = :status WHERE id = :id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(':status', $new_status);
        $update_stmt->bindParam(':id', $order_id);
        
        if ($update_stmt->execute()) {
            echo "<script>alert('Order status updated successfully!'); window.location.href='order_details.php?id=$order_id';</script>";
            exit();
        }
    } catch(PDOException $e) {
        echo "<script>alert('Error updating status: " . addslashes($e->getMessage()) . "');</script>";
    }
}

// 1. Fetch the main order details
$order_query = "SELECT * FROM orders WHERE id = :id LIMIT 1";
$stmt = $db->prepare($order_query);
$stmt->bindParam(':id', $order_id);
$stmt->execute();
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "<script>alert('Order not found!'); window.location.href='indexA.php';</script>";
    exit();
}

// 2. Fetch the items for this order
$items_query = "SELECT * FROM order_items WHERE order_id = :order_id";
$item_stmt = $db->prepare($items_query);
$item_stmt->bindParam(':order_id', $order_id);
$item_stmt->execute();
$order_items = $item_stmt->fetchAll(PDO::FETCH_ASSOC);

// Parse the customer info we saved in the 'notes' column
$customer_info = $order['notes'] ?? ''; 
$info_parts = $customer_info ? explode(' | ', $customer_info) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Puffy Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-purple: #7B61FF; 
            --dark-purple: #5A4FCF;
            --bg-light: #F8F9FA;
            --text-dark: #333333;
            --border-light: #EAEAEA;
        }
        body { margin: 0; font-family: 'Poppins', sans-serif; background-color: var(--bg-light); color: var(--text-dark); }
        
        .admin-sidebar { width: 250px; background: white; height: 100vh; position: fixed; box-shadow: 2px 0 10px rgba(0,0,0,0.05); padding: 20px 0; }
        .admin-sidebar h2 { text-align: center; color: var(--primary-purple); margin-bottom: 30px; }
        .admin-nav a { display: block; padding: 15px 25px; color: var(--text-dark); text-decoration: none; font-weight: 500; border-left: 4px solid transparent; }
        .admin-nav a:hover, .admin-nav a.active { background: var(--bg-light); border-left-color: var(--primary-purple); color: var(--primary-purple); }
        .sidebar-footer {
            padding: 180px 80px;
        }
        .admin-content { margin-left: 250px; padding: 40px; }
        .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .btn-back { background: #ccc; color: #333; padding: 8px 20px; border-radius: 20px; text-decoration: none; font-weight: 600; font-size: 14px; }
        .btn-back:hover { background: #bbb; }
        .btn-logout { background: #FF4D4D; color: white; padding: 8px 20px; border-radius: 20px; text-decoration: none; font-weight: 600; font-size: 14px; }

        .title-area { display: flex; align-items: center; gap: 15px; }
        .badge { padding: 6px 15px; border-radius: 20px; font-size: 14px; font-weight: bold; text-transform: capitalize; }
        .status-pending { background: #FFF3CD; color: #856404; }
        .status-processing { background: #CCE5FF; color: #004085; }
        .status-shipped { background: #E2E3E5; color: #383D41; }
        .status-delivered { background: #D4EDDA; color: #155724; }

        .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .card { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); padding: 25px; }
        .card h3 { margin-top: 0; color: var(--primary-purple); border-bottom: 2px solid var(--border-light); padding-bottom: 10px; }
        
        .info-line { margin-bottom: 10px; line-height: 1.6; }
        .info-line strong { color: #555; }

        .items-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .items-table th, .items-table td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border-light); }
        .items-table th { background-color: var(--bg-light); color: #666; }
        
        .totals-section { text-align: right; margin-top: 20px; font-size: 16px; line-height: 1.8; }
        .grand-total { font-size: 22px; font-weight: bold; color: var(--primary-purple); margin-top: 10px; border-top: 2px solid var(--border-light); padding-top: 10px; display: inline-block;}
        
        .status-form { display: flex; align-items: center; gap: 10px; margin-top: 15px; background: var(--bg-light); padding: 15px; border-radius: 8px; border: 1px solid var(--border-light); }
        .status-select { padding: 8px; border-radius: 6px; border: 1px solid #ccc; font-family: inherit; }
        .btn-update { background: var(--primary-purple); color: white; border: none; padding: 8px 15px; border-radius: 6px; cursor: pointer; font-weight: 500; }
        .btn-update:hover { background: var(--dark-purple); }
    </style>
</head>
<body>

    <div class="admin-sidebar">
        <h2>Puffy Admin</h2>
        <div class="admin-nav">
            <a href="indexA.php" class="active">Orders</a>
            <a href="products.php" >Products</a>
            <a href="add_product.php">Add Product</a>
            <a href="Coupons.php">Coupons</a>
            <a href="users.php">Users</a>
            <a href="indexA.php?page=messages" class="<?php echo $page === 'messages' ? 'active' : ''; ?>">Contact Messages</a>
            <a href="indexA.php?page=resets" class="<?php echo $page === 'resets' ? 'active' : ''; ?>">Reset Requests</a>
            <a href="../pages/index.php">View Store</a>
        </div>
        <div class="sidebar-footer">
            <a href="../pages/logout.php" class="btn-logout">Logout</a>
        </div>
    </div>

    <div class="admin-content">
        <div class="header-top">
            <div class="title-area">
                <h1 style="margin: 0;">Order #<?php echo htmlspecialchars($order['order_number']); ?></h1>
                <span class="badge status-<?php echo strtolower($order['status']); ?>">
                    <?php echo htmlspecialchars($order['status']); ?>
                </span>
            </div>
            <a href="indexA.php" class="btn-back">&larr; Back to Orders</a>
        </div>

        <div class="details-grid">
            <div class="card">
                <h3>Order Information</h3>
                <div class="info-line"><strong>Date Placed:</strong> <?php echo date('F d, Y h:i A', strtotime($order['created_at'])); ?></div>
                <div class="info-line"><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></div>
                <div class="info-line"><strong>Shipping Method:</strong> <?php echo htmlspecialchars($order['shipping_method']); ?></div>
                
                <form method="POST" class="status-form">
                    <strong>Update Status:</strong>
                    <select name="status" class="status-select">
                        <option value="pending" <?php echo (strtolower($order['status']) == 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="processing" <?php echo (strtolower($order['status']) == 'processing') ? 'selected' : ''; ?>>Processing</option>
                        <option value="shipped" <?php echo (strtolower($order['status']) == 'shipped') ? 'selected' : ''; ?>>Shipped</option>
                        <option value="delivered" <?php echo (strtolower($order['status']) == 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                    </select>
                    <button type="submit" name="update_status" class="btn-update">Save</button>
                </form>
            </div>

            <div class="card">
                <h3>Customer & Delivery Details</h3>
                <?php 
                if (!empty($info_parts)) {
                    foreach($info_parts as $part) {
                        $detail = explode(':', $part, 2); 
                        if(count($detail) == 2) {
                            echo '<div class="info-line"><strong>' . htmlspecialchars(trim($detail[0])) . ':</strong> ' . htmlspecialchars(trim($detail[1])) . '</div>';
                        } else {
                            echo '<div class="info-line">' . htmlspecialchars($part) . '</div>';
                        }
                    }
                } else {
                    echo '<div class="info-line">No customer details available.</div>';
                }
                ?>
            </div>
        </div>

        <div class="card">
            <h3>Items Purchased</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Product ID</th>
                        <th>Product Name</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td><small style="color: #888;"><?php echo htmlspecialchars($item['product_id']); ?></small></td>
                            <td><strong><?php echo htmlspecialchars($item['product_name']); ?></strong></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td><?php echo number_format($item['unit_price'], 3); ?> TND</td>
                            <td><strong><?php echo number_format($item['total_price'], 3); ?> TND</strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="totals-section">
                <div>Subtotal: <?php echo number_format($order['subtotal'], 3); ?> TND</div>
                <?php if ($order['discount_amount'] > 0): ?>
                    <div style="color: #28a745;">Discount: -<?php echo number_format($order['discount_amount'], 3); ?> TND</div>
                <?php endif; ?>
                <div>Shipping: <?php echo number_format($order['shipping_cost'], 3); ?> TND</div>
                
                <div class="grand-total">Grand Total: <?php echo number_format($order['total'], 3); ?> TND</div>
            </div>
        </div>
    </div>

</body>
</html>