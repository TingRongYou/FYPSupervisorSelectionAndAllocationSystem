<?php

class User {

    protected $userID;
    protected $name;
    protected $universityEmail;
    protected $password;
    protected $activeStatus;
    protected $programme;

    public function __construct(
        $userID,
        $name,
        $universityEmail,
        $password,
        $activeStatus = true,
        $programme = ""
    ) {

        $this->userID = $userID;
        $this->name = $name;
        $this->universityEmail = $universityEmail;
        $this->password = $password;
        $this->activeStatus = $activeStatus;
        $this->programme = $programme;
    }

    public function getUserID() {
        return $this->userID;
    }

    public function getName() {
        return $this->name;
    }

    public function getUniversityEmail() {
        return $this->universityEmail;
    }

    public function getPassword() {
        return $this->password;
    }

    public function getActiveStatus() {
        return $this->activeStatus;
    }

    public function getProgramme() {
        return $this->programme;
    }

    public function getSystemRole() {
        return "User";
    }
}

?>
