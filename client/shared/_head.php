<?php
// client/shared/_head.php

/**
 * Generates standard head tags for SSAS pages.
 * 
 * @param string $pageTitle The text to display in the browser tab.
 * @param string|null $module The module name ('student', 'supervisor', 'admin', 'auth') to load specific CSS/JS.
 * @return string HTML tags for the <head> section.
 */
function renderSsasHead($pageTitle, $module = null) {
    // 1. Meta and Title
    $html = '    <meta charset="UTF-8">' . "\n";
    $html .= '    <meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
    $html .= '    <title>' . htmlspecialchars((string)$pageTitle, ENT_QUOTES, 'UTF-8') . ' | SSAS</title>' . "\n\n";
    
    // 2. Favicon and Fonts
    $html .= '    <link rel="icon" type="image/png" href="../assets/img/tarumt_logo_only.png">' . "\n";
    $html .= '    <link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
    $html .= '    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
    $html .= '    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">' . "\n\n";
    
    $moduleLower = $module ? strtolower(trim($module)) : null;
    $isAuth = ($moduleLower === 'auth');

    // 3. Shared Assets (Skip for the disconnected Auth silo)
    if (!$isAuth) {
        $sharedCssPath = __DIR__ . '/../assets/css/shared.css';
        $sharedVersion = file_exists($sharedCssPath) ? filemtime($sharedCssPath) : '1.0';
        $html .= '    <link rel="stylesheet" href="../assets/css/shared.css?v=' . $sharedVersion . '">' . "\n";

        $sharedJsPath = __DIR__ . '/../assets/js/shared.js';
        $sharedJsVersion = file_exists($sharedJsPath) ? filemtime($sharedJsPath) : '1.0';
        $html .= '    <script src="../assets/js/shared.js?v=' . $sharedJsVersion . '" defer></script>' . "\n";
    }

    // 4. Module-specific CSS and JS
    if ($moduleLower) {
        $moduleCssPath = __DIR__ . "/../assets/css/{$moduleLower}.css";
        if (file_exists($moduleCssPath)) {
            $cssVersion = filemtime($moduleCssPath);
            $html .= '    <link rel="stylesheet" href="../assets/css/' . $moduleLower . '.css?v=' . $cssVersion . '">' . "\n";
        }
        
        $moduleJsPath = __DIR__ . "/../assets/js/{$moduleLower}.js";
        if (file_exists($moduleJsPath)) {
            $jsVersion = filemtime($moduleJsPath);
            $html .= '    <script src="../assets/js/' . $moduleLower . '.js?v=' . $jsVersion . '" defer></script>' . "\n";
        }
    }

    return $html;
}
?>