<?php
include 'db.php';

function failReview(int $httpStatus, string $message): void
{
    http_response_code($httpStatus);
    header('Content-Type: application/json');
    echo json_encode(['error' => $message]);
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    failReview(403, "Unauthorized");
}

$submissionId = filter_input(INPUT_POST, 'submission_id', FILTER_VALIDATE_INT);
$status = $_POST['status'] ?? '';
if (!$submissionId || !in_array($status, ['approved', 'rejected'], true)) {
    failReview(400, "Invalid submission review.");
}

$stmt = $conn->prepare(
    "UPDATE farmer_submissions
     SET status = ?, reviewed_at = CURRENT_TIMESTAMP
     WHERE id = ? AND (status = 'pending' OR status IS NULL)"
);
if (!$stmt) {
    failReview(500, "Unable to prepare submission review.");
}

$stmt->bind_param("si", $status, $submissionId);
if (!$stmt->execute()) {
    failReview(500, "Unable to update submission.");
}
if ($stmt->affected_rows !== 1) {
    failReview(409, "Submission is no longer pending.");
}

$timestampStmt = $conn->prepare("SELECT reviewed_at FROM farmer_submissions WHERE id = ?");
if (!$timestampStmt) {
    failReview(500, "Submission was updated, but its review timestamp could not be loaded.");
}
$timestampStmt->bind_param("i", $submissionId);
if (!$timestampStmt->execute()) {
    failReview(500, "Submission was updated, but its review timestamp could not be loaded.");
}
$timestampRow = $timestampStmt->get_result()->fetch_assoc();
if (!$timestampRow || !$timestampRow['reviewed_at']) {
    failReview(500, "Submission was updated, but its review timestamp could not be loaded.");
}
$reviewedAt = $timestampRow['reviewed_at'];
header('Content-Type: application/json');
echo json_encode([
    'status' => $status,
    'reviewed_at' => $reviewedAt,
    'reviewed_at_display' => date('M j, Y g:i A', strtotime($reviewedAt)),
]);
$timestampStmt->close();
$stmt->close();
$conn->close();
?>
