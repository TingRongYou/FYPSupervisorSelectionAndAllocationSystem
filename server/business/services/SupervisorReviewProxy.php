<?php

interface ReviewRecordView {

    public function display();
}

class SupervisorReviewRecord implements ReviewRecordView {

    private $review;

    public function __construct($review) {

        $this->review = $review;
    }

    public function display() {

        return [
            "reviewID" => $this->review["reviewID"],
            "authorName" => $this->review["trueStudentName"] ?? "Student",
            "authorID" => $this->review["trueStudentID"] ?? "",
            "starRating" => (int) $this->review["starRating"],
            "textFeedback" => $this->review["textFeedback"] ?? "",
            "isAnonymous" => (bool) ($this->review["isAnonymous"] ?? false)
        ];
    }
}

class AnonymousReviewProxy implements ReviewRecordView {

    private $reviewRecord;

    public function __construct(ReviewRecordView $reviewRecord) {

        $this->reviewRecord = $reviewRecord;
    }

    public function display() {

        $payload = $this->reviewRecord->display();

        if ($payload["isAnonymous"]) {

            $payload["authorName"] = "Anonymous Student";
            $payload["authorID"] = "";
        }

        return $payload;
    }
}

?>
