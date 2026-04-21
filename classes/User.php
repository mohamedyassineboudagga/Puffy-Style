<?php
class User {
    private $conn;
    private $table_name = "users";

    public function __construct($db) {
        $this->conn = $db;
    }
    
    private function generateUniqueId() {
        $isUnique = false;
        $newId = '';
        
        while (!$isUnique) {
            $randomNum = mt_rand(1000, 9999);
            $newId = 'USR' . $randomNum;

            $stmt = $this->conn->prepare("SELECT id FROM " . $this->table_name . " WHERE id = ? LIMIT 1");
            $stmt->execute([$newId]);
            
            if ($stmt->rowCount() == 0) {
                $isUnique = true; 
            }
        }
        return $newId;
    }

    public function register($first_name, $last_name, $email, $password, $dob, $phone) {
        $bday = new DateTime($dob);
        $today = new DateTime('today');
        $age = $bday->diff($today)->y;

        if ($age < 18) {
            return "You must be at least 18 years old to register.";
        }

        $email_check = "SELECT id FROM " . $this->table_name . " WHERE email = ? LIMIT 1";
        $stmt = $this->conn->prepare($email_check);
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            return "Email is already used.";
        }

        $phone_check = "SELECT id FROM " . $this->table_name . " WHERE phone = ? LIMIT 1";
        $stmt = $this->conn->prepare($phone_check);
        $stmt->execute([$phone]);
        if ($stmt->rowCount() > 0) {
            return "Phone number is already used.";
        }

        $user_id = $this->generateUniqueId();
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        $query = "INSERT INTO " . $this->table_name . " 
                  (id, first_name, last_name, email, password, date_of_birth, phone) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        
        if($stmt->execute([$user_id, $first_name, $last_name, $email, $hashed_password, $dob, $phone])) {
            return true; 
        }
        
        return "Registration failed. Please try again.";
    }

    // ---> THIS IS THE FUNCTION YOUR LOGIN.PHP IS LOOKING FOR <---
    public function login($email, $password) {
        $query = "SELECT id, first_name, last_name, password, role, is_active 
                  FROM " . $this->table_name . " 
                  WHERE email = ? LIMIT 1";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (isset($row['is_active']) && $row['is_active'] == 0) {
                return false; 
            }
            
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_name'] = $row['first_name'] . ' ' . $row['last_name'];
                $_SESSION['user_role'] = $row['role'];
                
                return true; 
            }
        }
        
        return false; 
    }
}
?>