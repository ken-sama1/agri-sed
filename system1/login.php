<?php
include 'db.php';

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? '';
$workerId = $_POST['workerId'] ?? '';

if (!is_string($username) ||
    !is_string($password) ||
    !is_string($role) ||
    !is_string($workerId)) {
    http_response_code(400);
    exit("Invalid login data.");
}
$username = trim($username);
$workerId = trim($workerId);
if (mb_strlen($username) < 3 ||
    mb_strlen($username) > 30 ||
    preg_match('/\A[\p{L} ]+\z/u', $username) !== 1 ||
    !in_array($role, ['user', 'admin'], true)) {
    http_response_code(400);
    exit("Invalid username or account role.");
}

$sql = "SELECT id, password, role, worker_id FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();

$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {

    if (password_verify($password, $user['password'])) {
        // Check role
        if ($user['role'] !== $role) {
            echo "Invalid Role: This account is a " . $user['role'];
        }
        // Check worker ID only if one is assigned in the database
        elseif (
            !empty($user['worker_id']) &&
            $user['worker_id'] !== $workerId
        ) {
            echo "Invalid Worker ID";
        }
        else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];

            echo "Success";
        }
    } else {
        echo "Wrong Password";
    }

} else {
    echo "User Not Found";
}

$stmt->close();
$conn->close();
?>
