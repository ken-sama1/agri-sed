<?php
include 'db.php';

if (!isset($_SESSION['role'], $_SESSION['user_id']) ||
    !in_array($_SESSION['role'], ['admin', 'user'], true)) {
    header('Location: index.php');
    exit();
}

$submissionId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$submissionId) {
    http_response_code(400);
    exit('Invalid submission.');
}

$sql = "SELECT s.*, u.username,
               a.id AS appeal_id, a.status AS appeal_status,
               a.message AS appeal_message, a.submitted_at AS appeal_submitted_at,
               a.reviewed_at AS appeal_reviewed_at, a.admin_comment AS appeal_admin_comment,
               a.comment_updated_at AS appeal_comment_updated_at
        FROM farmer_submissions s
        JOIN users u ON u.id = s.user_id
        LEFT JOIN farmer_submission_appeals a ON a.id = (
            SELECT MAX(latest.id)
            FROM farmer_submission_appeals latest
            WHERE latest.submission_id = s.id
        )
        WHERE s.id = ? AND (? = 'admin' OR s.user_id = ?)";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    exit('Unable to load submission details.');
}
$userId = (int) $_SESSION['user_id'];
$role = $_SESSION['role'];
$stmt->bind_param('isi', $submissionId, $role, $userId);
if (!$stmt->execute()) {
    http_response_code(500);
    exit('Unable to load submission details.');
}
$submission = $stmt->get_result()->fetch_assoc();
if (!$submission) {
    http_response_code(404);
    exit('Submission not found.');
}
$status = $submission['status'] ?? 'pending';
$backUrl = $role === 'admin' ? 'admin_home.php?section=manage' : 'user_home.php?section=history';
$fileLabels = [
    'file_land' => 'Picture of land',
    'file_crop' => 'Crop photo',
    'file_payslip' => 'Payslip',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submission Details | AGRI-SED</title>
    <link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">
</head>
<body>
    <nav class="navbar">
        <div class="logo">🌱 <b>AGRI SED</b></div>
        <div class="nav-menu"><a class="nav-link-button" href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') ?>">BACK</a></div>
    </nav>
    <main class="section submission-detail-page">
        <h1 class="title">SUBMISSION DETAILS</h1>
        <article class="submission-detail-card" id="submission-<?= (int) $submission['id'] ?>">
            <h2><?= htmlspecialchars($submission['crop_type'], ENT_QUOTES, 'UTF-8') ?> submission</h2>
            <?php if ($role === 'admin'): ?>
                <p class="detail-owner">Submitted by <?= htmlspecialchars($submission['username'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            <dl class="history-details">
                <div><dt>Status</dt><dd class="status-<?= htmlspecialchars((int) $submission['is_paused'] === 1 ? 'paused' : $status, ENT_QUOTES, 'UTF-8') ?>" data-submission-status><?= (int) $submission['is_paused'] === 1 ? 'Approved (paused)' : htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Submitted</dt><dd><?= htmlspecialchars(date('M j, Y g:i A', strtotime($submission['submitted_at'])), ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Reviewed</dt><dd><?= $submission['reviewed_at'] ? htmlspecialchars(date('M j, Y g:i A', strtotime($submission['reviewed_at'])), ENT_QUOTES, 'UTF-8') : 'Not reviewed' ?></dd></div>
                <div><dt>Location</dt><dd><?= htmlspecialchars($submission['location'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Crop type</dt><dd><?= htmlspecialchars($submission['crop_type'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Sacks</dt><dd><?= (int) $submission['sacks'] ?></dd></div>
                <div><dt>Total weight</dt><dd><?= htmlspecialchars((string) $submission['weight'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($submission['weight_unit'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Land size</dt><dd><?= htmlspecialchars((string) $submission['land_size'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($submission['land_unit'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt>Profit</dt><dd>₱<?= number_format((float) $submission['monthly_profit'], 2) ?></dd></div>
                <?php if ($status === 'approved'): ?>
                    <div><dt>Benchmark</dt><dd><?= (int) $submission['is_paused'] === 1 ? 'Paused' : 'Included' ?></dd></div>
                <?php endif; ?>
            </dl>
            <h3>Attachments</h3>
            <div class="detail-attachments">
                <?php foreach ($fileLabels as $field => $label): ?>
                    <?php if ($submission[$field]): ?>
                        <?php $path = $submission[$field]; $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION)); ?>
                        <section class="detail-attachment">
                            <h4><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></h4>
                            <?php if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)): ?>
                                <a href="<?= htmlspecialchars($path, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                                    <img src="<?= htmlspecialchars($path, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>">
                                </a>
                            <?php else: ?>
                                <a class="btn-edit-submission" href="<?= htmlspecialchars($path, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">View PDF</a>
                            <?php endif; ?>
                        </section>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php if ($role === 'admin'): ?>
                <?php if ($status === 'pending'): ?>
                    <div class="submission-actions">
                        <button class="btn-approve" type="button" onclick="reviewSubmission(<?= (int) $submission['id'] ?>, 'approved')">Accept</button>
                        <button class="btn-reject" type="button" onclick="reviewSubmission(<?= (int) $submission['id'] ?>, 'rejected')">Reject</button>
                    </div>
                <?php elseif ($status === 'approved'): ?>
                    <div class="submission-actions">
                        <?php if ((int) $submission['is_paused'] === 1): ?>
                            <button class="btn-approve" type="button" onclick="manageSubmission(<?= (int) $submission['id'] ?>, 'resume')">Resume benchmarks</button>
                        <?php else: ?>
                            <button class="btn-sub" type="button" onclick="manageSubmission(<?= (int) $submission['id'] ?>, 'pause')">Pause benchmarks</button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <div class="submission-actions">
                    <button class="btn-delete" type="button" onclick="manageSubmission(<?= (int) $submission['id'] ?>, 'delete')">Delete submission</button>
                </div>
                <?php if ($submission['appeal_status'] === 'pending'): ?>
                    <div class="submission-actions">
                        <button class="btn-approve" type="button" onclick="resolveAppeal(<?= (int) $submission['appeal_id'] ?>, 'accepted')">Accept appeal</button>
                        <button class="btn-reject" type="button" onclick="resolveAppeal(<?= (int) $submission['appeal_id'] ?>, 'denied')">Deny appeal</button>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($role === 'user' && $status === 'pending'): ?>
                <div class="submission-actions detail-actions">
                    <a class="btn-edit-submission" href="edit_submission.php?id=<?= (int) $submission['id'] ?>">Edit submission</a>
                    <button class="btn-delete" type="button" onclick="cancelSubmission(<?= (int) $submission['id'] ?>)">Cancel submission</button>
                </div>
            <?php endif; ?>
            <?php if ($submission['appeal_status']): ?>
                <h3>Appeal</h3>
                <dl class="history-details">
                    <div><dt>Status</dt><dd><?= htmlspecialchars(ucfirst($submission['appeal_status']), ENT_QUOTES, 'UTF-8') ?></dd></div>
                    <div><dt>Submitted</dt><dd><?= htmlspecialchars(date('M j, Y g:i A', strtotime($submission['appeal_submitted_at'])), ENT_QUOTES, 'UTF-8') ?></dd></div>
                    <?php if ($submission['appeal_reviewed_at']): ?>
                        <div><dt>Reviewed</dt><dd><?= htmlspecialchars(date('M j, Y g:i A', strtotime($submission['appeal_reviewed_at'])), ENT_QUOTES, 'UTF-8') ?></dd></div>
                    <?php endif; ?>
                    <div class="appeal-message"><dt>Message</dt><dd><?= nl2br(htmlspecialchars($submission['appeal_message'], ENT_QUOTES, 'UTF-8')) ?></dd></div>
                    <?php if ($submission['appeal_admin_comment']): ?>
                        <div class="appeal-message"><dt>Admin comment</dt><dd><?= nl2br(htmlspecialchars($submission['appeal_admin_comment'], ENT_QUOTES, 'UTF-8')) ?></dd></div>
                    <?php endif; ?>
                </dl>
                <?php if ($role === 'admin'): ?>
                    <div class="appeal-form">
                        <label for="appeal-comment-<?= (int) $submission['appeal_id'] ?>">Admin comment on appeal</label>
                        <textarea id="appeal-comment-<?= (int) $submission['appeal_id'] ?>" rows="3" maxlength="2000"><?= htmlspecialchars($submission['appeal_admin_comment'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        <button type="button" onclick="saveAdminComment('appeal', <?= (int) $submission['appeal_id'] ?>)">Save appeal comment</button>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($role === 'user' && $status === 'rejected' && $submission['appeal_status'] !== 'pending'): ?>
                <div class="appeal-form">
                    <label for="appeal-<?= (int) $submission['id'] ?>">Appeal this rejection</label>
                    <textarea id="appeal-<?= (int) $submission['id'] ?>" rows="4" maxlength="1000" placeholder="Explain why you believe this submission should be reviewed again."></textarea>
                    <button class="btn-appeal" type="button" onclick="submitAppeal(<?= (int) $submission['id'] ?>)">Submit appeal</button>
                </div>
            <?php endif; ?>
        </article>
    </main>
    <script src="script.js?v=<?= filemtime(__DIR__ . '/script.js') ?>"></script>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
