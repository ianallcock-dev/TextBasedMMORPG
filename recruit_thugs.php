<?php

require 'config.php';
require 'regenerate_turns.php';
include 'header.php';
// Fetch user
$stmt = $pdo->prepare("
    SELECT * FROM users
    WHERE id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$feedback = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $turnsSpent = intval($_POST['turns']);

    if ($turnsSpent <= 0) {
        $feedback = "Please enter a valid number of turns.";
    } elseif ($user['turns'] < $turnsSpent) {
        $feedback = "You don’t have enough turns to spend $turnsSpent.";
    } else {
        // Random number of thugs:
        $minThugs = $turnsSpent * 1;
        $maxThugs = $turnsSpent * 10;

        $thugsGenerated = rand($minThugs, $maxThugs);

        // Update thugs + deduct turns
        $stmt = $pdo->prepare("
            UPDATE users
            SET thugs = thugs + ?, turns = turns - ?
            WHERE id = ?
        ");
        $stmt->execute([$thugsGenerated, $turnsSpent, $user['id']]);

        $feedback = "You spent $turnsSpent turns and recruited $thugsGenerated thugs!";

        // Reload user data
        $stmt = $pdo->prepare("
            SELECT * FROM users
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
<div class="container">
<section>
<h2>Recruit Thugs</h2>
<p>You have <?= $user['turns'] ?> turns remaining.</p>

<form method="POST">
    How many turns do you want to spend to recruit thugs?<br>
    <input type="number" name="turns" id="turnsInput" min="1">
    <br><br>
    <span id="rangeDisplay" style="font-weight: bold;"></span><br><br>
    <input type="submit" value="Recruit">
</form>

<p><?= htmlspecialchars($feedback) ?></p>

<a href="dashboard.php">Return to Dashboard</a>

<script>
document.getElementById('turnsInput').addEventListener('input', function() {
    var turns = parseInt(this.value);
    var rangeDisplay = document.getElementById('rangeDisplay');

    if (isNaN(turns) || turns <= 0) {
        rangeDisplay.textContent = "";
    } else {
        var minThugs = turns * 1;
        var maxThugs = turns * 10;
        rangeDisplay.textContent = `→ You'll recruit between ${minThugs} and ${maxThugs} thugs.`;
    }
});
</script>
</section></div>
<?php include 'footer.php'; ?>