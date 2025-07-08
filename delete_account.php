<?php
// delete_account.php
session_start();
require 'config.php';
if (!isset($_SESSION['user_id'])) {
    die("Please log in to delete your account.");
}
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirm = trim($_POST['confirm_text'] ?? '');
    if (strcasecmp($confirm, 'DELETE') !== 0) {
        die("You must type DELETE (uppercase) to confirm.");
    }

    // Delete all user data
    $pdo->prepare("DELETE FROM messages WHERE sender_id = ? OR receiver_id = ?")
        ->execute([$user_id, $user_id]);
    // add any other cleanup here...

    $pdo->prepare("DELETE FROM users WHERE id = ?")
        ->execute([$user_id]);

    // Log out
    session_unset();
    session_destroy();

    echo "Account deleted. Goodbye!";
    exit;
}

// If not POST
header("Location: account.php");
exit;
?>