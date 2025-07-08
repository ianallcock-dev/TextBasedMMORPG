<?php
// ─── show errors for debugging (optional, remove in production) ─────────
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'config.php';

// 1) Ensure logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// 2) Fetch the current round
$stmt  = $pdo->query("SELECT * FROM rounds ORDER BY id DESC LIMIT 1");
$round = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$round || empty($round['round_end'])) {
    die("Error: no active round data.");
}

// 3) Possibly reset round
$now = new DateTime('now', new DateTimeZone('UTC'));
$end = new DateTime($round['round_end'], new DateTimeZone('UTC'));
if ($now > $end) {
    require 'reset_round.php';
    header('Location: dashboard.php');
    exit;
}

// 4) Countdown & server time
$diff       = $now->diff($end);
$timeLeft   = sprintf('%dd %02d:%02d:%02d',$diff->days,$diff->h,$diff->i,$diff->s);
$serverTime = $now->format('Y-m-d H:i:s');

// 5) Regenerate turns & collect income/items
require 'regenerate_turns.php';

// 6) Re-fetch updated user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    die("Error: user not found.");
}

// 7) Count unread messages
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM messages
    WHERE receiver_id = ? AND is_read = 0
");
$stmt->execute([$user_id]);
$newMessages = (int)$stmt->fetchColumn();

// 8) Count pending gang invites
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM gang_invites
    WHERE user_id = ? AND status = 'pending'
");
$stmt->execute([$user_id]);
$pendingInvites = (int)$stmt->fetchColumn();

// 9) Fetch my gang membership
$stmt = $pdo->prepare("
    SELECT g.id, g.name, gm.role
    FROM gang_members gm
    JOIN gangs g ON g.id = gm.gang_id
    WHERE gm.user_id = ?
");
$stmt->execute([$user_id]);
$myGang = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($page_title ?? 'My Site'); ?></title>

  <!-- Steampunk‐Modern stylesheet -->
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <header>
    <div class="header-art">
  <h1 class="header-art__title">Steam &amp; Steel</h1>
</div>


    <button class="nav-toggle" aria-label="Toggle menu">☰</button>

    <nav>
      

  <a href="dashboard.php">Dashboard</a>
       <a href="missions.php">Missions</a>
       <a href="recruit_thugs.php">Recruit Thugs</a>
       <a href="recruit_entertainers.php">Recruit Entertainers</a>
       <a href="recruit_operations.php">Invest Ops</a>
       <a href="attack.php">Attack</a>
       <a href="current_leaderboard.php">Leaderboard</a>
       <a href="inbox.php">Inbox</a>
       <a href="account.php">Account</a>
      
  </nav>
  </header>

  