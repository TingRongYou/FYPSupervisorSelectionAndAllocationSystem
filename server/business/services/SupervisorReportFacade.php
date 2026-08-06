<?php

require_once __DIR__ . "/../../data/dao/SupervisorReportDAO.php";
require_once __DIR__ . "/StudentTrackingSystem.php";
require_once __DIR__ . "/SupervisionArchive.php";
require_once __DIR__ . "/QuotaAnalytics.php";

/*
|--------------------------------------------------------------------------
| Supervisor Report Facade
|--------------------------------------------------------------------------
| Coordinates the student tracking, supervision archive, and quota analytics
| subsystems behind one report-facing API.
*/

class SupervisorReportFacade {

    private $studentSys;

    private $historySys;

    private $quotaSys;

    private $reportDAO;

    /*
    |--------------------------------------------------------------------------
    | Filter Normalization
    |--------------------------------------------------------------------------
    | Keeps page views and exports using the same accepted filter values before
    | any subsystem or DAO receives user-supplied input.
    */

    private function normalizeYear($year) {

        $year = trim((string) $year);

        return preg_match("/^[0-9]{4}$/", $year) ? $year : "";
    }

    private function normalizeSemester($semester) {

        $semester = trim((string) $semester);

        return in_array($semester, ["1", "2", "3"], true) ? $semester : "";
    }

    /*
    |--------------------------------------------------------------------------
    | Facade Wiring
    |--------------------------------------------------------------------------
    | Initializes the subsystems shown in the supervisor report class diagram.
    */

    public function __construct() {

        $this->reportDAO = new SupervisorReportDAO();

        $this->studentSys = new StudentTrackingSystem($this->reportDAO);

        $this->historySys = new SupervisionArchive($this->reportDAO);

        $this->quotaSys = new QuotaAnalytics($this->reportDAO);
    }

    /*
    |--------------------------------------------------------------------------
    | UC600 - Applicant Demographics
    |--------------------------------------------------------------------------
    | Builds programme distribution data and matched expertise tag counts for
    | allocated supervisees shown in the donut-chart report.
    */

