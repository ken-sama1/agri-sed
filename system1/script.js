let currentRole = "user";

window.onload = function () {
  console.log("Jarvis: System Online.");
  setRole("user");
  const section = new URLSearchParams(window.location.search).get("section");
  if (section) showSection(section);
};

function setRole(role) {
  const roleChanged = role !== currentRole;
  currentRole = role;
  const adminBtn = document.getElementById("adminBtn");
  const userBtn = document.getElementById("userBtn");
  const workerIdField = document.getElementById("workerId");
  const workerIdLabel = document.getElementById("workerIdLabel");
  const confirmPasswordField = document.getElementById("confirmPassword");
  const confirmPasswordLabel = document.getElementById("confirmPasswordLabel");
  const authTitle = document.getElementById("authTitle");
  const signupButton = document.querySelector(".btn-signup");
  const formFields = document.querySelectorAll(
    "#username, #workerId, #password, #confirmPassword",
  );

  if (roleChanged) {
    formFields.forEach((field) => {
      field.value = "";
      field.setCustomValidity("");
    });
  }

  if (adminBtn && userBtn) {
    if (role === "admin") {
      adminBtn.classList.add("active-admin");
      userBtn.classList.remove("active");
      adminBtn.setAttribute("aria-selected", "true");
      userBtn.setAttribute("aria-selected", "false");
      if (workerIdField) workerIdField.classList.remove("hidden");
      if (workerIdLabel) workerIdLabel.classList.remove("hidden");
      if (confirmPasswordField) confirmPasswordField.classList.add("hidden");
      if (confirmPasswordLabel) confirmPasswordLabel.classList.add("hidden");
      if (signupButton) signupButton.classList.add("hidden");
      if (authTitle) authTitle.innerText = "Admin Portal";
    } else {
      userBtn.classList.add("active");
      adminBtn.classList.remove("active-admin");
      userBtn.setAttribute("aria-selected", "true");
      adminBtn.setAttribute("aria-selected", "false");
      if (workerIdField) workerIdField.classList.add("hidden");
      if (workerIdLabel) workerIdLabel.classList.add("hidden");
      if (confirmPasswordField) confirmPasswordField.classList.remove("hidden");
      if (confirmPasswordLabel) confirmPasswordLabel.classList.remove("hidden");
      if (signupButton) signupButton.classList.remove("hidden");
      if (authTitle) authTitle.innerText = "Farmer Portal";
    }
  }
}

function validatePassword(pass) {
  return /^(?=(?:[^A-Za-z]*[A-Za-z]){6})(?=.*[0-9]).{8,}$/.test(pass);
}
function validateUsername(username) {
  return /^[\p{L} ]{3,30}$/u.test(username.trim());
}
function validateWorkerId(id) {
  return /^\d{2}-\d{4}-\d{6}$/.test(id);
}

document.addEventListener("keydown", (event) => {
  if (
    event.target instanceof HTMLInputElement &&
    event.target.type === "number" &&
    ["e", "E"].includes(event.key)
  ) {
    event.preventDefault();
  }
});

document.addEventListener("beforeinput", (event) => {
  if (
    event.target instanceof HTMLInputElement &&
    event.target.type === "number" &&
    /[eE]/.test(event.data || "")
  ) {
    event.preventDefault();
  }
});

document.addEventListener("paste", (event) => {
  if (
    event.target instanceof HTMLInputElement &&
    event.target.type === "number" &&
    /[eE]/.test(event.clipboardData?.getData("text") || "")
  ) {
    event.preventDefault();
  }
});
function showSubmissionToast(message, type) {
  let toast = document.getElementById("submission-toast");
  if (!toast) {
    toast = document.createElement("div");
    toast.id = "submission-toast";
    toast.className = "submission-toast";
    toast.setAttribute("aria-live", "polite");
    document.body.append(toast);
  }

  toast.textContent = message;
  toast.className = `submission-toast is-${type}`;
  toast.setAttribute("role", type === "success" ? "status" : "alert");
  window.clearTimeout(showSubmissionToast.timeoutId);
  showSubmissionToast.timeoutId = window.setTimeout(() => toast.remove(), 5000);
}

