<?php
// dev: show errors until this is rock‐solid

// 1) Start session & require config
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require 'config.php';

// 2) Login check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$me = (int) $_SESSION['user_id'];

// 3) Validate incoming gang ID
$gid = isset($_GET['gang']) ? (int) $_GET['gang'] : 0;
if ($gid < 1) {
    die("Invalid gang ID.");
}

// 4) Verify membership & get role
$stmt = $pdo->prepare("
    SELECT role 
    FROM gang_members
    WHERE gang_id = ? AND user_id = ?
");
$stmt->execute([$gid, $me]);
$role = $stmt->fetchColumn();
if (!$role) {
    die("You’re not in that gang.");
}

// 5) Handle new post (only members+)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $txt = trim($_POST['content'] ?? '');
    if ($txt !== '') {
        $ins = $pdo->prepare("
          INSERT INTO gang_posts (gang_id, user_id, content)
          VALUES (?, ?, ?)
        ");
        $ins->execute([$gid, $me, $txt]);
    }
    // reload to show the new message
    header("Location: gang_board.php?gang={$gid}");
    exit;
}

// 6) Fetch all posts
$stmt = $pdo->prepare("
    SELECT gp.created_at, gp.content, u.username
    FROM gang_posts gp
    JOIN users u ON u.id = gp.user_id
    WHERE gp.gang_id = ?
    ORDER BY gp.created_at DESC
");
$stmt->execute([$gid]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 7) Now include your common header (which opens <html><body> etc)
include 'header.php';
?>

<div class="container">
  <section>
    <h2>Gang Board</h2>
    <p>Your role: <strong><?= htmlspecialchars($role) ?></strong></p>

    <!-- Post form -->
    <form method="POST">
      <label for="content">New message</label>
      <textarea id="content" name="content" rows="4" required></textarea>
      <button type="submit">Post Message</button>
    </form>

    <hr>

    <!-- Display posts -->
    <?php if (count($posts) === 0): ?>
      <p>No messages yet.</p>
    <?php else: ?>
      <?php foreach ($posts as $p): ?>
        <article>
          <p>
            <strong><?= htmlspecialchars($p['username']) ?></strong>
            <em><?= htmlspecialchars($p['created_at']) ?></em>
          </p>
          <div><?= nl2br(htmlspecialchars($p['content'])) ?></div>
          <hr>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>

    <p>
      <a href="view_gang.php?id=<?= $gid ?>">← Back to Gang</a>
    </p>
  </section>
</div>

<?php
// 8) And your common footer (which closes </body></html>)
include 'footer.php';
?>