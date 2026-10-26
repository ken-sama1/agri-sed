<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>AGRI-SED | Portal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div id="loginSection" class="auth-container">
        <div class="logo-pill">🌱 <b>AGRI SED</b></div>
        <div class="role-selector">
            <button id="adminBtn" class="role-btn" onclick="setRole('admin')"><span class="btn-text">ADMIN</span><span class="btn-sub">AGRI HEAD</span></button>
            <button id="userBtn" class="role-btn active" onclick="setRole('user')"><span class="btn-text">USER</span><span class="btn-sub">FARMER</span></button>
        </div>
        <div class="auth-box">
            <h2 id="authTitle">Farmer Registration</h2>
            <div class="input-group">
                <input type="text" id="username" placeholder="USERNAME" minlength="3" maxlength="30" pattern="[\p{L} ]{3,30}" title="Use 3–30 letters and spaces only.">
                <input type="text" id="workerId" class="hidden" placeholder="WORKER ID (00-0000-000000)">
                <input type="password" id="password" placeholder="PASSWORD" minlength="8" pattern="(?=(?:[^A-Za-z]*[A-Za-z]){6})(?=.*[0-9]).{8,}" title="At least 8 characters, including 6 letters and 1 number.">
                <input type="password" id="confirmPassword" placeholder="CONFIRM PASSWORD">
            </div>
            <div class="auth-footer">
                <button class="btn-signup" onclick="handleSignup()">Sign Up</button>
                <button class="btn-login" onclick="handleLogin()">Log In</button>
            </div>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>
