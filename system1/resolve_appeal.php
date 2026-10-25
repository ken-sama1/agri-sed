<?php
include 'db.php';

function failAppealResolution(int $httpStatus, string $message): void
{
    http_response_code($httpStatus);
    header('Content-Type: application/json');
    echo json_encode(['error' => $message]);
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    failAppealResolution(403, 'Unauthorized.');
}

$appealId = filter_input(INPUT_POST, 'appeal_id', FILTER_VALIDATE_INT);
$decision = $_POST['decision'] ?? '';
if (!$appealId || !in_array($decision, ['accepted', 'denied'], true)) {
    failAppealResolution(400, 'Invalid appeal decision.');
}

$conn->begin_transaction();
$appealStmt = $conn->prepare(
    "SELECT submission_id FROM farmer_submission_appeals WHERE id = ? AND status = 'pending' FOR UPDATE"
);
if (!$appealStmt) {
    $conn->rollback();
    failAppealResolution(500, 'Unable to prepare appeal review.');
}
$appealStmt->bind_param('i', $appealId);
if (!$appealStmt->execute()) {
    $conn->rollback();
    failAppealResolution(500, 'Unable to load appeal.');
}
$appeal = $appealStmt->get_result()->fetch_assoc();
if (!$appeal) {
    $conn->rollback();
    failAppealResolution(409, 'This appeal has already been reviewed.');
}

if ($decision === 'accepted') {
    $submissionStmt = $conn->prepare(
        "UPDATE farmer_submissions SET status = 'pending', reviewed_at = NULL
         WHERE id = ? AND status = 'rejected'"
    );
    if (!$submissionStmt) {
        $conn->rollback();
        failAppealResolution(500, 'Unable to reopen submission.');
    }
    $submissionId = (int) $appeal['submission_id'];
    $submissionStmt->bind_param('i', $submissionId);
    if (!$submissionStmt->execute() || $submissionStmt->affected_rows !== 1) {
        $conn->rollback();
        failAppealResolution(409, 'The rejected submission is no longer available for appeal.');
    }
}

$updateStmt = $conn->prepare(
    'UPDATE farmer_submission_appeals SET status = ?, reviewed_at = CURRENT_TIMESTAMP WHERE id = ?'
);
if (!$updateStmt) {
    $conn->rollback();
    failAppealResolution(500, 'Unable to save appeal decision.');
}
$updateStmt->bind_param('si', $decision, $appealId);
if (!$updateStmt->execute()) {
    $conn->rollback();
    failAppealResolution(500, 'Unable to save appeal decision.');
}

if (!$conn->commit()) {
    failAppealResolution(500, 'Unable to complete appeal review.');
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'decision' => $decision]);
$updateStmt->close();
$appealStmt->close();
$conn->close();
?>