function openPasswordReset(userId, username) {
  const dialog = document.getElementById("password-reset-dialog");
  document.getElementById("password-reset-form").reset();
  document.getElementById("password-reset-user-id").value = String(userId);
  document.getElementById("password-reset-username").textContent =
    `Set a new password for ${username}.`;
  document.getElementById("password-reset-message").textContent = "";
  dialog.showModal();
}

function closePasswordReset() {
  document.getElementById("password-reset-dialog").close();
}

async function resetUserPassword(event) {
  event.preventDefault();
  const form = document.getElementById("password-reset-form");
  if (!form.reportValidity()) return;
  const password = document.getElementById("new-user-password").value;
  const confirmation = document.getElementById("confirm-user-password").value;
  if (password !== confirmation) {
    document.getElementById("password-reset-message").textContent =
      "Passwords do not match.";
    return;
  }

  const button = form.querySelector('button[type="submit"]');
  const formData = new FormData();
  formData.append(
    "user_id",
    document.getElementById("password-reset-user-id").value,
  );
  formData.append("password", password);
  button.disabled = true;
  try {
    const response = await fetch("reset_user_password.php", {
      method: "POST",
      body: formData,
    });
    const result = await response.json();
    if (!response.ok) {
      throw new Error(result.error || "Unable to reset user password.");
    }
    closePasswordReset();
    showSubmissionToast("User password updated.", "success");
  } catch (error) {
    document.getElementById("password-reset-message").textContent =
      error.message;
  } finally {
    button.disabled = false;
  }
}

async function handleSignup() {
  const user = document.getElementById("username").value;
  const pass = document.getElementById("password").value;
  const confirmPass = document.getElementById("confirmPassword").value;
  const workerId = document.getElementById("workerId")
    ? document.getElementById("workerId").value
    : "";

  if (!user || !pass) {
    alert("Fill all fields.");
    return;
  }
  if (!validateUsername(user)) {
    alert("Username must be 3–30 characters and contain only letters and spaces.");
    return;
  }
  if (pass !== confirmPass) {
    alert("Passwords mismatch!");
    return;
  }
  if (!validatePassword(pass)) {
    alert(
      "Password must be at least 8 characters, contain 6 letters, and include a number.",
    );
    return;
  }
  if (currentRole === "admin" && !validateWorkerId(workerId)) {
    alert("Invalid Worker ID! Format: 00-0000-000000");
    return;
  }

  const formData = new FormData();
  formData.append("username", user);
  formData.append("password", pass);
  formData.append("role", currentRole);
  formData.append("worker_id", workerId);

  try {
    const response = await fetch("signup.php", {
      method: "POST",
      body: formData,
    });
    const result = await response.text();
    alert(
      result.trim() === "Success" ? "Account Created!" : "Error: " + result,
    );
  } catch (error) {
    alert("Connection error: " + error);
  }
}

async function handleLogin() {
  const user = document.getElementById("username").value.trim();
  const pass = document.getElementById("password").value;

  if (!validateUsername(user)) {
    alert("Username must be 3–30 characters and contain only letters and spaces.");
    return;
  }

  const formData = new FormData();
  formData.append("username", user);
  formData.append("password", pass);
  formData.append("role", currentRole);
  formData.append("workerId", document.getElementById("workerId").value);

  try {
    const response = await fetch("login.php", {
      method: "POST",
      body: formData,
    });
    const result = await response.text();

    if (result.trim() === "Success") {
      document.getElementById("loginSection")?.classList.add("hidden");
      document.getElementById("mainPage")?.classList.remove("hidden");
      if (currentRole === "user") {
        window.location.href = "user_home.php"; // Redirect to separated file
      } else {
        window.location.href = "admin_home.php"; // Redirect to separated file
      }
    } else {
      alert(result);
    }
  } catch (error) {
    alert("Connection error" + error);
  }
}

