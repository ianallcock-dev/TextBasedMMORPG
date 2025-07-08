<?php
require 'config.php';
include 'header.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$me  = $_SESSION['user_id'];
$gid = intval($_GET['id'] ?? 0);
if (!$gid) {
    die("No gang specified.");
}

// Fetch gang info
$stmt = $pdo->prepare("SELECT * FROM gangs WHERE id = ?");
$stmt->execute([$gid]);
$gang = $stmt->fetch();
if (!$gang) {
    die("Gang not found.");
}

// Fetch my role in this gang
$stmt = $pdo->prepare("
    SELECT role 
    FROM gang_members 
    WHERE gang_id = ? AND user_id = ?
");
$stmt->execute([$gid, $me]);
$myRole = $stmt->fetchColumn(); // leader, deputy, member, or false

// Fetch all members
$stmt = $pdo->prepare("
    SELECT u.id, u.username, gm.role
    FROM gang_members gm
    JOIN users u ON u.id = gm.user_id
    WHERE gm.gang_id = ?
    ORDER BY FIELD(gm.role,'leader','deputy','member'), u.username
");
$stmt->execute([$gid]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// If I’m leader/deputy, fetch pending invites
if (in_array($myRole, ['leader','deputy'], true)) {
    $stmt = $pdo->prepare("
        SELECT gi.id, u.username
        FROM gang_invites gi
        JOIN users u ON u.id = gi.user_id
        WHERE gi.gang_id = ? AND gi.status = 'pending'
    ");
    $stmt->execute([$gid]);
    $invites = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<main>
<div class="container">
  <nav><div class="container">
    <ul>
      <li><a href="dashboard.php">Dashboard</a></li>
      <li><a href="gang_board.php?gang=<?= $gid ?>">Board</a></li>
      <?php if ($myRole === 'leader'): ?>
        <li><a href="dissolve_gang.php?id=<?= $gid ?>"
               onclick="return confirm('Dissolve gang?');">
          Dissolve Gang
        </a></li>
      <?php endif; ?>
    </ul>
  </div></nav></div>

  <div class="container">
    <section>
      <p><strong>Your Gang:</strong> <?= htmlspecialchars($gang['name']) ?></p>
      <!--<p><?= nl2br(htmlspecialchars($gang['description'])) ?></p>-->
      <p><strong>Your Role:</strong> <?= $myRole ?: 'None' ?></p>
    </section>

    <section>
      <h3>Members</h3>
      <ul>
        <?php foreach ($members as $m): ?>
        <li>
          <?= htmlspecialchars($m['username']) ?> (<?= $m['role'] ?>)
          <?php if (
              /* leader can kick anyone except themself */
              ($myRole === 'leader' && $m['id'] != $me)
              /* deputy can kick members only */
              || ($myRole === 'deputy' && $m['role'] === 'member')
          ): ?>
            <a href="kick_member.php?gang=<?= $gid ?>&user=<?= $m['id'] ?>">
              [Kick]
            </a>
          <?php endif; ?>

          <?php if ($myRole === 'leader' && $m['role'] === 'member'): ?>
            <a href="assign_deputy.php?gang=<?= $gid ?>&user=<?= $m['id'] ?>">
              [Make Deputy]
            </a>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ul>
    </section>

    <?php if (in_array($myRole, ['leader','deputy'], true)): ?>
    <section>
      <h3>Invite a Player</h3>
      <form method="POST" action="invite_member.php">
        <input type="hidden" name="gang_id" value="<?= $gid ?>">
        <label>
          Username to invite:<br>
          <input type="text" name="username" required>
        </label><br>
        <button type="submit">Invite</button>
      </form>
    </section>

      <?php if (!empty($invites)): ?>
      <section>
        <h3>Pending Invites</h3>
        <ul>
          <?php foreach ($invites as $inv): ?>
          <li>
            <?= htmlspecialchars($inv['username']) ?>
            <?php if ($myRole === 'leader'): ?>
              <a href="cancel_invite.php?id=<?= $inv['id'] ?>">
                [Cancel]
              </a>
            <?php endif; ?>
          </li>
          <?php endforeach; ?>
        </ul>
      </section>
      <?php endif; ?>
    <?php endif; ?>

    
  </div></main>

<?php include 'footer.php'?>
