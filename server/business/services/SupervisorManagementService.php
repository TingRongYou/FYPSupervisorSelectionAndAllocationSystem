<?php

require_once __DIR__ . "/../../data/dao/SupervisorDAO.php";
require_once __DIR__ . "/../../data/dao/UserDAO.php";

class SupervisorManagementService {

    private const MAX_EMPLOYMENT_CATEGORY_LENGTH = 50;

    private const CLASSIFICATION_QUOTA_RULES = [
        "Full-Time Lecturer" => "Full-Time",
        "Part-Time Lecturer" => "Part-Time",
        "Dean" => "Administrative",
        "Deputy Dean" => "Administrative",
        "Academic Director" => "Administrative",
        "Programme Leader" => "Administrative"
    ];

    private $supervisorDAO;

    private $userDAO;

    public function __construct() {

        $this->supervisorDAO = new SupervisorDAO();

        $this->userDAO = new UserDAO();
    }

    /*
    |--------------------------------------------------------------------------
    | Supervisor Directory
    |--------------------------------------------------------------------------
    */

    public function getSupervisorDirectory($filters) {

        $searchName = trim($filters["searchName"] ?? "");

        $programme = trim($filters["programme"] ?? "");

        $supervisors = $this->supervisorDAO->getSupervisorsForManagement($searchName,$programme);

        foreach ($supervisors as $index => $supervisor) {

            $currentSupervisees = (int) $supervisor["currentSupervisees"];

            $tierQuotaLimit = (int) ($supervisor["tierQuotaLimit"] ?? $supervisor["maxSuperviseesAllowed"]);

            $assignedQuotaLimit =
                isset($supervisor["assignedQuotaLimit"])
                &&
                $supervisor["assignedQuotaLimit"] !== null
                ? (int) $supervisor["assignedQuotaLimit"]
                : $tierQuotaLimit;

            $supervisors[$index]["currentSupervisees"] = $currentSupervisees;

            $supervisors[$index]["activeStatus"] = (bool) $supervisor["activeStatus"];

            $supervisors[$index]["maxSuperviseesAllowed"] = $assignedQuotaLimit;

            $supervisors[$index]["tierQuotaLimit"] = $tierQuotaLimit;

            $supervisors[$index]["assignedQuotaLimit"] = $assignedQuotaLimit;

            $supervisors[$index]["loadPercentage"] =
                $assignedQuotaLimit > 0
                ? min(
                    100,
                    round(
                        (
                            $currentSupervisees
                            /
                            $assignedQuotaLimit
                        )
                        *
                        100
                    )
                )
                : 0;

            $supervisors[$index]["quotaText"] =
                $currentSupervisees
                . "/"
                . $assignedQuotaLimit;

            $supervisors[$index]["availabilityStatus"] =
                !$supervisors[$index]["activeStatus"]
                ? "Inactive"
                : (
                    $currentSupervisees < $assignedQuotaLimit
                ? "Available"
                    : "Full"
                );
        }

        return $supervisors;
    }

    /*
    |--------------------------------------------------------------------------
    | Quota Options
    |--------------------------------------------------------------------------
    */

    public function getQuotaOptions() {

        return $this->supervisorDAO->getAllQuotaConfigurations();
    }

    /*
    |--------------------------------------------------------------------------
    | Programme Options
    |--------------------------------------------------------------------------
    */

    public function getProgrammeOptions() {

        return $this->supervisorDAO->getSupervisorProgrammes();
    }

    /*
    |--------------------------------------------------------------------------
    | Classify Supervisor Role
    |--------------------------------------------------------------------------
    */

