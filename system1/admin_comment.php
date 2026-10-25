<?php
include 'db.php';

function failAdminComment(int $httpStatus, string $message): void
{
    http_response_code($httpStatus);
    header('Content-Type: application/json');
    echo json_encode(['error' => $message]);
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    failAdminComment(403, 'Unauthorized.');
}

$type = $_POST['type'] ?? '';
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$comment = $_POST['comment'] ?? '';
if (!in_array($type, ['appeal', 'report'], true) || !$id || !is_string($comment)) {
    failAdminComment(400, 'Invalid comment request.');
}
$comment = trim($comment);
if ($comment === '' || mb_strlen($comment) > 2000) {
    failAdminComment(400, 'Comments must be between 1 and 2000 characters.');
}

$table = $type === 'appeal' ? 'farmer_submission_appeals' : 'user_reports';
$stmt = $conn->prepare(
    "UPDATE `$table` SET admin_comment = ?, comment_updated_at = CURRENT_TIMESTAMP WHERE id = ?"
);
if (!$stmt) {
    failAdminComment(500, 'Unable to prepare comment update.');
}
$stmt->bind_param('si', $comment, $id);
if (!$stmt->execute()) {
    failAdminComment(500, 'Unable to save comment.');
}
if ($stmt->affected_rows === 0) {
    $existsStmt = $conn->prepare("SELECT id FROM `$table` WHERE id = ?");
    if (!$existsStmt) {
        failAdminComment(500, 'Unable to verify comment update.');
    }
    $existsStmt->bind_param('i', $id);
    if (!$existsStmt->execute() || !$existsStmt->get_result()->fetch_assoc()) {
        failAdminComment(404, 'Report or appeal not found.');
    }
}

header('Content-Type: application/json');
echo json_encode(['success' => true]);
$stmt->close();
$conn->close();
?>
