<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $displayName = trim($_POST['display_name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $ageBracket = $_POST['age_bracket'] ?? null;
    $consent = isset($_POST['consent']) ? 1 : 0;
    $newsletter = isset($_POST['newsletter']) ? 1 : 0;

    if (!$consent) {
        die("You must agree to the privacy policy and terms.");
    }

    // Basic validation
    if (strlen($username) < 3 || strlen($username) > 50) {
        die("Username must be between 3 and 50 characters.");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email address.");
    }

    $stmt = $pdo->prepare("
        INSERT INTO users 
            (username, display_name, email, password_hash, age_bracket, consent, newsletter_optin,turns,cash)
        VALUES (?, ?, ?, ?, ?, ?, ?, 1000,5000)
    ");
    $stmt->execute([
        $username,
        $displayName,
        $email,
        $password,
        $ageBracket,
        $consent,
        $newsletter
    ]);

    echo "Registration successful. <a href='login.php'>Login here</a>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
</head>
<body>
    <h2>Register</h2>
    <form action="register.php" method="POST">
        Username:<br>
        <input type="text" name="username" required><br><br>

        Display Name:<br>
        <input type="text" name="display_name" required><br><br>

        Email:<br>
        <input type="email" name="email" required><br><br>

        Password:<br>
        <input type="password" name="password" required><br><br>

        Age Bracket:<br>
        <select name="age_bracket">
            <option value="">Prefer not to say</option>
            <option value="under18">Under 18</option>
            <option value="18-24">18-24</option>
            <option value="25-34">25-34</option>
            <option value="35-44">35-44</option>
            <option value="45plus">45+</option>
        </select><br><br>

        <input type="checkbox" name="consent" required>
        I agree to the <a href="privacy_policy.html" target="_blank">Privacy Policy</a> and <a href="terms.html" target="_blank">Terms of Service</a>.<br><br>

        <input type="checkbox" name="newsletter" value="1">
        Subscribe me to the newsletter (optional).<br><br>

        <input type="submit" value="Register">
    </form>
</body>
</html>
