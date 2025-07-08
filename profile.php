<?php
require 'config.php';
include 'header.php';

if (empty($_GET['user_id'])) {
    die("No user specified.");
}
$profile_id = intval($_GET['user_id']);

// Fetch profile user
$stmt = $pdo->prepare("
    SELECT id, username, display_name
    FROM users
    WHERE id = ?
");
$stmt->execute([$profile_id]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$profile) {
    die("User not found.");
}
$profile_name = $profile['display_name'] ?: $profile['username'];

// Fetch profile’s gang (if any)
$stmt = $pdo->prepare("
    SELECT g.id, g.name
    FROM gang_members gm
    JOIN gangs g ON g.id = gm.gang_id
    WHERE gm.user_id = ?
");
$stmt->execute([$profile_id]);
$profileGang = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch my role in my gang
$me = $_SESSION['user_id'] ?? null;
$myRole = null;
$myGangId = null;
if ($me) {
    $stmt = $pdo->prepare("
        SELECT gang_id, role
        FROM gang_members
        WHERE user_id = ?
    ");
    $stmt->execute([$me]);
    $tmp = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($tmp) {
        $myGangId = $tmp['gang_id'];
        $myRole   = $tmp['role'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Profile of <?= htmlspecialchars($profile_name) ?></title>
  <style>
    body { font-family: sans-serif; padding:1em; max-width:600px; margin:auto; }
    .section { margin-bottom: 1em; }
  </style>
</head>
<body>
  <h2>Profile: <?= htmlspecialchars($profile_name) ?></h2>

  <div class="section">
    <h3>Basic Info</h3>
    <ul>
      <li><strong>Username:</strong> <?= htmlspecialchars($profile['username']) ?></li>
      <?php if ($profileGang): ?>
        <li>
          <strong>Gang:</strong>
          <a href="view_gang.php?id=<?= $profileGang['id'] ?>">
            <?= htmlspecialchars($profileGang['name']) ?>
          </a>
        </li>
      <?php else: ?>
        <li><strong>Gang:</strong> None</li>
      <?php endif; ?>
    </ul>
  </div>

  <?php if ($me && $myRole && $myGangId && $myGangId !== ($profileGang['id'] ?? 0)
            && in_array($myRole, ['leader','deputy'])): ?>
    <div class="section">
      <h3>Invite to Your Gang</h3>
      <form method="POST" action="invite_member.php">
        <input type="hidden" name="gang_id" value="<?= $myGangId ?>">
        <input type="hidden" name="username" 
               value="<?= htmlspecialchars($profile['username']) ?>">
        <button>Invite <?= htmlspecialchars($profile_name) ?> to <?= htmlspecialchars($tmp['name'] ?? 'Your Gang') ?></button>
      </form>
    </div>
  <?php endif; ?>

  <p><a href="dashboard.php">← Back to Dashboard</a></p>
  <?php include 'footer.php'; ?>