function showSection(id) {
  document
    .querySelectorAll(".section")
    .forEach((s) => s.classList.add("hidden"));
  const target = document.getElementById(id);
  if (target) target.classList.remove("hidden");
  if (id === "benchmark") loadBenchmarkCharts();
}

function toggleDropdown(e) {
  e.stopPropagation();
  const menu = document.getElementById("dropdown-menu");
  if (menu) menu.classList.toggle("show");
}

document.addEventListener("click", () => {
  const menu = document.getElementById("dropdown-menu");
  if (menu) menu.classList.remove("show");
});

// UNIT CONVERSIONS
function convertWeight() {
  const weightInput = document.getElementById("weight");
  const unit = document.getElementById("weightUnit").value;
  let val = parseFloat(weightInput.value);
  if (!val) return;
  // Logic handled by PHP on submit, JS just manages visuals
}

function convertLand() {
  const landInput = document.getElementById("landSize");
  const unit = document.getElementById("landUnit").value;
  let val = parseFloat(landInput.value);
  if (!val) return;
}

async function submitFarmerData(event) {
  event.preventDefault();
  const form = document.getElementById("farmer-submission-form");
  const allowedTypes = [
    "image/jpeg",
    "image/png",
    "image/gif",
    "image/webp",
    "application/pdf",
  ];
  const requiredFiles = ["file_land", "file_crop", "file_payslip"].map((id) =>
    document.getElementById(id).files[0],
  );
  if (
    requiredFiles.some((file) => !file || !allowedTypes.includes(file.type))
  ) {
    showSubmissionToast(
      "Choose an image or PDF for the land picture, crop photo, and payslip.",
      "error",
    );
    return;
  }
  const submitButton = form.querySelector('button[type="submit"]');

  const formData = new FormData();
  formData.append("location", document.getElementById("location").value);
  formData.append("crop_type", document.getElementById("crop_type").value);
  formData.append("sacks", document.getElementById("sacks").value);
  formData.append("weight", document.getElementById("weight").value);
  formData.append("weightUnit", document.getElementById("weightUnit").value);
  formData.append("landSize", document.getElementById("landSize").value);
  formData.append("landUnit", document.getElementById("landUnit").value);
  formData.append("profit", document.getElementById("profit").value);
  formData.append("file_land", document.getElementById("file_land").files[0]);
  formData.append("file_crop", document.getElementById("file_crop").files[0]);
  formData.append(
    "file_payslip",
    document.getElementById("file_payslip").files[0],
  );

  submitButton.disabled = true;
  submitButton.classList.add("is-loading");
  submitButton.textContent = "Uploading…";
  try {
    const response = await fetch("submit_data.php", {
      method: "POST",
      body: formData,
    });
    const result = await response.text();
    if (response.ok && result.trim() === "Success") {
      form.reset();
      showSubmissionToast("Submission uploaded successfully.", "success");
    } else {
      showSubmissionToast(result || "Unable to submit data.", "error");
    }
  } catch (error) {
    showSubmissionToast(`Connection error: ${error.message}`, "error");
  } finally {
    submitButton.disabled = false;
    submitButton.classList.remove("is-loading");
    submitButton.textContent = "SUBMIT DATA";
  }
}

async function updateSubmission(event, submissionId) {
  event.preventDefault();
  const form = document.getElementById("edit-submission-form");
  if (!form || !form.reportValidity()) return;

  const formData = new FormData();
  formData.append("submission_id", String(submissionId));
  formData.append("location", form.elements.location.value);
  formData.append("crop_type", form.elements.crop_type.value);
  formData.append("sacks", form.elements.sacks.value);
  formData.append("weight", form.elements.weight.value);
  formData.append("weight_unit", form.elements.weight_unit.value);
  formData.append("land_size", form.elements.land_size.value);
  formData.append("land_unit", form.elements.land_unit.value);
  formData.append("profit", form.elements.profit.value);

  const button = form.querySelector('button[type="submit"]');
  if (button) button.disabled = true;
  try {
    const response = await fetch("update_submission.php", {
      method: "POST",
      body: formData,
    });
    const result = await response.json();
    if (!response.ok) {
      throw new Error(result.error || "Unable to update submission.");
    }
    form.reset();
    window.location.href = "user_home.php?section=history";
  } catch (error) {
    alert(`Error: ${error.message}`);
    if (button) button.disabled = false;
  }
}

