<?php
session_start();
require_once('database.php');

if (!isset($_SESSION['userID'])) {
    header('Location: login.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $childName = trim($_POST['childName'] ?? '');
    $birthDate = $_POST['birthDate'] ?? '';

    if ($childName === '') {
        $error = 'Please enter the child’s name.';
    } else {
        $query = 'INSERT INTO children
                    (userID, childName, birthDate)
                  VALUES
                    (:userID, :childName, :birthDate)';

        $statement = $db->prepare($query);
        $statement->bindValue(
            ':userID',
            $_SESSION['userID'],
            PDO::PARAM_INT
        );
        $statement->bindValue(':childName', $childName);

        if ($birthDate === '') {
            $statement->bindValue(
                ':birthDate',
                null,
                PDO::PARAM_NULL
            );
        } else {
            $statement->bindValue(':birthDate', $birthDate);
        }

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
    <title>Add Child - LittleSteps</title>
    <link rel="stylesheet" href="CSS/style.css">
</head>
<body>
    <main>
        <h1>LittleSteps</h1>
        <h2>Add Child</h2>

        <?php if ($error !== ''): ?>
            <p><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form action="add_child.php" method="post">
            <label for="childName">Child’s Name:</label>
            <input
                type="text"
                id="childName"
                name="childName"
                required
            >

            <label for="birthDate">Birth Date:</label>
            <input
                type="date"
                id="birthDate"
                name="birthDate"
            >

            <button type="submit">Add Child</button>
            <a href="dashboard.php">Cancel</a>
        </form>
    </main>
</body>
</html>