<?php
session_start();
include '../config.php'; // Include your database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $document_name = $_POST['document_name'];

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO documents (document_name) VALUES (?)");
    $stmt->bind_param("s", $document_name);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Document added successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to add document.']);
    }

    $stmt->close();
    $conn->close();
}
?>