<?php
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    die("Unauthorized");
}

$userId = $_SESSION['user_id'];
$location = $_POST['location'] ?? '';
$crop = $_POST['crop_type'] ?? '';
$sacks = $_POST['sacks'] ?? 0;
$weight = $_POST['weight'] ?? 0;
$wUnit = $_POST['weightUnit'] ?? '';
$land = $_POST['landSize'] ?? 0;
$lUnit = $_POST['landUnit'] ?? '';
$profit = $_POST['profit'] ?? 0;

// Validate allowed enum values
if (!in_array($location, ['Munoz', 'Cabanatuan', 'San Jose'], true) ||
    !in_array($crop, ['Rice', 'Onion', 'Sugar'], true) ||
    !in_array($wUnit, ['KG', 'LB'], true) ||
    !in_array($lUnit, ['Hectares', 'Acres', 'SqMeters', 'SqFeet'], true)) {
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
