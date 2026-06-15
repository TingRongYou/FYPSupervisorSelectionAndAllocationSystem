<?php

/*
|--------------------------------------------------------------------------
| Video Storage DAO
|--------------------------------------------------------------------------
| Handles introductory video upload validation, storage,
| and deletion. Only MP4 and WebM files under 50MB are accepted.
*/

class VideoStorageDAO {

    /*
    |--------------------------------------------------------------------------
    | Upload Constraints
    |--------------------------------------------------------------------------
    */
    private const MAX_VIDEO_SIZE = 52428800; // 50MB in bytes

    private const WEB_STORAGE_ROOT = "../../storage";

    private const ALLOWED_MIME_TYPES = [
        "video/mp4",
        "video/webm"
    ];

    /*
    |--------------------------------------------------------------------------
    | Store Introductory Video
    |--------------------------------------------------------------------------
    | Validates and stores uploaded supervisor intro videos.
    */
    public function storeIntroVideo($uploadedFile, $supervisorID) {

        /*
        |--------------------------------------------------------------------------
        | Upload Presence Validation
        |--------------------------------------------------------------------------
        */
        if (
            !isset($uploadedFile["error"])
        ) {

            return [
                "success" => false,
                "message" => "No video file was received"
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
                "message" =>
                    $this->uploadErrorMessage(
                        (int) $uploadedFile["error"]
                    )
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | File Size Validation
        |--------------------------------------------------------------------------
        */
        if ((int) $uploadedFile["size"] > self::MAX_VIDEO_SIZE) {

            return [
                "success" => false,
                "message" => "Invalid Format/Size - The uploaded file is not supported or exceeds the 50MB limit. Please provide a valid MP4 file or URL."
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
                "message" => "Invalid Format/Size - The uploaded file is not supported or exceeds the 50MB limit. Please provide a valid MP4 file or URL."
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | MIME Type Validation
        |--------------------------------------------------------------------------
        | Ensures only MP4 and WebM video files are accepted.
        */
        $finfo =
            new finfo(FILEINFO_MIME_TYPE);

        $mimeType =
            $finfo->file(
                $uploadedFile["tmp_name"]
            );

        if (
            !in_array(
                $mimeType,
                self::ALLOWED_MIME_TYPES,
                true
            )
        ) {

            return [
                "success" => false,
                "message" =>
                    "Invalid Format/Size - The uploaded file is not supported or exceeds the 50MB limit. Please provide a valid MP4 file or URL."
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | File Name Generation
        |--------------------------------------------------------------------------
        | Generates a safe and unique video file name.
        */
        $extension =
            $mimeType === "video/webm"
                ? "webm"
                : "mp4";

        $safeSupervisorID =
            preg_replace(
                "/[^A-Za-z0-9_-]/",
                "",
                $supervisorID
            );

        $fileName =
            $safeSupervisorID .
            "_" .
            date("YmdHis") .
            "." .
            $extension;

        /*
        |--------------------------------------------------------------------------
        | Storage Directory Resolution
        |--------------------------------------------------------------------------
        */
        $storageDirectory =
            realpath(
                __DIR__ . "/../../../storage"
            );

        if ($storageDirectory === false) {

            $storageDirectory =
                __DIR__ . "/../../../storage";
        }

        $videoDirectory =
            $storageDirectory .
            DIRECTORY_SEPARATOR .
            "intro_videos";

        /*
        |--------------------------------------------------------------------------
        | Create Intro Video Directory
        |--------------------------------------------------------------------------
        */
        if (!is_dir($videoDirectory)) {

            if (
                !mkdir(
                    $videoDirectory,
                    0755,
                    true
                )
            ) {

                return [
                    "success" => false,
                    "message" =>
                        "Unable to create video storage directory"
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Directory Write Permission Validation
        |--------------------------------------------------------------------------
        */
        if (!is_writable($videoDirectory)) {

            return [
                "success" => false,
                "message" =>
                    "Video storage directory is not writable"
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Destination Path Generation
        |--------------------------------------------------------------------------
        */
        $destination =
            $videoDirectory .
            DIRECTORY_SEPARATOR .
            $fileName;

        /*
        |--------------------------------------------------------------------------
        | Move Uploaded Video
        |--------------------------------------------------------------------------
        */
        if (
            !move_uploaded_file(
                $uploadedFile["tmp_name"],
                $destination
            )
        ) {

            return [
                "success" => false,
                "message" =>
                    "Unable to store uploaded video"
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Successful Upload Response
        |--------------------------------------------------------------------------
        */
        return [
            "success" => true,
            "path" => self::WEB_STORAGE_ROOT . "/intro_videos/" . $fileName
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Stored Video
    |--------------------------------------------------------------------------
    | Deletes managed introductory videos safely.
    */
    public function deleteStoredVideo($videoPath) {

        /*
        |--------------------------------------------------------------------------
        | Managed Path Validation
        |--------------------------------------------------------------------------
        */
        if (
            !$this->isManagedVideoPath(
                $videoPath
            )
        ) {

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Resolve Absolute File Path
        |--------------------------------------------------------------------------
        */
        $absolutePath =
            realpath($this->managedVideoAbsolutePath($videoPath));

        /*
        |--------------------------------------------------------------------------
        | Delete Existing File
        |--------------------------------------------------------------------------
        */
        if (
            $absolutePath !== false &&
            is_file($absolutePath)
        ) {

            unlink($absolutePath);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Managed Video Path Validation
    |--------------------------------------------------------------------------
    | Ensures only application-managed videos can be deleted.
    */
    private function isManagedVideoPath($videoPath) {
        return preg_match(
            "/^(?:\.\.\/)*(storage\/intro_videos\/[A-Za-z0-9_-]+\.(mp4|webm))$/i",
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

            return "Invalid Format/Size - The uploaded file is not supported or exceeds the 50MB limit. Please provide a valid MP4 file or URL.";
        }

        /*
        |--------------------------------------------------------------------------
        | No File Selected Error
        |--------------------------------------------------------------------------
        */
        if (
            $uploadErrorCode === UPLOAD_ERR_NO_FILE
        ) {

            return
                "Invalid Format/Size - The uploaded file is not supported or exceeds the 50MB limit. Please provide a valid MP4 file or URL.";
        }

        /*
        |--------------------------------------------------------------------------
        | Partial Upload Error
        |--------------------------------------------------------------------------
        */
        if (
            $uploadErrorCode === UPLOAD_ERR_PARTIAL
        ) {

            return
                "Video upload was incomplete. Please try again";
        }

        /*
        |--------------------------------------------------------------------------
        | Default Upload Error
        |--------------------------------------------------------------------------
        */
        return "Video upload failed";
    }
}

?>
