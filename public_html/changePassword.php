<?php
require 'api/auth-process.php';
$nonce = generateNonce();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="login-container">
    <div class="welcome-message">
        <h2>Welcome Back!</h2>
        <p>Please confirm your password.</p>
    </div>
    <form class="login-form" action="api/auth-process.php" method="post">
        <input type="hidden" name="action" value="change_password">

        <label for="password">Current Password:</label>
        <input type="password" name="old_password" required>

        <label for="password">New Password:</label>
        <input type="password" name="new_password" required>

        <label for="password">Confirm New Password:</label>
        <input type="password" name="confirm_new_password" required>

        <input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce); ?>">
        <button type="submit">Change Password</button>
    </form>
</div>

</body>
</html>