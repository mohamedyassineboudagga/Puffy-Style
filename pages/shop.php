<?php

// pages/shop.php

require_once '../config/Database.php';

require_once '../classes/Product.php';



// Include your existing header (which starts the session and shows the nav/avatar)

include_once '../includes/header.php';



// Initialize Database and Product

$database = new Database();

$db = $database->getConnection();



$product = new Product($db);

$stmt = $product->getAllActiveProducts();



// 1. Fetch ALL products into an array first

$all_products = $stmt->fetchAll(PDO::FETCH_ASSOC);



// --- SPECIFIED FILTER OPTIONS ---

$available_brands = ['Wotofo', 'Voopoo', 'Nexbar', 'Geek Bar', 'Vozol', 'Elfbar'];

$available_nicotine = ['6', '20', '50']; // Representing mg



// --- GET ACTIVE FILTERS (Now expecting arrays for multiple selections) ---

$min_price = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float)$_GET['min_price'] : null;

$max_price = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : null;



// Use arrays for multi-select. If not set, default to empty arrays.

$filter_puffs = isset($_GET['puffs']) && is_array($_GET['puffs']) ? $_GET['puffs'] : [];

// Added extra trim to prevent spaces from breaking the brand matches

$filter_brand = isset($_GET['brand']) && is_array($_GET['brand']) ? array_map('strtolower', array_map('trim', $_GET['brand'])) : [];

$filter_nicotine = isset($_GET['nicotine']) && is_array($_GET['nicotine']) ? $_GET['nicotine'] : [];



// --- GET SEARCH QUERY ---

$search_query = $_GET['search'] ?? '';



// --- APPLY FILTERS ---

$filtered_products = array_filter($all_products, function($row) use ($min_price, $max_price, $filter_puffs, $filter_brand, $search_query) {

    // Determine the actual price to filter against (promo or standard)

    $actual_price = (!empty($row['promo_type']) && $row['promo_type'] !== 'none' && !empty($row['promotional_price']) && $row['promotional_price'] > 0)

                    ? (float)$row['promotional_price'] : (float)$row['price'];



    // 0. Search Filter

    if (!empty($search_query)) {

        $term = strtolower(trim($search_query));

        $prod_name = strtolower($row['name'] ?? '');

        $prod_brand = strtolower($row['brand'] ?? '');

       

        if (strpos($prod_name, $term) === false && strpos($prod_brand, $term) === false) {

            return false;

        }

    }



    // 1. Price Filter

    if ($min_price !== null && $actual_price < $min_price) return false;

    if ($max_price !== null && $actual_price > $max_price) return false;



    // 2. Puffs Filter (Match ANY of the selected ranges)

    if (!empty($filter_puffs)) {

        // Bulletproof parsing: Strip everything except numbers from DB string (e.g., "30,000 Puffs" -> 30000)

        $raw_puff_str = strtolower(trim($row['puff_count'] ?? '0'));

        $puffs = (int)preg_replace('/[^0-9]/', '', $raw_puff_str);

       

        // Failsafe: if the database stores "30k" instead of 30000, $puffs above becomes '30'. This fixes that.

        if (strpos($raw_puff_str, 'k') !== false && $puffs < 1000) {

            $puffs *= 1000;

        }



        $puff_match = false;

        foreach ($filter_puffs as $range) {

            if ($range === 'up_to_6k' && $puffs > 0 && $puffs <= 6000) $puff_match = true;

            if ($range === '7k_to_20k' && ($puffs >= 7000 && $puffs <= 20000)) $puff_match = true;

            if ($range === '25k_to_40k' && ($puffs >= 25000 && $puffs <= 40000)) $puff_match = true;

            if ($range === 'more_than_40k' && $puffs > 40000) $puff_match = true;

        }

        if (!$puff_match) return false;

    }



    // 3. Brand Filter (Match if selected brand exists in product name)

    if (!empty($filter_brand)) {

        $row_name = strtolower(trim($row['name'] ?? ''));

        $brand_match = false;



        foreach ($filter_brand as $selected_brand) {

            // Check if the selected brand string exists inside the product name

            if (strpos($row_name, $selected_brand) !== false) {

                $brand_match = true;

                break; // Stop checking once we find a match

            }

        }



        // If none of the selected brands were found in the product name, filter it out

        if (!$brand_match) {

            return false;

        }

    }

    return true;

});



