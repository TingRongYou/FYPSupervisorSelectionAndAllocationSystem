<?php

require_once __DIR__ . "/../../data/dao/StudentProfileDAO.php";
require_once __DIR__ . "/../../data/dao/TagDAO.php";
require_once __DIR__ . "/../../data/storage/ImageStorageDAO.php";

class StudentProfileFacade {

    // Physical enforcement of Functional Constraint
    private const MAX_BIO_LENGTH = 500;
    private const MAX_TAGS = 5;
    private const MAX_AVATAR_SIZE = 2097152; // 2.0 MB payload limit
    private const VALID_URL_PATTERN = "/^https?:\/\/[^\s\/$.?#].[^\s]*$/i";

    // Subsystem collaborators
    private $studentProfileDAO;
    private $tagDAO;
    private $imageStorageDAO;

    public function __construct() {
        // Composition of underlying data access objects
        $this->studentProfileDAO = new StudentProfileDAO();
        $this->tagDAO = new TagDAO();
        $this->imageStorageDAO = new ImageStorageDAO();
    }

    public function getProfilePayload($studentID) {

        $profile = $this->studentProfileDAO->getStudentProfile($studentID); // Get base profile information for a specific student

        if (!$profile) { // Handle missing data
            return null;
        }

        $selectedTagIDs = array_map("intval", $this->tagDAO->getStudentTagIDs($studentID)); // Fetch tag IDs currently associated with the student, turns it into integer so that it is cleaner and safer

        return [
            "profile" => $profile, // Main student info
            "allTags" => $this->tagDAO->getAllTags(), // A list of every possible tag (for dropdown or checkbox selection)
            "selectedTagIDs" => $selectedTagIDs // Specific tags that belongs to the student
        ];
    }

    // Unified Facade method: Orchestrates the entire update lifecycle
    public function updateProfile($studentID, $input, $avatarFile) {
        $studentID = trim((string) $studentID);
        $profile = $this->studentProfileDAO->getStudentProfile($studentID);

        if (!$profile) {
            return $this->failure("Student profile was not found.");
        }

        $contactNumber = trim((string) ($input["contactNumber"] ?? ""));
        $personalBio = trim((string) ($input["personalBio"] ?? ""));
        $linkedInURL = trim((string) ($input["linkedInURL"] ?? ""));
        $githubURL = trim((string) ($input["githubURL"] ?? ""));
        $portfolioURL = trim((string) ($input["portfolioURL"] ?? ""));
        $tagIDs = $this->normaliseTagIDs($input["interestTags"] ?? []);

        // Constraint C1: Personal Bio limitation
        if (strlen($personalBio) > self::MAX_BIO_LENGTH) {
            return $this->failure("Validation Error - Personal Bio cannot exceed 500 characters.");
        }

        // FR 2.2: Tag Array limitation
        if (count($tagIDs) > self::MAX_TAGS) {
            return $this->failure("You can select a minimum of 1 and a maximum of 5 research interests . Please remove one to add another.");
        }

        if (empty($tagIDs)) {
            return $this->failure("Please select at least one research interest.");
        }

        if (!$this->tagDAO->tagIDsExist($tagIDs)) {
            return $this->failure("Save failed. Please ensure all selected research interests are valid.");
        }

        if (!$this->isValidOptionalURL($linkedInURL) ||
            !$this->isValidOptionalURL($githubURL) ||
            !$this->isValidOptionalURL($portfolioURL)) {
            return $this->failure("Save failed. Please ensure all links begin with a valid URL.");
        }

        $avatarPath = null;
        $oldAvatarPath = $profile["profilePhotoPath"] ?? "";

        // Defense in Depth: Fail-fast file size check before DAO delegation
        if ($this->hasUploadedAvatar($avatarFile)) {
            if ((int) $avatarFile["size"] > self::MAX_AVATAR_SIZE) {
                return $this->failure("Upload failed. Please ensure your profile picture is in JPG or PNG format and does not exceed 2.0MB.");
            }

            $avatarResult = $this->imageStorageDAO->storeProfilePhoto($avatarFile,$studentID);

            if (!$avatarResult["success"] || $avatarResult["path"] === null) {
                return $this->failure("Upload failed. Please ensure your profile picture is in JPG or PNG format and does not exceed 2.0MB.");
            }

            $avatarPath = $avatarResult["path"];
        }

        $updated = $this->studentProfileDAO->updateStudentProfile(
            $studentID,
            [
                "contactNumber" => $contactNumber,
                "personalBio" => $personalBio,
                "linkedInURL" => $linkedInURL,
                "githubURL" => $githubURL,
                "portfolioURL" => $portfolioURL
            ],
            $tagIDs,
            $avatarPath
        );

        if (!$updated) {
            if ($avatarPath !== null) {
                $this->imageStorageDAO->deleteStoredImage($avatarPath);
            }
            return $this->failure("Save failed. Please ensure all links begin with a valid URL.");
        }

        if ($avatarPath !== null) {
            $this->imageStorageDAO->deleteStoredImage($oldAvatarPath);
        }
        return $this->success("Your profile has been successfully updated.");
    }

    private function normaliseTagIDs($tagIDs) {

        if (!is_array($tagIDs)) {

            return [];
        }

        $normalised = [];

        foreach ($tagIDs as $tagID) {

            $tagID = (int) $tagID;

            if ($tagID > 0 && !in_array($tagID, $normalised, true)) {

                $normalised[] = $tagID;
            }
        }

        return $normalised;
    }

    // Constraint C2: Regex evaluation for external links
    private function isValidOptionalURL($url) {

        return $url === "" || preg_match(self::VALID_URL_PATTERN, $url) === 1;
    }

    private function hasUploadedAvatar($avatarFile) {

        return is_array($avatarFile) &&
            isset($avatarFile["error"]) &&
            (int) $avatarFile["error"] !== UPLOAD_ERR_NO_FILE;
    }

    private function success($message) {

        return ["success" => true, "message" => $message];
    }

    private function failure($message) {

        return ["success" => false, "message" => $message];
    }
}

?>
