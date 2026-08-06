<?php

/*
|--------------------------------------------------------------------------
| Proposal Storage DAO
|--------------------------------------------------------------------------
| Handles proposal PDF upload validation and storage.
| Only PDF files under 5MB are accepted.
*/

class ProposalStorageDAO {

    /*
    |--------------------------------------------------------------------------
    | Upload Constraints
    |--------------------------------------------------------------------------
    */
    private const MAX_PROPOSAL_SIZE = 5242880; // 5MB in bytes

    private const WEB_STORAGE_ROOT = "storage";

    private const INVALID_FILE_MESSAGE = "Upload failed. The document must be in PDF format and cannot exceed 5.0 MB in size.";

    /*
    |--------------------------------------------------------------------------
    | Store Proposal PDF
    |--------------------------------------------------------------------------
    | Validates and stores uploaded proposal documents securely.
    */
    public function storeProposalPDF($uploadedFile, $studentID) {

        /*
        |--------------------------------------------------------------------------
        | Upload Presence Validation
        |--------------------------------------------------------------------------
        */
        if (
            !isset($uploadedFile["error"]) ||
            (int) $uploadedFile["error"] === UPLOAD_ERR_NO_FILE
        ) {

            return [
                "success" => false,
                "message" => self::INVALID_FILE_MESSAGE
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
                "message" => self::INVALID_FILE_MESSAGE
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | File Size Validation
        |--------------------------------------------------------------------------
        */
        if ((int) $uploadedFile["size"] > self::MAX_PROPOSAL_SIZE) {

            return ["success" => false, "message" => self::INVALID_FILE_MESSAGE];
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
                "message" => self::INVALID_FILE_MESSAGE
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | MIME Type Validation
        |--------------------------------------------------------------------------
        | Ensures only PDF proposal documents are accepted.
        */
        // Gate 2: True MIME-type inspection via server-side 'finfo' (FR 1.3)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($uploadedFile["tmp_name"]);

        // Gate 3: Extension sanitization
        $extension = strtolower(pathinfo($uploadedFile["name"] ?? "", PATHINFO_EXTENSION));

        if ($mimeType !== "application/pdf" || $extension !== "pdf") {
            return ["success" => false, "message" => self::INVALID_FILE_MESSAGE];
        }

        /*
        |--------------------------------------------------------------------------
        | Storage Directory Resolution
        |--------------------------------------------------------------------------
        */
        $storageDirectory = realpath(__DIR__ . "/../../../storage");

        if ($storageDirectory === false) {

            $storageDirectory = __DIR__ . "/../../../storage";
        }

        $proposalDirectory = $storageDirectory . DIRECTORY_SEPARATOR . "proposals";

        /*
        |--------------------------------------------------------------------------
        | Create Proposal Directory
        |--------------------------------------------------------------------------
        */
        if (
            !is_dir($proposalDirectory) &&
            !mkdir($proposalDirectory, 0755, true)
        ) {

            return [
                "success" => false,
                "message" => "Unable to create proposal storage directory."
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Directory Write Permission Validation
        |--------------------------------------------------------------------------
        */
        if (!is_writable($proposalDirectory)) {

            return [
                "success" => false,
                "message" => "Proposal storage directory is not writable."
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | File Name Generation
        |--------------------------------------------------------------------------
        | Generates a safe and unique proposal file name.
        */
        $safeStudentID =
            preg_replace(
                "/[^A-Za-z0-9_-]/",
                "",
                $studentID
            );

        $fileName =
            $safeStudentID .
            "_" .
            date("YmdHis") .
            ".pdf";

        /*
        |--------------------------------------------------------------------------
        | Destination Path Generation
        |--------------------------------------------------------------------------
        */
        $destination =
            $proposalDirectory .
            DIRECTORY_SEPARATOR .
            $fileName;

        /*
        |--------------------------------------------------------------------------
        | Move Uploaded Proposal
        |--------------------------------------------------------------------------
        */
        // Physical file routing to secure server directory
        if (!move_uploaded_file($uploadedFile["tmp_name"], $destination)) {
            return ["success" => false, "message" => "Unable to store proposal document."];
        }

        /*
        |--------------------------------------------------------------------------
        | Successful Upload Response
        |--------------------------------------------------------------------------
        */
        return [
            "success" => true,
            "path" => self::WEB_STORAGE_ROOT . "/proposals/" . $fileName
        ];
    }
}

?>
