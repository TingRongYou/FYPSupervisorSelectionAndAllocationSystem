<?php

require_once __DIR__ . "/ApplicationState.php";

class FYPApplication {

    private $requestID;

    private $supervisorID;

    private $studentID;

    private $comment;

    private $state;

    public function __construct($requestID, $supervisorID, $studentID, ApplicationState $initialState) {

        $this->requestID = (int) $requestID;
        $this->supervisorID = $supervisorID;
        $this->studentID = $studentID;
        $this->state = $initialState;
        $this->comment = "";
    }

    public function getRequestID() {

        return $this->requestID;
    }

    public function getSupervisorID() {

        return $this->supervisorID;
    }

    public function getStudentID() {

        return $this->studentID;
    }

    public function getComment() {

        return $this->comment;
    }

    public function setComment($comment) {

        $this->comment = trim((string) $comment);
    }

    public function changeState(ApplicationState $state) {
        $this->state = $state;
    }

    public function accept($comment) {
        return $this->state->accept($this, $comment);
    }

    public function reject($comment) {

        return $this->state->reject($this, $comment);
    }

    public function timeout($comment) {

        return $this->state->timeout($this, $comment);
    }

    public function withdraw($comment) {

        return $this->state->withdraw($this, $comment);
    }

    public function autoAllocate($comment) {

        return $this->state->autoAllocate($this, $comment);
    }
}

?>
