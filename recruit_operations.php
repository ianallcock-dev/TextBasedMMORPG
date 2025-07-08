<?php

require 'config.php';
require 'regenerate_turns.php';
include 'header.php';
$user_id = $_SESSION['user_id'];
// Fetch only the columns we need
$stmt = $pdo->prepare("
    SELECT turns, operations
    FROM users
    WHERE id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$feedback = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantity = intval($_POST['quantity']);
    $turnCostPerOperation = 50;
    $totalCost = $quantity * $turnCostPerOperation;

    if ($quantity <= 0) {
        $feedback = "Please enter a valid number of Operations to build.";
    } elseif ($user['turns'] < $totalCost) {
        $feedback = "You don’t have enough turns to build $quantity Operations.";
    } else {
        // ✅ Update operations AND subtract turns in one query:
        $stmt2 = $pdo->prepare("
           UPDATE users
           SET operations = operations + ?, 
               turns      = turns - ?
           WHERE id = ?
        ");
        $stmt2->execute([
            $quantity,
            $totalCost,
            $user_id
        ]);

        $feedback = "You successfully built $quantity Operations!";

        // Update our local $user so the page reflects the new values immediately
        $user['turns']      -= $totalCost;
        $user['operations'] += $quantity;
    }
}
?>
<div class="container">
<section>
<h2>Build Operations</h2>
<p>You have <?= htmlspecialchars($user['turns']) ?> turns remaining.</p>
<p>You currently own <?= htmlspecialchars($user['operations']) ?> Operations.</p>

<form method="POST">
    How many Operations would you like to build?<br>
    <input type="number" name="quantity" min="1" value="1">
    <input type="submit" value="Build">
</form>

<?php if ($feedback): ?>
    <p style="color: green;"><?= htmlspecialchars($feedback) ?></p>
<?php endif; ?>
</section></div>
<?php include 'footer.php'; ?>
