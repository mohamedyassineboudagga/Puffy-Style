<?php
class Product {
    private $conn;
    private $table_name = "products";

    // Object properties
    public $id;
    public $name;
    public $price;
    public $flavor;
    public $puff_count;
    public $image_main;
    // Nouveaux attributs pour gérer les promotions et le stock
    public $promo_type;
    public $promo_value;
    public $promotional_price;
    public $stock_quantity;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Test if a specific Product ID already exists in the database
     */
    public function idExists($product_id) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$product_id]);
        
        // If rowCount is greater than 0, the ID already exists
        return $stmt->rowCount() > 0;
    }

    /**
     * Fetch all active products for the shop page
     */
    public function getAllActiveProducts() {
        // MISE À JOUR : Ajout des colonnes promo_type, promo_value, promotional_price et stock_quantity
        $query = "SELECT p.id, p.name, p.price, p.flavor, p.puff_count, p.image_main, 
                         p.promo_type, p.promo_value, p.promotional_price, p.stock_quantity,
                         c.name as category_name 
                  FROM " . $this->table_name . " p
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE p.is_active = 1
                  ORDER BY p.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * Create a new product
     * NOTE: You now MUST pass $custom_number as the first parameter!
     */
    public function create($custom_number, $category_id, $name, $slug, $description, $price, $stock_quantity, $flavor, $puff_count, $image_main) {
        
        // 1. Combine the fixed 'PRD' prefix with your custom number
        $this->id = 'PRD' . $custom_number;

        // 2. Test if this ID already exists
        if ($this->idExists($this->id)) {
            // Stop everything and return false if the ID is taken
            return false; 
        }

        // 3. Insert into the database
        $query = "INSERT INTO " . $this->table_name . " 
                  (id, category_id, name, slug, description, price, stock_quantity, flavor, puff_count, image_main) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        
        if($stmt->execute([$this->id, $category_id, $name, $slug, $description, $price, $stock_quantity, $flavor, $puff_count, $image_main])) {
            return true; // Product added successfully
        }
        
        return false; // Failed to add product
    }
}
?>