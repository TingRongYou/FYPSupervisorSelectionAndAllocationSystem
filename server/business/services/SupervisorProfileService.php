<?php

require_once __DIR__ . "/../../data/dao/SupervisorProfileDAO.php";
require_once __DIR__ . "/../../data/storage/VideoStorageDAO.php";
require_once __DIR__ . "/../../data/storage/ImageStorageDAO.php";
require_once __DIR__ . "/../../data/dao/UserDAO.php";
require_once __DIR__ . "/../../data/dao/TagDAO.php";
require_once __DIR__ . "/../../data/dao/PastProjectDAO.php";
require_once __DIR__ . "/../decorators/BasicSupervisorProfile.php";
require_once __DIR__ . "/../decorators/ExpertiseDecorator.php";
require_once __DIR__ . "/../decorators/VideoDecorator.php";
require_once __DIR__ . "/../decorators/ProjectShowcaseDecorator.php";
require_once __DIR__ . "/SupervisorAvailabilityService.php";

class SupervisorProfileService {

    /*
    |--------------------------------------------------------------------------
    | Constants
    |--------------------------------------------------------------------------
    */

    private const MAX_PROGRAMME_LENGTH = 100;

    private const MAX_EMPLOYMENT_CATEGORY_LENGTH = 50;

    private const MAX_ACTIVE_TIME_LENGTH = 100;

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

    private $tagDAO;

    private $pastProjectDAO;

    private $availabilityService;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */

    public function __construct() {

        $this->profileDAO = new SupervisorProfileDAO();

        $this->videoStorageDAO = new VideoStorageDAO();

        $this->imageStorageDAO = new ImageStorageDAO();

        $this->userDAO = new UserDAO();

        $this->tagDAO = new TagDAO();

        $this->pastProjectDAO = new PastProjectDAO();

        $this->availabilityService = new SupervisorAvailabilityService();
    }

    /*
    |--------------------------------------------------------------------------
    | Retrieve Digital Business Card
    |--------------------------------------------------------------------------
    */

    public function getDigitalBusinessCard($supervisorID) {

        $profile = $this->profileDAO->getSupervisorProfile($supervisorID);

        if (!$profile) {

            return null;
        }

        return $this->availabilityService->decorateAvailability($profile);
    }

    /*
    |--------------------------------------------------------------------------
    | Retrieve Decorated Public Professional Profile
    |--------------------------------------------------------------------------
    */

