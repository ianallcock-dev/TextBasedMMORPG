<?php

$totalCashEarned = 0;

// Fetch user data
$stmt = $pdo->prepare("
    SELECT operations, turns, operations_last_collect
    FROM users
    WHERE id = ?
");
$stmt->execute([$user_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$entertainers = $row['operations'] ?? 0;
$currentTurns = $row['turns'] ?? 0;
$lastCollectTurns = $row['operations_last_collect'] ?? 0;

$newTurns = $currentTurns - $lastCollectTurns;

if ($newTurns > 0 && $entertainers > 0) {
    for ($i = 0; $i < $newTurns; $i++) {
        $cashPerEntertainerThisTurn = rand(1, 3);
        $totalCashEarned += $entertainers * $cashPerEntertainerThisTurn;
    }

    // Update user's cash and last collected turn count
    $stmt = $pdo->prepare("
        UPDATE users
        SET items = items + ?, operations_last_collect = ?
        WHERE id = ?
    ");
    $stmt->execute([$totalItemsEarned, $currentTurns, $user_id]);
}

return $totalItemsEarned;
?>
