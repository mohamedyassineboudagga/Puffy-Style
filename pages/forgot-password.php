<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure you include your database connection file here. 
// Adjust the path if your Database class is located somewhere else.
require_once '../config/Database.php'; 

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    // Initialize Database Connection
    $database = new Database();
    $db = $database->getConnection();

    $email = trim($_POST['email']);

    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // 1. Find the user_id associated with this email
        $checkUserQuery = "SELECT id FROM users WHERE email = :email LIMIT 1";
        $stmt = $db->prepare($checkUserQuery);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $user_id = $user['id']; // This will safely pull your VARCHAR(20) ID
            
            // 2. Generate a secure token for the reset link
            $token = bin2hex(random_bytes(32));

            // 3. Insert into passforget table
            $insertQuery = "INSERT INTO passforget (user_id, email, token, created_at) VALUES (:user_id, :email, :token, NOW())";
            $insertStmt = $db->prepare($insertQuery);
            $insertStmt->bindParam(':user_id', $user_id);
            $insertStmt->bindParam(':email', $email);
            $insertStmt->bindParam(':token', $token);

            if ($insertStmt->execute()) {
                // Here you would add the code to actually send the email with the $token link
                // e.g., "http://localhost/Puffy%20Style/pages/reset-password.php?token=" . $token
                
                $message = "If that email exists in our system, a reset link has been generated.";
                $messageType = "success";
            } else {
                $message = "Something went wrong processing your request. Please try again.";
                $messageType = "error";
            }
        } else {
            // Security best practice: Always show success even if email isn't found 
            // to prevent attackers from checking which emails are registered.
            $message = "If that email exists in our system, a reset link has been generated.";
            $messageType = "success";
        }
    } else {
        $message = "Please enter a valid email address.";
        $messageType = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Puffy Style</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .auth-wrapper {
            background: url('../photos/index1.JPG') center/cover no-repeat fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }
        .auth-wrapper::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(4px); 
            z-index: 1;
        }

        .btn-back-home {
            position: absolute;
            top: 20px;
            left: 20px;
            background: white;
            color: #6B4C9A;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            z-index: 10; 
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-back-home:hover {
            background: #6B4C9A;
            color: white;
            transform: translateY(-2px);
        }

        .auth-card {
            background: white;
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 450px;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
            text-align: center;
        }
        .auth-title {
            color: #6B4C9A; 
            font-size: 24px;
            margin-bottom: 15px;
            font-weight: 700;
        }
        .auth-desc {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 30px;
        }
        .form-group {
            text-align: left;
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            color: #333;
            margin-bottom: 8px;
        }
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #EAEAEA;
            border-radius: 8px;
            font-size: 14px;
            box-sizing: border-box;
            outline: none;
            transition: 0.3s;
        }
        .form-control:focus {
            border-color: #6B4C9A;
        }
        .btn-submit {
            background: #6B4C9A;
            color: white;
            border: none;
            width: 100%;
            padding: 14px;
            border-radius: 25px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            margin-bottom: 20px;
            transition: background 0.3s;
        }
        .btn-submit:hover {
            background: #553A7D;
        }
        .back-to-login {
            font-size: 13px;
            color: #666;
            margin-bottom: 30px;
        }
        .back-to-login a {
            color: #F0719B; 
            text-decoration: none;
            font-weight: 600;
        }
        .back-to-login a:hover {
            text-decoration: underline;
        }
        .auth-footer {
            border-top: 1px solid #EAEAEA;
            padding-top: 20px;
            font-size: 12px;
            color: #999;
        }

        /* Alert Message Styles */
        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            text-align: left;
            font-weight: 500;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>

<div class="auth-wrapper">
    <a href="../pages/index.php" class="btn-back-home">← Back to Home</a>
    
    <div class="auth-card">
        <h2 class="auth-title">Forgot your password?</h2>
        <p class="auth-desc">Don't worry! Enter your <strong>Email Address</strong> below and we will send you a link to reset your password.</p>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="example@gmail.com" required>
            </div>
            
            <button type="submit" class="btn-submit">Send Link</button>
        </form>

        <div class="back-to-login">
            Remember your password? <a href="login.php">Back to login</a>
        </div>

        <div class="auth-footer">
            Puffy Style Store© 2026 - All rights reserved
        </div>
    </div>
</div>

</body>
</html>