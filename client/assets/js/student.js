/*
|--------------------------------------------------------------------------
| SSAS STUDENT MODULE SCRIPTS
|--------------------------------------------------------------------------
| This file contains all client-side JavaScript for the Student portal.
*/

/* ==========================================================================
   1. DASHBOARD TIMELINE COUNTDOWN
   ========================================================================== */
document.addEventListener("DOMContentLoaded", function() {
    const timer = document.getElementById("phaseTimer");
    
    // Safety check: Exit if we are not on the dashboard page
    if (!timer) return;

    const config = window.ssasDashboardConfig || { phaseEnd: "", serverEpoch: Date.now() / 1000 };
    const phaseEnd = config.phaseEnd;
    const serverOffset = (Number(config.serverEpoch) * 1000) - Date.now(); // JavaScript calculates in milliseconds while PHP in seconds, hence * 1000

    function updateTimer() {
        if (!phaseEnd) { 
            timer.textContent = "--:--:--"; 
            return; 
        }
        
        const remaining = new Date(phaseEnd.replace(" ", "T")).getTime() - (Date.now() + serverOffset);
        
        if (remaining <= 0) { 
            timer.textContent = "00d: 00h: 00m: 00s"; 
            return; 
        }
        
        // Calculate total days and modulo the hours
        const d = Math.floor(remaining / 86400000); // 1000 * 60 * 60 * 24 = 86400000 ms
        const h = Math.floor((remaining % 86400000) / 3600000); // 1000 * 60 * 60 = 3600000 ms
        const m = Math.floor((remaining % 3600000) / 60000); // 1000 * 60 = 60000 ms
        const s = Math.floor((remaining % 60000) / 1000); // 1000 ms = 1 s
        
        // Format with leading zeros
        timer.textContent =
            String(d).padStart(2, "0") + "d: " + 
            String(h).padStart(2, "0") + "h: " + 
            String(m).padStart(2, "0") + "m: " +
            String(s).padStart(2, "0") + "s";
    }

    updateTimer();
    setInterval(updateTimer, 1000); // Run the function every one second
});


// Wait for the DOM to fully load
document.addEventListener("DOMContentLoaded", () => {
    
    const toggleBtn = document.getElementById("toggleRecommendations");
    const contentArea = document.getElementById("recommendationsContent");

    if (toggleBtn && contentArea) {
        toggleBtn.addEventListener("click", () => {
            // Toggle the closed classes on both the content and the button
            contentArea.classList.toggle("is-closed");
            toggleBtn.classList.toggle("is-closed");
        });
    }

});

// Availability filter
document.addEventListener("DOMContentLoaded", function() {
    const availabilityInput = document.getElementById("availabilityInput");
    const tabs = document.querySelectorAll(".availability-tab");

    if (!availabilityInput || tabs.length === 0) return;

    tabs.forEach(tab => {
        tab.addEventListener("click", function() {
            // 1. Update the hidden input value
            availabilityInput.value = this.dataset.value; // Set value to the specific tab that was clicked

            // 2. Remove 'active' class from all tabs
            tabs.forEach(t => t.classList.remove("active")); // Ensure only one tab looked highlighted at a time

            // 3. Add 'active' class to the clicked tab
            this.classList.add("active");
        });
    });
});

/* ==========================================================================
   2. STUDENT PROFILE MANAGEMENT
   ========================================================================== */
