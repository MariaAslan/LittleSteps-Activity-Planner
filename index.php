<?php
require_once __DIR__ . '/session.php';

if (isset($_SESSION['userID'])) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}

exit();
?>