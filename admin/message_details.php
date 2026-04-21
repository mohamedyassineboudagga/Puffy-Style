<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo "<script>alert('Access Denied. Admins only.'); window.location.href='../pages/index.php';</script>";
    exit();
}

require_once '../config/Database.php';
$database = new Database();
$db = $database->getConnection();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('Invalid message ID.'); window.location.href='indexA.php?page=messages';</script>";
    exit();
}

$msg_id = $_GET['id'];

// Handle Admin Reply Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_reply'])) {
    $reply_text = trim($_POST['reply_message']);
    
    // 1. Save the reply in the contact_messages table so it stays visible to the admin
    $update_reply_q = "UPDATE contact_messages SET admin_reply = :reply WHERE id = :id";
    $update_reply_stmt = $db->prepare($update_reply_q);
    $update_reply_stmt->execute([':reply' => $reply_text, ':id' => $msg_id]);
    
    // 2. Grab the email of the person who sent the message for the notification
    $q = "SELECT email, subject FROM contact_messages WHERE id = :id";
    $s = $db->prepare($q);
    $s->execute([':id' => $msg_id]);
    $m = $s->fetch(PDO::FETCH_ASSOC);
    
    if ($m) {
        // Check if this email belongs to a registered user
        $u_query = "SELECT id FROM users WHERE email = :email LIMIT 1";
        $u_stmt = $db->prepare($u_query);
        $u_stmt->execute([':email' => $m['email']]);
        $user = $u_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // They have an account! Insert into their notifications.
            $notif_text = "Admin replied to: '" . htmlspecialchars($m['subject']) . "' - " . htmlspecialchars($reply_text);
            
            // FIX: Added is_read and created_at to ensure the database doesn't reject the insert
            $ins_query = "INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (:uid, :msg, 0, NOW())";
            $ins_stmt = $db->prepare($ins_query);
            $ins_stmt->execute([':uid' => $user['id'], ':msg' => $notif_text]);
            
            echo "<script>alert('Reply successfully saved and sent to user notifications!'); window.location.href='message_details.php?id=" . $msg_id . "';</script>";
            exit();
        } else {
            // Guest User Fallback
            echo "<script>alert('Reply saved! Note: This user is a Guest (no account). They cannot receive site notifications. Please also reply via their email.'); window.location.href='message_details.php?id=" . $msg_id . "';</script>";
            exit();
        }
    }
}

// Fetch the message (after any potential updates)
$query = "SELECT * FROM contact_messages WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->execute([':id' => $msg_id]);
$msg = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$msg) {
    echo "<script>alert('Message not found.'); window.location.href='indexA.php?page=messages';</script>";
    exit();
}

