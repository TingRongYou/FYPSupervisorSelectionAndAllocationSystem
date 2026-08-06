<?php

/*
|--------------------------------------------------------------------------
| Supervisor Repository
|--------------------------------------------------------------------------
| Repository layer used by the Admin Report Facade for supervisor capacity
| and quota-related report data.
*/

class SupervisorRepository {

    private $reportDAO;

    /*
    |--------------------------------------------------------------------------
    | Supervisor Repository Adapter
    |--------------------------------------------------------------------------
    | Provides supervisor capacity records and quota helper access.
    */

    public function __construct($reportDAO) {

        $this->reportDAO = $reportDAO;
    }

    /*
    |--------------------------------------------------------------------------
    | Fetch Filtered Supervisors
    |--------------------------------------------------------------------------
    | Retrieves supervisors for the UC301 allocation summary roster.
    */

    public function fetchSupervisors($programme = "") {

        return $this->reportDAO->fetchSupervisors($programme);
    }

    /*
    |--------------------------------------------------------------------------
    | Quota Limit Helper
    |--------------------------------------------------------------------------
    | Normalizes quota values before capacity calculations are performed.
    */

    public function getQuotaLimit($supervisor) {

        return (int) ($supervisor["maxSuperviseesAllowed"] ?? 0);
    }
}

?>
