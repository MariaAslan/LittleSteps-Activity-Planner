<?php
session_start();
require_once('database.php');

if (!isset($_SESSION['userID'])) {
    header('Location: login.php');
    exit();
}

$query = 'SELECT childID, childName, birthDate
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Children - LittleSteps</title>
    <link rel="stylesheet" href="CSS/style.css">
</head>
<body>
    <main>
        <h1>Manage Children</h1>

        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="add_child.php">+ Add Child</a>
        </nav>

        <?php if (count($children) === 0): ?>
            <p>No children added yet.</p>
        <?php else: ?>
            <?php foreach ($children as $child): ?>
                <article>
                    <h3>
                        <?php
                        echo htmlspecialchars($child['childName']);
                        ?>
                    </h3>

                    <?php if (!empty($child['birthDate'])): ?>
                        <p>
                            Birth Date:
                            <?php
                            echo date(
                                'M d, Y',
                                strtotime($child['birthDate'])
                            );
                            ?>
                        </p>
                    <?php endif; ?>

                    <form
                        action="delete_child.php"
                        method="post"
                        onsubmit="return confirm(
                            'Delete this child and all activities?'
                        );"
                    >
                        <input
                            type="hidden"
                            name="childID"
                            value="<?php echo $child['childID']; ?>"
                        >
                        <button type="submit">Delete</button>
                    </form>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</body>
</html>