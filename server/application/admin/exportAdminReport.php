<?php

require_once __DIR__ . "/../auth/SessionManager.php";
require_once __DIR__ . "/../../business/services/AdminReportFacade.php";

SessionManager::startSession();
SessionManager::requireRole("Administrator");

/*
|--------------------------------------------------------------------------
| Export Helpers
|--------------------------------------------------------------------------
| Shared response helpers for CSV, Excel-compatible HTML, and printable PDF.
*/

function e($value) {

    return
        htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function exportFilename($reportType, $extension) {

    return
        "admin_" . $reportType . "_report_" . date("Ymd_His") . "." . $extension;
}

function sendCsv($filename, $headers, $rows) {

    header("Content-Type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"{$filename}\"");

    $output =
        fopen("php://output", "w");

    fputcsv($output, $headers);

    foreach ($rows as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

function sendExcel($filename, $title, $headers, $rows) {

    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"{$filename}\"");

    echo "<table border=\"1\">";
    echo "<tr><th colspan=\"" . count($headers) . "\">" . e($title) . "</th></tr>";
    echo "<tr>";

    foreach ($headers as $header) {
        echo "<th>" . e($header) . "</th>";
    }

    echo "</tr>";

    foreach ($rows as $row) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . e($value) . "</td>";
        }
        echo "</tr>";
    }

    echo "</table>";
    exit;
}

