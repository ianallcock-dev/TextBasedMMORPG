<?php include 'header.php' ?>
<!-- Page Content -->
<main>
    <div class="container">
      <section>
        <h2>Welcome, <?= htmlspecialchars($user['username']) ?></h2>
        <p><strong>Server time (UTC):</strong> <?= $serverTime ?></p>
        <p><strong>Round ends in:</strong> <?= $timeLeft ?></p>
      </section>
      <section>
        <p class="alert">
          <?php if ($newMessages > 0): ?>
            <a href="inbox.php">📬 <?= $newMessages ?> new message<?= $newMessages!==1?'s':'' ?></a>
          <?php else: ?>
            <a href="inbox.php">📭 No new messages</a>
          <?php endif; ?>
        </p>
        <p class="alert">
          <?php if ($pendingInvites > 0): ?>
            <a href="inbox.php">📥 <?= $pendingInvites ?> pending gang invite<?= $pendingInvites!==1?'s':'' ?></a>
          <?php else: ?>
            <a href="inbox.php">📦 No gang invites</a>
          <?php endif; ?>
        </p>
        <p class="alert">
          <?php if ($myGang): ?>
            Gang: <strong><?= htmlspecialchars($myGang['name']) ?></strong>
            (Role: <?= htmlspecialchars($myGang['role']) ?>)
            &nbsp;<a href="view_gang.php?id=<?= $myGang['id'] ?>">Manage Gang</a>
            &nbsp;<a href="gang_board.php?gang=<?= $myGang['id'] ?>">Board</a>
          <?php else: ?>
            You’re not in a gang. <a href="create_gang.php">Create one</a>
          <?php endif; ?>
        </p>
      </section>

      <section>
        <h3>Your Stats</h3>
        <p><strong>Turns:</strong> <?= number_format($user['turns']) ?></p>
        <p><strong>Reserved:</strong> <?= number_format($user['reserved_turns']) ?></p>
        <p><strong>Cash:</strong> $<?= number_format($user['cash']) ?></p>
        <p><strong>Luxury Goods:</strong> <?= number_format($user['items']) ?></p>
        <p><strong>Thugs:</strong> <?= number_format($user['thugs']) ?></p>
        <p><strong>Entertainers:</strong> <?= number_format($user['entertainers']) ?></p>
        <p><strong>Operations:</strong> <?= number_format($user['operations']) ?></p>
      </section>

      <?php if (!empty($cashFromEntertainers)): ?>
        <section class="income">
          + $<?= number_format($cashFromEntertainers) ?> from entertainers
        </section>
      <?php endif; ?>

      <?php if (!empty($totalItemsProduced)): ?>
        <section class="income">
          + <?= number_format($totalItemsProduced) ?> Luxury Goods from operations
        </section>
      <?php endif; ?>
    </div>
  </main>

<?php include 'footer.php' ?>
