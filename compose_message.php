<?php
// compose_message.php

require 'config.php';
include 'header.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$sender_id = $_SESSION['user_id'];
$to_id     = isset($_GET['to']) ? intval($_GET['to']) : 0;
$to_user   = null;

// If ?to= is given, fetch that user
if ($to_id) {
    $stmt = $pdo->prepare("SELECT id, username, display_name FROM users WHERE id = ?");
    $stmt->execute([$to_id]);
    $to_user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$to_user) {
        die("Invalid recipient.");
    }
}

// Otherwise load full list
if (!$to_user) {
    $stmt = $pdo->prepare("SELECT id, username, display_name FROM users WHERE id != ? ORDER BY username");
    $stmt->execute([$sender_id]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Compose Message</title>
  <style>body { font-family: sans-serif; }</style>
</head>
<body>
<div class="container">
<section>
  <h2>Compose New Message</h2>
  <form method="POST" action="send_message.php">
    <?php if ($to_user): ?>
      <!-- Hidden input for the pre-selected recipient -->
      <input type="hidden" name="receiver_id" value="<?= $to_user['id'] ?>">
      <p><strong>To:</strong>
        <?= htmlspecialchars($to_user['display_name'] ?: $to_user['username']) ?>
      </p>
    <?php else: ?>
      <label>To:<br>
        <select name="receiver_id" required>
          <option value="">— select user —</option>
          <?php foreach ($users as $u): ?>
            <?php 
              $name = $u['display_name'] ?: $u['username'];
            ?>
            <option value="<?= $u['id'] ?>">
              <?= htmlspecialchars($name) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label><br><br>
    <?php endif; ?>

    <label>Subject:<br>
      <input type="text" name="subject" maxlength="255" style="width:100%" required>
    </label><br><br>

    <label>Message:<br>
      <textarea name="body" rows="8" style="width:100%" required></textarea>
    </label><br><br>

    <button type="submit">Send Message</button>
  </form>

          </section></div>
  <?php include 'footer.php'; ?>