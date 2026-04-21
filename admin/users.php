<?php
// admin/users.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- 1. ADMIN SECURITY CHECK ---
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../pages/index.php");
    exit();
}

require_once '../config/Database.php';
$database = new Database();
$db = $database->getConnection();

$message = '';
$message_type = '';
$page = 'users'; // Set active page for sidebar

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
    // Ignore error if table is missing or DB issue
}

// --- 2. HANDLE DELETE REQUEST ---
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    
    try {
        // Double check we aren't deleting an admin via URL manipulation
        $check_query = "SELECT role FROM users WHERE id = :id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':id', $delete_id);
        $check_stmt->execute();
        $user_to_delete = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if ($user_to_delete && $user_to_delete['role'] === 'admin') {
            $message = "Unauthorized: Admin accounts cannot be deleted from this interface.";
            $message_type = "error";
        } else {
            $query = "DELETE FROM users WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $delete_id);
            if ($stmt->execute()) {
                $message = "User account deleted successfully.";
                $message_type = "success";
            }
        }
    } catch(PDOException $e) {
        $message = "Error: This user is linked to existing orders and cannot be deleted.";
        $message_type = "error";
    }
}

// --- 3. FETCH USERS (EXCLUDING ADMINS) ---
try {
    // We filter using WHERE role != 'admin' to hide all administrators
    $query = "SELECT id, first_name, last_name, email, date_of_birth, role, created_at 
              FROM users 
              WHERE role != 'admin' 
              ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $message = "Error fetching users: " . $e->getMessage();
    $message_type = "error";
    $users = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Users | Puffy Style</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { 
            --primary-purple: #7B61FF; 
            --dark-purple: #5A4FCF; 
            --bg-light: #F8F9FA; 
            --text-dark: #333333; 
            --border-light: #EAEAEA; 
            --danger-red: #DC3545; 
        }
        body { margin: 0; font-family: 'Poppins', sans-serif; background-color: var(--bg-light); color: var(--text-dark); display: flex; }

        /* Sidebar */
        .admin-sidebar { width: 250px; background: white; height: 100vh; position: fixed; box-shadow: 2px 0 10px rgba(0,0,0,0.05); display: flex; flex-direction: column; z-index: 100;}
        .admin-sidebar h2 { text-align: center; color: var(--primary-purple); padding: 20px 0; margin: 0; }
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
        }
        .admin-nav a:hover, .admin-nav a.active { background: var(--bg-light); border-left-color: var(--primary-purple); color: var(--primary-purple); }
        
        /* Notification Badge CSS */
        .nav-badge {
            background-color: #FF4D6D;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
        }

        .sidebar-footer { padding: 30px; text-align: center; }
        .btn-logout { background: #FF4D4D; color: white; padding: 10px 30px; border-radius: 25px; text-decoration: none; font-weight: 600; font-size: 14px; display: inline-block; }

        /* Content Area */
        .admin-content { margin-left: 250px; padding: 40px; width: calc(100% - 250px); }
        .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        
        .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #D4EDDA; color: #155724; }
        .alert-error { background: #F8D7DA; color: #721C24; }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid var(--border-light); }
        th { font-weight: 600; color: #666; background-color: #fcfcfc; }
        
        .id-badge { background: #eee; padding: 4px 8px; border-radius: 4px; font-family: monospace; font-weight: bold; font-size: 12px; }
        .role-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; background: #E2F0FF; color: #007BFF; }

        .btn-delete { background: #ffe3e6; color: var(--danger-red); padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 500; border: none; cursor: pointer; }
        .btn-delete:hover { background: #ffcdd2; }
    </style>
</head>
<body>
    <div class="admin-sidebar">
        <h2>Puffy Admin</h2>
        <div class="admin-nav">
            <a href="indexA.php">
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
            <a href="users.php" class="<?php echo $page === 'users' ? 'active' : ''; ?>">
                <span>Users</span>
            </a>
            <a href="indexA.php?page=messages">
                <span>Contact Messages</span>
                <?php if ($unread_count > 0): ?>
                    <span class="nav-badge"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </a>
            <a href="indexA.php?page=resets">
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
            <h1>Customer Management</h1>
        </div>

        <div class="card">
            <?php if ($message): ?>
                <div class="alert <?php echo $message_type === 'success' ? 'alert-success' : 'alert-error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if (count($users) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Full Name</th>
                            <th>Email Address</th>
                            <th>Date of Birth</th>
                            <th>Account Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><span class="id-badge"><?php echo htmlspecialchars($user['id']); ?></span></td>
                            <td><strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['date_of_birth']); ?></td>
                            <td><?php echo date('M d, Y - H:i', strtotime($user['created_at'])); ?></td>
                            <td>
                                <a href="users.php?delete=<?php echo $user['id']; ?>" 
                                   class="btn-delete" 
                                   onclick="return confirm('Are you sure you want to permanently delete this user?');">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #777; padding: 20px;">No customers found in the database.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>