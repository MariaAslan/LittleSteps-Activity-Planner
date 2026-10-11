<?php
session_start();
require_once('database.php');

if (!isset($_SESSION['userID'])) {
    header('Location: login.php');
    exit();
}

/* Get this user’s children */
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

    $validChildIDs = array_column($children, 'childID');

    if (
        $activityName === '' ||
        !$childID ||
        !in_array($childID, $validChildIDs) ||
        $activityDate === '' ||
        $activityTime === ''
    ) {
        $error = 'Please complete all required fields.';
    } else {
        $query = 'INSERT INTO activities
                    (
                        childID,
                        activityName,
                        activityDate,
                        activityTime,
                        notes
                    )
                  VALUES
                    (
                        :childID,
                        :activityName,
                        :activityDate,
                        :activityTime,
                        :notes
                    )';

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
    <title>Add Activity - LittleSteps</title>
    <link rel="stylesheet" href="CSS/style.css">
</head>
<body>
    <main>
        <h1>LittleSteps</h1>
        <h2>Add New Activity</h2>

        <?php if ($error !== ''): ?>
            <p><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <?php if (count($children) === 0): ?>
            <p>Please add a child before adding an activity.</p>
            <a href="add_child.php">Add Child</a>
        <?php else: ?>
            <form action="add_activity.php" method="post">
                <label for="activityName">Activity Name:</label>
                <input
                    type="text"
                    id="activityName"
                    name="activityName"
                    required
                >

                <label for="childID">Child:</label>
                <select id="childID" name="childID" required>
                    <option value="">Select a child</option>

                    <?php foreach ($children as $child): ?>
                        <option value="<?php echo $child['childID']; ?>">
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
                    required
                >

                <label for="activityTime">Time:</label>
                <input
                    type="time"
                    id="activityTime"
                    name="activityTime"
                    required
                >

                <label for="notes">Notes:</label>
                <textarea id="notes" name="notes"></textarea>

                <button type="submit">Add Activity</button>
                <a href="dashboard.php">Cancel</a>
            </form>
        <?php endif; ?>
    </main>
</body>
</html>