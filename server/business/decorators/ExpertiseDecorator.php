<?php

require_once __DIR__ . "/ProfileDecorator.php";

class ExpertiseDecorator extends ProfileDecorator {

    private $areasOfInterest;

    public function __construct(SupervisorProfile $profile, $areasOfInterest) {

        parent::__construct($profile);

        $this->areasOfInterest = is_array($areasOfInterest) ? array_values($areasOfInterest) : [];
    }

    public function display() {

        $profile = parent::display();
        $profile["expertiseTags"] = $this->areasOfInterest;

        return $profile;
    }
}

?>
