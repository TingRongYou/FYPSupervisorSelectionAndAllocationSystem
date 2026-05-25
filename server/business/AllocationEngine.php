<?php

require_once __DIR__ . "/../data/AllocationDAO.php";

interface AllocationStrategy {

    public function allocate(
        $students,
        $supervisors
    );
}

class AutoAllocationEngineStrategy implements AllocationStrategy {

    /*
    |--------------------------------------------------------------------------
    | Auto Allocation Algorithm
    |--------------------------------------------------------------------------
    | Students are assigned to available supervisors with remaining capacity.
    | Programme matches are preferred, then the lowest current workload is used.
    */

    public function allocate(
        $students,
        $supervisors
    ) {

        $allocations =
            [];

        $unassigned =
            [];

        foreach ($supervisors as $index => $supervisor) {

            $supervisors[$index]["currentTotal"] =
                (int) $supervisor["currentTotal"];

            $supervisors[$index]["maxSuperviseesAllowed"] =
                (int) $supervisor["maxSuperviseesAllowed"];
        }

        foreach ($students as $student) {

            $selectedSupervisorIndex =
                $this->findBestSupervisorIndex(
                    $student,
                    $supervisors
                );

            if ($selectedSupervisorIndex === null) {

                $unassigned[] =
                    $student;

                continue;
            }

            $allocations[] = [
                "studentID" => $student["studentID"],
                "supervisorID" => $supervisors[$selectedSupervisorIndex]["supervisorID"],
                "allocationMethod" => "System Auto-Match"
            ];

            $supervisors[$selectedSupervisorIndex]["currentTotal"]++;
        }

        return [
            "allocations" => $allocations,
            "unassigned" => $unassigned
        ];
    }

    private function findBestSupervisorIndex(
        $student,
        $supervisors
    ) {

        $bestIndex =
            null;

        foreach ($supervisors as $index => $supervisor) {

            if (
                $supervisor["currentTotal"]
                >=
                $supervisor["maxSuperviseesAllowed"]
            ) {

                continue;
            }

            if ($bestIndex === null) {

                $bestIndex =
                    $index;

                continue;
            }

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

            if (
                $candidateScore === $bestScore
                &&
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

    private function scoreSupervisor(
        $student,
        $supervisor
    ) {

        $score =
            0;

        if (
            strtolower(trim($student["programme"]))
            ===
            strtolower(trim($supervisor["programme"]))
        ) {

            $score +=
                10;
        }

        $remainingSlots =
            (int) $supervisor["maxSuperviseesAllowed"]
            -
            (int) $supervisor["currentTotal"];

        $score +=
            $remainingSlots;

        return $score;
    }
}

class AllocationEngine {

    private $allocationDAO;

    private $strategy;

    public function __construct() {

        $this->allocationDAO =
            new AllocationDAO();

        $this->strategy =
            new AutoAllocationEngineStrategy();
    }

    public function getAllocationDashboard() {

        $summary =
            $this->allocationDAO
            ->getAllocationSummary();

        $eligibleStudents =
            (int) ($summary["eligibleStudents"] ?? 0);

        $allocatedStudents =
            (int) ($summary["allocatedStudents"] ?? 0);

        $unassignedStudents =
            (int) ($summary["unassignedStudents"] ?? 0);

        $pendingRequests =
            (int) ($summary["pendingRequests"] ?? 0);

        return [
            "eligibleStudents" => $eligibleStudents,
            "allocatedStudents" => $allocatedStudents,
            "unassignedStudents" => $unassignedStudents,
            "pendingRequests" => $pendingRequests,
            "allocationRate" => $eligibleStudents > 0
                ? round(($allocatedStudents / $eligibleStudents) * 100, 1)
                : 0
        ];
    }

    public function executeAutoAllocation(
        $administratorRole
    ) {

        if ($administratorRole !== "Administrator") {

            return $this->failure(
                "Access denied"
            );
        }

        $students =
            $this->allocationDAO
            ->getEligibleUnassignedStudents();

        if (empty($students)) {

            return $this->failure(
                "No unassigned eligible students found"
            );
        }

        $supervisors =
            $this->allocationDAO
            ->getSupervisorsWithCapacity();

        if (empty($supervisors)) {

            return $this->failure(
                "No supervisor capacity available"
            );
        }

        $result =
            $this->strategy
            ->allocate(
                $students,
                $supervisors
            );

        if (empty($result["allocations"])) {

            return $this->failure(
                "No students could be allocated with current capacity"
            );
        }

        $committed =
            $this->allocationDAO
            ->commitAllocations(
                $result["allocations"]
            );

        if (!$committed) {

            return $this->failure(
                "Auto-allocation failed during database commit"
            );
        }

        return $this->success(
            count($result["allocations"]) . " students allocated successfully. "
            . count($result["unassigned"]) . " students remain unassigned."
        );
    }

    private function success(
        $message
    ) {

        return [
            "success" => true,
            "message" => $message
        ];
    }

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
