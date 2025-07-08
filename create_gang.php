<?php
// debug output
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require 'config.php';#
include 'header.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id  = $_SESSION['user_id'];
$feedback = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);

    if (strlen($name) < 3) {
        $feedback = "Name too short.";
    } else {
        // 1) create the gang
        $stmt = $pdo->prepare("
            INSERT INTO gangs (name, description, leader_id)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$name, $desc, $user_id]);
        $gang_id = $pdo->lastInsertId();

        // 2) add leader to members (now that role exists)
        $pdo->prepare("
            INSERT INTO gang_members (gang_id, user_id, role)
            VALUES (?, ?, 'leader')
        ")->execute([$gang_id, $user_id]);

        header("Location: view_gang.php?id={$gang_id}");
        exit;
    }
}
?>
<!DOCTYPE html>
<html><body>
<div class="container">
<section>
  <h2>Create New Gang</h2>
  <?php if ($feedback): ?>
    <p style="color:red;"><?= htmlspecialchars($feedback) ?></p>
  <?php endif; ?>
  <form method="POST">
    <label>Name:<br>
      <input type="text" name="name" required>
    </label><br>
    <label>Description:<br>
      <textarea name="description"></textarea>
    </label><br>
    <button type="submit">Create Gang</button>
  </form>
  <p><a href="dashboard.php">← Back to Dashboard</a></p>
  </section></div>
  <?php include 'footer.php'; ?>