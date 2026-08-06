<?php

/*
|--------------------------------------------------------------------------
| Student Repository
|--------------------------------------------------------------------------
| Repository layer used by the Admin Report Facade for cohort-related data.
*/

class StudentRepository {

    private $reportDAO;

    /*
    |--------------------------------------------------------------------------
    | Student Repository Adapter
    |--------------------------------------------------------------------------
    | Wraps cohort-related DAO calls for the AdminReportFacade.
    */

    public function __construct($reportDAO) {

        $this->reportDAO = $reportDAO;
    }

    /*
    |--------------------------------------------------------------------------
    | Fetch Filtered Students
    |--------------------------------------------------------------------------
    | Delegates the UC300 cohort query to the DAO.
    */

    public function fetchStudents($filters = []) {

        return $this->reportDAO->fetchStudents($filters);
    }

    /*
    |--------------------------------------------------------------------------
    | Batch Filter Helper
    |--------------------------------------------------------------------------
    | Convenience helper for report flows that need to force a batch filter.
    */

    public function filterByBatch($batch, $filters = []) {

        $filters["batch"] = $batch;

        return $this->fetchStudents($filters);
    }
}

?>