document.addEventListener("DOMContentLoaded", function() {
    const profileForm = document.getElementById("studentProfileForm");
    
    // Safety check: Exit if we are not on the Profile page
    if (!profileForm) return;

    const avatarFile = document.getElementById("avatarFile");
    const avatarPreview = document.getElementById("avatarPreview");
    const avatarFileName = document.getElementById("avatarFileName");
    const bio = document.getElementById("personalBio");
    const bioCounter = document.getElementById("bioCounter");
    const tagCounter = document.getElementById("tagCounter");
    const selectedTags = document.getElementById("selectedTags");
    const tagInputs = Array.from(document.querySelectorAll('input[name="interestTags[]"]'));
    const maxAvatarBytes = 2 * 1024 * 1024; // 2.0MB
    const editBtn = document.getElementById("editProfileBtn");
    const cancelBtn = document.getElementById("cancelEditBtn");
    const saveBtn = document.getElementById("saveProfileBtn");
    const formElements = profileForm.querySelectorAll('input:not([type="hidden"]), textarea');

    // Handle Edit Button Click
    if (editBtn) {
        editBtn.addEventListener("click", function() {
            profileForm.classList.remove("view-mode"); 
            formElements.forEach(el => {
                el.disabled = false;
                // Set the placeholder when editing starts (only if value is empty)
                if (!el.value && el.dataset.placeholder) {
                    el.placeholder = el.dataset.placeholder;
                }
            });
            
            editBtn.style.display = "none";
            if (cancelBtn) cancelBtn.style.display = "inline-flex";
            if (saveBtn) saveBtn.style.display = "inline-flex";
        });
    }

    function updateBioCounter() {
        if (bio && bioCounter) {
            bioCounter.textContent = bio.value.length + " / 500";
        }
    }

    function updateTags() {
        if (!tagCounter || !selectedTags) return;
        
        const selected = tagInputs.filter(input => input.checked);

        tagCounter.textContent = selected.length + " / 5 active";
        selectedTags.innerHTML = "";

        selected.forEach(function(input) {
            const tag = document.createElement("span");
            tag.className = "selected-tag";
            tag.textContent = input.dataset.name;
            selectedTags.appendChild(tag);
        });

        if (selected.length === 0) {
            const empty = document.createElement("span");
            empty.className = "selected-tag";
            empty.textContent = "No interests selected";
            selectedTags.appendChild(empty);
        }

        tagInputs.forEach(function(input) {
            input.closest(".tag-option").classList.toggle("selected", input.checked);
        });
    }

    tagInputs.forEach(function(input) {
        input.addEventListener("change", function() {
            const selected = tagInputs.filter(item => item.checked);

            if (selected.length > 5) {
                input.checked = false;
                alert("You can select a minimum of 1 and a maximum of 5 research interests . Please remove one to add another.");
            }

            updateTags();
        });
    });

    if (avatarFile) {
        avatarFile.addEventListener("change", function() {
            const file = avatarFile.files[0];

            if (!file) {
                avatarFileName.textContent = "JPG or PNG, max 2.0MB.";
                return;
            }

            if (!["image/jpeg", "image/png"].includes(file.type) || file.size > maxAvatarBytes) {
                alert("Upload failed. Please ensure your profile picture is in JPG or PNG format and does not exceed 2.0MB.");
                avatarFile.value = "";
                avatarFileName.textContent = "JPG or PNG, max 2.0MB.";
                return;
            }

            avatarFileName.textContent = file.name;
            avatarPreview.innerHTML = '<img src="' + URL.createObjectURL(file) + '" alt="Avatar preview">';
        });
    }

    profileForm.addEventListener("submit", function(event) {
        const selected = tagInputs.filter(input => input.checked);
        const contactInput = document.getElementById("contactNumber");

        // 1. Mobile Number Validation Check
        if (contactInput && contactInput.value.trim() !== "") {
            const phonePattern = /^0\d{2}-\d{3}\s\d{4}$/;
            if (!phonePattern.test(contactInput.value)) {
                event.preventDefault();
                alert("Validation Error - Mobile number must follow the pattern: 0xx-xxx xxxx (e.g., 012-345 6789).");
                contactInput.focus();
                return;
            }
        }

        // 2. Personal Bio Length Check
        if (bio && bio.value.length > 500) {
            event.preventDefault();
            alert("Validation Error - Personal Bio cannot exceed 500 characters.");
            return;
        }

        // 3. Research Interests Check
        if (selected.length === 0 || selected.length > 5) {
            event.preventDefault();
            alert("You can select a minimum of 1 and a maximum of 5 research interests. Please remove one to add another.");
            return;
        }
    });

    profileForm.addEventListener("reset", function() {
        setTimeout(function() {
            updateBioCounter();
            updateTags();
            if (avatarFileName) avatarFileName.textContent = "JPG or PNG, max 2.0MB.";
            
            if (editBtn && cancelBtn && saveBtn) {
                profileForm.classList.add("view-mode");
                formElements.forEach(el => {
                    el.disabled = true;
                    // Clear out the placeholder when locked so it stays blank
                    el.placeholder = "";
                });
                
                editBtn.style.display = "inline-flex";
                cancelBtn.style.display = "none";
                saveBtn.style.display = "none";
            }
        }, 0);
    });

    updateBioCounter();
    updateTags();
    if (bio) {
        bio.addEventListener("input", updateBioCounter);
    }
});

/* ==========================================================================
   3. APPLICATION STATUS COUNTDOWN
   ========================================================================== */
