<?php

require_once __DIR__ . "/User.php";

class Supervisor extends User {

    private $employmentCategory;
    private $baseQuota;
    private $expertiseTags;

    public function __construct(
        $userID,
        $name,
        $universityEmail,
        $password,
        $activeStatus = true,
        $programme = "",
        $employmentCategory = "",
        $baseQuota = 0,
        $expertiseTags = []
    ) {

        parent::__construct(
            $userID,
            $name,
            $universityEmail,
            $password,
            $activeStatus,
            $programme
        );

        $this->employmentCategory = $employmentCategory;
        $this->baseQuota = (int) $baseQuota;
        $this->expertiseTags = $expertiseTags;
    }

    public function getSystemRole() {
        return "Supervisor";
    }

    public function getEmploymentCategory() {
        return $this->employmentCategory;
    }

    public function getBaseQuota() {
        return $this->baseQuota;
    }

    public function getExpertiseTags() {
        return $this->expertiseTags;
    }

    public function supervisorClassification($roleType) {
        $this->employmentCategory = $roleType;

        return $this->employmentCategory;
    }
}

?>