// Mark as read
if ($msg['is_read'] == 0) {
    $updateQuery = "UPDATE contact_messages SET is_read = 1 WHERE id = :id";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->execute([':id' => $msg_id]);
    $msg['is_read'] = 1;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Message Details - Admin Dashboard</title>
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
        .admin-nav a { display: block; padding: 15px 25px; color: var(--text-dark); text-decoration: none; font-weight: 500; border-left: 4px solid transparent; transition: 0.3s; }
        .admin-nav a:hover, .admin-nav a.active { background: var(--bg-light); border-left-color: var(--primary-purple); color: var(--primary-purple); }
        .admin-content { margin-left: 250px; padding: 40px; max-width: 900px; }
        
        .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .btn-back { background: #EAEAEA; color: #333; padding: 8px 20px; border-radius: 20px; text-decoration: none; font-weight: 600; font-size: 14px; transition: 0.3s; }
        .btn-back:hover { background: #d4d4d4; }

        .card { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); padding: 30px; margin-bottom: 20px; }
        .sidebar-footer {
            padding: 180px 80px;
        }
        .msg-header { border-bottom: 1px solid var(--border-light); padding-bottom: 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: flex-start;}
        .msg-info h3 { margin: 0 0 5px 0; color: #333; font-size: 20px; }
        .msg-info p { margin: 0 0 5px 0; color: #666; font-size: 14px; }
        .msg-info a { color: var(--primary-purple); text-decoration: none; font-weight: 500; }
        .btn-logout { background: #FF4D4D; color: white; padding: 8px 20px; border-radius: 20px; text-decoration: none; font-weight: 600; font-size: 14px; }

        .msg-meta { text-align: right; }
        .msg-date { font-size: 13px; color: #888; display: block; margin-bottom: 8px; }
        .status-badge { padding: 5px 12px; border-radius: 15px; font-size: 12px; font-weight: 600; background: #EAEAEA; color: #888; }
        
        .msg-body { line-height: 1.7; color: #444; font-size: 15px; white-space: pre-wrap; background: #F9F9FB; padding: 20px; border-radius: 8px; border: 1px solid #EAEAEA; margin-bottom: 30px;}

        /* Reply Form Styles */
        .reply-section h4 { margin-top: 0; color: var(--primary-purple); font-size: 16px; margin-bottom: 15px; }
        .reply-textarea { width: 100%; box-sizing: border-box; min-height: 120px; padding: 15px; border: 1px solid var(--border-light); border-radius: 8px; font-family: inherit; font-size: 14px; margin-bottom: 15px; outline: none; transition: 0.3s; resize: vertical; }
        .reply-textarea:focus { border-color: var(--primary-purple); box-shadow: 0 0 0 3px rgba(123, 97, 255, 0.1); }
        .btn-reply { background: var(--primary-purple); color: white; padding: 10px 25px; border-radius: 6px; border: none; font-weight: 600; font-size: 14px; cursor: pointer; transition: 0.3s; }
        .btn-reply:hover { background: var(--dark-purple); }

        /* Saved Reply Styles */
        .saved-reply { background: #F5F3FF; padding: 20px; border-radius: 8px; border-left: 4px solid var(--primary-purple); margin-top: 20px; }
        .saved-reply h4 { margin: 0 0 10px 0; color: var(--primary-purple); font-size: 15px; display: flex; align-items: center; gap: 8px; }
        .saved-reply p { margin: 0; color: #444; font-size: 15px; line-height: 1.6; white-space: pre-wrap; }
    </style>
</head>
<body>

    <div class="admin-sidebar">
        <h2>Puffy Admin</h2>
        <div class="admin-nav">
            <a href="indexA.php?page=orders">Orders</a>
            <a href="products.php">Products</a>
            <a href="add_product.php">Add Product</a>
            <a href="Coupons.php">Coupons</a>
            <a href="users.php">Users</a>
            <a href="indexA.php?page=messages" class="active">Contact Messages</a>
            <a href="indexA.php?page=resets" class="<?php echo isset($page) && $page === 'resets' ? 'active' : ''; ?>">Reset Requests</a>
            <a href="../pages/index.php">View Store</a>
        </div>
        <div class="sidebar-footer">
            <a href="../pages/logout.php" class="btn-logout">Logout</a>
        </div>
    </div>

    <div class="admin-content">
        <div class="header-top">
            <h1>Message Details</h1>
            <a href="indexA.php?page=messages" class="btn-back">← Back to Messages</a>
        </div>

        <div class="card">
            <div class="msg-header">
                <div class="msg-info">
                    <h3><?php echo htmlspecialchars($msg['subject'] ?? 'No Subject'); ?></h3>
                    <p><strong>From:</strong> <?php echo htmlspecialchars($msg['name']); ?></p>
                    <p><strong>Email:</strong> <a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>"><?php echo htmlspecialchars($msg['email']); ?></a></p>
                </div>
                <div class="msg-meta">
                    <span class="msg-date"><?php echo date('F d, Y \a\t h:i A', strtotime($msg['created_at'])); ?></span>
                    <span class="status-badge"><?php echo !empty($msg['admin_reply']) ? 'Replied' : 'Read'; ?></span>
                </div>
            </div>

            <div class="msg-body"><?php echo htmlspecialchars($msg['message']); ?></div>

            <?php if (!empty($msg['admin_reply'])): ?>
                <div class="saved-reply">
                    <h4>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        Your Reply
                    </h4>
                    <p><?php echo htmlspecialchars($msg['admin_reply']); ?></p>
                </div>
            <?php else: ?>
                <div class="reply-section">
                    <h4>Reply via On-Site Notification</h4>
                    <form action="" method="POST">
                        <textarea name="reply_message" class="reply-textarea" placeholder="Type your reply here... It will show up in the customer's header notifications." required></textarea>
                        <button type="submit" name="submit_reply" class="btn-reply">Send Reply to User</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>