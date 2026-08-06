<?php

// Shared Supervisor Report Styling
// CSS has been extracted to client/assets/css/supervisor.css
// Function retained to prevent undefined function errors in legacy files.
function reportStyles() {
    return "";
}

function reportExportMenu($reportType, $filters = []) {
    // Export Request Parameters
    // Keeps the selected report type and filters attached to CSV, Excel, and PDF exports.
    $queryParts = ["reportType" => $reportType];

    foreach ($filters as $key => $value) {
        if ((string) $value !== "") {
            $queryParts[$key] = $value;
        }
    }

    $baseQuery = http_build_query($queryParts);

    return "
        <form class=\"export-menu\" method=\"GET\" action=\"../../server/application/supervisor/exportSupervisorReport.php\" onsubmit=\"return prepareReportExport(this);\">
            <input type=\"hidden\" name=\"reportType\" value=\"" . e($reportType) . "\">
            " . reportHiddenInputs($filters) . "
            <select name=\"format\" aria-label=\"Export format\">
                <option value=\"pdf\">PDF</option>
                <option value=\"csv\">CSV</option>
                <option value=\"xls\">Excel</option>
            </select>
            <button type=\"submit\">Export</button>
        </form>
    ";
}

function reportExportScript() {
    // PDF Print Isolation
    // JS has been extracted to client/assets/js/supervisor.js
    // Function retained to prevent undefined function errors in legacy files.
    return "";
}

function reportHiddenInputs($filters) {
    // Hidden Filter Fields
    // Converts the active screen filters into hidden form inputs for export requests.
    $html = "";

    foreach ($filters as $key => $value) {
        $html .= "<input type=\"hidden\" name=\"" . e($key) . "\" value=\"" . e($value) . "\">";
    }

    return $html;
}

function reportInitials($name) {
    // Avatar Initials Helper
    // Produces compact initials for student chips in report tables.
    $parts = preg_split("/\s+/", trim((string) $name));

    return strtoupper(substr($parts[0] ?? "S", 0, 1)) . strtoupper(substr($parts[1] ?? "", 0, 1));
}

function reportSemesterLabel($semester) {
    // Semester Display Helper
    // Converts stored semester filter values into user-facing labels.
    if ($semester === "1") return "Semester 1";
    if ($semester === "2") return "Semester 2";
    if ($semester === "3") return "Semester 3";

    return "All Semesters";
}

function reportYearLabel($year) {
    // Year Display Helper
    // Keeps empty year filters visible as the all-years state in pills and exports.
    return trim((string) $year) === "" ? "All Years" : (string) $year;
}

function reportPalette() {
    // Chart Palette
    // Provides consistent colors for donut slices, chips, and report visuals.
    return [
        "#0d5be8",
        "#334155",
        "#b8c7de",
        "#dbe3ef",
        "#14b8a6",
        "#f59e0b"
    ];
}

?>