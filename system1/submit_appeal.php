<?php
include 'db.php';

function failAppeal(int $httpStatus, string $message): void
{
    http_response_code($httpStatus);
    header('Content-Type: application/json');
    echo json_encode(['error' => $message]);
    exit();
}

if (!isset($_SESSION['role'], $_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    failAppeal(403, 'Unauthorized.');
}

$submissionId = filter_input(INPUT_POST, 'submission_id', FILTER_VALIDATE_INT);
$message = $_POST['message'] ?? '';
if (!$submissionId || !is_string($message)) {
    failAppeal(400, 'Enter an appeal message of up to 1000 characters.');
}
$message = trim($message);
if ($message === '' || mb_strlen($message) > 1000) {
    failAppeal(400, 'Enter an appeal message of up to 1000 characters.');
}

$userId = (int) $_SESSION['user_id'];
$conn->begin_transaction();
$submissionStmt = $conn->prepare(
    "SELECT id FROM farmer_submissions
     WHERE id = ? AND user_id = ? AND status = 'rejected'
     FOR UPDATE"
);
if (!$submissionStmt) {
    $conn->rollback();
    failAppeal(500, 'Unable to validate this submission.');
}
$submissionStmt->bind_param('ii', $submissionId, $userId);
if (!$submissionStmt->execute()) {
    $conn->rollback();
    failAppeal(500, 'Unable to validate this submission.');
}
if (!$submissionStmt->get_result()->fetch_assoc()) {
    $conn->rollback();
    failAppeal(404, 'Only your rejected submissions can be appealed.');
}

$pendingStmt = $conn->prepare(
    "SELECT id FROM farmer_submission_appeals WHERE submission_id = ? AND status = 'pending' LIMIT 1"
);
if (!$pendingStmt) {
    $conn->rollback();
    failAppeal(500, 'Unable to check existing appeals.');
}
$pendingStmt->bind_param('i', $submissionId);
if (!$pendingStmt->execute()) {
    $conn->rollback();
    failAppeal(500, 'Unable to check existing appeals.');
}
if ($pendingStmt->get_result()->fetch_assoc()) {
    $conn->rollback();
    failAppeal(409, 'An appeal for this submission is already being reviewed.');
}

$appealStmt = $conn->prepare(
    "INSERT INTO farmer_submission_appeals (submission_id, user_id, message) VALUES (?, ?, ?)"
);
if (!$appealStmt) {
    $conn->rollback();
    failAppeal(500, 'Unable to prepare appeal.');
}
$appealStmt->bind_param('iis', $submissionId, $userId, $message);
if (!$appealStmt->execute()) {
    $conn->rollback();
    failAppeal(500, 'Unable to submit appeal.');
}
if (!$conn->commit()) {
    failAppeal(500, 'Unable to complete appeal submission.');
}

header('Content-Type: application/json');
echo json_encode(['success' => true]);
$appealStmt->close();
$pendingStmt->close();
$submissionStmt->close();
$conn->close();
?>
