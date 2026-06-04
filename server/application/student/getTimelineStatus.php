<?php

require_once __DIR__ . "/../auth/SessionManager.php";
require_once __DIR__ . "/../../business/services/TemporalPhaseEngine.php";

SessionManager::startSession();
SessionManager::requireRole("Student");

header("Content-Type: application/json");

echo json_encode(
    TemporalPhaseEngine::getInstance()->getPhasePayload()
);

?>
