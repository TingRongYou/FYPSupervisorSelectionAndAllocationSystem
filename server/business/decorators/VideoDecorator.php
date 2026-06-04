<?php

require_once __DIR__ . "/ProfileDecorator.php";

class VideoDecorator extends ProfileDecorator {

    private $videoLink;

    private $videoDescription;

    public function __construct(SupervisorProfile $profile, $videoLink, $videoDescription) {

        parent::__construct($profile);

        $this->videoLink = trim((string) $videoLink);
        $this->videoDescription = trim((string) $videoDescription);
    }

    public function display() {

        $profile = parent::display();
        $profile["introVideoLink"] = $this->videoLink;
        $profile["introVideoDescription"] = $this->videoDescription;
        $profile["hasIntroVideo"] = $this->videoLink !== "";

        return $profile;
    }
}

?>
