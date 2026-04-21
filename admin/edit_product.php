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

$success_msg = '';
$error_msg = '';
$product = null;

// Ensure we have a product ID in the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('No product ID provided!'); window.location.href='products.php';</script>";
    exit();
}

$product_id = $_GET['id'];

// Fetch existing product data
$stmt = $db->prepare("SELECT * FROM products WHERE id = :id OR sku = :id LIMIT 1");
$stmt->execute([':id' => $product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo "<script>alert('Product not found!'); window.location.href='products.php';</script>";
    exit();
}

// Handle Form Submission for Updating
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $name = trim($_POST['name'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock_quantity = (int)($_POST['stock_quantity'] ?? 0);
    $flavor = trim($_POST['flavor'] ?? '');
    $puff_count = (int)($_POST['puff_count'] ?? 0);
    $short_description = trim($_POST['short_description'] ?? '');

    // --- PROMOTION CALCULATION LOGIC ---
    $promo_type = $_POST['promo_type'] ?? 'none';
    $promo_value = (float)($_POST['promo_value'] ?? 0);
    $promotional_price = 0;

    // Server-side calculation to ensure accuracy
    if ($promo_type === 'percentage' && $promo_value > 0) {
        $promotional_price = $price - ($price * ($promo_value / 100));
    } elseif ($promo_type === 'amount' && $promo_value > 0) {
        $promotional_price = $price - $promo_value;
    } else {
        $promo_type = 'none';
        $promo_value = 0;
    }

    // Ensure price doesn't drop below 0
    if ($promotional_price < 0) {
        $promotional_price = 0;
    }

    // --- IMAGE UPLOAD LOGIC ---
    $upload_dir = '../uploads/'; 
    
    $final_image_main = $product['image_main'];
    $final_additional_images = $product['additional_images'];

    // 1. Process Main Image
    if (isset($_FILES['image_main']) && $_FILES['image_main']['error'] == UPLOAD_ERR_OK) {
        $file_ext = strtolower(pathinfo($_FILES['image_main']['name'], PATHINFO_EXTENSION));
        $new_main_name = time() . '_main_' . uniqid() . '.' . $file_ext;
        
        if (move_uploaded_file($_FILES['image_main']['tmp_name'], $upload_dir . $new_main_name)) {
            $final_image_main = $new_main_name;
        }
    }

    // 2. Process Additional Images
    if (isset($_FILES['additional_images']) && !empty($_FILES['additional_images']['name'][0])) {
        $uploaded_additional = [];
        foreach ($_FILES['additional_images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['additional_images']['error'][$key] == UPLOAD_ERR_OK) {
                $file_ext = strtolower(pathinfo($_FILES['additional_images']['name'][$key], PATHINFO_EXTENSION));
                $new_add_name = time() . '_gal_' . uniqid() . '.' . $file_ext;
                
                if (move_uploaded_file($tmp_name, $upload_dir . $new_add_name)) {
                    $uploaded_additional[] = $new_add_name;
                }
            }
        }
        
        if (!empty($uploaded_additional)) {
            $final_additional_images = json_encode($uploaded_additional);
        }
    }

    try {
        $update_query = "UPDATE products SET 
            name = :name, 
            price = :price, 
            promo_type = :promo_type,
            promo_value = :promo_value,
            promotional_price = :promotional_price, 
            stock_quantity = :stock_quantity, 
            flavor = :flavor, 
            puff_count = :puff_count, 
            short_description = :short_description,
            image_main = :image_main,
            additional_images = :additional_images
            WHERE id = :id";

        $update_stmt = $db->prepare($update_query);
        $update_stmt->execute([
            ':name' => $name,
            ':price' => $price,
            ':promo_type' => $promo_type,
            ':promo_value' => $promo_value,
            ':promotional_price' => $promotional_price, 
            ':stock_quantity' => $stock_quantity,
            ':flavor' => $flavor,
            ':puff_count' => $puff_count,
            ':short_description' => $short_description,
            ':image_main' => $final_image_main,
            ':additional_images' => $final_additional_images,
            ':id' => $product['id']
        ]);

        $success_msg = "Product successfully updated!";
        
        // Refresh product data
        $stmt->execute([':id' => $product['id']]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        $error_msg = "Failed to update product. Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product - Puffy Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-purple: #7B61FF; 
            --dark-purple: #5A4FCF;
            --bg-light: #F8F9FA;
            --text-dark: #333333;
            --border-light: #EAEAEA;
            --accent-pink: #FFB6C1;
        }
        
        body { margin: 0; font-family: 'Poppins', sans-serif; background-color: var(--bg-light); color: var(--text-dark); }
        
        /* Sidebar */
        .admin-sidebar { width: 250px; background: white; height: 100vh; position: fixed; box-shadow: 2px 0 10px rgba(0,0,0,0.05); padding: 20px 0; }
        .admin-sidebar h2 { text-align: center; color: var(--primary-purple); margin-bottom: 30px; }
        .admin-nav a { display: block; padding: 15px 25px; color: var(--text-dark); text-decoration: none; font-weight: 500; border-left: 4px solid transparent; transition: 0.3s; }
        .admin-nav a:hover, .admin-nav a.active { background: var(--bg-light); border-left-color: var(--primary-purple); color: var(--primary-purple); }
        .admin-content { margin-left: 250px; padding: 40px; }
        .sidebar-footer {
            padding: 180px 80px;
        }
        /* Header */
        .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header-top h1 { margin: 0; }
        .btn-back { background: #EAEAEA; color: var(--text-dark); padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; transition: 0.2s; }
        .btn-back:hover { background: #D0D0D0; }

        /* Forms */
        .card { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); padding: 30px; margin-bottom: 30px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-group label { font-size: 13px; font-weight: 600; margin-bottom: 8px; color: #555; }
        .form-group input[type="text"], .form-group input[type="number"], .form-group select, .form-group textarea { padding: 12px 15px; border: 1px solid var(--border-light); border-radius: 8px; outline: none; font-family: inherit; color: var(--text-dark); font-size: 14px; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--primary-purple); box-shadow: 0 0 0 3px rgba(123, 97, 255, 0.1); }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .btn-logout { background: #FF4D4D; color: white; padding: 8px 20px; border-radius: 20px; text-decoration: none; font-weight: 600; font-size: 14px; }
        /* Promos */
        .promo-box { background-color: #FFF5F7; border: 1px dashed var(--accent-pink); padding: 20px; border-radius: 8px; }
        .promo-box input[readonly] { background-color: #f1f1f1; cursor: not-allowed; border-color: var(--border-light); font-weight: bold; color: #FF4D6D;}

        .file-upload-box { border: 2px dashed var(--border-light); padding: 15px; border-radius: 8px; background: #fafafa; font-size: 13px; }
        .current-image-text { font-size: 12px; color: #888; margin-top: 8px; }
        .current-img-preview { max-width: 60px; border-radius: 6px; margin-top: 5px; border: 1px solid #ddd; }

        .btn { background: var(--primary-purple); color: white; border: none; padding: 14px 25px; border-radius: 8px; font-weight: 600; font-size: 15px; cursor: pointer; transition: 0.3s; font-family: inherit; width: 100%; max-width: 250px; }
        .btn:hover { background: var(--dark-purple); }
        
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; font-size: 14px; }
        .alert-success { background: #E8F5E9; color: #2E7D32; border: 1px solid #C8E6C9; }
        .alert-error { background: #FFEBEE; color: #C62828; border: 1px solid #FFCDD2; }
    </style>
</head>
<body>

    <div class="admin-sidebar">
        <h2>Puffy Admin</h2>
        <div class="admin-nav">
            <a href="indexA.php">Orders</a>
            <a href="products.php" class="active">Products</a>
            <a href="add_product.php">Add Product</a>
            <a href="coupons.php">Coupons</a>
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
            <h1>Edit Product</h1>
            <a href="products.php" class="btn-back">← Back to Products</a>
        </div>

        <?php if ($success_msg): ?> <div class="alert alert-success"><?php echo $success_msg; ?></div> <?php endif; ?>
        <?php if ($error_msg): ?> <div class="alert alert-error"><?php echo $error_msg; ?></div> <?php endif; ?>

        <div class="card">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    
                    <div class="form-group full-width">
                        <label>Product Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Regular Price (TND)</label>
                        <input type="number" step="0.001" name="price" id="regular_price" value="<?php echo htmlspecialchars($product['price'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Stock Quantity</label>   
                        <input type="number" name="stock_quantity" value="<?php echo htmlspecialchars($product['stock_quantity'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group full-width promo-box">
                        <div class="form-grid" style="margin-bottom: 0;">
                            <div class="form-group">
                                <label style="color: #FF4D6D;">Discount Type</label>
                                <select name="promo_type" id="promo_type">
                                    <option value="none" <?php echo (($product['promo_type'] ?? '') == 'none') ? 'selected' : ''; ?>>No Discount</option>
                                    <option value="percentage" <?php echo (($product['promo_type'] ?? '') == 'percentage') ? 'selected' : ''; ?>>Percentage (%)</option>
                                    <option value="amount" <?php echo (($product['promo_type'] ?? '') == 'amount') ? 'selected' : ''; ?>>Fixed Amount (TND)</option>
                                </select>
                            </div>
                            
                            <div class="form-group" id="promo_value_container" style="<?php echo (($product['promo_type'] ?? 'none') == 'none') ? 'display:none;' : ''; ?>">
                                <label id="promo_value_label" style="color: #FF4D6D;">Discount Value</label>
                                <input type="number" step="0.001" name="promo_value" id="promo_value" value="<?php echo htmlspecialchars($product['promo_value'] ?? '0'); ?>">
                            </div>

                            <div class="form-group">
                                <label>Final Promotional Price (TND)</label>
                                <input type="text" id="final_promo_price" value="<?php echo htmlspecialchars($product['promotional_price'] ?? '0'); ?>" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Puff Count</label>
                        <input type="number" name="puff_count" value="<?php echo htmlspecialchars($product['puff_count'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Flavors (Comma separated)</label>
                        <input type="text" name="flavor" value="<?php echo htmlspecialchars($product['flavor'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Main Image (Leave empty to keep current)</label>
                        <div class="file-upload-box">
                            <input type="file" name="image_main" accept="image/*">
                            <?php if (!empty($product['image_main'])): ?>
                                <div class="current-image-text">
                                    Current: <?php echo htmlspecialchars($product['image_main']); ?><br>
                                    <img src="../uploads/<?php echo htmlspecialchars($product['image_main']); ?>" class="current-img-preview" alt="Main" onerror="this.style.display='none'">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Gallery Images (Leave empty to keep current)</label>
                        <div class="file-upload-box">
                            <input type="file" name="additional_images[]" accept="image/*" multiple>
                            <?php if (!empty($product['additional_images']) && $product['additional_images'] !== '[]'): ?>
                                <div class="current-image-text">
                                    Currently has saved gallery images. Uploading new ones will replace them.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Short Description</label>
                        <textarea name="short_description"><?php echo htmlspecialchars($product['short_description'] ?? ''); ?></textarea>
                    </div>

                </div>
                
                <button type="submit" name="update_product" class="btn">Save Changes</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const priceInput = document.getElementById('regular_price');
            const promoTypeSelect = document.getElementById('promo_type');
            const promoValueInput = document.getElementById('promo_value');
            const promoValueContainer = document.getElementById('promo_value_container');
            const promoValueLabel = document.getElementById('promo_value_label');
            const finalPromoPriceInput = document.getElementById('final_promo_price');

            function calculatePromo() {
                let price = parseFloat(priceInput.value) || 0;
                let type = promoTypeSelect.value;
                let val = parseFloat(promoValueInput.value) || 0;
                let finalPrice = 0;

                if (type === 'none') {
                    promoValueContainer.style.display = 'none';
                    finalPromoPriceInput.value = '0.000'; // No promotion active
                } else {
                    promoValueContainer.style.display = 'block';
                    
                    if (type === 'percentage') {
                        promoValueLabel.innerText = 'Discount Percentage (%)';
                        finalPrice = price - (price * (val / 100));
                    } else if (type === 'amount') {
                        promoValueLabel.innerText = 'Discount Amount (TND)';
                        finalPrice = price - val;
                    }

                    // Prevent negative prices
                    if (finalPrice < 0) finalPrice = 0;
                    
                    finalPromoPriceInput.value = finalPrice.toFixed(3);
                }
            }

            // Listen for typing or changing options to calculate instantly
            priceInput.addEventListener('input', calculatePromo);
            promoTypeSelect.addEventListener('change', calculatePromo);
            promoValueInput.addEventListener('input', calculatePromo);
            
            // Run once on load to ensure accuracy
            calculatePromo();
        });
    </script>
</body>
</html>