<?php

/*
|--------------------------------------------------------------------------
| Student Tracking System
|--------------------------------------------------------------------------
| Subsystem used by the supervisor report facade to summarize allocated
| supervisee programme distribution for demographic reporting.
*/

class StudentTrackingSystem {

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
    | Applicant Programme Fetch
    |--------------------------------------------------------------------------
    | Pulls allocation data grouped by student programme.
    */

    public function fetchApplicantPrograms($supervisorID, $year = "") {

        return $this->reportDAO->getApplicantProgrammes($supervisorID,$year);
    }

    /*
    |--------------------------------------------------------------------------
    | Programme Count Alias
    |--------------------------------------------------------------------------
    | Keeps the method name aligned with the class diagram operation.
    */

    public function countByMajor($supervisorID, $year = "") {

        return $this->fetchApplicantPrograms($supervisorID, $year);
    }
}

?>
