<?php
session_start();
include 'config.php';
// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader (if you have Composer installed)
// require 'vendor/autoload.php';

// If you don't have Composer, include the PHPMailer files directly
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Check if staff is logged in
if (!isset($_SESSION['staff_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access'
    ]);
    exit();
}

// Validate request
if (!isset($_POST['request_id']) || !isset($_POST['email']) || !isset($_POST['reason'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request'
    ]);
    exit();
}

$request_id = $_POST['request_id'];
$email = $_POST['email'];
$reason = $_POST['reason'];
$staff_id = $_SESSION['staff_id'];

try {
    // First check if this request belongs to the staff member
// In reject_document.php, update the query to include reference_number
$check_query = "SELECT id, student_id, documents, reference_number FROM form_responses WHERE id = ? AND staff_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ii", $request_id, $staff_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $request = $result->fetch_assoc();

    if (!$request) {
        echo json_encode([
            'status' => 'error',
            'message' => 'You are not authorized to reject this request'
        ]);
        exit();
    }

    // Update status to rejected
    $update_query = "UPDATE form_responses SET status = 'rejected' WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();

    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);

    // Server settings
    $mail->isSMTP();                                      // Send using SMTP
    $mail->Host       = 'smtp.gmail.com';                 // Gmail SMTP server
    $mail->SMTPAuth   = true;                             // Enable SMTP authentication
    $mail->Username   = 'johncarlocatchero424@gmail.com';           // SMTP username (your Gmail email)
    $mail->Password   = 'mvst anzu hcpi jjwa';              // SMTP password (use App Password, not your regular password)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;   // Enable TLS encryption
    $mail->Port       = 587;                              // TCP port to connect to (587 for TLS)

    // Recipients
    $mail->setFrom('johncarlocatchero424@gmail.com', 'Perpetual Help College of Pangasinan Registrar Office');
    $mail->addAddress($email);                            // Add recipient

    // Content
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = "Document Request Rejected";
    
// In the email template in reject_document.php
$mail->Body = "
<html>
<head>
    <title>Document Request Rejected</title>
</head>
<body>
    <p>Dear Student (ID: {$request['student_id']}),</p>
    
    <p>We regret to inform you that your document request (ID: {$request['id']}) has been rejected.</p>
    
    <p><strong>Details:</strong><br>
    Reference Number: {$request['reference_number']}<br>
    Document(s): {$request['documents']}</p>
    
    <p><strong>Reason for rejection:</strong><br>
    {$reason}</p>
    
    <p>If you have any questions or need further assistance, please visit our office during regular business hours or reply to this email.</p>
    
    <p>Thank you for your understanding.</p>
    
    <p>Best regards,<br>
    Perpetual Help College of Pangasinan Registrar Office</p>
</body>
</html>
";

// Also update the plain text version
$mail->AltBody = "Dear Student (ID: {$request['student_id']}),\n\n"
               . "We regret to inform you that your document request (ID: {$request['id']}) has been rejected.\n\n"
               . "Details:\n"
               . "Reference Number: {$request['reference_number']}\n"
               . "Document(s): {$request['documents']}\n\n"
               . "Reason for rejection:\n"
               . "{$reason}\n\n"
               . "If you have any questions or need further assistance, please visit our office during regular business hours or reply to this email.\n\n"
               . "Thank you for your understanding.\n\n"
               . "Best regards,\n"
               . "Perpetual Help College of Pangasinan Registrar Office";
    
    // Send the email
    $mail->send();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Rejection email sent successfully'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error processing rejection: ' . $e->getMessage()
    ]);
}
?>