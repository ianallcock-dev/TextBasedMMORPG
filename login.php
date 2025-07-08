<?php
session_start();
require 'config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($_POST['password'], $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Steampunk Login</title>
  <link href="https://fonts.googleapis.com/css2?family=IM+Fell+English+SC&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=UnifrakturCook:wght@700&display=swap" rel="stylesheet">
  <style>
    /* ====================
       GLOBAL RESET & BASE
       ==================== */
    * { margin:0; padding:0; box-sizing:border-box; }
    body, html {
      height:100%;
      font-family: 'IM Fell English SC', serif;
      color: #f0e6d2;
    }
    /* ====================
       BACKGROUND & OVERLAY
       ==================== */
    body {
      background: url('images/metaltile.png') repeat;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .overlay {
      position: fixed;
      top:0; left:0;
      width:100%; height:100%;
      background: rgba(20,10,5,0.6);
      z-index: 1;
    }
    /* ====================
       LOGIN CONTAINER
       ==================== */
    .login-container {
      position: relative;
      z-index: 2;
      background: rgba(30,20,10,0.85);
      border: 2px solid #b67c3d;
      border-radius: 10px;
      padding: 40px 30px;
      width: 100%;
      max-width: 400px;
      box-shadow: 0 0 20px #b67c3d;
      backdrop-filter: blur(4px);
    }
    h1 {
      font-family: 'UnifrakturCook', cursive;
      font-size: 2.5rem;
      color: #e5b567;
      text-align: center;
      margin-bottom: 1.5rem;
      text-shadow: 2px 2px #000;
    }
    /* ====================
       FORM ELEMENTS
       ==================== */
    .login-container form input[type="text"],
    .login-container form input[type="password"] {
      width: 100%;
      padding: 0.75rem;
      margin-bottom: 1rem;
      border: 1px solid #b67c3d;
      background: #2b1e13;
      color: #f0e6d2;
      border-radius: 5px;
      font-size: 1rem;
    }
    .login-container form input[type="submit"] {
      width: 100%;
      padding: 0.75rem;
      border: none;
      background: linear-gradient(#b67c3d, #814e1e);
      color: #fff;
      font-size: 1.1rem;
      font-weight: bold;
      border-radius: 5px;
      cursor: pointer;
      transition: background 0.3s;
    }
    .login-container form input[type="submit"]:hover {
      background: linear-gradient(#d48b47, #a35b2c);
    }
    .error {
      color: #ff7777;
      text-align: center;
      margin-bottom: 1rem;
    }
    /* ====================
       RESPONSIVE ADJUSTMENTS
       ==================== */
    @media (max-width: 480px) {
      .login-container {
        padding: 30px 20px;
      }
      h1 {
        font-size: 2rem;
      }
    }
  </style>
</head>
<body>
  <div class="overlay"></div>
  <div class="login-container">
    <h1>Steam &amp; Steel</h1>
    <?php if($error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST" action="login.php">
      <input type="text" name="username" placeholder="Username" required>
      <input type="password" name="password" placeholder="Password" required>
      <input type="submit" value="Enter the Machine">
    </form>
  </div>
</body>
</html>
