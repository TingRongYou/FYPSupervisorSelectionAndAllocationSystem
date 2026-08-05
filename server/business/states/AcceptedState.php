<?php

require_once __DIR__ . "/TerminalApplicationState.php";

// Concrete terminal states are clean, empty subclasses inheriting the locks
class AcceptedState extends TerminalApplicationState {}

?>
