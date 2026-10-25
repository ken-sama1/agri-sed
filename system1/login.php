<?php
include 'db.php';
$username = $_POST['username'];
$password = $_POST['password'];
$role = $_POST['role'];

$sql = "SELECT id, password, role FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    // CHECK: Either the password is encrypted (new users) OR it is plain text (sample users)
    if (password_verify($password, $user['password']) || $password === $user['password']) {
        if ($user['role'] === $role) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            echo "Success";
        } else {
            echo "Invalid Role: This account is a " . $user['role'];
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
