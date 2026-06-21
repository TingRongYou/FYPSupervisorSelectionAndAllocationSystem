/* |--------------------------------------------------------------------------
| Admin UI Scripts
|--------------------------------------------------------------------------
*/

function toggleAdminReports(button) {
    const reportTree = document.getElementById("admin-report-tree");
    const isOpen = button.getAttribute("aria-expanded") === "true";
    button.setAttribute("aria-expanded", isOpen ? "false" : "true");
    reportTree.classList.toggle("open", !isOpen);
}

function prepareAdminReportExport(form) {
    const formatSelect = form.querySelector('select[name="format"]');
    const format = formatSelect ? formatSelect.value : '';

    // If PDF, load it quietly in a hidden iframe so the page doesn't refresh
    if (format === 'pdf') {
        const params = new URLSearchParams(new FormData(form));
        let printFrame = document.getElementById('adminReportPrintFrame');

        if (!printFrame) {
            printFrame = document.createElement('iframe');
            printFrame.id = 'adminReportPrintFrame';
            printFrame.name = 'adminReportPrintFrame';
            printFrame.style.position = 'fixed';
            printFrame.style.right = '0';
            printFrame.style.bottom = '0';
            printFrame.style.width = '1px';
            printFrame.style.height = '1px';
            printFrame.style.border = '0';
            printFrame.style.opacity = '0';
            document.body.appendChild(printFrame);
        }

        printFrame.src = form.action + '?' + params.toString();
        return false;
    }

    return true;
}

/* |--------------------------------------------------------------------------
| Admin Dashboard Timeline Validation
|--------------------------------------------------------------------------
*/
document.addEventListener("DOMContentLoaded", function() {
    const timelineForm = document.getElementById("timelineForm");
    if (!timelineForm) return; // Exit if not on the dashboard

    const initialAllocationDate = document.getElementById("initialAllocationDate");
    const finalAllocationDate = document.getElementById("finalAllocationDate");
    const reviewStartDate = document.getElementById("reviewStartDate");
    const reviewEndDate = document.getElementById("reviewEndDate");

    function syncTimelineMinimums() {
        if (finalAllocationDate && initialAllocationDate.value) {
            finalAllocationDate.min = initialAllocationDate.value;
        }
        if (reviewStartDate && finalAllocationDate.value) {
            reviewStartDate.min = finalAllocationDate.value;
        }
        if (reviewEndDate && reviewStartDate.value) {
            reviewEndDate.min = reviewStartDate.value;
        }
    }

    [initialAllocationDate, finalAllocationDate, reviewStartDate].forEach(function(input) {
        if (input) {
            input.addEventListener("change", syncTimelineMinimums);
        }
    });

    timelineForm.addEventListener("submit", function(event) {
        const initialTime = new Date(initialAllocationDate.value).getTime();
        const finalTime = new Date(finalAllocationDate.value).getTime();
        const reviewStartTime = new Date(reviewStartDate.value).getTime();
        const reviewEndTime = new Date(reviewEndDate.value).getTime();

        if (!(initialTime < finalTime) || !(finalTime < reviewStartTime) || !(reviewStartTime < reviewEndTime)) {
            event.preventDefault();
            alert("Timeline Error - Dates must follow this order: Initial Allocation, Final Allocation, Review Period Start, Review Period End.");
        }
    });

    syncTimelineMinimums();
});

/* |--------------------------------------------------------------------------
| Quota Management Scripts
|--------------------------------------------------------------------------
*/
document.addEventListener("DOMContentLoaded", function() {
    const quotaForm = document.getElementById("quotaForm");
    if (!quotaForm) return; // Exit if not on the Quota Management page

    const quotaInputs = Array.from(document.querySelectorAll(".quota-input"));
    const saveBar = document.getElementById("saveBar");
    const modifiedCount = document.getElementById("modifiedCount");
    const discardButton = document.getElementById("discardButton");
    const saveButton = document.getElementById("saveButton");

    function validateInput(input) {
        const rawValue = input.value.trim();
        const value = Number(rawValue);
        const original = Number(input.dataset.original);
        const tierMax = Number(input.dataset.tierMax);
        const currentLoad = Number(input.dataset.currentLoad);
        const row = input.closest(".quota-row");
        const badge = row.querySelector("[data-status-badge]");
        const changedFlag = row.querySelector(".changed-flag");
        const changed = value !== original;
        const invalid = rawValue === "" || !Number.isInteger(value) || value < currentLoad || value > tierMax;

        changedFlag.value = changed ? "1" : "0";
        input.classList.toggle("valid-field", changed && !invalid);
        input.classList.toggle("invalid-field", invalid);

        if (invalid) {
            badge.textContent = "Over-Capacity";
            badge.className = "badge over";
        } else {
            badge.textContent = "Valid";
            badge.className = "badge valid";
        }

        return { changed, invalid };
    }

    function refreshSaveBar() {
        let changedTotal = 0;
        let invalidTotal = 0;

        quotaInputs.forEach(function(input) {
            const result = validateInput(input);
            if (result.changed) changedTotal++;
            if (result.invalid) invalidTotal++;
        });

        modifiedCount.textContent = changedTotal;
        saveBar.classList.toggle("show", changedTotal > 0);
        saveButton.disabled = invalidTotal > 0;
        saveButton.style.opacity = invalidTotal > 0 ? ".55" : "1";
        saveButton.style.cursor = invalidTotal > 0 ? "not-allowed" : "pointer";
    }

    quotaInputs.forEach(function(input) {
        input.addEventListener("input", refreshSaveBar);
    });

    discardButton.addEventListener("click", function() {
        quotaInputs.forEach(function(input) {
            input.value = input.dataset.original;
        });
        refreshSaveBar();
    });

    quotaForm.addEventListener("submit", function(event) {
        refreshSaveBar();

        if (saveButton.disabled) {
            event.preventDefault();
            alert("Quota invalid: the supervisor quota limit is empty, exceeds the supervisor type limit, or is below the current student count.");
            return;
        }

        if (!confirm("Confirm quota limit update?")) {
            event.preventDefault();
        }
    });

    refreshSaveBar();
});

