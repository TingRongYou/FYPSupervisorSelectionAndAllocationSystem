<?php

require_once __DIR__ . "/ApplicationState.php";
require_once __DIR__ . "/AcceptedState.php";
require_once __DIR__ . "/RejectedState.php";
require_once __DIR__ . "/TimeoutState.php";
require_once __DIR__ . "/WithdrawnState.php";
require_once __DIR__ . "/AutoAllocatedState.php";

class PendingState implements ApplicationState {

    public function accept(FYPApplication $application, $comment) {

        $application->setComment($comment);
        $application->changeState(new AcceptedState());

        return [
            "success" => true,
            "decisionStatus" => "Accepted",
            "comment" => $application->getComment()
        ];
    }

    public function reject(FYPApplication $application, $comment) {

        $application->setComment($comment);
        $application->changeState(new RejectedState());

        return [
            "success" => true,
            "decisionStatus" => "Rejected",
            "comment" => $application->getComment()
        ];
    }

    public function timeout(FYPApplication $application, $comment) {

        $application->setComment($comment);
        $application->changeState(new TimeoutState());

        return [
            "success" => true,
            "decisionStatus" => "Rejected-Timeout",
            "comment" => $application->getComment()
        ];
    }

    public function withdraw(FYPApplication $application, $comment) {

        $application->setComment($comment);
        $application->changeState(new WithdrawnState());

        return [
            "success" => true,
            "decisionStatus" => "Withdrawn",
            "comment" => $application->getComment()
        ];
    }

    public function autoAllocate(FYPApplication $application, $comment) {

        $application->setComment($comment);
        $application->changeState(new AutoAllocatedState());

        return [
            "success" => true,
            "decisionStatus" => "Auto-Allocated",
            "comment" => $application->getComment()
        ];
    }
}

?>
