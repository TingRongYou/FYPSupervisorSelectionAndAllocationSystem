<?php

require_once __DIR__ . "/User.php";

class Admin extends User {

    public function getSystemRole() {
        return "Administrator";
    }

    public function generateReport() {
        return true;
    }

    public function manageQuota() {
        return true;
    }
}

?>

