<?php

require_once __DIR__ . "/../auth/SessionManager.php";
require_once __DIR__ . "/../../business/services/SupervisorReportFacade.php";

/*
|--------------------------------------------------------------------------
| Supervisor Export Access Control
|--------------------------------------------------------------------------
| Start session and ensure only supervisors can export reports.
*/

SessionManager::startSession();
SessionManager::requireRole("Supervisor");

header("X-Content-Type-Options: nosniff");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

/*
|--------------------------------------------------------------------------
| Export Request Retrieval
|--------------------------------------------------------------------------
| Retrieve selected report type and export format from URL.
*/

$reportType =
    trim(
        $_GET["reportType"] ?? ""
    );

$format =
    trim(
        $_GET["format"] ?? "csv"
    );

/*
|--------------------------------------------------------------------------
| Allowed Export Configuration
|--------------------------------------------------------------------------
| Define supported report types and export formats.
*/

$allowedReports =
    [
        "demographics",
        "history",
        "utilization"
    ];

$allowedFormats =
    [
        "csv",
        "xls",
        "pdf"
    ];

/*
|--------------------------------------------------------------------------
| Request Validation
|--------------------------------------------------------------------------
| Reject invalid report types or unsupported export formats.
*/

if (!in_array($reportType, $allowedReports, true) || !in_array($format, $allowedFormats, true)) {

    header(
        "Location: ../../../client/supervisor/supervisorApplicantDemographics.php?status=error&message=Invalid report export request"
    );

    exit();
}

/*
|--------------------------------------------------------------------------
| Report Facade Initialization
|--------------------------------------------------------------------------
| Create facade object to retrieve supervisor report data.
*/

$reportFacade =
    new SupervisorReportFacade();

/*
|--------------------------------------------------------------------------
| Export Dataset Initialization
|--------------------------------------------------------------------------
| Prepare rows array and report title for export output.
*/

$rows =
    [];

$title =
    "";

$emptyDetail =
    "No report records are available for the selected filter.";

/*
|--------------------------------------------------------------------------
| Demographic Report Export
|--------------------------------------------------------------------------
| Export applicant demographic distribution by programme.
*/

if ($reportType === "demographics") {

    // Retrieve selected year filter
    $year =
        trim(
            $_GET["year"] ?? ""
        );

    // Retrieve demographic report data
    $report =
        $reportFacade
        ->getDemographicData(
            $_SESSION["userID"],
            $year
        );

    $emptyDetail =
        $report["message"] !== ""
        ? $report["message"]
        : $emptyDetail;

    // Report title
    $title =
        "Applicant Demographic Chart";

    /*
    |--------------------------------------------------------------------------
    | Dataset Mapping
    |--------------------------------------------------------------------------
    | Convert programme data into exportable table rows.
    */

    foreach ($report["programmes"] as $programme) {

        $rows[] = [
            "Programme" => $programme["programme"],
            "Applicants" => $programme["count"],
            "Percentage" => $programme["percentage"] . "%"
        ];
    }

/*
|--------------------------------------------------------------------------
| Supervision History Export
|--------------------------------------------------------------------------
| Export archived supervision records.
*/

} elseif ($reportType === "history") {

    // Retrieve filters
    $year =
        trim(
            $_GET["year"] ?? ""
        );

    $semester =
        trim(
            $_GET["semester"] ?? ""
        );

    // Retrieve history report data
    $report =
        $reportFacade
        ->getSupervisionHistory(
            $_SESSION["userID"],
            $year,
            $semester
        );

    $emptyDetail =
        $report["message"] !== ""
        ? $report["message"]
        : $emptyDetail;

    // Report title
    $title =
        "Supervision History Log";

    /*
    |--------------------------------------------------------------------------
    | Dataset Mapping
    |--------------------------------------------------------------------------
    | Convert history records into exportable rows.
    */

    foreach ($report["records"] as $record) {

        $semesterLabel =
            $record["currentSem"] ?? "";

        if (preg_match('/S\s*([0-9]+)/i', (string) $semesterLabel, $matches)) {

            $semesterLabel =
                "Semester " . $matches[1];
        }

        $rows[] = [
            "Year" => $record["completionYear"],
            "Semester" => $semesterLabel,
            "Student Name" => $record["alumniName"],
            "Project Title" => $record["projectTitle"],
            "Status" => $record["statusLabel"] ?? "Active"
        ];
    }

} else {

/*
|--------------------------------------------------------------------------
| Slot Utilization Export
|--------------------------------------------------------------------------
| Export quota utilization statistics and performance metrics.
*/

    // Retrieve utilization report
    $report =
        $reportFacade
        ->getUtilizationStats(
            $_SESSION["userID"]
        );

    // Report title
    $title =
        "Slot Utilization Tracker";

    /*
    |--------------------------------------------------------------------------
    | Dataset Mapping
    |--------------------------------------------------------------------------
    | Convert utilization metrics into exportable rows.
    */
    $rows[] = [
        "Metric" => "Current Slots",
        "Value" => $report["currentSlots"]
    ];

    $rows[] = [
        "Metric" => "Quota",
        "Value" => $report["quota"]
    ];

    $rows[] = [
        "Metric" => "Fill Rate",
        "Value" => $report["fillRate"] . "%"
    ];

    $rows[] = [
        "Metric" => "Department Average",
        "Value" => $report["departmentAverage"] . "%"
    ];

    $rows[] = [
        "Metric" => "Available Slots",
        "Value" => $report["availableSlots"]
    ];
}
/*
|--------------------------------------------------------------------------
| Empty Dataset Handling
|--------------------------------------------------------------------------
| Show default row when no report data is available.
*/

