/*
|--------------------------------------------------------------------------
| SSAS SUPERVISOR MODULE SCRIPTS
|--------------------------------------------------------------------------
| This file contains all client-side JavaScript for the Supervisor portal.
*/

/* ==========================================================================
   1. DIGITAL BUSINESS CARD MANAGEMENT
   ========================================================================== */
document.addEventListener("DOMContentLoaded", function() {
    const photoPreview = document.getElementById("photoPreview");
    
    // Safety check: Exit if we are not on the Digital Business Card page
    if (!photoPreview) return;

    const profilePhoto = document.getElementById("profilePhoto");
    const photoUploadControl = document.getElementById("photoUploadControl");
    const photoUploadName = document.getElementById("photoUploadName");

    if (profilePhoto) {
        profilePhoto.addEventListener("change", function() {
            const file = profilePhoto.files[0];

            if (!file) {
                return;
            }

            // Validate file type
            if (!["image/jpeg", "image/png"].includes(file.type)) {
                alert("Invalid Image Format - The uploaded file is not a supported image format. Please upload a JPG or PNG file under 2MB.");
                profilePhoto.value = "";
                photoUploadControl.classList.remove("ready");
                photoUploadName.textContent = "No photo selected";
                return;
            }

            // Validate file size (2MB)
            if (file.size > 2 * 1024 * 1024) {
                alert("Invalid Image Format - The uploaded file is not a supported image format. Please upload a JPG or PNG file under 2MB.");
                profilePhoto.value = "";
                photoUploadControl.classList.remove("ready");
                photoUploadName.textContent = "No photo selected";
                return;
            }

            // Display preview and update UI state
            const previewUrl = URL.createObjectURL(file);
            photoPreview.innerHTML = '<img src="' + previewUrl + '" alt="Profile photo preview"><span>*</span>';
            photoUploadControl.classList.add("ready");
            photoUploadName.textContent = file.name;
        });
    }

    // Form confirmation and validation handling
    const cardForm = document.querySelector(".form-card");
    const discardLink = document.querySelector(".actions .button.secondary");

    if (cardForm) {
        cardForm.addEventListener("submit", function(event) {
            if (!cardForm.checkValidity()) {
                event.preventDefault();
                alert("Validation Error - Please complete all required fields before saving your profile.");
                return;
            }

            if (!confirm("Update your Digital Business Card now?")) {
                event.preventDefault();
            }
        });
    }

    if (discardLink) {
        discardLink.addEventListener("click", function(event) {
            if (!confirm("Cancel changes and return to the previously saved profile data?")) {
                event.preventDefault();
            }
        });
    }
});

/* ==========================================================================
   2. EXPERTISE TAGS MANAGEMENT
   ========================================================================== */
document.addEventListener("DOMContentLoaded", function() {
    const selectedTagsBox = document.getElementById("selectedTagsBox");
    
    // Safety check: Exit if we are not on the Expertise Tags page
    if (!selectedTagsBox) return;

    const activeTagCount = document.getElementById("activeTagCount");
    const tagCheckboxes = Array.from(document.querySelectorAll('input[name="tagIDs[]"]'));

    function renderSelectedTags() {
        const checkedTags = tagCheckboxes.filter(function(checkbox) {
            return checkbox.checked;
        });

        selectedTagsBox.innerHTML = "";

        if (checkedTags.length === 0) {
            selectedTagsBox.innerHTML = '<span class="selected-pill">No expertise selected</span>';
            activeTagCount.textContent = "0 tags active";
            return;
        }

        activeTagCount.textContent = checkedTags.length + " tags active";

        checkedTags.forEach(function(checkbox) {
            const pill = document.createElement("button");
            pill.className = "selected-pill selected-tag";
            pill.type = "button";
            pill.dataset.tagId = checkbox.value;
            pill.textContent = checkbox.dataset.tagName;
            selectedTagsBox.appendChild(pill);
        });
    }

    tagCheckboxes.forEach(function(checkbox) {
        checkbox.addEventListener("change", function() {
            const checkedTags = tagCheckboxes.filter(function(tagCheckbox) {
                return tagCheckbox.checked;
            });

            if (checkedTags.length > 10) {
                checkbox.checked = false;
                alert("A maximum of 10 expertise tags can be selected.");
            }

            renderSelectedTags();
        });
    });

    selectedTagsBox.addEventListener("click", function(event) {
        const pill = event.target.closest(".selected-tag");

        if (!pill) {
            return;
        }

        const checkbox = document.querySelector('input[name="tagIDs[]"][value="' + pill.dataset.tagId + '"]');

        if (checkbox) {
            checkbox.checked = false;
            renderSelectedTags();
        }
    });

    const tagForm = document.getElementById("tagForm");
    if (tagForm) {
        tagForm.addEventListener("submit", function(event) {
            const checkedTags = document.querySelectorAll('input[name="tagIDs[]"]:checked');
            if (checkedTags.length < 1 || checkedTags.length > 10) {
                event.preventDefault();
                alert("Please select between 1 and 10 expertise tags.");
            }
        });
    }
});

