<?php
// find_player.php

require 'config.php';
include 'header.php';
// 1) Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 2) Handle search query
$q      = trim($_GET['q'] ?? '');
$results = [];

if ($q !== '') {
    // Add wildcards for partial matching
    $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';

    // Search username or display_name
    $stmt = $pdo->prepare("
        SELECT id, username, display_name
        FROM users
        WHERE username     LIKE :like
           OR display_name LIKE :like
        ORDER BY username
        LIMIT 50
    ");
    $stmt->execute([':like' => $like]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Find Player</title>
  <style>
    body { font-family: sans-serif; padding:1em; max-width:600px; margin:auto; }
    input[type=text] { width: 80%; padding: 6px; }
    button { padding: 6px 12px; }
    ul { list-style: none; padding-left: 0; }
    li { margin: .5em 0; }
    a { color: #007bff; text-decoration: none; }
    a:hover { text-decoration: underline; }
  </style>
</head>
<body>
<div class="container">
<section>
  <h2>Find a Player</h2>

  <form method="GET" action="find_player.php">
    <input 
      type="text" 
      name="q" 
      placeholder="Enter username or display name" 
      value="<?= htmlspecialchars($q) ?>" 
      required 
    >
    <button type="submit">Search</button>
  </form>

  <?php if ($q !== ''): ?>
    <h3>Search results for “<?= htmlspecialchars($q) ?>”</h3>

    <?php if (empty($results)): ?>
      <p>No players found.</p>
    <?php else: ?>
      <ul>
        <?php foreach ($results as $u): ?>
          <?php 
            $name = $u['display_name'] ?: $u['username'];
          ?>
          <li>
            <a href="profile.php?user_id=<?= $u['id'] ?>">
              <?= htmlspecialchars($name) ?>
            </a>
            &nbsp;<small>(<?= htmlspecialchars($u['username']) ?>)</small>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  <?php endif; ?>

        </section></div>
  <?php include 'footer.php'; ?>
