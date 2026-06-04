<?php

require_once __DIR__ . "/SupervisorProfile.php";

abstract class ProfileDecorator implements SupervisorProfile {

    protected $wrappedProfile;

    public function __construct(SupervisorProfile $profile) {

        $this->wrappedProfile = $profile;
    }

    public function display() {

        return $this->wrappedProfile->display();
    }
}

?>
