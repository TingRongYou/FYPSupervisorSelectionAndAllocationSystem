<?php

class ImageStorageDAO {

    private const MAX_IMAGE_SIZE = 2097152;

    private const ALLOWED_MIME_TYPES = [
        "image/jpeg",
        "image/png"
    ];

    public function storeProfilePhoto($uploadedFile, $userID) {

        if (!isset($uploadedFile["error"])) {

            return [
                "success" => false,
                "message" => "No profile photo was received"
            ];
        }

        if ((int) $uploadedFile["error"] === UPLOAD_ERR_NO_FILE) {

            return [
                "success" => true,
                "path" => null
            ];
        }

        if ((int) $uploadedFile["error"] !== UPLOAD_ERR_OK) {

            return [
                "success" => false,
                "message" => $this->uploadErrorMessage((int) $uploadedFile["error"])
            ];
        }

        if ((int) $uploadedFile["size"] > self::MAX_IMAGE_SIZE) {

            return [
                "success" => false,
                "message" => "Invalid Image Format - The uploaded file is not a supported image format. Please upload a JPG or PNG file under 2MB."
            ];
        }

        if (
            !isset($uploadedFile["tmp_name"]) ||
            !is_uploaded_file($uploadedFile["tmp_name"])
        ) {

            return [
                "success" => false,
                "message" => "Invalid Image Format - The uploaded file is not a supported image format. Please upload a JPG or PNG file under 2MB."
            ];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($uploadedFile["tmp_name"]);

        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {

            return [
                "success" => false,
                "message" => "Invalid Image Format - The uploaded file is not a supported image format. Please upload a JPG or PNG file under 2MB."
            ];
        }

        $extension = $mimeType === "image/png" ? "png" : "jpg";
        $safeUserID = preg_replace("/[^A-Za-z0-9_-]/", "", $userID);
        $fileName = $safeUserID . "_" . date("YmdHis") . "." . $extension;
        $storageDirectory = realpath(__DIR__ . "/../../../storage");

        if ($storageDirectory === false) {

            $storageDirectory = __DIR__ . "/../../../storage";
        }

        $photoDirectory = $storageDirectory . DIRECTORY_SEPARATOR . "profile_photos";

        if (!is_dir($photoDirectory)) {

            if (!mkdir($photoDirectory, 0755, true)) {

                return [
                    "success" => false,
                    "message" => "Unable to create profile photo storage directory"
                ];
            }
        }

        if (!is_writable($photoDirectory)) {

            return [
                "success" => false,
                "message" => "Profile photo storage directory is not writable"
            ];
        }

        $destination = $photoDirectory . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file($uploadedFile["tmp_name"], $destination)) {

            return [
                "success" => false,
                "message" => "Unable to store uploaded profile photo"
            ];
        }

        return [
            "success" => true,
            "path" => "../storage/profile_photos/" . $fileName
        ];
    }

    public function deleteStoredImage($imagePath) {

        if (!$this->isManagedProfilePhotoPath($imagePath)) {

            return;
        }

        $absolutePath =
            realpath(__DIR__ . "/../../../" . substr($imagePath, 3));

        if ($absolutePath !== false && is_file($absolutePath)) {

            unlink($absolutePath);
        }
    }

    private function isManagedProfilePhotoPath($imagePath) {

        return preg_match(
            "/^\.\.\/storage\/profile_photos\/[A-Za-z0-9_-]+\.(jpg|png)$/i",
            (string) $imagePath
        ) === 1;
    }

    private function uploadErrorMessage($uploadErrorCode) {

        if (
            $uploadErrorCode === UPLOAD_ERR_INI_SIZE ||
            $uploadErrorCode === UPLOAD_ERR_FORM_SIZE
        ) {

            return "Invalid Image Format - The uploaded file is not a supported image format. Please upload a JPG or PNG file under 2MB.";
        }

        if ($uploadErrorCode === UPLOAD_ERR_PARTIAL) {

            return "Profile photo upload was incomplete. Please try again";
        }

        return "Profile photo upload failed";
    }
}

?>


