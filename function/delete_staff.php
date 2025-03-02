<?php
session_start();
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];

    // Prepare and bind
    $stmt = $conn->prepare("DELETE FROM staff WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Staff deleted successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete staff.']);
    }

    $stmt->close();
    $conn->close();
}
?>