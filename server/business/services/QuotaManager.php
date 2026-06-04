<?php

/*
|--------------------------------------------------------------------------
| Required Dependencies
|--------------------------------------------------------------------------
| Loads the SupervisorDAO used for quota dashboard data, quota options,
| supervisor workload checking, and quota updates.
*/
require_once __DIR__ . "/../../data/dao/SupervisorDAO.php";

/*
|--------------------------------------------------------------------------
| Quota Validation Strategy Interface
|--------------------------------------------------------------------------
| Defines the required methods for validating quota limits based on
| supervisor employment category or role.
*/
interface QuotaValidationStrategy {

    public function isValid(
        $newQuotaLimit
    );

    public function getFailureMessage();

    public function getMaximumLimit();
}

/*
|--------------------------------------------------------------------------
| Full-Time Quota Strategy
|--------------------------------------------------------------------------
| Validates quota rules for full-time lecturers.
*/
class FullTimeQuotaStrategy implements QuotaValidationStrategy {

    public function isValid(
        $newQuotaLimit
    ) {

        return
            $newQuotaLimit <= 30;
    }

    public function getFailureMessage() {

        return "Full-Time lecturers cannot exceed 30 students per academic year";
    }

    public function getMaximumLimit() {

        return 30;
    }
}

/*
|--------------------------------------------------------------------------
| Part-Time Quota Strategy
|--------------------------------------------------------------------------
| Validates quota rules for part-time lecturers.
*/
class PartTimeQuotaStrategy implements QuotaValidationStrategy {

    public function isValid(
        $newQuotaLimit
    ) {

        return
            $newQuotaLimit <= 10;
    }

    public function getFailureMessage() {

        return "Part-Time lecturers cannot exceed 10 students per semester";
    }

    public function getMaximumLimit() {

        return 10;
    }
}

/*
|--------------------------------------------------------------------------
| Management Role Quota Strategy
|--------------------------------------------------------------------------
| Validates quota rules for supervisors holding management roles.
*/
class ManagementRoleQuotaStrategy implements QuotaValidationStrategy {

    public function isValid(
        $newQuotaLimit
    ) {

        return
            $newQuotaLimit <= 15;
    }

    public function getFailureMessage() {

        return "Management roles cannot exceed 15 students per academic year";
    }

    public function getMaximumLimit() {

        return 15;
    }
}

/*
|--------------------------------------------------------------------------
| Quota Manager
|--------------------------------------------------------------------------
| Handles supervisor quota dashboard data and administrator quota updates.
| Uses strategy classes to validate quota limits by supervisor category.
*/
class QuotaManager {

    /*
    |--------------------------------------------------------------------------
    | DAO Dependency
    |--------------------------------------------------------------------------
    */
    private $supervisorDAO;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    | Initializes supervisor data access dependency.
    */
    public function __construct() {

        $this->supervisorDAO =
            new SupervisorDAO();
    }

