<?php
$dsn = 'mysql:host=localhost;dbname=littlesteps_db;charset=utf8mb4';
$username = 'root';
$password = '';

try {
    $db = new PDO($dsn, $username, $password);

    $db->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );
} catch (PDOException $error) {
    die('Database connection failed: ' . $error->getMessage());
}
?>