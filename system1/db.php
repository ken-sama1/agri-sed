<?php
session_start();
$conn = new mysqli("localhost", "user", "W@llyb0ld", "agrised");
if ($conn->connect_error) { 
    die("Connection failed: " . $conn->connect_error); 
}
?>
