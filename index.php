<?php
session_start();

if (isset($_SESSION['userID'])) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}

exit();
?>