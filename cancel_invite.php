<?php
// cancel_invite.php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}
$me = $_SESSION['user_id'];
$inviteId = intval($_GET['id'] ?? 0);
if (!$inviteId) {
  die("No invite specified.");
}

// Fetch the invite + leader_id
$stmt = $pdo->prepare("
  SELECT gi.gang_id, g.leader_id, gi.status
  FROM gang_invites gi
  JOIN gangs g ON g.id = gi.gang_id
  WHERE gi.id = ?
");
$stmt->execute([$inviteId]);
$inv = $stmt->fetch();

if (!$inv || $inv['status'] !== 'pending') {
  die("Invite not found or not pending.");
}
if ($inv['leader_id'] != $me) {
  die("Only the gang leader may cancel invites.");
}

// Now cancel _only_ the invite, not the whole gang:
$pdo->prepare("
  UPDATE gang_invites
  SET status = 'declined'
  WHERE id = ?
")->execute([$inviteId]);

// Redirect back to the gang page:
header("Location: view_gang.php?id=" . $inv['gang_id']);
exit;
