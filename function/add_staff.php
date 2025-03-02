<?php
session_start();
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $staff_name = $_POST['staff_name'];
    $window_number = $_POST['window_number'];
    $courses = isset($_POST['courses']) ? implode(',', $_POST['courses']) : ''; // Convert array to comma-separated string
    $password = $_POST['password']; // Store the password as plain text

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO staff (staff_name, window_number, courses, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("siss", $staff_name, $window_number, $courses, $password);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Staff added successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to add staff.']);
    }

    $stmt->close();
    $conn->close();
}
?>