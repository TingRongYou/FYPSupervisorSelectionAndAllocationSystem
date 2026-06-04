<?php

require_once __DIR__ . "/User.php";

class Student extends User {

    private $cgpa;
    private $academicStatus;
    private $currentSem;

    public function __construct(
        $userID,
        $name,
        $universityEmail,
        $password,
        $activeStatus = true,
        $programme = "",
        $cgpa = 0.0,
        $academicStatus = "",
        $currentSem = ""
    ) {

        parent::__construct(
            $userID,
            $name,
            $universityEmail,
            $password,
            $activeStatus,
            $programme
        );

        $this->cgpa = (float) $cgpa;
        $this->academicStatus = $academicStatus;
        $this->currentSem = $currentSem;
    }

    public function getSystemRole() {
        return "Student";
    }

    public function getCgpa() {
        return $this->cgpa;
    }

    public function getAcademicStatus() {
        return $this->academicStatus;
    }

    public function getCurrentSem() {
        return $this->currentSem;
    }
}

?>
