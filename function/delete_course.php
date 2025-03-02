<?php
session_start();
include '../config.php'; // Include your database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];

    // Prepare and bind
    $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Course deleted successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete course.']);
    }

    $stmt->close();
    $conn->close();
}
?>