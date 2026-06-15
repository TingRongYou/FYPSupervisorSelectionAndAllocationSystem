<?php
/*
|--------------------------------------------------------------------------
| Image Storage DAO
|--------------------------------------------------------------------------
| Handles profile photo upload validation, storage, and deletion.
| Only JPG and PNG images under 2MB are accepted.
*/

class ImageStorageDAO {

    /*
    |--------------------------------------------------------------------------
    | Upload Constraints
    |--------------------------------------------------------------------------
    */

    private const MAX_IMAGE_SIZE = 2097152; // 2MB in bytes

    private const WEB_STORAGE_ROOT = "../../storage";

    private const ALLOWED_MIME_TYPES = [
        "image/jpeg",
        "image/png"
    ];

    /*
    |--------------------------------------------------------------------------
    | Store Profile Photo
    |--------------------------------------------------------------------------
    | Validates and stores uploaded profile photos securely.
    */

    public function storeProfilePhoto($uploadedFile, $userID) {

        /*
        |--------------------------------------------------------------------------
        | Upload Error Validation
        |--------------------------------------------------------------------------
        */

        if (!isset($uploadedFile["error"])) {

            return [
                "success" => false,
                "message" => "No profile photo was received"
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | No File Uploaded
        |--------------------------------------------------------------------------
        */

        if ((int) $uploadedFile["error"] === UPLOAD_ERR_NO_FILE) {

            return [
                "success" => true,
                "path" => null
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | PHP Upload Error Handling
        |--------------------------------------------------------------------------
        */

        if ((int) $uploadedFile["error"] !== UPLOAD_ERR_OK) {

            return [
                "success" => false,
                "message" => $this->uploadErrorMessage((int) $uploadedFile["error"])
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | File Size Validation
        |--------------------------------------------------------------------------
        */

        if ((int) $uploadedFile["size"] > self::MAX_IMAGE_SIZE) {

            return [
                "success" => false,
                "message" => "Invalid Image Format - The uploaded file is not supported image format. Please upload a JPG or PNG file under 2MB."
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Temporary File Validation
        |--------------------------------------------------------------------------
        */

        if (
            !isset($uploadedFile["tmp_name"]) ||
            !is_uploaded_file($uploadedFile["tmp_name"])
        ) {

            return [
                "success" => false,
                "message" => "Invalid Image Format - The uploaded file is not supported image format. Please upload a JPG or PNG file under 2MB."
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | MIME Type Validation
        |--------------------------------------------------------------------------
        | Ensures only JPG and PNG files are accepted.
        */

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($uploadedFile["tmp_name"]);

        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {

            return [
                "success" => false,
                "message" => "Invalid Image Format - The uploaded file is not supported image format. Please upload a JPG or PNG file under 2MB."
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | File Name Generation
        |--------------------------------------------------------------------------
        | Generates a safe and unique file name.
        */

        $extension = $mimeType === "image/png" ? "png" : "jpg";
        $safeUserID = preg_replace("/[^A-Za-z0-9_-]/", "", $userID);
        $fileName = $safeUserID . "_" . date("YmdHis") . "." . $extension;

        /*
        |--------------------------------------------------------------------------
        | Storage Directory Resolution
        |--------------------------------------------------------------------------
        */

        $storageDirectory = realpath(__DIR__ . "/../../../storage");

        if ($storageDirectory === false) {

            $storageDirectory = __DIR__ . "/../../../storage";
        }

        $photoDirectory = $storageDirectory . DIRECTORY_SEPARATOR . "profile_photos";

        /*
        |--------------------------------------------------------------------------
        | Create Profile Photo Directory
        |--------------------------------------------------------------------------
        */

        if (!is_dir($photoDirectory)) {

            if (!mkdir($photoDirectory, 0755, true)) {

                return [
                    "success" => false,
                    "message" => "Unable to create profile photo storage directory"
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Directory Write Permission Validation
        |--------------------------------------------------------------------------
        */

        if (!is_writable($photoDirectory)) {

            return [
                "success" => false,
                "message" => "Profile photo storage directory is not writable"
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Destination Path Generation
        |--------------------------------------------------------------------------
        */

        $destination = $photoDirectory . DIRECTORY_SEPARATOR . $fileName;

        /*
        |--------------------------------------------------------------------------
        | Move Uploaded File
        |--------------------------------------------------------------------------
        */

        if (!move_uploaded_file($uploadedFile["tmp_name"], $destination)) {

            return [
                "success" => false,
                "message" => "Unable to store uploaded profile photo"
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Successful Upload Response
        |--------------------------------------------------------------------------
        */

        return [
            "success" => true,
            "path" => self::WEB_STORAGE_ROOT . "/profile_photos/" . $fileName
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Stored Image
    |--------------------------------------------------------------------------
    | Deletes managed profile photos safely.
    */

    public function deleteStoredImage($imagePath) {

        /*
        |--------------------------------------------------------------------------
        | Managed Path Validation
        |--------------------------------------------------------------------------
        */

        if (!$this->isManagedProfilePhotoPath($imagePath)) {

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Resolve Absolute File Path
        |--------------------------------------------------------------------------
        */

        $relativeImagePath =
            $this->normaliseManagedProfilePhotoPath($imagePath);

        if ($relativeImagePath === null) {

            return;
        }

        $absolutePath =
            realpath(__DIR__ . "/../../../" . $relativeImagePath);

        /*
        |-------------------------------------------------------------------------- 
        | Delete Existing File
        |--------------------------------------------------------------------------
        */

        if ($absolutePath !== false && is_file($absolutePath)) {

            unlink($absolutePath);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Managed Profile Photo Path Validation
    |--------------------------------------------------------------------------
    | Ensures only application-managed files can be deleted.
    */

    private function isManagedProfilePhotoPath($imagePath) {

        return $this->normaliseManagedProfilePhotoPath($imagePath) !== null;
    }

    private function normaliseManagedProfilePhotoPath($imagePath) {

        $imagePath = (string) $imagePath;

        $patterns = [
            "/^(?:\.\.\/)*(storage\/profile_photos\/[A-Za-z0-9_-]+\.(jpg|png))$/i"
        ];

        foreach ($patterns as $pattern) {

            if (preg_match($pattern, $imagePath, $matches) === 1) {

                return $matches[1];
            }
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Upload Error Message Resolver
    |--------------------------------------------------------------------------
    | Converts PHP upload error codes into readable messages.
    */

    private function uploadErrorMessage($uploadErrorCode) {

    /*
        |--------------------------------------------------------------------------
        | File Size Related Upload Errors
        |--------------------------------------------------------------------------
        */

        if (
            $uploadErrorCode === UPLOAD_ERR_INI_SIZE ||
            $uploadErrorCode === UPLOAD_ERR_FORM_SIZE
        ) {

            return "Invalid Image Format - The uploaded file is not supported image format. Please upload a JPG or PNG file under 2MB.";
        }

        /*
        |--------------------------------------------------------------------------
        | Partial Upload Error
        |--------------------------------------------------------------------------
        */

        if ($uploadErrorCode === UPLOAD_ERR_PARTIAL) {

            return "Profile photo upload was incomplete. Please try again";
        }

        /*
        |--------------------------------------------------------------------------
        | Default Upload Error
        |--------------------------------------------------------------------------
        */

        return "Profile photo upload failed";
    }
}

?>