function renderPrintPage($title, $summaryHtml, $tableHeaders, $tableRows) {

    // Printable report layout used only inside the hidden iframe export flow.
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title><?php echo e($title); ?></title>
        <style>
            body { margin: 0; padding: 30px; font-family: Arial, Helvetica, sans-serif; color: #10263d; }
            .head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 4px solid #0d5be8; padding-bottom: 14px; margin-bottom: 18px; }
            h1 { margin: 0; font-size: 24px; }
            .brand { color: #526a7f; font-size: 12px; text-align: right; line-height: 1.45; }
            .summary { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin: 18px 0; }
            .box { border: 1px solid #d9e7f3; background: #f8fbff; padding: 12px; border-radius: 8px; }
            .label { color: #7c8da0; font-size: 10px; text-transform: uppercase; font-weight: 900; letter-spacing: 1px; }
            .value { color: #0d5be8; font-size: 24px; font-weight: 900; margin-top: 6px; }
            .bar { height: 8px; border-radius: 999px; background: #e8eef5; overflow: hidden; margin-top: 10px; }
            .bar span { display: block; height: 100%; background: #0d5be8; }
            table { width: 100%; border-collapse: collapse; margin-top: 18px; }
            th, td { border: 1px solid #d9e7f3; padding: 9px; text-align: left; font-size: 11px; }
            th { background: #f1f5fb; text-transform: uppercase; font-size: 9px; letter-spacing: .8px; }
            @media print { body { padding: 20px; } }
        </style>
    </head>
    <body>
        <div class="head">
            <h1><?php echo e($title); ?></h1>
            <div class="brand">TAR UMT SSAS<br>Generated <?php echo e(date("Y-m-d H:i")); ?></div>
        </div>

        <?php echo $summaryHtml; ?>

        <table>
            <thead>
                <tr>
                    <?php foreach ($tableHeaders as $header): ?>
                        <th><?php echo e($header); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tableRows as $row): ?>
                    <tr>
                        <?php foreach ($row as $value): ?>
                            <td><?php echo e($value); ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <script>
            window.addEventListener("load", function() {
                window.focus();
                window.print();
            });
        </script>
    </body>
    </html>
    <?php
    exit;
}

$reportType =
    trim($_GET["reportType"] ?? "");

$format =
    strtolower(
        trim($_GET["format"] ?? "pdf")
    );

if (
    !in_array($reportType, ["cohort", "allocation"], true)
    ||
    !in_array($format, ["pdf", "csv", "xls"], true)
) {

    http_response_code(400);
    echo "Invalid admin report request.";
    exit;
}

// The export endpoint reuses the same facade as the on-screen report pages.
$facade =
    new AdminReportFacade();

/*
|--------------------------------------------------------------------------
| Cohort Overview Export
|--------------------------------------------------------------------------
| Exports the UC300 filtered roster using the same facade data as the UI.
*/

if ($reportType === "cohort") {

    $filters = [
        "programme" => trim($_GET["programme"] ?? ""),
        "specialization" => trim($_GET["specialization"] ?? ""),
        "batch" => trim($_GET["batch"] ?? ""),
        "status" => strtolower(trim($_GET["status"] ?? ""))
    ];

    if (!in_array($filters["status"], ["assigned", "unassigned", ""], true)) {

        $filters["status"] = "";
    }

    $report =
        $facade->getCohortOverview($filters);

    $headers = [
        "studentID",
        "fullName",
        "programme",
        "currentSem",
        "specialization",
        "allocationStatus",
        "supervisorID",
        "supervisorName"
    ];

    $rows = [];

    foreach ($report["students"] as $student) {
        $assigned = !empty($student["allocationID"]);
        $rows[] = [
            $student["studentID"],
            $student["fullName"],
            $student["programme"],
            $student["currentSem"],
            $student["specializations"] ?: "No specialization",
            $assigned ? "Assigned" : "Unassigned",
            $assigned ? $student["supervisorID"] : "",
            $assigned ? $student["supervisorName"] : ""
        ];
    }

    if ($format === "csv") {
        sendCsv(exportFilename("cohort_overview", "csv"), $headers, $rows);
    }

    if ($format === "xls") {
        sendExcel(exportFilename("cohort_overview", "xls"), "Cohort Overview", $headers, $rows);
    }

    $summaryHtml = "
        <div class=\"summary\">
            <div class=\"box\"><div class=\"label\">Total Students</div><div class=\"value\">" . e($report["totalStudents"]) . "</div></div>
            <div class=\"box\"><div class=\"label\">Allocated</div><div class=\"value\">" . e($report["allocatedStudents"]) . "</div></div>
            <div class=\"box\"><div class=\"label\">Unassigned</div><div class=\"value\">" . e($report["unassignedStudents"]) . "</div></div>
        </div>
        <div class=\"box\"><div class=\"label\">Allocation Progress</div><div class=\"value\">" . e($report["allocationProgress"]) . "%</div><div class=\"bar\"><span style=\"width:" . e(min($report["allocationProgress"], 100)) . "%\"></span></div><div class=\"label\">Filtered " . e($report["totalStudents"]) . " of " . e($report["systemTotalStudents"]) . " active students</div></div>
    ";

    renderPrintPage("Cohort Overview", $summaryHtml, $headers, $rows);
}

/*
|--------------------------------------------------------------------------
| Allocation Summary Export
|--------------------------------------------------------------------------
| Exports the UC301 supervisor workload report using the same facade data as
| the UI.
*/

if ($reportType === "allocation") {

    $programme =
        trim($_GET["programme"] ?? "");

    $report =
        $facade->getAllocationSummary($programme);

    $headers = [
        "supervisorID",
        "fullName",
        "programme",
        "quotaTierName",
        "currentTotal",
        "maxSuperviseesAllowed",
        "fillRate",
        "capacityStatus"
    ];

    $rows = [];

    foreach ($report["supervisors"] as $supervisor) {
        $rows[] = [
            $supervisor["supervisorID"],
            $supervisor["fullName"],
            $supervisor["programme"],
            $supervisor["quotaTierName"],
            $supervisor["currentTotal"],
            $supervisor["maxSuperviseesAllowed"],
            $supervisor["fillRate"] . "%",
            $supervisor["capacityStatus"]
        ];
    }

    if ($format === "csv") {
        sendCsv(exportFilename("allocation_summary", "csv"), $headers, $rows);
    }

    if ($format === "xls") {
        sendExcel(exportFilename("allocation_summary", "xls"), "Allocation Summary", $headers, $rows);
    }

    $summaryHtml = "
        <div class=\"summary\">
            <div class=\"box\"><div class=\"label\">Slot Utilization</div><div class=\"value\">" . e($report["slotUtilization"]) . "%</div><div class=\"bar\"><span style=\"width:" . e(min($report["slotUtilization"], 100)) . "%\"></span></div></div>
            <div class=\"box\"><div class=\"label\">Supervisors at Capacity</div><div class=\"value\">" . e($report["atCapacity"]) . "</div></div>
            <div class=\"box\"><div class=\"label\">Pending Requests</div><div class=\"value\">" . e($report["pendingRequests"]) . "</div></div>
        </div>
    ";

    renderPrintPage("Allocation Summary", $summaryHtml, $headers, $rows);
}

?>
