<?php

/*
|--------------------------------------------------------------------------
| Required Dependencies
|--------------------------------------------------------------------------
| Loads the DAO for student eligibility records and the user account manager
| used to create student account payloads during CSV import.
*/
require_once __DIR__ . "/../../data/dao/StudentEligibilityDAO.php";
require_once __DIR__ . "/UserAccountManager.php";

/*
|--------------------------------------------------------------------------
| Eligibility Service
|--------------------------------------------------------------------------
| Handles student eligibility rules, dashboard data, batch processing,
| and CSV import for student academic records.
*/
class EligibilityService {

    /*
    |--------------------------------------------------------------------------
    | Default Eligibility Rules
    |--------------------------------------------------------------------------
    */
    private const MINIMUM_CGPA = 2.0;

    private const REQUIRED_NEXT_SEMESTER = "Y2S3";

    private const BLOCKED_ACADEMIC_STATUS = "EF";

    private const MAX_CSV_ROWS = 5000;

    /*
    |--------------------------------------------------------------------------
    | Service Dependencies
    |--------------------------------------------------------------------------
    */
    private $studentEligibilityDAO;

    private $userAccountManager;

    private $rules;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    | Initializes DAO, account manager, and active eligibility rules.
    */
    public function __construct() {

        $this->studentEligibilityDAO =
            new StudentEligibilityDAO();

        $this->userAccountManager =
            new UserAccountManager();

        $this->rules =
            $this->loadEligibilityRules();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Eligibility Dashboard
    |--------------------------------------------------------------------------
    | Retrieves filtered student eligibility data and formats each row for
    | dashboard display.
    */
    public function getEligibilityDashboard(
        $filters
    ) {

        /*
        |--------------------------------------------------------------------------
        | Read Filter Inputs
        |--------------------------------------------------------------------------
        */
        $searchName =
            trim($filters["searchName"] ?? "");

        $programme =
            trim($filters["programme"] ?? "");

        $eligibilityStatus = $filters["eligibilityStatus"] ?? "";

        /*
        |--------------------------------------------------------------------------
        | Validate Eligibility Status Filter
        |--------------------------------------------------------------------------
        */
        if ($eligibilityStatus !== "1" && $eligibilityStatus !== "0") {

            $eligibilityStatus = "";
        }

        /*
        |--------------------------------------------------------------------------
        | Fetch Filtered Student Records
        |--------------------------------------------------------------------------
        */
        $students =
            $this->studentEligibilityDAO
                ->getStudentsForEligibility(
                    $searchName,
                    $programme,
                    $eligibilityStatus
                );

        /*
        |--------------------------------------------------------------------------
        | Format Student Dashboard Rows
        |--------------------------------------------------------------------------
        */
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

    /*
    |--------------------------------------------------------------------------
    | Get Programme Options
    |--------------------------------------------------------------------------
    | Retrieves programme options for dashboard filtering.
    */
    public function getProgrammeOptions() {

        return
            $this->studentEligibilityDAO
                ->getStudentProgrammes();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Eligibility Summary
    |--------------------------------------------------------------------------
    | Retrieves total, eligible, and ineligible student counts.
    */
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

            "eligibleRate" =>
                $totalStudents > 0
                    ? round(($eligibleStudents / $totalStudents) * 100)
                    : 0
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Eligibility Rules
    |--------------------------------------------------------------------------
    | Returns the currently active eligibility rule set.
    */
    public function getEligibilityRules() {

        return $this->rules;
    }

    /*
    |--------------------------------------------------------------------------
    | Update Eligibility Rules
    |--------------------------------------------------------------------------
    | Allows administrators to update CGPA, required next semester,
    | and blocked academic status rules.
    */
    public function updateEligibilityRules(
        $administratorRole,
        $minimumCGPA,
        $requiredNextSemester,
        $blockedAcademicStatus
    ) {

        /*
        |--------------------------------------------------------------------------
        | Administrator Access Validation
        |--------------------------------------------------------------------------
        */
        if ($administratorRole !== "Administrator") {

            return $this->failure(
                "Access denied"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Normalize Rule Inputs
        |--------------------------------------------------------------------------
        */
        $minimumCGPA =
            trim(
                (string) $minimumCGPA
            );

        $requiredNextSemester =
            strtoupper(
                trim(
                    (string) $requiredNextSemester
                )
            );

        $blockedAcademicStatus =
            strtoupper(
                trim(
                    (string) $blockedAcademicStatus
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Minimum CGPA Validation
        |--------------------------------------------------------------------------
        */
        if (
            !is_numeric($minimumCGPA) ||
            (float) $minimumCGPA < 0 ||
            (float) $minimumCGPA > 4
        ) {

            return $this->failure(
                "Minimum CGPA must be between 0.00 and 4.00"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Required Semester Format Validation
        |--------------------------------------------------------------------------
        */
        if (!preg_match("/^Y[0-9]+S[1-3]$/", $requiredNextSemester)) {

            return $this->failure(
                "Required next semester must use format such as Y2S3"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Blocked Academic Status Validation
        |--------------------------------------------------------------------------
        */
        if (
            $blockedAcademicStatus === "" ||
            strlen($blockedAcademicStatus) > 50
        ) {

            return $this->failure(
                "Blocked academic status is required"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Persist Eligibility Rules
        |--------------------------------------------------------------------------
        */
        $updated =
            $this->studentEligibilityDAO
                ->updateEligibilityRules(
                    number_format((float) $minimumCGPA, 2, ".", ""),
                    $requiredNextSemester,
                    $blockedAcademicStatus
                );

        if (!$updated) {

            return $this->failure(
                "Eligibility rules could not be updated"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Reload Active Rules
        |--------------------------------------------------------------------------
        */
        $this->rules =
            $this->loadEligibilityRules();

        return $this->success(
            "Eligibility rules updated successfully. Run Eligibility Batch to apply the new rules."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Run Eligibility Batch
    |--------------------------------------------------------------------------
    | Recalculates eligibility status for all students based on active rules.
    */
    public function runEligibilityBatch(
        $administratorRole
    ) {

        /*
        |--------------------------------------------------------------------------
        | Administrator Access Validation
        |--------------------------------------------------------------------------
        */
        if ($administratorRole !== "Administrator") {

            return $this->failure(
                "Access denied"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Fetch All Students For Batch Processing
        |--------------------------------------------------------------------------
        */
        $students =
            $this->studentEligibilityDAO
                ->getAllStudentsForBatch();

        if (empty($students)) {

            return $this->failure(
                "No Record: No Student Record Found"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Initialize Batch Counters
        |--------------------------------------------------------------------------
        */
        $eligibilityResults =
            [];

        $eligibleCount =
            0;

        $ineligibleCount =
            0;

        /*
        |--------------------------------------------------------------------------
        | Process Student Eligibility
        |--------------------------------------------------------------------------
        */
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

            $accountPayload =
                $isEligible
                ? $this->createStudentAccountPayload($student)
                : [
                    "studentID" => $student["studentID"],
                    "universityEmail" => $student["universityEmail"],
                    "icNumber" => $student["icNumber"],
                    "fullName" => $student["fullName"],
                    "programme" => $student["programme"],
                    "intakeBatch" => $student["intakeBatch"],
                    "currentSem" => $student["currentSem"],
                    "academicStatus" => $student["academicStatus"],
                    "cgpa" => $student["cgpa"],
                    "eligibilityStatus" => false,
                    "hashedPassword" => ""
                ];

            $accountPayload["eligibilityStatus"] =
                $isEligible;

            $eligibilityResults[] =
                $accountPayload;
        }

        /*
        |--------------------------------------------------------------------------
        | Persist Batch Eligibility Statuses
        |--------------------------------------------------------------------------
        */
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
            . " students marked as Eligible and account access enabled, "
            . $ineligibleCount
            . " students marked as Ineligible and blocked from selection access."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Import Student Eligibility CSV
    |--------------------------------------------------------------------------
    | Validates uploaded CSV records, calculates eligibility, creates student
    | account payloads, and imports records into the database.
    */
    public function importStudentEligibilityCSV(
        $administratorRole,
        $csvFilePath
    ) {

        /*
        |--------------------------------------------------------------------------
        | Administrator Access Validation
        |--------------------------------------------------------------------------
        */
        if ($administratorRole !== "Administrator") {

            return $this->failure(
                "Access denied"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | CSV File Read Validation
        |--------------------------------------------------------------------------
        */
        if (
            $csvFilePath === "" ||
            !is_readable($csvFilePath)
        ) {

            return $this->failure(
                "Uploaded CSV file could not be read"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Open CSV File
        |--------------------------------------------------------------------------
        */
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

        /*
        |--------------------------------------------------------------------------
        | Read CSV Header
        |--------------------------------------------------------------------------
        */
        $header =
            fgetcsv($handle);

        if (!$header) {

            fclose($handle);

            return $this->failure(
                "CSV file is empty"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Map CSV Header Columns
        |--------------------------------------------------------------------------
        */
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

        /*
        |--------------------------------------------------------------------------
        | Validate Required CSV Columns
        |--------------------------------------------------------------------------
        */
        foreach ($requiredColumns as $column) {

            if (!isset($headerMap[$column])) {

                fclose($handle);

                return $this->failure(
                    "CSV missing required column: " . $column
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Initialize CSV Import Counters
        |--------------------------------------------------------------------------
        */
        $records =
            [];

        $eligibleCount =
            0;

        $ineligibleCount =
            0;

        $rowNumber =
            1;

        /*
        |--------------------------------------------------------------------------
        | Process CSV Rows
        |--------------------------------------------------------------------------
        */
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

            $record["intakeBatch"] =
                $this->deriveIntakeBatch(
                    $record["studentID"]
                );

            $record["eligibilityStatus"] =
                false;

            $ineligibleCount++;

            $records[] =
                $record;
        }

        fclose($handle);

        /*
        |--------------------------------------------------------------------------
        | Empty CSV Record Validation
        |--------------------------------------------------------------------------
        */
        if (empty($records)) {

            return $this->failure(
                "No student records found in CSV"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Import Student Records
        |--------------------------------------------------------------------------
        */
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
            . count($records)
            . " record(s) displayed. Run Eligibility Batch to validate eligibility and create eligible accounts."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Check Student Eligibility
    |--------------------------------------------------------------------------
    | Returns true only when CGPA, next semester, and academic status rules pass.
    */
    private function isStudentEligible(
        $student
    ) {

        return
            (float) $student["cgpa"] > (float) $this->rules["minimumCGPA"] &&
            $this->calculateNextSemester(
                strtoupper(
                    trim(
                        $student["currentSem"]
                    )
                )
            ) === $this->rules["requiredNextSemester"] &&
            strtoupper(trim($student["academicStatus"])) !==
                $this->rules["blockedAcademicStatus"];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Eligibility Reason
    |--------------------------------------------------------------------------
    | Returns the reason why a student is eligible or ineligible.
    */
    private function getEligibilityReason(
        $student
    ) {

        if ((float) $student["cgpa"] <= (float) $this->rules["minimumCGPA"]) {

            return
                "CGPA must be greater than " .
                number_format((float) $this->rules["minimumCGPA"], 2);
        }

        if (
            $this->calculateNextSemester(
                strtoupper(
                    trim(
                        $student["currentSem"]
                    )
                )
            ) !== $this->rules["requiredNextSemester"]
        ) {

            return
                "Next semester is not " .
                $this->rules["requiredNextSemester"];
        }

        if (
            strtoupper(trim($student["academicStatus"])) ===
            $this->rules["blockedAcademicStatus"]
        ) {

            return
                "Academic status is " .
                $this->rules["blockedAcademicStatus"];
        }

        return "Meets eligibility criteria";
    }

    /*
    |--------------------------------------------------------------------------
    | Map CSV Header
    |--------------------------------------------------------------------------
    | Converts flexible CSV column names into standard internal field names.
    */
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

    /*
    |--------------------------------------------------------------------------
    | Normalise CSV Student Record
    |--------------------------------------------------------------------------
    | Cleans and standardizes raw CSV row values.
    */
    private function normaliseCsvStudentRecord(
        $row,
        $headerMap
    ) {

        return [
            "studentID" =>
                strtoupper(trim($row[$headerMap["studentID"]] ?? "")),

            "universityEmail" =>
                strtolower(trim($row[$headerMap["universityEmail"]] ?? "")),

            "icNumber" =>
                trim($row[$headerMap["icNumber"]] ?? ""),

            "fullName" =>
                trim($row[$headerMap["fullName"]] ?? ""),

            "programme" =>
                trim($row[$headerMap["programme"]] ?? ""),

            "cgpa" =>
                trim($row[$headerMap["cgpa"]] ?? ""),

            "currentSem" =>
                strtoupper(trim($row[$headerMap["currentSem"]] ?? "")),

            "academicStatus" =>
                strtoupper(trim($row[$headerMap["academicStatus"]] ?? ""))
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Validate CSV Student Record
    |--------------------------------------------------------------------------
    | Ensures each CSV row has required values and valid academic data.
    */
    private function validateCsvStudentRecord(
        $record,
        $rowNumber
    ) {

        foreach (
            [
                "studentID",
                "universityEmail",
                "icNumber",
                "fullName",
                "programme",
                "cgpa",
                "currentSem",
                "academicStatus"
            ] as $field
        ) {

            if ($record[$field] === "") {

                return
                    "CSV row " .
                    $rowNumber .
                    " missing " .
                    $field;
            }
        }

        if (!filter_var($record["universityEmail"], FILTER_VALIDATE_EMAIL)) {

            return
                "CSV row " .
                $rowNumber .
                " has invalid university email";
        }

        if (!is_numeric($record["cgpa"])) {

            return
                "CSV row " .
                $rowNumber .
                " has invalid CGPA";
        }

        if (
            (float) $record["cgpa"] < 0 ||
            (float) $record["cgpa"] > 4
        ) {

            return
                "CSV row " .
                $rowNumber .
                " CGPA must be between 0 and 4";
        }

        return "";
    }

    /*
    |--------------------------------------------------------------------------
    | Create Student Account Payload
    |--------------------------------------------------------------------------
    | Uses the factory-based user account manager to create a Student object,
    | then converts it into a database-ready import payload.
    */
    private function createStudentAccountPayload(
        $record
    ) {

        /*
        |--------------------------------------------------------------------------
        | Calculate Eligibility Status
        |--------------------------------------------------------------------------
        */
        $record["eligibilityStatus"] =
            $this->isStudentEligible($record);

        /*
        |--------------------------------------------------------------------------
        | Create Student Role Object
        |--------------------------------------------------------------------------
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

        /*
        |--------------------------------------------------------------------------
        | Build Database Import Payload
        |--------------------------------------------------------------------------
        */
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

    /*
    |--------------------------------------------------------------------------
    | Derive Intake Batch
    |--------------------------------------------------------------------------
    | Extracts the intake year from the first two digits of the student ID.
    */
    private function deriveIntakeBatch(
        $studentID
    ) {

        if (preg_match("/^([0-9]{2})/", $studentID, $matches)) {

            return "20" . $matches[1];
        }

        return date("Y");
    }

    /*
    |--------------------------------------------------------------------------
    | Calculate Next Semester
    |--------------------------------------------------------------------------
    | Converts current semester such as Y2S2 into the next semester Y2S3.
    */
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

    /*
    |--------------------------------------------------------------------------
    | Load Eligibility Rules
    |--------------------------------------------------------------------------
    | Loads rules from database, or falls back to default constants.
    */
    private function loadEligibilityRules() {

        $rules =
            $this->studentEligibilityDAO
                ->getEligibilityRules();

        if (!$rules) {

            return [
                "minimumCGPA" => self::MINIMUM_CGPA,
                "requiredNextSemester" => self::REQUIRED_NEXT_SEMESTER,
                "blockedAcademicStatus" => self::BLOCKED_ACADEMIC_STATUS
            ];
        }

        return [
            "minimumCGPA" =>
                (float) $rules["minimumCGPA"],

            "requiredNextSemester" =>
                strtoupper(trim($rules["requiredNextSemester"])),

            "blockedAcademicStatus" =>
                strtoupper(trim($rules["blockedAcademicStatus"]))
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Empty CSV Row Check
    |--------------------------------------------------------------------------
    | Returns true when every column in a CSV row is blank.
    */
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
