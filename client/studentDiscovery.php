<?php

require_once __DIR__ . "/../server/application/SessionManager.php";
require_once __DIR__ . "/../server/business/SupervisorDiscoveryService.php";

// Session validation: only authenticated students can access supervisor discovery.
SessionManager::startSession();

if (!SessionManager::isLoggedIn()) {

    die("Access Denied");
}

SessionManager::requireRole("Student");

$discoveryService = new SupervisorDiscoveryService();

// Filtering logic: GET parameters are passed to the business service for normalisation.
$searchName = $_GET["searchName"] ?? "";
$selectedProgramme = $_GET["programme"] ?? "";
$selectedAvailability = $_GET["availability"] ?? "";

$programmes = $discoveryService->getProgrammes();
$supervisors = $discoveryService->discoverSupervisors($_GET);

function escapeOutput($value) {

    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisor Discovery | SSAS</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f8fc;
            color: #1d2b3a;
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background: #0b4f8a;
            color: #ffffff;
            padding: 28px 22px;
        }

        .brand {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #cfe5f8;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 32px;
        }

        .nav-link {
            display: block;
            color: #eaf5ff;
            text-decoration: none;
            padding: 12px 14px;
            border-radius: 8px;
            margin-bottom: 8px;
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .nav-link:hover,
        .nav-link.active {
            background: #176fac;
            transform: translateX(2px);
        }

        .main {
            flex: 1;
            padding: 34px;
        }

        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 24px;
        }

        h1 {
            margin: 0 0 8px;
            color: #0b3760;
            font-size: 30px;
        }

        .welcome {
            margin: 0;
            color: #5c6f82;
        }

        .filters {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 14px;
            background: #ffffff;
            border: 1px solid #d9e7f3;
            border-radius: 8px;
            padding: 18px;
            margin-bottom: 24px;
            box-shadow: 0 8px 22px rgba(11, 79, 138, 0.08);
        }

        .field label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #35546d;
            margin-bottom: 7px;
        }

        input,
        select {
            width: 100%;
            height: 42px;
            border: 1px solid #c6d8e8;
            border-radius: 6px;
            padding: 0 12px;
            font-size: 14px;
            background: #ffffff;
        }

        input:focus,
        select:focus {
            outline: 2px solid #9dccf1;
            border-color: #2179b8;
        }

        .filter-actions {
            display: flex;
            align-items: end;
            gap: 10px;
        }

        .button,
        button {
            height: 42px;
            border: 0;
            border-radius: 6px;
            padding: 0 18px;
            background: #0b66ad;
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
        }

        .button:hover,
        button:hover {
            background: #084f88;
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(11, 102, 173, 0.2);
        }

        .button.secondary {
            background: #e8f2fb;
            color: #0b4f8a;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .card {
            background: #ffffff;
            border: 1px solid #d9e7f3;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 8px 22px rgba(11, 79, 138, 0.08);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 26px rgba(11, 79, 138, 0.14);
        }

        .card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
        }

        .supervisor-name {
            margin: 0;
            font-size: 20px;
            color: #0b3760;
        }

        .badge {
            border-radius: 999px;
            color: #ffffff;
            font-size: 12px;
            font-weight: 700;
            padding: 6px 10px;
            white-space: nowrap;
        }

        .badge.available {
            background: #178f56;
        }

        .badge.full {
            background: #c93838;
        }

        .meta {
            display: grid;
            gap: 10px;
            margin-bottom: 18px;
        }

        .meta-item {
            color: #526a7f;
            font-size: 14px;
            line-height: 1.4;
        }

        .meta-item strong {
            display: block;
            color: #243d54;
            font-size: 13px;
            margin-bottom: 2px;
        }

        .apply-button.disabled {
            background: #b7c5d1;
            color: #ffffff;
            cursor: not-allowed;
            pointer-events: none;
            box-shadow: none;
        }

        .empty-state {
            background: #ffffff;
            border: 1px dashed #aac7df;
            border-radius: 8px;
            padding: 28px;
            color: #526a7f;
            text-align: center;
        }

        @media (max-width: 1100px) {
            .cards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .filters {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 760px) {
            .layout {
                display: block;
            }

            .sidebar {
                width: 100%;
            }

            .main {
                padding: 22px;
            }

            .page-header {
                display: block;
            }

            .filters,
            .cards {
                grid-template-columns: 1fr;
            }

            .filter-actions {
                align-items: stretch;
            }

            .button,
            button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <div class="brand">SSAS</div>
            <div class="subtitle">TAR UMT Supervisor Selection and Allocation System</div>
            <a class="nav-link" href="studentDashboard.php">Dashboard</a>
            <a class="nav-link active" href="studentDiscovery.php">Supervisor Discovery</a>
            <a class="nav-link" href="../server/application/logout.php">Logout</a>
        </aside>

        <main class="main">
            <section class="page-header">
                <div>
                    <h1>Supervisor Discovery</h1>
                    <p class="welcome">Welcome, <?php echo escapeOutput($_SESSION["fullName"]); ?></p>
                </div>
            </section>

            <form class="filters" method="GET" action="studentDiscovery.php">
                <div class="field">
                    <label for="searchName">Search Supervisor Name</label>
                    <input type="text" id="searchName" name="searchName" value="<?php echo escapeOutput($searchName); ?>" placeholder="Enter supervisor name">
                </div>

                <div class="field">
                    <label for="programme">Programme</label>
                    <select id="programme" name="programme">
                        <option value="">All Programmes</option>
                        <?php foreach ($programmes as $programme): ?>
                            <option value="<?php echo escapeOutput($programme["programme"]); ?>" <?php echo $selectedProgramme === $programme["programme"] ? "selected" : ""; ?>>
                                <?php echo escapeOutput($programme["programme"]); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="availability">Availability</label>
                    <select id="availability" name="availability">
                        <option value="">All Availability</option>
                        <option value="Available" <?php echo $selectedAvailability === "Available" ? "selected" : ""; ?>>Available</option>
                        <option value="Full" <?php echo $selectedAvailability === "Full" ? "selected" : ""; ?>>Full</option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit">Filter</button>
                    <a class="button secondary" href="studentDiscovery.php">Reset</a>
                </div>
            </form>

            <?php if (empty($supervisors)): ?>
                <div class="empty-state">No supervisors match the selected filters.</div>
            <?php else: ?>
                <section class="cards">
                    <?php foreach ($supervisors as $supervisor): ?>
                        <?php
                            $isFull = $supervisor["status"] === "Full";
                            $badgeClass = $isFull ? "full" : "available";
                        ?>
                        <article class="card">
                            <div class="card-header">
                                <h2 class="supervisor-name"><?php echo escapeOutput($supervisor["fullName"]); ?></h2>
                                <span class="badge <?php echo $badgeClass; ?>">
                                    <?php echo escapeOutput($supervisor["status"]); ?>
                                </span>
                            </div>

                            <div class="meta">
                                <div class="meta-item">
                                    <strong>Programme</strong>
                                    <?php echo escapeOutput($supervisor["programme"]); ?>
                                </div>
                                <div class="meta-item">
                                    <strong>Employment Category</strong>
                                    <?php echo escapeOutput($supervisor["employmentCategory"]); ?>
                                </div>
                                <div class="meta-item">
                                    <strong>Quota</strong>
                                    <?php echo escapeOutput($supervisor["quotaText"]); ?>
                                </div>
                            </div>

                            <?php if ($isFull): ?>
                                <span class="button apply-button disabled">Apply</span>
                            <?php else: ?>
                                <a class="button apply-button" href="applySupervisorForm.php?supervisorID=<?php echo urlencode($supervisor["userID"]); ?>">Apply</a>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
