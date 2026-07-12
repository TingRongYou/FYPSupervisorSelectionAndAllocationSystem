<?php

require_once __DIR__ . "/../../data/dao/SupervisorProfileDAO.php";

interface AvailabilityObserver {

    public function update(
        $availabilityPayload
    );
}

class SupervisorAvailabilityUI implements AvailabilityObserver {

    private $payload =
        [];

    public function update(
        $availabilityPayload
    ) {

        $isOnline =
            ($availabilityPayload["availabilityStatus"] ?? "Offline") === "Online";

        $isQuotaOpen =
            ($availabilityPayload["quotaStatus"] ?? "Full") === "Available";

        $this->payload =
            array_merge(
                $availabilityPayload,
                [
                    "availabilityClass" =>
                        $isOnline ? "online" : "offline",

                    "quotaClass" =>
                        $isQuotaOpen ? "available" : "full",

                    "canApply" =>
                        $this->toggleApplyButton($isQuotaOpen),

                    "buttonLabel" =>
                        $this->buttonLabel($isQuotaOpen),

                    "message" =>
                        $this->statusMessage($isQuotaOpen)
                ]
            );
    }

    public function calculateQuota() {

        return
            ($this->payload["activeStudents"] ?? 0) .
            "/" .
            ($this->payload["maxSlots"] ?? 0) .
            " slots taken";
    }

    public function toggleApplyButton($isQuotaOpen) {

        return (bool) $isQuotaOpen;
    }

    public function getPayload() {

        $this->payload["quotaText"] =
            $this->calculateQuota();

        return $this->payload;
    }

    private function buttonLabel($isQuotaOpen) {

        if (!$isQuotaOpen) {

            return "Application Closed";
        }

        return "Apply";
    }

    private function statusMessage($isQuotaOpen) {

        if (!$isQuotaOpen) {

            return SupervisorAvailabilityService::SUPERVISOR_FULL_MESSAGE;
        }

        return "This supervisor has available supervision slots and is accepting proposals.";
    }
}

class AllocationRegistry { // Calculate and broadcast supervisor's real time availabiltiy

    private const ONLINE_THRESHOLD_SECONDS =
        900;

    private $observers =
        [];

    public function attach(
        AvailabilityObserver $observer
    ) {

        $this->observers[] =
            $observer;
    }

    public function detach(
        AvailabilityObserver $observer
    ) {

        $this->observers =
            array_filter(
                $this->observers,
                function ($registeredObserver) use ($observer) {

                    return $registeredObserver !== $observer;
                }
            );
    }

    public function saveAllocationStatus(
        $supervisor
    ) {

        $payload =
            $this->buildAvailabilityPayload($supervisor);

        $this->notifyObservers($payload);

        return $payload;
    }

    private function buildAvailabilityPayload(
        $supervisor
    ) {

        $activeStudents =
            max(
                0,
                (int) ($supervisor["activeStudents"]
                    ?? $supervisor["currentSupervisees"]
                    ?? 0)
            );

        $maxSlots =
            max(
                0,
                (int) ($supervisor["maxSuperviseesAllowed"]
                    ?? $supervisor["maxSlots"]
                    ?? 0)
            );

        $lastSeenAt =
            $supervisor["lastSeenAt"] ?? null;

        $isOnline =
            ((int) ($supervisor["isOnline"] ?? 0)) === 1
            &&
            $lastSeenAt !== null
            &&
            (time() - strtotime($lastSeenAt)) <= self::ONLINE_THRESHOLD_SECONDS;

        $quotaStatus =
            $maxSlots > 0 && $activeStudents < $maxSlots
                ? "Available"
                : "Full";

        return [
            "availabilityStatus" =>
                $isOnline ? "Online" : "Offline",

            "quotaStatus" =>
                $quotaStatus,

            "activeStudents" =>
                $activeStudents,

            "maxSlots" =>
                $maxSlots,

            "availableSlots" =>
                max(0, $maxSlots - $activeStudents),

            "lastSeenAt" =>
                $lastSeenAt
        ];
    }

    private function notifyObservers(
        $payload
    ) {

        foreach ($this->observers as $observer) {

            $observer->update($payload);
        }
    }
}

class SupervisorAvailabilityService {

    public const SUPERVISOR_OFFLINE_MESSAGE =
        "This supervisor is currently offline. Availability is still determined by remaining supervision slots.";

    public const SUPERVISOR_FULL_MESSAGE =
        "This supervisor has reached their maximum student capacity and is no longer accepting proposals at this time.";

    private $profileDAO;

    private $registry;

    public function __construct() {

        $this->profileDAO =
            new SupervisorProfileDAO();

        $this->registry =
            new AllocationRegistry();
    }

    // Calculate or fetches their current workload
    public function decorateAvailability(
        $supervisor
    ) {

        $uiObserver =
            new SupervisorAvailabilityUI();

        $this->registry
            ->attach($uiObserver);

        $payload =
            $this->registry
            ->saveAllocationStatus($supervisor);

        $uiPayload =
            $uiObserver
            ->getPayload();

        $this->registry
            ->detach($uiObserver);

        return array_merge(
            $supervisor,
            $payload,
            $uiPayload,
            [
                "status" =>
                    $uiPayload["availabilityStatus"],

                "statusClass" =>
                    $uiPayload["availabilityClass"]
            ]
        );
    }

    public function getSupervisorAvailability(
        $supervisorID
    ) {

        $profile =
            $this->profileDAO
            ->getSupervisorProfile($supervisorID);

        if (!$profile) {

            return null;
        }

        return $this->decorateAvailability($profile);
    }

    public function canStudentApply(
        $supervisorID
    ) {

        $availability =
            $this->getSupervisorAvailability($supervisorID);

        if (!$availability) {

            return [
                "success" => false,
                "message" => "Selected supervisor was not found."
            ];
        }

        if (!$availability["canApply"]) {

            return [
                "success" => false,
                "message" => $availability["message"]
            ];
        }

        return [
            "success" => true,
            "message" => "This supervisor has available supervision slots and is accepting proposals.",
            "availability" => $availability
        ];
    }
}

?>