    public function classifySupervisorRole($administratorRole, $supervisorID, $employmentCategory, $quotaID) {

        if ($administratorRole !== "Administrator") {

            return $this->failure("Access denied");
        }

        $supervisorID = trim($supervisorID);

        $employmentCategory = trim($employmentCategory);

        $quotaID = trim($quotaID);

        if ($supervisorID === "" ||$employmentCategory === "" || $quotaID === "") {

            return $this->failure( "Supervisor, classification, and quota are required");
        }

        if (strlen($employmentCategory) > self::MAX_EMPLOYMENT_CATEGORY_LENGTH) {

            return $this->failure("Classification cannot exceed 50 characters");
        }

        if (!ctype_digit($quotaID)) {

            return $this->failure("Invalid quota selection");
        }

        if (!isset(self::CLASSIFICATION_QUOTA_RULES[$employmentCategory])) {

            return $this->failure("Invalid supervisor classification");
        }

        $currentLoad = $this->supervisorDAO->getSupervisorLoad($supervisorID);

        if (!$currentLoad) {

            return $this->failure("Supervisor record was not found");
        }

        $newQuota = $this->getRequiredQuotaForClassification($employmentCategory);

        if (!$newQuota) {

            return $this->failure("Default quota tier for selected classification was not found");
        }

        if ((int) $quotaID !== (int) $newQuota["quotaID"]) {

            return $this->failure("Selected quota tier does not match the supervisor classification");
        }

        $currentSupervisees = (int) $currentLoad["currentSupervisees"];

        $newQuotaLimit = (int) $newQuota["maxSuperviseesAllowed"];

        $existingAssignedQuota =
            isset($currentLoad["assignedQuotaLimit"])
            &&
            $currentLoad["assignedQuotaLimit"] !== null
            ? (int) $currentLoad["assignedQuotaLimit"]
            : (int) $currentLoad["maxSuperviseesAllowed"];

        $isSameClassification =
            trim($currentLoad["employmentCategory"])
            ===
            $employmentCategory
            &&
            (int) $currentLoad["quotaID"]
            ===
            (int) $newQuota["quotaID"];

        $assignedQuotaLimit =
            $isSameClassification
            ? $existingAssignedQuota
            : $newQuotaLimit;

        /*
        |--------------------------------------------------------------------------
        | Quota Conflict Validation
        |--------------------------------------------------------------------------
        | UC100 prevents a role/quota change if the supervisor's current load
        | exceeds the selected classification quota.
        */

        if ($currentSupervisees > $assignedQuotaLimit) {

            return $this->failure(
                "Unable to reclassify. The supervisor currently has "
                . $currentSupervisees
                . " students, which exceeds the "
                . $assignedQuotaLimit
                . " limit of the selected category."
            );
        }

        $updated =
            $this->supervisorDAO
            ->updateSupervisorClassification(
                $supervisorID,
                $employmentCategory,
                (int) $newQuota["quotaID"],
                $assignedQuotaLimit
            );

        if (!$updated) {

            return $this->failure("Supervisor classification could not be updated");
        }

        return $this->success("Supervisor classification has been updated. The new base quota is now active.");
    }

    private function success($message) {

        return ["success" => true, "message" => $message];
    }

    private function failure($message) {

        return ["success" => false, "message" => $message];
    }

    /*
    |--------------------------------------------------------------------------
    | Update Supervisor Account Particulars
    |--------------------------------------------------------------------------
    */

    public function updateSupervisorAccount($administratorRole, $supervisorID, $fullName, $universityEmail, $activeStatus) {

        if ($administratorRole !== "Administrator") {

            return $this->failure("Access denied");
        }

        $supervisorID = trim($supervisorID);

        $fullName = trim($fullName);

        $universityEmail = strtolower( trim($universityEmail));

        $activeStatus = trim($activeStatus);

        if (
            $supervisorID === ""
            ||
            $fullName === ""
            ||
            $universityEmail === ""
            ||
            $activeStatus === ""
        ) {

            return $this->failure("Supervisor name, email, and account status are required");
        }

        if (strlen($fullName) > 100) {

            return $this->failure("Full name cannot exceed 100 characters");
        }

        if (!filter_var($universityEmail, FILTER_VALIDATE_EMAIL)) {

            return $this->failure("Invalid university email format");
        }

        if (!in_array($activeStatus, ["1", "0"], true)) {

            return $this->failure("Invalid account status");
        }

        $supervisor = $this->userDAO->getUserByID($supervisorID);

        if (!$supervisor || $supervisor["systemRole"] !== "Supervisor") {

            return $this->failure("Supervisor record was not found");
        }

        $existingEmailUser = $this->userDAO->getUserByEmail($universityEmail);

        if ( $existingEmailUser && $existingEmailUser["userID"] !== $supervisorID) {

            return $this->failure("University email is already used by another account");
        }

        $updated =
            $this->userDAO
            ->updateAccountParticulars(
                $supervisorID,
                $fullName,
                $universityEmail,
                $activeStatus === "1"
            );

        if (!$updated) {

            return $this->failure("Supervisor account particulars could not be updated");
        }

        return $this->success("Supervisor account particulars updated successfully");
    }

    private function getRequiredQuotaForClassification($employmentCategory) {

        $requiredTierText = self::CLASSIFICATION_QUOTA_RULES[$employmentCategory] ?? "";

        foreach ($this->getQuotaOptions() as $quota) {

            if (stripos($quota["quotaTierName"], $requiredTierText) !== false) {

                return $quota;
            }
        }

        return null;
    }
}

?>
