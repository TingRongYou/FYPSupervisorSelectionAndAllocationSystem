<?php

require_once "AuthManager.php";
require_once "SessionManager.php";

/*
|--------------------------------------------------------------------------
| Start Session
|--------------------------------------------------------------------------
*/

SessionManager::startSession();

/*
|--------------------------------------------------------------------------
| Request Method Validation
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: ../../client/login.html?status=error&message=Invalid request method");
    exit();
}

/*
|--------------------------------------------------------------------------
| Retrieve Form Data
|--------------------------------------------------------------------------
*/

$email =
    trim($_POST["email"] ?? "");

$password =
    trim($_POST["password"] ?? "");

/*
|--------------------------------------------------------------------------
| Empty Field Validation
|--------------------------------------------------------------------------
*/

if ($email === "" || $password === "") {

    header("Location: ../../client/login.html?status=error&message=Email and password are required");
    exit();
}

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

$authManager =
    new AuthManager();

$isLoggedIn =
    $authManager->login(
        $email,
        $password
    );

/*
|--------------------------------------------------------------------------
| Login Failed
|--------------------------------------------------------------------------
*/

if (!$isLoggedIn) {

    header("Location: ../../client/login.html?status=error&message=Invalid email or password");
    exit();
}

/*
|--------------------------------------------------------------------------
| Role-Based Redirect
|--------------------------------------------------------------------------
*/

$systemRole =
    $_SESSION["systemRole"] ?? "";

/*
|--------------------------------------------------------------------------
| Administrator
|--------------------------------------------------------------------------
*/

if ($systemRole === "Administrator") {

    header("Location: ../../client/adminDashboard.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| Supervisor
|--------------------------------------------------------------------------
*/

if ($systemRole === "Supervisor") {

    header("Location: ../../client/supervisorDashboard.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| Student
|--------------------------------------------------------------------------
*/

if ($systemRole === "Student") {

    header("Location: ../../client/studentDashboard.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| Invalid Role
|--------------------------------------------------------------------------
*/

SessionManager::destroySession();

header("Location: ../../client/login.html?status=error&message=Invalid system role");
exit();

?>