<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../config/Database.php';
$database = new Database();
$db = $database->getConnection();

// Check if a notification ID was passed in the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('Invalid notification.'); window.location.href='index.php';</script>";
    exit();
}

$notif_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch the specific notification
$query = "SELECT * FROM notifications WHERE id = :id AND user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->execute([':id' => $notif_id, ':user_id' => $user_id]);
$notif = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$notif) {
    echo "<script>alert('Notification not found or access denied.'); window.location.href='index.php';</script>";
    exit();
}

// Mark the notification as read if it is currently unread
if ($notif['is_read'] == 0) {
    $updateQuery = "UPDATE notifications SET is_read = 1 WHERE id = :id";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->execute([':id' => $notif_id]);
}

// --- PARSE THE MESSAGE STRUCTURE ---
$raw_message = $notif['message'];
$is_admin_reply = false;
$subject = '';
$reply_text = $raw_message;

// Upgraded Regex: Safely extracts subject and reply even if the subject contains dashes or quotes
if (preg_match("/^Admin replied to: '(.*?)' - (.*)$/s", $raw_message, $matches)) {
    $is_admin_reply = true;
    $subject = $matches[1];
    $reply_text = $matches[2];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Notification - Puffy Style</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-purple: #7B61FF; 
            --dark-purple: #5A4FCF;
            --light-purple: #F5F3FF;
            --bg-gradient: linear-gradient(135deg, #F8F9FA 0%, #EBEAFA 100%);
            --text-dark: #2D3748;
            --text-muted: #718096;
            --border-light: #EDF2F7;
        }
        
        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: var(--bg-gradient);
            color: var(--text-dark);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            box-sizing: border-box;
        }

        .brand-header { margin-bottom: 30px; text-align: center; }
        .brand-header h2 { margin: 0; color: var(--primary-purple); font-size: 28px; font-weight: 700; letter-spacing: -0.5px; }

        .notification-card {
            background: white; border-radius: 20px;
            box-shadow: 0 20px 40px rgba(123, 97, 255, 0.08);
            padding: 40px; max-width: 650px; width: 100%;
            border: 1px solid rgba(255, 255, 255, 0.5); box-sizing: border-box;
        }

        .header-top {
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 2px solid var(--border-light);
            padding-bottom: 20px; margin-bottom: 25px;
        }
        .title-wrapper { display: flex; align-items: center; gap: 12px; }
        .icon-box { background: var(--light-purple); color: var(--primary-purple); width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
        .header-top h1 { margin: 0; font-size: 20px; font-weight: 600; color: var(--text-dark); }
        .notif-date { font-size: 13px; color: var(--text-muted); font-weight: 500; background: #F8F9FA; padding: 6px 12px; border-radius: 20px; }

        /* --- Structured Message Styles --- */
        .message-thread {
            display: flex; flex-direction: column; gap: 20px; margin-bottom: 35px;
        }

        .user-subject-box {
            background: #F8F9FA;
            padding: 15px 20px;
            border-radius: 12px;
            border: 1px solid var(--border-light);
        }
        .user-subject-box .label {
            font-size: 12px; color: var(--text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; margin-bottom: 5px; display: block;
        }
        .user-subject-box .subject-text {
            margin: 0; font-size: 16px; font-weight: 500; color: var(--text-dark);
        }

        .admin-reply-box {
            background: var(--light-purple);
            padding: 25px;
            border-radius: 16px;
            border-left: 4px solid var(--primary-purple);
            position: relative;
        }
        /* Tail for the message bubble */
        .admin-reply-box::before {
            content: ''; position: absolute; top: -10px; left: 20px;
            border-width: 0 10px 10px 10px; border-style: solid;
            border-color: transparent transparent var(--light-purple) transparent;
        }
        .admin-header {
            display: flex; align-items: center; gap: 10px; margin-bottom: 12px;
        }
        .admin-avatar {
            width: 28px; height: 28px; background: var(--primary-purple); color: white;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 14px; font-weight: bold;
        }
        .admin-name { font-size: 14px; font-weight: 600; color: var(--primary-purple); }
        .reply-content {
            font-size: 15px; line-height: 1.7; color: #4A5568; white-space: pre-wrap; margin: 0;
        }

        /* Generic fallback body if it's not an admin reply */
        .generic-body {
            font-size: 16px; line-height: 1.7; color: #4A5568; white-space: pre-wrap;
            background: #F8F9FA; padding: 25px; border-radius: 12px; margin-bottom: 35px;
        }

        .card-footer { display: flex; justify-content: center; }
        .btn-back {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            background: var(--primary-purple); color: white; padding: 14px 30px;
            border-radius: 30px; text-decoration: none; font-weight: 600; font-size: 15px;
            transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(123, 97, 255, 0.3);
        }
        .btn-back:hover { background: var(--dark-purple); transform: translateY(-2px); box-shadow: 0 6px 20px rgba(123, 97, 255, 0.4); }

        @media (max-width: 600px) {
            .notification-card { padding: 25px; }
            .header-top { flex-direction: column; align-items: flex-start; gap: 15px; }
            .notif-date { align-self: flex-start; }
        }
    </style>
</head>
<body>

    <div class="brand-header">
        <h2>Puffy Style</h2>
    </div>

    <div class="notification-card">
        <div class="header-top">
            <div class="title-wrapper">
                <div class="icon-box">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                </div>
                <h1><?php echo $is_admin_reply ? 'Support Reply' : 'Notification Details'; ?></h1>
            </div>
            <span class="notif-date"><?php echo date('M d, Y • h:i A', strtotime($notif['created_at'])); ?></span>
        </div>

        <?php if ($is_admin_reply): ?>
            <div class="message-thread">
                <div class="user-subject-box">
                    <span class="label">Regarding Your Message:</span>
                    <h3 class="subject-text"><?php echo $subject; ?></h3>
                </div>
                
                <div class="admin-reply-box">
                    <div class="admin-header">
                        <div class="admin-avatar">P</div>
                        <span class="admin-name">Puffy Style Support</span>
                    </div>
                    <p class="reply-content"><?php echo $reply_text; ?></p>
                </div>
            </div>
        <?php else: ?>
            <div class="generic-body">
                <?php echo htmlspecialchars($notif['message']); ?>
            </div>
        <?php endif; ?>

        <div class="card-footer">
            <a href="index.php" class="btn-back">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Back to Store
            </a>
        </div>
    </div>

</body>
</html>