<?php

class ProposalStorageDAO {

    private const MAX_PROPOSAL_SIZE = 5242880;

    private const WEB_STORAGE_ROOT = "/FYPSupervisorSelectionAndAllocationSystem/storage";

    public function storeProposalPDF($uploadedFile, $studentID) {

        if (!isset($uploadedFile["error"]) || (int) $uploadedFile["error"] === UPLOAD_ERR_NO_FILE) {

            return [
                "success" => false,
                "message" => "Please upload a proposal document in PDF format."
            ];
        }

        if ((int) $uploadedFile["error"] !== UPLOAD_ERR_OK) {

            return [
                "success" => false,
                "message" => "Proposal upload failed. Please try again."
            ];
        }

        if ((int) $uploadedFile["size"] > self::MAX_PROPOSAL_SIZE) {

            return [
                "success" => false,
                "message" => "Proposal file cannot exceed 5MB."
            ];
        }

        if (!isset($uploadedFile["tmp_name"]) || !is_uploaded_file($uploadedFile["tmp_name"])) {

            return [
                "success" => false,
                "message" => "Invalid proposal upload source."
            ];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($uploadedFile["tmp_name"]);

        if ($mimeType !== "application/pdf") {

            return [
                "success" => false,
                "message" => "Please upload a proposal document in PDF format."
            ];
        }

        $storageDirectory = realpath(__DIR__ . "/../../../storage");

        if ($storageDirectory === false) {

            $storageDirectory = __DIR__ . "/../../../storage";
        }

        $proposalDirectory = $storageDirectory . DIRECTORY_SEPARATOR . "proposals";

        if (!is_dir($proposalDirectory) && !mkdir($proposalDirectory, 0755, true)) {

            return [
                "success" => false,
                "message" => "Unable to create proposal storage directory."
            ];
        }

        if (!is_writable($proposalDirectory)) {

            return [
                "success" => false,
                "message" => "Proposal storage directory is not writable."
            ];
        }

        $safeStudentID = preg_replace("/[^A-Za-z0-9_-]/", "", $studentID);
        $fileName = $safeStudentID . "_" . date("YmdHis") . ".pdf";
        $destination = $proposalDirectory . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file($uploadedFile["tmp_name"], $destination)) {

            return [
                "success" => false,
                "message" => "Unable to store proposal document."
            ];
        }

        return [
            "success" => true,
            "path" => self::WEB_STORAGE_ROOT . "/proposals/" . $fileName
        ];
    }
}

?>


