<?php
session_start();
require_once('database.php');

if (!isset($_SESSION['userID'])) {
    header('Location: login.php');
    exit();
}

$activityID = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$activityID) {
    header('Location: dashboard.php');
    exit();
}

/* Get the user's children */
$query = 'SELECT childID, childName
          FROM children
          WHERE userID = :userID
          ORDER BY childName';

$statement = $db->prepare($query);
$statement->bindValue(
    ':userID',
    $_SESSION['userID'],
    PDO::PARAM_INT
);
$statement->execute();
$children = $statement->fetchAll(PDO::FETCH_ASSOC);
$statement->closeCursor();

/* Get the selected activity */
$query = 'SELECT activities.activityID,
                 activities.childID,
                 activities.activityName,
                 activities.activityDate,
                 activities.activityTime,
                 activities.notes
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
$activity = $statement->fetch(PDO::FETCH_ASSOC);
$statement->closeCursor();

if (!$activity) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $activityName = trim($_POST['activityName'] ?? '');
    $childID = filter_input(
        INPUT_POST,
        'childID',
        FILTER_VALIDATE_INT
    );
    $activityDate = $_POST['activityDate'] ?? '';
    $activityTime = $_POST['activityTime'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    if (
        $activityName === '' ||
        !$childID ||
        $activityDate === '' ||
        $activityTime === ''
    ) {
        $error = 'Please complete all required fields.';
    } else {
        $query = 'UPDATE activities
                  SET childID = :childID,
                      activityName = :activityName,
                      activityDate = :activityDate,
                      activityTime = :activityTime,
                      notes = :notes
                  WHERE activityID = :activityID';

        $statement = $db->prepare($query);
        $statement->bindValue(
            ':childID',
            $childID,
            PDO::PARAM_INT
        );
        $statement->bindValue(':activityName', $activityName);
        $statement->bindValue(':activityDate', $activityDate);
        $statement->bindValue(':activityTime', $activityTime);
        $statement->bindValue(':notes', $notes);
        $statement->bindValue(
            ':activityID',
            $activityID,
            PDO::PARAM_INT
        );
        $statement->execute();
        $statement->closeCursor();

        header('Location: dashboard.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Activity - LittleSteps</title>
</head>
<body>
    <main>
        <h1>Edit Activity</h1>

        <?php if ($error !== ''): ?>
            <p>
                <?php echo htmlspecialchars($error); ?>
            </p>
        <?php endif; ?>

        <form
            action="edit_activity.php?id=<?php echo $activityID; ?>"
            method="post"
        >
            <label for="activityName">Activity Name:</label>
            <input
                type="text"
                id="activityName"
                name="activityName"
                value="<?php
                echo htmlspecialchars($activity['activityName']);
                ?>"
                required
            >

            <label for="childID">Child:</label>
            <select id="childID" name="childID" required>
                <?php foreach ($children as $child): ?>
                    <option
                        value="<?php echo $child['childID']; ?>"
                        <?php
                        if ($activity['childID'] == $child['childID']) {
                            echo 'selected';
                        }
                        ?>
                    >
                        <?php
                        echo htmlspecialchars($child['childName']);
                        ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="activityDate">Date:</label>
            <input
                type="date"
                id="activityDate"
                name="activityDate"
                value="<?php
                echo htmlspecialchars($activity['activityDate']);
                ?>"
                required
            >

            <label for="activityTime">Time:</label>
            <input
                type="time"
                id="activityTime"
                name="activityTime"
                value="<?php
                echo htmlspecialchars(
                    substr($activity['activityTime'], 0, 5)
                );
                ?>"
                required
            >

            <label for="notes">Notes:</label>
            <textarea
                id="notes"
                name="notes"
            ><?php echo htmlspecialchars($activity['notes']); ?></textarea>

            <button type="submit">Update Activity</button>
            <a href="dashboard.php">Cancel</a>
        </form>
    </main>
</body>
</html>