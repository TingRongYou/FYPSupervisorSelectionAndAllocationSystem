<?php

require_once __DIR__ . "/SupervisorProfile.php";

class BasicSupervisorProfile implements SupervisorProfile {

    private $profile;

    public function __construct($profile) {

        $this->profile = $profile;
    }

    public function display() {

        return $this->profile;
    }
}

?>