document.addEventListener("DOMContentLoaded", function() {
    const countdownElements = document.querySelectorAll("[data-expiry]");
    
    // Safety check: Exit if there are no countdowns on the page
    if (countdownElements.length === 0) return;

    function formatRemaining(ms) {
        if (ms <= 0) {
            return "Expired";
        }

        const hours = Math.floor(ms / 3600000);
        const minutes = Math.floor((ms % 3600000) / 60000);
        const seconds = Math.floor((ms % 60000) / 1000);

        return String(hours).padStart(2, "0") + "h " +
               String(minutes).padStart(2, "0") + "m " +
               String(seconds).padStart(2, "0") + "s";
    }

    function tickCountdowns() {
        countdownElements.forEach(function(element) {
            const expiry = Number(element.dataset.expiry || 0);

            if (!expiry) {
                return;
            }

            element.textContent = formatRemaining(expiry - Date.now());
        });
    }

    tickCountdowns();
    setInterval(tickCountdowns, 1000);
});

/* ==========================================================================
   4. SUPERVISOR REVIEW
   ========================================================================== */
document.addEventListener("DOMContentLoaded", function() {
    const reviewForm = document.getElementById("reviewForm");
    
    // Safety check: Exit if we are not on the Review page
    if (!reviewForm) return;

    const feedback = document.getElementById("textFeedback");
    const feedbackCounter = document.getElementById("feedbackCounter");
    const ratingInputs = Array.from(document.querySelectorAll('input[name="starRating"]'));
    const ratingText = document.getElementById("ratingText");

    function updateFeedbackCounter() {
        if (!feedback || !feedbackCounter) return;
        feedbackCounter.textContent = feedback.value.length + " / 1000 characters";
    }

    function updateRatingText() {
        const selected = ratingInputs.find(input => input.checked);
        if (ratingText) {
            ratingText.textContent = (selected ? selected.value : "0") + " / 5.0";
        }
    }

    ratingInputs.forEach(function(input) {
        input.addEventListener("change", updateRatingText);
    });

    if (feedback) {
        feedback.addEventListener("input", updateFeedbackCounter);
    }

    reviewForm.addEventListener("submit", function(event) {
        const selected = ratingInputs.find(input => input.checked);

        if (!selected) {
            event.preventDefault();
            alert("Submission failed. You must select a star rating (1-5) to submit a review. Text feedback is optional.");
            return;
        }

        if (feedback && feedback.value.length > 1000) {
            event.preventDefault();
            alert("Submission failed. Feedback cannot exceed 1000 characters.");
        }
    });

    reviewForm.addEventListener("reset", function() {
        setTimeout(function() {
            updateRatingText();
            updateFeedbackCounter();
        }, 0);
    });

    updateRatingText();
    updateFeedbackCounter();
});

/* ==========================================================================
   4. TIMELINE & MILESTONES
   ========================================================================== */
