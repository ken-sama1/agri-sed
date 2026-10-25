<?php
include 'db.php';

function failSubmissionUpdate(int $httpStatus, string $message): void
{
    http_response_code($httpStatus);
    header('Content-Type: application/json');
    echo json_encode(['error' => $message]);
    exit();
}

if (!isset($_SESSION['role'], $_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    failSubmissionUpdate(403, 'Unauthorized.');
}

$submissionId = filter_input(INPUT_POST, 'submission_id', FILTER_VALIDATE_INT);
$location = $_POST['location'] ?? '';
$crop = $_POST['crop_type'] ?? '';
$sacks = $_POST['sacks'] ?? '';
$weight = $_POST['weight'] ?? '';
$weightUnit = $_POST['weight_unit'] ?? '';
$land = $_POST['land_size'] ?? '';
$landUnit = $_POST['land_unit'] ?? '';
$profit = $_POST['profit'] ?? '';

if (!$submissionId ||
    !is_string($location) || !in_array($location, ['Munoz', 'Cabanatuan', 'San Jose'], true) ||
    !is_string($crop) || !in_array($crop, ['Rice', 'Onion', 'Sugar'], true) ||
    !is_string($weightUnit) || !in_array($weightUnit, ['KG', 'LB'], true) ||
    !is_string($landUnit) || !in_array($landUnit, ['Hectares', 'Acres', 'SqMeters', 'SqFeet'], true) ||
    !is_string($sacks) || !preg_match('/^\d{1,10}$/', $sacks) ||
    !is_string($weight) || !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $weight) ||
    !is_string($land) || !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $land) ||
    !is_string($profit) || !preg_match('/^\d{1,13}(?:\.\d{1,2})?$/', $profit)) {
    failSubmissionUpdate(400, 'Check the submission fields and try again.');
}

$userId = (int) $_SESSION['user_id'];
$stmt = $conn->prepare(
    "UPDATE farmer_submissions
     SET location = ?, crop_type = ?, sacks = ?, weight = ?, weight_unit = ?,
         land_size = ?, land_unit = ?, monthly_profit = ?
     WHERE id = ? AND user_id = ? AND (status = 'pending' OR status IS NULL)"
);
if (!$stmt) {
    failSubmissionUpdate(500, 'Unable to prepare submission update.');
}

$sacksValue = (int) $sacks;
$weightValue = (float) $weight;
$landValue = (float) $land;
$profitValue = (float) $profit;
$stmt->bind_param(
    'ssidsdsdii',
    $location,
    $crop,
    $sacksValue,
    $weightValue,
    $weightUnit,
    $landValue,
    $landUnit,
    $profitValue,
    $submissionId,
    $userId
);
if (!$stmt->execute()) {
    failSubmissionUpdate(500, 'Unable to update submission.');
}

if ($stmt->affected_rows === 0) {
    $checkStmt = $conn->prepare(
        "SELECT id FROM farmer_submissions WHERE id = ? AND user_id = ? AND (status = 'pending' OR status IS NULL)"
    );
    if (!$checkStmt) {
        failSubmissionUpdate(500, 'Unable to verify submission update.');
    }
    $checkStmt->bind_param('ii', $submissionId, $userId);
    if (!$checkStmt->execute() || !$checkStmt->get_result()->fetch_assoc()) {
        failSubmissionUpdate(409, 'Only your pending submissions can be edited.');
    }
}

header('Content-Type: application/json');
echo json_encode(['success' => true]);
$stmt->close();
$conn->close();
?>
