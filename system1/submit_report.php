<?php
include 'db.php';

function failReport(int $httpStatus, string $message): void
{
    http_response_code($httpStatus);
    header('Content-Type: application/json');
    echo json_encode(['error' => $message]);
    exit();
}

if (!isset($_SESSION['role'], $_SESSION['user_id']) ||
    $_SESSION['role'] !== 'user') {
    failReport(403, 'Unauthorized.');
}

$category = $_POST['category'] ?? '';
$message = $_POST['message'] ?? '';
if (!is_string($category) || !in_array($category, ['bug', 'ux', 'other'], true) || !is_string($message)) {
    failReport(400, 'Choose a valid report type and provide a message.');
}
$message = trim($message);
if ($message === '' || mb_strlen($message) > 2000) {
    failReport(400, 'Report details must be between 1 and 2000 characters.');
}

$attachmentPath = null;
if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
    $upload = $_FILES['attachment'];
    if ($upload['error'] !== UPLOAD_ERR_OK || $upload['size'] > 5 * 1024 * 1024) {
        failReport(400, 'Image upload failed or exceeds the 5 MB limit.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($upload['tmp_name']);
    $imageTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];
    if (!isset($imageTypes[$mime]) || @getimagesize($upload['tmp_name']) === false) {
        failReport(400, 'Attach a valid JPG, PNG, GIF, or WebP image.');
    }

    $uploadDirectory = __DIR__ . '/uploads';
    if (!is_dir($uploadDirectory) || !is_writable($uploadDirectory)) {
        failReport(500, 'Report image storage is unavailable.');
    }
    $filename = bin2hex(random_bytes(16)) . '.' . $imageTypes[$mime];
    if (!move_uploaded_file($upload['tmp_name'], $uploadDirectory . '/' . $filename)) {
        failReport(500, 'Unable to save the report image.');
    }
    $attachmentPath = 'uploads/' . $filename;
}

$stmt = $conn->prepare(
    "INSERT INTO user_reports (user_id, category, message, attachment_path) VALUES (?, ?, ?, ?)"
);
if (!$stmt) {
    if ($attachmentPath !== null) {
        unlink(__DIR__ . '/' . $attachmentPath);
    }
    failReport(500, 'Unable to prepare report submission.');
}
$userId = (int) $_SESSION['user_id'];
$stmt->bind_param('isss', $userId, $category, $message, $attachmentPath);
if (!$stmt->execute()) {
    if ($attachmentPath !== null) {
        unlink(__DIR__ . '/' . $attachmentPath);
    }
    failReport(500, 'Unable to submit report.');
}

header('Content-Type: application/json');
echo json_encode(['success' => true]);
$stmt->close();
$conn->close();
?>
