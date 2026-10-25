<?php
include 'db.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'user'], true)) {
    http_response_code(403);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Unauthorized']));
}

$result = $conn->query(
    "SELECT location, crop_type,
            SUM(monthly_profit) AS total_profit,
            SUM(sacks) AS total_sacks,
            SUM(CASE WHEN weight_unit = 'LB' THEN weight * 0.45359237 ELSE weight END) AS total_weight_kg
     FROM farmer_submissions
     WHERE status = 'approved' AND is_paused = 0
     GROUP BY location, crop_type"
);
if (!$result) {
    http_response_code(500);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Unable to load benchmark data.']));
}

$locations = ['Munoz', 'Cabanatuan', 'San Jose'];
$crops = ['Rice', 'Onion', 'Sugar'];
$totals = [];
foreach ($locations as $location) {
    foreach ($crops as $crop) {
        $totals[$location][$crop] = [
            'profit' => 0.0,
            'sacks' => 0,
            'weight_kg' => 0.0,
        ];
    }
}

while ($row = $result->fetch_assoc()) {
    $totals[$row['location']][$row['crop_type']] = [
        'profit' => (float) $row['total_profit'],
        'sacks' => (int) $row['total_sacks'],
        'weight_kg' => (float) $row['total_weight_kg'],
    ];
}

header('Content-Type: application/json');
echo json_encode([
    'locations' => $locations,
    'crops' => $crops,
    'totals' => $totals,
]);
$result->free();
$conn->close();
?>
