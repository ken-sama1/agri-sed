<?php include 'db.php'; if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') { header("Location: index.php"); exit(); } ?>
<?php
$historyStmt = $conn->prepare(
    "SELECT s.id, s.location, s.crop_type, s.sacks, s.weight, s.weight_unit,
            s.land_size, s.land_unit, s.monthly_profit, s.status, s.is_paused,
            s.submitted_at, s.reviewed_at,
            a.status AS appeal_status, a.message AS appeal_message,
            a.submitted_at AS appeal_submitted_at, a.reviewed_at AS appeal_reviewed_at,
            a.admin_comment AS appeal_admin_comment,
            a.comment_updated_at AS appeal_comment_updated_at
     FROM farmer_submissions s
     LEFT JOIN (
         SELECT appeals.*
         FROM farmer_submission_appeals appeals
         INNER JOIN (
             SELECT submission_id, MAX(id) AS latest_id
             FROM farmer_submission_appeals
             GROUP BY submission_id
         ) latest ON latest.latest_id = appeals.id
     ) a ON a.submission_id = s.id
     WHERE s.user_id = ?
     ORDER BY s.submitted_at DESC"
);
if (!$historyStmt) {
    die("Unable to load submission history.");
}
$historyStmt->bind_param("i", $_SESSION['user_id']);
if (!$historyStmt->execute()) {
    die("Unable to load submission history.");
}
$historyResult = $historyStmt->get_result();

