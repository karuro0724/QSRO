
<?php
// Prevent any output before JSON response
ob_start();

// Start session
session_start();

// Set error reporting to suppress warnings/notices from being output
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Include database configuration
include '../config.php';

// Ensure no HTML is output if there's an error in the config file
if(ob_get_length()) ob_clean();

// Set JSON header
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Log the start of request processing
        error_log("Processing queue generation request");
        
        // Validate connection
        if (!$conn) {
            throw new Exception('Database connection failed: ' . mysqli_connect_error());
        }

        // Log received POST data
        error_log("Received POST data: " . print_r($_POST, true));

        // Retrieve and sanitize input data
        $name = isset($_POST['name']) ? trim(mysqli_real_escape_string($conn, $_POST['name'])) : '';
        $contact = isset($_POST['contact']) ? trim(mysqli_real_escape_string($conn, $_POST['contact'])) : '';
        $window_number = isset($_POST['window_number']) ? trim(mysqli_real_escape_string($conn, $_POST['window_number'])) : '';
        $service_type = isset($_POST['service']) ? trim(mysqli_real_escape_string($conn, $_POST['service'])) : '';

        // Validate input data
        if (empty($name) || empty($contact) || empty($window_number) || empty($service_type)) {
            throw new Exception('All fields are required. Please check your input.');
        }

        // Generate a unique 3-digit queue number
        $queue_number = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);

        // Check if queue number already exists
        $check_query = "SELECT queue_number FROM queue WHERE queue_number = ? AND DATE(created_at) = CURDATE()";
        $check_stmt = $conn->prepare($check_query);
        
        if (!$check_stmt) {
            throw new Exception('Failed to prepare check statement: ' . $conn->error);
        }

        $check_stmt->bind_param("s", $queue_number);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        // If queue number exists, generate a new one
        while ($result->num_rows > 0) {
            $queue_number = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
        }
        $check_stmt->close();

        // Insert into the queue table
        $insert_query = "INSERT INTO queue (
            name, 
            contact, 
            window_number, 
            service_type, 
            queue_number,
            status,
            display_status,
            created_at
        ) VALUES (?, ?, ?, ?, ?, 'waiting', 'inactive', NOW())";
        
        $stmt = $conn->prepare($insert_query);
        
        if (!$stmt) {
            throw new Exception('Failed to prepare insert statement: ' . $conn->error);
        }

        $stmt->bind_param("sssss", 
            $name, 
            $contact, 
            $window_number, 
            $service_type, 
            $queue_number
        );

        if ($stmt->execute()) {
            error_log("Queue generated successfully: " . $queue_number);
            
            // Clear any output buffers before sending response
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            echo json_encode([
                'success' => true,
                'queue_number' => $queue_number,
                'message' => 'Queue generated successfully'
            ]);
        } else {
            throw new Exception('Failed to insert queue: ' . $stmt->error);
        }
        $stmt->close();

    } catch (Exception $e) {
        error_log("Queue generation error: " . $e->getMessage());
        
        // Clear any output buffers before sending error response
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
}

// Ensure the connection is closed
if (isset($conn)) {
    $conn->close();
}
?>