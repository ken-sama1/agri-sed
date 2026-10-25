<?php include 'db.php'; if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') { header("Location: index.php"); exit(); } ?>
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
                    <div class="feature"><span class="icon">🎫</span><h3>Support</h3><p>Report via Tickets.</p></div>
                </div>
            </div>
        </section>

        <section id="input" class="section hidden">
            <h1 class="title">START DATA ENTRY</h1>
            <div class="centered-form">
                <div class="crop-card">
                    <label>Location</label>
                    <select id="location" class="input-field"><option value="Munoz">Munoz</option><option value="Cabanatuan">Cabanatuan</option><option value="San Jose">San Jose</option></select>
                    <label>Crop Type</label>
                    <select id="crop_type" class="input-field"><option value="Rice">Rice</option><option value="Onion">Onion</option><option value="Sugar">Sugar</option></select>
                    <label>Sacks</label><input type="number" id="sacks">
                    <label>Total Weight</label>
                    <div class="unit-row"><input type="number" id="weight"><select id="weightUnit" onchange="convertWeight()"><option value="KG">KG</option><option value="LB">LB</option></select></div>
                    <label>Land Size</label>
                    <div class="unit-row"><input type="number" id="landSize"><select id="landUnit" onchange="convertLand()"><option value="Hectares">Hectares</option><option value="Acres">Acres</option><option value="SqMeters">SqMeters</option><option value="SqFeet">SqFeet</option></select></div>
                    <label>Earned Profit (₱)</label><input type="number" id="profit">
                    <label>Upload Land Title</label><input type="file" id="file_land">
                    <label>Upload Crop Photo</label><input type="file" id="file_crop">
                    <label>Upload Payslip</label><input type="file" id="file_payslip">
                    <button class="btn-main" onclick="submitFarmerData()">SUBMIT DATA</button>
                </div>
            </div>
        </section>

        <section id="history" class="section hidden">
            <h1 class="title">MY SUBMISSIONS</h1>
            <div id="historyList" class="history-container"></div>
        </section>

        <section id="benchmark" class="section hidden">
            <h1 class="title">DATA BENCHMARK</h1>
            <div class="benchmark-grid">
                <div class="chart-box"><canvas id="userBarChart"></canvas></div>
                <div class="chart-box"><canvas id="userPieChart"></canvas></div>
            </div>
        </section>
    </div>
    <script src="script.js"></script>
</body>
</html>
