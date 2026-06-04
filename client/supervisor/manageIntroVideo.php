<?php

require_once __DIR__ . "/../../server/application/auth/SessionManager.php";
require_once __DIR__ . "/../../server/business/services/SupervisorProfileService.php";
require_once __DIR__ . "/supervisorLayout.php";

SessionManager::startSession();
SessionManager::requireRole("Supervisor");

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$profileService   = new SupervisorProfileService();
$profile          = $profileService->getDigitalBusinessCard($_SESSION["userID"]);
$videoLink        = $profile["introVideoLink"]        ?? "";
$videoDescription = $profile["introVideoDescription"] ?? "";
$videoStatus      = strtolower($profile["introVideoStatus"] ?? "");
$hasVideo         = !empty($videoLink);
$isPublished      = $videoStatus === "published" && $hasVideo;
$isUploadedVideo  = preg_match("/\/storage\/intro_videos\/.+\.(mp4|webm)$/i", $videoLink) === 1;
$hasExternalVideo = $hasVideo && !$isUploadedVideo;
$videoScore       = ($hasVideo ? 1 : 0) + (trim($videoDescription) !== "" ? 1 : 0);
$displayStatus    = $isPublished ? "Published" : "Draft";

function videoEmbedUrl($url) {
    if (preg_match("/youtu\.be\/([^?&]+)/", $url, $matches))
        return "https://www.youtube.com/embed/" . $matches[1];
    if (preg_match("/youtube\.com\/watch\?v=([^?&]+)/", $url, $matches))
        return "https://www.youtube.com/embed/" . $matches[1];
    if (preg_match("/vimeo\.com\/([0-9]+)/", $url, $matches))
        return "https://player.vimeo.com/video/" . $matches[1];
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

        /* Page shell — full width of .main — */
        .intro-shell { width: 100%; }
        .intro-hero { border-radius: 8px; padding: 30px 34px; }
        .intro-hero .hero-stat { min-width: 310px; display: grid; grid-template-columns: 1fr 1fr; gap: 18px; align-items: center; }
        .intro-hero .stat-value { font-size: 28px; }
        .intro-hero .status-value { color: #fff; font-size: 20px; font-weight: 900; margin-top: 8px; }

        /* Two-column layout */
        .video-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 300px;
            gap: 24px;
            align-items: start;
        }

        /*  Video preview — taller to fill the space — */
        .video-preview {
            height: 340px;
            border-radius: 10px;
            background: #111418;
            display: grid;
            place-items: center;
            color: #fff;
            position: relative;
            overflow: hidden;
        }
        .video-preview iframe,
        .video-preview video { width: 100%; height: 100%; border: 0; display: block; background: #000; }

        .play-btn {
            width: 72px; height: 72px; border-radius: 50%;
            border: 3px solid rgba(255,255,255,.5);
            background: rgba(255,255,255,.12);
            display: grid; place-items: center;
        }
        .play-btn::before {
            content: "";
            width: 0; height: 0;
            border-top: 13px solid transparent;
            border-bottom: 13px solid transparent;
            border-left: 20px solid rgba(255,255,255,.85);
            margin-left: 5px;
        }
        .preview-label {
            position: absolute; bottom: 14px;
            font-size: 11px; font-weight: 800;
            letter-spacing: 1px; color: rgba(255,255,255,.6);
            text-transform: uppercase;
        }

        /*  Description card */
        .description-card {
            margin-top: 16px;
            padding: 18px;
            border-radius: 10px;
        }
        .description-card label {
            font-size: 11px; text-transform: uppercase;
            letter-spacing: .8px; color: #8a9caf;
            font-weight: 800; margin-bottom: 8px; display: block;
        }
        .description-card textarea {
            min-height: 120px;
            background: #f6f8fb;
            font-size: 13px;
            resize: vertical;
            width: 100%;
        }
        .description-meta {
            display: flex; justify-content: space-between;
            gap: 12px; margin-top: 7px;
            color: #9aa8b7; font-size: 11px;
        }

        /* Right sidebar cards  */
        .sidebar-card {
            background: #fff;
            border: 1px solid #dce8f3;
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 14px;
        }
        .sidebar-card:last-child { margin-bottom: 0; }

        .card-label {
            font-size: 11px; font-weight: 900;
            text-transform: uppercase; letter-spacing: .8px;
            color: #8a9caf; margin: 0 0 14px; display: block;
        }

        /*  Source toggle  */
        .toggle {
            display: grid; grid-template-columns: 1fr 1fr;
            background: #eef3f8; border-radius: 8px;
            padding: 3px; margin-bottom: 16px;
        }
        .toggle label {
            text-align: center; border-radius: 6px;
            padding: 8px; font-weight: 800; font-size: 12px;
            color: #526a7f; margin: 0; cursor: pointer;
        }
        .toggle input { display: none; }
        .toggle label.active {
            background: #fff; color: #0d5be8;
            box-shadow: 0 2px 8px rgba(11,79,138,.1);
        }

        /* Upload drop zone */
        .drop-zone {
            border: 2px dashed #c4d6ea; border-radius: 10px;
            padding: 24px 14px 20px; text-align: center;
            cursor: pointer; position: relative;
            transition: border-color .18s, background .18s;
            margin-bottom: 0;
        }
        .drop-zone:hover, .drop-zone.dragover {
            border-color: #0d5be8; background: #f0f6ff;
        }
        .drop-zone input[type="file"] {
            position: absolute; inset: 0;
            width: 100%; height: 100%;
            opacity: 0; cursor: pointer;
        }
        .upload-icon {
            width: 44px; height: 44px;
            margin: 0 auto 10px;
            background: #e8f0ff; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
        }
        .upload-icon svg { width: 22px; height: 22px; }
        .drop-title { display: block; font-size: 13px; font-weight: 700; color: #2d3f52; margin-bottom: 3px; }
        .drop-hint  { display: block; font-size: 11px; color: #8fa3b5; margin-bottom: 0; }
        .drop-filename { display: block; margin-top: 8px; font-size: 11px; color: #0d5be8; font-weight: 600; min-height: 14px; word-break: break-all; }

        /* URL field */
        .url-section { margin-top: 14px; padding-top: 14px; border-top: 1px solid #edf2f7; }
        .url-label {
            font-size: 11px; font-weight: 800;
            text-transform: uppercase; letter-spacing: .7px;
            color: #8a9caf; margin-bottom: 7px; display: block;
        }
        .url-wrap { position: relative; }
        .url-wrap input { background: #f6f8fb; padding-left: 32px; font-size: 12px; }
        .url-icon {
            position: absolute; left: 10px; top: 50%;
            transform: translateY(-50%);
            color: #8a9caf; font-size: 13px; pointer-events: none;
        }
        .saved-link-pill {
            display: <?php echo $hasExternalVideo ? "flex" : "none"; ?>;
            align-items: center;
            gap: 8px;
            min-height: 38px;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            background: #f0fdf4;
            color: #15803d;
            padding: 0 12px;
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .4px;
        }
        .saved-link-pill:before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #22c55e;
            flex: 0 0 auto;
        }
        .url-wrap.saved { display: none; }

        /* Status card */
        .status-header { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
        .status-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
        .status-dot.published { background: #22c55e; }
        .status-dot.draft     { background: #f59e0b; }
        .status-label { font-size: 12px; font-weight: 900; text-transform: uppercase; letter-spacing: .6px; }
        .status-label.published { color: #15803d; }
        .status-label.draft     { color: #b45309; }
        .status-card-text { color: #526a7f; font-size: 12px; line-height: 1.6; margin: 0 0 16px; }

        /* Action buttons */
        .actions { display: grid; gap: 10px; }
        .btn-publish {
            width: 100%; height: 40px; border-radius: 8px;
            background: #0d5be8; color: #fff;
            border: none; font-weight: 800; font-size: 13px; cursor: pointer;
        }
        .btn-publish:hover { background: #0947c2; }
        .btn-remove {
            width: 100%; height: 38px; border-radius: 8px;
            background: #fff2f2; color: #c02d2d;
            border: 1px solid #fecaca; font-weight: 800; font-size: 13px; cursor: pointer;
        }
        .btn-remove:hover { background: #fee2e2; }
        .btn-draft {
            width: 100%; height: 38px; border-radius: 8px;
            background: #f1f5f9; color: #3d5166;
            border: 1px solid #dce8f3; font-weight: 800; font-size: 13px;
            cursor: pointer; text-decoration: none;
            display: flex; align-items: center; justify-content: center;
        }
        .btn-draft:hover { background: #e8eef5; }

        /* Best practices */
        .practice-list { margin: 0; padding: 0; list-style: none; display: grid; gap: 10px; }
        .practice-list li { display: flex; align-items: center; gap: 9px; color: #526a7f; font-size: 12px; line-height: 1.4; }
        .practice-dot {
            width: 20px; height: 20px; border-radius: 50%;
            background: #dcfce7; flex-shrink: 0;
            display: grid; place-items: center;
        }
        .practice-dot::after { content: "OK"; font-size: 8px; font-weight: 900; color: #16a34a; }

        /* Responsive */
        @media (max-width: 960px) {
            .intro-hero .hero-stat { min-width: 0; margin-top: 18px; }
            .video-layout { grid-template-columns: 1fr; }
            .video-preview { height: 280px; }
        }
    </style>
</head>
<body>
    <?php echo supervisorTopbar(); ?>
    <div class="content-shell">
        <?php echo supervisorSidebar("intro-video"); ?>
        <main class="main">
            <div class="intro-shell">
                <?php echo statusMessage(); ?>

                <section class="hero intro-hero">
                    <div>
                        <h1>Introductory Video</h1>
                        <p>Enhance your profile visibility by adding a short introductory video.</p>
                    </div>
                    <div class="hero-stat">
                        <div>
                            <div class="stat-label">Video Setup</div>
                            <div class="stat-value"><?php echo e($videoScore); ?>/2</div>
                        </div>
                        <div>
                            <div class="stat-label">Status</div>
                            <div class="status-value"><?php echo e($displayStatus); ?></div>
                        </div>
                    </div>
                </section>

                <form class="video-layout"
                    action="../../server/application/supervisor/updateIntroVideo.php"
                    method="POST"
                    id="videoForm"
                    enctype="multipart/form-data">

                    <input type="hidden" name="csrf_token"             value="<?php echo e($_SESSION["csrf_token"]); ?>">
                    <input type="hidden" name="existingIntroVideoLink" value="<?php echo e($videoLink); ?>">

                    <!--  Left: preview + description -->
                    <section>
                        <div class="video-preview">
                            <?php if ($hasVideo && $isUploadedVideo): ?>
                                <video controls src="<?php echo e($videoLink); ?>"></video>
                            <?php elseif ($hasVideo && $embedUrl !== ""): ?>
                                <iframe src="<?php echo e($embedUrl); ?>" title="Introductory video" allowfullscreen></iframe>
                            <?php else: ?>
                                <div class="play-btn" aria-label="Preview"></div>
                                <span class="preview-label">Preview Mode</span>
                            <?php endif; ?>
                        </div>

                        <section class="card description-card">
                            <label for="introVideoDescription">Video Description</label>
                            <textarea
                                id="introVideoDescription"
                                name="introVideoDescription"
                                maxlength="500"
                                placeholder="Briefly describe what students will learn from your video..."
                            ><?php echo e($videoDescription); ?></textarea>
                            <div class="description-meta">
                                <span>Tip: keep descriptions concise and highlight your core research areas.</span>
                                <span><span id="descriptionCount"><?php echo e(strlen($videoDescription)); ?></span> / 500 characters</span>
                            </div>
                        </section>
                    </section>

                    <!--  Right sidebar  -->
                    <aside>

                        <!-- Content source -->
                        <div class="sidebar-card">
                            <span class="card-label">Content Source</span>
                            <div class="toggle">
                                <label id="uploadTab" class="<?php echo $isUploadedVideo ? "active" : ""; ?>">
                                    <input type="radio" name="contentSource" value="upload"
                                        <?php echo $isUploadedVideo ? "checked" : ""; ?>>
                                    Upload File
                                </label>
                                <label id="externalTab" class="<?php echo !$isUploadedVideo ? "active" : ""; ?>">
                                    <input type="radio" name="contentSource" value="external"
                                        <?php echo !$isUploadedVideo ? "checked" : ""; ?>>
                                    External Link
                                </label>
                            </div>

                            <div class="drop-zone" id="uploadPanel">
                                <div class="upload-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="#0d5be8"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="16 16 12 12 8 16"/>
                                        <line x1="12" y1="12" x2="12" y2="21"/>
                                        <path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/>
                                    </svg>
                                </div>
                                <span class="drop-title">Click or drag MP4 here</span>
                                <span class="drop-hint">Maximum file size: 50MB</span>
                                <span class="drop-filename" id="fileLabel">No file chosen</span>
                                <input type="hidden" name="MAX_FILE_SIZE" value="52428800">
                                <input type="file" id="introVideoFile" name="introVideoFile"
                                    accept="video/mp4,video/webm">
                            </div>

                            <div class="url-section" id="externalPanel">
                                <span class="url-label">Or Paste A URL</span>
                                <div class="saved-link-pill" id="savedLinkPill">External link saved</div>
                                <div class="url-wrap <?php echo $hasExternalVideo ? "saved" : ""; ?>" id="urlWrap">
                                    <span class="url-icon">URL</span>
                                    <input type="url"
                                        id="introVideoLink"
                                        name="introVideoLink"
                                        value="<?php echo $isUploadedVideo ? "" : e($videoLink); ?>"
                                        placeholder="YouTube or Vimeo link"
                                        pattern="https?:\/\/(www\.)?(youtube\.com|youtu\.be|vimeo\.com)\/.+">
                                </div>
                            </div>
                        </div>

                        <!-- Status + actions -->
                        <div class="sidebar-card">
                            <div class="status-header">
                                <span class="status-dot <?php echo $isPublished ? "published" : "draft"; ?>"></span>
                                <span class="status-label <?php echo $isPublished ? "published" : "draft"; ?>">
                                    Status: <?php echo $isPublished ? "Published" : "Draft"; ?>
                                </span>
                            </div>
                            <p class="status-card-text">
                                Save incomplete video details as a draft, then publish when the video source is ready.
                            </p>
                            <div class="actions">
                                <button class="btn-publish" type="submit">Publish Video</button>
                                <?php if ($hasVideo): ?>
                                    <button class="btn-remove" type="submit"
                                            name="removeIntroVideo" value="1" formnovalidate>
                                        Remove Video
                                    </button>
                                <?php endif; ?>
                                <button class="btn-draft" type="submit"
                                        name="saveDraft" value="1" formnovalidate>
                                    Save as Draft
                                </button>
                            </div>
                        </div>

                        <!-- Best practices -->
                        <div class="sidebar-card">
                            <span class="card-label">Best Practices</span>
                            <ul class="practice-list">
                                <li><span class="practice-dot"></span> Recommended length: 90-120 seconds</li>
                                <li><span class="practice-dot"></span> Use a clear academic introduction</li>
                                <li><span class="practice-dot"></span> Include supervision expectations</li>
                            </ul>
                        </div>

                    </aside>
                </form>
            </div>
        </main>
    </div>

    <script>
        const uploadTab      = document.getElementById("uploadTab");
        const externalTab    = document.getElementById("externalTab");
        const uploadPanel    = document.getElementById("uploadPanel");
        const externalPanel  = document.getElementById("externalPanel");
        const description    = document.getElementById("introVideoDescription");
        const descCount      = document.getElementById("descriptionCount");
        const fileInput      = document.getElementById("introVideoFile");
        const fileLabel      = document.getElementById("fileLabel");
        const videoPreview   = document.querySelector(".video-preview");
        const introVideoLink = document.getElementById("introVideoLink");
        const urlWrap        = document.getElementById("urlWrap");
        const savedLinkPill  = document.getElementById("savedLinkPill");
        const existingUploadedVideo = <?php echo $isUploadedVideo ? "true" : "false"; ?>;
        const existingExternalVideo = <?php echo $hasExternalVideo ? "true" : "false"; ?>;
        const maxVideoBytes  = 50 * 1024 * 1024;
        let localPreviewUrl  = "";

        function syncSourceTabs() {
            const source = document.querySelector('input[name="contentSource"]:checked').value;
            uploadTab.classList.toggle("active", source === "upload");
            externalTab.classList.toggle("active", source === "external");
            uploadPanel.style.display   = source === "upload"   ? "block" : "none";
            externalPanel.style.display = source === "external" ? "block" : "none";
            fileInput.disabled      = source !== "upload";
            introVideoLink.disabled = source !== "external";
            if (source === "external" && existingExternalVideo) {
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

        description.addEventListener("input", function() {
            descCount.textContent = description.value.length;
        });

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
            if (!file) { fileLabel.textContent = "No file chosen"; return true; }
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

        fileInput.addEventListener("change", function() {
            validateVideoFile(fileInput.files[0]);
        });

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
            if (event.submitter && event.submitter.name === "saveDraft") {
                return;
            }
            if (event.submitter && event.submitter.name === "removeIntroVideo") {
                if (!confirm("Remove the current introductory video?")) event.preventDefault();
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
