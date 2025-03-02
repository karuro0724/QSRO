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
if (!isset($_POST['request_id']) || !isset($_POST['email'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request'
    ]);
    exit();
}

$request_id = $_POST['request_id'];
$email = $_POST['email'];
$staff_id = $_SESSION['staff_id'];

try {
    // First check if this request belongs to the staff member
    // In notify_document.php, update the query to include reference_number
$check_query = "SELECT id, student_id, documents, reference_number FROM form_responses WHERE id = ? AND staff_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ii", $request_id, $staff_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $request = $result->fetch_assoc();

    if (!$request) {
        echo json_encode([
            'status' => 'error',
            'message' => 'You are not authorized to notify for this request'
        ]);
        exit();
    }

    // Update status to claimable
    $update_query = "UPDATE form_responses SET status = 'claimable' WHERE id = ?";
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
    $mail->Subject = "Document Request Ready for Claim";
    
// In the email template in notify_document.php
$mail->Body = "
<html>
<head>
    <title>Document Ready for Claim</title>
</head>
<body>
    <p>Dear Student (ID: {$request['student_id']}),</p>
    
    <p>We are pleased to inform you that your requested document is now ready for claim.</p>
    
    <p><strong>Details:</strong><br>
    Request ID: {$request['id']}<br>
    Reference Number: {$request['reference_number']}<br>
    Document(s): {$request['documents']}</p>
    
    <p>Please visit our office during regular business hours with your student ID to collect your document.</p>
    
    <p>Thank you for your patience.</p>
    
    <p>Best regards,<br>
    Perpetual Help College of Pangasinan Registrar Office</p>
</body>
</html>
";

// Also update the plain text version
$mail->AltBody = "Dear Student (ID: {$request['student_id']}),\n\n"
               . "We are pleased to inform you that your requested document is now ready for claim.\n\n"
               . "Details:\n"
               . "Request ID: {$request['id']}\n"
               . "Reference Number: {$request['reference_number']}\n"
               . "Document(s): {$request['documents']}\n\n"
               . "Please visit our office during regular business hours with your student ID to collect your document.\n\n"
               . "Thank you for your patience.\n\n"
               . "Best regards,\n"
               . "Perpetual Help College of Pangasinan Registrar Office";
    
    // Send the email
    $mail->send();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Notification email sent successfully'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error processing notification: ' . $e->getMessage()
    ]);
}
?>