<?php

/*
|--------------------------------------------------------------------------
| Quota Analytics
|--------------------------------------------------------------------------
| Subsystem used by the supervisor report facade to calculate personal and
| department workload utilization metrics.
*/

class QuotaAnalytics {

    private $reportDAO;

    /*
    |--------------------------------------------------------------------------
    | DAO Injection
    |--------------------------------------------------------------------------
    */

    public function __construct(
        $reportDAO
    ) {

        $this->reportDAO =
            $reportDAO;
    }

    /*
    |--------------------------------------------------------------------------
    | Personal Fill Rate
    |--------------------------------------------------------------------------
    | Calculates the supervisor's current slot usage as a percentage.
    */

    public function getFillRate(
        $supervisorID
    ) {

        $stats =
            $this->reportDAO
            ->getSlotUtilization(
                $supervisorID
            );

        if (!$stats) {

            return 0;
        }

        $quota =
            (int) ($stats["maxSuperviseesAllowed"] ?? 0);

        if ($quota <= 0) {

            return 0;
        }

        return
            round(
                ((int) $stats["currentSlots"] / $quota) * 100,
                1
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Department Average
    |--------------------------------------------------------------------------
    | Returns the anonymized aggregate fill rate for benchmarking.
    */

    public function getDepartmentAverage() {

        return
            round(
                $this->reportDAO
                ->getDepartmentAverageFillRate(),
                1
            );
    }
}

?>
