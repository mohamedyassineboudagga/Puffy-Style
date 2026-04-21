<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security Check (Matching indexA.php)
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo "<script>alert('Access Denied. Admins only.'); window.location.href='../pages/index.php';</script>";
    exit();
}

require_once '../config/Database.php';
$database = new Database();
$db = $database->getConnection();

$success_msg = '';
$error_msg = '';

// --- Handle Add Coupon ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_coupon'])) {
    $code = trim($_POST['code']);
    $type = $_POST['type'];
    $value = (float)$_POST['value'];
    $min_order = !empty($_POST['min_order']) ? (float)$_POST['min_order'] : 0.000;
    
    // Safety check: Ensure max_uses is only set if it's a positive number. Otherwise, make it unlimited (null).
    $max_uses = (!empty($_POST['max_uses']) && (int)$_POST['max_uses'] > 0) ? (int)$_POST['max_uses'] : null;
    
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    // 1. Check if the coupon CODE already exists
    $check_code = $db->prepare("SELECT COUNT(*) FROM coupons WHERE code = :code");
    $check_code->execute([':code' => $code]);
    
    if ($check_code->fetchColumn() > 0) {
        $error_msg = "Error: The coupon code '$code' already exists.";
    } else {
        // 2. Generate Unique COUPxxxxxxx ID
        $is_unique = false;
        $new_id = '';
        
        while (!$is_unique) {
            // Generate COUP + 7 random digits
            $new_id = 'COUP' . str_pad(mt_rand(0, 9999999), 7, '0', STR_PAD_LEFT);
            
            // Verify against database
            $check_id = $db->prepare("SELECT COUNT(*) FROM coupons WHERE id = :id");
            $check_id->execute([':id' => $new_id]);
            
            if ($check_id->fetchColumn() == 0) {
                $is_unique = true;
            }
        }

        // 3. Insert into database
        try {
            $query = "INSERT INTO coupons (id, code, type, value, min_order, max_uses, start_date, end_date) 
                      VALUES (:id, :code, :type, :value, :min_order, :max_uses, :start_date, :end_date)";
            
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':id' => $new_id,
                ':code' => strtoupper($code), // Force uppercase for codes
                ':type' => $type,
                ':value' => $value,
                ':min_order' => $min_order,
                ':max_uses' => $max_uses,
                ':start_date' => $start_date,
                ':end_date' => $end_date
            ]);
            
            $success_msg = "Coupon successfully created with ID: " . $new_id;
        } catch (Exception $e) {
            $error_msg = "Failed to create coupon. Error: " . $e->getMessage();
        }
    }
}