if (empty($rows)) {

    $rows[] = [
        "Message" => "No Data",
        "Detail" => $emptyDetail
    ];
}

/*
|--------------------------------------------------------------------------
| Export Filename Generation
|--------------------------------------------------------------------------
| Generate timestamped filename for exported report.
*/

$filename =
    $reportType
    . "_report_"
    . date("Ymd_His");

/*
|--------------------------------------------------------------------------
| CSV Export
|--------------------------------------------------------------------------
| Stream report as downloadable CSV file.
*/

if ($format === "csv") {

    // CSV response headers
    header("Content-Type: text/csv; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");

    // Open output stream
    $output =
        fopen(
            "php://output",
            "w"
        );

    // Write table headings
    fputcsv(
        $output,
        array_keys($rows[0])
    );

    // Write each row
    foreach ($rows as $row) {

        fputcsv(
            $output,
            $row
        );
    }

    // Close output stream
    fclose($output);
    exit();
}

/*
|--------------------------------------------------------------------------
| Excel Export
|--------------------------------------------------------------------------
| Export report as HTML table with Excel MIME type.
*/

if ($format === "xls") {

    // Excel response headers
    header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"{$filename}.xls\"");

    // Output export table
    echo renderExportTable(
        $title,
        $rows
    );

    exit();
}

/*
|--------------------------------------------------------------------------
| PDF Print View
|--------------------------------------------------------------------------
| Render printable HTML report for browser PDF export.
*/

header("Content-Type: text/html; charset=UTF-8");
header("Content-Disposition: inline; filename=\"{$filename}.html\"");

// Render printable report
echo renderPrintableReport(
    $title,
    $rows,
    $reportType,
    $report
);

exit();

/*
|--------------------------------------------------------------------------
| HTML Escape Helper
|--------------------------------------------------------------------------
| Safely escape values before rendering into HTML.
*/

function h(
    $value
) {

    // HTML Escape Helper
    // Escapes table and visual content before it is written into export markup.
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}

/*
|--------------------------------------------------------------------------
| Export Table Renderer
|--------------------------------------------------------------------------
| Generate reusable HTML table for Excel and printable reports.
*/

function renderExportTable(
    $title,
    $rows
) {

    $html =
        "<table border=\"1\"><thead><tr><th colspan=\"" . count($rows[0]) . "\">" . h($title) . "</th></tr><tr>";

    foreach (array_keys($rows[0]) as $heading) {

        $html .= "<th>" . h($heading) . "</th>";
    }

    $html .= "</tr></thead><tbody>";

    foreach ($rows as $row) {

        $html .= "<tr>";

        foreach ($row as $value) {

            $html .= "<td>" . h($value) . "</td>";
        }

        $html .= "</tr>";
    }

    return $html . "</tbody></table>";
}

/*
|--------------------------------------------------------------------------
| Printable Report Renderer
|--------------------------------------------------------------------------
| Build complete printable HTML report layout.
*/

