<?php

interface ApplicationState {

    public function accept(FYPApplication $application, $comment);

    public function reject(FYPApplication $application, $comment);

    public function timeout(FYPApplication $application, $comment);

    public function withdraw(FYPApplication $application, $comment);

    public function autoAllocate(FYPApplication $application, $comment);
}

?>
