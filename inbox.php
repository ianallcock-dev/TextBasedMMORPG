<?php
require 'config.php';
include 'header.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$me = $_SESSION['user_id'];

// Fetch inbox messages
$stmt = $pdo->prepare("
    SELECT m.id, m.subject, m.is_read, m.sent_at, u.username AS sender
    FROM messages m
    JOIN users u ON u.id = m.sender_id
    WHERE m.receiver_id = ?
    ORDER BY m.sent_at DESC
");
$stmt->execute([$me]);
$inbox = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch pending gang invites
$stmt = $pdo->prepare("
    SELECT gi.id, g.name AS gang_name, u.username AS invited_by
    FROM gang_invites gi
    JOIN gangs g ON g.id = gi.gang_id
    JOIN users u ON u.id = gi.invited_by
    WHERE gi.user_id = ? AND gi.status = 'pending'
    ORDER BY gi.created_at DESC
");
$stmt->execute([$me]);
$invites = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Your Inbox & Invites</title>
  <style>
    table { width:100%; border-collapse: collapse; margin-top:1em; }
    th, td { border:1px solid #ccc; padding:8px; text-align:left; }
    th { background: #f0f0f0; }
    .unread { font-weight: bold; }
    ul { list-style:none; padding-left:0; }
  </style>
</head>
<body>
<div class="container">
<section>
  <h2>Your Inbox</h2>
  <?php if (empty($inbox)): ?>
    <p>No messages.</p>
  <?php else: ?>
    <table>
      <tr><th>Status</th><th>From</th><th>Subject</th><th>When</th></tr>
      <?php foreach ($inbox as $msg): ?>
        <tr class="<?= $msg['is_read'] ? '' : 'unread' ?>">
          <td><?= $msg['is_read'] ? 'Read' : 'New' ?></td>
          <td><?= htmlspecialchars($msg['sender']) ?></td>
          <td>
            <a href="view_message.php?id=<?= $msg['id'] ?>">
              <?= htmlspecialchars($msg['subject']) ?>
            </a>
          </td>
          <td><?= htmlspecialchars($msg['sent_at']) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>

  <h2>Gang Invites</h2>
  <?php if (empty($invites)): ?>
    <p>No pending invites.</p>
  <?php else: ?>
    <ul>
      <?php foreach ($invites as $inv): ?>
        <li>
          You were invited to join <strong><?= htmlspecialchars($inv['gang_name']) ?></strong>
          by <?= htmlspecialchars($inv['invited_by']) ?>.
          [<a href="accept_invite.php?id=<?= $inv['id'] ?>">Accept</a>]
          [<a href="decline_invite.php?id=<?= $inv['id'] ?>">Decline</a>]
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <p>
    <a href="compose_message.php">Compose New Message</a> |
  </p>
      </section></div>
<?php include 'footer.php'; ?>
