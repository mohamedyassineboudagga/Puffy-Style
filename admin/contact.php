<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/Database.php';
$database = new Database();
$db = $database->getConnection();

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_contact'])) {
    $name    = htmlspecialchars($_POST['name'] ?? '');
    $email   = htmlspecialchars($_POST['email'] ?? '');
    $subject = htmlspecialchars($_POST['subject'] ?? '');
    $message = htmlspecialchars($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($message)) {
        $error_msg = "Please fill in all required fields.";
    } else {
        try {
            // Generate a unique ID for the message (matches your varchar(20) structure)
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
?>
