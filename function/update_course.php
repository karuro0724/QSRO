<?php
session_start();
include '../config.php'; // Include your database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $course_name = $_POST['course_name'];
    $description = $_POST['description'];

    // Prepare and bind
    $stmt = $conn->prepare("UPDATE courses SET course_name = ?, description = ? WHERE id = ?");
    $stmt->bind_param("ssi", $course_name, $description, $id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Course updated successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update course.']);
    }

    $stmt->close();
    $conn->close();
}
?>