/* |--------------------------------------------------------------------------
| Student Eligibility Scripts
|--------------------------------------------------------------------------
*/
document.addEventListener("DOMContentLoaded", function() {
    const studentCSV = document.getElementById("studentCSV");
    if (!studentCSV) return; // Exit if not on Eligibility page

    const uploadButton = document.getElementById("uploadButton");
    const editRulesButton = document.getElementById("editRulesButton");
    const cancelRulesButton = document.getElementById("cancelRulesButton");
    const rulesEditor = document.getElementById("rulesEditor");
    const heroForm = document.querySelector(".hero-actions form");

    studentCSV.addEventListener("change", function () {
        const label = document.getElementById("fileName");
        label.textContent = this.files.length ? this.files[0].name : "No file uploaded";
        if (this.files.length) {
            this.closest("form").submit();
        }
    });

    uploadButton.addEventListener("click", function () {
        studentCSV.click();
    });

    editRulesButton.addEventListener("click", function () {
        rulesEditor.classList.add("open");
    });

    cancelRulesButton.addEventListener("click", function () {
        rulesEditor.classList.remove("open");
    });

    if (heroForm) {
        heroForm.addEventListener("submit", function (event) {
            if (!confirm("Run eligibility batch using the current active criteria?")) {
                event.preventDefault();
            }
        });
    }
});

/* |--------------------------------------------------------------------------
| Supervisor Management Scripts
|--------------------------------------------------------------------------
*/
document.addEventListener("DOMContentLoaded", function() {
    const createPanel = document.getElementById("createSupervisorPanel");
    if (!createPanel) return; // Exit if not on the Supervisor Management page

    const createClassSelect = document.getElementById("createEmploymentCategory");
    const createQuotaID = document.getElementById("createQuotaID");
    const accountModal = document.getElementById("accountModal");
    const editSupervisorID = document.getElementById("editSupervisorID");
    const editFullName = document.getElementById("editFullName");
    const editUniversityEmail = document.getElementById("editUniversityEmail");
    const editActiveStatus = document.getElementById("editActiveStatus");

    // Must be injected by the page since it requires PHP database data
    const quotaRules = window.ssasQuotaRules || {};

    // ── Open / close create panel ──
    document.querySelectorAll("[data-open-create]").forEach((btn) => {
        btn.addEventListener("click", () => {
            createPanel.classList.add("show");
            createPanel.scrollIntoView({ behavior: "smooth", block: "nearest" });
        });
    });

    document.querySelectorAll("[data-close-create]").forEach((btn) => {
        btn.addEventListener("click", () => {
            createPanel.classList.remove("show");
        });
    });

    // ── Populate hidden quotaID when classification changes ──
    createClassSelect.addEventListener("change", () => {
        const rule = quotaRules[createClassSelect.value];
        createQuotaID.value = rule ? rule.quotaID : "";
    });

    // Seed value on page load
    (function seedQuotaID() {
        const rule = quotaRules[createClassSelect.value];
        createQuotaID.value = rule ? rule.quotaID : "";
    })();

    // ── Account modal ──
    function openAccountModal(button) {
        editSupervisorID.value = button.dataset.supervisorId || "";
        editFullName.value = button.dataset.fullName || "";
        editUniversityEmail.value = button.dataset.email || "";
        editActiveStatus.value = button.dataset.activeStatus || "1";
        accountModal.classList.add("show");
        accountModal.setAttribute("aria-hidden", "false");
        editFullName.focus();
    }

    function closeAccountModal() {
        accountModal.classList.remove("show");
        accountModal.setAttribute("aria-hidden", "true");
    }

    document.querySelectorAll("[data-edit-account]").forEach((button) => {
        button.addEventListener("click", () => openAccountModal(button));
    });

    document.querySelectorAll("[data-close-account-modal]").forEach((button) => {
        button.addEventListener("click", closeAccountModal);
    });

    // Close modal on backdrop click
    accountModal.addEventListener("click", (event) => {
        if (event.target === accountModal) {
            closeAccountModal();
        }
    });

    // Close modal on Escape key
    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && accountModal.classList.contains("show")) {
            closeAccountModal();
        }
    });
});