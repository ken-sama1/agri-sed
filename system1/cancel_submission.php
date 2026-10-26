<?php
include 'db.php';

function failSubmissionCancellation(int $status, string $message): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode(['error' => $message]);
    exit();
}

if (!isset($_SESSION['role'], $_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    failSubmissionCancellation(403, 'Unauthorized.');
}

$submissionId = filter_input(INPUT_POST, 'submission_id', FILTER_VALIDATE_INT);
if (!$submissionId) {
    failSubmissionCancellation(400, 'Invalid submission.');
}

$userId = (int) $_SESSION['user_id'];
$stmt = $conn->prepare(
    "UPDATE farmer_submissions
     SET status = 'cancelled'
     WHERE id = ? AND user_id = ? AND (status = 'pending' OR status IS NULL)"
);
if (!$stmt) {
    failSubmissionCancellation(500, 'Unable to prepare submission cancellation.');
}
$stmt->bind_param('ii', $submissionId, $userId);
if (!$stmt->execute()) {
    failSubmissionCancellation(500, 'Unable to cancel submission.');
}
if ($stmt->affected_rows !== 1) {
    failSubmissionCancellation(409, 'Only your pending submissions can be cancelled.');
}

header('Content-Type: application/json');
echo json_encode(['success' => true]);
$stmt->close();
$conn->close();
?>
