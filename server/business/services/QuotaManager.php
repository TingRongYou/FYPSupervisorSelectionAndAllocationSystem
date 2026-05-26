<?php

require_once __DIR__ . "/../../data/dao/SupervisorDAO.php";

interface QuotaValidationStrategy {

    public function isValid(
        $newQuotaLimit
    );

    public function getFailureMessage();

    public function getMaximumLimit();
}

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

class QuotaManager {

    private $supervisorDAO;

    public function __construct() {

        $this->supervisorDAO =
            new SupervisorDAO();
    }

    /*
    |--------------------------------------------------------------------------
    | Quota Management Dashboard Data
    |--------------------------------------------------------------------------
    */

    public function getQuotaDashboard(
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

        $supervisors =
            $this->supervisorDAO
            ->getSupervisorsForManagement(
                $searchName,
                $programme
            );

        foreach ($supervisors as $index => $supervisor) {

            $currentSupervisees =
                (int) $supervisor["currentSupervisees"];

            $tierQuotaLimit =
                (int) (
                    $supervisor["tierQuotaLimit"]
                    ?? $supervisor["maxSuperviseesAllowed"]
                );

            $assignedQuotaLimit =
                isset($supervisor["assignedQuotaLimit"])
                && $supervisor["assignedQuotaLimit"] !== null
                ? (int) $supervisor["assignedQuotaLimit"]
                : $tierQuotaLimit;

            $strategy =
                $this->resolveQuotaStrategy(
                    $supervisor["employmentCategory"]
                );

            $classificationLimit =
                $strategy->getMaximumLimit();

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

            $supervisors[$index]["remainingSlots"] =
                max(
                    0,
                    $assignedQuotaLimit - $currentSupervisees
                );

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

            $supervisors[$index]["quotaStatus"] =
                $assignedQuotaLimit > $classificationLimit
                ||
                $currentSupervisees > $assignedQuotaLimit
                ? "Over-Capacity"
                : "Valid";
        }

        return $supervisors;
    }

    public function getQuotaOptions() {

        return
            $this->supervisorDAO
            ->getAllQuotaConfigurations();
    }

    public function getProgrammeOptions() {

        return
            $this->supervisorDAO
            ->getSupervisorProgrammes();
    }

    /*
    |--------------------------------------------------------------------------
    | Update Quota Definition
    |--------------------------------------------------------------------------
    */

    public function updateSupervisorQuota(
        $administratorRole,
        $supervisorID,
        $quotaID,
        $assignedQuotaLimit
    ) {

        if ($administratorRole !== "Administrator") {

            return $this->failure(
                "Access denied"
            );
        }

        $supervisorID =
            trim($supervisorID);

        $quotaID =
            trim($quotaID);

        $assignedQuotaLimit =
            trim($assignedQuotaLimit);

        if (
            $supervisorID === ""
            ||
            $quotaID === ""
            ||
            $assignedQuotaLimit === ""
        ) {

            return $this->failure(
                "Quota invalid: the supervisor quota limit is empty or invalid."
            );
        }

        if (
            !ctype_digit($quotaID)
            ||
            !ctype_digit($assignedQuotaLimit)
        ) {

            return $this->failure(
                "Quota invalid: the supervisor quota limit is empty or invalid."
            );
        }

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

        if ($assignedQuotaLimit < 0) {

            return $this->failure(
                "Assigned quota cannot be negative"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Quota Load Validation
        |--------------------------------------------------------------------------
        | UC200 blocks any quota change that would make the current load invalid.
        */

        $strategy =
            $this->resolveQuotaStrategy(
                $supervisorLoad["employmentCategory"]
            );

        $classificationLimit =
            $strategy->getMaximumLimit();

        if (!$strategy->isValid((int) $quota["maxSuperviseesAllowed"])) {

            return $this->failure(
                $strategy->getFailureMessage()
            );
        }

        if ($assignedQuotaLimit > $classificationLimit) {

            return $this->failure(
                "Quota invalid: supervisor type quota limit is exceeded."
            );
        }

        if ($currentSupervisees > $assignedQuotaLimit) {

            return $this->failure(
                "Quota invalid: supervisor current student count exceeds the new quota limit."
            );
        }

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

    public function updateSupervisorQuotas(
        $administratorRole,
        $quotaRows
    ) {

        if ($administratorRole !== "Administrator") {

            return $this->failure(
                "Access denied"
            );
        }

        if (!is_array($quotaRows) || empty($quotaRows)) {

            return $this->failure(
                "No quota changes were submitted"
            );
        }

        $updatedCount = 0;

        foreach ($quotaRows as $supervisorID => $row) {

            $changed =
                isset($row["changed"])
                &&
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
    | Strategy Resolution
    |--------------------------------------------------------------------------
    */

    private function resolveQuotaStrategy(
        $employmentCategory
    ) {

        $normalizedCategory =
            strtolower(
                trim($employmentCategory)
            );

        if (
            strpos($normalizedCategory, "part-time") !== false
            ||
            strpos($normalizedCategory, "part time") !== false
        ) {

            return new PartTimeQuotaStrategy();
        }

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

        return new FullTimeQuotaStrategy();
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


