<?php

require_once __DIR__ . "/../../data/dao/SupervisorDAO.php";
require_once __DIR__ . "/../../data/dao/TagDAO.php";
require_once __DIR__ . "/SupervisorAvailabilityService.php";

abstract class DiscoveryEngine { // Abstract class that defines structure for discovery method

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

    // Calls multiple methods to transform raw data into polished result
    public function executeSearch(
        $context
    ) {

        $supervisors =
            $this->fetchActiveSupervisors(); // Get a raw list of supervisors and decorates them with their availability status

        $supervisors =
            $this->attachTagPayload($supervisors); // Add supervisor's research interest tag to their profile

        $supervisors =
            $this->filterSlotAvailability($supervisors, $context); // Filter student's availability status choice

        $supervisors =
            $this->applyMatchingLogic($supervisors, $context); // An abstract method

        $supervisors =
            $this->rankResults($supervisors, $context); // sorts the filtered list

        return $this->renderResults($supervisors, $context); // Returns the final results
    }

    // Get supervisors that are available
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

    // Attach tags to supervisors
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

    // Filter availability statuses
    protected function filterSlotAvailability(
        $supervisors,
        $context
    ) {

        $quotaStatus =
            $context["quotaStatus"] ?? $context["availability"] ?? "";

        return array_values(
            array_filter(
                $supervisors,
                function ($supervisor) use ($quotaStatus) { // Depends on availability status filter

                    return
                        $quotaStatus === "" ||
                        $supervisor["quotaStatus"] === $quotaStatus;
                }
            )
        );
    }

    abstract protected function applyMatchingLogic( // Used for different search types, including manual and recommendation
        $supervisors,
        $context
    );

    protected function rankResults(
        $supervisors,
        $context
    ) {

        // Ranking priorities
        usort(
            $supervisors,
            function ($first, $second) {

                if ($first["canApply"] !== $second["canApply"]) { // Can apply one on the top

                    return $first["canApply"] ? -1 : 1;
                }

                if ($first["availabilityStatus"] !== $second["availabilityStatus"]) { // Online on top if both can apply

                    return $first["availabilityStatus"] === "Online"
                        ? -1
                        : 1;
                }

                return strcmp( // If both can't apply and offline, then based on the name alphebtical order
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

        return count( // Returns the number of matches
            array_intersect( // Compare two arrays and returns a new array with intersects value
                array_map("intval", $first), // intval ensures the comparison is done on integers
                array_map("intval", $second)
            )
        );
    }
}

// Allows student to filter
class ManualSearchProcessor extends DiscoveryEngine { // Manual search that overwrite applyMatchingLogic function

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
                        strpos(strtolower($supervisor["fullName"]), $searchName) === false // Checks if searchName exist within supervisor's fullName
                    ) {

                        return false;
                    }

                    if (
                        $programme !== "" &&
                        $supervisor["programme"] !== $programme // Check the programme
                    ) {

                        return false;
                    }

                    if (
                        $interestTagID > 0 &&
                        !in_array($interestTagID, $supervisor["tagIDs"], true) // Check if the interestTagID is in their list of research interest
                    ) {

                        return false;
                    }

                    return true;
                }
            )
        );
    }
}

// Recommends supervisors based on student's saved interest tags
class RecommendationProcessor extends DiscoveryEngine {

    protected function applyMatchingLogic(
        $supervisors,
        $context
    ) {

        // Check if student has any tags
        $studentTagIDs =
            $this->tagDAO
            ->getStudentTagIDs($context["studentID"] ?? "");

        if (empty($studentTagIDs)) {

            return [];
        }

        $matched =
            [];

        // Calculate supervisor limit
        foreach ($supervisors as $supervisor) {

            if (($supervisor["quotaStatus"] ?? "Full") !== "Available") { // Skip if supervisor is not available

                continue;
            }

            // Find overlapping tags between student and supervisor
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

    // Sorts overlapping count DESCENDING, limit output to maximum of 3 elements
    protected function rankResults(
        $supervisors,
        $context
    ) {

        usort(
            $supervisors,
            function ($first, $second) {

                if ($first["matchScore"] !== $second["matchScore"]) {

                    return $second["matchScore"] <=> $first["matchScore"]; // Sort in descending order
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

        return array_slice( // Ensure only top 3 are returned to the UI
            array_values($supervisors),
            0,
            3
        );
    }
}

?>
