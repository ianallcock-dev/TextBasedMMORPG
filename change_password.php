<?php
// change_password.php
session_start();
require 'config.php';
if (!isset($_SESSION['user_id'])) {
    die("Please log in to change your password.");
}
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old  = $_POST['old_password']     ?? '';
    $new  = $_POST['new_password']     ?? '';
    $conf = $_POST['confirm_password'] ?? '';

    if ($new !== $conf) {
        die("New passwords do not match.");
    }

    // Verify old password
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $hash = $stmt->fetchColumn();
    if (!password_verify($old, $hash)) {
        die("Current password is incorrect.");
    }

    // Update to new hash
    $newHash = password_hash($new, PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")
        ->execute([$newHash, $user_id]);

    echo "Password changed. <a href='account.php'>Back to Account</a>";
    exit;
}

// If not POST
header("Location: account.php");
exit;
?>