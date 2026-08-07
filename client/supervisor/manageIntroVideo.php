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
    <?php 
    require_once __DIR__ . "/../shared/_head.php";
    echo renderSsasHead("Introductory Video", "supervisor"); 
    ?>
    <script>
        window.ssasVideoConfig = {
            existingUploadedVideo: <?php echo $isUploadedVideo ? "true" : "false"; ?>,
            existingExternalVideo: <?php echo $hasExternalVideo ? "true" : "false"; ?>
        };
    </script>
</head>
<body>
    <?php echo supervisorTopbar(); ?>
    <div class="content-shell">
        <?php echo supervisorSidebar("intro-video"); ?>
        <main class="main">
            <div class="intro-shell">
                <?php echo statusMessage(); ?>

                <section class="card hero intro-hero">
                    <div>
                        <h1>Introductory Video</h1>
                        <p>Enhance your profile visibility by adding a short introductory video.</p>
                    </div>
                    <div class="hero-stat">
                        <div>
                            <div class="metric-label" style="color: #b9d2ff;">Video Setup</div>
                            <div class="stat-value"><?php echo e($videoScore); ?>/2</div>
                        </div>
                        <div>
                            <div class="metric-label" style="color: #b9d2ff;">Status</div>
                            <div class="status-value"><?php echo e($displayStatus); ?></div>
                        </div>
                    </div>
                </section>

                <form class="video-layout"
                    action="../../server/application/supervisor/updateIntroVideo.php"
                    method="POST"
                    id="videoForm"
                    enctype="multipart/form-data">

                    <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION["csrf_token"]); ?>">
                    <input type="hidden" name="existingIntroVideoLink" value="<?php echo e($videoLink); ?>">

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
                            <button class="video-fullscreen-button" id="videoFullscreenButton" type="button" aria-label="Toggle video fullscreen">
                                <span class="fullscreen-enter">Fullscreen</span>
                                <span class="fullscreen-exit">Exit</span>
                            </button>
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

                    <aside>
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

                            <div class="drop-zone" id="uploadPanel" style="display: <?php echo $isUploadedVideo ? 'block' : 'none'; ?>">
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
                                <input type="file" id="introVideoFile" name="introVideoFile" accept="video/mp4,video/webm">
                            </div>

                            <div class="url-section" id="externalPanel" style="display: <?php echo !$isUploadedVideo ? 'block' : 'none'; ?>">
                                <span class="url-label">Or Paste A URL</span>
                                <div class="saved-link-pill" id="savedLinkPill" style="display: <?php echo $hasExternalVideo ? 'flex' : 'none'; ?>;">External link saved</div>
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
</body>
</html>
