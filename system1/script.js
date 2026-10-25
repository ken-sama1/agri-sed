let currentRole = 'user';

window.onload = function() {
    console.log("Jarvis: System Online.");
    setRole('user'); 
};

function setRole(role) {
    currentRole = role;
    const adminBtn = document.getElementById('adminBtn');
    const userBtn = document.getElementById('userBtn');
    const workerIdField = document.getElementById('workerId');
    const authTitle = document.getElementById('authTitle');

    if (adminBtn && userBtn) {
        if (role === 'admin') {
            adminBtn.classList.add('active-admin');
            userBtn.classList.remove('active');
            if(workerIdField) workerIdField.classList.remove('hidden');
            if(authTitle) authTitle.innerText = "Agri Head Registration";
        } else {
            userBtn.classList.add('active');
            adminBtn.classList.remove('active-admin');
            if(workerIdField) workerIdField.classList.add('hidden');
            if(authTitle) authTitle.innerText = "Farmer Registration";
        }
    }
}

function validatePassword(pass) { return /^(?=.*[0-9]).{8,}$/.test(pass); }
function validateWorkerId(id) { return /^\d{2}-\d{4}-\d{6}$/.test(id); }

async function handleSignup() {
    const user = document.getElementById('username').value;
    const pass = document.getElementById('password').value;
    const confirmPass = document.getElementById('confirmPassword').value;
    const workerId = document.getElementById('workerId') ? document.getElementById('workerId').value : "";

    if (!user || !pass) { alert("Fill all fields."); return; }
    if (pass !== confirmPass) { alert("Passwords mismatch!"); return; }
    if (!validatePassword(pass)) { alert("Weak password! 8+ chars & 1 number."); return; }
    if (currentRole === 'admin' && !validateWorkerId(workerId)) {
        alert("Invalid Worker ID! Format: 00-0000-000000");
        return;
    }

    const formData = new FormData();
    formData.append('username', user);
    formData.append('password', pass);
    formData.append('role', currentRole);
    formData.append('worker_id', workerId);

    try {
        const response = await fetch('signup.php', { method: 'POST', body: formData });
        const result = await response.text();
        alert(result.trim() === "Success" ? "Account Created!" : "Error: " + result);
    } catch (error) { alert("Connection error: " + error); }
}

async function handleLogin() {
    const user = document.getElementById('username').value;
    const pass = document.getElementById('password').value;

    const formData = new FormData();
    formData.append('username', user);
    formData.append('password', pass);
    formData.append('role', currentRole);

    try {
        const response = await fetch('login.php', { method: 'POST', body: formData });
        const result = await response.text();

        if (result.trim() === "Success") {
            document.getElementById('loginSection').classList.add('hidden');
            document.getElementById('mainPage').classList.remove('hidden');
            if (currentRole === 'user') {
                window.location.href = 'user_home.php'; // Redirect to separated file
            } else {
                window.location.href = 'admin_home.php'; // Redirect to separated file
            }
        } else {
            alert(result); 
        }
    } catch (error) {
        alert("Connection error: " + error);
    }
}

function showSection(id) {
    document.querySelectorAll('.section').forEach(s => s.classList.add('hidden'));
    const target = document.getElementById(id);
    if (target) target.classList.remove('hidden');
}

function toggleDropdown(e) {
    e.stopPropagation();
    const menu = document.getElementById("dropdown-menu");
    if(menu) menu.classList.toggle('show');
}

document.addEventListener('click', () => {
    const menu = document.getElementById("dropdown-menu");
    if (menu) menu.classList.remove("show");
});

// UNIT CONVERSIONS
function convertWeight() {
    const weightInput = document.getElementById('weight');
    const unit = document.getElementById('weightUnit').value;
    let val = parseFloat(weightInput.value);
    if(!val) return;
    // Logic handled by PHP on submit, JS just manages visuals
}

function convertLand() {
    const landInput = document.getElementById('landSize');
    const unit = document.getElementById('landUnit').value;
    let val = parseFloat(landInput.value);
    if(!val) return;
}

async function submitFarmerData() {
    const formData = new FormData();
    formData.append('location', document.getElementById('location').value);
    formData.append('crop_type', document.getElementById('crop_type').value);
    formData.append('sacks', document.getElementById('sacks').value);
    formData.append('weight', document.getElementById('weight').value);
    formData.append('weightUnit', document.getElementById('weightUnit').value);
    formData.append('landSize', document.getElementById('landSize').value);
    formData.append('landUnit', document.getElementById('landUnit').value);
    formData.append('profit', document.getElementById('profit').value);
    formData.append('file_land', document.getElementById('file_land').files[0]);
    formData.append('file_crop', document.getElementById('file_crop').files[0]);
    formData.append('file_payslip', document.getElementById('file_payslip').files[0]);

    try {
        const response = await fetch('submit_data.php', { method: 'POST', body: formData });
        const result = await response.text();
        alert(result.trim() === "Success" ? "Data Submitted Successfully!" : "Error: " + result);
    } catch (error) { alert("Connection error: " + error); }
}

// CHARTS INITIALIZATION
function initCharts() {
    const ctxBar = document.getElementById('userBarChart');
    const ctxPie = document.getElementById('userPieChart');
    if(!ctxBar || !ctxPie) return;

    new Chart(ctxBar, {
        type: 'bar',
        data: { labels: ['Munoz', 'Cabanatuan', 'San Jose'], datasets: [{ label: 'Profit', data: [12, 19, 3], backgroundColor: '#b5ff00' }] },
        options: { scales: { y: { beginAtZero: true } } }
    });

    new Chart(ctxPie, {
        type: 'pie',
        data: { labels: ['Rice', 'Onion', 'Sugar'], datasets: [{ data: [300, 50, 100], backgroundColor: ['#b5ff00', '#ff3131', '#333'] }] }
    });
}
// Call initCharts() inside showSection('benchmark')
