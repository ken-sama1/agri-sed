<?php
include 'db.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$statsResult = $conn->query(
    "SELECT
        (SELECT COUNT(*) FROM users WHERE role = 'user') AS farmers,
        (SELECT COUNT(*) FROM farmer_submissions WHERE status = 'pending' OR status IS NULL) AS pending,
        (SELECT COUNT(*) FROM farmer_submissions WHERE status = 'approved' AND is_paused = 0) AS approved"
);
$userResult = $conn->query(
    "SELECT u.id, u.username, COUNT(s.id) AS submissions,
            SUM(CASE WHEN s.status = 'approved' THEN 1 ELSE 0 END) AS approved
     FROM users u
     LEFT JOIN farmer_submissions s ON s.user_id = u.id
     WHERE u.role = 'user'
     GROUP BY u.id, u.username
     ORDER BY u.username"
);
if (!$statsResult || !$userResult) {
    die("Unable to load admin dashboard.");
}
$stats = $statsResult->fetch_assoc();

$submissionStmt = $conn->prepare(
    "SELECT s.id, u.username, s.location, s.crop_type, s.sacks, s.weight, s.weight_unit,
            s.land_size, s.land_unit, s.monthly_profit, s.file_land, s.file_crop,
            s.file_payslip, s.status, s.submitted_at, s.reviewed_at, s.is_paused,
            a.id AS appeal_id, a.status AS appeal_status, a.message AS appeal_message,
            a.submitted_at AS appeal_submitted_at, a.reviewed_at AS appeal_reviewed_at,
            a.admin_comment AS appeal_admin_comment,
            a.comment_updated_at AS appeal_comment_updated_at
     FROM farmer_submissions s
     JOIN users u ON u.id = s.user_id
     LEFT JOIN (
         SELECT appeals.*
         FROM farmer_submission_appeals appeals
         INNER JOIN (
             SELECT submission_id, MAX(id) AS latest_id
             FROM farmer_submission_appeals
             GROUP BY submission_id
         ) latest ON latest.latest_id = appeals.id
     ) a ON a.submission_id = s.id
     ORDER BY s.submitted_at DESC"
);
if (!$submissionStmt || !$submissionStmt->execute()) {
    die("Unable to load submissions.");
}
$submissionResult = $submissionStmt->get_result();

