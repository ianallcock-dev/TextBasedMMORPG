<?php

require 'config.php';
include 'header.php';

// Make sure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Fetch all other players
$stmt = $pdo->prepare("
  SELECT id, username, prestige, thugs, entertainers, operations
  FROM users
  WHERE id != ?
");
$stmt->execute([ $_SESSION['user_id'] ]);
$players = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Attack a Player</title>
</head>
<body>
<div class="container">
<section>
  <h2>Attack a Player</h2>

  <form method="POST" action="attack_process.php">
    <label>
      Select target:
      <select name="target_id">
        <?php foreach ($players as $p): ?>
          <option value="<?= $p['id'] ?>">
            <?= htmlspecialchars($p['username']) ?> 
            (Thugs: <?= $p['thugs'] ?>, 
             Ent: <?= $p['entertainers'] ?>, 
             Ops: <?= $p['operations'] ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </label>
    <br><br>

    <label><input type="radio" name="action" value="attack" checked> Attack Thugs</label><br>
    <label><input type="radio" name="action" value="lure"> Lure Entertainers</label><br>
    <label><input type="radio" name="action" value="sabotage"> Sabotage Operations</label>
    <br><br>

    <div id="lure-input" style="display:none;">
      <label>
        Luxury Goods to use:
        <input type="number" name="luxury_goods_used" min="1">
      </label>
      <br><br>
    </div>

    <div id="sabotage-input" style="display:none;">
      <label>
        Operations to destroy:
        <input type="number" name="sabotage_qty" min="1">
      </label>
      <br><br>
    </div>

    <button type="submit">Execute</button>
  </form>

  <p><a href="dashboard.php">← Back to Dashboard</a></p>

  <script>
    // Show/hide the extra fields based on action
    const actionRadios = document.querySelectorAll('input[name=action]');
    function updateFields() {
      document.getElementById('lure-input').style.display = 
        document.querySelector('input[name=action]:checked').value === 'lure' ? 'block' : 'none';
      document.getElementById('sabotage-input').style.display = 
        document.querySelector('input[name=action]:checked').value === 'sabotage' ? 'block' : 'none';
    }
    actionRadios.forEach(r=>r.addEventListener('change', updateFields));
    updateFields();
  </script>
  </section></div>
<?php include 'footer.php'; ?>
