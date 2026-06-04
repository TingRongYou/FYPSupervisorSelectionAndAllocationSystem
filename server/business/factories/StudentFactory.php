<?php

require_once __DIR__ . "/RoleFactory.php";
require_once __DIR__ . "/../entities/Student.php";

class StudentFactory implements RoleFactory {

    public function createRole($data) {

        return new Student(
            $data["userID"] ?? "",
            $data["fullName"] ?? "",
            $data["universityEmail"] ?? "",
            $data["password"] ?? "",
            $data["activeStatus"] ?? true,
            $data["programme"] ?? "",
            $data["cgpa"] ?? 0.0,
            $data["academicStatus"] ?? "",
            $data["currentSem"] ?? ""
        );
    }
}

?>