async function reviewSubmission(id, status) {
  if (status === "rejected" && !window.confirm("Reject this submission?"))
    return;
  const card = document.getElementById(`submission-${id}`);
  const reviewActions = card
    ?.querySelector(".submission-actions .btn-approve")
    ?.closest(".submission-actions");
  const buttons = reviewActions?.querySelectorAll("button");
  if (!card || !buttons || !["approved", "rejected"].includes(status)) return;
  buttons.forEach((button) => (button.disabled = true));

  const formData = new FormData();
  formData.append("submission_id", String(id));
  formData.append("status", status);

  try {
    const response = await fetch("review_submission.php", {
      method: "POST",
      body: formData,
    });
    const result = await response.json();
    if (!response.ok || !result.reviewed_at) {
      throw new Error(result.error || "Unable to update submission.");
    }

    const statusElement = card.querySelector("[data-submission-status]");
    if (statusElement) {
      statusElement.textContent =
        status === "approved" ? "Approved" : "Rejected";
      statusElement.className = `status-${status}`;
    }
    const reviewedAtElement = card.querySelector("[data-reviewed-at]");
    if (reviewedAtElement) {
      reviewedAtElement.textContent = result.reviewed_at_display;
    }
    reviewActions.remove();
    const pendingCount = document.getElementById("statPending");
    const approvedCount = document.getElementById("statApproved");
    if (pendingCount) {
      pendingCount.textContent = String(
        Math.max(0, Number(pendingCount.textContent) - 1),
      );
    }
    if (status === "approved" && approvedCount) {
      approvedCount.textContent = String(Number(approvedCount.textContent) + 1);
    }
    window.benchmarkData = null;
    if (!document.getElementById("benchmark")?.classList.contains("hidden")) {
      loadBenchmarkCharts();
    }
  } catch (error) {
    alert(`Error: ${error.message}`);
    buttons.forEach((button) => (button.disabled = false));
  }
}

async function manageSubmission(id, action) {
  if (!["pause", "resume", "delete"].includes(action)) return;
  const prompts = {
    pause:
      "Pause this approved submission? It will be excluded from benchmarks.",
    resume: "Resume this submission in benchmarks?",
    delete:
      "Permanently delete this submission and its appeal history? This cannot be undone.",
  };
  if (!window.confirm(prompts[action])) return;

  const formData = new FormData();
  formData.append("submission_id", String(id));
  formData.append("action", action);
  try {
    const response = await fetch("manage_submission.php", {
      method: "POST",
      body: formData,
    });
    const result = await response.json();
    if (!response.ok) {
      throw new Error(result.error || "Unable to update submission.");
    }
    if (result.warning) alert(result.warning);
    window.location.href = "admin_home.php?section=manage";
  } catch (error) {
    alert(`Error: ${error.message}`);
  }
}

async function submitAppeal(submissionId) {
  const messageField = document.getElementById(`appeal-${submissionId}`);
  const message = messageField?.value.trim();
  if (!message) {
    alert("Please explain why you are appealing this rejection.");
    return;
  }

  const formData = new FormData();
  formData.append("submission_id", String(submissionId));
  formData.append("message", message);

  try {
    const response = await fetch("submit_appeal.php", {
      method: "POST",
      body: formData,
    });
    const result = await response.json();
    if (!response.ok) {
      throw new Error(result.error || "Unable to submit appeal.");
    }
    messageField.value = "";
    alert("Appeal submitted for admin review.");
    window.location.reload();
  } catch (error) {
    alert(`Error: ${error.message}`);
  }
}

