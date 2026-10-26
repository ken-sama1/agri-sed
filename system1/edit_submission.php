<?php
include 'db.php';

if (!isset($_SESSION['role'], $_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: index.php');
    exit();
}

$submissionId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$submissionId) {
    http_response_code(400);
    exit('Invalid submission.');
}

$stmt = $conn->prepare(
    "SELECT id, location, crop_type, sacks, weight, weight_unit,
            land_size, land_unit, monthly_profit
     FROM farmer_submissions
     WHERE id = ? AND user_id = ? AND (status = 'pending' OR status IS NULL)"
);
if (!$stmt) {
    http_response_code(500);
    exit('Unable to load submission.');
}
$userId = (int) $_SESSION['user_id'];
$stmt->bind_param('ii', $submissionId, $userId);
if (!$stmt->execute()) {
    http_response_code(500);
    exit('Unable to load submission.');
}
$submission = $stmt->get_result()->fetch_assoc();
if (!$submission) {
    http_response_code(404);
    exit('This submission is not available for editing.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Submission | AGRI-SED</title>
    <link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">
</head>
<body>
    <nav class="navbar">
        <div class="logo">🌱 <b>AGRI SED</b></div>
        <div class="nav-menu">
            <a class="nav-link-button" href="user_home.php">BACK TO FARMER HUB</a>
        </div>
    </nav>
    <main class="section edit-page">
        <h1 class="title">EDIT PENDING SUBMISSION</h1>
        <p class="edit-page-intro">Update the details below. Only pending submissions can be edited.</p>
        <div class="centered-form">
            <form id="edit-submission-form" class="crop-card edit-submission-form" onsubmit="updateSubmission(event, <?= (int) $submission['id'] ?>)">
                <label for="edit-location">Location</label>
                <select id="edit-location" name="location" required>
                    <?php foreach (['Munoz', 'Cabanatuan', 'San Jose'] as $location): ?>
                        <option value="<?= $location ?>" <?= $submission['location'] === $location ? 'selected' : '' ?>><?= $location ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="edit-crop">Crop type</label>
                <select id="edit-crop" name="crop_type" required>
                    <?php foreach (['Rice', 'Onion', 'Sugarcane'] as $crop): ?>
                        <option value="<?= $crop ?>" <?= $submission['crop_type'] === $crop ? 'selected' : '' ?>><?= $crop ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="edit-sacks">No. of sacks</label>
                <input id="edit-sacks" name="sacks" type="number" min="0" step="1" value="<?= (int) $submission['sacks'] ?>" required>
                <label for="edit-weight">Total weight</label>
                <div class="unit-row">
                    <input id="edit-weight" name="weight" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) $submission['weight'], ENT_QUOTES, 'UTF-8') ?>" required>
                    <select id="edit-weight-unit" name="weight_unit">
                        <?php foreach (['KG', 'LB'] as $unit): ?>
                            <option value="<?= $unit ?>" <?= $submission['weight_unit'] === $unit ? 'selected' : '' ?>><?= $unit ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <label for="edit-land">Land size</label>
                <div class="unit-row">
                    <input id="edit-land" name="land_size" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) $submission['land_size'], ENT_QUOTES, 'UTF-8') ?>" required>
                    <select id="edit-land-unit" name="land_unit">
                        <?php foreach (['Hectares', 'Acres', 'SqMeters', 'SqFeet'] as $unit): ?>
                            <option value="<?= $unit ?>" <?= $submission['land_unit'] === $unit ? 'selected' : '' ?>><?= $unit ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <label for="edit-profit">Profit (₱)</label>
                <input id="edit-profit" name="profit" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) $submission['monthly_profit'], ENT_QUOTES, 'UTF-8') ?>" required>
                <button type="submit" class="btn-main">SAVE CHANGES</button>
            </form>
        </div>
    </main>
    <script src="script.js?v=<?= filemtime(__DIR__ . '/script.js') ?>"></script>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