// --- Fetch all coupons for the table ---
$query = "SELECT * FROM coupons ORDER BY start_date DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        }
        
        body { margin: 0; font-family: 'Poppins', sans-serif; background-color: var(--bg-light); color: var(--text-dark); }
        
        /* Layout matching indexA.php exactly */
        .admin-sidebar { width: 250px; background: white; height: 100vh; position: fixed; box-shadow: 2px 0 10px rgba(0,0,0,0.05); padding: 20px 0; }
        .admin-sidebar h2 { text-align: center; color: var(--primary-purple); margin-bottom: 30px; }
        .admin-nav a { display: block; padding: 15px 25px; color: var(--text-dark); text-decoration: none; font-weight: 500; border-left: 4px solid transparent; transition: 0.3s; }
        .admin-nav a:hover, .admin-nav a.active { background: var(--bg-light); border-left-color: var(--primary-purple); color: var(--primary-purple); }
        .admin-content { margin-left: 250px; padding: 40px; }
        .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header-top h1 { margin: 0; }
        .btn-logout { background: #FF4D4D; color: white; padding: 8px 20px; border-radius: 20px; text-decoration: none; font-weight: 600; font-size: 14px; }
        .sidebar-footer {
            padding: 180px 80px;
        }
        /* Cards */
        .card { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); padding: 25px; margin-bottom: 30px; overflow-x: auto; }
        .card h2 { margin-top: 0; font-size: 18px; color: var(--text-dark); border-bottom: 1px solid var(--border-light); padding-bottom: 15px; margin-bottom: 25px; }
        
        /* Forms */
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-size: 13px; font-weight: 600; margin-bottom: 8px; color: #888; }
        .form-group input, .form-group select { padding: 12px 15px; border: 1px solid var(--border-light); border-radius: 8px; outline: none; font-family: inherit; color: var(--text-dark); }
        .form-group input:focus, .form-group select:focus { border-color: var(--primary-purple); }
        .btn { background: var(--primary-purple); color: white; border: none; padding: 12px 25px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.3s; font-family: inherit; }
        .btn:hover { background: var(--dark-purple); }
        
        /* Alerts */
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; font-size: 14px; }
        .alert-success { background: #E8F5E9; color: #2E7D32; border: 1px solid #C8E6C9; }
        .alert-error { background: #FFEBEE; color: #C62828; border: 1px solid #FFCDD2; }

        /* Data Tables */
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 15px; text-align: left; border-bottom: 1px solid var(--border-light); font-size: 14px; }
        .data-table th { background-color: var(--bg-light); color: #888; font-size: 13px; }
        
        /* Badges */
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; }
        .badge-percentage { background: #E3F2FD; color: #1565C0; }
        .badge-fixed { background: #F3E5F5; color: #7B1FA2; }
        .badge-active { background: #E8F5E9; color: #2E7D32; }
        .badge-inactive { background: #FFEBEE; color: #C62828; }
        .mono { font-family: monospace; background: var(--bg-light); padding: 3px 6px; border-radius: 4px; color: #555; }
    </style>
</head>
<body>

    <div class="admin-sidebar">
        <h2>Puffy Admin</h2>
        <div class="admin-nav">
            <a href="indexA.php">Orders</a>
            <a href="products.php">Products</a>
            <a href="add_product.php">Add Product</a>
            <a href="coupons.php" class="active">Coupons</a>
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
            <h1>Coupons</h1>
        </div>

        <?php if ($success_msg): ?> <div class="alert alert-success"><?php echo $success_msg; ?></div> <?php endif; ?>
        <?php if ($error_msg): ?> <div class="alert alert-error"><?php echo $error_msg; ?></div> <?php endif; ?>

        <div class="card">
            <h2>Generate New Coupon</h2>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Coupon Code (e.g. SUMMER20)</label>
                        <input type="text" name="code" required placeholder="User types this...">
                    </div>
                    <div class="form-group">
                        <label>Discount Type</label>
                        <select name="type" required>
                            <option value="percentage">Percentage (%)</option>
                            <option value="fixed">Fixed Amount (TND)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Discount Value</label>
                        <input type="number" name="value" step="0.001" required placeholder="e.g. 15 or 10.000">
                    </div>
                    <div class="form-group">
                        <label>Minimum Order (TND)</label>
                        <input type="number" name="min_order" step="0.001" placeholder="0.000">
                    </div>
                    <div class="form-group">
                        <label>Max Uses (Leave blank for unlimited)</label>
                        <input type="number" name="max_uses" placeholder="e.g. 100">
                    </div>
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" name="start_date" required>
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" name="end_date" required>
                    </div>
                </div>
                <button type="submit" name="add_coupon" class="btn">+ Generate Coupon</button>
            </form>
        </div>

        <div class="card">
            <h2>Existing Coupons</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID / Code</th>
                        <th>Type / Value</th>
                        <th>Min Order</th>
                        <th>Uses</th>
                        <th>Validity</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($coupons)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #888; padding: 30px;">No coupons generated yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($coupons as $c): ?>
                            <tr>
                                <td>
                                    <div style="font-size: 12px; color: #888;">ID: <span class="mono"><?php echo htmlspecialchars($c['id']); ?></span></div>
                                    <div style="font-weight: 600; font-size: 15px; margin-top: 4px;"><?php echo htmlspecialchars($c['code']); ?></div>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $c['type']; ?>"><?php echo ucfirst($c['type']); ?></span>
                                    <div style="margin-top: 6px; font-weight: 600;">
                                        <?php 
                                            if ($c['type'] == 'percentage') {
                                                echo rtrim(rtrim($c['value'], '0'), '.') . '%';
                                            } else {
                                                echo number_format($c['value'], 3) . ' TND';
                                            }
                                        ?>
                                    </div>
                                </td>
                                <td style="font-weight: 500;"><?php echo number_format($c['min_order'], 3); ?> TND</td>
                                <td style="color: #666; font-weight: 500;">
                                    <?php echo $c['used_count']; ?> / 
                                    <?php echo $c['max_uses'] === null ? '∞' : $c['max_uses']; ?>
                                </td>
                                <td style="font-size: 13px; color: #666;">
                                    <?php echo date('M d, Y', strtotime($c['start_date'])); ?> <br>to<br> 
                                    <?php echo date('M d, Y', strtotime($c['end_date'])); ?>
                                </td>
                                <td>
                                    <?php if ($c['is_active']): ?>
                                        <span class="badge badge-active">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-inactive">Inactive</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>