<?php
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['window_number'])) {
    $window_number = $_POST['window_number'];

    // Use prepared statements to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO windows (window_number, status) VALUES (?, 'Active')");
    $stmt->bind_param("s", $window_number);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Window added successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to add window!"]);
    }
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request!"]);
}
?>
