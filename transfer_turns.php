<?php

require 'config.php';

$user_id = $_SESSION['user_id'];

// Fetch user
$stmt = $pdo->prepare("SELECT reserved_turns, turns FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requested = intval($_POST['amount']);

    if ($requested <= 0 || $requested > $user['reserved_turns']) {
        die("Invalid amount.");
    }

    // Transfer
    $stmt = $pdo->prepare("
        UPDATE users
        SET reserved_turns = reserved_turns - ?,
            turns = turns + ?
        WHERE id = ?
    ");
    $stmt->execute([$requested, $requested, $user_id]);

    echo "Transferred $requested reserved turns into active turns.";
}
?>

<form method="POST">
    <p>You have <?= $user['reserved_turns'] ?> reserved turns.</p>
    <input type="number" name="amount" min="1" max="<?= $user['reserved_turns'] ?>">
    <input type="submit" value="Transfer Turns">
</form>

<a href="dashboard.php">Back to Dashboard</a>
