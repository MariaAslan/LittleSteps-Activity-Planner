<?php
session_start();
require_once('database.php');

if (!isset($_SESSION['userID'])) {
    header('Location: login.php');
    exit();
}

$monthValue = $_GET['month'] ?? date('Y-m');

$month = DateTime::createFromFormat('!Y-m', $monthValue);

if (!$month) {
    $month = new DateTime('first day of this month');
}

$monthStart = $month->format('Y-m-01');
$monthEnd = $month->format('Y-m-t');

$query = 'SELECT activities.activityName,
                 activities.activityDate,
                 activities.activityTime,
                 children.childName
          FROM activities
          INNER JOIN children
              ON activities.childID = children.childID
          WHERE children.userID = :userID
            AND activities.activityDate
                BETWEEN :monthStart AND :monthEnd
          ORDER BY activities.activityDate,
                   activities.activityTime';

$statement = $db->prepare($query);
$statement->bindValue(
    ':userID',
    $_SESSION['userID'],
    PDO::PARAM_INT
);
$statement->bindValue(':monthStart', $monthStart);
$statement->bindValue(':monthEnd', $monthEnd);
$statement->execute();
$activities = $statement->fetchAll(PDO::FETCH_ASSOC);
$statement->closeCursor();

$activitiesByDay = [];

foreach ($activities as $activity) {
    $day = (int) date(
        'j',
        strtotime($activity['activityDate'])
    );

    $activitiesByDay[$day][] = $activity;
}

$daysInMonth = (int) $month->format('t');
$firstDay = (int) $month->format('w');

$previousMonth = clone $month;
$previousMonth->modify('-1 month');

$nextMonth = clone $month;
$nextMonth->modify('+1 month');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Calendar - LittleSteps</title>
    <link rel="stylesheet" href="CSS/style.css">

    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #cccccc;
            padding: 10px;
            vertical-align: top;
        }

        td {
            height: 100px;
            width: 14%;
        }

        .activity {
            background-color: #e8f3ec;
            margin-top: 5px;
            padding: 5px;
        }
    </style>
</head>

<body>
    <main>
        <h1>LittleSteps Calendar</h1>

        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="add_activity.php">+ Add Activity</a>
        </nav>

        <p>
            <a href="calendar.php?month=<?php
            echo $previousMonth->format('Y-m');
            ?>">
                Previous
            </a>

            <strong>
                <?php echo $month->format('F Y'); ?>
            </strong>

            <a href="calendar.php?month=<?php
            echo $nextMonth->format('Y-m');
            ?>">
                Next
            </a>
        </p>

        <table>
            <tr>
                <th>Sun</th>
                <th>Mon</th>
                <th>Tue</th>
                <th>Wed</th>
                <th>Thu</th>
                <th>Fri</th>
                <th>Sat</th>
            </tr>

            <tr>
                <?php for ($blank = 0; $blank < $firstDay; $blank++): ?>
                    <td></td>
                <?php endfor; ?>

                <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>
                    <?php
                    $column = ($firstDay + $day - 1) % 7;
                    ?>

                    <td>
                        <strong><?php echo $day; ?></strong>

                        <?php if (isset($activitiesByDay[$day])): ?>
                            <?php foreach (
                                $activitiesByDay[$day] as $activity
                            ): ?>
                                <div class="activity">
                                    <strong>
                                        <?php
                                        echo htmlspecialchars(
                                            $activity['activityName']
                                        );
                                        ?>
                                    </strong>

                                    <br>

                                    <?php
                                    echo htmlspecialchars(
                                        $activity['childName']
                                    );
                                    ?>

                                    <br>

                                    <?php
                                    echo date(
                                        'g:i A',
                                        strtotime(
                                            $activity['activityTime']
                                        )
                                    );
                                    ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </td>

                    <?php if ($column === 6 && $day !== $daysInMonth): ?>
                        </tr><tr>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php
                $lastColumn = ($firstDay + $daysInMonth - 1) % 7;
                ?>

                <?php for (
                    $blank = $lastColumn + 1;
                    $blank < 7;
                    $blank++
                ): ?>
                    <td></td>
                <?php endfor; ?>
            </tr>
        </table>
    </main>
</body>
</html>