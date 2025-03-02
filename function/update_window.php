<?php
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'], $_POST['window_number'], $_POST['status'])) {
    $id = $_POST['id'];
    $window_number = $_POST['window_number'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE windows SET window_number = ?, status = ? WHERE id = ?");
    $stmt->bind_param("ssi", $window_number, $status, $id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Window updated successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update window!"]);
    }
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request!"]);
}
?>
