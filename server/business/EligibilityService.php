<?php

require_once __DIR__ . "/../data/StudentEligibilityDAO.php";

class EligibilityService {

    private const MINIMUM_CGPA = 2.0;

    private const REQUIRED_SEMESTER = "Y2S3";

    private $studentEligibilityDAO;

    public function __construct() {

        $this->studentEligibilityDAO =
            new StudentEligibilityDAO();
    }

    /*
    |--------------------------------------------------------------------------
    | Eligibility Dashboard Data
    |--------------------------------------------------------------------------
    */

    public function getEligibilityDashboard(
        $filters
    ) {

        $searchName =
            trim(
                $filters["searchName"] ?? ""
            );

        $programme =
            trim(
                $filters["programme"] ?? ""
            );

        $eligibilityStatus =
            $filters["eligibilityStatus"] ?? "";

        if (
            $eligibilityStatus !== "1"
            &&
            $eligibilityStatus !== "0"
        ) {

            $eligibilityStatus =
                "";
        }

        $students =
            $this->studentEligibilityDAO
            ->getStudentsForEligibility(
                $searchName,
                $programme,
                $eligibilityStatus
            );

        foreach ($students as $index => $student) {

            $students[$index]["cgpa"] =
                number_format(
                    (float) $student["cgpa"],
                    4
                );

            $students[$index]["eligibilityStatus"] =
                (bool) $student["eligibilityStatus"];

            $students[$index]["eligibilityReason"] =
                $this->getEligibilityReason(
                    $student
                );
        }

        return $students;
    }

    public function getProgrammeOptions() {

        return
            $this->studentEligibilityDAO
            ->getStudentProgrammes();
    }

    public function getEligibilitySummary() {

        $summary =
            $this->studentEligibilityDAO
            ->getEligibilitySummary();

        $totalStudents =
            (int) ($summary["totalStudents"] ?? 0);

        $eligibleStudents =
            (int) ($summary["eligibleStudents"] ?? 0);

        $ineligibleStudents =
            (int) ($summary["ineligibleStudents"] ?? 0);

        return [
            "totalStudents" => $totalStudents,
            "eligibleStudents" => $eligibleStudents,
            "ineligibleStudents" => $ineligibleStudents,
            "eligibleRate" => $totalStudents > 0
                ? round(($eligibleStudents / $totalStudents) * 100)
                : 0
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Batch Eligibility Processing
    |--------------------------------------------------------------------------
    */

    public function runEligibilityBatch(
        $administratorRole
    ) {

        if ($administratorRole !== "Administrator") {

            return $this->failure(
                "Access denied"
            );
        }

        $students =
            $this->studentEligibilityDAO
            ->getAllStudentsForBatch();

        if (empty($students)) {

            return $this->failure(
                "No student records found"
            );
        }

        $eligibilityResults =
            [];

        foreach ($students as $student) {

            $eligibilityResults[] = [
                "studentID" => $student["studentID"],
                "eligibilityStatus" => $this->isStudentEligible($student)
            ];
        }

        $updated =
            $this->studentEligibilityDAO
            ->updateEligibilityStatuses(
                $eligibilityResults
            );

        if (!$updated) {

            return $this->failure(
                "Eligibility batch could not be completed"
            );
        }

        return $this->success(
            "Eligibility batch completed successfully"
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Eligibility Rules
    |--------------------------------------------------------------------------
    | UC101 marks students eligible only when all documented criteria pass.
    */

    private function isStudentEligible(
        $student
    ) {

        return
            (float) $student["cgpa"] >= self::MINIMUM_CGPA
            &&
            strtoupper(trim($student["currentSem"])) === self::REQUIRED_SEMESTER
            &&
            !in_array(
                strtolower(trim($student["academicStatus"])),
                [
                    "withdrawn",
                    "ep",
                    "existing students on appeal successful",
                    "existing student on appeal successful"
                ],
                true
            );
    }

    private function getEligibilityReason(
        $student
    ) {

        if ((float) $student["cgpa"] < self::MINIMUM_CGPA) {

            return "CGPA below 2.0000";
        }

        if (strtoupper(trim($student["currentSem"])) !== self::REQUIRED_SEMESTER) {

            return "Student is not in Y2S3";
        }

        if (
            in_array(
                strtolower(trim($student["academicStatus"])),
                [
                    "withdrawn",
                    "ep",
                    "existing students on appeal successful",
                    "existing student on appeal successful"
                ],
                true
            )
        ) {

            return "Academic status is not eligible";
        }

        return "Meets eligibility criteria";
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
