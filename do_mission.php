<?php
session_start();
require 'config.php';

$user_id = $_SESSION['user_id'];

// Fetch turns
$stmt = $pdo->prepare("SELECT turns, cash FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$cost = 20;

if ($user['turns'] < $cost) {
    die("Not enough turns.");
}

$success = rand(0,1);
if ($success) {
    $reward = rand(1000,3000);
    $stmt = $pdo->prepare("UPDATE users SET cash = cash + ?, turns = turns - ? WHERE id = ?");
    $stmt->execute([$reward, $cost, $user_id]);

    echo "Mission succeeded! You earned $".$reward;
} else {
    $stmt = $pdo->prepare("UPDATE users SET turns = turns - ? WHERE id = ?");
    $stmt->execute([$cost, $user_id]);

    echo "Mission failed. Better luck next time.";
}
?>
<br><a href="dashboard.php">Back</a>
