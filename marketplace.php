<?php

require 'config.php';
include 'header.php';
$user_id = $_SESSION['user_id'];

// Fetch current balances
$stmt = $pdo->prepare("SELECT cash, items FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$feedback = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['buy_qty'])) {
        $buyQty = max(0, intval($_POST['buy_qty']));
        $cost   = $buyQty * 50;

        if ($buyQty <= 0) {
            $feedback = "Enter a positive number to buy.";
        } elseif ($cost > $user['cash']) {
            $feedback = "You need \$$cost but only have \${$user['cash']}.";
        } else {
            // Deduct cash, add items
            $stmt = $pdo->prepare("
                UPDATE users
                SET cash = cash - ?, items = items + ?
                WHERE id = ?
            ");
            $stmt->execute([$cost, $buyQty, $user_id]);
            $feedback = "Bought {$buyQty} item(s) for \${$cost}.";
        }

    } elseif (isset($_POST['sell_qty'])) {
        $sellQty = max(0, intval($_POST['sell_qty']));
        $gain    = $sellQty * 30;

        if ($sellQty <= 0) {
            $feedback = "Enter a positive number to sell.";
        } elseif ($sellQty > $user['items']) {
            $feedback = "You have only {$user['items']} item(s).";
        } else {
            // Deduct items, add cash
            $stmt = $pdo->prepare("
                UPDATE users
                SET items = items - ?, cash = cash + ?
                WHERE id = ?
            ");
            $stmt->execute([$sellQty, $gain, $user_id]);
            $feedback = "Sold {$sellQty} item(s) for \${$gain}.";
        }
    }

    // Refresh balances
    $stmt = $pdo->prepare("SELECT cash, items FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html>
<head><title>Market</title></head>
<body>
<div class="container">
<section>
<h2>Market</h2>
<p><strong>Cash:</strong> $<?= number_format($user['cash']) ?></p>
<p><strong>Luxury Goods:</strong> <?= number_format($user['items']) ?></p>
<?php if ($feedback): ?>
  <p style="color: green;"><?= htmlspecialchars($feedback) ?></p>
<?php endif; ?>

<h3>Buy Items</h3>
<p>Price per item: $<?= 50 ?></p>
<form method="POST">
  <input type="number" name="buy_qty" min="1" placeholder="Qty to buy">
  <button type="submit">Buy</button>
</form>

<h3>Sell Items</h3>
<p>Sell price per item: $<?= 30 ?></p>
<form method="POST">
  <input type="number" name="sell_qty" min="1" placeholder="Qty to sell">
  <button type="submit">Sell</button>
</form>

</section></div>
<?php include 'footer.php'; ?>