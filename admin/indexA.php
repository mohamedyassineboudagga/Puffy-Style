<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo "<script>alert('Access Denied. Admins only.'); window.location.href='../pages/index.php';</script>";
    exit();
}

require_once '../config/Database.php';
$database = new Database();
$db = $database->getConnection();

// --- FETCH UNREAD MESSAGES COUNT FOR NOTIFICATION BADGE ---
$unread_count = 0;
try {
    $unread_query = "SELECT COUNT(*) as unread_count FROM contact_messages WHERE is_read = 0";
    $unread_stmt = $db->prepare($unread_query);
    $unread_stmt->execute();
    $unread_result = $unread_stmt->fetch(PDO::FETCH_ASSOC);
    if ($unread_result) {
        $unread_count = $unread_result['unread_count'];
    }
} catch(PDOException $e) {
    // If table doesn't exist yet or other error, it will just default to 0
}

// Determine which page to show (default is 'orders')
$page = $_GET['page'] ?? 'orders';

if ($page === 'orders') {
    // Fetch all orders
    $query = "SELECT * FROM orders ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($page === 'messages') {
    // Fetch all contact messages
    $msg_query = "SELECT * FROM contact_messages ORDER BY created_at DESC";
    $msg_stmt = $db->prepare($msg_query);
    $msg_stmt->execute();
    $messages = $msg_stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($page === 'resets') {
    // Fetch all password reset requests from passforget table
    $reset_query = "SELECT * FROM passforget ORDER BY created_at DESC";
    $reset_stmt = $db->prepare($reset_query);
    $reset_stmt->execute();
    $resets = $reset_stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Helper function to extract data from the notes string
 */
function get_note_value($notes, $label) {
    if (empty($notes)) return "N/A";
    if (preg_match('/' . $label . ':\s*([^|]+)/', $notes, $matches)) {
        return trim($matches[1]);
    }
    return "N/A";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Puffy Style</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-purple: #7B61FF; 
            --dark-purple: #5A4FCF;
            --bg-light: #F8F9FA;
            --text-dark: #333333;
            --border-light: #EAEAEA;
            --danger-red: #FF4D6D;
        }
        
        body { margin: 0; font-family: 'Poppins', sans-serif; background-color: var(--bg-light); color: var(--text-dark); display: flex; }
        
        /* Sidebar */
        .admin-sidebar { 
            width: 250px; 
            background: white; 
            height: 100vh; 
            position: fixed; 
            box-shadow: 2px 0 10px rgba(0,0,0,0.05); 
            display: flex;
            flex-direction: column;
            z-index: 100;
        }
        .admin-sidebar h2 { text-align: center; color: var(--primary-purple); padding: 20px 0; margin: 0; margin-bottom: 10px; }
        .admin-nav { flex-grow: 1; }
        .admin-nav a { 
            display: flex; 
            align-items: center; 
            justify-content: space-between; /* Pushes badge to the right */
            padding: 15px 25px; 
            color: var(--text-dark); 
            text-decoration: none; 
            font-weight: 500; 
            border-left: 4px solid transparent; 
            transition: 0.3s; 
        }
        .admin-nav a:hover, .admin-nav a.active { background: var(--bg-light); border-left-color: var(--primary-purple); color: var(--primary-purple); }
        
        /* Notification Badge CSS */
        .nav-badge {
            background-color: var(--danger-red);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
        }

        .sidebar-footer { padding: 30px; text-align: center; }
        .btn-logout { background: #FF4D4D; color: white; padding: 10px 30px; border-radius: 25px; text-decoration: none; font-weight: 600; font-size: 14px; display: inline-block; }

        /* Content Area */
        .admin-content { margin-left: 250px; padding: 40px; width: calc(100% - 250px); box-sizing: border-box; }
        .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        
        .card { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); padding: 30px; overflow-x: auto; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 15px; text-align: left; border-bottom: 1px solid var(--border-light); }
        .data-table th { background-color: #fcfcfc; color: #666; font-size: 14px; font-weight: 600; }
        
        .status-badge { padding: 5px 12px; border-radius: 15px; font-size: 12px; font-weight: 600; text-transform: capitalize; }
        .status-pending { background: #FFF3CD; color: #856404; }
        
        .btn-view { 
            background: var(--primary-purple); 
            color: white; 
            padding: 8px 14px; 
            border-radius: 6px; 
            text-decoration: none; 
            font-size: 13px; 
            display: inline-block; 
            white-space: nowrap; 
            transition: background 0.3s ease;
        }
        .btn-view:hover { background: var(--dark-purple); }
    </style>
</head>
<body>

    <div class="admin-sidebar">
        <h2>Puffy Admin</h2>
        <div class="admin-nav">
            <a href="indexA.php?page=orders" class="<?php echo $page === 'orders' ? 'active' : ''; ?>">
                <span>Orders</span>
            </a>
            <a href="products.php">
                <span>Products</span>
            </a>
            <a href="add_product.php">
                <span>Add Product</span>
            </a>
            <a href="Coupons.php">
                <span>Coupons</span>
            </a>
            <a href="users.php">
                <span>Users</span>
            </a>
            <a href="indexA.php?page=messages" class="<?php echo $page === 'messages' ? 'active' : ''; ?>">
                <span>Contact Messages</span>
                <?php if ($unread_count > 0): ?>
                    <span class="nav-badge"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </a>
            <a href="indexA.php?page=resets" class="<?php echo $page === 'resets' ? 'active' : ''; ?>">
                <span>Reset Requests</span>
            </a>
            <a href="../pages/index.php">
                <span>View Store</span>
            </a>
        </div>
        <div class="sidebar-footer">
            <a href="../pages/logout.php" class="btn-logout">Logout</a>
        </div>
    </div>

    <div class="admin-content">
        <div class="header-top">
            <h1>
                <?php 
                    if($page === 'messages') echo 'Customer Messages'; 
                    elseif($page === 'resets') echo 'Password Reset Requests';
                    else echo 'Recent Orders'; 
                ?>
            </h1>
        </div>

        <?php if ($page === 'orders'): ?>
            <div class="card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($orders) > 0): ?>
                            <?php foreach ($orders as $order): 
                                $customer_name = get_note_value($order['notes'], 'Customer');
                                if($customer_name == "N/A") $customer_name = get_note_value($order['notes'], 'Phone');
                                $city = get_note_value($order['notes'], 'City');
                            ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                    <td>
                                        <?php echo htmlspecialchars($customer_name); ?><br>
                                        <small style="color: #888;"><?php echo htmlspecialchars($city); ?></small>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                    <td><strong><?php echo number_format($order['total'], 3); ?> TND</strong></td>
                                    <td><span class="status-badge status-<?php echo strtolower($order['status']); ?>"><?php echo htmlspecialchars($order['status']); ?></span></td>
                                    <td><a href="order_details.php?id=<?php echo urlencode($order['id']); ?>" class="btn-view">View</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align: center; padding: 30px; color: #888;">No orders found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($page === 'messages'): ?>
            <div class="card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($messages) > 0): ?>
                            <?php foreach ($messages as $msg): ?>
                                <tr>
                                    <td><?php echo date('M d, Y H:i', strtotime($msg['created_at'])); ?></td>
                                    <td><strong><?php echo htmlspecialchars($msg['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($msg['email']); ?></td>
                                    <td><?php echo htmlspecialchars($msg['subject'] ?? 'No Subject'); ?></td>
                                    <td style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($msg['message']); ?></td>
                                    <td>
                                        <?php if (!empty($msg['admin_reply'])): ?>
                                            <span class="status-badge" style="background: #D4EDDA; color: #155724;">Replied</span>
                                        <?php elseif ($msg['is_read'] == 0): ?>
                                            <span class="status-badge" style="background: #FF4D6D; color: white;">New</span>
                                        <?php else: ?>
                                            <span class="status-badge" style="background: #EAEAEA; color: #888;">Read</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><a href="message_details.php?id=<?php echo urlencode($msg['id']); ?>" class="btn-view">View & Reply</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align: center; padding: 30px; color: #888;">No messages found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($page === 'resets'): ?>
            <div class="card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>User ID </th>
                            <th>Email Address</th>
                            <th>Requested At</th>
                            <th>Security Token</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($resets) > 0): ?>
                            <?php foreach ($resets as $reset): ?>
                                <tr>
                                    <td>#<?php echo htmlspecialchars($reset['id']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($reset['user_id']); ?></strong></td>
                                    <td>
                                        <a href="mailto:<?php echo htmlspecialchars($reset['email']); ?>" style="color: var(--primary-purple); text-decoration: none;">
                                            <?php echo htmlspecialchars($reset['email']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($reset['created_at'])); ?></td>
                                    <td><code style="background: #eee; padding: 4px 8px; border-radius: 4px; font-size: 12px;"><?php echo substr($reset['token'], 0, 15); ?>...</code></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align: center; padding: 30px; color: #888;">No password reset requests found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>