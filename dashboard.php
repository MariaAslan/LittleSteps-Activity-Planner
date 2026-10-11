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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - LittleSteps</title>
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
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</body>
</html>