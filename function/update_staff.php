<?php
session_start();
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $staff_name = $_POST['staff_name'];
    $window_number = $_POST['window_number']; // This will be empty if not updated
    $courses = isset($_POST['courses']) ? implode(',', $_POST['courses']) : ''; // Convert array to comma-separated string

    // Fetch the current window number if not provided
    if (empty($window_number)) {
        $stmt = $conn->prepare("SELECT window_number FROM staff WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($current_window_number);
        $stmt->fetch();
        $stmt->close();

        $window_number = $current_window_number; // Use the current window number
    }

    // Check if a new password is provided
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $sql = "UPDATE staff SET staff_name = ?, window_number = ?, courses = ?, password = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sissi", $staff_name, $window_number, $courses, $password, $id);
    } else {
        $sql = "UPDATE staff SET staff_name = ?, window_number = ?, courses = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sisi", $staff_name, $window_number, $courses, $id);
    }

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Staff updated successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update staff.']);
    }

    $stmt->close();
    $conn->close();
}
?>