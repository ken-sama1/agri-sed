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

// Initialize optional file paths
$targetDir = "uploads/";
$files = ['file_land', 'file_crop', 'file_payslip'];
$paths = [
    'file_land' => null,
    'file_crop' => null,
    'file_payslip' => null
];

foreach ($files as $f) {
    if (isset($_FILES[$f]) &&
        $_FILES[$f]['error'] === UPLOAD_ERR_OK) {

        $fileName = time() . "_" . basename($_FILES[$f]["name"]);
        $targetFilePath = $targetDir . $fileName;

        if (move_uploaded_file($_FILES[$f]["tmp_name"], $targetFilePath)) {
            $paths[$f] = $targetFilePath;
        }
    }
}

$sql = "INSERT INTO farmer_submissions
    (user_id, location, crop_type, sacks, weight, weight_unit,
     land_size, land_unit, monthly_profit, file_land, file_crop, file_payslip)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
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
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
