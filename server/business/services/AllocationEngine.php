<?php

/*
|--------------------------------------------------------------------------
| Required Dependencies
|--------------------------------------------------------------------------
| Loads the AllocationDAO used for retrieving students, supervisors,
| dashboard summary data, and committing allocation records.
*/
require_once __DIR__ . "/../../data/dao/AllocationDAO.php";
require_once __DIR__ . "/AllocationWindowService.php";

/*
|--------------------------------------------------------------------------
| Allocation Strategy Interface
|--------------------------------------------------------------------------
| Defines the required allocation method for any allocation strategy class.
*/
interface AllocationStrategy {

    public function allocate(
        $students,
        $supervisors
    );
}

/*
|--------------------------------------------------------------------------
| Auto Allocation Engine Strategy
|--------------------------------------------------------------------------
| Automatically assigns eligible unassigned students to supervisors with
| available capacity. Programme matches are preferred, followed by workload.
*/
class AutoAllocationEngineStrategy implements AllocationStrategy {

    /*
    |--------------------------------------------------------------------------
    | Allocate Students
    |--------------------------------------------------------------------------
    | Processes students one by one and assigns each student to the best
    | available supervisor based on score and remaining capacity.
    */
    public function allocate(
        $students,
        $supervisors
    ) {

        /*
        |--------------------------------------------------------------------------
        | Initialize Allocation Result Lists
        |--------------------------------------------------------------------------
        */
        $allocations =
            [];

        $unassigned =
            [];

        /*
        |--------------------------------------------------------------------------
        | Normalize Supervisor Capacity Values
        |--------------------------------------------------------------------------
        */
        foreach ($supervisors as $index => $supervisor) {

            $supervisors[$index]["currentTotal"] =
                (int) $supervisor["currentTotal"];

            $supervisors[$index]["maxSuperviseesAllowed"] =
                (int) $supervisor["maxSuperviseesAllowed"];
        }

        /*
        |--------------------------------------------------------------------------
        | Process Eligible Students
        |--------------------------------------------------------------------------
        */
        foreach ($students as $student) {

            $selectedSupervisorIndex =
                $this->findBestSupervisorIndex(
                    $student,
                    $supervisors
                );

            /*
            |--------------------------------------------------------------------------
            | Handle Unassigned Student
            |--------------------------------------------------------------------------
            */
            if ($selectedSupervisorIndex === null) {

                $unassigned[] =
                    $student;

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Create Allocation Record
            |--------------------------------------------------------------------------
            */
            $allocations[] = [
                "studentID" =>
                    $student["studentID"],

                "supervisorID" =>
                    $supervisors[$selectedSupervisorIndex]["supervisorID"],

                "allocationMethod" =>
                    "System Auto-Match"
            ];

            /*
            |--------------------------------------------------------------------------
            | Update Temporary Supervisor Workload
            |--------------------------------------------------------------------------
            */
            $supervisors[$selectedSupervisorIndex]["currentTotal"]++;
        }

        /*
        |--------------------------------------------------------------------------
        | Return Allocation Result
        |--------------------------------------------------------------------------
        */
        return [
            "allocations" => $allocations,
            "unassigned" => $unassigned
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Find Best Supervisor Index
    |--------------------------------------------------------------------------
    | Finds the most suitable supervisor for a student by comparing programme
    | match score and current workload.
    */
    private function findBestSupervisorIndex(
        $student,
        $supervisors
    ) {

        $bestIndex =
            null;

        foreach ($supervisors as $index => $supervisor) {

            /*
            |--------------------------------------------------------------------------
            | Skip Full Supervisors
            |--------------------------------------------------------------------------
            */
            if (
                $supervisor["currentTotal"]
                >=
                $supervisor["maxSuperviseesAllowed"]
            ) {

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Select First Available Supervisor
            |--------------------------------------------------------------------------
            */
            if ($bestIndex === null) {

                $bestIndex =
                    $index;

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Compare Candidate Score Against Current Best
            |--------------------------------------------------------------------------
            */
            $candidateScore =
                $this->scoreSupervisor(
                    $student,
                    $supervisor
                );

            $bestScore =
                $this->scoreSupervisor(
                    $student,
                    $supervisors[$bestIndex]
                );

            if ($candidateScore > $bestScore) {

                $bestIndex =
                    $index;

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Tie Breaker - Lowest Current Workload
            |--------------------------------------------------------------------------
            */
            if (
                $candidateScore === $bestScore &&
                $supervisor["currentTotal"]
                <
                $supervisors[$bestIndex]["currentTotal"]
            ) {

                $bestIndex =
                    $index;
            }
        }

        return $bestIndex;
    }

    /*
    |--------------------------------------------------------------------------
    | Score Supervisor
    |--------------------------------------------------------------------------
    | Gives higher score to supervisors from the same programme and with more
    | remaining supervision slots.
    */
    private function scoreSupervisor(
        $student,
        $supervisor
    ) {

        $score =
            0;

        /*
        |--------------------------------------------------------------------------
        | Programme Match Score
        |--------------------------------------------------------------------------
        */
        if (
            strtolower(trim($student["programme"]))
            ===
            strtolower(trim($supervisor["programme"]))
        ) {

            $score +=
                10;
        }

        /*
        |--------------------------------------------------------------------------
        | Remaining Slot Score
        |--------------------------------------------------------------------------
        */
        $remainingSlots =
            (int) $supervisor["maxSuperviseesAllowed"] -
            (int) $supervisor["currentTotal"];

        $score +=
            $remainingSlots;

        return $score;
    }
}

/*
|--------------------------------------------------------------------------
| Allocation Engine
|--------------------------------------------------------------------------
| Coordinates allocation dashboard data and executes the selected allocation
| strategy for administrator auto-allocation.
*/
class AllocationEngine {

    /*
    |--------------------------------------------------------------------------
    | Engine Dependencies
    |--------------------------------------------------------------------------
    */
    private $allocationDAO;

    private $strategy;

    private $allocationWindowService;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    | Initializes the allocation DAO and default auto-allocation strategy.
    */
    public function __construct() {

        $this->allocationDAO =
            new AllocationDAO();

        $this->strategy =
            new AutoAllocationEngineStrategy();

        $this->allocationWindowService =
            new AllocationWindowService();
    }

    /*
    |--------------------------------------------------------------------------
    | Set Allocation Strategy
    |--------------------------------------------------------------------------
    | Allows the allocation manager context to switch strategies without
    | changing the controller or dashboard code.
    */
    public function setStrategy(
        AllocationStrategy $strategy
    ) {

        $this->strategy =
            $strategy;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Allocation Dashboard
    |--------------------------------------------------------------------------
    | Retrieves allocation summary metrics for the administrator dashboard.
    */
    public function getAllocationDashboard() {

        /*
        |--------------------------------------------------------------------------
        | Fetch Allocation Summary
        |--------------------------------------------------------------------------
        */
        $summary =
            $this->allocationDAO
                ->getAllocationSummary();

        /*
        |--------------------------------------------------------------------------
        | Normalize Dashboard Metrics
        |--------------------------------------------------------------------------
        */
        $eligibleStudents =
            (int) ($summary["eligibleStudents"] ?? 0);

        $allocatedStudents =
            (int) ($summary["allocatedStudents"] ?? 0);

        $unassignedStudents =
            (int) ($summary["unassignedStudents"] ?? 0);

        $pendingRequests =
            (int) ($summary["pendingRequests"] ?? 0);

        /*
        |--------------------------------------------------------------------------
        | Build Dashboard Result
        |--------------------------------------------------------------------------
        */
        return [
            "eligibleStudents" =>
                $eligibleStudents,

            "allocatedStudents" =>
                $allocatedStudents,

            "unassignedStudents" =>
                $unassignedStudents,

            "pendingRequests" =>
                $pendingRequests,

            "allocationRate" =>
                $eligibleStudents > 0
                    ? round(($allocatedStudents / $eligibleStudents) * 100, 1)
                    : 0
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Execute Auto Allocation
    |--------------------------------------------------------------------------
    | Validates administrator access, retrieves eligible students and available
    | supervisors, runs the allocation strategy, and commits the result.
    */
    public function executeAutoAllocation(
        $administratorRole,
        $administratorID = null
    ) {

        /*
        |--------------------------------------------------------------------------
        | Administrator Access Validation
        |--------------------------------------------------------------------------
        */
        if ($administratorRole !== "Administrator") {

            return $this->failure(
                "Access denied. Only administrators can run auto-allocation."
            );
        }

        $allocationWindow =
            $this->allocationWindowService
            ->getWindow();

        if (!$allocationWindow["canRunAutoAllocation"]) {

            $message =
                "Time Integrity: Auto-allocation can only run after the final allocation date has passed.";

            $this->recordAutoAllocationLog(
                $administratorID,
                $allocationWindow,
                0,
                0,
                0,
                "TIME_LOCKED",
                $message
            );

            return $this->failure($message);
        }

        /*
        |--------------------------------------------------------------------------
        | Fetch Eligible Unassigned Students
        |--------------------------------------------------------------------------
        */
        $students =
            $this->allocationDAO
                ->getEligibleUnassignedStudents();

        if (empty($students)) {

            $message =
                "No unassigned eligible students found.";

            $this->recordAutoAllocationLog(
                $administratorID,
                $allocationWindow,
                0,
                0,
                0,
                "NO_UNASSIGNED",
                $message
            );

            return $this->failure($message);
        }

        $eligibleCount =
            count($students);

        /*
        |--------------------------------------------------------------------------
        | Fetch Supervisors With Capacity
        |--------------------------------------------------------------------------
        */
        $supervisors =
            $this->allocationDAO
                ->getSupervisorsWithCapacity();

        if (empty($supervisors)) {

            $message =
                $this->shortageMessage($eligibleCount);

            $this->recordAutoAllocationLog(
                $administratorID,
                $allocationWindow,
                $eligibleCount,
                0,
                $eligibleCount,
                "SHORTAGE",
                $message
            );

            return $this->failure($message);
        }

        /*
        |--------------------------------------------------------------------------
        | Run Allocation Strategy
        |--------------------------------------------------------------------------
        */
        $result =
            $this->executeStrategy(
                $students,
                $supervisors
            );

        if (empty($result["allocations"])) {

            $message =
                $this->shortageMessage($eligibleCount);

            $this->recordAutoAllocationLog(
                $administratorID,
                $allocationWindow,
                $eligibleCount,
                0,
                $eligibleCount,
                "SHORTAGE",
                $message
            );

            return $this->failure($message);
        }

        /*
        |--------------------------------------------------------------------------
        | Commit Allocation Records
        |--------------------------------------------------------------------------
        */
        $committed =
            $this->allocationDAO
                ->commitAllocations(
                    $result["allocations"]
                );

        if (!$committed) {

            $message =
                "Auto-allocation failed during database commit.";

            $this->recordAutoAllocationLog(
                $administratorID,
                $allocationWindow,
                $eligibleCount,
                0,
                $eligibleCount,
                "COMMIT_FAILED",
                $message
            );

            return $this->failure($message);
        }

        /*
        |--------------------------------------------------------------------------
        | Auto Allocation Success
        |--------------------------------------------------------------------------
        */
        $allocatedCount =
            count($result["allocations"]);

        $unassignedCount =
            count($result["unassigned"]);

        if ($unassignedCount > 0) {

            $message =
                "Auto-Allocation Complete: " .
                $allocatedCount .
                " students successfully matched. Notification records generated. " .
                $this->shortageMessage($unassignedCount);

            $logID =
                $this->recordAutoAllocationLog(
                $administratorID,
                $allocationWindow,
                $eligibleCount,
                $allocatedCount,
                $unassignedCount,
                "PARTIAL_SHORTAGE",
                $message
            );

            $this->recordAutoAllocationNotifications(
                $logID,
                $result["allocations"]
            );

            return $this->success($message);
        }

        $message =
            "Auto-Allocation Complete: " .
            $allocatedCount .
            " students successfully matched. Notification records generated.";

        $logID =
            $this->recordAutoAllocationLog(
            $administratorID,
            $allocationWindow,
            $eligibleCount,
            $allocatedCount,
            0,
            "COMPLETED",
            $message
        );

        $this->recordAutoAllocationNotifications(
            $logID,
            $result["allocations"]
        );

        return $this->success($message);
    }

    /*
    |--------------------------------------------------------------------------
    | Recent Auto Allocation Logs
    |--------------------------------------------------------------------------
    */
    public function getRecentAutoAllocationLogs(
        $limit = 5
    ) {

        return $this->allocationDAO
            ->getRecentAutoAllocationLogs($limit);
    }

    /*
    |--------------------------------------------------------------------------
    | Execute Selected Strategy
    |--------------------------------------------------------------------------
    */
    private function executeStrategy(
        $students,
        $supervisors
    ) {

        return $this->strategy
            ->allocate(
                $students,
                $supervisors
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Quota Shortage Message
    |--------------------------------------------------------------------------
    */
    private function shortageMessage(
        $unassignedCount
    ) {

        return
            "Warning: Insufficient quota slots. " .
            $unassignedCount .
            " students remain unassigned. Please review the Allocation Log.";
    }

    /*
    |--------------------------------------------------------------------------
    | Record Auto Allocation Log
    |--------------------------------------------------------------------------
    */
    private function recordAutoAllocationLog(
        $administratorID,
        $allocationWindow,
        $eligibleCount,
        $matchedCount,
        $unassignedCount,
        $status,
        $message
    ) {

        return $this->allocationDAO
            ->createAutoAllocationLog(
                $administratorID,
                $allocationWindow["finalAllocationDate"] ?? null,
                $eligibleCount,
                $matchedCount,
                $unassignedCount,
                $status,
                $message
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Record Auto Allocation Notifications
    |--------------------------------------------------------------------------
    */
    private function recordAutoAllocationNotifications(
        $logID,
        $allocations
    ) {

        $this->allocationDAO
            ->createAutoAllocationNotifications(
                $logID,
                $allocations
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Success Response Helper
    |--------------------------------------------------------------------------
    */
    private function success(
        $message
    ) {

        return [
            "success" => true,
            "message" => $message
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Failure Response Helper
    |--------------------------------------------------------------------------
    */
    private function failure(
        $message
    ) {

        return [
            "success" => false,
            "message" => $message
        ];
    }
}

?>
