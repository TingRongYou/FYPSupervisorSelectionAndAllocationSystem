<?php

class VideoStorageDAO {

    private const MAX_VIDEO_SIZE = 52428800;

    private const WEB_STORAGE_ROOT = "/FYPSupervisorSelectionAndAllocationSystem/storage";

    private const ALLOWED_MIME_TYPES = [
        "video/mp4",
        "video/webm"
    ];

    public function storeIntroVideo($uploadedFile, $supervisorID) {

        if (
            !isset($uploadedFile["error"])
        ) {

            return [
                "success" => false,
                "message" => "No video file was received"
            ];
        }

        if ((int) $uploadedFile["error"] !== UPLOAD_ERR_OK) {

            return [
                "success" => false,
                "message" => $this->uploadErrorMessage((int) $uploadedFile["error"])
            ];
        }

        if ((int) $uploadedFile["size"] > self::MAX_VIDEO_SIZE) {

            return [
                "success" => false,
                "message" => "Video file cannot exceed 50MB"
            ];
        }

        if (
            !isset($uploadedFile["tmp_name"]) ||
            !is_uploaded_file($uploadedFile["tmp_name"])
        ) {

            return [
                "success" => false,
                "message" => "Invalid video upload source"
            ];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($uploadedFile["tmp_name"]);

        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {

            return [
                "success" => false,
                "message" => "Only MP4 or WebM video files are allowed"
            ];
        }

        $extension = $mimeType === "video/webm" ? "webm" : "mp4";
        $safeSupervisorID = preg_replace("/[^A-Za-z0-9_-]/", "", $supervisorID);
        $fileName = $safeSupervisorID . "_" . date("YmdHis") . "." . $extension;
        $storageDirectory = realpath(__DIR__ . "/../../../storage");

        if ($storageDirectory === false) {

            $storageDirectory = __DIR__ . "/../../../storage";
        }

        $videoDirectory = $storageDirectory . DIRECTORY_SEPARATOR . "intro_videos";

        if (!is_dir($videoDirectory)) {

            if (!mkdir($videoDirectory, 0755, true)) {

                return [
                    "success" => false,
                    "message" => "Unable to create video storage directory"
                ];
            }
        }

        if (!is_writable($videoDirectory)) {

            return [
                "success" => false,
                "message" => "Video storage directory is not writable"
            ];
        }

        $destination = $videoDirectory . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file($uploadedFile["tmp_name"], $destination)) {

            return [
                "success" => false,
                "message" => "Unable to store uploaded video"
            ];
        }

        return [
            "success" => true,
            "path" => self::WEB_STORAGE_ROOT . "/intro_videos/" . $fileName
        ];
    }

    public function deleteStoredVideo($videoPath) {

        if (!$this->isManagedVideoPath($videoPath)) {

            return;
        }

        $absolutePath =
            realpath($this->managedVideoAbsolutePath($videoPath));

        if ($absolutePath !== false && is_file($absolutePath)) {

            unlink($absolutePath);
        }
    }

    private function isManagedVideoPath($videoPath) {

        return preg_match(
            "/^(?:\.\.\/storage|\/FYPSupervisorSelectionAndAllocationSystem\/storage)\/intro_videos\/[A-Za-z0-9_-]+\.(mp4|webm)$/i",
            (string) $videoPath
        ) === 1;
    }

    private function managedVideoAbsolutePath($videoPath) {

        $relativePath =
            str_replace(
                [
                    "../storage/",
                    self::WEB_STORAGE_ROOT . "/"
                ],
                "",
                (string) $videoPath
            );

        return __DIR__ . "/../../../storage/" . $relativePath;
    }

    private function uploadErrorMessage($uploadErrorCode) {

        if (
            $uploadErrorCode === UPLOAD_ERR_INI_SIZE ||
            $uploadErrorCode === UPLOAD_ERR_FORM_SIZE
        ) {

            return "Video file cannot exceed 50MB";
        }

        if ($uploadErrorCode === UPLOAD_ERR_NO_FILE) {

            return "Please select an MP4 or WebM video file";
        }

        if ($uploadErrorCode === UPLOAD_ERR_PARTIAL) {

            return "Video upload was incomplete. Please try again";
        }

        return "Video upload failed";
    }
}

?>