async function cancelSubmission(submissionId) {
  if (
    !window.confirm(
      "Cancel this pending submission? It will remain in your history as cancelled.",
    )
  ) {
    return;
  }

  const formData = new FormData();
  formData.append("submission_id", String(submissionId));
  try {
    const response = await fetch("cancel_submission.php", {
      method: "POST",
      body: formData,
    });
    const result = await response.json();
    if (!response.ok) {
      throw new Error(result.error || "Unable to cancel submission.");
    }
    window.location.href = "user_home.php?section=history";
  } catch (error) {
    showSubmissionToast(error.message, "error");
  }
}

async function resolveAppeal(appealId, decision) {
  if (!["accepted", "denied"].includes(decision)) return;
  const formData = new FormData();
  formData.append("appeal_id", String(appealId));
  formData.append("decision", decision);

  try {
    const response = await fetch("resolve_appeal.php", {
      method: "POST",
      body: formData,
    });
    const result = await response.json();
    if (!response.ok) {
      throw new Error(result.error || "Unable to resolve appeal.");
    }
    window.location.reload();
  } catch (error) {
    alert(`Error: ${error.message}`);
  }
}

async function submitUserReport() {
  const message = document.getElementById("reportMessage")?.value.trim();
  const category = document.getElementById("reportCategory")?.value;
  const attachment = document.getElementById("reportAttachment")?.files[0];
  if (!message) {
    alert("Please describe the bug or feedback.");
    return;
  }

  const formData = new FormData();
  formData.append("category", category);
  formData.append("message", message);
  if (attachment) formData.append("attachment", attachment);
  try {
    const response = await fetch("submit_report.php", {
      method: "POST",
      body: formData,
    });
    const result = await response.json();
    if (!response.ok)
      throw new Error(result.error || "Unable to submit report.");
    document.getElementById("reportMessage").value = "";
    document.getElementById("reportAttachment").value = "";
    document.getElementById("reportCategory").selectedIndex = 0;
    alert("Your report was submitted.");
    window.location.reload();
  } catch (error) {
    alert(`Error: ${error.message}`);
  }
}

async function saveAdminComment(type, id) {
  if (!["appeal", "report"].includes(type)) return;
  const message = document
    .getElementById(`${type}-comment-${id}`)
    ?.value.trim();
  if (!message) {
    alert("Enter a comment before saving.");
    return;
  }

  const formData = new FormData();
  formData.append("type", type);
  formData.append("id", String(id));
  formData.append("comment", message);
  try {
    const response = await fetch("admin_comment.php", {
      method: "POST",
      body: formData,
    });
    const result = await response.json();
    if (!response.ok)
      throw new Error(result.error || "Unable to save comment.");
    window.location.reload();
  } catch (error) {
    alert(`Error: ${error.message}`);
  }
}

async function resolveUserReport(reportId) {
  const formData = new FormData();
  formData.append("report_id", String(reportId));
  try {
    const response = await fetch("resolve_report.php", {
      method: "POST",
      body: formData,
    });
    const result = await response.json();
    if (!response.ok)
      throw new Error(result.error || "Unable to resolve report.");
    window.location.reload();
  } catch (error) {
    alert(`Error: ${error.message}`);
  }
}

