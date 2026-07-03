<?php

/*
|--------------------------------------------------------------------------
| Required Dependencies
|--------------------------------------------------------------------------
| Loads the UserDAO for database operations and ImageStorageDAO for
| profile photo file storage operations.
*/
require_once __DIR__ . "/../../data/dao/UserDAO.php";
require_once __DIR__ . "/../../data/storage/ImageStorageDAO.php";

/*
|--------------------------------------------------------------------------
| Account Service
|--------------------------------------------------------------------------
| Handles account-related business logic such as retrieving profile data,
| changing passwords, and updating profile photos.
*/
class AccountService {

    /*
    |--------------------------------------------------------------------------
    | Service Dependencies
    |--------------------------------------------------------------------------
    */
    private $userDAO;
    private $imageStorageDAO;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    | Initializes DAO and storage classes required by this service.
    */
    public function __construct() {

        $this->userDAO =
            new UserDAO();

        $this->imageStorageDAO =
            new ImageStorageDAO();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Account Profile
    |--------------------------------------------------------------------------
    | Retrieves the user profile by user ID and removes the password hash
    | before returning the data.
    */
    public function getAccountProfile($userID) {

        $user =
            $this->userDAO->getUserByID($userID);

        if (!$user) {

            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Remove Sensitive Password Data
        |--------------------------------------------------------------------------
        */
        unset($user["password"]);

        return $user;
    }

    /*
    |--------------------------------------------------------------------------
    | Change Password
    |--------------------------------------------------------------------------
    | Validates the current password, checks password strength, prevents
    | password reuse, hashes the new password, and updates the database.
    */
    public function changePassword(
        $userID,
        $currentPassword,
        $newPassword,
        $confirmPassword
    ) {

        /*
        |--------------------------------------------------------------------------
        | Trim Password Inputs
        |--------------------------------------------------------------------------
        */
        $currentPassword =
            trim($currentPassword);

        $newPassword =
            trim($newPassword);

        $confirmPassword =
            trim($confirmPassword);

        /*
        |--------------------------------------------------------------------------
        | Empty Field Validation
        |--------------------------------------------------------------------------
        */
        if (
            $currentPassword === "" ||
            $newPassword === "" ||
            $confirmPassword === ""
        ) {

            return $this->failure(
                "All password fields are required."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Confirm Password Validation
        |--------------------------------------------------------------------------
        */
        if ($newPassword !== $confirmPassword) {

            return $this->failure(
                "New password and confirm password do not match."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Password Strength Validation
        |--------------------------------------------------------------------------
        */
        if (!$this->isStrongPassword($newPassword)) {

            return $this->failure(
                "Password must be at least 8 characters and contain " .
                "letters, numbers, and special characters."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Load User Record
        |--------------------------------------------------------------------------
        */
        $user =
            $this->userDAO->getUserByID($userID);

        if (!$user) {

            return $this->failure(
                "Account was not found."
            );
        }

        $storedHash =
            $user["password"];

        /*
        |--------------------------------------------------------------------------
        | Current Password Verification
        |--------------------------------------------------------------------------
        | Supports both hashed passwords and legacy plain-text passwords.
        */
        $validCurrentPassword =
            password_verify($currentPassword, $storedHash) ||
            hash_equals($storedHash, $currentPassword);

        if (!$validCurrentPassword) {

            return $this->failure(
                "Current password is incorrect."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Same Password Validation
        |--------------------------------------------------------------------------
        | Prevents the user from reusing the current password as the new one.
        */
        $sameAsCurrentPassword =
            password_verify($newPassword, $storedHash) ||
            hash_equals($storedHash, $newPassword);

        if ($sameAsCurrentPassword) {

            return $this->failure(
                "New password must be different from your current password."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Password Hashing
        |--------------------------------------------------------------------------
        */
        $hashedPassword =
            password_hash(
                $newPassword,
                PASSWORD_DEFAULT
            );

        /*
        |--------------------------------------------------------------------------
        | Update Password Record
        |--------------------------------------------------------------------------
        */
        $updated =
            $this->userDAO->updatePassword(
                $userID,
                $hashedPassword
            );

        if (!$updated) {

            return $this->failure(
                "Password could not be updated. Please try again."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Password Update Success
        |--------------------------------------------------------------------------
        */
        return $this->success(
            "Password updated successfully."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update Profile Photo
    |--------------------------------------------------------------------------
    | Stores the uploaded profile photo, updates the user record, and deletes
    | the previous profile photo from storage.
    */
    public function updateProfilePhoto($userID, $profilePhotoFile) {

        /*
        |--------------------------------------------------------------------------
        | Load User Record
        |--------------------------------------------------------------------------
        */
        $user =
            $this->userDAO->getUserByID($userID);

        if (!$user) {

            return $this->failure(
                "Account was not found."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Store Uploaded Profile Photo
        |--------------------------------------------------------------------------
        */
        $photoResult =
            $this->imageStorageDAO->storeProfilePhoto(
                $profilePhotoFile,
                $userID
            );

        if (!$photoResult["success"]) {

            return $this->failure(
                $photoResult["message"]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Empty File Selection Validation
        |--------------------------------------------------------------------------
        */
        if ($photoResult["path"] === null) {

            return $this->failure(
                "Please select a JPG or PNG profile photo."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Update Profile Photo Path
        |--------------------------------------------------------------------------
        */
        $updated =
            $this->userDAO->updateProfilePhoto(
                $userID,
                $photoResult["path"]
            );

        if (!$updated) {

            return $this->failure(
                "Profile photo could not be updated."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Delete Previous Profile Photo
        |--------------------------------------------------------------------------
        */
        $this->imageStorageDAO->deleteStoredImage(
            $user["profilePhotoPath"] ?? ""
        );

        /*
        |--------------------------------------------------------------------------
        | Profile Photo Update Success
        |--------------------------------------------------------------------------
        */
        return $this->success(
            "Profile photo updated successfully."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Remove Profile Photo
    |--------------------------------------------------------------------------
    | Clears the profile photo path and removes the managed photo file.
    */
    public function removeProfilePhoto($userID) {

        $user =
            $this->userDAO->getUserByID($userID);

        if (!$user) {

            return $this->failure(
                "Account was not found."
            );
        }

        if (empty($user["profilePhotoPath"])) {

            return $this->failure(
                "No profile photo is currently set."
            );
        }

        $updated =
            $this->userDAO->updateProfilePhoto(
                $userID,
                ""
            );

        if (!$updated) {

            return $this->failure(
                "Profile photo could not be removed."
            );
        }

        $this->imageStorageDAO->deleteStoredImage(
            $user["profilePhotoPath"]
        );

        return $this->success(
            "Profile photo removed successfully."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Strong Password Validation
    |--------------------------------------------------------------------------
    | Ensures the password has at least 8 characters, one letter, one digit,
    | and one special character.
    */
    private function isStrongPassword($password) {

        return
            strlen($password) >= 8 &&
            preg_match("/[A-Za-z]/", $password) &&
            preg_match("/[0-9]/", $password) &&
            preg_match("/[^A-Za-z0-9]/", $password);
    }

    /*
    |--------------------------------------------------------------------------
    | Success Response Helper
    |--------------------------------------------------------------------------
    */
    private function success($message) {

        return [
            "success" => true,
            "message" => $message
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Failure Response Helper
    |--------------------------------------------------------------------------
    */
    private function failure($message) {

        return [
            "success" => false,
            "message" => $message
        ];
    }
}

?>
