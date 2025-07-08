<?php

$totalCashEarned = 0;

// Fetch user data
$stmt = $pdo->prepare("
    SELECT entertainers, turns, entertainers_last_collect
    FROM users
    WHERE id = ?
");
$stmt->execute([$user_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$entertainers = $row['entertainers'] ?? 0;
$currentTurns = $row['turns'] ?? 0;
$lastCollectTurns = $row['entertainers_last_collect'] ?? 0;

$newTurns = $currentTurns - $lastCollectTurns;

if ($newTurns > 0 && $entertainers > 0) {
    for ($i = 0; $i < $newTurns; $i++) {
        $cashPerEntertainerThisTurn = rand(5, 15);
        $totalCashEarned += $entertainers * $cashPerEntertainerThisTurn;
    }

    // Update user's cash and last collected turn count
    $stmt = $pdo->prepare("
        UPDATE users
        SET cash = cash + ?, entertainers_last_collect = ?
        WHERE id = ?
    ");
    $stmt->execute([$totalCashEarned, $currentTurns, $user_id]);
}

return $totalCashEarned;
?>
