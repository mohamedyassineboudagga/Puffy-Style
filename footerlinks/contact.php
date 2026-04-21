<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/Database.php';

// Initialize Database connection
$database = new Database();
$db = $database->getConnection();

$success_msg = '';
$error_msg = '';

// Handle Contact Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_contact'])) {
    $name    = htmlspecialchars($_POST['name'] ?? '');
    $email   = htmlspecialchars($_POST['email'] ?? '');
    $subject = htmlspecialchars($_POST['subject'] ?? '');
    $message = htmlspecialchars($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($message)) {
        $error_msg = "Please fill in all required fields.";
    } else {
        try {
            // Generate a unique ID for the message (matches varchar(20))
            $msg_id = 'MSG' . str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);

            $query = "INSERT INTO contact_messages (id, name, email, subject, message) 
                      VALUES (:id, :name, :email, :subject, :message)";
            
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':id'      => $msg_id,
                ':name'    => $name,
                ':email'   => $email,
                ':subject' => $subject,
                ':message' => $message
            ]);

            $success_msg = "Thank you! Your message has been sent successfully.";
        } catch (PDOException $e) {
            $error_msg = "Error sending message: " . $e->getMessage();
        }
    }
}

// Load Header after backend logic
require_once '../includes/header.php';
?>

<style>
    .page-hero { background-color: #7B61FF; color: white; text-align: center; padding: 60px 20px; }
    .page-hero h1 { font-size: 36px; margin-bottom: 10px; font-weight: 700; }
    .page-hero p { font-size: 16px; opacity: 0.9; }
    
    .contact-grid {
        max-width: 1000px; margin: -30px auto 60px; background: white;
        border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        position: relative; display: grid; grid-template-columns: 3fr 2fr;
        overflow: hidden;
    }

    .contact-form-side { padding: 40px; }
    .contact-form-side h2 { font-size: 24px; color: #333; margin-top: 0; margin-bottom: 10px; }
    .contact-form-side p { color: #666; font-size: 14px; margin-bottom: 30px; }

    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-size: 13px; font-weight: 600; color: #444; margin-bottom: 8px; }
    .form-control {
        width: 100%; padding: 12px 15px; border: 1px solid #EAEAEA;
        border-radius: 8px; font-size: 14px; font-family: inherit;
        box-sizing: border-box; outline: none; transition: 0.3s;
    }
    .form-control:focus { border-color: #7B61FF; box-shadow: 0 0 0 3px rgba(123, 97, 255, 0.1); }
    textarea.form-control { resize: vertical; min-height: 120px; }

    .btn-submit {
        background-color: #7B61FF; color: white; padding: 12px 30px;
        border-radius: 25px; border: none; font-weight: 600; cursor: pointer;
        transition: 0.3s; font-size: 15px; width: auto;
    }
    .btn-submit:hover { background-color: #634bc4; }

    .contact-info-side { background: #F9F9FB; padding: 40px; border-left: 1px solid #EAEAEA; }
    .contact-info-side h3 { font-size: 20px; color: #7B61FF; margin-top: 0; margin-bottom: 20px; }
    .contact-info-side p { color: #555; font-size: 14px; line-height: 1.6; margin-bottom: 25px; }
    
    .info-item { margin-bottom: 20px; }
    .info-item strong { display: block; font-size: 14px; color: #333; margin-bottom: 5px; }
    .info-item span { color: #666; font-size: 14px; }
    .info-item a { color: #666; text-decoration: none; transition: 0.3s;}
    .info-item a:hover { color: #7B61FF; }

    .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
    .alert-success { background-color: #D4EDDA; color: #155724; border: 1px solid #c3e6cb; }
    .alert-danger { background-color: #F8D7DA; color: #721C24; border: 1px solid #f5c6cb; }

    @media (max-width: 768px) {
        .contact-grid { grid-template-columns: 1fr; }
        .contact-info-side { border-left: none; border-top: 1px solid #EAEAEA; }
    }
</style>

<div class="page-hero">
    <h1>Contact Us</h1>
    <p>Have a question? Our team is here to help you.</p>
</div>

<div class="contact-grid">
    <div class="contact-form-side">
        <h2>Send a message</h2>
        <p>Fill out the form below, we will reply within 24 hours.</p>

        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" class="form-control" placeholder="Your name" required>
            </div>
            
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="example@email.com" required>
            </div>

            <div class="form-group">
                <label>Subject</label>
                <select name="subject" class="form-control">
                    <option value="Order Tracking">Order Tracking</option>
                    <option value="Product Information">Product Information</option>
                    <option value="Complaint">Complaint</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="form-group">
                <label>Message</label>
                <textarea name="message" class="form-control" placeholder="How can we help you?" required></textarea>
            </div>

            <button type="submit" name="submit_contact" class="btn-submit">Send Message</button>
        </form>
    </div>

    <div class="contact-info-side">
        <h3>Contact Information</h3>
        <p>Prefer to speak with us directly?</p>

        <div class="info-item">
            <strong>📧 Email</strong>
            <a href="mailto:support@puffystyle.com">support@puffystyle.com</a>
        </div>

        <div class="info-item">
            <strong>📞 Phone</strong>
            <span>+216 97 318 008</span>
        </div>

        <div class="info-item">
            <strong>📍 Address</strong>
            <span>Tunis, Tunisia</span>
        </div>

        <div class="info-item">
            <strong>📱 Follow Us</strong>
            <p style="margin-bottom:0; margin-top:5px;">For exclusive promos:</p>
            <a href="#">Instagram</a> &nbsp;|&nbsp; <a href="#">Facebook</a>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>