    // Get supervisor profile through decorator, wrap only profile information that are available
    public function getPublicProfessionalProfile( $supervisorID) {

        $profile = $this->getDigitalBusinessCard($supervisorID);

        if (!$profile) {

            return null;
        }

        $selectedTagIDs = $this->tagDAO->getSupervisorTagIDs($supervisorID);

        $expertiseTags =
            array_map(
                function ($tag) {

                    return $tag["tagName"];
                },
                $this->tagDAO->getTagNamesByIDs($selectedTagIDs)
            );

        $pastProjects =
            $this->pastProjectDAO->getProjectsBySupervisor($supervisorID);

        $hasPublishedVideo =
            strtolower($profile["introVideoStatus"] ?? "") === "published" && trim((string) ($profile["introVideoLink"] ?? "")) !== "";

        if (!$hasPublishedVideo) {

            $profile["introVideoLink"] = "";
            $profile["introVideoDescription"] = "";
            $profile["hasIntroVideo"] = false;
        }

        $decoratedProfile = new BasicSupervisorProfile($profile );

        $decoratedProfile = new ExpertiseDecorator($decoratedProfile, $expertiseTags);

        if ($hasPublishedVideo) {

            $decoratedProfile = new VideoDecorator($decoratedProfile, $profile["introVideoLink"] ?? "", $profile["introVideoDescription"] ?? "");
        }

        $decoratedProfile = new ProjectShowcaseDecorator($decoratedProfile, $pastProjects);

        return $decoratedProfile->display();
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
        $activeTime,
        $introVideoLink,
        $supervisorBio,
        $profilePhotoFile = null
    ) {

        /*
        |--------------------------------------------------------------------------
        | Normalize Input
        |--------------------------------------------------------------------------
        */

        $programme = trim($programme);

        $employmentCategory = trim($employmentCategory);

        $activeTime = trim($activeTime);

        $introVideoLink = trim($introVideoLink);

        $supervisorBio = trim($supervisorBio);

        /*
        |--------------------------------------------------------------------------
        | Validate Programme
        |--------------------------------------------------------------------------
        */

        if ($programme === "") {

            return $this->failure("Validation Error - Please complete all required fields before saving your profile.");
        }

        if (strlen($programme) > self::MAX_PROGRAMME_LENGTH) {

            return $this->failure("Validation Error - Please complete all required fields before saving your profile.");
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Employment Category
        |--------------------------------------------------------------------------
        */

        if ($employmentCategory === "") {

            return $this->failure("Validation Error - Please complete all required fields before saving your profile.");
        }

        if (strlen($employmentCategory) > self::MAX_EMPLOYMENT_CATEGORY_LENGTH) {

            return $this->failure("Validation Error - Please complete all required fields before saving your profile.");
        }

        if ($activeTime === "") {

            return $this->failure("Validation Error - Please complete all required fields before saving your profile.");
        }

        if (strlen($activeTime) > self::MAX_ACTIVE_TIME_LENGTH
        ) {

            return $this->failure("Validation Error - Please complete all required fields before saving your profile.");
        }

        if ($supervisorBio === "") {

            return $this->failure("Validation Error - Please complete all required fields before saving your profile.");
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Video URL
        |--------------------------------------------------------------------------
        */

        if (strlen($introVideoLink) > self::MAX_VIDEO_URL_LENGTH) {

            return $this->failure("Invalid Format/Size - The uploaded file is not supported or exceeds the 50MB limit. Please provide a valid MP4 file or URL.");
        }

        if (strlen($supervisorBio) > self::MAX_BIO_LENGTH) {

            return $this->failure("Validation Error - Please complete all required fields before saving your profile.");
        }

        if ($introVideoLink !== "" && !$this->isValidVideoUrl($introVideoLink)) {

            return $this->failure("Invalid Format/Size - The uploaded file is not supported or exceeds the 50MB limit. Please provide a valid MP4 file or URL.");
        }

        /*
        |--------------------------------------------------------------------------
        | Execute Update
        |--------------------------------------------------------------------------
        */

        try {

            if ($profilePhotoFile !== null) {

                $photoResult = $this->imageStorageDAO->storeProfilePhoto($profilePhotoFile, $supervisorID);

                if (!$photoResult["success"]) {

                    return $this->failure($photoResult["message"]);
                }

                if ($photoResult["path"] !== null) {

                    $currentProfile = $this->userDAO->getUserByID($supervisorID);

                    $photoUpdated = $this->userDAO->updateProfilePhoto($supervisorID, $photoResult["path"]);

                    if (!$photoUpdated) {

                        return $this->failure("Profile photo could not be updated");
                    }

                    $this->imageStorageDAO->deleteStoredImage($currentProfile["profilePhotoPath"] ?? "");
                }
            }

            $updated =
                $this->profileDAO
                ->updateDigitalBusinessCard(

                    $supervisorID,

                    $programme,

                    $employmentCategory,

                    $activeTime,

                    $introVideoLink,

                    $supervisorBio
                );

            if (!$updated) {

                return $this->failure("Digital business card could not be updated");
            }

        } catch (Exception $exception) {

            return $this->failure("System error occurred while updating business card");
        }

        return $this->success("Update Successful - Your professional details have been updated successfully.");
    }

    /*
    |--------------------------------------------------------------------------
    | Update Introductory Video
    |--------------------------------------------------------------------------
    */

    public function updateIntroVideo($supervisorID, $introVideoLink, $introVideoDescription) {

        /*
        |--------------------------------------------------------------------------
        | Normalize
        |--------------------------------------------------------------------------
        */

        $introVideoLink = trim($introVideoLink);

        $introVideoDescription = trim($introVideoDescription);

        /*
        |--------------------------------------------------------------------------
        | Validate Empty
        |--------------------------------------------------------------------------
        */

        if ($introVideoLink === "") {

            return $this->failure("Invalid Format/Size - The uploaded file is not supported or exceeds the 50MB limit. Please provide a valid MP4 file or URL.");
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Length
        |--------------------------------------------------------------------------
        */

        if (strlen($introVideoLink) > self::MAX_VIDEO_URL_LENGTH) {

            return $this->failure("Invalid Format/Size - The uploaded file is not supported or exceeds the 50MB limit. Please provide a valid MP4 file or URL.");
        }

        if (strlen($introVideoDescription) > self::MAX_VIDEO_DESCRIPTION_LENGTH) {

            return $this->failure("Validation Error - Please complete all required fields before saving your profile.");
        }

        /*
        |--------------------------------------------------------------------------
        | Validate URL
        |--------------------------------------------------------------------------
        */

        if (!$this->isValidVideoUrl($introVideoLink)) {

            return $this->failure("Invalid Format/Size - The uploaded file is not supported or exceeds the 50MB limit. Please provide a valid MP4 file or URL.");
        }

        /*
        |--------------------------------------------------------------------------
        | Execute Update
        |--------------------------------------------------------------------------
        */

        try {

            $updated = $this->profileDAO->updateIntroVideo($supervisorID, $introVideoLink, $introVideoDescription, "published");

            if (!$updated) {

                return $this->failure("Introductory video could not be updated");
            }

        } catch (Exception $exception) {

            return $this->failure("System error occurred while updating introductory video");
        }

        return $this->success("Update Successful - Your introductory video has been successfully updated and is now visible to students.");
    }

    public function updateIntroVideoFromUpload($supervisorID, $uploadedFile, $introVideoDescription) {

        $introVideoDescription = trim($introVideoDescription);

        if (strlen($introVideoDescription) > self::MAX_VIDEO_DESCRIPTION_LENGTH) {

            return $this->failure("Validation Error - Please complete all required fields before saving your profile.");
        }

        $storageResult = $this->videoStorageDAO->storeIntroVideo($uploadedFile, $supervisorID);

        if (!$storageResult["success"]) {

            return $this->failure($storageResult["message"]);
        }

        return $this->updateIntroVideo($supervisorID, $storageResult["path"], $introVideoDescription);
    }

    public function saveIntroVideoDraft($supervisorID, $introVideoLink, $introVideoDescription, $uploadedFile = null) {

        $introVideoLink = trim($introVideoLink);

        $introVideoDescription = trim($introVideoDescription);

        if (
            is_array($uploadedFile)
            &&
            isset($uploadedFile["error"])
            &&
            (int) $uploadedFile["error"] !== UPLOAD_ERR_NO_FILE
        ) {

            $storageResult = $this->videoStorageDAO->storeIntroVideo($uploadedFile, $supervisorID);

            if (!$storageResult["success"]) {

                return $this->failure($storageResult["message"]);
            }

            $introVideoLink = $storageResult["path"];
        }

        if ($introVideoLink === "") {

            $profile = $this->profileDAO->getSupervisorProfile($supervisorID);

            $introVideoLink = trim($profile["introVideoLink"] ?? "");
        }

        if (strlen($introVideoDescription) > self::MAX_VIDEO_DESCRIPTION_LENGTH) {

            return $this->failure("Validation Error - Please complete all required fields before saving your profile.");
        }

        if ($introVideoLink !== "" &&!$this->isValidVideoUrl($introVideoLink)
        ) {

            return $this->failure("Invalid Format/Size - The uploaded file is not supported or exceeds the 50MB limit. Please provide a valid MP4 file or URL.");
        }

        try {

            $updated = $this->profileDAO->updateIntroVideo($supervisorID, $introVideoLink, $introVideoDescription, "draft");

            if (!$updated) {

                return $this->failure("Introductory video draft could not be saved");
            }

        } catch (Exception $exception) {

            return $this->failure("System error occurred while saving introductory video draft");
        }

        return $this->success("Introductory video draft saved successfully.");
    }

    public function removeIntroVideo($supervisorID) {

        $profile = $this->profileDAO->getSupervisorProfile($supervisorID);

        if (!$profile) {

            return $this->failure("Supervisor profile was not found");
        }

        try {

            $updated = $this->profileDAO->updateIntroVideo($supervisorID, "", "", "draft");

            if (!$updated) {

                return $this->failure("Introductory video could not be removed");
            }

            $this->videoStorageDAO->deleteStoredVideo($profile["introVideoLink"] ?? "");

        } catch (Exception $exception) {

            return $this->failure("System error occurred while removing introductory video");
        }

        return $this->success("Introductory video removed successfully");
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Video URL
    |--------------------------------------------------------------------------
    */

    private function isValidVideoUrl($url) {

        if (preg_match("/\/storage\/intro_videos\/[A-Za-z0-9_-]+\.(mp4|webm)$/i", $url) === 1) {

            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | General URL Validation
        |--------------------------------------------------------------------------
        */

        if (!filter_var($url, FILTER_VALIDATE_URL)) {

            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Allow Only YouTube & Vimeo
        |--------------------------------------------------------------------------
        */

        return preg_match("/^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be|vimeo\.com)\/.+$/i", $url) === 1;
    }

    /*
    |--------------------------------------------------------------------------
    | Success Response
    |--------------------------------------------------------------------------
    */

    private function success($message) {

        return ["success" => true, "message" => $message];
    }

    /*
    |--------------------------------------------------------------------------
    | Failure Response
    |--------------------------------------------------------------------------
    */

    private function failure($message) {

        return [ "success" => false,"message" => $message];
    }
}

?>
