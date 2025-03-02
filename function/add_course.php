<?php
session_start();
include '../config.php'; // Include your database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_name = $_POST['course_name'];
    $description = $_POST['description'];

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO courses (course_name, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $course_name, $description);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Course added successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to add course.']);
    }

    $stmt->close();
    $conn->close();
}
?>