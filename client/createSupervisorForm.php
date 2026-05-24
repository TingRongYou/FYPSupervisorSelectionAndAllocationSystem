<?php

require_once "../server/application/SessionManager.php";

SessionManager::startSession();

if (!SessionManager::isLoggedIn()) {

    die("Access Denied");
}

SessionManager::requireRole("Administrator");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Supervisor Account</title>
</head>
<body>
    <h1>Create Supervisor Account</h1>

    <form action="../server/application/createSupervisorProcess.php" method="POST">
        <label for="supervisorID">Supervisor ID</label><br>
        <input type="text" id="supervisorID" name="supervisorID" required><br><br>

        <label for="fullName">Full Name</label><br>
        <input type="text" id="fullName" name="fullName" required><br><br>

        <label for="universityEmail">Email</label><br>
        <input type="email" id="universityEmail" name="universityEmail" required><br><br>

        <label for="password">Password</label><br>
        <input type="password" id="password" name="password" required><br><br>

        <label for="programme">Programme</label><br>
        <input type="text" id="programme" name="programme" required><br><br>

        <label for="employmentCategory">Employment Category</label><br>
        <select id="employmentCategory" name="employmentCategory" required>
            <option value="">Select Employment Category</option>
            <option value="Full-Time">Full-Time</option>
            <option value="Part-Time">Part-Time</option>
        </select><br><br>

        <label for="quotaID">Quota Tier</label><br>
        <input type="number" id="quotaID" name="quotaID" min="1" required><br><br>

        <button type="submit">Create Supervisor</button>
    </form>

    <br>
    <a href="adminDashboard.php">Back to Administrator Dashboard</a>
</body>
</html>
