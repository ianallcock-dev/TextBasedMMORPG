<?php
date_default_timezone_set('UTC');
$host = 'localhost';
$db   = 'YOURDATABASE';
$user = 'YOURUSER';
$pass = 'YOURPASSWORD';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET time_zone = '+00:00'");
} catch (PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}
?>