$reportStmt = $conn->prepare(
    "SELECT category, message, status, submitted_at, resolved_at,
            attachment_path, admin_comment, comment_updated_at
     FROM user_reports
     WHERE user_id = ?
     ORDER BY submitted_at DESC"
);
if (!$reportStmt) {
    die("Unable to load your reports.");
}
$reportStmt->bind_param("i", $_SESSION['user_id']);
if (!$reportStmt->execute()) {
    die("Unable to load your reports.");
}
$reportResult = $reportStmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>Farmer Hub | AGRI-SED</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <nav class="navbar">
        <div class="logo">🌱 <b>AGRI SED</b></div>
        <div class="nav-menu">
            <button onclick="showSection('home')">HOME</button>
            <div class="dropdown">
                <button onclick="toggleDropdown(event)">SUB CONTENT ▾</button>
                <div class="dropdown-content" id="dropdown-menu">
                    <a onclick="showSection('input')">INPUT DATA</a>
                    <a onclick="showSection('history')">MY SUBMISSIONS</a>
                    <a onclick="showSection('report')">REPORT AN ISSUE</a>
                    <a onclick="location.href='logout.php'">LOG OUT</a>
                </div>
            </div>
        </div>
    </nav>

    <div id="mainContent">
        <section id="home" class="section">
            <div class="hero">
                <div class="hero-text">
                    <h1 class="glitch-text">AGRI-SED <br><span class="highlight">Data Hub</span></h1>
                    <p>Empowering growers through precise data collection.</p>
                    <div class="hero-btns">
                        <button class="btn-main" onclick="showSection('input')">Start Data Entry</button>
                        <button class="btn-sub" onclick="showSection('benchmark')">VIEW DATA BENCHMARK</button>
                    </div>
                </div>
                <div class="feature-box">
                    <div class="feature"><span class="icon">📊</span><h3>Data Entry</h3><p>Submit land and profit.</p></div>
                    <div class="feature"><span class="icon">🔍</span><h3>Benchmark</h3><p>Compare regional trends.</p></div>
                    <div class="feature"><span class="icon">✅</span><h3>Verification</h3><p>Get approved by Head.</p></div>
                    <button type="button" class="feature feature-button" onclick="showSection('report')"><span class="icon">🎫</span><h3>Report an Issue</h3><p>Report a bug, UX problem, or other feedback.</p></button>
                </div>
            </div>
        </section>

        <section id="input" class="section hidden">
            <h1 class="title">START DATA ENTRY</h1>
            <div class="centered-form">
                <div class="crop-card">
                    <label>Location</label>
                    <select name="location" id="location" class="input-field"><option value="Munoz">Munoz</option><option value="Cabanatuan">Cabanatuan</option><option value="San Jose">San Jose</option></select>
                    <label>Crop Type</label>
            <select name="crop_type" id="crop_type"  class="input-field">
              <option value="Rice">Rice</option><option value="Onion">Onion</option><option value="Sugar">Sugar</option></select>
                    <label>Sacks</label><input type="number" id="sacks" name="sacks">
                    <label>Total Weight</label>
            <div class="unit-row">
              <input type="number" name="weight" id="weight">
              <select id="weightUnit" name="weight"  onchange="convertWeight()"><option value="KG">KG</option><option value="LB">LB</option></select></div>
                    <label>Land Size</label>
                    <div class="unit-row"><input type="number" name="landSize" id="landSize"><select id="landUnit" name="landUnit" onchange="convertLand()"><option value="Hectares">Hectares</option><option value="Acres">Acres</option><option value="SqMeters">SqMeters</option><option value="SqFeet">SqFeet</option></select></div>
                    <label>Earned Profit (₱)</label><input type="number" id="profit" name="profit">
                    <label>Upload Land Title</label><input type="file" id="file_land" name="file_land">
                    <label>Upload Crop Photo</label><input type="file" id="file_crop" name="file_crop">
                    <label>Upload Payslip</label><input type="file" id="file_payslip" name="file_payslip">
                    <button class="btn-main" onclick="submitFarmerData()">SUBMIT DATA</button>
                </div>
            </div>
        </section>

        <section id="report" class="section hidden">
            <h1 class="title">REPORT A BUG OR GIVE FEEDBACK</h1>
            <div class="centered-form">
                <div class="crop-card">
                    <label for="reportCategory">Report type</label>
                    <select id="reportCategory" class="input-field">
                        <option value="bug">Bug</option>
                        <option value="ux">UX / Design</option>
                        <option value="other">Other feedback</option>
                    </select>
                    <label for="reportMessage">What happened or could be improved?</label>
                    <textarea id="reportMessage" rows="6" maxlength="2000" placeholder="Describe the issue or feedback (up to 2000 characters)."></textarea>
                    <label for="reportAttachment">Attach an image (optional, up to 5 MB)</label>
                    <input type="file" id="reportAttachment" accept="image/jpeg,image/png,image/gif,image/webp">
                    <button class="btn-main" type="button" onclick="submitUserReport()">SUBMIT REPORT</button>
                </div>
            </div>
            <h2 class="title">MY REPORTS</h2>
            <div class="history-container">
                <?php if ($reportResult->num_rows === 0): ?>
                    <p class="history-empty">You have not submitted any reports.</p>
                <?php else: ?>
                    <?php while ($report = $reportResult->fetch_assoc()): ?>
                        <article class="history-card">
                            <h2><?= htmlspecialchars(ucfirst($report['category']), ENT_QUOTES, 'UTF-8') ?> report</h2>
                            <dl class="history-details">
                                <div><dt>Submitted</dt><dd><?= htmlspecialchars(date('M j, Y g:i A', strtotime($report['submitted_at'])), ENT_QUOTES, 'UTF-8') ?></dd></div>
                                <div><dt>Status</dt><dd class="status-<?= htmlspecialchars($report['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(ucfirst($report['status']), ENT_QUOTES, 'UTF-8') ?></dd></div>
                                <?php if ($report['resolved_at']): ?>
                                    <div><dt>Resolved</dt><dd><?= htmlspecialchars(date('M j, Y g:i A', strtotime($report['resolved_at'])), ENT_QUOTES, 'UTF-8') ?></dd></div>
                                <?php endif; ?>
                                <div class="appeal-message"><dt>Report</dt><dd><?= nl2br(htmlspecialchars($report['message'], ENT_QUOTES, 'UTF-8')) ?></dd></div>
                                <?php if ($report['admin_comment']): ?>
                                    <div class="appeal-message"><dt>Admin comment</dt><dd><?= nl2br(htmlspecialchars($report['admin_comment'], ENT_QUOTES, 'UTF-8')) ?></dd></div>
                                    <div><dt>Comment updated</dt><dd><?= htmlspecialchars(date('M j, Y g:i A', strtotime($report['comment_updated_at'])), ENT_QUOTES, 'UTF-8') ?></dd></div>
                                <?php endif; ?>
                            </dl>
                            <?php if ($report['attachment_path']): ?>
                                <a class="report-attachment" href="<?= htmlspecialchars($report['attachment_path'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener"><img src="<?= htmlspecialchars($report['attachment_path'], ENT_QUOTES, 'UTF-8') ?>" alt="Attached report image"></a>
                            <?php endif; ?>
                        </article>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </section>

        <section id="history" class="section hidden">
            <h1 class="title">MY SUBMISSIONS</h1>
            <div id="historyList" class="history-container">
                <?php if ($historyResult->num_rows === 0): ?>
                    <p class="history-empty">No submissions yet.</p>
                <?php else: ?>
                    <?php while ($submission = $historyResult->fetch_assoc()): ?>
                        <article class="history-card">
                            <h2><?= htmlspecialchars($submission['crop_type'], ENT_QUOTES, 'UTF-8') ?> submission</h2>
                            <dl class="history-details">
                                <div><dt>Submitted</dt><dd><?= $submission['submitted_at'] ? htmlspecialchars(date('M j, Y g:i A', strtotime($submission['submitted_at'])), ENT_QUOTES, 'UTF-8') : 'Not available' ?></dd></div>
                                <div><dt>Status</dt><dd class="status-<?= (int) $submission['is_paused'] === 1 ? 'paused' : htmlspecialchars($submission['status'] ?? 'pending', ENT_QUOTES, 'UTF-8') ?>"><?= (int) $submission['is_paused'] === 1 ? 'Approved (paused)' : htmlspecialchars(ucfirst($submission['status'] ?? 'pending'), ENT_QUOTES, 'UTF-8') ?></dd></div>
                                <div><dt>Reviewed</dt><dd><?= $submission['reviewed_at'] ? htmlspecialchars(date('M j, Y g:i A', strtotime($submission['reviewed_at'])), ENT_QUOTES, 'UTF-8') : 'Not reviewed' ?></dd></div>
                                <?php if ($submission['appeal_status']): ?>
                                    <div><dt>Appeal status</dt><dd class="status-<?= htmlspecialchars($submission['appeal_status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(ucfirst($submission['appeal_status']), ENT_QUOTES, 'UTF-8') ?></dd></div>
                                    <div><dt>Appeal submitted</dt><dd><?= htmlspecialchars(date('M j, Y g:i A', strtotime($submission['appeal_submitted_at'])), ENT_QUOTES, 'UTF-8') ?></dd></div>
                                    <?php if ($submission['appeal_reviewed_at']): ?>
                                        <div><dt>Appeal reviewed</dt><dd><?= htmlspecialchars(date('M j, Y g:i A', strtotime($submission['appeal_reviewed_at'])), ENT_QUOTES, 'UTF-8') ?></dd></div>
                                    <?php endif; ?>
                                    <div class="appeal-message"><dt>Your appeal</dt><dd><?= nl2br(htmlspecialchars($submission['appeal_message'], ENT_QUOTES, 'UTF-8')) ?></dd></div>
                                    <?php if ($submission['appeal_admin_comment']): ?>
                                        <div class="appeal-message"><dt>Admin comment</dt><dd><?= nl2br(htmlspecialchars($submission['appeal_admin_comment'], ENT_QUOTES, 'UTF-8')) ?></dd></div>
                                        <div><dt>Comment updated</dt><dd><?= htmlspecialchars(date('M j, Y g:i A', strtotime($submission['appeal_comment_updated_at'])), ENT_QUOTES, 'UTF-8') ?></dd></div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <div><dt>Location</dt><dd><?= htmlspecialchars($submission['location'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                                <div><dt>Crop type</dt><dd><?= htmlspecialchars($submission['crop_type'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                                <div><dt>No. of sacks</dt><dd><?= htmlspecialchars((string) $submission['sacks'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                                <div><dt>Total weight</dt><dd><?= htmlspecialchars((string) $submission['weight'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($submission['weight_unit'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                                <div><dt>Land size</dt><dd><?= htmlspecialchars((string) $submission['land_size'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($submission['land_unit'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                                <div><dt>Profit</dt><dd>₱<?= number_format((float) $submission['monthly_profit'], 2) ?></dd></div>
                            </dl>
                            <?php if (($submission['status'] ?? 'pending') === 'rejected' && $submission['appeal_status'] !== 'pending'): ?>
                                <div class="appeal-form">
                                    <label for="appeal-<?= (int) $submission['id'] ?>">Appeal this rejection</label>
                                    <textarea id="appeal-<?= (int) $submission['id'] ?>" rows="4" maxlength="1000" placeholder="Explain why you believe this submission should be reviewed again."></textarea>
                                    <button class="btn-appeal" type="button" onclick="submitAppeal(<?= (int) $submission['id'] ?>)">Submit appeal</button>
                                </div>
                            <?php endif; ?>
                            <?php if (($submission['status'] ?? 'pending') === 'pending'): ?>
                                <a class="btn-edit-submission" href="edit_submission.php?id=<?= (int) $submission['id'] ?>">Edit pending submission</a>
                            <?php endif; ?>
                        </article>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </section>

        <section id="benchmark" class="section hidden">
            <h1 class="title">DATA BENCHMARK</h1>
            <div class="benchmark-grid">
                <div class="chart-box">
                    <h2 class="chart-title">Total earnings by area</h2>
                    <canvas id="userBarChart"></canvas>
                </div>
                <div class="chart-box">
                    <h2 class="chart-title">Crop totals by area</h2>
                    <div class="chart-controls">
                        <label for="userBenchmarkCrop">Crop</label>
                        <select id="userBenchmarkCrop" class="input-field">
                            <option value="Rice">Rice</option>
                            <option value="Onion">Onion</option>
                            <option value="Sugar">Sugar</option>
                        </select>
                        <label for="userBenchmarkMetric">Total</label>
                        <select id="userBenchmarkMetric" class="input-field">
                            <option value="profit">Profit (₱)</option>
                            <option value="sacks">Sacks</option>
                            <option value="weight_kg">Weight (kg)</option>
                        </select>
                    </div>
                    <canvas id="userPieChart"></canvas>
                </div>
            </div>
            <p id="benchmarkMessage" class="history-empty" role="status"></p>
        </section>
    </div>
    <script src="script.js"></script>
</body>
</html>
<?php
$historyStmt->close();
$reportStmt->close();
$conn->close();
?>
