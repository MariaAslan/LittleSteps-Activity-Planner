<?php
require_once __DIR__ . '/session.php';
require_once('database.php');

if (!isset($_SESSION['userID'])) {
    header('Location: login.php');
    exit();
}

/* Get the user's activities */
$query = 'SELECT activities.activityID,
                 activities.activityName,
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

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $activityID = filter_input(
        INPUT_POST,
        'activityID',
        FILTER_VALIDATE_INT
    );

    $reminderDateTime = $_POST['reminderDateTime'] ?? '';

    if (!$activityID || $reminderDateTime === '') {
        $error = 'Please complete all fields.';
    } else {
        /* Confirm that this activity belongs to the user */
        $query = 'SELECT COUNT(*)
                  FROM activities
                  INNER JOIN children
                      ON activities.childID = children.childID
                  WHERE activities.activityID = :activityID
                    AND children.userID = :userID';

        $statement = $db->prepare($query);
        $statement->bindValue(
            ':activityID',
            $activityID,
            PDO::PARAM_INT
        );
        $statement->bindValue(
            ':userID',
            $_SESSION['userID'],
            PDO::PARAM_INT
        );
        $statement->execute();
        $activityExists = $statement->fetchColumn();
        $statement->closeCursor();

        if (!$activityExists) {
            $error = 'Invalid activity selected.';
        } else {
            $query = 'INSERT INTO reminders
                      (activityID, reminderDateTime, isCompleted)
                      VALUES
                      (:activityID, :reminderDateTime, 0)';

            $statement = $db->prepare($query);
            $statement->bindValue(
                ':activityID',
                $activityID,
                PDO::PARAM_INT
            );
            $statement->bindValue(
                ':reminderDateTime',
                $reminderDateTime
            );
            $statement->execute();
            $statement->closeCursor();

            header('Location: dashboard.php');
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Reminder - LittleSteps</title>
    <link rel="stylesheet" href="CSS/style.css">
</head>
<body>
    <main>
        <h1>Add Reminder</h1>

        <?php if ($error !== ''): ?>
            <p>
                <?php echo htmlspecialchars($error); ?>
            </p>
        <?php endif; ?>

        <?php if (count($activities) === 0): ?>
            <p>You must add an activity first.</p>
            <a href="add_activity.php">Add Activity</a>
        <?php else: ?>
            <form action="add_reminder.php" method="post">
                <label for="activityID">Activity:</label>

                <select
                    id="activityID"
                    name="activityID"
                    required
                >
                    <option value="">Select an activity</option>

                    <?php foreach ($activities as $activity): ?>
                        <option
                            value="<?php
                            echo $activity['activityID'];
                            ?>"
                        >
                            <?php
                            echo htmlspecialchars(
                                $activity['activityName']
                            );
                            ?>
                            -
                            <?php
                            echo htmlspecialchars(
                                $activity['childName']
                            );
                            ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="reminderDateTime">
                    Reminder Date and Time:
                </label>

                <input
                    type="datetime-local"
                    id="reminderDateTime"
                    name="reminderDateTime"
                    required
                >

                <button type="submit">Save Reminder</button>
                <a href="dashboard.php">Cancel</a>
            </form>
        <?php endif; ?>
    </main>
</body>
</html>