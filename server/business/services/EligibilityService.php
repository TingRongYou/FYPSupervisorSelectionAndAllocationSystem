<?php

require_once __DIR__ . "/../../data/dao/StudentEligibilityDAO.php";
require_once __DIR__ . "/UserAccountManager.php";

class EligibilityService {

    private const MINIMUM_CGPA = 2.0;

    private const REQUIRED_NEXT_SEMESTER = "Y2S3";

    private const MAX_CSV_ROWS = 5000;

    private $studentEligibilityDAO;

    private $userAccountManager;

    public function __construct() {

        $this->studentEligibilityDAO =
            new StudentEligibilityDAO();

        $this->userAccountManager =
            new UserAccountManager();
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
                "No Record: No Student Record Found"
            );
        }

        $eligibilityResults =
            [];

        $eligibleCount =
            0;

        $ineligibleCount =
            0;

        foreach ($students as $student) {

            $isEligible =
                $this->isStudentEligible(
                    $student
                );

            if ($isEligible) {

                $eligibleCount++;

            } else {

                $ineligibleCount++;
            }

            $eligibilityResults[] = [
                "studentID" => $student["studentID"],
                "eligibilityStatus" => $isEligible
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
            "Batch Complete: Eligibility check completed. "
            . $eligibleCount
            . " students marked as Eligible, "
            . $ineligibleCount
            . " students marked as Ineligible."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CSV Student Academic Record Import
    |--------------------------------------------------------------------------
    */

    public function importStudentEligibilityCSV(
        $administratorRole,
        $csvFilePath
    ) {

        if ($administratorRole !== "Administrator") {

            return $this->failure(
                "Access denied"
            );
        }

        if (
            $csvFilePath === ""
            ||
            !is_readable($csvFilePath)
        ) {

            return $this->failure(
                "Uploaded CSV file could not be read"
            );
        }

        $handle =
            fopen(
                $csvFilePath,
                "r"
            );

        if (!$handle) {

            return $this->failure(
                "Unable to open uploaded CSV file"
            );
        }

        $header =
            fgetcsv($handle);

        if (!$header) {

            fclose($handle);

            return $this->failure(
                "CSV file is empty"
            );
        }

        $headerMap =
            $this->mapCsvHeader($header);

        $requiredColumns =
            [
                "studentID",
                "universityEmail",
                "icNumber",
                "fullName",
                "programme",
                "cgpa",
                "currentSem",
                "academicStatus"
            ];

        foreach ($requiredColumns as $column) {

            if (!isset($headerMap[$column])) {

                fclose($handle);

                return $this->failure(
                    "CSV missing required column: " . $column
                );
            }
        }

        $records =
            [];

        $eligibleCount =
            0;

        $ineligibleCount =
            0;

        $rowNumber =
            1;

        while (($row = fgetcsv($handle)) !== false) {

            $rowNumber++;

            if ($this->isEmptyCsvRow($row)) {

                continue;
            }

            if (count($records) >= self::MAX_CSV_ROWS) {

                fclose($handle);

                return $this->failure(
                    "CSV exceeds maximum supported row count"
                );
            }

            $record =
                $this->normaliseCsvStudentRecord(
                    $row,
                    $headerMap
                );

            $validationError =
                $this->validateCsvStudentRecord(
                    $record,
                    $rowNumber
                );

            if ($validationError !== "") {

                fclose($handle);

                return $this->failure(
                    $validationError
                );
            }

            $record =
                $this->createStudentAccountPayload(
                    $record
                );

            if ($record["eligibilityStatus"]) {

                $eligibleCount++;

                $records[] =
                    $record;

            } else {

                $ineligibleCount++;
            }
        }

        fclose($handle);

        if (empty($records)) {

            return $this->failure(
                "No eligible student records found in CSV"
            );
        }

        $imported =
            $this->studentEligibilityDAO
            ->importStudentRecords(
                $records
            );

        if (!$imported) {

            return $this->failure(
                "Student eligibility CSV import could not be completed"
            );
        }

        return $this->success(
            "CSV imported successfully. "
            . $eligibleCount
            . " eligible account(s) activated, "
            . $ineligibleCount
            . " ineligible record(s) blocked."
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
            (float) $student["cgpa"] > self::MINIMUM_CGPA
            &&
            $this->calculateNextSemester(
                strtoupper(
                    trim(
                        $student["currentSem"]
                    )
                )
            )
            ===
            self::REQUIRED_NEXT_SEMESTER
            &&
            strtoupper(trim($student["academicStatus"])) !== "EF";
    }

    private function getEligibilityReason(
        $student
    ) {

        if ((float) $student["cgpa"] <= self::MINIMUM_CGPA) {

            return "CGPA must be greater than 2.0000";
        }

        if (
            $this->calculateNextSemester(
                strtoupper(
                    trim(
                        $student["currentSem"]
                    )
                )
            )
            !==
            self::REQUIRED_NEXT_SEMESTER
        ) {

            return "Next semester is not Y2S3";
        }

        if (
            strtoupper(trim($student["academicStatus"])) === "EF"
        ) {

            return "Enrollment status is EF";
        }

        return "Meets eligibility criteria";
    }

    private function mapCsvHeader(
        $header
    ) {

        $map =
            [];

        foreach ($header as $index => $columnName) {

            $normalised =
                strtolower(
                    preg_replace(
                        "/[^a-zA-Z0-9]/",
                        "",
                        trim($columnName)
                    )
                );

            $aliases =
                [
                    "studentid" => "studentID",
                    "id" => "studentID",
                    "universityemail" => "universityEmail",
                    "email" => "universityEmail",
                    "malaysiaicnumber" => "icNumber",
                    "icnumber" => "icNumber",
                    "ic" => "icNumber",
                    "mykad" => "icNumber",
                    "name" => "fullName",
                    "fullname" => "fullName",
                    "studentname" => "fullName",
                    "programme" => "programme",
                    "program" => "programme",
                    "cgpa" => "cgpa",
                    "currentsem" => "currentSem",
                    "semester" => "currentSem",
                    "enrollmentstatus" => "academicStatus",
                    "enrolmentstatus" => "academicStatus",
                    "academicstatus" => "academicStatus",
                    "status" => "academicStatus"
                ];

            if (isset($aliases[$normalised])) {

                $map[$aliases[$normalised]] =
                    $index;
            }
        }

        return $map;
    }

    private function normaliseCsvStudentRecord(
        $row,
        $headerMap
    ) {

        return [
            "studentID" => strtoupper(trim($row[$headerMap["studentID"]] ?? "")),
            "universityEmail" => strtolower(trim($row[$headerMap["universityEmail"]] ?? "")),
            "icNumber" => trim($row[$headerMap["icNumber"]] ?? ""),
            "fullName" => trim($row[$headerMap["fullName"]] ?? ""),
            "programme" => trim($row[$headerMap["programme"]] ?? ""),
            "cgpa" => trim($row[$headerMap["cgpa"]] ?? ""),
            "currentSem" => strtoupper(trim($row[$headerMap["currentSem"]] ?? "")),
            "academicStatus" => strtoupper(trim($row[$headerMap["academicStatus"]] ?? ""))
        ];
    }

    private function validateCsvStudentRecord(
        $record,
        $rowNumber
    ) {

        foreach (["studentID", "universityEmail", "icNumber", "fullName", "programme", "cgpa", "currentSem", "academicStatus"] as $field) {

            if ($record[$field] === "") {

                return "CSV row " . $rowNumber . " missing " . $field;
            }
        }

        if (!filter_var($record["universityEmail"], FILTER_VALIDATE_EMAIL)) {

            return "CSV row " . $rowNumber . " has invalid university email";
        }

        if (!is_numeric($record["cgpa"])) {

            return "CSV row " . $rowNumber . " has invalid CGPA";
        }

        if ((float) $record["cgpa"] < 0 || (float) $record["cgpa"] > 4) {

            return "CSV row " . $rowNumber . " CGPA must be between 0 and 4";
        }

        return "";
    }

    private function createStudentAccountPayload(
        $record
    ) {

        $record["eligibilityStatus"] =
            $this->isStudentEligible($record);

        /*
        The User Management module uses the Factory pattern from the SDD:
        UserAccountManager delegates Student construction to StudentFactory,
        while this service keeps UC101 eligibility rules in the business layer.
        */
        $student =
            $this->userAccountManager
            ->createRole(
                "Student",
                [
                    "userID" => $record["studentID"],
                    "fullName" => $record["fullName"],
                    "universityEmail" => $record["universityEmail"],
                    "password" => $record["icNumber"],
                    "activeStatus" => $record["eligibilityStatus"],
                    "programme" => $record["programme"],
                    "cgpa" => (float) $record["cgpa"],
                    "academicStatus" => $record["academicStatus"],
                    "currentSem" => $record["currentSem"]
                ]
            );

        $record["studentID"] =
            $student->getUserID();

        $record["fullName"] =
            $student->getName();

        $record["universityEmail"] =
            $student->getUniversityEmail();

        $record["programme"] =
            $student->getProgramme();

        $record["cgpa"] =
            $student->getCgpa();

        $record["academicStatus"] =
            $student->getAcademicStatus();

        $record["currentSem"] =
            $student->getCurrentSem();

        $record["hashedPassword"] =
            password_hash(
                $student->getPassword(),
                PASSWORD_DEFAULT
            );

        $record["intakeBatch"] =
            $this->deriveIntakeBatch(
                $student->getUserID()
            );

        return $record;
    }

    private function deriveIntakeBatch(
        $studentID
    ) {

        if (preg_match("/^([0-9]{2})/", $studentID, $matches)) {

            return "20" . $matches[1];
        }

        return date("Y");
    }

    private function calculateNextSemester(
        $currentSem
    ) {

        if (
            !preg_match(
                "/^Y([0-9]+)S([1-3])$/",
                $currentSem,
                $matches
            )
        ) {

            return "";
        }

        $year =
            (int) $matches[1];

        $semester =
            (int) $matches[2];

        if ($semester < 3) {

            $semester++;

        } else {

            $year++;
            $semester = 1;
        }

        return "Y" . $year . "S" . $semester;
    }

    private function isEmptyCsvRow(
        $row
    ) {

        foreach ($row as $value) {

            if (trim($value) !== "") {

                return false;
            }
        }

        return true;
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


