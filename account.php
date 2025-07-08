<?php

require 'config.php';
include 'header.php';
// 1) Ensure logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// 2) Fetch current user for display (e.g. username, email)
$stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Account Overview</title>
  <style>
    h2, h3 { margin-top:0; }
    label { display:block; margin:0.5em 0 0.2em; }
    input[type=text], input[type=password] { width:100%; padding:6px; box-sizing:border-box; }
    button { margin-top:0.5em; padding:6px 12px; }
  </style>
</head>
<body>
<div class="container">
<section>
  <h2>Account Overview for <?= htmlspecialchars($user['username']) ?></h2>
  <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>

  <!-- 1) Transfer Turns -->
  <section id="transfer-turns">
    <h3>Transfer Turns</h3>
    <?php include 'transfer_turns.php'; ?>
  </section>

  <!-- 2) Change Password -->
  <section id="change-password">
    <h3>Change Password</h3>
    <form method="POST" action="change_password.php">
      <label for="old_pw">Current Password</label>
      <input type="password" id="old_pw" name="old_password" required>

      <label for="new_pw">New Password</label>
      <input type="password" id="new_pw" name="new_password" required>

      <label for="confirm_pw">Confirm New Password</label>
      <input type="password" id="confirm_pw" name="confirm_password" required>

      <button type="submit">Change Password</button>
    </form>
  </section>

  <!-- 3) Delete Account -->
  <section id="delete-account">
    <h3>Delete Account</h3>
    <p style="color:darkred;">
      <strong>Warning:</strong> This is irreversible! All your data will be permanently removed.
    </p>
    <form method="POST" action="delete_account.php">
      <label for="confirm">Type <strong>DELETE</strong> to confirm:</label>
      <input type="text" id="confirm" name="confirm_text" required>

      <button type="submit" style="background:darkred; color:white;">
        Delete My Account
      </button>
    </form>
  </section></div>

  <?php include 'footer.php'; ?>
