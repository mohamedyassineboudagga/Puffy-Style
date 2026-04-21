<?php
// pages/login.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If user is already logged in, redirect them to the home page
if(isset($_SESSION['user_id'])) {
    header("Location: ../pages/index.php");
    exit();
}

require_once '../config/Database.php';
require_once '../classes/User.php';
// Header removed for clean auth layout

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        $user = new User($db);
        
        // Sanitize the email input
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        
        // Call our OOP login method
        // Our User::login() method now sets $_SESSION directly and returns true or false
        $result = $user->login($email, $password);
        
        // If the result is true, the login was successful!
        if ($result === true) {
            
            // Redirect based on role (which was saved to $_SESSION inside the login method)
            if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
                header("Location: ../admin/indexA.php"); // Redirects to our new Admin Dashboard
            } else {
                header("Location: ../pages/index.php");
            }
            exit();
        } else {
            // If it returns false, the credentials were wrong
            $error_message = "Invalid email or password. Please try again.";
        }
    } else {
        $error_message = "System Error: Cannot connect to the database.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Puffy Style</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* New UI Styling matching your mockups with background */
        .auth-wrapper { 
            background: url('../photos/index1.JPG') center/cover no-repeat fixed;
            min-height: 100vh;
            display: flex; 
            justify-content: center; 
            align-items: center;
            padding: 40px 20px; 
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
        
        /* Back to Home Button Styling */
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
            z-index: 10; /* Keeps it above the blurred background */
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
            z-index: 2; /* Keeps form above the blurred background */
            width: 100%;
            max-width: 450px;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            border: 1px solid #EAEAEA;
        }
        .auth-card h2 { 
            color: #6B4C9A; 
            text-align: center; 
            margin: 0 0 5px 0; 
            font-size: 24px; 
        }
        .auth-card p.sub { 
            text-align: center; 
            color: #666; 
            font-size: 14px; 
            margin-bottom: 30px; 
        }
        .error-alert {
            background-color: #FEE2E2;
            color: #EF4444;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 20px;
            border: 1px solid #FCA5A5;
        }
        .input-group { 
            margin-bottom: 20px; 
        }
        .input-group label { 
            display: block; 
            font-size: 13px; 
            font-weight: 600; 
            margin-bottom: 8px; 
            color: #333; 
        }
        .input-group input {
            width: 100%; 
            padding: 12px; 
            border: 1px solid #EAEAEA;
            border-radius: 6px; 
            box-sizing: border-box; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            transition: border-color 0.3s;
        }
        .input-group input:focus { 
            outline: none; 
            border-color: #9C89B8; 
        }
        .btn-submit {
            width: 100%; 
            background: #6B4C9A; 
            color: white; 
            border: none;
            padding: 14px; 
            border-radius: 25px; 
            font-size: 15px; 
            font-weight: 600; 
            cursor: pointer; 
            margin-top: 10px;
            transition: background-color 0.3s;
        }
        .btn-submit:hover { 
            background: #553A7D; 
        }
        .auth-footer { 
            text-align: center; 
            margin-top: 25px; 
            font-size: 13px; 
            color: #666; 
        }
        .auth-footer a { 
            color: #F0719B; 
            text-decoration: none; 
            font-weight: 600; 
        }
        .auth-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="auth-wrapper">
    <a href="../pages/index.php" class="btn-back-home">← Back to Home</a>
    
    <div class="auth-card">
        <h2>Welcome back!</h2>
        <p class="sub">Log in to access your account</p>

        <?php if ($error_message): ?>
            <div class="error-alert">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="input-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="example@email.com" required>
            </div>
            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Your password" required>
            </div>
            <button type="submit" class="btn-submit">Log In</button>
        </form>

        <div class="auth-footer">
            No account yet? <a href="register.php">Register here</a>
            <br><br>
            <a href="forgot-password.php" style="color: #F0719B; text-decoration: none; font-weight: 600; font-size: 13px;">Forgot password?</a>
        </div>
        <div class="auth-footer">
            Puffy Style Store© 2026 - All rights reserved
        </div>
    </div>
</div>

</body>
</html>