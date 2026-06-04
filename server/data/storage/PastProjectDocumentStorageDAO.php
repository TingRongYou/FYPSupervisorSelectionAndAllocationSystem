<?php

class PastProjectDocumentStorageDAO {

    private const MAX_DOCUMENT_SIZE = 5242880;

    private const INVALID_FILE_MESSAGE =
        "Invalid File - The past project document must be a PDF and cannot exceed 5.0 MB.";

    public function storeProjectPDF($uploadedFile, $supervisorID) {

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

            return $this->failure(self::INVALID_FILE_MESSAGE);
        }

        if ((int) $uploadedFile["size"] > self::MAX_DOCUMENT_SIZE) {

            return $this->failure(self::INVALID_FILE_MESSAGE);
        }

        if (
            !isset($uploadedFile["tmp_name"]) ||
            !is_uploaded_file($uploadedFile["tmp_name"])
        ) {

            return $this->failure(self::INVALID_FILE_MESSAGE);
        }

        $finfo =
            new finfo(FILEINFO_MIME_TYPE);

        $mimeType =
            $finfo->file($uploadedFile["tmp_name"]);

        $extension =
            strtolower(pathinfo($uploadedFile["name"] ?? "", PATHINFO_EXTENSION));

        if ($mimeType !== "application/pdf" || $extension !== "pdf") {

            return $this->failure(self::INVALID_FILE_MESSAGE);
        }

        $storageDirectory =
            realpath(__DIR__ . "/../../../storage");

        if ($storageDirectory === false) {

            $storageDirectory =
                __DIR__ . "/../../../storage";
        }

        $projectDirectory =
            $storageDirectory . DIRECTORY_SEPARATOR . "past_projects";

        if (!is_dir($projectDirectory) && !mkdir($projectDirectory, 0755, true)) {

            return $this->failure("Unable to create past project storage directory.");
        }

        if (!is_writable($projectDirectory)) {

            return $this->failure("Past project storage directory is not writable.");
        }

        $safeSupervisorID =
            preg_replace("/[^A-Za-z0-9_-]/", "", $supervisorID);

        $fileName =
            $safeSupervisorID . "_" . date("YmdHis") . "_" . bin2hex(random_bytes(4)) . ".pdf";

        $destination =
            $projectDirectory . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file($uploadedFile["tmp_name"], $destination)) {

            return $this->failure("Unable to store past project document.");
        }

        return [
            "success" => true,
            "path" => "../storage/past_projects/" . $fileName
        ];
    }

    public function deleteStoredPDF($path) {

        $path =
            trim((string) $path);

        if ($path === "") {

            return;
        }

        $fileName =
            basename($path);

        $projectDirectory =
            realpath(__DIR__ . "/../../../storage/past_projects");

        if ($projectDirectory === false) {

            return;
        }

        $fullPath =
            $projectDirectory . DIRECTORY_SEPARATOR . $fileName;

        $resolvedPath =
            realpath($fullPath);

        if (
            $resolvedPath !== false &&
            strpos($resolvedPath, $projectDirectory) === 0 &&
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
}

?>
