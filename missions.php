<?php
require 'config.php';
include 'header.php';

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT turns FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<div class="container">
<section>
<h2>Missions</h2>

<form action="do_mission.php" method="POST">
  <input type="hidden" name="mission" value="heist">
  <p>Clockwork Heist - Costs 20 turns</p>
  <input type="submit" value="Do Mission">
</form>
</section></div>
<?php include 'footer.php'; ?>