<?php

require_once __DIR__ . "/../server/application/SessionManager.php";
require_once __DIR__ . "/../server/business/SupervisorProfileService.php";
require_once __DIR__ . "/supervisorLayout.php";

SessionManager::startSession();
SessionManager::requireRole("Supervisor");

if (empty($_SESSION["csrf_token"])) {

    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$profileService = new SupervisorProfileService();
$profile = $profileService->getDigitalBusinessCard($_SESSION["userID"]);
$videoLink = $profile["introVideoLink"] ?? "";
$videoDescription = $profile["introVideoDescription"] ?? "";
$hasVideo = !empty($videoLink);
$isUploadedVideo = preg_match("/^\.\.\/storage\/intro_videos\/.+\.(mp4|webm)$/i", $videoLink) === 1;

function videoEmbedUrl($url) {

    if (preg_match("/youtu\.be\/([^?&]+)/", $url, $matches)) {
        return "https://www.youtube.com/embed/" . $matches[1];
    }

    if (preg_match("/youtube\.com\/watch\?v=([^?&]+)/", $url, $matches)) {
        return "https://www.youtube.com/embed/" . $matches[1];
    }

    if (preg_match("/vimeo\.com\/([0-9]+)/", $url, $matches)) {
        return "https://player.vimeo.com/video/" . $matches[1];
    }

    return "";
}

$embedUrl = $hasVideo && !$isUploadedVideo ? videoEmbedUrl($videoLink) : "";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Introductory Video | SSAS</title>
    <style>
        <?php echo supervisorBaseStyles(); ?>
        .video-layout { display: grid; grid-template-columns: 1.45fr .7fr; gap: 24px; align-items: start; }
        .page-title { margin: 0 0 6px; color: #1d2b3a; font-size: 25px; }
        .page-subtitle { margin: 0 0 24px; color: #6b7f91; }
        .video-preview { height: 315px; border-radius: 10px; background: radial-gradient(circle at center, #343a46, #090b10 70%); display: grid; place-items: center; color: #fff; position: relative; overflow: hidden; }
        .video-preview iframe, .video-preview video { width: 100%; height: 100%; border: 0; display: block; background: #000; }
        .play { width: 94px; height: 94px; border-radius: 50%; border: 4px solid rgba(255,255,255,.55); display: grid; place-items: center; font-size: 16px; font-weight: 900; background: rgba(255,255,255,.08); }
        .preview-mode { position: absolute; top: calc(50% + 48px); font-weight: 800; font-size: 12px; letter-spacing: .8px; }
        .description-card { margin-top: 18px; padding: 18px; }
        .source-card, .status-card, .practice-card { padding: 20px; margin-bottom: 18px; }
        .toggle { display: grid; grid-template-columns: 1fr 1fr; background: #f0f4f8; border-radius: 8px; padding: 4px; margin-bottom: 16px; }
        .toggle label { text-align: center; border-radius: 6px; padding: 9px; font-weight: 800; font-size: 12px; color: #526a7f; margin: 0; cursor: pointer; text-transform: none; letter-spacing: 0; }
        .toggle input { display: none; }
        .toggle label.active { background: #fff; color: #0d5be8; box-shadow: 0 4px 10px rgba(11,79,138,.08); }

        /* Drag-and-drop upload zone */
        .drop-zone {
            border: 2px dashed #b8cfe8;
            border-radius: 10px;
            padding: 28px 16px;
            text-align: center;
            color: #6b7f91;
            margin-bottom: 16px;
            cursor: pointer;
            transition: border-color .2s, background .2s;
            position: relative;
        }
        .drop-zone:hover,
        .drop-zone.dragover {
            border-color: #3b82f6;
            background: #f0f6ff;
        }
        .drop-zone input[type="file"] {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        .drop-zone-icon {
            width: 42px;
            height: 42px;
            margin: 0 auto 8px;
            display: block;
            border-radius: 50%;
            background: #e8f0ff;
            position: relative;
        }
        .drop-zone-icon::before {
            content: "";
            position: absolute;
            inset: 11px 10px 13px;
            border: 2px solid #0d5be8;
            border-top: 0;
            border-radius: 0 0 8px 8px;
        }
        .drop-zone-icon::after {
            content: "";
            position: absolute;
            left: 50%;
            top: 10px;
            width: 10px;
            height: 10px;
            border-left: 2px solid #0d5be8;
            border-top: 2px solid #0d5be8;
            transform: translateX(-50%) rotate(45deg);
        }
        .drop-zone-title {
            font-weight: 700;
            font-size: 14px;
            color: #2d3f52;
            display: block;
            margin-bottom: 4px;
        }
        .drop-zone-hint {
            font-size: 12px;
            color: #8fa3b5;
            display: block;
            margin-bottom: 14px;
        }
        .drop-zone-btn {
            display: inline-block;
            padding: 7px 20px;
            background: #3b82f6;
            color: #fff;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 700;
            pointer-events: none; /* click handled by the invisible <input> */
        }
        .drop-zone-filename {
            display: block;
            margin-top: 10px;
            font-size: 12px;
            color: #3b82f6;
            font-weight: 600;
            min-height: 16px;
            word-break: break-all;
        }

        .status-dot { color: #d33; font-weight: 900; }
        .status-card p, .practice-card p { color: #526a7f; line-height: 1.6; font-size: 13px; }
        .actions { display: grid; gap: 12px; }
        .button.danger { background: #fee2e2; color: #b42318; border: 1px solid #fecaca; }
        .practice-list { margin: 0; padding-left: 18px; color: #526a7f; line-height: 1.9; font-size: 13px; }
        @media (max-width: 1000px) { .video-layout { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php echo supervisorTopbar(); ?>
    <div class="content-shell">
        <?php echo supervisorSidebar("intro-video"); ?>
        <main class="main">
            <?php echo statusMessage(); ?>
            <h1 class="page-title">Introductory Video</h1>
            <p class="page-subtitle">Enhance your profile visibility by adding a short introductory video.</p>

            <form class="video-layout" action="../server/application/updateIntroVideo.php" method="POST" id="videoForm" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION["csrf_token"]); ?>">
                <input type="hidden" name="existingIntroVideoLink" value="<?php echo e($videoLink); ?>">
                <section>
                    <div class="video-preview">
                        <?php if ($hasVideo && $isUploadedVideo): ?>
                            <video controls src="<?php echo e($videoLink); ?>"></video>
                        <?php elseif ($hasVideo && $embedUrl !== ""): ?>
                            <iframe src="<?php echo e($embedUrl); ?>" title="Introductory video" allowfullscreen></iframe>
                        <?php else: ?>
                            <div class="play">Play</div>
                            <div class="preview-mode">PREVIEW MODE</div>
                        <?php endif; ?>
                    </div>
                    <section class="card description-card">
                        <label>Video Description</label>
                        <textarea id="introVideoDescription" name="introVideoDescription" maxlength="500" placeholder="Briefly describe what students will learn from your video..."><?php echo e($videoDescription); ?></textarea>
                        <p style="text-align:right;color:#9aa8b7;font-size:12px;"><span id="descriptionCount"><?php echo e(strlen($videoDescription)); ?></span> / 500 characters</p>
                    </section>
                </section>

                <aside>
                    <section class="card source-card">
                        <label>Content Source</label>
                        <div class="toggle">
                            <label id="uploadTab" class="<?php echo $isUploadedVideo ? "active" : ""; ?>">
                                <input type="radio" name="contentSource" value="upload" <?php echo $isUploadedVideo ? "checked" : ""; ?>>
                                Upload File
                            </label>
                            <label id="externalTab" class="<?php echo !$isUploadedVideo ? "active" : ""; ?>">
                                <input type="radio" name="contentSource" value="external" <?php echo !$isUploadedVideo ? "checked" : ""; ?>>
                                External Link
                            </label>
                        </div>

                        <div class="drop-zone" id="uploadPanel">
                            <span class="drop-zone-icon" aria-hidden="true"></span>
                            <span class="drop-zone-title">Drag & drop your video here</span>
                            <span class="drop-zone-hint">MP4 or WebM - Max 50 MB</span>
                            <span class="drop-zone-btn">Browse File</span>
                            <span class="drop-zone-filename" id="fileLabel">No file chosen</span>
                            <input type="hidden" name="MAX_FILE_SIZE" value="52428800">
                            <input type="file" id="introVideoFile" name="introVideoFile" accept="video/mp4,video/webm">
                        </div>

                        <div id="externalPanel">
                            <label>Or Paste A URL</label>
                            <input type="url" id="introVideoLink" name="introVideoLink" value="<?php echo $isUploadedVideo ? "" : e($videoLink); ?>" placeholder="YouTube or Vimeo link" pattern="https?:\/\/(www\.)?(youtube\.com|youtu\.be|vimeo\.com)\/.+">
                        </div>
                    </section>
                    <section class="card status-card">
                        <label><span class="status-dot">*</span> Status: <?php echo $hasVideo ? "Published" : "Draft"; ?></label>
                        <p>Your video is published when a valid uploaded video file or YouTube/Vimeo link is saved.</p>
                        <div class="actions">
                            <button class="button" type="submit">Publish Video</button>
                            <?php if ($hasVideo): ?>
                                <button class="button danger" type="submit" name="removeIntroVideo" value="1" formnovalidate>Remove Video</button>
                            <?php endif; ?>
                            <a class="button secondary" href="manageIntroVideo.php">Save as Draft</a>
                        </div>
                    </section>
                    <section class="practice-card">
                        <label>Best Practices</label>
                        <ul class="practice-list">
                            <li>Recommended length: 90-120 seconds</li>
                            <li>Use a clear academic introduction</li>
                            <li>Include supervision expectations</li>
                        </ul>
                    </section>
                </aside>
            </form>
        </main>
    </div>
    <script>
        const uploadTab    = document.getElementById("uploadTab");
        const externalTab  = document.getElementById("externalTab");
        const uploadPanel  = document.getElementById("uploadPanel");
        const externalPanel = document.getElementById("externalPanel");
        const description  = document.getElementById("introVideoDescription");
        const descCount    = document.getElementById("descriptionCount");
        const fileInput    = document.getElementById("introVideoFile");
        const fileLabel    = document.getElementById("fileLabel");
        const videoPreview = document.querySelector(".video-preview");
        const introVideoLink = document.getElementById("introVideoLink");
        const existingUploadedVideo = <?php echo $isUploadedVideo ? "true" : "false"; ?>;
        const maxVideoBytes = 50 * 1024 * 1024;
        let localPreviewUrl = "";

        function syncSourceTabs() {
            const source = document.querySelector('input[name="contentSource"]:checked').value;
            uploadTab.classList.toggle("active", source === "upload");
            externalTab.classList.toggle("active", source === "external");
            uploadPanel.style.display  = source === "upload"   ? "block" : "none";
            externalPanel.style.display = source === "external" ? "block" : "none";
            fileInput.disabled = source !== "upload";
            introVideoLink.disabled = source !== "external";
        }

        document.querySelectorAll('input[name="contentSource"]').forEach(function(input) {
            input.addEventListener("change", syncSourceTabs);
        });

        description.addEventListener("input", function() {
            descCount.textContent = description.value.length;
        });

        function showSelectedVideo(file) {
            if (localPreviewUrl !== "") {
                URL.revokeObjectURL(localPreviewUrl);
            }

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

            const allowedTypes = ["video/mp4", "video/webm"];

            if (!allowedTypes.includes(file.type)) {
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

        fileInput.addEventListener("change", function() {
            validateVideoFile(fileInput.files[0]);
        });

        // Drag-and-drop highlight
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

        syncSourceTabs();

        document.getElementById("videoForm").addEventListener("submit", function(event) {
            if (event.submitter && event.submitter.name === "removeIntroVideo") {
                if (!confirm("Remove the current introductory video?")) {
                    event.preventDefault();
                }

                return;
            }

            const source  = document.querySelector('input[name="contentSource"]:checked').value;
            const url     = introVideoLink.value.trim();
            const pattern = /^https?:\/\/(www\.)?(youtube\.com|youtu\.be|vimeo\.com)\/.+$/i;

            if (source === "external" && !pattern.test(url)) {
                event.preventDefault();
                alert("Please enter a valid YouTube or Vimeo URL.");
            }

            if (source === "upload" && fileInput.files.length === 0 && !existingUploadedVideo) {
                event.preventDefault();
                alert("Please select an MP4 or WebM video file.");
            }
        });
    </script>
</body>
</html>