/* ==========================================================================
   3. INTRODUCTORY VIDEO MANAGEMENT
   ========================================================================== */
document.addEventListener("DOMContentLoaded", function() {
    const videoForm = document.getElementById("videoForm");
    
    // Safety check: Exit if we are not on the Intro Video page
    if (!videoForm) return;

    const uploadTab = document.getElementById("uploadTab");
    const externalTab = document.getElementById("externalTab");
    const uploadPanel = document.getElementById("uploadPanel");
    const externalPanel = document.getElementById("externalPanel");
    const description = document.getElementById("introVideoDescription");
    const descCount = document.getElementById("descriptionCount");
    const fileInput = document.getElementById("introVideoFile");
    const fileLabel = document.getElementById("fileLabel");
    const videoPreview = document.querySelector(".video-preview");
    const introVideoLink = document.getElementById("introVideoLink");
    const urlWrap = document.getElementById("urlWrap");
    const savedLinkPill = document.getElementById("savedLinkPill");
    
    // Dynamic Database Rules Injected via PHP
    const config = window.ssasVideoConfig || { existingUploadedVideo: false, existingExternalVideo: false };
    
    const maxVideoBytes = 50 * 1024 * 1024;
    let localPreviewUrl = "";

    function syncSourceTabs() {
        const source = document.querySelector('input[name="contentSource"]:checked').value;
        uploadTab.classList.toggle("active", source === "upload");
        externalTab.classList.toggle("active", source === "external");
        
        uploadPanel.style.display = source === "upload" ? "block" : "none";
        externalPanel.style.display = source === "external" ? "block" : "none";
        
        fileInput.disabled = source !== "upload";
        introVideoLink.disabled = source !== "external";
        
        if (source === "external" && config.existingExternalVideo) {
            savedLinkPill.style.display = "flex";
            urlWrap.style.display = "none";
        } else {
            savedLinkPill.style.display = "none";
            urlWrap.style.display = "block";
        }
    }

    document.querySelectorAll('input[name="contentSource"]').forEach(function(input) {
        input.addEventListener("change", syncSourceTabs);
    });

    if (description && descCount) {
        description.addEventListener("input", function() {
            descCount.textContent = description.value.length;
        });
    }

    function showSelectedVideo(file) {
        if (localPreviewUrl) URL.revokeObjectURL(localPreviewUrl);
        localPreviewUrl = URL.createObjectURL(file);
        
        videoPreview.innerHTML = "";
        const video = document.createElement("video");
        video.controls = true;
        video.src = localPreviewUrl;
        videoPreview.appendChild(video);
    }

    function validateVideoFile(file) {
        if (!file) { 
            fileLabel.textContent = "No file chosen"; 
            return true; 
        }
        
        const allowed = ["video/mp4", "video/webm"];
        if (!allowed.includes(file.type)) {
            alert("Only MP4 or WebM video files are allowed.");
            fileInput.value = "";
            fileLabel.textContent = "No file chosen";
            return false;
        }
        
        if (file.size > maxVideoBytes) {
            alert("Video file cannot exceed 50MB.");
            fileInput.value = "";
            fileLabel.textContent = "No file chosen";
            return false;
        }
        
        fileLabel.textContent = file.name;
        showSelectedVideo(file);
        return true;
    }

    if (fileInput) {
        fileInput.addEventListener("change", function() {
            validateVideoFile(fileInput.files[0]);
        });
    }

    if (uploadPanel) {
        uploadPanel.addEventListener("dragover", function(e) {
            e.preventDefault();
            uploadPanel.classList.add("dragover");
        });
        
        uploadPanel.addEventListener("dragleave", function() {
            uploadPanel.classList.remove("dragover");
        });
        
        uploadPanel.addEventListener("drop", function(e) {
            e.preventDefault();
            uploadPanel.classList.remove("dragover");
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                validateVideoFile(e.dataTransfer.files[0]);
            }
        });
    }

    syncSourceTabs();

    videoForm.addEventListener("submit", function(event) {
        if (event.submitter && event.submitter.name === "saveDraft") {
            return; // Allow draft save without strict validation
        }
        if (event.submitter && event.submitter.name === "removeIntroVideo") {
            if (!confirm("Remove the current introductory video?")) {
                event.preventDefault();
            }
            return;
        }
        
        const source = document.querySelector('input[name="contentSource"]:checked').value;
        const url = introVideoLink.value.trim();
        const pattern = /^https?:\/\/(www\.)?(youtube\.com|youtu\.be|vimeo\.com)\/.+$/i;
        
        if (source === "external" && !pattern.test(url)) {
            event.preventDefault();
            alert("Please enter a valid YouTube or Vimeo URL.");
        }
        
        if (source === "upload" && fileInput.files.length === 0 && !config.existingUploadedVideo) {
            event.preventDefault();
            alert("Please select an MP4 or WebM video file.");
        }
    });
});

