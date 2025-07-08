<?php
// dissolve_gang.php

// 1) Show errors while you’re testing
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

session_start();
require 'config.php';

// 2) Must be logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$me   = $_SESSION['user_id'];
$gid  = intval($_GET['id'] ?? 0);

if ($gid < 1) {
    die("No gang specified.");
}

// 3) Verify that I’m the leader of that gang
$stmt = $pdo->prepare("
    SELECT leader_id
    FROM gangs
    WHERE id = ?
");
$stmt->execute([$gid]);
$gang = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$gang) {
    die("Gang not found.");
}
if ($gang['leader_id'] != $me) {
    die("Only the leader can dissolve this gang.");
}

// 4) Delete the gang (cascade cleans up members, invites, posts)
$del = $pdo->prepare("DELETE FROM gangs WHERE id = ?");
$del->execute([$gid]);

// 5) Redirect back with a flash message (or just to dashboard)
header("Location: dashboard.php?msg=" . urlencode("Gang dissolved."));
exit;
?>