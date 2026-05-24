<?php

require_once __DIR__ . "/../data/SupervisorDAO.php";

class SupervisorDiscoveryService {

    /*
    |--------------------------------------------------------------------------
    | Properties
    |--------------------------------------------------------------------------
    */

    private $supervisorDAO;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */

    public function __construct() {

        $this->supervisorDAO =
            new SupervisorDAO();
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

    /*
    |--------------------------------------------------------------------------
    | Main Discovery Algorithm
    |--------------------------------------------------------------------------
    */

    public function discoverSupervisors(
        $filters
    ) {

        /*
        |--------------------------------------------------------------------------
        | Normalize Filters
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

        $availability =
            trim(
                $filters["availability"] ?? ""
            );

        /*
        |--------------------------------------------------------------------------
        | Validate Availability
        |--------------------------------------------------------------------------
        */

        if (
            !$this->isValidAvailability(
                $availability
            )
        ) {

            $availability = "";
        }

        /*
        |--------------------------------------------------------------------------
        | Retrieve Supervisors
        |--------------------------------------------------------------------------
        */

        $supervisors =
            $this->supervisorDAO
            ->getSupervisorsForDiscovery(

                $searchName,

                $programme,

                $availability
            );

        /*
        |--------------------------------------------------------------------------
        | Prepare Results
        |--------------------------------------------------------------------------
        */

        $discoveryResults = [];

        foreach (
            $supervisors
            as $supervisor
        ) {

            /*
            |--------------------------------------------------------------------------
            | Normalize Capacity Data
            |--------------------------------------------------------------------------
            */

            $activeStudents =
                (int)
                (
                    $supervisor["activeStudents"]
                    ?? 0
                );

            $maxSuperviseesAllowed =
                (int)
                (
                    $supervisor["maxSuperviseesAllowed"]
                    ?? 0
                );

            /*
            |--------------------------------------------------------------------------
            | Prevent Negative Slot Values
            |--------------------------------------------------------------------------
            */

            $availableSlots =
                max(
                    0,
                    $maxSuperviseesAllowed
                    - $activeStudents
                );

            /*
            |--------------------------------------------------------------------------
            | Generate Quota Text
            |--------------------------------------------------------------------------
            */

            $quotaText =
                $activeStudents
                . "/"
                . $maxSuperviseesAllowed
                . " slots taken";

            /*
            |--------------------------------------------------------------------------
            | Determine Status
            |--------------------------------------------------------------------------
            */

            $status =
                $this->determineSupervisorStatus(
                    $activeStudents,
                    $maxSuperviseesAllowed
                );

            /*
            |--------------------------------------------------------------------------
            | Append Discovery Payload
            |--------------------------------------------------------------------------
            */

            $discoveryResults[] = [

                "userID" =>
                    $supervisor["userID"],

                "fullName" =>
                    $supervisor["fullName"],

                "programme" =>
                    $supervisor["programme"],

                "employmentCategory" =>
                    $supervisor["employmentCategory"],

                "introVideoLink" =>
                    $supervisor["introVideoLink"]
                    ?? "",

                "activeStudents" =>
                    $activeStudents,

                "availableSlots" =>
                    $availableSlots,

                "maxSlots" =>
                    $maxSuperviseesAllowed,

                "quotaText" =>
                    $quotaText,

                "status" =>
                    $status
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Sort Results
        |--------------------------------------------------------------------------
        */

        usort(

            $discoveryResults,

            function (
                $first,
                $second
            ) {

                /*
                |--------------------------------------------------------------------------
                | Available Supervisors First
                |--------------------------------------------------------------------------
                */

                if (
                    $first["status"]
                    !==
                    $second["status"]
                ) {

                    return
                        $first["status"]
                        === "Available"
                        ? -1
                        : 1;
                }

                /*
                |--------------------------------------------------------------------------
                | Then Sort By Name
                |--------------------------------------------------------------------------
                */

                return strcmp(
                    $first["fullName"],
                    $second["fullName"]
                );
            }
        );

        return
            $discoveryResults;
    }

    /*
    |--------------------------------------------------------------------------
    | Determine Availability Status
    |--------------------------------------------------------------------------
    */

    private function determineSupervisorStatus(
        $activeStudents,
        $maxSuperviseesAllowed
    ) {

        return
            $activeStudents
            <
            $maxSuperviseesAllowed

            ? "Available"

            : "Full";
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Availability Filter
    |--------------------------------------------------------------------------
    */

    private function isValidAvailability(
        $availability
    ) {

        return in_array(

            $availability,

            [
                "",
                "Available",
                "Full"
            ],

            true
        );
    }
}

?>