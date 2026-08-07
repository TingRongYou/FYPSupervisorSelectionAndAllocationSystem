<?php

require_once __DIR__ . "/../shared/accountLayout.php";

function adminReportExportMenu($reportType, $filters = []) {
    /*
    |--------------------------------------------------------------------------
    | Multi-Format Export Menu
    |--------------------------------------------------------------------------
    */
    return "
        <form class=\"export-menu\" method=\"GET\" action=\"../../server/application/admin/exportAdminReport.php\" onsubmit=\"return prepareAdminReportExport(this);\">
            <input type=\"hidden\" name=\"reportType\" value=\"" . ssasEscape($reportType) . "\">
            " . adminReportHiddenInputs($filters) . "
            <select name=\"format\" aria-label=\"Export format\">
                <option value=\"pdf\">PDF</option>
                <option value=\"csv\">CSV</option>
                <option value=\"xls\">Excel</option>
            </select>
            <button type=\"submit\">Export</button>
        </form>
    ";
}

function adminReportHiddenInputs($filters) {
    $html = "";
    foreach ($filters as $key => $value) {
        $html .= "<input type=\"hidden\" name=\"" . ssasEscape($key) . "\" value=\"" . ssasEscape($value) . "\">";
    }
    return $html;
}

function adminLastActiveLabel($dateValue) {
    // Converts the latest allocation timestamp into a short roster label.
    if (empty($dateValue)) {
        return "No activity";
    }

    $seconds = max(0, time() - strtotime($dateValue));

    if ($seconds < 3600) {
        return "Today";
    }
    if ($seconds < 86400) {
        return floor($seconds / 3600) . " hours ago";
    }
    return floor($seconds / 86400) . " days ago";
}

?>