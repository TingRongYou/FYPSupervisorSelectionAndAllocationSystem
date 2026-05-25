<?php

require_once __DIR__ . "/../data/UserDAO.php";
require_once __DIR__ . "/../data/ImageStorageDAO.php";

class AccountService {

    private $userDAO;

    private $imageStorageDAO;

    public function __construct() {

        $this->userDAO = new UserDAO();
        $this->imageStorageDAO = new ImageStorageDAO();
    }

    public function getAccountProfile($userID) {

        $user =
            $this->userDAO
            ->getUserByID($userID);

        if (!$user) {

            return null;
        }

        unset($user["password"]);

        return $user;
    }

    public function changePassword(
        $userID,
        $currentPassword,
        $newPassword,
        $confirmPassword
    ) {

        $currentPassword = trim($currentPassword);
        $newPassword = trim($newPassword);
        $confirmPassword = trim($confirmPassword);

        if ($currentPassword === "" || $newPassword === "" || $confirmPassword === "") {

            return $this->failure("All password fields are required");
        }

        if ($newPassword !== $confirmPassword) {

            return $this->failure("New password and confirm password do not match");
        }

        if (!$this->isStrongPassword($newPassword)) {

            return $this->failure("Password must contain letters, numbers, special characters and at least 8 characters");
        }

        $user =
            $this->userDAO
            ->getUserByID($userID);

        if (!$user) {

            return $this->failure("Account was not found");
        }

        $storedPassword = $user["password"];

        $validCurrentPassword =
            password_verify($currentPassword, $storedPassword)
            ||
            hash_equals($storedPassword, $currentPassword);

        if (!$validCurrentPassword) {

            return $this->failure("Current password is incorrect");
        }

        $hashedPassword =
            password_hash(
                $newPassword,
                PASSWORD_DEFAULT
            );

        $updated =
            $this->userDAO
            ->updatePassword(
                $userID,
                $hashedPassword
            );

        if (!$updated) {

            return $this->failure("Password could not be updated");
        }

        return $this->success("Password updated successfully");
    }

    public function updateProfilePhoto($userID, $profilePhotoFile) {

        $user =
            $this->userDAO
            ->getUserByID($userID);

        if (!$user) {

            return $this->failure("Account was not found");
        }

        $photoResult =
            $this->imageStorageDAO
            ->storeProfilePhoto(
                $profilePhotoFile,
                $userID
            );

        if (!$photoResult["success"]) {

            return $this->failure($photoResult["message"]);
        }

        if ($photoResult["path"] === null) {

            return $this->failure("Please select a JPG or PNG profile photo");
        }

        $updated =
            $this->userDAO
            ->updateProfilePhoto(
                $userID,
                $photoResult["path"]
            );

        if (!$updated) {

            return $this->failure("Profile photo could not be updated");
        }

        $this->imageStorageDAO
        ->deleteStoredImage(
            $user["profilePhotoPath"] ?? ""
        );

        return $this->success("Profile photo updated successfully");
    }

    private function isStrongPassword($password) {

        return
            strlen($password) >= 8
            && preg_match("/[A-Za-z]/", $password)
            && preg_match("/[0-9]/", $password)
            && preg_match("/[^A-Za-z0-9]/", $password);
    }

    private function success($message) {

        return [
            "success" => true,
            "message" => $message
        ];
    }

    private function failure($message) {

        return [
            "success" => false,
            "message" => $message
        ];
    }
}

?>
