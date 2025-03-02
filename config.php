<?php
$host = 'sql12.freesqldatabase.com';
$user = 'sql12765187'; // Change if needed
$pass = '3XBvmPQjcd'; // Change if needed
$dbname = 'sql12765187';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>