<?php

require_once __DIR__ . "/../entities/User.php";

interface RoleFactory {

    public function createRole($data);
}

?>


