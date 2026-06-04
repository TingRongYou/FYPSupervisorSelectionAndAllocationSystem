<?php

require_once __DIR__ . "/../../data/dao/SupervisorDAO.php";
require_once __DIR__ . "/../../data/dao/TagDAO.php";
require_once __DIR__ . "/SupervisorAvailabilityService.php";

abstract class DiscoveryEngine {

    protected $supervisorDAO;

    protected $tagDAO;

    protected $availabilityService;

    public function __construct() {

        $this->supervisorDAO =
            new SupervisorDAO();

        $this->tagDAO =
            new TagDAO();

        $this->availabilityService =
            new SupervisorAvailabilityService();
    }

    public function executeSearch(
        $context
    ) {

        $supervisors =
            $this->fetchActiveSupervisors();

        $supervisors =
            $this->attachTagPayload($supervisors);

        $supervisors =
            $this->filterSlotAvailability($supervisors, $context);

        $supervisors =
            $this->applyMatchingLogic($supervisors, $context);

        $supervisors =
            $this->rankResults($supervisors, $context);

        return $this->renderResults($supervisors, $context);
    }

    protected function fetchActiveSupervisors() {

        $supervisors =
            $this->supervisorDAO
            ->getSupervisorsForDiscovery("", "", "");

        foreach ($supervisors as $index => $supervisor) {

            $supervisors[$index] =
                $this->availabilityService
                ->decorateAvailability($supervisor);
        }

        return $supervisors;
    }

    protected function attachTagPayload(
        $supervisors
    ) {

        $supervisorIDs =
            array_map(
                function ($supervisor) {

                    return $supervisor["userID"];
                },
                $supervisors
            );

        $tagMap =
            $this->tagDAO
            ->getSupervisorTagMap($supervisorIDs);

        foreach ($supervisors as $index => $supervisor) {

            $tags =
                $tagMap[$supervisor["userID"]] ?? [];

            $supervisors[$index]["tagIDs"] =
                array_map(
                    function ($tag) {

                        return (int) $tag["tagID"];
                    },
                    $tags
                );

            $supervisors[$index]["tagNames"] =
                array_map(
                    function ($tag) {

                        return $tag["tagName"];
                    },
                    $tags
                );
        }

        return $supervisors;
    }

    protected function filterSlotAvailability(
        $supervisors,
        $context
    ) {

        $quotaStatus =
            $context["quotaStatus"] ?? $context["availability"] ?? "";

        return array_values(
            array_filter(
                $supervisors,
                function ($supervisor) use ($quotaStatus) {

                    return
                        $quotaStatus === "" ||
                        $supervisor["quotaStatus"] === $quotaStatus;
                }
            )
        );
    }

    abstract protected function applyMatchingLogic(
        $supervisors,
        $context
    );

    protected function rankResults(
        $supervisors,
        $context
    ) {

        usort(
            $supervisors,
            function ($first, $second) {

                if ($first["canApply"] !== $second["canApply"]) {

                    return $first["canApply"] ? -1 : 1;
                }

                if ($first["availabilityStatus"] !== $second["availabilityStatus"]) {

                    return $first["availabilityStatus"] === "Online"
                        ? -1
                        : 1;
                }

                return strcmp(
                    $first["fullName"],
                    $second["fullName"]
                );
            }
        );

        return $supervisors;
    }

    protected function renderResults(
        $supervisors,
        $context
    ) {

        return array_values($supervisors);
    }

    protected function intersects(
        $first,
        $second
    ) {

        return count(
            array_intersect(
                array_map("intval", $first),
                array_map("intval", $second)
            )
        );
    }
}

class ManualSearchProcessor extends DiscoveryEngine {

    protected function applyMatchingLogic(
        $supervisors,
        $context
    ) {

        $searchName =
            strtolower(trim($context["searchName"] ?? ""));

        $programme =
            trim($context["programme"] ?? "");

        $interestTagID =
            (int) ($context["interestTagID"] ?? 0);

        return array_values(
            array_filter(
                $supervisors,
                function ($supervisor) use ($searchName, $programme, $interestTagID) {

                    if (
                        $searchName !== "" &&
                        strpos(strtolower($supervisor["fullName"]), $searchName) === false
                    ) {

                        return false;
                    }

                    if (
                        $programme !== "" &&
                        $supervisor["programme"] !== $programme
                    ) {

                        return false;
                    }

                    if (
                        $interestTagID > 0 &&
                        !in_array($interestTagID, $supervisor["tagIDs"], true)
                    ) {

                        return false;
                    }

                    return true;
                }
            )
        );
    }
}

class RecommendationProcessor extends DiscoveryEngine {

    protected function applyMatchingLogic(
        $supervisors,
        $context
    ) {

        $studentTagIDs =
            $this->tagDAO
            ->getStudentTagIDs($context["studentID"] ?? "");

        if (empty($studentTagIDs)) {

            return [];
        }

        $matched =
            [];

        foreach ($supervisors as $supervisor) {

            if (($supervisor["quotaStatus"] ?? "Full") !== "Available") {

                continue;
            }

            $matchScore =
                $this->intersects(
                    $studentTagIDs,
                    $supervisor["tagIDs"]
                );

            if ($matchScore <= 0) {

                continue;
            }

            $supervisor["matchScore"] =
                $matchScore;

            $matched[] =
                $supervisor;
        }

        return $matched;
    }

    protected function rankResults(
        $supervisors,
        $context
    ) {

        usort(
            $supervisors,
            function ($first, $second) {

                if ($first["matchScore"] !== $second["matchScore"]) {

                    return $second["matchScore"] <=> $first["matchScore"];
                }

                return strcmp(
                    $first["fullName"],
                    $second["fullName"]
                );
            }
        );

        return $supervisors;
    }

    protected function renderResults(
        $supervisors,
        $context
    ) {

        return array_slice(
            array_values($supervisors),
            0,
            3
        );
    }
}

?>
