<?php
include 'db.php';

function failReportResolution(int $httpStatus, string $message): void
{
    http_response_code($httpStatus);
    header('Content-Type: application/json');
    echo json_encode(['error' => $message]);
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    failReportResolution(403, 'Unauthorized.');
}

$reportId = filter_input(INPUT_POST, 'report_id', FILTER_VALIDATE_INT);
if (!$reportId) {
    failReportResolution(400, 'Invalid report.');
}

$stmt = $conn->prepare(
    "UPDATE user_reports
     SET status = 'resolved', resolved_at = CURRENT_TIMESTAMP
     WHERE id = ? AND status = 'open'"
);
if (!$stmt) {
    failReportResolution(500, 'Unable to prepare report update.');
}
$stmt->bind_param('i', $reportId);
if (!$stmt->execute()) {
    failReportResolution(500, 'Unable to resolve report.');
}
if ($stmt->affected_rows !== 1) {
    failReportResolution(409, 'Report is already resolved or does not exist.');
}

header('Content-Type: application/json');
echo json_encode(['success' => true]);
$stmt->close();
$conn->close();
?>
