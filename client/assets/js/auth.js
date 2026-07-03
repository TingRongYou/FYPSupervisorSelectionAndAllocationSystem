/* ==========================================================================
   AUTHENTICATION SCRIPTS (login.php)
   ========================================================================== */
document.addEventListener("DOMContentLoaded", function() {
    const params = new URLSearchParams(window.location.search);
    const message = params.get("message");
    const type = params.get("type") || params.get("status") || "error";
    const box = document.getElementById("loginMessage");

    function showMessage(text, messageType) {
        if (!box) return;

        box.textContent = text;
        box.className = "message show " + (messageType || "error");
        box.setAttribute("role", messageType === "success" ? "status" : "alert");
    }

    function hideMessage() {
        if (!box) return;

        box.textContent = "";
        box.className = "message";
        box.removeAttribute("role");
    }

    function fieldWrapper(input) {
        return input ? input.closest(".field") : null;
    }

    function setFieldError(input, text) {
        const field = fieldWrapper(input);
        if (!field || !input) return;

        field.classList.remove("valid");
        field.classList.add("invalid");
        input.setAttribute("aria-invalid", "true");

        let error = field.querySelector(".field-error");
        if (!error) {
            error = document.createElement("span");
            error.className = "field-error";
            field.appendChild(error);
        }

        error.textContent = text;
    }

    function clearFieldError(input) {
        const field = fieldWrapper(input);
        if (!field || !input) return;

        field.classList.remove("invalid");
        input.removeAttribute("aria-invalid");

        const error = field.querySelector(".field-error");
        if (error) {
            error.textContent = "";
        }
    }

    function setFieldValid(input) {
        const field = fieldWrapper(input);
        if (!field || !input) return;

        clearFieldError(input);
        field.classList.add("valid");
    }

    function clearFieldState(input) {
        const field = fieldWrapper(input);
        if (!field || !input) return;

        clearFieldError(input);
        field.classList.remove("valid");
    }

    function validateEmailInput(input, showError) {
        const email = input ? input.value.trim() : "";

        if (email === "") {
            if (showError) setFieldError(input, "University email is required.");
            else clearFieldState(input);
            return false;
        }

        if (input && !input.validity.valid) {
            if (showError) setFieldError(input, "Please enter a valid university email.");
            else clearFieldState(input);
            return false;
        }

        setFieldValid(input);
        return true;
    }

    function validateEmailField(showError) {
        return validateEmailInput(emailInput, showError);
    }

    function bindEmailValidationForm(form) {
        if (!form) return;

        const input = form.querySelector('input[type="email"], input[name="email"]');
        if (!input) return;

        form.setAttribute("novalidate", "novalidate");

        input.addEventListener("input", function() {
            validateEmailInput(input, true);
        });

        input.addEventListener("blur", function() {
            validateEmailInput(input, true);
        });

        form.addEventListener("submit", function(event) {
            if (!validateEmailInput(input, true)) {
                event.preventDefault();
                input.focus();
            }
        });
    }

    function validatePasswordField(showError) {
        const password = pwInput ? pwInput.value.trim() : "";

        if (password === "") {
            if (showError) setFieldError(pwInput, "Password is required.");
            else clearFieldState(pwInput);
            return false;
        }

        setFieldValid(pwInput);
        return true;
    }

    if (message && box) {
        showMessage(message, type === "success" ? "success" : "error");
    }

    if (window.history.replaceState && (params.has("message") || params.has("type") || params.has("status"))) {
        window.history.replaceState({}, "", window.location.pathname);
    }

    const toggleBtn = document.getElementById("togglePw");
    const pwInput = document.getElementById("passwordInput");
    const eyeIcon = document.getElementById("eyeIcon");
    const eyeOffIcon = document.getElementById("eyeOffIcon");
    const loginForm = document.querySelector(".login-card form");
    const emailInput = loginForm ? loginForm.querySelector('input[name="email"]') : null;

    if (toggleBtn && pwInput) {
        toggleBtn.addEventListener("click", function () {
            const isHidden = pwInput.type === "password";
            pwInput.type = isHidden ? "text" : "password";
            if (eyeIcon) eyeIcon.style.display = isHidden ? "none" : "block";
            if (eyeOffIcon) eyeOffIcon.style.display = isHidden ? "block" : "none";
            toggleBtn.setAttribute("aria-label", isHidden ? "Hide password" : "Show password");
        });
    }

    [emailInput, pwInput].forEach(function(input) {
        if (!input) return;

        input.addEventListener("input", function() {
            hideMessage();
            if (input === emailInput) {
                validateEmailField(true);
            } else {
                validatePasswordField(true);
            }
        });

        input.addEventListener("blur", function() {
            if (input === emailInput) {
                validateEmailField(true);
            } else {
                validatePasswordField(true);
            }
        });
    });

    if (loginForm) {
        loginForm.setAttribute("novalidate", "novalidate");

        loginForm.addEventListener("submit", function(event) {
            const hasError =
                !validateEmailField(true) ||
                !validatePasswordField(true);

            if (hasError) {
                event.preventDefault();
                showMessage("Please correct the highlighted login fields.", "error");

                const firstInvalid = loginForm.querySelector("[aria-invalid='true']");
                if (firstInvalid) {
                    firstInvalid.focus();
                }
            }
        });
    }

    document
        .querySelectorAll("form[data-email-validation]")
        .forEach(bindEmailValidationForm);
});
