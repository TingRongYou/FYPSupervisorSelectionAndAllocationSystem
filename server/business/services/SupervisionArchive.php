<?php

/*
|--------------------------------------------------------------------------
| Supervision Archive
|--------------------------------------------------------------------------
| Subsystem used by the supervisor report facade to retrieve and sort
| supervision assignment records for history-log reporting.
*/

class SupervisionArchive {

    private $reportDAO;

    /*
    |--------------------------------------------------------------------------
    | DAO Injection
    |--------------------------------------------------------------------------
    */

    public function __construct($reportDAO) {

        $this->reportDAO = $reportDAO;
    }

    /*
    |--------------------------------------------------------------------------
    | Past Student Retrieval
    |--------------------------------------------------------------------------
    | Fetches students allocated to the current supervisor.
    */

    public function getPastStudents($supervisorID, $year = "") {

        return $this->reportDAO->getSupervisionHistory($supervisorID, $year);
    }

    /*
    |--------------------------------------------------------------------------
    | Chronological Sorting
    |--------------------------------------------------------------------------
    | Orders history rows by newest allocation first, then student name A-Z.
    */

    public function sortByYear($historyRows) {

        usort(
            $historyRows,
            function ($first, $second) {

                $dateCompare =
                    strtotime($second["allocationDate"] ?? "1970-01-01")
                    <=>
                    strtotime($first["allocationDate"] ?? "1970-01-01");

                if ($dateCompare !== 0) {
                    return $dateCompare;
                }

                $nameCompare =
                    strcasecmp(
                        (string) ($first["alumniName"] ?? ""),
                        (string) ($second["alumniName"] ?? "")
                    );

                if ($nameCompare !== 0) {
                    return $nameCompare;
                }

                return
                    (int) ($second["allocationID"] ?? 0)
                    <=>
                    (int) ($first["allocationID"] ?? 0);
            }
        );

        return $historyRows;
    }
}

?>