// Re-index the array so it works cleanly with pagination

$filtered_products = array_values($filtered_products);

$total_products = count($filtered_products);



// --- PAGINATION LOGIC ---

$limit = 6;

$total_pages = ceil($total_products / $limit);

$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;



// Constrain page between 1 and total_pages

$page = max(1, min($page, max(1, $total_pages)));



$offset = ($page - 1) * $limit;

$paginated_products = array_slice($filtered_products, $offset, $limit);

$num_products = count($paginated_products);



// Build query string for pagination links (so filters aren't lost when changing pages)

$query_params = $_GET;

unset($query_params['page']);

$query_string = http_build_query($query_params);

$url_prefix = "shop.php?" . ($query_string ? $query_string . "&" : "");

?>



<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>Shop - Puffy Style</title>

    <style>

        /* Base Shop Styling */

        .shop-container { max-width: 1200px; margin: 40px auto; padding: 0 20px; font-family: 'Poppins', sans-serif; }

        .shop-header { text-align: center; margin-bottom: 40px; }

        .shop-header h1 { color: var(--primary-purple, #7B61FF); font-size: 32px; margin-bottom: 10px; }

        .shop-header p { color: var(--text-muted, #777); font-size: 16px; }



        /* Sidebar & Wrapper Layout */

        .shop-wrapper { display: flex; flex-direction: column; gap: 30px; }

        @media (min-width: 768px) { .shop-wrapper { flex-direction: row; } }

       

        .sidebar-filters { width: 100%; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid var(--border-light, #EAEAEA); height: fit-content; }

        @media (min-width: 768px) { .sidebar-filters { width: 260px; flex-shrink: 0; } }



        .filter-title { font-size: 18px; font-weight: 700; color: var(--text-dark, #333); margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid var(--border-light, #EAEAEA); }

        .filter-group { margin-bottom: 25px; }

        .filter-group > label { display: block; font-size: 15px; font-weight: 600; margin-bottom: 10px; color: var(--text-dark, #333); }

       

        .price-inputs { display: flex; gap: 10px; align-items: center; }

        .price-inputs input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; font-family: inherit; font-size: 14px; box-sizing: border-box; }



        /* Multi-Select Checkbox Styling */

        .checkbox-list { display: flex; flex-direction: column; gap: 8px; }

        .checkbox-item { display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 400; cursor: pointer; color: #555; }

        .checkbox-item input[type="checkbox"] { width: 16px; height: 16px; cursor: pointer; margin: 0; accent-color: var(--primary-purple, #7B61FF); }

        .checkbox-item:hover { color: var(--primary-purple, #7B61FF); }



        .btn-filter { width: 100%; background: var(--primary-purple, #7B61FF); color: white; border: none; padding: 12px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.3s; margin-bottom: 10px; font-family: inherit; }

        .btn-filter:hover { background: var(--dark-purple, #5A4FCF); }

        .btn-reset { display: block; text-align: center; width: 100%; padding: 10px; color: var(--text-muted, #777); text-decoration: none; font-size: 14px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; transition: 0.3s; }

        .btn-reset:hover { background: #f5f5f5; color: #333; }



        /* Product Grid */

        .products-area { flex-grow: 1; }

        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 25px; }



        /* Product Card */

        .product-card { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid var(--border-light, #EAEAEA); transition: transform 0.3s ease, box-shadow 0.3s ease; display: flex; flex-direction: column; position: relative; }

        .product-card:hover { transform: translateY(-8px); box-shadow: 0 10px 25px rgba(123, 97, 255, 0.15); }

       

        .product-image-wrapper { position: relative; width: 100%; height: 250px; border-bottom: 1px solid var(--border-light, #EAEAEA); background: #fafafa; }

        .product-image-link { display: block; text-decoration: none; position: relative; height: 100%; }

        .product-image { width: 100%; height: 100%; object-fit: cover; transition: opacity 0.3s ease; }

        .product-image-link:hover .product-image:not(.img-out-of-stock) { opacity: 0.85; }

       

        .out-of-stock-text { position: absolute; top: 15px; left: 15px; color: #ff0000; font-size: 13px; font-weight: 800; z-index: 3; text-transform: uppercase; }

        .img-out-of-stock { opacity: 0.6; filter: grayscale(80%); }



        .promo-badge-img { position: absolute; top: 15px; right: 15px; background-color: #FF4D6D; color: white; padding: 6px 12px; border-radius: 8px; font-size: 13px; font-weight: 700; box-shadow: 0 4px 10px rgba(255, 77, 109, 0.3); z-index: 2; }



        .product-info { padding: 20px; flex-grow: 1; display: flex; flex-direction: column; }

        .product-category { font-size: 12px; color: var(--accent-pink, #FF6B6B); text-transform: uppercase; font-weight: 700; margin-bottom: 5px; }

        .product-title-link { text-decoration: none; color: var(--text-dark, #333); transition: color 0.2s ease; }

        .product-title-link:hover { color: var(--primary-purple, #7B61FF); }

        .product-title { font-size: 18px; font-weight: 600; margin: 0 0 10px 0; }

        .product-specs { font-size: 13px; color: var(--text-muted, #777); margin-bottom: 15px; }

        .product-specs span { display: inline-block; background: #F0F0F0; padding: 3px 8px; border-radius: 4px; margin-right: 5px; margin-bottom: 5px; }



        /* Pricing Area */

        .product-price { margin-top: auto; margin-bottom: 15px; }

        .price-standard { font-size: 20px; font-weight: 700; color: var(--primary-purple, #7B61FF); }

        .price-old { font-size: 15px; text-decoration: line-through; color: #999; margin-right: 8px; }

        .price-promo { font-size: 20px; font-weight: 700; color: #FF4D6D; }



        /* Action Buttons */

        .action-buttons { display: flex; gap: 10px; align-items: stretch; }

        .btn-details { flex: 1; display: flex; align-items: center; justify-content: center; background-color: transparent; color: var(--primary-purple, #7B61FF); border: 2px solid var(--primary-purple, #7B61FF); border-radius: 8px; font-size: 14px; font-weight: 600; text-decoration: none; transition: all 0.3s ease; padding: 10px; text-align: center; }

        .btn-details:hover { background-color: var(--primary-purple, #7B61FF); color: white; }

       

        .form-add-cart { flex: 1; margin: 0; display: flex; }

        .btn-add-cart { width: 100%; text-align: center; background-color: var(--primary-purple, #7B61FF); color: white; padding: 10px; border: 2px solid var(--primary-purple, #7B61FF); border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: background-color 0.3s; text-decoration: none; box-sizing: border-box; }

        .btn-add-cart:hover { background-color: var(--dark-purple, #5A4FCF); border-color: var(--dark-purple, #5A4FCF); }

        .btn-out-of-stock { background-color: #D3D3D3 !important; color: #888 !important; border-color: #D3D3D3 !important; cursor: not-allowed; }



        .no-products { text-align: center; grid-column: 1 / -1; padding: 50px; background: white; border-radius: 12px; color: var(--text-muted, #777); border: 1px dashed #ccc; }



        /* Pagination */

        .pagination-container { display: flex; justify-content: center; align-items: center; gap: 20px; margin-top: 50px; padding: 20px 0; }

        .btn-page { background-color: var(--primary-purple, #7B61FF); color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; transition: background-color 0.3s ease; }

        .btn-page:hover { background-color: var(--dark-purple, #5A4FCF); }

        .btn-page.disabled { background-color: #D3D3D3; color: #888; pointer-events: none; }

        .page-info { font-weight: 600; color: var(--text-dark, #333); font-size: 16px; }

    </style>

</head>

<body>



<div class="shop-container">

    <div class="shop-header">

        <?php if(!empty($search_query)): ?>

            <h1>Search Results for "<?php echo htmlspecialchars($search_query); ?>"</h1>

            <p>Showing items matching your search criteria.</p>

        <?php else: ?>

            <h1>Our Collection</h1>

            <p>Discover our premium selection of puffs with unique flavors.</p>

        <?php endif; ?>

    </div>



    <div class="shop-wrapper">

        <aside class="sidebar-filters">

            <h3 class="filter-title">Filter By</h3>

            <form action="shop.php" method="GET">

                <?php if(!empty($search_query)): ?>

                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">

                <?php endif; ?>

               

                <div class="filter-group">

                    <label>Price Range (TND)</label>

                    <div class="price-inputs">

                        <input type="number" name="min_price" placeholder="Min" step="0.001" min="0" value="<?php echo htmlspecialchars($_GET['min_price'] ?? ''); ?>">

                        <span>-</span>

                        <input type="number" name="max_price" placeholder="Max" step="0.001" min="0" value="<?php echo htmlspecialchars($_GET['max_price'] ?? ''); ?>">

                    </div>

                </div>



                <div class="filter-group">

                    <label>Number of Puffs</label>

                    <div class="checkbox-list">

                        <label class="checkbox-item">

                            <input type="checkbox" name="puffs[]" value="up_to_6k" <?php echo in_array('up_to_6k', $filter_puffs) ? 'checked' : ''; ?>> Up to 6,000

                        </label>

                        <label class="checkbox-item">

                            <input type="checkbox" name="puffs[]" value="7k_to_20k" <?php echo in_array('7k_to_20k', $filter_puffs) ? 'checked' : ''; ?>> 7,000 - 20,000

                        </label>

                        <label class="checkbox-item">

                            <input type="checkbox" name="puffs[]" value="25k_to_40k" <?php echo in_array('25k_to_40k', $filter_puffs) ? 'checked' : ''; ?>> 25,000 - 40,000

                        </label>

                        <label class="checkbox-item">

                            <input type="checkbox" name="puffs[]" value="more_than_40k" <?php echo in_array('more_than_40k', $filter_puffs) ? 'checked' : ''; ?>> More than 40,000

                        </label>

                    </div>

                </div>



                <div class="filter-group">

                    <label>Brand</label>

                    <div class="checkbox-list">

                        <?php foreach($available_brands as $brand): ?>

                            <label class="checkbox-item">

                                <input type="checkbox" name="brand[]" value="<?php echo htmlspecialchars($brand); ?>"

                                    <?php echo in_array(strtolower(trim($brand)), $filter_brand) ? 'checked' : ''; ?>>

                                <?php echo htmlspecialchars($brand); ?>

                            </label>

                        <?php endforeach; ?>

                    </div>

                </div>

                <button type="submit" class="btn-filter">Apply Filters</button>

                <a href="shop.php" class="btn-reset">Clear Filters</a>

            </form>

        </aside>



        <div class="products-area">

            <div class="product-grid">

                <?php if ($num_products > 0): ?>

                    <?php foreach ($paginated_products as $row): ?>

                        <?php

                            $stock_count = isset($row['stock_quantity']) ? (int)$row['stock_quantity'] : 1;

                            $is_out_of_stock = ($stock_count <= 0);

                        ?>

                       

                        <div class="product-card">

                            <div class="product-image-wrapper">

                                <a href="product_details.php?id=<?php echo $row['id']; ?>" class="product-image-link">

                                    <?php if (!empty($row['promo_type']) && $row['promo_type'] !== 'none' && !empty($row['promotional_price']) && $row['promotional_price'] > 0): ?>

                                        <span class="promo-badge-img">

                                            <?php

                                            if ($row['promo_type'] === 'percentage') {

                                                echo "-" . floatval($row['promo_value']) . "%";

                                            } elseif ($row['promo_type'] === 'amount') {

                                                echo "-" . floatval($row['promo_value']) . " TND";

                                            }

                                            ?>

                                        </span>

                                    <?php endif; ?>

                                   

                                    <?php if($is_out_of_stock): ?>

                                        <div class="out-of-stock-text">Out of Stock</div>

                                    <?php endif; ?>



                                    <img src="../photos/<?php echo htmlspecialchars($row['image_main']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="product-image <?php echo $is_out_of_stock ? 'img-out-of-stock' : ''; ?>" onerror="this.src='../photos/placeholder.jpg'">

                                </a>

                            </div>

                           

                            <div class="product-info">

                                <?php if(!empty($row['brand'])): ?>

                                    <div class="product-category"><?php echo htmlspecialchars($row['brand']); ?></div>

                                <?php endif; ?>

                               

                                <a href="product_details.php?id=<?php echo $row['id']; ?>" class="product-title-link">

                                    <h2 class="product-title"><?php echo htmlspecialchars($row['name']); ?></h2>

                                </a>

                               

                                <div class="product-specs">

                                    <span>💨 <?php echo htmlspecialchars($row['puff_count'] ?? 'N/A'); ?> Puffs</span>

                                    <span>🤤 <?php echo htmlspecialchars(substr($row['flavor'] ?? 'Standard', 0, 30)); ?></span>

                                    <?php if(!empty($row['nicotine_strength'])): ?>

                                        <span>💧 <?php echo htmlspecialchars($row['nicotine_strength']); ?></span>

                                    <?php endif; ?>

                                </div>

                               

                                <div class="product-price">

                                    <?php if (!empty($row['promo_type']) && $row['promo_type'] !== 'none' && !empty($row['promotional_price']) && $row['promotional_price'] > 0): ?>

                                        <span class="price-old"><?php echo number_format($row['price'], 3); ?> TND</span>

                                        <span class="price-promo"><?php echo number_format($row['promotional_price'], 3); ?> TND</span>

                                    <?php else: ?>

                                        <span class="price-standard"><?php echo number_format($row['price'], 3); ?> TND</span>

                                    <?php endif; ?>

                                </div>

                               

                                <div class="action-buttons">

                                    <a href="product_details.php?id=<?php echo $row['id']; ?>" class="btn-details">Details</a>

                                   

                                    <?php if($is_out_of_stock): ?>

                                        <div class="form-add-cart">

                                            <button type="button" class="btn-add-cart btn-out-of-stock" disabled>Out of Stock</button>

                                        </div>

                                    <?php else: ?>

                                        <form action="../footerlinks/cart.php" method="POST" class="form-add-cart">

                                            <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">

                                            <input type="hidden" name="quantity" value="1">

                                            <?php if (!empty($row['flavor'])): ?>

                                                <input type="hidden" name="flavor" value="<?php echo htmlspecialchars($row['flavor']); ?>">

                                            <?php endif; ?>

                                            <input type="hidden" name="action" value="add">

                                            <button type="submit" class="btn-add-cart">Add to Cart</button>

                                        </form>

                                    <?php endif; ?>

                                </div>

                            </div>

                        </div>



                    <?php endforeach; ?>

                <?php else: ?>

                    <div class="no-products">

                        <h2>No products match your filters!</h2>

                        <p>Try adjusting your criteria or clearing your filters to see more puffs.</p>

                    </div>

                <?php endif; ?>

            </div>



            <?php if ($total_pages > 1): ?>

                <div class="pagination-container">

                    <?php if ($page > 1): ?>

                        <a href="<?php echo $url_prefix; ?>page=<?php echo $page - 1; ?>" class="btn-page">« Back</a>

                    <?php else: ?>

                        <span class="btn-page disabled">« Back</span>

                    <?php endif; ?>



                    <span class="page-info">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>



                    <?php if ($page < $total_pages): ?>

                        <a href="<?php echo $url_prefix; ?>page=<?php echo $page + 1; ?>" class="btn-page">Next »</a>

                    <?php else: ?>

                        <span class="btn-page disabled">Next »</span>

                    <?php endif; ?>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>



<?php

if (file_exists('../includes/footer.php')) {

    include_once '../includes/footer.php';

}

?>

</body>

</html>