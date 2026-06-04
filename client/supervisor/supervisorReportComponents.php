<?php

// Shared Supervisor Report Styling
// Centralizes the common report layout, cards, tables, legends, and responsive behavior.
function reportStyles() {

    return <<<CSS
        .report-shell { width: 100%; max-width: 1500px; margin: 0 auto; }
        .report-head { display: flex; justify-content: space-between; align-items: start; gap: 20px; margin: 4px 0 22px; }
        .eyebrow { color: #0d5be8; font-size: 14px; font-weight: 900; text-transform: uppercase; letter-spacing: 1.1px; margin-bottom: 5px; }
        .report-head h1 { margin: 0 0 6px; color: #172033; font-size: 28px; line-height: 1.1; }
        .report-head p { margin: 0; color: #6b7f91; max-width: 560px; font-size: 15px; line-height: 1.5; }
        .export-menu { display: flex; align-items: center; gap: 8px; }
        .export-menu select { width: 140px; height: 40px; border-radius: 7px; font-weight: 800; font-size: 14px; background: #fff; }
        .export-menu button { min-height: 40px; border: 0; border-radius: 7px; padding: 0 15px; background: #eef2f6; color: #2f4053; font-size: 14px; font-weight: 900; cursor: pointer; }
        .report-card { background: rgba(255,255,255,.94); border: 1px solid #e8eef7; border-radius: 10px; box-shadow: 0 16px 38px rgba(11,79,138,.08); }
        .panel-title { margin: 0; color: #172033; font-size: 15px; font-weight: 900; }
        .panel-subtitle { margin: 4px 0 0; color: #7c8da0; font-size: 14px; }
        .filter-row { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; }
        .filter-row select { width: auto; min-width: 145px; height: 40px; background: #fff; font-size: 14px; }
        .filter-row .button { min-height: 40px; border-radius: 7px; padding: 0 15px; font-size: 14px; background: #172033; }
        .empty-message { padding: 24px; border: 1px dashed #aac7df; border-radius: 8px; color: #526a7f; background: #f8fbff; }
        .stat-note { color: #7c8da0; font-size: 14px; line-height: 1.5; }
        .status-pill { display: inline-flex; align-items: center; justify-content: center; min-height: 24px; border-radius: 999px; padding: 0 11px; font-size: 12px; font-weight: 900; text-transform: uppercase; }
        .status-pill.green { background: #dcfce7; color: #166534; }
        .status-pill.blue { background: #e6f0ff; color: #0d5be8; }
        .status-pill.gray { background: #eef2f6; color: #526a7f; }
        .report-table { width: 100%; border-collapse: collapse; }
        .report-table th, .report-table td { padding: 15px 17px; border-bottom: 1px solid #eef3f8; text-align: left; font-size: 14px; vertical-align: middle; }
        .report-table th { color: #7c8da0; font-size: 12px; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; }
        .student-chip { display: inline-grid; place-items: center; width: 28px; height: 28px; border-radius: 7px; color: #fff; font-size: 12px; font-weight: 900; margin-right: 10px; }
        .chart-legend { display: grid; gap: 20px; min-width: 300px; }
        .legend-row { display: grid; grid-template-columns: 14px minmax(0, 1fr) 64px; gap: 12px; align-items: start; }
        .legend-dot { width: 9px; height: 9px; border-radius: 50%; margin-top: 4px; }
        .legend-name { color: #172033; font-size: 14px; font-weight: 800; }
        .legend-count { color: #7c8da0; font-size: 14px; margin-top: 3px; }
        .legend-percent { text-align: right; color: #172033; font-size: 14px; font-weight: 900; }
        .pagination-note { display: flex; justify-content: space-between; align-items: center; padding: 14px 17px; color: #7c8da0; font-size: 14px; }
        @media (max-width: 900px) {
            .report-head { display: block; }
            .export-menu { margin-top: 16px; }
            .filter-row { flex-wrap: wrap; }
            .report-table { min-width: 760px; }
            .table-scroll { overflow-x: auto; }
        }
CSS;
}

function reportExportMenu(
    $reportType,
    $filters = []
) {

    // Export Request Parameters
    // Keeps the selected report type and filters attached to CSV, Excel, and PDF exports.
    $queryParts = [
        "reportType" => $reportType
    ];

    foreach ($filters as $key => $value) {

        if ((string) $value !== "") {

            $queryParts[$key] = $value;
        }
    }

    $baseQuery =
        http_build_query(
            $queryParts
        );

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
    // Loads the printable PDF view in a hidden iframe so the current report page remains open.
    return "
        <script>
            function prepareReportExport(form) {
                const formatSelect = form.querySelector('select[name=\"format\"]');
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
            }
        </script>
    ";
}

function reportHiddenInputs(
    $filters
) {

    // Hidden Filter Fields
    // Converts the active screen filters into hidden form inputs for export requests.
    $html = "";

    foreach ($filters as $key => $value) {

        $html .= "<input type=\"hidden\" name=\"" . e($key) . "\" value=\"" . e($value) . "\">";
    }

    return $html;
}

function reportInitials(
    $name
) {

    // Avatar Initials Helper
    // Produces compact initials for student chips in report tables.
    $parts =
        preg_split(
            "/\s+/",
            trim((string) $name)
        );

    return
        strtoupper(substr($parts[0] ?? "S", 0, 1))
        .
        strtoupper(substr($parts[1] ?? "", 0, 1));
}

function reportSemesterLabel(
    $semester
) {

    // Semester Display Helper
    // Converts stored semester filter values into user-facing labels.
    if ($semester === "1") {

        return "Semester 1";
    }

    if ($semester === "2") {

        return "Semester 2";
    }

    if ($semester === "3") {

        return "Semester 3";
    }

    return "All Semesters";
}

function reportYearLabel(
    $year
) {

    // Year Display Helper
    // Keeps empty year filters visible as the all-years state in pills and exports.
    return
        trim((string) $year) === ""
        ? "All Years"
        : (string) $year;
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
