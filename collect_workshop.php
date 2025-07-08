<?php
session_start();
require 'config.php';
require 'regenerate_turns.php';

$user_id = $_SESSION['user_id'];

// Fetch user's workshops
$stmt = $pdo->prepare("
    SELECT * FROM workshops
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$workshops = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalThugs = 0;
$totalEntertainers = 0;
$currentTime = time();

foreach ($workshops as $workshop) {
    $elapsedHours = floor(($currentTime - $workshop['last_collect']) / 3600);
    if ($elapsedHours > 0) {
        $rate = ($workshop['level'] == 1) ? 5 :
                (($workshop['level'] == 2) ? 10 : 20);

        $amountProduced = $elapsedHours * $rate;

        if ($workshop['type'] === 'thugs') {
            $totalThugs += $amountProduced;
        } else {
            $totalEntertainers += $amountProduced;
        }

        // Update last_collect
        $stmt2 = $pdo->prepare("
            UPDATE workshops
            SET last_collect = ?
            WHERE id = ?
        ");
        $stmt2->execute([$currentTime, $workshop['id']]);
    }
}

// Update user resources
$stmt = $pdo->prepare("
    UPDATE users
    SET thugs = thugs + ?, entertainers = entertainers + ?
    WHERE id = ?
");
$stmt->execute([$totalThugs, $totalEntertainers, $user_id]);

echo "Collected $totalThugs thugs and $totalEntertainers entertainers.<br>";
echo "<a href='dashboard.php'>Back to Dashboard</a>";
?>
