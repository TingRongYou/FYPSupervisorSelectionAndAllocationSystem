<?php

require_once __DIR__ . "/../data/TagDAO.php";

class TagManagementService {

    /*
    |--------------------------------------------------------------------------
    | Constants
    |--------------------------------------------------------------------------
    */

    private const MINIMUM_TAG_SELECTION = 1;

    private const MAXIMUM_TAG_SELECTION = 10;

    /*
    |--------------------------------------------------------------------------
    | Properties
    |--------------------------------------------------------------------------
    */

    private $tagDAO;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */

    public function __construct() {

        $this->tagDAO =
            new TagDAO();
    }

    /*
    |--------------------------------------------------------------------------
    | Retrieve All Tags
    |--------------------------------------------------------------------------
    */

    public function getAllTags() {

        return
            $this->tagDAO
            ->getAllTags();
    }

    /*
    |--------------------------------------------------------------------------
    | Retrieve Selected Supervisor Tag IDs
    |--------------------------------------------------------------------------
    */

    public function getSupervisorTagIDs(
        $supervisorID
    ) {

        $tagIDs =
            $this->tagDAO
            ->getSupervisorTagIDs(
                $supervisorID
            );

        return
            array_map(
                "intval",
                $tagIDs
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Update Supervisor Expertise Tags
    |--------------------------------------------------------------------------
    */

    public function updateSupervisorTags(
        $supervisorID,
        $tagIDs
    ) {

        /*
        |--------------------------------------------------------------------------
        | Validate Input Type
        |--------------------------------------------------------------------------
        */

        if (!is_array($tagIDs)) {

            $tagIDs = [];
        }

        /*
        |--------------------------------------------------------------------------
        | Normalize Tag IDs
        |--------------------------------------------------------------------------
        */

        $tagIDs =
            array_values(

                array_unique(

                    array_map(
                        "intval",
                        $tagIDs
                    )
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Validate Minimum Selection
        |--------------------------------------------------------------------------
        */

        if (
            count($tagIDs)
            <
            self::MINIMUM_TAG_SELECTION
        ) {

            return $this->failure(
                "Please select at least one expertise tag"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Maximum Selection
        |--------------------------------------------------------------------------
        */

        if (
            count($tagIDs)
            >
            self::MAXIMUM_TAG_SELECTION
        ) {

            return $this->failure(
                "A maximum of 10 expertise tags can be selected"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Positive Integer IDs
        |--------------------------------------------------------------------------
        */

        foreach ($tagIDs as $tagID) {

            if (
                !$this->isPositiveInteger(
                    $tagID
                )
            ) {

                return $this->failure(
                    "Invalid expertise tag detected"
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Tag Existence
        |--------------------------------------------------------------------------
        */

        try {

            $validTags =
                $this->tagDAO
                ->tagIDsExist(
                    $tagIDs
                );

            if (!$validTags) {

                return $this->failure(
                    "One or more selected tags are invalid"
                );
            }

        } catch (Exception $exception) {

            return $this->failure(
                "Unable to validate expertise tags"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Replace Tags Transaction
        |--------------------------------------------------------------------------
        */

        try {

            /*
            |--------------------------------------------------------------------------
            | Multi-step replacement:
            | 1. Delete old tags
            | 2. Insert new tags
            |--------------------------------------------------------------------------
            */

            $updated =
                $this->tagDAO
                ->replaceSupervisorTags(
                    $supervisorID,
                    $tagIDs
                );

            if (!$updated) {

                return $this->failure(
                    "Expertise tags could not be updated"
                );
            }

        } catch (Exception $exception) {

            return $this->failure(
                "System error occurred while updating expertise tags"
            );
        }

        return $this->success(
            "Expertise tags updated successfully"
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Positive Integer
    |--------------------------------------------------------------------------
    */

    private function isPositiveInteger(
        $value
    ) {

        return
            is_int($value)
            &&
            $value > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Success Response
    |--------------------------------------------------------------------------
    */

    private function success(
        $message
    ) {

        return [

            "success" => true,

            "message" => $message
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Failure Response
    |--------------------------------------------------------------------------
    */

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