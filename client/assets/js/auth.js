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

    const loginForm = document.querySelector(".login-card form, .card form");
    const emailInput = loginForm ? loginForm.querySelector('input[type="email"]') : null;
    const passwordInputs = document.querySelectorAll('input[type="password"]');

    // 1. Dynamic Eye Toggle: Works for ANY password field on the page
    document.querySelectorAll(".toggle-pw").forEach(function(toggleBtn) {
        toggleBtn.addEventListener("click", function () {
            const field = this.closest(".field");
            const input = field.querySelector("input");
            const icons = this.querySelectorAll("svg");
            
            const isHidden = input.type === "password";
            input.type = isHidden ? "text" : "password";
            
            if (icons[0]) icons[0].style.display = isHidden ? "none" : "block";
            if (icons[1]) icons[1].style.display = isHidden ? "block" : "none";
            
            this.setAttribute("aria-label", isHidden ? "Hide password" : "Show password");
        });
    });

    // 2. Dynamic Validation: Applies color logic to ALL password fields
    function validateAnyPassword(input, showError) {
        if (!input) return false;
        if (input.value.trim() === "") {
            if (showError) setFieldError(input, "Password is required.");
            else clearFieldState(input);
            return false;
        }
        setFieldValid(input);
        return true;
    }

    if (emailInput) {
        emailInput.addEventListener("input", function() { hideMessage(); validateEmailField(true); });
        emailInput.addEventListener("blur", function() { validateEmailField(true); });
    }

    passwordInputs.forEach(function(input) {
        input.addEventListener("input", function() { hideMessage(); validateAnyPassword(input, true); });
        input.addEventListener("blur", function() { validateAnyPassword(input, true); });
    });

    if (loginForm) {
        loginForm.setAttribute("novalidate", "novalidate");

        loginForm.addEventListener("submit", function(event) {
            let hasError = false;

            // 1. Only validate the email field IF it exists on the page
            if (emailInput && !validateEmailField(true)) {
                hasError = true;
            }

            // 2. Validate EVERY password field found on the page
            if (typeof passwordInputs !== 'undefined') {
                passwordInputs.forEach(function(input) {
                    if (!validateAnyPassword(input, true)) {
                        hasError = true;
                    }
                });
            }

            // 3. Block submission if any errors were found
            if (hasError) {
                event.preventDefault();
                showMessage("Please correct the highlighted fields.", "error");

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
