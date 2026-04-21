<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security Check: Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];

// Fetch only the orders belonging to this logged-in user
try {
    $query = "SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Error fetching orders.";
    $orders = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Puffy Style</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-purple: #7B61FF;
            --accent-pink: #FF61A6;
            --bg-light: #F8F9FA;
            --text-dark: #333333;
            --success-green: #28a745; /* Ajout d'une couleur pour les réductions */
        }
        body { 
            font-family: 'Poppins', sans-serif; 
            background-color: var(--bg-light); 
            color: var(--text-dark);
            margin: 0; 
            padding: 40px 20px; 
        }
        .container { 
            max-width: 1050px; /* Légèrement élargi pour la nouvelle colonne */
            margin: 0 auto; 
            background: white; 
            padding: 30px; 
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
        }
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        h1 { color: var(--primary-purple); margin: 0; font-size: 24px; }
        .btn-back { color: var(--primary-purple); text-decoration: none; font-weight: 600; font-size: 14px; }
        .btn-back:hover { text-decoration: underline; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; vertical-align: middle; }
        th { background: #f8f9fa; color: #888; font-size: 13px; text-transform: uppercase; }
        
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: capitalize; }
        .status-pending { background: #FFF3CD; color: #856404; }
        .status-processing { background: #CCE5FF; color: #004085; }
        .status-shipped { background: #E2E3E5; color: #383D41; }
        .status-delivered, .status-completed { background: #D4EDDA; color: #155724; }
        
        .discount-text { color: var(--success-green); font-weight: 500; font-size: 14px;}
        
        /* Action Buttons Container to keep them aligned */
        .action-buttons {
            display: flex;
            gap: 8px; /* Space between the two buttons */
            align-items: center;
        }

        /* View Details Button (Styled like Facture but Pink) */
        .btn-view-details { 
            background: var(--accent-pink); 
            color: white; 
            padding: 8px 18px; 
            text-decoration: none; 
            border-radius: 6px; 
            font-size: 13px; 
            font-weight: 500; 
            transition: 0.3s; 
            display: inline-block;
        }
        .btn-view-details:hover { 
            background: #E55595; /* Slightly darker pink on hover */
        }
        
        /* Facture Button (Purple) */
        .btn-facture { 
            background: var(--primary-purple); 
            color: white; 
            padding: 8px 18px; 
            text-decoration: none; 
            border-radius: 6px; 
            font-size: 13px; 
            font-weight: 500; 
            transition: 0.3s; 
            display: inline-block;
        }
        .btn-facture:hover { 
            background: #5A4FCF; /* Slightly darker purple on hover */
        }
        
        .empty-state { text-align: center; padding: 50px 20px; }
        .empty-state h3 { color: #555; }
        .btn-shop { display: inline-block; background: var(--primary-purple); color: white; padding: 10px 25px; border-radius: 25px; text-decoration: none; font-weight: 600; margin-top: 15px; }
    </style>
</head>
<body>

    <div class="container">
        <div class="header-top">
            <h1>My Order History</h1>
            <a href="shop.php" class="btn-back">&larr; Back to Shop</a>
        </div>
        
        <?php if (count($orders) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Date</th>
                        <th>Total Amount</th>
                        <th>Discount</th> <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                            <td><strong><?php echo number_format($order['total'], 3); ?> TND</strong></td>
                            <td>
                                <?php if (isset($order['discount_amount']) && $order['discount_amount'] > 0): ?>
                                    <span class="discount-text">-<?php echo number_format($order['discount_amount'], 3); ?> TND</span>
                                <?php else: ?>
                                    <span style="color: #aaa;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge status-<?php echo strtolower($order['status']); ?>">
                                    <?php echo htmlspecialchars($order['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="order_details.php?order_id=<?php echo $order['id']; ?>" class="btn-view-details">View Details</a>
                                    <a href="facture.php?order_id=<?php echo $order['id']; ?>" target="_blank" class="btn-facture">Facture</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <h3>You haven't placed any orders yet.</h3>
                <p style="color: #888;">Looks like you haven't made your choice yet. Browse our products and find something you love!</p>
                <a href="shop.php" class="btn-shop">Start Shopping</a>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>