/* ==========================================================================
   4. PAST PROJECTS MANAGEMENT
   ========================================================================== */
document.addEventListener("DOMContentLoaded", function() {
    const projectForm = document.getElementById("projectForm");
    
    // Safety check: Exit if we are not on the Past Projects page
    if (!projectForm) return;

    // Handle displaying file names when files are selected
    const nativeFiles = document.querySelectorAll(".native-file");
    
    nativeFiles.forEach(function(input) {
        input.addEventListener("change", function() {
            const label = document.querySelector('[data-file-label="' + input.id + '"]');
            if (label) {
                label.textContent = input.files.length ? input.files[0].name : "No file selected";
            }
        });
    });

    // Validate completion year before submitting the form
    projectForm.addEventListener("submit", function(event) {
        const yearInput = document.getElementById("completionYear");
        
        if (yearInput) {
            const year = parseInt(yearInput.value);
            const currentYear = new Date().getFullYear() + 1;
            
            if (isNaN(year) || year < 2000 || year > currentYear) {
                event.preventDefault();
                alert("Please enter a valid completion year.");
            }
        }
    });
});

/* ==========================================================================
   5. SIDEBAR DROPDOWN TOGGLES
   ========================================================================== */
window.toggleProfileMenu = function() {
    const subnav = document.getElementById('profileSubnav');
    if (subnav) {
        subnav.style.display = (subnav.style.display === 'none' || subnav.style.display === '') ? 'block' : 'none';
    }
};

window.toggleRequestMenu = function() {
    const subnav = document.getElementById('requestSubnav');
    if (subnav) {
        subnav.style.display = (subnav.style.display === 'none' || subnav.style.display === '') ? 'block' : 'none';
    }
};

window.toggleReportMenu = function() {
    const subnav = document.getElementById('reportSubnav');
    if (subnav) {
        subnav.style.display = (subnav.style.display === 'none' || subnav.style.display === '') ? 'block' : 'none';
    }
};

/* ==========================================================================
   6. REPORT EXPORT (supervisorReportComponents.php)
   ========================================================================== */
window.prepareReportExport = function(form) {
    const formatSelect = form.querySelector('select[name="format"]');
    const format = formatSelect ? formatSelect.value : '';

    if (format === 'pdf') {
        const params = new URLSearchParams(new FormData(form));
        let printFrame = document.getElementById('reportPrintFrame');

        if (!printFrame) {
            printFrame = document.createElement('iframe');
            printFrame.id = 'reportPrintFrame';
            printFrame.name = 'reportPrintFrame';
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

    form.removeAttribute('target');
    return true;
};

/* ==========================================================================
   7. PROPOSAL REVIEW DECISION
   ========================================================================== */
document.addEventListener("DOMContentLoaded", function() {
    const decisionForm = document.querySelector("form.decision-card");
    
    // Safety check: Exit if we are not on a page with an active decision form
    if (!decisionForm) return;

    decisionForm.addEventListener("submit", function(event) {
        const commentArea = decisionForm.querySelector('textarea[name="supervisorComment"]');
        if (!commentArea) return;
        
        const comment = commentArea.value.trim();

        if (comment === "") {
            event.preventDefault();
            alert("Comment Required - Please provide a reason for rejection to help the student improve their next application.");
        }
    });
});