function renderPrintableReport(
    $title,
    $rows,
    $reportType,
    $report
) {
    // Generate matching chart or visual
    $visual =
        renderPrintableVisual(
            $reportType,
            $report
        );

    return "
        <!DOCTYPE html>
        <html lang=\"en\">
        <head>
            <meta charset=\"UTF-8\">
            <title>" . h($title) . "</title>
            <style>
                body { font-family: Arial, Helvetica, sans-serif; margin: 34px; color: #172033; }
                .header { display: flex; justify-content: space-between; align-items: start; border-bottom: 3px solid #0d5be8; padding-bottom: 14px; margin-bottom: 22px; }
                h1 { margin: 0; font-size: 24px; }
                .meta { color: #526a7f; font-size: 12px; text-align: right; line-height: 1.5; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #dbe6f0; padding: 10px; text-align: left; font-size: 12px; }
                th { background: #eef3f8; text-transform: uppercase; letter-spacing: .6px; }
                .visual-card { border: 1px solid #dbe6f0; border-radius: 10px; padding: 20px; margin-bottom: 22px; page-break-inside: avoid; }
                .visual-title { margin: 0 0 12px; font-size: 16px; color: #172033; }
                .visual-grid { display: grid; grid-template-columns: 280px 1fr; gap: 24px; align-items: center; }
                .legend-list { display: grid; gap: 10px; }
                .legend-line { display: grid; grid-template-columns: 12px minmax(0, 1fr) auto; gap: 10px; align-items: center; font-size: 12px; }
                .legend-dot { width: 10px; height: 10px; border-radius: 50%; }
                .legend-name { font-weight: 700; }
                .legend-muted { color: #64748b; font-size: 11px; }
                .legend-percent { font-size: 16px; font-weight: 800; }
                .bar-legend { display: flex; justify-content: flex-end; gap: 18px; margin-bottom: 8px; color: #64748b; font-size: 11px; font-weight: 800; text-transform: uppercase; }
                .bar-key { display: inline-flex; align-items: center; gap: 6px; }
                .bar-swatch { width: 11px; height: 11px; border-radius: 3px; display: inline-block; }
                .bar-swatch.department { background: #cbd5e1; }
                .bar-swatch.personal { background: #0d5be8; }
                .print-note { margin-top: 18px; color: #526a7f; font-size: 12px; }
                @media print { .print-note { display: none; } body { margin: 18mm; } .visual-card { break-inside: avoid; } }
            </style>
        </head>
        <body>
            <section class=\"header\">
                <h1>" . h($title) . "</h1>
                <div class=\"meta\">
                    TAR UMT SSAS<br>
                    Generated " . h(date("Y-m-d H:i")) . "
                </div>
            </section>
            {$visual}
            " . renderExportTable($title, $rows) . "
            <p class=\"print-note\">Use the browser print dialog and choose Save as PDF for official documentation.</p>
            <script>window.print();</script>
        </body>
        </html>
    ";
}

/*
|--------------------------------------------------------------------------
| Printable Visual Router
|--------------------------------------------------------------------------
| Select printable chart based on report type.
*/

function renderPrintableVisual(
    $reportType,
    $report
) {

    if ($reportType === "demographics") {

        return renderDemographicChart(
            $report
        );
    }

    if ($reportType === "utilization") {

        return renderUtilizationChart(
            $report
        );
    }

    return "";
}

/*
|--------------------------------------------------------------------------
| Chart Color Palette
|--------------------------------------------------------------------------
| Define reusable colors for printable charts.
*/

function chartPalette() {

    return [
        "#0d5be8",
        "#334155",
        "#b8c7de",
        "#dbe3ef",
        "#14b8a6",
        "#f59e0b"
    ];
}

/*
|--------------------------------------------------------------------------
| Demographic Donut Chart Renderer
|--------------------------------------------------------------------------
| Generate printable SVG donut chart for programme distribution.
*/

function renderDemographicChart(
    $report
) {

    $programmes =
        $report["programmes"] ?? [];

    $total =
        (int) ($report["totalApplicants"] ?? 0);

    if ($total === 0 || empty($programmes)) {

        return "";
    }

    $palette =
        chartPalette();

    $radius = 82;
    $circumference =
        2 * pi() * $radius;

    $offset = 0;
    $circles = "";
    $legend = "";

    /*
    |--------------------------------------------------------------------------
    | Donut Segment Generation
    |--------------------------------------------------------------------------
    | Convert percentages into SVG circle segments.
    */

    foreach ($programmes as $index => $programme) {

        $percentage =
            (float) $programme["percentage"];

        $dash =
            ($percentage / 100) * $circumference;

        $gap =
            $circumference - $dash;

        $color =
            $palette[$index % count($palette)];

        $circles .= "
            <circle cx=\"120\" cy=\"120\" r=\"{$radius}\" fill=\"none\" stroke=\"" . h($color) . "\" stroke-width=\"28\"
                stroke-dasharray=\"" . h(round($dash, 2)) . " " . h(round($gap, 2)) . "\"
                stroke-dashoffset=\"" . h(round(-$offset, 2)) . "\"
                transform=\"rotate(-90 120 120)\" />
        ";

        $offset += $dash;

        $legend .= "
            <div class=\"legend-line\">
                <span class=\"legend-dot\" style=\"background:" . h($color) . ";\"></span>
                <div>
                    <div class=\"legend-name\">" . h($programme["programme"]) . "</div>
                    <div class=\"legend-muted\">" . h($programme["count"]) . " applicant(s)</div>
                </div>
                <div class=\"legend-percent\">" . h($programme["percentage"]) . "%</div>
            </div>
        ";
    }

    return "
        <section class=\"visual-card\">
            <h2 class=\"visual-title\">Programme Distribution</h2>
            <div class=\"visual-grid\">
                <svg width=\"240\" height=\"240\" viewBox=\"0 0 240 240\" role=\"img\" aria-label=\"Applicant programme distribution donut chart\">
                    <circle cx=\"120\" cy=\"120\" r=\"{$radius}\" fill=\"none\" stroke=\"#edf2f7\" stroke-width=\"28\" />
                    {$circles}
                    <circle cx=\"120\" cy=\"120\" r=\"54\" fill=\"#ffffff\" />
                    <text x=\"120\" y=\"114\" text-anchor=\"middle\" font-size=\"32\" font-weight=\"800\" fill=\"#172033\">" . h(number_format($total)) . "</text>
                    <text x=\"120\" y=\"137\" text-anchor=\"middle\" font-size=\"10\" font-weight=\"800\" fill=\"#64748b\">TOTAL APPLICANTS</text>
                </svg>
                <div class=\"legend-list\">
                    {$legend}
                </div>
            </div>
        </section>
    ";
}

/*
|--------------------------------------------------------------------------
| Utilization Chart Renderer
|--------------------------------------------------------------------------
| Generate printable SVG bar chart for weekly utilization trends.
*/

function renderUtilizationChart(
    $report
) {

    $trend =
        $report["weeklyTrend"] ?? [];

    if (empty($trend)) {

        return "";
    }

    $maxValue = 1;

    /*
    |--------------------------------------------------------------------------
    | Chart Scaling
    |--------------------------------------------------------------------------
    | Find maximum value for shared bar chart scale.
    */

    foreach ($trend as $day) {

        $maxValue =
            max(
                $maxValue,
                (float) $day["departmentAverage"],
                (float) $day["personal"]
            );
    }

    $svgWidth = 700;
    $svgHeight = 260;
    $chartTop = 24;
    $chartBottom = 205;
    $chartHeight =
        $chartBottom - $chartTop;

    $groupWidth =
        $svgWidth / max(1, count($trend));

    $bars = "";
    $labels = "";

    foreach ($trend as $index => $day) {

        // Weekly Bar Pair
        // Places department average on the left and personal value on the right for each day.
        $center =
            ($index * $groupWidth)
            + ($groupWidth / 2);

        $departmentHeight =
            ((float) $day["departmentAverage"] / $maxValue)
            * $chartHeight;

        $personalHeight =
            ((float) $day["personal"] / $maxValue)
            * $chartHeight;

        $departmentHeight =
            (float) $day["departmentAverage"] > 0
            ? max(4, $departmentHeight)
            : 0;

        $personalHeight =
            (float) $day["personal"] > 0
            ? max(4, $personalHeight)
            : 0;

        $departmentY =
            $chartBottom - $departmentHeight;

        $personalY =
            $chartBottom - $personalHeight;

        $bars .= "
            <rect x=\"" . h(round($center - 12, 1)) . "\" y=\"" . h(round($departmentY, 1)) . "\" width=\"9\" height=\"" . h(round($departmentHeight, 1)) . "\" rx=\"4\" fill=\"#cbd5e1\" />
            <rect x=\"" . h(round($center + 3, 1)) . "\" y=\"" . h(round($personalY, 1)) . "\" width=\"9\" height=\"" . h(round($personalHeight, 1)) . "\" rx=\"4\" fill=\"#0d5be8\" />
        ";

        $labels .= "
            <text x=\"" . h(round($center, 1)) . "\" y=\"236\" text-anchor=\"middle\" font-size=\"10\" font-weight=\"800\" fill=\"#64748b\">" . h($day["label"]) . "</text>
        ";
    }

    return "
        <section class=\"visual-card\">
            <h2 class=\"visual-title\">Weekly Slot Trends</h2>
            <div class=\"bar-legend\">
                <span class=\"bar-key\"><span class=\"bar-swatch department\"></span>Dept Avg</span>
                <span class=\"bar-key\"><span class=\"bar-swatch personal\"></span>Personal</span>
            </div>
            <svg width=\"100%\" height=\"260\" viewBox=\"0 0 {$svgWidth} {$svgHeight}\" role=\"img\" aria-label=\"Weekly slot trend bar chart\">
                <line x1=\"20\" y1=\"{$chartBottom}\" x2=\"680\" y2=\"{$chartBottom}\" stroke=\"#dbe6f0\" stroke-width=\"1\" />
                {$bars}
                {$labels}
            </svg>
        </section>
    ";
}

?>
