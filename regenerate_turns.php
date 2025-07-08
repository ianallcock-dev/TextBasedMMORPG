<?php
require 'config.php';
//session_start();

if (!isset($_SESSION['user_id'])) {
    return;
}

$user_id = $_SESSION['user_id'];

// Fetch all the fields we need
$stmt = $pdo->prepare("
    SELECT turns, last_turn_update,
           entertainers, operations,
           items, cash
    FROM users
    WHERE id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    return;
}

$currentTime = time();
$lastUpdate  = $user['last_turn_update'] ?: $currentTime;

// 1) If user already at or above cap, just bump timestamp and exit
if ($user['turns'] >= 2400) {
    $stmt = $pdo->prepare("
        UPDATE users
        SET last_turn_update = ?
        WHERE id = ?
    ");
    $stmt->execute([$currentTime, $user_id]);
    return;
}

// 2) Calculate full minutes elapsed
$minutesPassed = floor(($currentTime - $lastUpdate) / 60);
if ($minutesPassed < 1) {
    return;
}

// 3) Compute new turns (5 per minute), cap at 2400
$newTurns = $minutesPassed * 5;
$newTotalTurns = min($user['turns'] + $newTurns, 2400);

// 4) Compute entertainer cash & operation items
$totalCashEarned  = 0;
$totalItemsEarned = 0;
for ($i = 0; $i < $minutesPassed; $i++) {
    $totalCashEarned  += $user['entertainers'] * rand(5, 15);
    $totalItemsEarned += $user['operations']   * rand(1,  3);
}

// 5) Apply all updates in order
// 5a) Update turns & timestamp
$stmt = $pdo->prepare("
    UPDATE users
    SET turns            = ?,
        last_turn_update = ?
    WHERE id = ?
");
$stmt->execute([$newTotalTurns, $currentTime, $user_id]);

// 5b) Award cash from entertainers
if ($totalCashEarned > 0) {
    $stmt = $pdo->prepare("
        UPDATE users
        SET cash                      = cash + ?,
            entertainers_last_collect = ?
        WHERE id = ?
    ");
    $stmt->execute([$totalCashEarned, $currentTime, $user_id]);
}

// 5c) Award items from operations
if ($totalItemsEarned > 0) {
    $stmt = $pdo->prepare("
        UPDATE users
        SET items                  = items + ?,
            operations_last_collect = ?
        WHERE id = ?
    ");
    $stmt->execute([$totalItemsEarned, $currentTime, $user_id]);
}
