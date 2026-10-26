<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AGRI-SED | Portal</title>
    <link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">
</head>
<body>
    <div id="loginSection" class="auth-container">
        <div class="auth-box">
            <div class="logo-pill">🌱 <b>AGRI SED</b></div>
            <p class="auth-eyebrow">AGRICULTURE DATA PORTAL</p>
            <h1 id="authTitle">Farmer Portal</h1>
            <p class="auth-description">Sign in to manage submissions and explore regional benchmarks.</p>
            <div class="role-selector" role="tablist" aria-label="Choose account type">
                <button id="userBtn" class="role-btn active" type="button" role="tab" aria-selected="true" onclick="setRole('user')"><span class="btn-text">FARMER</span><span class="btn-sub">USER ACCOUNT</span></button>
                <button id="adminBtn" class="role-btn" type="button" role="tab" aria-selected="false" onclick="setRole('admin')"><span class="btn-text">ADMIN</span><span class="btn-sub">AGRI HEAD</span></button>
            </div>
            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" id="username" placeholder="Your username" minlength="3" maxlength="30" pattern="[\p{L} ]{3,30}" autocomplete="username" required title="Use 3–30 letters and spaces only.">
                <label for="workerId" id="workerIdLabel" class="hidden">Worker ID</label>
                <input type="text" id="workerId" class="hidden" placeholder="00-0000-000000" autocomplete="off">
                <label for="password">Password</label>
                <input type="password" id="password" placeholder="Your password" minlength="8" autocomplete="current-password" required pattern="(?=(?:[^A-Za-z]*[A-Za-z]){6})(?=.*[0-9]).{8,}" title="At least 8 characters, including 6 letters and 1 number.">
                <label for="confirmPassword" id="confirmPasswordLabel">Confirm password</label>
                <input type="password" id="confirmPassword" placeholder="Re-enter your password" autocomplete="new-password">
            </div>
            <div class="auth-footer">
                <button class="btn-signup" type="button" onclick="handleSignup()">Create farmer account</button>
                <button class="btn-login" type="button" onclick="handleLogin()">Log In</button>
            </div>
        </div>
    </div>
    <script src="script.js?v=<?= filemtime(__DIR__ . '/script.js') ?>"></script>
</body>
</html>
