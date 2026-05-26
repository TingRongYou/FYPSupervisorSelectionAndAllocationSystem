<?php

require_once __DIR__ . "/RoleFactory.php";
require_once __DIR__ . "/../entities/Supervisor.php";

class SupervisorFactory implements RoleFactory {

    public function createRole($data) {

        return new Supervisor(
            $data["userID"] ?? "",
            $data["fullName"] ?? "",
            $data["universityEmail"] ?? "",
            $data["password"] ?? "",
            $data["activeStatus"] ?? true,
            $data["programme"] ?? "",
            $data["employmentCategory"] ?? "",
            $data["baseQuota"] ?? 0,
            $data["expertiseTags"] ?? []
        );
    }
}

?>


