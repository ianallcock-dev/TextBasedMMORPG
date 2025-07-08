<?php
session_start();require 'config.php';
if(!isset($_SESSION['user_id']))die;
$me=$_SESSION['user_id'];
$id=intval($_GET['id']);
if(!$id)die;
// fetch invite
$stmt=$pdo->prepare("
  SELECT gang_id,user_id 
  FROM gang_invites
  WHERE id=? AND user_id=? AND status='pending'
");
$stmt->execute([$id,$me]);
$inv=$stmt->fetch();
if(!$inv)die("Invite not found.");
// mark accepted & add to members
$pdo->prepare("
  UPDATE gang_invites 
  SET status='accepted' 
  WHERE id=?
")->execute([$id]);
$pdo->prepare("
  INSERT INTO gang_members (gang_id,user_id,role)
  VALUES (?,?,'member')
")->execute([$inv['gang_id'],$me]);
header("Location: view_gang.php?id={$inv['gang_id']}");
?>