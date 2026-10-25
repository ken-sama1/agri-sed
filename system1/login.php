<?php
include 'db.php';

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? '';
$workerId = trim($_POST['workerId'] ?? '');

$sql = "SELECT id, password, role, worker_id FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();

$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {

    if (
        password_verify($password, $user['password']) ||
        $password === $user['password']
    ) {
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
