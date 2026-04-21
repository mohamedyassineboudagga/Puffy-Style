<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../pages/index.php");
    exit();
}
require_once '../config/Database.php';
$message = '';
$message_type = '';
$page = 'add_product';
function productIDExists($db, $id) {
    $stmt = $db->prepare("SELECT id FROM products WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    return $stmt->rowCount() > 0;
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    $custom_num = preg_replace('/[^0-9]/', '', $_POST['product_num']); 
    $product_id = 'PRD' . $custom_num;
    if (productIDExists($db, $product_id)) {
        $message = "Error: The ID <strong>$product_id</strong> is already taken.";
        $message_type = "error";
    } else {
        $name = htmlspecialchars(strip_tags($_POST['name']));
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        $description = htmlspecialchars(strip_tags($_POST['description']));
        $price = $_POST['price'];
        $stock_quantity = !empty($_POST['stock_quantity']) ? (int)$_POST['stock_quantity'] : 0;
        $flavor = htmlspecialchars(strip_tags($_POST['flavor']));
        $puff_count = !empty($_POST['puff_count']) ? (int)$_POST['puff_count'] : NULL;
        $image_main = "default_puff.png"; 
        $additional_images = [];
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
            $target_directory = "../photos/";
            if (!is_dir($target_directory)) {
                mkdir($target_directory, 0777, true);
            }
            $file_count = count($_FILES['images']['name']);
            for ($i = 0; $i < $file_count; $i++) {
                if ($_FILES['images']['error'][$i] == 0) {
                    if (in_array($_FILES['images']['type'][$i], $allowed_types)) {                    
                        $clean_filename = time() . '_' . $i . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES['images']['name'][$i]));
                        $target_file = $target_directory . $clean_filename;
                        if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $target_file)) {
                            if ($i === 0) { $image_main = $clean_filename; } 
                            else { $additional_images[] = $clean_filename; }
                        }
                    }
                }
            }
        }
        $additional_images_json = json_encode($additional_images);
        try {
            $query = "INSERT INTO products 
                      (id, name, slug, description, price, stock_quantity, flavor, puff_count, image_main, additional_images) 
                      VALUES 
                      (:id, :name, :slug, :description, :price, :stock_quantity, :flavor, :puff_count, :image_main, :additional_images)";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':id' => $product_id, ':name' => $name, ':slug' => $slug, ':description' => $description, 
                ':price' => $price, ':stock_quantity' => $stock_quantity, ':flavor' => $flavor, 
                ':puff_count' => $puff_count, ':image_main' => $image_main, ':additional_images' => $additional_images_json
            ]);
            $message = "Success! Product $product_id has been added.";
            $message_type = "success";
        } catch(PDOException $e) {
            $message = "Database Error: " . $e->getMessage();
            $message_type = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Add Product | Puffy Style</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { 
            --primary-purple: #7B61FF; 
            --dark-purple: #5A4FCF; 
            --bg-light: #F8F9FA; 
            --text-dark: #333333; 
        }
        body { margin: 0; font-family: 'Poppins', sans-serif; background-color: var(--bg-light); color: var(--text-dark); display: flex; }
        .admin-sidebar { 
            width: 250px; 
            background: white; 
            height: 100vh; 
            position: fixed; 
            left: 0;
            top: 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05); 
            display: flex;
            flex-direction: column;
            z-index: 1000;
        }
        .sidebar-header h2 { text-align: center; color: var(--primary-purple); padding: 20px 0; margin: 0; }
        .admin-nav { flex-grow: 1; }
        .admin-nav a { 
            display: block; 
            padding: 15px 25px; 
            color: var(--text-dark); 
            text-decoration: none; 
            font-weight: 500; 
            border-left: 4px solid transparent; 
            transition: 0.3s;
        }
        .admin-nav a:hover, .admin-nav a.active { 
            background: var(--bg-light); 
            border-left-color: var(--primary-purple); 
            color: var(--primary-purple); 
        }
        .sidebar-footer { padding: 30px; text-align: center; }
        .btn-logout { 
            background: #FF4D4D; 
            color: white; 
            padding: 10px 30px; 
            border-radius: 25px; 
            text-decoration: none; 
            font-weight: 600; 
            font-size: 14px; 
            display: inline-block;
            transition: 0.3s;
        }
        .btn-logout:hover { background: #e60000; box-shadow: 0 4px 10px rgba(255, 77, 77, 0.3); }
        .admin-content { margin-left: 250px; padding: 40px; width: calc(100% - 250px); }
        .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); max-width: 800px; margin-top: 20px; }
        .form-row { display: flex; gap: 20px; }
        .input-group { margin-bottom: 20px; flex: 1; }
        label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px; }
        input, textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; font-family: inherit; }
        .btn-submit { background: var(--primary-purple); color: white; border: none; padding: 15px; border-radius: 8px; font-weight: 600; cursor: pointer; width: 100%; transition: 0.3s; }
        .btn-submit:hover { background: var(--dark-purple); }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #D4EDDA; color: #155724; }
        .alert-error { background: #F8D7DA; color: #721C24; }
        .flex-input { display: flex; }
        .id-prefix { background: #eee; padding: 12px; border: 1px solid #ddd; border-right: none; border-radius: 8px 0 0 8px; font-weight: bold; display: flex; align-items: center; }
        .flex-input input { border-radius: 0 8px 8px 0; }
    </style>
</head>
<body>
    <div class="admin-sidebar">
        <div class="sidebar-header">
            <h2>Puffy Admin</h2>
        </div>
        <div class="admin-nav">
            <a href="indexA.php?page=orders" class="<?php echo ($page === 'orders') ? 'active' : ''; ?>">Orders</a>
            <a href="products.php" class="<?php echo ($page === 'products') ? 'active' : ''; ?>">Products</a>
            <a href="add_product.php" class="<?php echo ($page === 'add_product') ? 'active' : ''; ?>">Add Product</a>
            <a href="Coupons.php" class="<?php echo ($page === 'coupons') ? 'active' : ''; ?>">Coupons</a>
            <a href="users.php">Users</a>
            <a href="indexA.php?page=messages" class="<?php echo ($page === 'messages') ? 'active' : ''; ?>">Contact Messages</a>
            <a href="indexA.php?page=resets" class="<?php echo ($page === 'resets') ? 'active' : ''; ?>">Reset Requests</a>
            <a href="../pages/index.php">View Store</a>
        </div>
        <div class="sidebar-footer">
            <a href="../pages/logout.php" class="btn-logout">Logout</a>
        </div>
    </div>
    <div class="admin-content">
        <h1>Add New Puff Product</h1>
        <div class="card">
            <?php if ($message): ?>
                <div class="alert <?php echo $message_type === 'success' ? 'alert-success' : 'alert-error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            <form action="add_product.php" method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="input-group">
                        <label>Product ID Number *</label>
                        <div class="flex-input">
                            <span class="id-prefix">PRD</span>
                            <input type="number" name="product_num" required placeholder="e.g. 101">
                        </div>
                    </div>
                    <div class="input-group">
                        <label>Product Name *</label>
                        <input type="text" name="name" required placeholder="e.g., Vozol Gear">
                    </div>
                </div>
                <div class="form-row">
                    <div class="input-group">
                        <label>Price (TND) *</label>
                        <input type="number" step="0.001" name="price" required placeholder="69.000">
                    </div>
                    <div class="input-group">
                        <label>Stock Quantity *</label>
                        <input type="number" name="stock_quantity" required value="50">
                    </div>
                </div>
                <div class="form-row">
                    <div class="input-group">
                        <label>Flavors</label>
                        <input type="text" name="flavor" placeholder="Blueberry, Mint">
                    </div>
                    <div class="input-group">
                        <label>Puff Count</label>
                        <input type="number" name="puff_count" placeholder="10000">
                    </div>
                </div>
                <div class="input-group">
                    <label>Description *</label>
                    <textarea name="description" required rows="4"></textarea>
                </div>
                <div class="input-group">
                    <label>Images *</label>
                    <input type="file" name="images[]" multiple required>
                </div>
                <button type="submit" class="btn-submit">+ Create Product</button>
            </form>
        </div>
    </div>
</body>
</html>