document.addEventListener("DOMContentLoaded", function() {
    const timer = document.getElementById("countdownValue");
    if (!timer) return;

    let timelinePayload = window.ssasTimelineData || {};
    let serverOffsetMs = (Number(timelinePayload.serverEpoch || 0) * 1000) - Date.now();

    function pad(value) { return String(value).padStart(2, "0"); }

    function formatRemaining(seconds) {
        seconds = Math.max(0, Math.floor(seconds));
        const days = Math.floor(seconds / 86400);
        const hours = Math.floor((seconds % 86400) / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        return `${pad(days)}d ${pad(hours)}h ${pad(minutes)}m`;
    }

    function formatDateTime(value) {
        if (!value) return "-";
        const date = new Date(value.replace(" ", "T"));
        return date.toLocaleString("en-MY", { day: "2-digit", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit" });
    }

    function renderCountdown() {
        const target = timelinePayload.activePhase ? timelinePayload.endTimestamp : (timelinePayload.startTimestamp || null);
        let remainingSeconds = 0;
        if (target) {
            // Calculates remaining time by comparing target against local time + server offset
            remainingSeconds = Math.max(0, Math.floor((new Date(target.replace(" ", "T")).getTime() - (Date.now() + serverOffsetMs)) / 1000));
        }
        // Physically mutates the DOM text without reloading the page
        timer.textContent = formatRemaining(remainingSeconds);
    }

    function renderPayload(payload) {
        timelinePayload = payload;
        serverOffsetMs = (Number(payload.serverEpoch || 0) * 1000) - Date.now();
        document.getElementById("serverClock").textContent = "Server Time: " + payload.serverTime;
        document.getElementById("phaseStatus").textContent = payload.phaseStatus;
        document.getElementById("phaseTitle").textContent = payload.activePhaseName;
        document.getElementById("phaseMessage").textContent = payload.message;
        document.getElementById("submissionAccess").textContent = payload.canSubmitProposal ? "Unlocked" : "Locked";
        document.getElementById("phaseStart").textContent = formatDateTime(payload.startTimestamp);
        document.getElementById("phaseEnd").textContent = formatDateTime(payload.endTimestamp);
        document.getElementById("countdownCaption").textContent = payload.endTimestamp ? "Ends " + formatDateTime(payload.endTimestamp) : "No active deadline";
        renderCountdown();
    }

    // Re-syncs the UI with the authoritative server clock
    async function refreshTimeline() {
        try {
            const response = await fetch("../../server/application/student/getTimelineStatus.php", { credentials: "same-origin" });
            if (response.ok) renderPayload(await response.json());
        } catch (error) { /* Silent fail */ }
    }

    // Local 1000ms visual tick
    renderCountdown();
    setInterval(renderCountdown, 1000); // Executes the DOM mutation every 1000 milliseconds
    setInterval(refreshTimeline, 60000); // Authoritative server poll to prevent browser desynchronization
});

/* ==========================================================================
   4. PROPOSAL SUBMISSION FORM
   ========================================================================== */
document.addEventListener("DOMContentLoaded", function() {
    const proposalForm = document.getElementById("proposalForm");
    if (!proposalForm) return;

    const proposalPDF = document.getElementById("proposalPDF");
    const fileName = document.getElementById("fileName");
    const dropZone = document.getElementById("dropZone");
    const uploadIcon = document.querySelector(".upload-icon");
    const requirementRows = document.querySelectorAll(".requirement");
    const fileTypeRequirement = requirementRows[0] || null;
    const fileSizeRequirement = requirementRows[1] || null;
    const maxBytes = 5 * 1024 * 1024;

    function setRequirementState(element, state) {
        if (!element) return;
        element.classList.remove("valid", "invalid");
        if (state) element.classList.add(state);
    }

    function validateFile(file, showMissing = false) {
        if (!file) {
            if (fileName) fileName.textContent = "";
            if (dropZone) dropZone.classList.remove("valid", "invalid");
            if (uploadIcon) uploadIcon.textContent = "↑";
            setRequirementState(fileTypeRequirement, showMissing ? "invalid" : "");
            setRequirementState(fileSizeRequirement, showMissing ? "invalid" : "");
            return false;
        }

        const isPdf = file.type === "application/pdf" || file.name.toLowerCase().endsWith(".pdf");
        const isWithinLimit = file.size <= maxBytes;

        setRequirementState(fileTypeRequirement, isPdf ? "valid" : "invalid");
        setRequirementState(fileSizeRequirement, isWithinLimit ? "valid" : "invalid");

        if (!isPdf || !isWithinLimit) {
            dropZone.classList.remove("valid");
            dropZone.classList.add("invalid");
            if (uploadIcon) uploadIcon.textContent = "!";
            fileName.textContent = file.name + " - " + (!isPdf ? "PDF only" : "exceeds 5MB");
            proposalPDF.value = "";
            return false;
        }

        dropZone.classList.remove("invalid");
        dropZone.classList.add("valid");
        if (uploadIcon) uploadIcon.textContent = "✓";
        fileName.textContent = file.name;
        return true;
    }

    if (proposalPDF) {
        proposalPDF.addEventListener("change", function() {
            validateFile(proposalPDF.files[0]);
        });
    }

    if (dropZone) {
        dropZone.addEventListener("dragover", function(event) { event.preventDefault(); });
        dropZone.addEventListener("drop", function(event) {
            event.preventDefault();
            if (event.dataTransfer.files.length) {
                proposalPDF.files = event.dataTransfer.files;
                validateFile(event.dataTransfer.files[0]);
            }
        });
    }

    proposalForm.addEventListener("submit", function(event) {
        if (!proposalForm.checkValidity() || !validateFile(proposalPDF.files[0], true)) {
            event.preventDefault();
            return;
        }
        if (!confirm("Submit this proposal to the selected supervisor?")) {
            event.preventDefault();
        }
    });
});