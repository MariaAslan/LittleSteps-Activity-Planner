<?php
session_start();
require_once('database.php');

if (!isset($_SESSION['userID'])) {
    header('Location: login.php');
    exit();
}

$fullName = $_SESSION['fullName'];

$query = 'SELECT activities.activityID,
                 activities.activityName,
                 activities.activityDate,
                 activities.activityTime,
                 activities.notes,
                 children.childName
          FROM activities
          INNER JOIN children
              ON activities.childID = children.childID
          WHERE children.userID = :userID
          ORDER BY activities.activityDate,
                   activities.activityTime';

$statement = $db->prepare($query);
$statement->bindValue(
    ':userID',
    $_SESSION['userID'],
    PDO::PARAM_INT
);
$statement->execute();
$activities = $statement->fetchAll(PDO::FETCH_ASSOC);
$statement->closeCursor();
$query = 'SELECT reminders.reminderID,
                 reminders.reminderDateTime,
                 activities.activityName,
                 children.childName
          FROM reminders
          INNER JOIN activities
              ON reminders.activityID = activities.activityID
          INNER JOIN children
              ON activities.childID = children.childID
          WHERE children.userID = :userID
            AND reminders.isCompleted = 0
          ORDER BY reminders.reminderDateTime';

$statement = $db->prepare($query);
$statement->bindValue(
    ':userID',
    $_SESSION['userID'],
    PDO::PARAM_INT
);
$statement->execute();
$reminders = $statement->fetchAll(PDO::FETCH_ASSOC);
$statement->closeCursor();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - LittleSteps</title>
    <link rel="stylesheet" href="CSS/style.css">
</head>
<body>
    <header>
        <h1>LittleSteps</h1>
        <a href="logout.php">Log Out</a>
    </header>

    <main>
        <h2>
            Welcome,
            <?php echo htmlspecialchars($fullName); ?>!
        </h2>

        <p>Here’s what’s happening with your family today.</p>

        <nav>
            <a href="add_child.php">+ Add Child</a>
            <a href="add_activity.php">+ Add Activity</a>
            <a href="add_reminder.php">+ Add Reminder</a><a href="calendar.php">Calendar</a>
        </nav>

        <h3>Activities</h3>

        <?php if (count($activities) === 0): ?>
            <p>No activities added yet.</p>
        <?php else: ?>
            <?php foreach ($activities as $activity): ?>
                <article>
                    <h4>
                        <?php
                        echo htmlspecialchars(
                            $activity['activityName']
                        );
                        ?>
                    </h4>

                    <p>
                        <?php
                        echo htmlspecialchars(
                            $activity['childName']
                        );
                        ?>
                        •
                        <?php
                        echo date(
                            'M d, Y',
                            strtotime($activity['activityDate'])
                        );
                        ?>
                        •
                        <?php
                        echo date(
                            'g:i A',
                            strtotime($activity['activityTime'])
                        );
                        ?>
                    </p>

                    <?php if ($activity['notes'] !== ''): ?>
                        <p>
                            <?php
                            echo htmlspecialchars($activity['notes']);
                            ?>
                        </p>
                                       <?php endif; ?>
<a href="edit_activity.php?id=<?php
echo $activity['activityID'];
?>">Edit</a>
                    <form
                        action="delete_activity.php"
                        method="post"
                        onsubmit="return confirm('Delete this activity?');"
                    >
                        <input
                            type="hidden"
                            name="activityID"
                            value="<?php
                            echo $activity['activityID'];
                            ?>"
                        >
                        <button type="submit">Delete</button>
                    </form>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
        <h3>Upcoming Reminders</h3>

<?php if (count($reminders) === 0): ?>
    <p>No reminders added yet.</p>
<?php else: ?>
    <?php foreach ($reminders as $reminder): ?>
        <article>
            <h4>
                <?php
                echo htmlspecialchars(
                    $reminder['activityName']
                );
                ?>
            </h4>

            <p>
                <?php
                echo htmlspecialchars(
                    $reminder['childName']
                );
                ?>
                •
                <?php
                echo date(
                    'M d, Y - g:i A',
                    strtotime($reminder['reminderDateTime'])
                );
                ?>
            </p>
        </article>
    <?php endforeach; ?>
<?php endif; ?>
    </main>
</body>
</html>