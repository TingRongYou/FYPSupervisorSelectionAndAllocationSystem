/* ==========================================================================
   SHARED UTILITIES (profile.php)
   ========================================================================== */
document.addEventListener("DOMContentLoaded", function() {
    const photoInput = document.getElementById("profilePhotoInput");
    const photoPreview = document.getElementById("profilePhotoPreview");
    const photoName = document.getElementById("profilePhotoName");

    if (photoInput) {
        photoInput.addEventListener("change", function() {
            const file = photoInput.files[0];
            if (!file) {
                photoName.textContent = "No file selected";
                return;
            }
            if (!["image/jpeg", "image/png"].includes(file.type)) {
                alert("Only JPG or PNG profile photos are allowed.");
                photoInput.value = "";
                photoName.textContent = "No file selected";
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                alert("Profile photo cannot exceed 5MB.");
                photoInput.value = "";
                photoName.textContent = "No file selected";
                return;
            }
            photoName.textContent = file.name;
            photoPreview.innerHTML = '<img src="' + URL.createObjectURL(file) + '" alt="Profile photo preview">';
        });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const photoInput = document.getElementById('profilePhotoInput');
    const photoForm = document.getElementById('avatarUploadForm');
    
    if (photoInput && photoForm) {
        photoInput.addEventListener('change', function() {
            const file = this.files[0];
            
            if (file) {
                // Frontend Validation
                if (!['image/jpeg', 'image/png'].includes(file.type)) {
                    alert("Please select a valid JPG or PNG image.");
                    this.value = "";
                    return;
                }
                // 2MB (2097152 bytes)
                if (file.size > 2097152) { 
                    alert("File is too large. Maximum size is 2.0MB.");
                    this.value = "";
                    return;
                }
                
                // If valid, submit the form automatically
                photoForm.submit();
            }
        });
    }
});

/*
|--------------------------------------------------------------------------
| Portal Sidebar Subnav Toggle
|--------------------------------------------------------------------------
| Handles the opening and closing of dropdown menus in the shared sidebar.
*/
function togglePortalSubnav(button) {
    const targetId = button.getAttribute('data-subnav-target');
    if (!targetId) return;

    const subnav = document.getElementById(targetId);
    if (!subnav) return;

    const isClosed = subnav.style.display === "none" || subnav.style.display === "";
    
    // Toggle display
    subnav.style.display = isClosed ? "block" : "none";
    
    // Toggle accessibility and chevron animation
    button.setAttribute('aria-expanded', isClosed ? "true" : "false");
    
    const chevron = button.querySelector('.portal-nav-chevron');
    if (chevron) {
        chevron.style.transform = isClosed ? "rotate(0deg)" : "rotate(-180deg)";
    }
}

/* ==========================================================================
   PASSWORD MANAGEMENT (setPassword.php)
   ========================================================================== */
document.addEventListener("DOMContentLoaded", function() {
    const pwForm = document.getElementById("pwForm");
    if (!pwForm) return;

    // Toggle Visibility
    document.querySelectorAll(".eye-btn").forEach(btn => {
        btn.addEventListener("click", () => {
            const input = document.getElementById(btn.dataset.target);
            const isHidden = input.type === "password";
            input.type = isHidden ? "text" : "password";
            btn.querySelectorAll("svg").forEach(svg => svg.style.display = svg.style.display === "none" ? "block" : "none");
        });
    });

    // Strength Meter Logic
    const newPwInput = document.getElementById("newPassword");
    const confirmPwInput = document.getElementById("confirmPassword");
    const strengthFill = document.getElementById("strengthFill");
    const strengthLabel = document.getElementById("strengthLabel");

    const levels = [
        ["Too short", "#e33434", "10%"],
        ["Weak", "#e33434", "25%"],
        ["Fair", "#f59e0b", "50%"],
        ["Good", "#3b82f6", "75%"],
        ["Strong", "#22c55e", "100%"]
    ];

    function evaluatePassword() {
        const pw = newPwInput.value;
        const confirm = confirmPwInput.value;
        
        const hasLength = pw.length >= 8;
        const hasAlpha = /[a-zA-Z]/.test(pw);
        const hasNum = /[0-9]/.test(pw);
        const hasSpecial = /[^a-zA-Z0-9]/.test(pw);
        
        document.getElementById("req-length").classList.toggle("met", hasLength);
        document.getElementById("req-alpha").classList.toggle("met", hasAlpha);
        document.getElementById("req-num").classList.toggle("met", hasNum);
        document.getElementById("req-special").classList.toggle("met", hasSpecial);
        document.getElementById("req-match").classList.toggle("met", pw !== "" && pw === confirm);

        const score = [hasLength, hasAlpha, hasNum, hasSpecial].filter(Boolean).length;
        if (pw.length === 0) {
            strengthFill.style.width = "0%";
            strengthFill.style.background = "#e4eaf0";
            strengthLabel.textContent = "Password Strength";
            return;
        }
        const [label, color, width] = levels[score];
        strengthFill.style.width = width;
        strengthFill.style.background = color;
        strengthLabel.textContent = label;
        strengthLabel.style.color = color;
    }

    [newPwInput, confirmPwInput].forEach(el => el.addEventListener("input", evaluatePassword));

    pwForm.addEventListener("submit", function(e) {
        const pw = newPwInput.value;
        const confirm = confirmPwInput.value;
        if (pw.length < 8 || !/[a-zA-Z]/.test(pw) || !/[0-9]/.test(pw) || !/[^a-zA-Z0-9]/.test(pw) || pw !== confirm) {
            e.preventDefault();
            alert("Please check your password meets all requirements and matches.");
        }
    });

    document.getElementById("resetBtn").addEventListener("click", () => setTimeout(evaluatePassword, 10));
});