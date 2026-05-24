<?php

require_once __DIR__ . "/../data/SupervisorDAO.php";

class SupervisorManagementService {

    private const MAX_EMPLOYMENT_CATEGORY_LENGTH = 50;

    private $supervisorDAO;

    public function __construct() {

        $this->supervisorDAO =
            new SupervisorDAO();
    }

    /*
    |--------------------------------------------------------------------------
    | Supervisor Directory
    |--------------------------------------------------------------------------
    */

    public function getSupervisorDirectory(
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

            $maxSuperviseesAllowed =
                (int) $supervisor["maxSuperviseesAllowed"];

            $supervisors[$index]["currentSupervisees"] =
                $currentSupervisees;

            $supervisors[$index]["maxSuperviseesAllowed"] =
                $maxSuperviseesAllowed;

            $supervisors[$index]["loadPercentage"] =
                $maxSuperviseesAllowed > 0
                ? min(
                    100,
                    round(
                        (
                            $currentSupervisees
                            /
                            $maxSuperviseesAllowed
                        )
                        *
                        100
                    )
                )
                : 0;

            $supervisors[$index]["quotaText"] =
                $currentSupervisees
                . "/"
                . $maxSuperviseesAllowed;

            $supervisors[$index]["availabilityStatus"] =
                $currentSupervisees < $maxSuperviseesAllowed
                ? "Available"
                : "Full";
        }

        return $supervisors;
    }

    /*
    |--------------------------------------------------------------------------
    | Quota Options
    |--------------------------------------------------------------------------
    */

    public function getQuotaOptions() {

        return
            $this->supervisorDAO
            ->getAllQuotaConfigurations();
    }

    /*
    |--------------------------------------------------------------------------
    | Programme Options
    |--------------------------------------------------------------------------
    */

    public function getProgrammeOptions() {

        return
            $this->supervisorDAO
            ->getSupervisorProgrammes();
    }

    /*
    |--------------------------------------------------------------------------
    | Classify Supervisor Role
    |--------------------------------------------------------------------------
    */

    public function classifySupervisorRole(
        $administratorRole,
        $supervisorID,
        $employmentCategory,
        $quotaID
    ) {

        if ($administratorRole !== "Administrator") {

            return $this->failure(
                "Access denied"
            );
        }

        $supervisorID =
            trim($supervisorID);

        $employmentCategory =
            trim($employmentCategory);

        $quotaID =
            trim($quotaID);

        if (
            $supervisorID === ""
            ||
            $employmentCategory === ""
            ||
            $quotaID === ""
        ) {

            return $this->failure(
                "Supervisor, classification, and quota are required"
            );
        }

        if (
            strlen($employmentCategory)
            >
            self::MAX_EMPLOYMENT_CATEGORY_LENGTH
        ) {

            return $this->failure(
                "Classification cannot exceed 50 characters"
            );
        }

        if (!ctype_digit($quotaID)) {

            return $this->failure(
                "Invalid quota selection"
            );
        }

        $currentLoad =
            $this->supervisorDAO
            ->getSupervisorLoad(
                $supervisorID
            );

        if (!$currentLoad) {

            return $this->failure(
                "Supervisor record was not found"
            );
        }

        $newQuota =
            $this->supervisorDAO
            ->getQuotaByID(
                (int) $quotaID
            );

        if (!$newQuota) {

            return $this->failure(
                "Selected quota tier does not exist"
            );
        }

        $currentSupervisees =
            (int) $currentLoad["currentSupervisees"];

        $newQuotaLimit =
            (int) $newQuota["maxSuperviseesAllowed"];

        /*
        |--------------------------------------------------------------------------
        | Quota Conflict Validation
        |--------------------------------------------------------------------------
        | UC100 prevents a role/quota change if the supervisor's current load
        | exceeds the selected classification quota.
        */

        if ($currentSupervisees > $newQuotaLimit) {

            return $this->failure(
                "Quota conflict: current supervisee count exceeds the selected quota limit"
            );
        }

        $updated =
            $this->supervisorDAO
            ->updateSupervisorClassification(
                $supervisorID,
                $employmentCategory,
                (int) $quotaID
            );

        if (!$updated) {

            return $this->failure(
                "Supervisor classification could not be updated"
            );
        }

        return $this->success(
            "Supervisor classification updated successfully"
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
