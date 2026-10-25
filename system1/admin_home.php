<?php include 'db.php'; if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header("Location: index.php"); exit(); } ?>
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
                <div class="stat-card"><span class="stat-icon">👥</span><h3 id="statFarmers">0</h3><p>Total Farmers</p></div>
                <div class="stat-card highlight-card"><span class="stat-icon">⏳</span><h3 id="statPending">0</h3><p>Pending Approvals</p></div>
                <div class="stat-card"><span class="stat-icon">✅</span><h3 id="statApproved">0</h3><p>Approved Data</p></div>
            </div>
            <div class="admin-actions">
                <button class="btn-main" onclick="showSection('benchmark')">DATA BENCHMARK</button>
            </div>
        </section>

        <section id="benchmark" class="section hidden">
            <h1 class="title">REGIONAL BENCHMARK</h1>
            <div class="benchmark-grid">
                <div class="chart-box"><canvas id="adminBarChart"></canvas></div>
                <div class="chart-box"><canvas id="adminPieChart"></canvas></div>
            </div>
        </section>

        <section id="manage" class="section hidden">
            <h1 class="title">MANAGE SUBMISSIONS</h1>
            <div id="submissionList" class="history-container"></div>
        </section>

        <section id="directory" class="section hidden">
            <h1 class="title">USER DIRECTORY</h1>
            <div id="userList" class="history-container"></div>
        </section>
    </div>
    <script src="script.js"></script>
</body>
</html>
