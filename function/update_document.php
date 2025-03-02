<?php
session_start();
include '../config.php'; // Include your database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $document_name = $_POST['document_name'];

    // Prepare and bind
    $stmt = $conn->prepare("UPDATE documents SET document_name = ? WHERE id = ?");
    $stmt->bind_param("si", $document_name, $id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Document updated successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update document.']);
    }

    $stmt->close();
    $conn->close();
}
?>