<?php

require 'config.php';
include 'header.php';
// (Optional) require login
 if (!isset($_SESSION['user_id'])) {
   header('Location: login.php');
   exit;
}

// 1) Top 10 by Cash
$topCash = $pdo->query("
    SELECT username, cash
    FROM users
    ORDER BY cash DESC
    LIMIT 10
");

// 2) Top 10 by Thugs Killed
$topKills = $pdo->query("
    SELECT username, thugs_killed_this_round AS kills
    FROM users
    ORDER BY kills DESC
    LIMIT 10
");

// 3) Top 10 by Entertainer Lures
$topLures = $pdo->query("
    SELECT username, entertainers_lured_this_round AS lures
    FROM users
    ORDER BY lures DESC
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Current Leaderboards</title>
  <style>
    body { font-family: sans-serif; }
    table { float: left; margin-right: 2em; border-collapse: collapse; }
    th, td { border: 1px solid #666; padding: 4px 8px; }
    caption { font-weight: bold; margin-bottom: .5em; }
    .container { overflow: auto; }
  </style>
</head>
<body>
<div class="container">
<section>
  <h1>Current Leaderboard Standings</h1>
  <div class="container">

    <!-- Cash Leaderboard -->
    <table>
      <caption>Top 10 Cash</caption>
      <tr><th>#</th><th>User</th><th>Cash</th></tr>
      <?php $rank = 1; while ($row = $topCash->fetch(PDO::FETCH_ASSOC)): ?>
      <tr>
        <td><?= $rank ?></td>
        <td><?= htmlspecialchars($row['username']) ?></td>
        <td>$<?= number_format($row['cash']) ?></td>
      </tr>
      <?php $rank++; endwhile; ?>
    </table>

    <!-- Thugs Killed Leaderboard -->
    <table>
      <caption>Top 10 Thugs Killed</caption>
      <tr><th>#</th><th>User</th><th>Kills</th></tr>
      <?php $rank = 1; while ($row = $topKills->fetch(PDO::FETCH_ASSOC)): ?>
      <tr>
        <td><?= $rank ?></td>
        <td><?= htmlspecialchars($row['username']) ?></td>
        <td><?= number_format($row['kills']) ?></td>
      </tr>
      <?php $rank++; endwhile; ?>
    </table>

    <!-- Entertainer Lures Leaderboard -->
    <table>
      <caption>Top 10 Entertainer Lurers</caption>
      <tr><th>#</th><th>User</th><th>Lures</th></tr>
      <?php $rank = 1; while ($row = $topLures->fetch(PDO::FETCH_ASSOC)): ?>
      <tr>
        <td><?= $rank ?></td>
        <td><?= htmlspecialchars($row['username']) ?></td>
        <td><?= number_format($row['lures']) ?></td>
      </tr>
      <?php $rank++; endwhile; ?>
    </table>

  </div>
  <p style="clear: both;"><a href="dashboard.php">← Back to Dashboard</a></p>
      </section></div>
  <?php include 'footer.php'; ?>
