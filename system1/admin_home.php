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
                            <dl class="history-details">
                                <div><dt>Submitted</dt><dd><?= $submission['submitted_at'] ? htmlspecialchars(date('M j, Y g:i A', strtotime($submission['submitted_at'])), ENT_QUOTES, 'UTF-8') : 'Not available' ?></dd></div>
                                <div><dt>Status</dt><dd class="status-<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>" data-submission-status><?= htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8') ?></dd></div>
                                <div><dt>Reviewed</dt><dd data-reviewed-at><?= $submission['reviewed_at'] ? htmlspecialchars(date('M j, Y g:i A', strtotime($submission['reviewed_at'])), ENT_QUOTES, 'UTF-8') : 'Not reviewed' ?></dd></div>
                                <?php if ($status === 'approved'): ?>
                                    <div><dt>Benchmark</dt><dd class="<?= (int) $submission['is_paused'] === 1 ? 'status-paused' : 'status-approved' ?>"><?= (int) $submission['is_paused'] === 1 ? 'Paused' : 'Included' ?></dd></div>
                                <?php endif; ?>
                                <?php if ($submission['appeal_status']): ?>
                                    <div><dt>Appeal status</dt><dd class="status-<?= htmlspecialchars($submission['appeal_status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(ucfirst($submission['appeal_status']), ENT_QUOTES, 'UTF-8') ?></dd></div>
                                    <div><dt>Appeal submitted</dt><dd><?= htmlspecialchars(date('M j, Y g:i A', strtotime($submission['appeal_submitted_at'])), ENT_QUOTES, 'UTF-8') ?></dd></div>
                                    <?php if ($submission['appeal_reviewed_at']): ?>
                                        <div><dt>Appeal reviewed</dt><dd><?= htmlspecialchars(date('M j, Y g:i A', strtotime($submission['appeal_reviewed_at'])), ENT_QUOTES, 'UTF-8') ?></dd></div>
                                    <?php endif; ?>
                                    <div class="appeal-message"><dt>Appeal message</dt><dd><?= nl2br(htmlspecialchars($submission['appeal_message'], ENT_QUOTES, 'UTF-8')) ?></dd></div>
                                    <?php if ($submission['appeal_admin_comment']): ?>
                                        <div class="appeal-message"><dt>Admin comment</dt><dd><?= nl2br(htmlspecialchars($submission['appeal_admin_comment'], ENT_QUOTES, 'UTF-8')) ?></dd></div>
                                        <div><dt>Comment updated</dt><dd><?= htmlspecialchars(date('M j, Y g:i A', strtotime($submission['appeal_comment_updated_at'])), ENT_QUOTES, 'UTF-8') ?></dd></div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <div><dt>Location</dt><dd><?= htmlspecialchars($submission['location'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                                <div><dt>Crop type</dt><dd><?= htmlspecialchars($submission['crop_type'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                                <div><dt>No. of sacks</dt><dd><?= (int) $submission['sacks'] ?></dd></div>
                                <div><dt>Total weight</dt><dd><?= htmlspecialchars((string) $submission['weight'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($submission['weight_unit'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                                <div><dt>Land size</dt><dd><?= htmlspecialchars((string) $submission['land_size'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($submission['land_unit'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                                <div><dt>Profit</dt><dd>₱<?= number_format((float) $submission['monthly_profit'], 2) ?></dd></div>
                            </dl>
                            <div class="submission-files">
                                <?php foreach (['file_land' => 'Land title', 'file_crop' => 'Crop photo', 'file_payslip' => 'Payslip'] as $fileField => $fileLabel): ?>
                                    <?php if ($submission[$fileField]): ?>
                                        <a href="<?= htmlspecialchars($submission[$fileField], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener"><?= htmlspecialchars($fileLabel, ENT_QUOTES, 'UTF-8') ?></a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($status === 'pending'): ?>
                                <div class="submission-actions">
                                    <button class="btn-approve" onclick="reviewSubmission(<?= (int) $submission['id'] ?>, 'approved')">Accept</button>
                                    <button class="btn-reject" onclick="reviewSubmission(<?= (int) $submission['id'] ?>, 'rejected')">Reject</button>
                                </div>
                            <?php endif; ?>
                            <?php if ($status === 'approved'): ?>
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
                                    <button class="btn-approve" onclick="resolveAppeal(<?= (int) $submission['appeal_id'] ?>, 'accepted')">Accept appeal</button>
                                    <button class="btn-reject" onclick="resolveAppeal(<?= (int) $submission['appeal_id'] ?>, 'denied')">Deny appeal</button>
                                </div>
                            <?php endif; ?>
                            <?php if ($submission['appeal_status']): ?>
                                <div class="appeal-form">
                                    <label for="appeal-comment-<?= (int) $submission['appeal_id'] ?>">Admin comment on appeal</label>
                                    <textarea id="appeal-comment-<?= (int) $submission['appeal_id'] ?>" rows="3" maxlength="2000"><?= htmlspecialchars($submission['appeal_admin_comment'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                                    <button type="button" onclick="saveAdminComment('appeal', <?= (int) $submission['appeal_id'] ?>)">Save appeal comment</button>
                                </div>
                            <?php endif; ?>
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
                        </article>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>
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
