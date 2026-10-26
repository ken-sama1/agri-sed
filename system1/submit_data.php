<?php
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    die("Unauthorized");
}

$userId = $_SESSION['user_id'];
$location = $_POST['location'] ?? '';
$crop = $_POST['crop_type'] ?? '';
$sacks = $_POST['sacks'] ?? '';
$weight = $_POST['weight'] ?? '';
$wUnit = $_POST['weightUnit'] ?? '';
$land = $_POST['landSize'] ?? '';
$lUnit = $_POST['landUnit'] ?? '';
$profit = $_POST['profit'] ?? '';

// Validate allowed enum values
if (!is_string($location) ||
    !in_array($location, ['Munoz', 'Cabanatuan', 'San Jose'], true) ||
    !is_string($crop) ||
    !in_array($crop, ['Rice', 'Onion', 'Sugar'], true) ||
    !is_string($wUnit) ||
    !in_array($wUnit, ['KG', 'LB'], true) ||
    !is_string($lUnit) ||
    !is_string($sacks) || !preg_match('/^\d{1,10}$/', $sacks) ||
    !is_string($weight) || !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $weight) ||
    !is_string($land) || !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $land) ||
    !is_string($profit) || !preg_match('/^\d{1,13}(?:\.\d{1,2})?$/', $profit) ||
    !in_array($lUnit, ['Hectares', 'Acres', 'SqMeters', 'SqFeet'], true)) {
    http_response_code(400);
    die("Invalid form values.");
}

// Require and validate all evidence files before creating the submission.
$targetDir = __DIR__ . "/uploads/";
$allowedFileTypes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
    'application/pdf' => 'pdf',
];
$files = ['file_land', 'file_crop', 'file_payslip'];
$paths = [
    'file_land' => null,
    'file_crop' => null,
    'file_payslip' => null
];
$removeUploadedFiles = static function (array $uploadedPaths): void {
    foreach ($uploadedPaths as $path) {
        if ($path !== null) {
            unlink(__DIR__ . '/' . $path);
        }
    }
};
$fileInfo = new finfo(FILEINFO_MIME_TYPE);

foreach ($files as $f) {
    if (!isset($_FILES[$f]) || $_FILES[$f]['error'] !== UPLOAD_ERR_OK) {
        $removeUploadedFiles($paths);
        http_response_code(400);
        exit("Upload a picture of land, crop photo, and payslip as images or PDFs.");
    }

    $mimeType = $fileInfo->file($_FILES[$f]['tmp_name']);
    $isValidImage = str_starts_with((string) $mimeType, 'image/') &&
        @getimagesize($_FILES[$f]['tmp_name']) !== false;
    $isValidPdf = $mimeType === 'application/pdf' &&
        file_get_contents($_FILES[$f]['tmp_name'], false, null, 0, 5) === '%PDF-';
    if (!isset($allowedFileTypes[$mimeType]) || (!$isValidImage && !$isValidPdf)) {
        $removeUploadedFiles($paths);
        http_response_code(400);
        exit("All submission attachments must be valid JPG, PNG, GIF, WebP, or PDF files.");
    }

    $fileName = bin2hex(random_bytes(16)) . '.' . $allowedFileTypes[$mimeType];
    $targetFilePath = $targetDir . $fileName;
    if (!move_uploaded_file($_FILES[$f]['tmp_name'], $targetFilePath)) {
        $removeUploadedFiles($paths);
        http_response_code(500);
        exit("Unable to save submission files.");
    }
    $paths[$f] = "uploads/" . $fileName;
}

$sql = "INSERT INTO farmer_submissions
    (user_id, location, crop_type, sacks, weight, weight_unit,
     land_size, land_unit, monthly_profit, file_land, file_crop, file_payslip)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    $removeUploadedFiles($paths);
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param(
    "issidsdsdsss",
    $userId,
    $location,
    $crop,
    $sacks,
    $weight,
    $wUnit,
    $land,
    $lUnit,
    $profit,
    $paths['file_land'],
    $paths['file_crop'],
    $paths['file_payslip']
);

if ($stmt->execute()) {
    echo "Success";
} else {
    $removeUploadedFiles($paths);
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
