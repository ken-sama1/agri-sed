<?php
include 'db.php';
$username = $_POST['username'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
$role = $_POST['role'];
$worker_id = $_POST['worker_id'];
$sql = "INSERT INTO users (username, password, role, worker_id) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $username, $password, $role, $worker_id);
echo ($stmt->execute()) ? "Success" : "Error: " . $stmt->error;
?>
