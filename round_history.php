<?php
// === DEBUGGING ON ===
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require 'config.php';
include 'header.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$myId = $_SESSION['user_id'];

// 1) Find the current (or most recent) round
$stmt = $pdo->query("
    SELECT id, round_end
    FROM rounds
    ORDER BY id DESC
    LIMIT 1
");
$currentRound = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$currentRound) {
    die("No round data available.");
}

// 2) Fetch only events where *you* were the target in that round
$stmt = $pdo->prepare("
    SELECT
      e.occurred_at,
      a.username        AS actor,
      e.action_type,
      e.amount,
      e.details
    FROM round_events e
    JOIN users a ON e.actor_id = a.id
    WHERE e.round_id  = :round_id
      AND e.target_id = :my_id
    ORDER BY e.occurred_at ASC
");
$stmt->execute([
    ':round_id' => $currentRound['id'],
    ':my_id'    => $myId
]);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Your Round Events</title>
  <style>
    body { font-family: sans-serif; padding:1em; }
    table { width:100%; border-collapse: collapse; margin-top:1em; }
    th, td { border:1px solid #ccc; padding:6px 8px; text-align:left; }
    th { background:#f0f0f0; }
    caption { font-weight:bold; margin-bottom:.5em; }
  </style>
</head>
<body>
<div class="container">
    <section>
  <h2>Events Targeting You in Round Ending <?= htmlspecialchars($currentRound['round_end']) ?></h2>

  <?php if (empty($events)): ?>
    <p>No actions have targeted you yet this round.</p>
  <?php else: ?>
    <table>
      <caption>Your Personal Event Log</caption>
      <tr>
        <th>Time</th>
        <th>Who</th>
        <th>Action</th>
        <th>Amount</th>
        <th>Details</th>
      </tr>
      <?php foreach ($events as $e): ?>
      <tr>
        <td><?= htmlspecialchars($e['occurred_at']) ?></td>
        <td><?= htmlspecialchars($e['actor']) ?></td>
        <td><?= ucfirst(htmlspecialchars($e['action_type'])) ?></td>
        <td><?= number_format($e['amount']) ?></td>
        <td><?= htmlspecialchars($e['details']) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>

      </section></div>
<?php include 'footer.php'; ?>
