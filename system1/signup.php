<?php
include 'db.php';

$username = $_POST['username'] ?? '';
$plainPassword = $_POST['password'] ?? '';
$role = $_POST['role'] ?? '';
$worker_id = $_POST['worker_id'] ?? '';

if (!is_string($username) || !is_string($plainPassword) || !is_string($role) || !is_string($worker_id)) {
    http_response_code(400);
    exit("Invalid signup data.");
}
$username = trim($username);
if (mb_strlen($username) < 3 ||
    mb_strlen($username) > 30 ||
    preg_match('/\A[\p{L} ]+\z/u', $username) !== 1) {
    http_response_code(400);
    exit("Username must be 3–30 characters and contain only letters and spaces.");
}
if (!preg_match('/^(?=(?:[^A-Za-z]*[A-Za-z]){6})(?=.*[0-9]).{8,}$/', $plainPassword)) {
    http_response_code(400);
    exit("Password must be at least 8 characters, contain 6 letters, and include a number.");
}
if (!in_array($role, ['user', 'admin'], true)) {
    http_response_code(400);
    exit("Invalid account role.");
}
if (($role === 'admin' && !preg_match('/^\d{2}-\d{4}-\d{6}$/', $worker_id)) ||
    ($role === 'user' && $worker_id !== '')) {
    http_response_code(400);
    exit("Invalid worker ID.");
}

$password = password_hash($plainPassword, PASSWORD_DEFAULT);
$sql = "INSERT INTO users (username, password, role, worker_id) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    exit("Unable to create account.");
}
$stmt->bind_param("ssss", $username, $password, $role, $worker_id);
echo ($stmt->execute()) ? "Success" : "Error: " . $stmt->error;
$stmt->close();
$conn->close();
?>
