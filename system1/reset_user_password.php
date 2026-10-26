<?php
include 'db.php';

function failPasswordReset(int $status, string $message): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode(['error' => $message]);
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    failPasswordReset(403, 'Unauthorized.');
}

$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$password = $_POST['password'] ?? '';
if (!$userId || !is_string($password)) {
    failPasswordReset(400, 'Invalid password reset request.');
}
if (!preg_match('/^(?=(?:[^A-Za-z]*[A-Za-z]){6})(?=.*[0-9]).{8,}$/', $password)) {
    failPasswordReset(400, 'Password must be at least 8 characters, contain 6 letters, and include a number.');
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ? AND role = 'user'");
if (!$stmt) {
    failPasswordReset(500, 'Unable to prepare password reset.');
}
$stmt->bind_param('si', $passwordHash, $userId);
if (!$stmt->execute()) {
    failPasswordReset(500, 'Unable to update user password.');
}
if ($stmt->affected_rows !== 1) {
    failPasswordReset(404, 'User not found.');
}

header('Content-Type: application/json');
echo json_encode(['success' => true]);
$stmt->close();
$conn->close();
?>