$reportResult = $conn->query(
    "SELECT r.id, r.user_id, u.username, u.role, r.category, r.message, r.status,
            r.submitted_at, r.resolved_at, r.attachment_path, r.admin_comment,
            r.comment_updated_at
     FROM user_reports r
     JOIN users u ON u.id = r.user_id
     ORDER BY r.status = 'open' DESC, r.submitted_at DESC"
);
if (!$reportResult) {
    die("Unable to load user reports.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>Admin Command Center | AGRI-SED</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <nav class="navbar">
        <div class="logo">🌱 <b>AGRI SED</b></div>
        <div class="nav-menu">
            <button onclick="showSection('adminHome')">HOME</button>
            <div class="dropdown">
                <button onclick="toggleDropdown(event)">SUB CONTENT ▾</button>
                <div class="dropdown-content" id="dropdown-menu">
                    <a onclick="showSection('manage')">MANAGE SUBMISSIONS</a>
                    <a onclick="showSection('reports')">USER REPORTS</a>
                    <a onclick="showSection('directory')">USER DIRECTORY</a>
                    <a onclick="location.href='logout.php'">LOG OUT</a>
                </div>
            </div>
        </div>
    </nav>

    <div id="mainContent">
        <section id="adminHome" class="section">
            <h1 class="title">Agri-SED <span class="highlight">Command Center</span></h1>
            <div class="admin-stats">
                <div class="stat-card"><span class="stat-icon">👥</span><h3 id="statFarmers"><?= (int) $stats['farmers'] ?></h3><p>Total Farmers</p></div>
                <div class="stat-card highlight-card"><span class="stat-icon">⏳</span><h3 id="statPending"><?= (int) $stats['pending'] ?></h3><p>Pending Approvals</p></div>
                <div class="stat-card"><span class="stat-icon">✅</span><h3 id="statApproved"><?= (int) $stats['approved'] ?></h3><p>Approved Data</p></div>
            </div>
            <div class="admin-actions">
                <button class="btn-main" onclick="showSection('manage')">MANAGE SUBMISSIONS</button>
                <button class="btn-sub" onclick="showSection('reports')">USER REPORTS</button>
                <button class="btn-sub" onclick="showSection('directory')">USER DIRECTORY</button>
                <button class="btn-main" onclick="showSection('benchmark')">DATA BENCHMARK</button>
            </div>
        </section>

        <section id="benchmark" class="section hidden">
            <h1 class="title">REGIONAL BENCHMARK</h1>
            <div class="benchmark-grid">
                <div class="chart-box">
                    <h2 class="chart-title">Total earnings by area</h2>
                    <canvas id="adminBarChart"></canvas>
                </div>
                <div class="chart-box">
                    <h2 class="chart-title">Crop totals by area</h2>
                    <div class="chart-controls">
                        <label for="adminBenchmarkCrop">Crop</label>
                        <select id="adminBenchmarkCrop" class="input-field">
                            <option value="Rice">Rice</option>
                            <option value="Onion">Onion</option>
                            <option value="Sugar">Sugar</option>
                        </select>
                        <label for="adminBenchmarkMetric">Total</label>
                        <select id="adminBenchmarkMetric" class="input-field">
                            <option value="profit">Profit (₱)</option>
                            <option value="sacks">Sacks</option>
                            <option value="weight_kg">Weight (kg)</option>
                        </select>
                    </div>
                    <canvas id="adminPieChart"></canvas>
                </div>
            </div>
            <p id="benchmarkMessage" class="history-empty" role="status"></p>
        </section>

        <section id="manage" class="section hidden">
            <h1 class="title">MANAGE SUBMISSIONS</h1>
            <div id="submissionList" class="history-container">
                <?php if ($submissionResult->num_rows === 0): ?>
                    <p class="history-empty">No submissions yet.</p>
                <?php else: ?>
                    <?php while ($submission = $submissionResult->fetch_assoc()): ?>
                        <?php $status = $submission['status'] ?? 'pending'; ?>
                        <article class="history-card" id="submission-<?= (int) $submission['id'] ?>">
                            <h2><?= htmlspecialchars($submission['username'], ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars($submission['crop_type'], ENT_QUOTES, 'UTF-8') ?></h2>
                            <div class="submission-summary">
                                <span><?= htmlspecialchars($submission['location'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span>₱<?= number_format((float) $submission['monthly_profit'], 2) ?></span>
                                <span class="status-<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>" data-submission-status><?= htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="submission-actions">
                                <a class="btn-edit-submission" href="submission_detail.php?id=<?= (int) $submission['id'] ?>">View details</a>
                            </div>
                            <span class="submission-card-date">Submitted <?= $submission['submitted_at'] ? htmlspecialchars(date('M j, Y', strtotime($submission['submitted_at'])), ENT_QUOTES, 'UTF-8') : 'date unavailable' ?></span>
                        </article>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </section>

        <section id="reports" class="section hidden">
            <h1 class="title">USER REPORTS</h1>
            <div class="history-container">
                <?php if ($reportResult->num_rows === 0): ?>
                    <p class="history-empty">No user reports yet.</p>
                <?php else: ?>
                    <?php while ($report = $reportResult->fetch_assoc()): ?>
                        <article class="history-card" id="report-<?= (int) $report['id'] ?>">
                            <h2><?= htmlspecialchars(ucfirst($report['category']), ENT_QUOTES, 'UTF-8') ?> report from <?= htmlspecialchars($report['username'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($report['role'], ENT_QUOTES, 'UTF-8') ?>)</h2>
                            <dl class="history-details">
                                <div><dt>Submitted</dt><dd><?= htmlspecialchars(date('M j, Y g:i A', strtotime($report['submitted_at'])), ENT_QUOTES, 'UTF-8') ?></dd></div>
                                <div><dt>Status</dt><dd class="status-<?= htmlspecialchars($report['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(ucfirst($report['status']), ENT_QUOTES, 'UTF-8') ?></dd></div>
                                <?php if ($report['resolved_at']): ?>
                                    <div><dt>Resolved</dt><dd><?= htmlspecialchars(date('M j, Y g:i A', strtotime($report['resolved_at'])), ENT_QUOTES, 'UTF-8') ?></dd></div>
                                <?php endif; ?>
                                <div class="appeal-message"><dt>Report</dt><dd><?= nl2br(htmlspecialchars($report['message'], ENT_QUOTES, 'UTF-8')) ?></dd></div>
                                <?php if ($report['admin_comment']): ?>
                                    <div class="appeal-message"><dt>Admin comment</dt><dd><?= nl2br(htmlspecialchars($report['admin_comment'], ENT_QUOTES, 'UTF-8')) ?></dd></div>
                                <?php endif; ?>
                                <?php if ($report['comment_updated_at']): ?>
                                    <div><dt>Comment updated</dt><dd><?= htmlspecialchars(date('M j, Y g:i A', strtotime($report['comment_updated_at'])), ENT_QUOTES, 'UTF-8') ?></dd></div>
                                <?php endif; ?>
                            </dl>
                            <?php if ($report['attachment_path']): ?>
                                <a class="report-attachment" href="<?= htmlspecialchars($report['attachment_path'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener"><img src="<?= htmlspecialchars($report['attachment_path'], ENT_QUOTES, 'UTF-8') ?>" alt="Attached report image"></a>
                            <?php endif; ?>
                            <div class="appeal-form">
                                <label for="report-comment-<?= (int) $report['id'] ?>">Admin comment</label>
                                <textarea id="report-comment-<?= (int) $report['id'] ?>" rows="3" maxlength="2000"><?= htmlspecialchars($report['admin_comment'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                                <button type="button" onclick="saveAdminComment('report', <?= (int) $report['id'] ?>)">Save report comment</button>
                            </div>
                            <?php if ($report['status'] === 'open'): ?>
                                <div class="submission-actions">
                                    <button class="btn-approve" type="button" onclick="resolveUserReport(<?= (int) $report['id'] ?>)">Mark resolved</button>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </section>

        <section id="directory" class="section hidden">
            <h1 class="title">USER DIRECTORY</h1>
            <div id="userList" class="history-container">
                <?php if ($userResult->num_rows === 0): ?>
                    <p class="history-empty">No farmers registered yet.</p>
                <?php else: ?>
                    <?php while ($farmer = $userResult->fetch_assoc()): ?>
                        <article class="history-card">
                            <h2><?= htmlspecialchars($farmer['username'], ENT_QUOTES, 'UTF-8') ?></h2>
                            <dl class="history-details">
                                <div><dt>Submissions</dt><dd><?= (int) $farmer['submissions'] ?></dd></div>
                                <div><dt>Approved</dt><dd><?= (int) $farmer['approved'] ?></dd></div>
                            </dl>
                            <button class="btn-edit-submission" type="button" onclick="openPasswordReset(<?= (int) $farmer['id'] ?>, <?= htmlspecialchars(json_encode($farmer['username']), ENT_QUOTES, 'UTF-8') ?>)">Reset password</button>
                        </article>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>
    <dialog id="password-reset-dialog" class="password-reset-dialog">
        <form id="password-reset-form" onsubmit="resetUserPassword(event)">
            <h2>Reset user password</h2>
            <p id="password-reset-username"></p>
            <input type="hidden" id="password-reset-user-id">
            <label for="new-user-password">New password</label>
            <input id="new-user-password" type="password" minlength="8" required autocomplete="new-password" pattern="(?=(?:[^A-Za-z]*[A-Za-z]){6})(?=.*[0-9]).{8,}" title="At least 8 characters, including 6 letters and 1 number.">
            <label for="confirm-user-password">Confirm new password</label>
            <input id="confirm-user-password" type="password" minlength="8" required autocomplete="new-password">
            <div class="dialog-actions">
                <button class="btn-sub" type="button" onclick="closePasswordReset()">Cancel</button>
                <button class="btn-main" type="submit">Change password</button>
            </div>
            <p id="password-reset-message" role="status"></p>
        </form>
    </dialog>
    <script src="script.js"></script>
</body>
</html>
<?php
$submissionStmt->close();
$reportResult->free();
$statsResult->free();
$userResult->free();
$conn->close();
?>
