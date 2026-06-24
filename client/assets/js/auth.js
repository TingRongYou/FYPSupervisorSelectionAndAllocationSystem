/* ==========================================================================
   AUTHENTICATION SCRIPTS (login.php)
   ========================================================================== */
document.addEventListener("DOMContentLoaded", function() {
    // 1. Handle URL message parameters
    const params = new URLSearchParams(window.location.search);
    const message = params.get("message");
    const type = params.get("type") || params.get("status") || "error";
    const box = document.getElementById("loginMessage");

    if (message && box) {
        box.textContent = decodeURIComponent(message);
        box.classList.add("show", type);
    }

    if (window.history.replaceState && (params.has("message") || params.has("type") || params.has("status"))) {
        window.history.replaceState({}, "", window.location.pathname);
    }

    // 2. Password toggle
    const toggleBtn = document.getElementById("togglePw");
    const pwInput = document.getElementById("passwordInput");
    const eyeIcon = document.getElementById("eyeIcon");
    const eyeOffIcon = document.getElementById("eyeOffIcon");

    if (toggleBtn && pwInput) {
        toggleBtn.addEventListener("click", function () {
            const isHidden = pwInput.type === "password";
            pwInput.type = isHidden ? "text" : "password";
            if (eyeIcon) eyeIcon.style.display = isHidden ? "none" : "block";
            if (eyeOffIcon) eyeOffIcon.style.display = isHidden ? "block" : "none";
            toggleBtn.setAttribute("aria-label", isHidden ? "Hide password" : "Show password");
        });
    }
});