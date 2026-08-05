<?php

require_once __DIR__ . "/ApplicationState.php";

abstract class TerminalApplicationState implements ApplicationState {
    public function accept(FYPApplication $application, $comment) {
        return $this->lockedResponse();
    }

    public function reject(FYPApplication $application, $comment) {
        return $this->lockedResponse();
    }

    public function timeout(FYPApplication $application, $comment) {
        return $this->lockedResponse();
    }

    public function withdraw(FYPApplication $application, $comment) {
        return $this->lockedResponse();
    }

    public function autoAllocate(FYPApplication $application, $comment) {
        return $this->lockedResponse();
    }

    protected function lockedResponse() {
        return [
            "success" => false,
            "message" => "Decision already processed for this proposal. A new decision is only available after the student submits a new proposal."
        ];
    }
}

?>
