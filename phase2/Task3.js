// ========== Task 3 – Weekly Plan (Phase 2 – JavaScript) ==========
// ⚠ Do not change file name or function names

// Logout placeholder (later will connect to PHP)
function logout() {
  alert("Logging out...");
  window.location.href = "Login Page.html"; 
}

// Comment box validation
function submitComment() {
  const textarea = document.querySelector("textarea");

  if (!textarea.value.trim()) {
    alert("Comment cannot be empty!");
    return;
  }

  alert("Comment submitted successfully!");
  textarea.value = "";
}

// Role label placeholder (static for now until PHP connects to DB)
const roleDisplay = document.getElementById("roleDisplay");
if (roleDisplay) {
  roleDisplay.textContent = "Role: Student"; // ثابت مؤقتًا
}
