<?php

require_once __DIR__ . "/ProfileDecorator.php";

class ProjectShowcaseDecorator extends ProfileDecorator {

    private $pastProjects;

    public function __construct(SupervisorProfile $profile, $pastProjects) {

        parent::__construct($profile);

        $this->pastProjects = is_array($pastProjects) ? array_values($pastProjects) : [];
    }

    public function display() {

        $profile = parent::display();
        $profile["pastProjects"] = $this->pastProjects;

        return $profile;
    }
}

?>
