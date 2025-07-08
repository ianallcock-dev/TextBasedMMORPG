<?php

require 'config.php';
include 'header.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$me     = $_SESSION['user_id'];
$msg_id = intval($_GET['id'] ?? 0);
if (!$msg_id) {
    die("No message specified.");
}

// 1) Load the message (ensure you’re the recipient)
$stmt = $pdo->prepare("
  SELECT m.*, s.username AS sender_name, s.id AS sender_id
  FROM messages m
  JOIN users s ON s.id = m.sender_id
  WHERE m.id = ? AND m.receiver_id = ?
");
$stmt->execute([$msg_id, $me]);
$msg = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$msg) {
    die("Message not found or access denied.");
}

// 2) Mark it read if unread
if (!$msg['is_read']) {
    $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ?")
        ->execute([$msg_id]);
}

// 3) Prepare reply defaults
$reply_to_id   = $msg['sender_id'];
$reply_to_name = htmlspecialchars($msg['sender_name']);
$reply_subject = 'Re: ' . $msg['subject'];
$quoted_body   = "\n\n--- On {$msg['sent_at']}, {$reply_to_name} wrote: ---\n"
               . wordwrap($msg['body'], 70, "\n");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View Message & Reply</title>
  <style>
    body { font-family: sans-serif; padding: 1em; max-width: 800px; margin: auto; }
    .msg-header { margin-bottom: .5em; }
    .msg-body { border: 1px solid #ccc; padding: .5em; background: #f9f9f9; }
    form.reply { margin-top: 2em; }
    label { display: block; margin: .5em 0 0.2em; }
    input[type=text], textarea { width:100%; box-sizing: border-box; }
    textarea { height: 150px; }
    button { margin-top: .5em; }
  </style>
</head>
<body>
<div class="container">
<section>
  <h2>View Message</h2>

  <div class="msg-header">
    <p><strong>From:</strong> <?= $reply_to_name ?></p>
    <p><strong>Subject:</strong> <?= htmlspecialchars($msg['subject']) ?></p>
    <p><strong>Sent at:</strong> <?= htmlspecialchars($msg['sent_at']) ?></p>
  </div>
  <div class="msg-body">
    <?= nl2br(htmlspecialchars($msg['body'])) ?>
  </div>

  <!-- Inline reply form -->
  <form class="reply" method="POST" action="send_message.php">
    <h3>Reply to <?= $reply_to_name ?></h3>

    <!-- hidden recipient -->
    <input type="hidden" name="receiver_id" value="<?= $reply_to_id ?>">

    <label for="subject">Subject</label>
    <input type="text" id="subject" name="subject"
           value="<?= htmlspecialchars($reply_subject) ?>" required>

    <label for="body">Message</label>
    <textarea id="body" name="body" required><?= htmlspecialchars($quoted_body) ?></textarea>

    <button type="submit">Send Reply</button>
  </form>

  <p><a href="inbox.php">← Back to Inbox</a></p>
</section></div>
  <?php include 'footer.php'; ?>
