<?php
include 'db.php';

function failSubmissionManagement(int $httpStatus, string $message): void
{
    http_response_code($httpStatus);
    header('Content-Type: application/json');
    echo json_encode(['error' => $message]);
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    failSubmissionManagement(403, 'Unauthorized.');
}

$submissionId = filter_input(INPUT_POST, 'submission_id', FILTER_VALIDATE_INT);
$action = $_POST['action'] ?? '';
if (!$submissionId || !in_array($action, ['pause', 'resume', 'delete'], true)) {
    failSubmissionManagement(400, 'Invalid submission action.');
}

$conn->begin_transaction();
$selectStmt = $conn->prepare(
    'SELECT status, is_paused, file_land, file_crop, file_payslip
     FROM farmer_submissions WHERE id = ? FOR UPDATE'
);
if (!$selectStmt) {
    $conn->rollback();
    failSubmissionManagement(500, 'Unable to load submission.');
}
$selectStmt->bind_param('i', $submissionId);
if (!$selectStmt->execute()) {
    $conn->rollback();
    failSubmissionManagement(500, 'Unable to load submission.');
}
$submission = $selectStmt->get_result()->fetch_assoc();
if (!$submission) {
    $conn->rollback();
    failSubmissionManagement(404, 'Submission not found.');
}

$filesToRemove = [];
$cleanupWarnings = [];
if ($action === 'delete') {
    $appealStmt = $conn->prepare('DELETE FROM farmer_submission_appeals WHERE submission_id = ?');
    if (!$appealStmt) {
        $conn->rollback();
        failSubmissionManagement(500, 'Unable to prepare appeal cleanup.');
    }
    $appealStmt->bind_param('i', $submissionId);
    if (!$appealStmt->execute()) {
        $conn->rollback();
        failSubmissionManagement(500, 'Unable to remove appeal history.');
    }

    $deleteStmt = $conn->prepare('DELETE FROM farmer_submissions WHERE id = ?');
    if (!$deleteStmt) {
        $conn->rollback();
        failSubmissionManagement(500, 'Unable to prepare submission deletion.');
    }
    $deleteStmt->bind_param('i', $submissionId);
    if (!$deleteStmt->execute() || $deleteStmt->affected_rows !== 1) {
        $conn->rollback();
        failSubmissionManagement(500, 'Unable to delete submission.');
    }
    $filesToRemove = [
        $submission['file_land'],
        $submission['file_crop'],
        $submission['file_payslip'],
    ];
} else {
    if ($submission['status'] !== 'approved') {
        $conn->rollback();
        failSubmissionManagement(409, 'Only approved submissions can be paused or resumed.');
    }
    $isPaused = $action === 'pause' ? 1 : 0;
    $updateStmt = $conn->prepare('UPDATE farmer_submissions SET is_paused = ? WHERE id = ?');
    if (!$updateStmt) {
        $conn->rollback();
        failSubmissionManagement(500, 'Unable to prepare submission update.');
    }
    $updateStmt->bind_param('ii', $isPaused, $submissionId);
    if (!$updateStmt->execute()) {
        $conn->rollback();
        failSubmissionManagement(500, 'Unable to update submission.');
    }
}

if (!$conn->commit()) {
    failSubmissionManagement(500, 'Unable to complete submission action.');
}

if ($action === 'delete') {
    $uploadDirectory = realpath(__DIR__ . '/uploads');
    if ($uploadDirectory !== false) {
        foreach ($filesToRemove as $filePath) {
            if (!is_string($filePath) || $filePath === '') {
                continue;
            }
            $resolvedPath = realpath(__DIR__ . '/' . $filePath);
            if ($resolvedPath !== false &&
                dirname($resolvedPath) === $uploadDirectory &&
                is_file($resolvedPath)) {
                if (!unlink($resolvedPath)) {
                    $cleanupWarnings[] = basename($resolvedPath);
                }
            }
        }
    }
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'warning' => $cleanupWarnings
        ? 'Submission was deleted, but some uploaded files could not be removed.'
        : null,
]);
$selectStmt->close();
$conn->close();
?>
