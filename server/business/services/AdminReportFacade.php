<?php

/*
|--------------------------------------------------------------------------
| Required Dependencies
|--------------------------------------------------------------------------
| Loads the report DAO and repository classes used to collect report data.
*/
require_once __DIR__ . "/../../data/dao/AdminReportDAO.php";
require_once __DIR__ . "/StudentRepository.php";
require_once __DIR__ . "/SupervisorRepository.php";
require_once __DIR__ . "/AllocationRepository.php";

/*
|--------------------------------------------------------------------------
| Admin Report Facade
|--------------------------------------------------------------------------
| Provides a simplified interface for administrator report pages.
| It combines student, supervisor, and allocation data into report-ready arrays.
*/
class AdminReportFacade {

    /*
    |--------------------------------------------------------------------------
    | Repository Dependencies
    |--------------------------------------------------------------------------
    */
    private $studentRepo;

    private $supervisorRepo;

    private $allocationRepo;

    private $reportDAO;

    /*
    |--------------------------------------------------------------------------
    | Facade Wiring
    |--------------------------------------------------------------------------
    | Centralizes report data access behind repositories, so UI pages do not
    | query the database directly.
    */
    public function __construct() {

        $this->reportDAO =
            new AdminReportDAO();

        $this->studentRepo =
            new StudentRepository($this->reportDAO);

        $this->supervisorRepo =
            new SupervisorRepository($this->reportDAO);

        $this->allocationRepo =
            new AllocationRepository($this->reportDAO);
    }

    /*
    |--------------------------------------------------------------------------
    | UC300 - Generate Cohort Overview
    |--------------------------------------------------------------------------
    | Compiles filtered student data, allocation totals, and dropdown options
    | for the administrator cohort overview report.
    */
    public function getCohortOverview($filters = []) {

        /*
        |--------------------------------------------------------------------------
        | Normalize UC300 Filters
        |--------------------------------------------------------------------------
        | Keeps the facade as the single validation point before repositories fetch
        | cohort data from the Student/User/Allocation stores.
        */
        $filters =
            $this->normaliseCohortFilters($filters);

        /*
        |--------------------------------------------------------------------------
        | Fetch Filtered Student Records
        |--------------------------------------------------------------------------
        */
        $students =
            $this->studentRepo->fetchStudents($filters);

        $allStudents =
            $this->studentRepo->fetchStudents([]);

        /*
        |--------------------------------------------------------------------------
        | Calculate Cohort Totals
        |--------------------------------------------------------------------------
        */
        $total =
            count($students);

        $allocated =
            count(
                array_filter(
                    $students,
                    function ($student) {

                        return
                            !empty($student["allocationID"]);
                    }
                )
            );

        $unassigned =
            $total - $allocated;

        /*
        |--------------------------------------------------------------------------
        | Build Cohort Overview Result
        |--------------------------------------------------------------------------
        */
        return [
            "students" => $students,

            "totalStudents" => $total,

            "systemTotalStudents" =>
                count($allStudents),

            "allocatedStudents" => $allocated,

            "unassignedStudents" => $unassigned,

            "allocationProgress" =>
                $total > 0
                    ? round(($allocated / $total) * 100, 1)
                    : 0,

            "programmeOptions" =>
                $this->reportDAO->getProgrammeOptions(),

            "batchOptions" =>
                $this->reportDAO->getBatchOptions(),

            "specializationOptions" =>
                $this->reportDAO->getSpecializationOptions(),

            "message" =>
                $total === 0
                    ? "M1: Filtered Result Empty = No student records match the selected criteria."
                    : ""
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | UC301 - Generate Allocation Summary
    |--------------------------------------------------------------------------
    | Compiles supervisor capacity metrics and highlights workload risks.
    */
    public function getAllocationSummary($programme = "") {

        $programme =
            trim((string) $programme);

        /*
        |--------------------------------------------------------------------------
        | Fetch Supervisor Records
        |--------------------------------------------------------------------------
        */
        $supervisors =
            $this->supervisorRepo->fetchSupervisors($programme);

        /*
        |--------------------------------------------------------------------------
        | Initialize Summary Counters
        |--------------------------------------------------------------------------
        */
        $totalCapacity = 0;
        $allocatedTotal = 0;
        $atCapacity = 0;

        /*
        |--------------------------------------------------------------------------
        | Calculate Supervisor Capacity Metrics
        |--------------------------------------------------------------------------
        */
        foreach ($supervisors as &$supervisor) {

            $quota =
                $this->supervisorRepo->getQuotaLimit($supervisor);

            $current =
                (int) ($supervisor["currentTotal"] ?? 0);

            $fillRate =
                $this->allocationRepo->calculateFillRate(
                    $current,
                    $quota
                );

            $supervisor["fillRate"] =
                $fillRate;

            /*
            |--------------------------------------------------------------------------
            | Assign Capacity Status
            |--------------------------------------------------------------------------
            */
            $supervisor["capacityStatus"] =
                $quota > 0 && $current >= $quota
                    ? "Full Capacity"
                    : ($fillRate >= 80 ? "High Usage" : "Available");

            $totalCapacity +=
                $quota;

            $allocatedTotal +=
                $current;

            if ($quota > 0 && $current >= $quota) {

                $atCapacity++;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Clear Reference Variable
        |--------------------------------------------------------------------------
        | Prevents accidental modification after foreach by reference.
        */
        unset($supervisor);

        /*
        |--------------------------------------------------------------------------
        | Count Pending Requests
        |--------------------------------------------------------------------------
        */
        $pendingRequests =
            $this->reportDAO->countPendingRequests($programme);

        /*
        |--------------------------------------------------------------------------
        | Build Allocation Summary Result
        |--------------------------------------------------------------------------
        */
        return [
            "supervisors" => $supervisors,

            "totalCapacity" => $totalCapacity,

            "allocatedTotal" => $allocatedTotal,

            "slotUtilization" =>
                $totalCapacity > 0
                    ? round(($allocatedTotal / $totalCapacity) * 100, 1)
                    : 0,

            "atCapacity" => $atCapacity,

            "pendingRequests" => $pendingRequests,

            "programmeOptions" =>
                $this->reportDAO->getProgrammeOptions(),

            "message" =>
                empty($supervisors)
                    ? "M1: No Record = No supervisor data available."
                    : ""
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Dashboard Programme Distribution
    |--------------------------------------------------------------------------
    | Uses allocated students as the source of truth so the dashboard reflects
    | the actual student roster programme mix.
    */

    public function getAllocatedStudentProgrammeDistribution() {

        return
            $this->reportDAO
            ->fetchAllocatedStudentProgrammeDistribution();
    }

    private function normaliseCohortFilters(
        $filters
    ) {

        $status =
            strtolower(
                trim((string) ($filters["status"] ?? ""))
            );

        if (!in_array($status, ["assigned", "unassigned"], true)) {

            $status =
                "";
        }

        return [
            "programme" => trim((string) ($filters["programme"] ?? "")),
            "specialization" => trim((string) ($filters["specialization"] ?? "")),
            "batch" => trim((string) ($filters["batch"] ?? "")),
            "status" => $status
        ];
    }
}

?>
