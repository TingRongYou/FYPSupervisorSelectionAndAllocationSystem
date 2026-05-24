<?php

require_once __DIR__ . "/../data/SupervisorProfileDAO.php";

class SupervisorProfileService {

    /*
    |--------------------------------------------------------------------------
    | Constants
    |--------------------------------------------------------------------------
    */

    private const MAX_PROGRAMME_LENGTH = 100;

    private const MAX_EMPLOYMENT_CATEGORY_LENGTH = 50;

    private const MAX_VIDEO_URL_LENGTH = 255;

    /*
    |--------------------------------------------------------------------------
    | Properties
    |--------------------------------------------------------------------------
    */

    private $profileDAO;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */

    public function __construct() {

        $this->profileDAO =
            new SupervisorProfileDAO();
    }

    /*
    |--------------------------------------------------------------------------
    | Retrieve Digital Business Card
    |--------------------------------------------------------------------------
    */

    public function getDigitalBusinessCard(
        $supervisorID
    ) {

        $profile =
            $this->profileDAO
            ->getSupervisorProfile(
                $supervisorID
            );

        if (!$profile) {

            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Normalize Capacity Data
        |--------------------------------------------------------------------------
        */

        $currentSupervisees =
            (int)
            (
                $profile["currentSupervisees"]
                ?? 0
            );

        $maxSuperviseesAllowed =
            (int)
            (
                $profile["maxSuperviseesAllowed"]
                ?? 0
            );

        $availableSlots =
            max(
                0,
                $maxSuperviseesAllowed
                - $currentSupervisees
            );

        /*
        |--------------------------------------------------------------------------
        | Generate Status
        |--------------------------------------------------------------------------
        */

        $status =
            $currentSupervisees
            <
            $maxSuperviseesAllowed

            ? "Available"

            : "Full";

        /*
        |--------------------------------------------------------------------------
        | Attach UI Payload
        |--------------------------------------------------------------------------
        */

        $profile["currentSupervisees"] =
            $currentSupervisees;

        $profile["maxSuperviseesAllowed"] =
            $maxSuperviseesAllowed;

        $profile["availableSlots"] =
            $availableSlots;

        $profile["status"] =
            $status;

        $profile["quotaText"] =
            $currentSupervisees
            . "/"
            . $maxSuperviseesAllowed
            . " supervisees";

        return $profile;
    }

    /*
    |--------------------------------------------------------------------------
    | Update Digital Business Card
    |--------------------------------------------------------------------------
    */

    public function updateDigitalBusinessCard(
        $supervisorID,
        $programme,
        $employmentCategory,
        $introVideoLink
    ) {

        /*
        |--------------------------------------------------------------------------
        | Normalize Input
        |--------------------------------------------------------------------------
        */

        $programme =
            trim($programme);

        $employmentCategory =
            trim($employmentCategory);

        $introVideoLink =
            trim($introVideoLink);

        /*
        |--------------------------------------------------------------------------
        | Validate Programme
        |--------------------------------------------------------------------------
        */

        if ($programme === "") {

            return $this->failure(
                "Programme is required"
            );
        }

        if (
            strlen($programme)
            >
            self::MAX_PROGRAMME_LENGTH
        ) {

            return $this->failure(
                "Programme cannot exceed 100 characters"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Employment Category
        |--------------------------------------------------------------------------
        */

        if ($employmentCategory === "") {

            return $this->failure(
                "Employment category is required"
            );
        }

        if (
            strlen($employmentCategory)
            >
            self::MAX_EMPLOYMENT_CATEGORY_LENGTH
        ) {

            return $this->failure(
                "Employment category cannot exceed 50 characters"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Video URL
        |--------------------------------------------------------------------------
        */

        if (
            strlen($introVideoLink)
            >
            self::MAX_VIDEO_URL_LENGTH
        ) {

            return $this->failure(
                "Introductory video URL cannot exceed 255 characters"
            );
        }

        if (
            $introVideoLink !== ""
            &&
            !$this->isValidVideoUrl(
                $introVideoLink
            )
        ) {

            return $this->failure(
                "Introductory video must be a valid YouTube or Vimeo URL"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Execute Update
        |--------------------------------------------------------------------------
        */

        try {

            $updated =
                $this->profileDAO
                ->updateDigitalBusinessCard(

                    $supervisorID,

                    $programme,

                    $employmentCategory,

                    $introVideoLink
                );

            if (!$updated) {

                return $this->failure(
                    "Digital business card could not be updated"
                );
            }

        } catch (Exception $exception) {

            return $this->failure(
                "System error occurred while updating business card"
            );
        }

        return $this->success(
            "Digital business card updated successfully"
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update Introductory Video
    |--------------------------------------------------------------------------
    */

    public function updateIntroVideo(
        $supervisorID,
        $introVideoLink
    ) {

        /*
        |--------------------------------------------------------------------------
        | Normalize
        |--------------------------------------------------------------------------
        */

        $introVideoLink =
            trim($introVideoLink);

        /*
        |--------------------------------------------------------------------------
        | Validate Empty
        |--------------------------------------------------------------------------
        */

        if ($introVideoLink === "") {

            return $this->failure(
                "Introductory video URL is required"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Length
        |--------------------------------------------------------------------------
        */

        if (
            strlen($introVideoLink)
            >
            self::MAX_VIDEO_URL_LENGTH
        ) {

            return $this->failure(
                "Introductory video URL cannot exceed 255 characters"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validate URL
        |--------------------------------------------------------------------------
        */

        if (
            !$this->isValidVideoUrl(
                $introVideoLink
            )
        ) {

            return $this->failure(
                "Introductory video must be a valid YouTube or Vimeo URL"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Execute Update
        |--------------------------------------------------------------------------
        */

        try {

            $updated =
                $this->profileDAO
                ->updateIntroVideo(

                    $supervisorID,

                    $introVideoLink
                );

            if (!$updated) {

                return $this->failure(
                    "Introductory video could not be updated"
                );
            }

        } catch (Exception $exception) {

            return $this->failure(
                "System error occurred while updating introductory video"
            );
        }

        return $this->success(
            "Introductory video updated successfully"
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Video URL
    |--------------------------------------------------------------------------
    */

    private function isValidVideoUrl(
        $url
    ) {

        /*
        |--------------------------------------------------------------------------
        | General URL Validation
        |--------------------------------------------------------------------------
        */

        if (
            !filter_var(
                $url,
                FILTER_VALIDATE_URL
            )
        ) {

            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Allow Only YouTube & Vimeo
        |--------------------------------------------------------------------------
        */

        return preg_match(

            "/^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be|vimeo\.com)\/.+$/i",

            $url

        ) === 1;
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