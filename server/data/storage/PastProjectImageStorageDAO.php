<?php

class PastProjectImageStorageDAO {

    private const IMAGE_DIRECTORY_NAME = "past_project_images";

    private const MAX_IMAGE_SIZE = 2097152;

    private const ALLOWED_MIME_TYPES = [
        "image/jpeg",
        "image/png"
    ];

    private const INVALID_IMAGE_MESSAGE =
        "Invalid Image Format - The cover image must be JPG or PNG and cannot exceed 2.0 MB.";

    public function storeProjectImage($uploadedFile, $supervisorID) {

        if (
            !is_array($uploadedFile) ||
            !isset($uploadedFile["error"]) ||
            (int) $uploadedFile["error"] === UPLOAD_ERR_NO_FILE
        ) {

            return [
                "success" => true,
                "path" => null
            ];
        }

        if ((int) $uploadedFile["error"] !== UPLOAD_ERR_OK) {

            return $this->failure(self::INVALID_IMAGE_MESSAGE);
        }

        if ((int) $uploadedFile["size"] > self::MAX_IMAGE_SIZE) {

            return $this->failure(self::INVALID_IMAGE_MESSAGE);
        }

        if (
            !isset($uploadedFile["tmp_name"]) ||
            !is_uploaded_file($uploadedFile["tmp_name"])
        ) {

            return $this->failure(self::INVALID_IMAGE_MESSAGE);
        }

        $finfo =
            new finfo(FILEINFO_MIME_TYPE);

        $mimeType =
            $finfo->file($uploadedFile["tmp_name"]);

        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {

            return $this->failure(self::INVALID_IMAGE_MESSAGE);
        }

        $imageDirectory =
            $this->imageDirectory();

        if (!is_dir($imageDirectory) && !mkdir($imageDirectory, 0755, true)) {

            return $this->failure("Unable to create past project image storage directory.");
        }

        if (!is_writable($imageDirectory)) {

            return $this->failure("Past project image storage directory is not writable.");
        }

        $extension =
            $mimeType === "image/png" ? "png" : "jpg";

        $safeSupervisorID =
            preg_replace("/[^A-Za-z0-9_-]/", "", $supervisorID);

        $fileName =
            $safeSupervisorID . "_" . date("YmdHis") . "_" . bin2hex(random_bytes(4)) . "." . $extension;

        $destination =
            $imageDirectory . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file($uploadedFile["tmp_name"], $destination)) {

            return $this->failure("Unable to store past project cover image.");
        }

        return [
            "success" => true,
            "path" => "../storage/" . self::IMAGE_DIRECTORY_NAME . "/" . $fileName
        ];
    }

    public function deleteStoredImage($path) {

        $path =
            trim((string) $path);

        if ($path === "") {

            return;
        }

        $fileName =
            basename($path);

        $imageDirectory =
            realpath($this->imageDirectory());

        if ($imageDirectory === false) {

            return;
        }

        $fullPath =
            $imageDirectory . DIRECTORY_SEPARATOR . $fileName;

        $resolvedPath =
            realpath($fullPath);

        if (
            $resolvedPath !== false &&
            strpos($resolvedPath, $imageDirectory) === 0 &&
            is_file($resolvedPath)
        ) {

            unlink($resolvedPath);
        }
    }

    private function failure($message) {

        return [
            "success" => false,
            "message" => $message,
            "path" => null
        ];
    }

    private function imageDirectory() {

        $storageDirectory =
            realpath(__DIR__ . "/../../../storage");

        if ($storageDirectory === false) {

            $storageDirectory =
                __DIR__ . "/../../../storage";
        }

        return $storageDirectory . DIRECTORY_SEPARATOR . self::IMAGE_DIRECTORY_NAME;
    }
}

?>
