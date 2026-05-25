<?php

require_once __DIR__ . "/../data/SupervisorProfileDAO.php";
require_once __DIR__ . "/../data/VideoStorageDAO.php";
require_once __DIR__ . "/../data/ImageStorageDAO.php";
require_once __DIR__ . "/../data/UserDAO.php";

class SupervisorProfileService {

    /*
    |--------------------------------------------------------------------------
    | Constants
    |--------------------------------------------------------------------------
    */

    private const MAX_PROGRAMME_LENGTH = 100;

    private const MAX_EMPLOYMENT_CATEGORY_LENGTH = 50;

    private const MAX_VIDEO_URL_LENGTH = 255;

    private const MAX_BIO_LENGTH = 500;

    private const MAX_VIDEO_DESCRIPTION_LENGTH = 500;

    /*
    |--------------------------------------------------------------------------
    | Properties
    |--------------------------------------------------------------------------
    */

    private $profileDAO;

    private $videoStorageDAO;

    private $imageStorageDAO;

    private $userDAO;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */

    public function __construct() {

        $this->profileDAO =
            new SupervisorProfileDAO();

        $this->videoStorageDAO =
            new VideoStorageDAO();

        $this->imageStorageDAO =
            new ImageStorageDAO();

        $this->userDAO =
            new UserDAO();
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
        $introVideoLink,
        $supervisorBio,
        $profilePhotoFile = null
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

        $supervisorBio =
            trim($supervisorBio);

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
            strlen($supervisorBio)
            >
            self::MAX_BIO_LENGTH
        ) {

            return $this->failure(
                "Short biography cannot exceed 500 characters"
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

            if ($profilePhotoFile !== null) {

                $photoResult =
                    $this->imageStorageDAO
                    ->storeProfilePhoto(
                        $profilePhotoFile,
                        $supervisorID
                    );

                if (!$photoResult["success"]) {

                    return $this->failure(
                        $photoResult["message"]
                    );
                }

                if ($photoResult["path"] !== null) {

                    $currentProfile =
                        $this->userDAO
                        ->getUserByID(
                            $supervisorID
                        );

                    $photoUpdated =
                        $this->userDAO
                        ->updateProfilePhoto(
                            $supervisorID,
                            $photoResult["path"]
                        );

                    if (!$photoUpdated) {

                        return $this->failure(
                            "Profile photo could not be updated"
                        );
                    }

                    $this->imageStorageDAO
                    ->deleteStoredImage(
                        $currentProfile["profilePhotoPath"] ?? ""
                    );
                }
            }

            $updated =
                $this->profileDAO
                ->updateDigitalBusinessCard(

                    $supervisorID,

                    $programme,

                    $employmentCategory,

                    $introVideoLink,

                    $supervisorBio
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
        $introVideoLink,
        $introVideoDescription
    ) {

        /*
        |--------------------------------------------------------------------------
        | Normalize
        |--------------------------------------------------------------------------
        */

        $introVideoLink =
            trim($introVideoLink);

        $introVideoDescription =
            trim($introVideoDescription);

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

        if (
            strlen($introVideoDescription)
            >
            self::MAX_VIDEO_DESCRIPTION_LENGTH
        ) {

            return $this->failure(
                "Video description cannot exceed 500 characters"
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
                "Introductory video must be a valid YouTube or Vimeo URL, or an uploaded MP4/WebM video"
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

                    $introVideoLink,

                    $introVideoDescription
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

    public function updateIntroVideoFromUpload(
        $supervisorID,
        $uploadedFile,
        $introVideoDescription
    ) {

        $introVideoDescription =
            trim($introVideoDescription);

        if (
            strlen($introVideoDescription)
            >
            self::MAX_VIDEO_DESCRIPTION_LENGTH
        ) {

            return $this->failure(
                "Video description cannot exceed 500 characters"
            );
        }

        $storageResult =
            $this->videoStorageDAO
            ->storeIntroVideo(
                $uploadedFile,
                $supervisorID
            );

        if (!$storageResult["success"]) {

            return $this->failure(
                $storageResult["message"]
            );
        }

        return $this->updateIntroVideo(
            $supervisorID,
            $storageResult["path"],
            $introVideoDescription
        );
    }

    public function removeIntroVideo($supervisorID) {

        $profile =
            $this->profileDAO
            ->getSupervisorProfile(
                $supervisorID
            );

        if (!$profile) {

            return $this->failure(
                "Supervisor profile was not found"
            );
        }

        try {

            $updated =
                $this->profileDAO
                ->updateIntroVideo(
                    $supervisorID,
                    "",
                    ""
                );

            if (!$updated) {

                return $this->failure(
                    "Introductory video could not be removed"
                );
            }

            $this->videoStorageDAO
            ->deleteStoredVideo(
                $profile["introVideoLink"] ?? ""
            );

        } catch (Exception $exception) {

            return $this->failure(
                "System error occurred while removing introductory video"
            );
        }

        return $this->success(
            "Introductory video removed successfully"
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

        if (preg_match(

            "/^\.\.\/storage\/intro_videos\/[A-Za-z0-9_-]+\.(mp4|webm)$/i",

            $url

        ) === 1) {

            return true;
        }

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
