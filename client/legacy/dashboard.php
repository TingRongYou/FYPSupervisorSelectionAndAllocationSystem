<?php

require_once "../../server/application/auth/SessionManager.php";

SessionManager::startSession();

if (!SessionManager::isLoggedIn()) {

    die("Access Denied");
}

?>

<h1>Welcome to SSAS Dashboard</h1>

<p>User ID: <?php echo $_SESSION['userID']; ?></p>

<p>Full Name: <?php echo $_SESSION['fullName']; ?></p>

<p>Role: <?php echo $_SESSION['systemRole']; ?></p>

<br><br>

<a href="../../server/application/auth/logout.php">
    Logout
</a>

