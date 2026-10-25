<?php
include 'db.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') { die("Unauthorized"); }

$userId = $_SESSION['user_id'];
$location = htmlspecialchars($_POST['location']);
$crop = htmlspecialchars($_POST['crop_type']);
$sacks = $_POST['sacks'];
$weight = $_POST['weight'];
$wUnit = $_POST['weightUnit'];
$land = $_POST['landSize'];
$lUnit = $_POST['landUnit'];
$profit = $_POST['profit'];

// SECURITY: XSS Check
if (strpos($location . $crop, '<script>') !== false) {
    die("Security Alert: Malicious input detected!");
}

$targetDir = "uploads/";
$files = ['file_land', 'file_crop', 'file_payslip'];
$paths = [];
foreach ($files as $f) {
    if (isset($_FILES[$f])) {
        $fileName = time() . "_" . basename($_FILES[$f]["name"]);
        $targetFilePath = $targetDir . $fileName;
        $paths[$f] = move_uploaded_file($_FILES[$f]["tmp_name"], $targetFilePath) ? $targetFilePath : null;
    }
}

$sql = "INSERT INTO farmer_submissions (user_id, location, crop_type, sacks, weight, weight_unit, land_size, land_unit, monthly_profit, file_land, file_crop, file_payslip) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("isssdssdsss", $userId, $location, $crop, $sacks, $weight, $wUnit, $land, $lUnit, $profit, $paths['file_land'], $paths['file_crop'], $paths['file_payslip']);
echo ($stmt->execute()) ? "Success" : "Error: " . $stmt->error;
?>
