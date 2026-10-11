<?php
session_start();
require_once('database.php');

if (!isset($_SESSION['userID'])) {
    header('Location: login.php');
    exit();
}

$activityID = filter_input(
    INPUT_POST,
    'activityID',
    FILTER_VALIDATE_INT
);

if ($activityID) {
    $query = 'DELETE activities
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
    $statement->closeCursor();
}

header('Location: dashboard.php');
exit();
?>