    /*
    |--------------------------------------------------------------------------
    | Quota Management Dashboard Data
    |--------------------------------------------------------------------------
    | Retrieves supervisors and calculates quota usage, remaining slots,
    | percentage load, and quota status for display.
    */
    public function getQuotaDashboard(
        $filters
    ) {

        /*
        |--------------------------------------------------------------------------
        | Read Filter Inputs
        |--------------------------------------------------------------------------
        */
        $searchName =
            trim(
                $filters["searchName"] ?? ""
            );

        $programme =
            trim(
                $filters["programme"] ?? ""
            );

        /*
        |--------------------------------------------------------------------------
        | Fetch Supervisors For Management
        |--------------------------------------------------------------------------
        */
        $supervisors =
            $this->supervisorDAO
                ->getSupervisorsForManagement(
                    $searchName,
                    $programme
                );

        /*
        |--------------------------------------------------------------------------
        | Format Supervisor Quota Rows
        |--------------------------------------------------------------------------
        */
        foreach ($supervisors as $index => $supervisor) {

            $currentSupervisees =
                (int) $supervisor["currentSupervisees"];

            $tierQuotaLimit =
                (int) (
                    $supervisor["tierQuotaLimit"] ??
                    $supervisor["maxSuperviseesAllowed"]
                );

            $assignedQuotaLimit =
                isset($supervisor["assignedQuotaLimit"]) &&
                $supervisor["assignedQuotaLimit"] !== null
                    ? (int) $supervisor["assignedQuotaLimit"]
                    : $tierQuotaLimit;

            /*
            |--------------------------------------------------------------------------
            | Resolve Category Quota Limit
            |--------------------------------------------------------------------------
            */
            $strategy =
                $this->resolveQuotaStrategy(
                    $supervisor["employmentCategory"]
                );

            $classificationLimit =
                $strategy->getMaximumLimit();

            /*
            |--------------------------------------------------------------------------
            | Assign Normalized Quota Values
            |--------------------------------------------------------------------------
            */
            $supervisors[$index]["currentSupervisees"] =
                $currentSupervisees;

            $supervisors[$index]["maxSuperviseesAllowed"] =
                $assignedQuotaLimit;

            $supervisors[$index]["tierQuotaLimit"] =
                $tierQuotaLimit;

            $supervisors[$index]["classificationQuotaLimit"] =
                $classificationLimit;

            $supervisors[$index]["assignedQuotaLimit"] =
                $assignedQuotaLimit;

            /*
            |--------------------------------------------------------------------------
            | Calculate Remaining Slots
            |--------------------------------------------------------------------------
            */
            $supervisors[$index]["remainingSlots"] =
                max(
                    0,
                    $assignedQuotaLimit - $currentSupervisees
                );

            /*
            |--------------------------------------------------------------------------
            | Calculate Load Percentage
            |--------------------------------------------------------------------------
            */
            $supervisors[$index]["loadPercentage"] =
                $assignedQuotaLimit > 0
                    ? min(
                        100,
                        round(
                            (
                                $currentSupervisees /
                                $assignedQuotaLimit
                            ) * 100
                        )
                    )
                    : 0;

            /*
            |--------------------------------------------------------------------------
            | Assign Quota Status
            |--------------------------------------------------------------------------
            */
            $supervisors[$index]["quotaStatus"] =
                $assignedQuotaLimit > $classificationLimit ||
                $currentSupervisees > $assignedQuotaLimit
                    ? "Over-Capacity"
                    : "Valid";
        }

        return $supervisors;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Quota Options
    |--------------------------------------------------------------------------
    | Retrieves all available quota configuration tiers.
    */
    public function getQuotaOptions() {

        return
            $this->supervisorDAO
                ->getAllQuotaConfigurations();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Programme Options
    |--------------------------------------------------------------------------
    | Retrieves supervisor programme options for filtering.
    */
    public function getProgrammeOptions() {

        return
            $this->supervisorDAO
                ->getSupervisorProgrammes();
    }

    /*
    |--------------------------------------------------------------------------
    | Update Supervisor Quota
    |--------------------------------------------------------------------------
    | Validates administrator access, quota tier, supervisor workload,
    | employment category limit, and updates one supervisor quota.
    */
    public function updateSupervisorQuota(
        $administratorRole,
        $supervisorID,
        $quotaID,
        $assignedQuotaLimit
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
        | Normalize Inputs
        |--------------------------------------------------------------------------
        */
        $supervisorID =
            trim($supervisorID);

        $quotaID =
            trim($quotaID);

        $assignedQuotaLimit =
            trim($assignedQuotaLimit);

        /*
        |--------------------------------------------------------------------------
        | Required Field Validation
        |--------------------------------------------------------------------------
        */
        if (
            $supervisorID === "" ||
            $quotaID === "" ||
            $assignedQuotaLimit === ""
        ) {

            return $this->failure(
                "Quota invalid: the supervisor quota limit is empty or invalid."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Numeric Input Validation
        |--------------------------------------------------------------------------
        */
        if (
            !ctype_digit($quotaID) ||
            !ctype_digit($assignedQuotaLimit)
        ) {

            return $this->failure(
                "Quota invalid: the supervisor quota limit is empty or invalid."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Load Supervisor Current Workload
        |--------------------------------------------------------------------------
        */
        $supervisorLoad =
            $this->supervisorDAO
                ->getSupervisorLoad(
                    $supervisorID
                );

        if (!$supervisorLoad) {

            return $this->failure(
                "Supervisor record was not found"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Load Selected Quota Tier
        |--------------------------------------------------------------------------
        */
        $quota =
            $this->supervisorDAO
                ->getQuotaByID(
                    (int) $quotaID
                );

        if (!$quota) {

            return $this->failure(
                "Selected quota tier does not exist"
            );
        }

        $currentSupervisees =
            (int) $supervisorLoad["currentSupervisees"];

        $assignedQuotaLimit =
            (int) $assignedQuotaLimit;

        /*
        |--------------------------------------------------------------------------
        | Negative Quota Validation
        |--------------------------------------------------------------------------
        */
        if ($assignedQuotaLimit < 0) {

            return $this->failure(
                "Assigned quota cannot be negative"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Resolve Quota Validation Strategy
        |--------------------------------------------------------------------------
        */
        $strategy =
            $this->resolveQuotaStrategy(
                $supervisorLoad["employmentCategory"]
            );

        $classificationLimit =
            $strategy->getMaximumLimit();

        /*
        |--------------------------------------------------------------------------
        | Selected Quota Tier Validation
        |--------------------------------------------------------------------------
        */
        if (!$strategy->isValid((int) $quota["maxSuperviseesAllowed"])) {

            return $this->failure(
                $strategy->getFailureMessage()
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Assigned Quota Limit Validation
        |--------------------------------------------------------------------------
        */
        if ($assignedQuotaLimit > $classificationLimit) {

            return $this->failure(
                "Quota invalid: supervisor type quota limit is exceeded."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Current Load Validation
        |--------------------------------------------------------------------------
        | Prevents setting quota below current supervised student count.
        */
        if ($currentSupervisees > $assignedQuotaLimit) {

            return $this->failure(
                "Quota invalid: supervisor current student count exceeds the new quota limit."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Update Supervisor Quota
        |--------------------------------------------------------------------------
        */
        $updated =
            $this->supervisorDAO
                ->updateSupervisorQuota(
                    $supervisorID,
                    (int) $quotaID,
                    $assignedQuotaLimit
                );

        if (!$updated) {

            return $this->failure(
                "Supervisor quota could not be updated"
            );
        }

        return $this->success(
            "Update Successful - Supervisor quota limit has been updated. The new base quota is now active."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update Multiple Supervisor Quotas
    |--------------------------------------------------------------------------
    | Processes only changed rows and applies quota validation to each row.
    */
    public function updateSupervisorQuotas(
        $administratorRole,
        $quotaRows
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
        | Submitted Quota Rows Validation
        |--------------------------------------------------------------------------
        */
        if (
            !is_array($quotaRows) ||
            empty($quotaRows)
        ) {

            return $this->failure(
                "No quota changes were submitted"
            );
        }

        $updatedCount =
            0;

        /*
        |--------------------------------------------------------------------------
        | Process Changed Quota Rows
        |--------------------------------------------------------------------------
        */
        foreach ($quotaRows as $supervisorID => $row) {

            $changed =
                isset($row["changed"]) &&
                (string) $row["changed"] === "1";

            if (!$changed) {

                continue;
            }

            $result =
                $this->updateSupervisorQuota(
                    $administratorRole,
                    $supervisorID,
                    $row["quotaID"] ?? "",
                    $row["assignedQuotaLimit"] ?? ""
                );

            if (!$result["success"]) {

                return $this->failure(
                    $result["message"]
                );
            }

            $updatedCount++;
        }

        /*
        |--------------------------------------------------------------------------
        | No Changes Validation
        |--------------------------------------------------------------------------
        */
        if ($updatedCount === 0) {

            return $this->failure(
                "No quota changes were made"
            );
        }

        return $this->success(
            "Update Successful - Supervisor quota limit has been updated. The new base quota is now active."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Resolve Quota Strategy
    |--------------------------------------------------------------------------
    | Selects the correct quota validation strategy based on employment category
    | or management role.
    */
    private function resolveQuotaStrategy(
        $employmentCategory
    ) {

        $normalizedCategory =
            strtolower(
                trim($employmentCategory)
            );

        /*
        |--------------------------------------------------------------------------
        | Part-Time Lecturer Strategy
        |--------------------------------------------------------------------------
        */
        if (
            strpos($normalizedCategory, "part-time") !== false ||
            strpos($normalizedCategory, "part time") !== false
        ) {

            return new PartTimeQuotaStrategy();
        }

        /*
        |--------------------------------------------------------------------------
        | Management Role Strategy
        |--------------------------------------------------------------------------
        */
        if (
            in_array(
                $normalizedCategory,
                [
                    "dean",
                    "deputy dean",
                    "academic director",
                    "programme leader",
                    "program leader",
                    "ad",
                    "pl"
                ],
                true
            )
        ) {

            return new ManagementRoleQuotaStrategy();
        }

        /*
        |--------------------------------------------------------------------------
        | Default Full-Time Lecturer Strategy
        |--------------------------------------------------------------------------
        */
        return new FullTimeQuotaStrategy();
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
