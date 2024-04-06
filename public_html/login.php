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
        <p>Please sign in to continue.</p>
    </div>
    <form class="login-form" action="api/auth-process.php" method="post">
        <?php if (isset($_GET['error'])): ?>
            <div class="error">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce); ?>">
        <button type="submit" name="login">Login</button>
    </form>
</div>
</body>
</html>