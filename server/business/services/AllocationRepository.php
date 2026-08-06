<?php

/*
|--------------------------------------------------------------------------
| Allocation Repository
|--------------------------------------------------------------------------
| Repository layer used by the Admin Report Facade for allocation report
| records and derived workload metrics.
*/

class AllocationRepository {

    private $reportDAO;

    /*
    |--------------------------------------------------------------------------
    | Allocation Repository Adapter
    |--------------------------------------------------------------------------
    | Provides allocation records and reusable fill-rate calculations.
    */

    public function __construct($reportDAO) {

        $this->reportDAO = $reportDAO;
    }

    /*
    |--------------------------------------------------------------------------
    | Allocation Records
    |--------------------------------------------------------------------------
    | Reuses the student/allocation joined data for export and report summaries.
    */

    public function getAllocationRecords($filters = []) {

        return $this->reportDAO->fetchStudents($filters);
    }

    /*
    |--------------------------------------------------------------------------
    | Fill Rate Calculation
    |--------------------------------------------------------------------------
    | Converts current allocation count and quota limit into a percentage.
    */

    public function calculateFillRate($currentTotal, $quotaLimit) {

        $quotaLimit = (int) $quotaLimit;

        if ($quotaLimit <= 0) {
            return 0;
        }

        return round(((int) $currentTotal / $quotaLimit) * 100, 1);
    }
}

?>
