<?php
// send_message.php
session_start();
require 'config.php';

// 1) Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Please log in to send messages.");
}
$sender_id = $_SESSION['user_id'];

// 2) Grab & sanitize POST inputs
$receiver_id = intval($_POST['receiver_id'] ?? 0);
$subject     = trim($_POST['subject']       ?? '');
$body        = trim($_POST['body']          ?? '');

// 3) Basic validation
if ($receiver_id < 1 || $subject === '' || $body === '') {
    die("All fields (To, Subject, Message) are required.");
}

// 4) Ensure the recipient exists
$stmt = $pdo->prepare("SELECT 1 FROM users WHERE id = ?");
$stmt->execute([$receiver_id]);
if (!$stmt->fetchColumn()) {
    die("That recipient does not exist.");
}

// 5) Insert into messages
$stmt = $pdo->prepare("
    INSERT INTO messages
      (sender_id, receiver_id, subject, body)
    VALUES (?, ?, ?, ?)
");
$stmt->execute([
    $sender_id,
    $receiver_id,
    $subject,
    $body
]);

// 6) Redirect to inbox
header("Location: inbox.php");
exit;
?>