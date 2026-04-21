<?php
// pages/register.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If user is already logged in, redirect them to the home page
if(isset($_SESSION['user_id'])) {
    header("Location: ../pages/index.php");
    exit();
}

// 1. Include our OOP Classes
require_once '../config/Database.php';
require_once '../classes/User.php';
// Header removed for clean auth layout

$message = '';
$message_type = ''; // Will be 'success' or 'error'

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 2. Instantiate the Database and get the connection
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        // 3. Instantiate the User object
        $user = new User($db);
        
        // 4. Sanitize and retrieve form data securely
        $first_name = htmlspecialchars(strip_tags($_POST['first_name']));
        $last_name = htmlspecialchars(strip_tags($_POST['last_name']));
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        $date_of_birth = $_POST['date_of_birth'];
        $phone = htmlspecialchars(strip_tags($_POST['phone']));
        
        // 5. Attempt to register using our User method 
        // (Age verification, duplicate email/phone checks, and custom USRxxxx ID generation are handled inside this method)
        $result = $user->register($first_name, $last_name, $email, $password, $date_of_birth, $phone);
        
        if ($result === true) {
            $message = "Registration successful! You can now <a href='login.php' style='color:#10B981; text-decoration:underline;'>log in</a>.";
            $message_type = "success";
        } else {
            $message = $result; // Displays the specific error (e.g., "Email is already used.")
            $message_type = "error";
        }
    } else {
        $message = "System Error: Cannot connect to the database.";
        $message_type = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Puffy Style</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* New UI Styling matching forgot-password.php background */
        .auth-wrapper { 
            background: url('../photos/index1.JPG') center/cover no-repeat fixed;
            min-height: 100vh;
            display: flex; 
            align-items: center;
            justify-content: center; 
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
            max-width: 500px;
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
        
        /* Styled Alerts for Success and Error messages */
        .alert-error {
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
        .alert-success {
            background-color: #D1FAE5;
            color: #10B981;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 20px;
            border: 1px solid #6EE7B7;
        }

        .form-row {
            display: flex;
            gap: 15px;
        }
        .form-row .input-group {
            flex: 1;
        }
        .input-group { 
            margin-bottom: 18px; 
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
            margin-top: 15px;
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
        <h2>Create an account</h2>
        <p class="sub">Join the Puffy Store community</p>

        <?php if ($message): ?>
            <div class="<?php echo $message_type === 'error' ? 'alert-error' : 'alert-success'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            
            <div class="form-row">
                <div class="input-group">
                    <label>First Name *</label>
                    <input type="text" name="first_name" placeholder="Your first name" required>
                </div>
                <div class="input-group">
                    <label>Last Name *</label>
                    <input type="text" name="last_name" placeholder="Your last name" required>
                </div>
            </div>

            <div class="input-group">
                <label>Email Address *</label>
                <input type="email" name="email" placeholder="example@email.com" required>
            </div>

            <div class="input-group">
                <label>Password *</label>
                <input type="password" name="password" placeholder="Create a password" required minlength="6">
            </div>

            <div class="form-row">
                <div class="input-group">
                    <label>Date of Birth (18+) *</label>
                    <input type="date" name="date_of_birth" required>
                </div>
                <div class="input-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" placeholder="+216 xx xxx xxx">
                </div>
            </div>

            <button type="submit" class="btn-submit">Register</button>
        </form>
        
        <div class="auth-footer">
            Already have an account? <a href="login.php">Log in</a>
        </div>
        <div class="auth-footer">
            Puffy Style Store© 2026 - All rights reserved
        </div>
    </div>
</div>

</body>
</html>