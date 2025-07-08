<?php
// === Show all errors for debugging ===
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'config.php';
include 'header.php';
// 1) Find the most recent round_end_date
$stmt = $pdo->query("
    SELECT MAX(round_end_date) 
    FROM leaderboard_snapshots
");
$endedAt = $stmt->fetchColumn();

if (!$endedAt) {
    // No rounds have been run yet
    echo "<p>No rounds completed yet. Check back once a round finishes!</p>";
    exit;
}

// 2) Prepare & run each leaderboard query

// Top 10 by Cash
$topCash = $pdo->prepare("
    SELECT u.username, ls.cash
    FROM leaderboard_snapshots ls
    JOIN users u ON u.id = ls.user_id
    WHERE ls.round_end_date = ?
    ORDER BY ls.cash DESC
    LIMIT 10
");
$topCash->execute([$endedAt]);

// Top 10 by Thugs Killed
$topKills = $pdo->prepare("
    SELECT u.username, ls.thugs_killed
    FROM leaderboard_snapshots ls
    JOIN users u ON u.id = ls.user_id
    WHERE ls.round_end_date = ?
    ORDER BY ls.thugs_killed DESC
    LIMIT 10
");
$topKills->execute([$endedAt]);

// Top 10 by Entertainer Lures
$topLures = $pdo->prepare("
    SELECT u.username, ls.entertainers_lured
    FROM leaderboard_snapshots ls
    JOIN users u ON u.id = ls.user_id
    WHERE ls.round_end_date = ?
    ORDER BY ls.entertainers_lured DESC
    LIMIT 10
");
$topLures->execute([$endedAt]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Leaderboards — Round Ending <?= htmlspecialchars($endedAt) ?></title>
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
  <h1>Leaderboards for Round Ending <?= htmlspecialchars($endedAt) ?></h1>
  <div class="container">

    <!-- Top Cash -->
    <table>
      <caption>Top Cash</caption>
      <tr><th>#</th><th>User</th><th>Cash</th></tr>
      <?php $rank = 1; while ($row = $topCash->fetch(PDO::FETCH_ASSOC)): ?>
      <tr>
        <td><?= $rank ?></td>
        <td><?= htmlspecialchars($row['username']) ?></td>
        <td>$<?= number_format($row['cash']) ?></td>
      </tr>
      <?php $rank++; endwhile; ?>
    </table>

    <!-- Top Thugs Killed -->
    <table>
      <caption>Top Thugs Killed</caption>
      <tr><th>#</th><th>User</th><th>Kills</th></tr>
      <?php $rank = 1; while ($row = $topKills->fetch(PDO::FETCH_ASSOC)): ?>
      <tr>
        <td><?= $rank ?></td>
        <td><?= htmlspecialchars($row['username']) ?></td>
        <td><?= number_format($row['thugs_killed']) ?></td>
      </tr>
      <?php $rank++; endwhile; ?>
    </table>

    <!-- Top Entertainer Lures -->
    <table>
      <caption>Top Entertainer Lures</caption>
      <tr><th>#</th><th>User</th><th>Lures</th></tr>
      <?php $rank = 1; while ($row = $topLures->fetch(PDO::FETCH_ASSOC)): ?>
      <tr>
        <td><?= $rank ?></td>
        <td><?= htmlspecialchars($row['username']) ?></td>
        <td><?= number_format($row['entertainers_lured']) ?></td>
      </tr>
      <?php $rank++; endwhile; ?>
    </table>

  </div>

      </section></div>

  <?php include 'footer.php'; ?>