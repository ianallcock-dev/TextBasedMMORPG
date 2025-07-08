<?php
session_start(); require 'config.php';
if (!isset($_SESSION['user_id'])) die;
$me = $_SESSION['user_id'];
$gid = intval($_POST['gang_id']);
$user = trim($_POST['username']);

if (!$gid||!$user) die("Bad data.");

// check role
$stmt = $pdo->prepare("
  SELECT role FROM gang_members
  WHERE gang_id=? AND user_id=?
");
$stmt->execute([$gid,$me]);
$role = $stmt->fetchColumn();
if (!in_array($role,['leader','deputy'])) die("No invite rights.");

// find user_id
$stmt = $pdo->prepare("SELECT id FROM users WHERE username=?");
$stmt->execute([$user]);
$uid = $stmt->fetchColumn();
if (!$uid) die("No such user.");

// insert invite
$pdo->prepare("
  INSERT INTO gang_invites (gang_id,user_id,invited_by)
  VALUES (?,?,?)
")->execute([$gid,$uid,$me]);

header("Location: view_gang.php?id={$gid}");
?>