<?php 
// admin/products.php 
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
$page = 'products'; 

// --- 2. HANDLE DELETE REQUEST --- 
if (isset($_GET['delete']) && !empty($_GET['delete'])) { 
    $delete_id = $_GET['delete']; 
    try { 
        $db->beginTransaction();

        // Step A: Remove from order_items first to avoid foreign key errors
        $clear_orders_query = "DELETE FROM order_items WHERE product_id = :id";
        $clear_stmt = $db->prepare($clear_orders_query);
        $clear_stmt->bindParam(':id', $delete_id);
        $clear_stmt->execute();

        // Step B: Get image names before deleting from database
        $img_query = "SELECT image_main, additional_images FROM products WHERE id = :id"; 
        $img_stmt = $db->prepare($img_query); 
        $img_stmt->bindParam(':id', $delete_id); 
        $img_stmt->execute(); 
        $images = $img_stmt->fetch(PDO::FETCH_ASSOC); 

        // Step C: Delete the actual product
        $delete_query = "DELETE FROM products WHERE id = :id"; 
        $delete_stmt = $db->prepare($delete_query); 
        $delete_stmt->bindParam(':id', $delete_id); 
        
        if ($delete_stmt->execute()) { 
            // Step D: Delete physical files from the folder
            if ($images) { 
                if ($images['image_main'] !== 'default_puff.png' && file_exists("../photos/" . $images['image_main'])) { 
                    unlink("../photos/" . $images['image_main']); 
                } 
                if (!empty($images['additional_images'])) { 
                    $additional = json_decode($images['additional_images'], true); 
                    if (is_array($additional)) { 
                        foreach ($additional as $img) { 
                            if (file_exists("../photos/" . $img)) { unlink("../photos/" . $img); } 
                        } 
                    } 
                } 
            } 
            $db->commit();
            $message = "Product and associated order records deleted successfully."; 
            $message_type = "success"; 
        } 
    } catch(PDOException $e) { 
        $db->rollBack();
        $message = "Error deleting product: " . $e->getMessage(); 
        $message_type = "error"; 
    } 
} 

