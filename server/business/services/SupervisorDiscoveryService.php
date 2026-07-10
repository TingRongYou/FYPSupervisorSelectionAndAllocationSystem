<?php

require_once __DIR__ . "/../../data/dao/SupervisorDAO.php";
require_once __DIR__ . "/DiscoveryEngine.php";
require_once __DIR__ . "/../../data/dao/TagDAO.php";

class SupervisorDiscoveryService {

    /*
    |--------------------------------------------------------------------------
    | Properties
    |--------------------------------------------------------------------------
    */

    private $supervisorDAO;

    private $tagDAO;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */

    public function __construct() {

        $this->supervisorDAO =
            new SupervisorDAO();

        $this->tagDAO =
            new TagDAO();
    }

    /*
    |--------------------------------------------------------------------------
    | Retrieve Programmes
    |--------------------------------------------------------------------------
    */

    public function getProgrammes() {

        return
            $this->supervisorDAO
            ->getSupervisorProgrammes();
    }

    public function getResearchTags() {

        return $this->tagDAO
            ->getAllTags();
    }

    /*
    |--------------------------------------------------------------------------
    | Main Discovery Algorithm
    |--------------------------------------------------------------------------
    */

    // Manual discovery
    public function discoverSupervisors(
        $filters
    ) {

        $filters =
            $this->normalizeFilters($filters); // Normalize data into standardized format engine expected

        $processor =
            new ManualSearchProcessor(); // Decide strategy to search supervisors

        return $processor
            ->executeSearch($filters); // Trigger search process
    }

    public function getRecommendedMatches(
        $studentID
    ) {

        $processor =
            new RecommendationProcessor();

        return $processor
            ->executeSearch([
                "studentID" => $studentID,
                "quotaStatus" => "Available"
            ]);
    }

    public function hasSavedInterestTags(
        $studentID
    ) {

        return count( // If count > 0, then true, else false
            $this->tagDAO
            ->getStudentTagIDs($studentID)
        ) > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Normalize Filters
    |--------------------------------------------------------------------------
    */

    private function normalizeFilters(
        $filters
    ) {

        $availability =
            trim($filters["availability"] ?? "");

        if (!in_array($availability, ["", "Available", "Full"], true)) {

            $availability = "";
        }

        $quotaStatus =
            trim($filters["quotaStatus"] ?? "");

        if ($quotaStatus === "" && $availability !== "") {

            $quotaStatus = $availability;
        }

        if (!in_array($quotaStatus, ["", "Available", "Full"], true)) {

            $quotaStatus = "";
        }

        $interestTagID =
            trim($filters["interestTagID"] ?? "");

        return [
            "searchName" =>
                trim($filters["searchName"] ?? ""),

            "programme" =>
                trim($filters["programme"] ?? ""),

            "interestTagID" =>
                ctype_digit($interestTagID) ? (int) $interestTagID : 0,

            "availability" =>
                $availability,

            "quotaStatus" =>
                $quotaStatus
        ];
    }
}

?>
