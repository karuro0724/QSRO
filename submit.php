<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection details (InfinityFree)
$servername = "	sql308.infinityfree.com"; // Replace with your database host
$username = "if0_38378758"; // Replace with your database username
$password = "7zQgRrlzVO22RxZ "; // Replace with your database password
$dbname = "if0_38378758_queue_system"; // Replace with your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get data from POST request
$student_id = $_POST['student_id'];
$email = $_POST['email'];
$course = $_POST['course'];
$documents = $_POST['documents']; // This is already a string

// Insert data into the database
$sql = "INSERT INTO requests (student_id, email, course, documents)
        VALUES ('$student_id', '$email', '$course', '$documents')";

if ($conn->query($sql) === TRUE) {
    echo "New record created successfully";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

// Close connection
$conn->close();
?>