// --- 3. FETCH ALL PRODUCTS --- 
try { 
    $query = "SELECT * FROM products ORDER BY id DESC"; 
    $stmt = $db->prepare($query); 
    $stmt->execute(); 
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC); 
} catch(PDOException $e) { 
    $message = "Error fetching products: " . $e->getMessage(); 
    $message_type = "error"; 
    $products = []; 
} 
?> 
<!DOCTYPE html> 
<html lang="en"> 
<head> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Admin - Manage Products | Puffy Style</title> 
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"> 
    <style> 
        :root { --primary-purple: #7B61FF; --dark-purple: #5A4FCF; --bg-light: #F8F9FA; --text-dark: #333333; --border-light: #EAEAEA; --danger-red: #DC3545; } 
        body { margin: 0; font-family: 'Poppins', sans-serif; background-color: var(--bg-light); color: var(--text-dark); display: flex; } 

        .admin-sidebar { width: 250px; background: white; height: 100vh; position: fixed; box-shadow: 2px 0 10px rgba(0,0,0,0.05); display: flex; flex-direction: column; z-index: 100;} 
        .admin-sidebar h2 { text-align: center; color: var(--primary-purple); padding: 20px 0; margin: 0; } 
        .admin-nav { flex-grow: 1; }
        .admin-nav a { display: block; padding: 15px 25px; color: var(--text-dark); text-decoration: none; font-weight: 500; border-left: 4px solid transparent; } 
        .admin-nav a:hover, .admin-nav a.active { background: var(--bg-light); border-left-color: var(--primary-purple); color: var(--primary-purple); } 
        .sidebar-footer { padding: 30px; text-align: center; }
        .btn-logout { background: #FF4D4D; color: white; padding: 10px 30px; border-radius: 25px; text-decoration: none; font-weight: 600; font-size: 14px; display: inline-block; }

        .admin-content { margin-left: 250px; padding: 40px; width: calc(100% - 250px); } 
        .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; } 
        .btn-add { background: var(--primary-purple); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; text-decoration: none; transition: 0.3s; } 
        
        .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); } 
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; } 
        .alert-success { background: #D4EDDA; color: #155724; } 
        .alert-error { background: #F8D7DA; color: #721C24; } 

        table { width: 100%; border-collapse: collapse; } 
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid var(--border-light); } 
        th { font-weight: 600; color: #666; background-color: #fcfcfc; } 
        .product-img { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; } 
        .id-badge { background: #eee; padding: 4px 8px; border-radius: 4px; font-family: monospace; font-weight: bold; font-size: 13px; }
        
        .actions { display: flex; gap: 10px; } 
        .btn-edit { background: #e0d9ff; color: var(--primary-purple); padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 14px; } 
        .btn-delete { background: #ffe3e6; color: var(--danger-red); padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 14px; border: none; cursor: pointer; } 
        .stock-badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; } 
        .in-stock { background: #D4EDDA; color: #155724; } 
        .out-of-stock { background: #F8D7DA; color: #721C24; } 
        
        .old-price { text-decoration: line-through; color: #999; font-size: 12px; } 
        .promo-price { color: var(--danger-red); font-weight: 700; } 
        .promo-badge { background: #FFF0F2; color: var(--danger-red); padding: 2px 5px; border-radius: 4px; font-size: 10px; font-weight: bold; border: 1px dashed var(--danger-red); } 
    </style> 
</head> 
<body> 
    <div class="admin-sidebar"> 
        <h2>Puffy Admin</h2> 
        <div class="admin-nav"> 
            <a href="indexA.php?page=orders">Orders</a> 
            <a href="products.php" class="active">Products</a> 
            <a href="add_product.php">Add Product</a> 
            <a href="Coupons.php">Coupons</a> 
            <a href="users.php">Users</a>
            <a href="indexA.php?page=messages">Contact Messages</a> 
            <a href="indexA.php?page=resets">Reset Requests</a> 
            <a href="../pages/index.php">View Store</a> 
        </div> 
        <div class="sidebar-footer"> 
            <a href="../pages/logout.php" class="btn-logout">Logout</a> 
        </div> 
    </div> 

    <div class="admin-content"> 
        <div class="header-top"> 
            <h1>Manage Products</h1> 
            <a href="add_product.php" class="btn-add">+ Add New Product</a> 
        </div> 

        <div class="card"> 
            <?php if ($message): ?> 
                <div class="alert <?php echo $message_type === 'success' ? 'alert-success' : 'alert-error'; ?>"> 
                    <?php echo $message; ?> 
                </div> 
            <?php endif; ?> 

            <?php if (count($products) > 0): ?> 
                <table> 
                    <thead> 
                        <tr> 
                            <th>Image</th> 
                            <th>ID</th> 
                            <th>Name</th> 
                            <th>Price & Promo</th> 
                            <th>Puffs</th> 
                            <th>Stock</th> 
                            <th>Actions</th> 
                        </tr> 
                    </thead> 
                    <tbody> 
                        <?php foreach ($products as $product): ?> 
                        <tr> 
                            <td> 
                                <img src="../photos/<?php echo htmlspecialchars($product['image_main']); ?>" alt="Product" class="product-img" onerror="this.src='../photos/default_puff.png'"> 
                            </td> 
                            <td><span class="id-badge"><?php echo htmlspecialchars($product['id']); ?></span></td> 
                            <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td> 
                            <td> 
                                <?php if (!empty($product['promo_type']) && $product['promo_type'] !== 'none' && $product['promotional_price'] > 0): ?> 
                                    <span class="old-price"><?php echo number_format($product['price'], 3); ?> TND</span><br>
                                    <span class="promo-price"><?php echo number_format($product['promotional_price'], 3); ?> TND</span> 
                                <?php else: ?> 
                                    <strong><?php echo number_format($product['price'], 3); ?> TND</strong> 
                                <?php endif; ?> 
                            </td> 
                            <td><?php echo $product['puff_count'] ? number_format($product['puff_count']) : '-'; ?></td> 
                            <td> 
                                <?php if ($product['stock_quantity'] > 0): ?> 
                                    <span class="stock-badge in-stock"><?php echo $product['stock_quantity']; ?></span> 
                                <?php else: ?> 
                                    <span class="stock-badge out-of-stock">0</span> 
                                <?php endif; ?> 
                            </td> 
                            <td class="actions"> 
                                <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn-edit">Edit</a> 
                                <a href="products.php?delete=<?php echo $product['id']; ?>" class="btn-delete" onclick="return confirm('WARNING: This will also remove this product from past customer order history. Continue?');">Delete</a> 
                            </td> 
                        </tr> 
                        <?php endforeach; ?> 
                    </tbody> 
                </table> 
            <?php else: ?> 
                <p style="text-align: center; color: #777;">No products found.</p> 
            <?php endif; ?> 
        </div> 
    </div> 
</body> 
</html>