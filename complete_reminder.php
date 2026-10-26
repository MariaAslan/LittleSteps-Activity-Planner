<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/database.php';

if (!isset($_SESSION['userID'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit();
}

$token = $_POST['csrfToken'] ?? '';

if (
    !is_string($token) ||
    !isset($_SESSION['csrfToken']) ||
    !hash_equals($_SESSION['csrfToken'], $token)
) {
    http_response_code(403);
    exit('Invalid request. Return to the dashboard and try again.');
}

$reminderID = filter_input(
    INPUT_POST,
    'reminderID',
    FILTER_VALIDATE_INT
);

if ($reminderID && $reminderID > 0) {
    $query = 'UPDATE reminders
              INNER JOIN activities
                  ON reminders.activityID = activities.activityID
              INNER JOIN children
                  ON activities.childID = children.childID
              SET reminders.isCompleted = 1
              WHERE reminders.reminderID = :reminderID
                AND children.userID = :userID
                AND reminders.isCompleted = 0';

    $statement = $db->prepare($query);
    $statement->bindValue(
        ':reminderID',
        $reminderID,
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