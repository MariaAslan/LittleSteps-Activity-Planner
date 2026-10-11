<?php
session_start();
require_once('database.php');

if (!isset($_SESSION['userID'])) {
    header('Location: login.php');
    exit();
}

$childID = filter_input(
    INPUT_POST,
    'childID',
    FILTER_VALIDATE_INT
);

if ($childID) {
    $query = 'DELETE FROM children
              WHERE childID = :childID
                AND userID = :userID';

    $statement = $db->prepare($query);
    $statement->bindValue(
        ':childID',
        $childID,
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

header('Location: manage_children.php');
exit();
?>