    public function getDemographicData($supervisorID, $year = "") {

        $year = $this->normalizeYear($year);

        $rows = $this->studentSys->countByMajor($supervisorID, $year);

        $total =
            array_sum(
                array_map(
                    function ($row) {

                        return (int) $row["totalApplicants"];
                    },
                    $rows
                )
            );

        $programmes = [];

        // Convert raw programme counts into percentages for chart legends.
        foreach ($rows as $row) {

            $count = (int) $row["totalApplicants"];

            $programmes[] = [
                "programme" => $row["programme"],
                "count" => $count,
                "percentage" => $total > 0 ? round(($count / $total) * 100, 1) : 0
            ];
        }

        return [
            "totalApplicants" => $total,
            "programmes" => $programmes,
            "years" => $this->reportDAO->getApplicantYears($supervisorID),
            "expertiseTags" => $this->reportDAO->getMatchedExpertiseTags($supervisorID, $year),
            "selectedYear" => $year,
            "message" => $total === 0
                ? "No Data - No allocated supervisees found. A chart cannot be generated until at least one student is assigned to you."
                : ""
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | UC601 - Supervision History Log
    |--------------------------------------------------------------------------
    | Fetches authoritative allocation records and applies year/semester
    | filters for the supervisor's supervision history.
    */

    public function getSupervisionHistory($supervisorID, $year = "", $semester = "") {

        $year = $this->normalizeYear($year);

        $semester = $this->normalizeSemester($semester);

        $careerTotal = count($this->historySys->getPastStudents($supervisorID, ""));

        $history = $this->historySys->sortByYear($this->historySys->getPastStudents($supervisorID, $year));

        if ($semester !== "") {

            $history =
                array_values(
                    array_filter(
                        $history,
                        function ($record) use ($semester) {

                            $recordSemester =
                                $this->extractSemesterNumber(
                                    $record["currentSem"] ?? ""
                                );

                            return $recordSemester === $semester;
                        }
                    )
                );
        }

        return [
            "records" => $history,
            "years" => $this->reportDAO->getHistoryYears($supervisorID),
            "primaryField" => $this->reportDAO->getPrimaryExpertiseTag($supervisorID),
            "selectedYear" => $year,
            "selectedSemester" => $semester,
            "total" => count($history),
            "careerTotal" => $careerTotal,
            "message" => empty($history)
                ? "No Data - No supervision assignment records found."
                : ""
        ];
    }

    private function extractSemesterNumber($semesterLabel) {

        if (preg_match('/S\s*([0-9]+)/i', (string) $semesterLabel, $matches)) {

            return $matches[1];
        }

        if (preg_match('/([0-9])\s*$/', (string) $semesterLabel, $matches)) {

            return $matches[1];
        }

        return "";
    }

    /*
    |--------------------------------------------------------------------------
    | UC602 - Slot Utilization Tracker
    |--------------------------------------------------------------------------
    | Combines personal quota, department average, and weekly trend data.
    */

    public function getUtilizationStats($supervisorID) {

        $stats = $this->reportDAO->getSlotUtilization($supervisorID);

        if (!$stats) {

            return [
                "currentSlots" => 0,
                "quota" => 0,
                "availableSlots" => 0,
                "fillRate" => 0,
                "departmentAverage" => $this->quotaSys->getDepartmentAverage(),
                "quotaTierName" => "Quota",
                "programme" => "",
                "isFull" => false,
                "weeklyTrend" => $this->buildWeeklyTrend($supervisorID),
                "maxTrendValue" => 1,
                "selectionPeriodStart" => "",
                "message" => "No supervisor quota record found."
            ];
        }

        $current = (int) ($stats["currentSlots"] ?? 0);

        $quota = (int) ($stats["maxSuperviseesAllowed"] ?? 0);

        $available = max(0, $quota - $current);

        $fillRate = $this->quotaSys->getFillRate($supervisorID);

        $departmentAverage = $this->quotaSys->getDepartmentAverage();

        $weeklyTrend = $this->buildWeeklyTrend($supervisorID);

        return [
            "currentSlots" => $current,
            "quota" => $quota,
            "availableSlots" => $available,
            "fillRate" => $fillRate,
            "departmentAverage" => $departmentAverage,
            "quotaTierName" => $stats["quotaTierName"] ?? "Quota",
            "programme" => $stats["programme"] ?? "",
            "isFull" => $quota > 0 && $current >= $quota,
            "weeklyTrend" => $weeklyTrend,
            "maxTrendValue" => $this->getMaxTrendValue($weeklyTrend),
            "selectionPeriodStart" => $this->reportDAO->getSelectionPeriodStartTimestamp(),
            "message" => $quota <= 0
                ? "No supervisor quota record found."
                : (
                    $current < $quota
                    ? "Current fill progress: " . $current . "/" . $quota . " slot(s), work in progress."
                    : ""
                )
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Weekly Trend Builder
    |--------------------------------------------------------------------------
    | Aligns personal and department records to fixed weekday labels for the
    | side-by-side chart.
    */

    private function buildWeeklyTrend($supervisorID) {

        $labels = [
            2 => "MON",
            3 => "TUE",
            4 => "WED",
            5 => "THU",
            6 => "FRI",
            7 => "SAT",
            1 => "SUN"
        ];

        $personalRows = $this->reportDAO->getWeeklyAllocationTrend($supervisorID);

        $departmentRows = $this->reportDAO->getDepartmentWeeklyAverageTrend();

        $personalMap = [];
        $departmentMap = [];

        foreach ($personalRows as $row) {

            $personalMap[(int) $row["weekdayNumber"]] = (int) $row["personalTotal"];
        }

        foreach ($departmentRows as $row) {

            $departmentMap[(int) $row["weekdayNumber"]] = round((float) $row["departmentAverage"], 1);
        }

        $trend = [];

        foreach ($labels as $weekdayNumber => $label) {

            $trend[] = [
                "label" => $label,
                "personal" => $personalMap[$weekdayNumber] ?? 0,
                "departmentAverage" => $departmentMap[$weekdayNumber] ?? 0
            ];
        }

        return $trend;
    }

    /*
    |--------------------------------------------------------------------------
    | Weekly Trend Scale
    |--------------------------------------------------------------------------
    | Finds a shared chart scale so personal and department bars are visually
    | comparable on the utilization screen and PDF output.
    */

    private function getMaxTrendValue($weeklyTrend) {

        $maxValue = 1;

        foreach ($weeklyTrend as $day) {

            $maxValue = max($maxValue, (float) $day["departmentAverage"], (float) $day["personal"]);
        }

        return $maxValue;
    }
}

?>