function renderBenchmarkPie(data) {
  const pieCanvas =
    document.getElementById("userPieChart") ||
    document.getElementById("adminPieChart");
  const cropSelect = document.querySelector(
    "#userBenchmarkCrop, #adminBenchmarkCrop",
  );
  const metricSelect = document.querySelector(
    "#userBenchmarkMetric, #adminBenchmarkMetric",
  );
  const title = pieCanvas?.parentElement.querySelector(".chart-title");
  if (!pieCanvas || !cropSelect || !metricSelect || !title) return;

  const crop = cropSelect.value;
  const metric = metricSelect.value;
  const metricLabels = {
    profit: "Profit (₱)",
    sacks: "Sacks",
    weight_kg: "Weight (kg)",
  };
  const values = data.locations.map(
    (location) => data.totals[location][crop][metric],
  );
  const palette = getComputedStyle(document.documentElement);

  title.textContent = `${crop} ${metricLabels[metric].toLowerCase()} by area`;
  if (window.benchmarkCharts?.pie) window.benchmarkCharts.pie.destroy();
  window.benchmarkCharts = window.benchmarkCharts || {};
  window.benchmarkCharts.pie = new Chart(pieCanvas, {
    type: "pie",
    data: {
      labels: data.locations,
      datasets: [
        {
          label: `${crop} ${metricLabels[metric]}`,
          data: values,
          backgroundColor: [
            palette.getPropertyValue("--primary").trim(),
            palette.getPropertyValue("--onion").trim(),
            palette.getPropertyValue("--sugarcane").trim(),
          ],
          borderColor: "#111",
          borderWidth: 4,
        },
      ],
    },
    options: {
      responsive: true,
      plugins: { legend: { position: "bottom" } },
    },
  });
}

async function loadBenchmarkCharts() {
  const barCanvas =
    document.getElementById("userBarChart") ||
    document.getElementById("adminBarChart");
  const pieCanvas =
    document.getElementById("userPieChart") ||
    document.getElementById("adminPieChart");
  const message = document.getElementById("benchmarkMessage");
  if (!barCanvas || !pieCanvas || !message) return;
  if (typeof Chart === "undefined") {
    message.textContent =
      "Charts could not be loaded. Please refresh and try again.";
    return;
  }

  if (window.benchmarkData) {
    renderBenchmarkPie(window.benchmarkData);
    return;
  }

  message.textContent = "Loading approved submission benchmarks...";
  try {
    const response = await fetch("benchmark_data.php");
    const data = await response.json();
    if (!response.ok)
      throw new Error(data.error || "Unable to load benchmarks.");

    window.benchmarkData = data;
    const cropSelect = document.querySelector(
      "#userBenchmarkCrop, #adminBenchmarkCrop",
    );
    const metricSelect = document.querySelector(
      "#userBenchmarkMetric, #adminBenchmarkMetric",
    );
    cropSelect?.addEventListener("change", () =>
      renderBenchmarkPie(window.benchmarkData),
    );
    metricSelect?.addEventListener("change", () =>
      renderBenchmarkPie(window.benchmarkData),
    );

    const crops = data.crops;
    const palette = getComputedStyle(document.documentElement);
    const cropColors = {
      Rice: palette.getPropertyValue("--primary").trim(),
      Onion: palette.getPropertyValue("--onion").trim(),
      Sugarcane: palette.getPropertyValue("--sugarcane").trim(),
    };
    window.benchmarkCharts = window.benchmarkCharts || {};
    window.benchmarkCharts.bar = new Chart(barCanvas, {
      type: "bar",
      data: {
        labels: data.locations,
        datasets: crops.map((crop) => ({
          label: crop,
          data: data.locations.map(
            (location) => data.totals[location][crop].profit,
          ),
          backgroundColor: cropColors[crop],
        })),
      },
      options: {
        responsive: true,
        scales: {
          x: { stacked: true },
          y: {
            stacked: true,
            beginAtZero: true,
            title: { display: true, text: "Profit (₱)" },
          },
        },
        plugins: { legend: { position: "bottom" } },
      },
    });
    renderBenchmarkPie(data);
    message.textContent = data.locations.every((location) =>
      data.crops.every((crop) =>
        ["profit", "sacks", "weight_kg"].every(
          (metric) => data.totals[location][crop][metric] === 0,
        ),
      ),
    )
      ? "No approved submissions are available to benchmark yet."
      : "";
  } catch (error) {
    message.textContent = error.message;
  }
}
