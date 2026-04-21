<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Calculate total items in the cart
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cart_count = array_sum($_SESSION['cart']); 
}

// Initialize Notification Variables
$unread_notifs = 0;
$notifications = [];

// Fetch notifications if user is logged in
if (isset($_SESSION['user_id'])) {
    // Ensure database connection is available
    if (!isset($db)) {
        require_once __DIR__ . '/../config/Database.php';
        $database = new Database();
        $db = $database->getConnection();
    }
    
    try {
        // Fetch the 5 most recent notifications
        $n_query = "SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 5";
        $n_stmt = $db->prepare($n_query);
        $n_stmt->execute([':user_id' => $_SESSION['user_id']]);
        $notifications = $n_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Count unread notifications
        $c_query = "SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0";
        $c_stmt = $db->prepare($c_query);
        $c_stmt->execute([':user_id' => $_SESSION['user_id']]);
        $unread_notifs = $c_stmt->fetchColumn();
    } catch (Exception $e) {
        // If table doesn't exist yet, just ignore to prevent site breaking
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Puffy Style</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-purple: #7B61FF; 
            --dark-purple: #5A4FCF;
            --accent-pink: #FFB6C1;
            --dark-pink: #FF9EBD;
            --bg-light: #F8F9FA;
            --text-dark: #333333;
            --text-muted: #777777;
            --border-light: #EAEAEA;
        }
        
        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
        }

        /* --- Header Navigation --- */
        header {
            background: white;
            padding: 15px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-purple);
            text-decoration: none;
        }
        .main-nav {
            display: flex;
            gap: 25px;
            align-items: center;
        }
        .main-nav a {
            text-decoration: none;
            color: var(--text-dark);
            font-size: 14px;
            font-weight: 500;
            transition: color 0.2s;
        }

        .main-nav a:hover { color: var(--primary-purple); }
        .main-nav a.active { color: var(--accent-pink); }
        
        .btn-pink {
            background-color: var(--accent-pink);
            color: white !important;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 600 !important;
        }
        .btn-pink:hover { background-color: var(--dark-pink); }

        .auth-buttons { display: flex; gap: 15px; align-items: center; }
        .btn-text {
            color: var(--dark-purple);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .btn-purple {
            background-color: var(--primary-purple);
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }
        .btn-purple:hover { background-color: var(--dark-purple); }

        /* --- Search Bar Styles --- */
        .search-bar-container {
            display: flex;
            align-items: center;
            background-color: white;
            border: 1px solid #EAEAEA;
            border-radius: 25px;
            padding: 6px 15px;
            margin-right: 10px;
            margin-left: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: border-color 0.3s ease;
        }
        .search-bar-container:focus-within {
            border-color: var(--primary-purple);
        }
        .search-bar-icon {
            color: #4A5568;
            margin-right: 8px;
            display: flex;
            align-items: center;
        }
        .search-bar-input {
            border: none;
            outline: none;
            background: transparent;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-dark);
            width: 110px;
            transition: width 0.3s ease;
        }
        .search-bar-input:focus {
            width: 150px;
        }
        .search-bar-input::placeholder {
            color: #337ab7;
            font-weight: 600;
            text-transform: uppercase;
        }

        /* Badges */
        .cart-badge {
            background-color: var(--accent-pink);
            color: white;
            font-size: 12px;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 10px;
        }

        /* --- Notification Styles --- */
        .notif-wrapper { position: relative; display: inline-block; margin-right: 10px; }
        .notif-icon { color: var(--dark-purple); cursor: pointer; display: flex; align-items: center; position: relative; padding: 5px; }
        .notif-icon:hover { color: var(--primary-purple); }
        .notif-badge { position: absolute; top: -2px; right: -5px; background-color: #FF4D4D; color: white; font-size: 10px; font-weight: bold; padding: 2px 6px; border-radius: 10px; border: 2px solid white; }
        .notif-dropdown { display: none; position: absolute; top: 40px; right: -10px; width: 320px; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid var(--border-light); z-index: 1001; overflow: hidden; }
        
        /* THIS CLASS IS NEW FOR JAVASCRIPT TOGGLE */
        .notif-dropdown.show { display: block; }
        
        .notif-header { padding: 15px; background: #F9F9FB; border-bottom: 1px solid var(--border-light); font-weight: 600; font-size: 14px; color: var(--text-dark); }
        .notif-item { padding: 15px; border-bottom: 1px solid var(--border-light); font-size: 13px; color: var(--text-dark); display: block; text-decoration: none; line-height: 1.4; transition: 0.2s;}
        .notif-item:hover { background: #F8F9FA; }
        .notif-item.unread { font-weight: 500; background: #F5F3FF; border-left: 3px solid var(--primary-purple); }
        .notif-empty { padding: 20px; text-align: center; color: var(--text-muted); font-size: 13px; }
        .notif-time { display: block; font-size: 11px; color: var(--text-muted); margin-top: 5px; font-weight: 400; }

        /* User Avatar */
        .user-avatar {
            width: 35px;
            height: 35px;
            background-color: #6B4C9A; 
            color: white;
            border-radius: 50%; 
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            transition: transform 0.2s ease, background-color 0.2s ease;
            margin-right: 10px;
            border: 2px solid transparent;
        }
        .user-avatar:hover {
            background-color: #F0719B; 
            transform: scale(1.1);
            border-color: rgba(255, 255, 255, 0.5);
        }

        /* --- Reusable Card Styles --- */
        .container { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }
        .section-title { text-align: center; color: var(--primary-purple); font-size: 28px; margin-bottom: 40px; font-weight: 600; }
        
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            padding: 20px;
            text-align: center;
            border: 1px solid var(--border-light);
            transition: transform 0.2s;
        }
        .card:hover { transform: translateY(-5px); }

        /* --- Age Verification Popup Styles --- */
        body.modal-open { overflow: hidden; }
        .age-gate-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100vh;
            background: rgba(0, 0, 0, 0.4); backdrop-filter: blur(8px);
            z-index: 9999; display: none; align-items: center;
            justify-content: center; padding: 20px;
        }
        .age-gate-card {
            background: white; width: 100%; max-width: 450px; padding: 40px;
            border-radius: 16px; box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            text-align: center; border: 1px solid #EAEAEA;
        }
        .age-gate-card h2 { color: #6B4C9A; margin-top: 0; margin-bottom: 10px; font-size: 26px; }
        .age-gate-card p { color: #666; font-size: 15px; line-height: 1.5; margin-bottom: 30px; }
        .age-gate-buttons { display: flex; flex-direction: column; gap: 15px; }
        .btn-age {
            width: 100%; padding: 15px; border-radius: 25px; font-size: 16px;
            font-weight: 600; cursor: pointer; transition: all 0.3s ease; border: none;
        }
        .btn-yes { background: #6B4C9A; color: white; }
        .btn-yes:hover { background: #553A7D; transform: translateY(-2px); }
        .btn-no { background: #F8F8F8; color: #666; border: 1px solid #EAEAEA; }
        .btn-no:hover { background: #FEE2E2; color: #EF4444; border-color: #FCA5A5; }
        #age-error {
            display: none; color: #EF4444; background: #FEE2E2; padding: 10px;
            border-radius: 8px; margin-top: 20px; font-size: 14px; font-weight: 600;
        }
    </style>
</head>
<body>

<?php if (!isset($_SESSION['user_id'])): ?>
    <div id="age-gate-modal" class="age-gate-overlay">
        <div class="age-gate-card">
            <h2>Age Verification</h2>
            <p>You must be 18 years of age or older to enter Puffy Style Store. Please confirm your age to continue.</p>
            
            <div class="age-gate-buttons">
                <button id="btn-yes" class="btn-age btn-yes" onclick="verifyAge(true)">Yes, I am 18 or older</button>
                <button id="btn-no" class="btn-age btn-no" onclick="verifyAge(false)">No, I am under 18</button>
            </div>

            <div id="age-error">Access Denied. Redirecting...</div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            if (!sessionStorage.getItem('ageVerified')) {
                const modal = document.getElementById('age-gate-modal');
                if(modal) {
                    modal.style.display = 'flex';
                    document.body.classList.add('modal-open');
                }
            }
        });

        function verifyAge(isAdult) {
            const modal = document.getElementById('age-gate-modal');
            const errorMsg = document.getElementById('age-error');
            const btnYes = document.getElementById('btn-yes');
            const btnNo = document.getElementById('btn-no');

            if (isAdult) {
                sessionStorage.setItem('ageVerified', 'true');
                modal.style.display = 'none';
                document.body.classList.remove('modal-open');
            } else {
                errorMsg.style.display = 'block';
                btnYes.style.display = 'none';
                btnNo.style.display = 'none';
                setTimeout(function() {
                    window.location.href = "https://www.google.com"; 
                }, 2000);
            }
        }
    </script>
<?php endif; ?>

<header>
    <a href="../pages/index.php" class="logo">Puffy Style</a>
    
    <nav class="main-nav">
        <a href="../pages/index.php">Home</a>
        <a href="../pages/shop.php">Products</a>
        <a href="../pages/promotions.php">Promotions</a>
        <a href="../footerlinks/contact.php">Contact</a>
        <a href="../pages/shop.php" class="btn-pink">Shop Now</a>
    </nav>
    
    <div class="auth-buttons">
        
        <form action="../pages/shop.php" method="GET" class="search-bar-container">
            <span class="search-bar-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </span>
            <input type="text" name="search" placeholder="SEARCH" class="search-bar-input" autocomplete="off" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
        </form>

        <a href="../footerlinks/cart.php" class="btn-text" style="margin-right: 10px;">
            🛒 Cart 
            <?php if ($cart_count > 0): ?>
                <span class="cart-badge"><?php echo $cart_count; ?></span>
            <?php endif; ?>
        </a>

        <?php if(isset($_SESSION['user_id'])): ?>
            
            <div class="notif-wrapper">
                <div class="notif-icon" id="notif-toggle-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <?php if($unread_notifs > 0): ?>
                        <span class="notif-badge"><?php echo $unread_notifs; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="notif-dropdown" id="notif-dropdown-menu">
                    <div class="notif-header">Notifications</div>
                    <?php if(count($notifications) > 0): ?>
                        <?php foreach($notifications as $notif): ?>
                            <a href="../pages/view_notification.php?id=<?php echo $notif['id']; ?>" class="notif-item <?php echo $notif['is_read'] == 0 ? 'unread' : ''; ?>">
                                <?php echo htmlspecialchars($notif['message']); ?>
                                <span class="notif-time"><?php echo date('M d, Y \a\t H:i', strtotime($notif['created_at'])); ?></span>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="notif-empty">No notifications yet.</div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <a href="../admin/indexA.php" class="btn-text" style="color: var(--accent-pink);">Admin Panel</a>
            <?php endif; ?>
            
        <?php 
            $initial = strtoupper(substr($_SESSION['user_name'], 0, 1)); 
        ?>
            <a href="../pages/my_orders.php" style="text-decoration: none;">
                <div class="user-avatar" title="View <?php echo htmlspecialchars($_SESSION['user_name']); ?>'s Orders">
                    <?php echo $initial; ?>
                </div>
            </a>
            <a href="../pages/logout.php" class="btn-purple">Logout</a>
        <?php else: ?>
            <a href="../pages/login.php" class="btn-text">Login</a>
            <a href="../pages/register.php" class="btn-purple">Register</a>
        <?php endif; ?>
    </div>
</header>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const notifBtn = document.getElementById("notif-toggle-btn");
    const notifMenu = document.getElementById("notif-dropdown-menu");

    if (notifBtn && notifMenu) {
        // Toggle the menu when the bell icon is clicked
        notifBtn.addEventListener("click", function(e) {
            e.stopPropagation(); // Prevents the click from bubbling up and triggering the document click listener right away
            notifMenu.classList.toggle("show");
        });

        // Close the menu if the user clicks anywhere else on the page
        document.addEventListener("click", function(e) {
            if (!notifBtn.contains(e.target) && !notifMenu.contains(e.target)) {
                notifMenu.classList.remove("show");
            }
        });
    }
});
</script>

</body>
</html>