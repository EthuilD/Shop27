<?php
require '../../includes/dbconfig.php';
// 验证电子邮件格式
function validateEmail($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die('Invalid email format');
    }
}
// 验证密码长度
function validatePassword($password) {
    if (!preg_match("/.{6,}/", $password)) {
        die('Password must be at least 6 characters long');
    }
}
// 生成令牌并设置Cookie
function setAuthCookie() {
    $token = hash('sha256', openssl_random_pseudo_bytes(32));
    $_SESSION['auth_token'] = $token;
    $expiryTime = time() + (2 * 24 * 60 * 60);
    setcookie('auth', $token, [
        'expires' => $expiryTime,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}
// 重定向到管理面板
function redirectToDashboard($isAdmin) {
    if ($isAdmin) {
        header('Location: ../../admin/AdminPanel.php');
        exit();
    } else {
        header('Location: ../index.php');
        exit();
    }
}
// 用户注销
function logoutUser() {
    $_SESSION = array();
    if (isset($_COOKIE['auth'])) {
        setcookie('auth', '', time() - 42000, '/');
    }
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header('Location: ../index.php');
    exit();
}
session_start();

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // 使用辅助函数验证电子邮件和密码
    validateEmail($email);
    validatePassword($password);

    // 准备查询
    $stmt = $conn->prepare("SELECT userid, username, password, is_admin FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // 验证密码
        if (password_verify($password, $user['password'])) {
            // 防止会话固定攻击
            session_regenerate_id(true);

            // 生成一个安全的哈希令牌并设置认证Cookie
            setAuthCookie();

            $_SESSION['userid'] = $user['userid'];
            $_SESSION['username'] = $user['username'];
            // 密码正确，根据用户类型重定向
            redirectToDashboard($user['is_admin']);
        } else {
            die('Invalid password');
        }
    } else {
        die('No user found with that email address');
    }
} elseif (isset($_POST['logout'])) { // 处理注销

    logoutUser();

}elseif (isset($_POST['action']) && $_POST['action'] == 'change_password') {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];

    // 验证当前用户是否已登录
    if (!isset($_SESSION['userid'])) {
        die('You must be logged in to change your password');
    }

    // 验证新密码是否匹配
    if ($new_password !== $confirm_new_password) {
        die('New passwords do not match');
    }

    // 验证新密码的长度
    validatePassword($new_password);

    $userid = $_SESSION['userid'];

    // 验证旧密码是否正确
    $stmt = $conn->prepare("SELECT password FROM users WHERE userid = ?");
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        die('User not found');
    }

    $user = $result->fetch_assoc();
    if (!password_verify($old_password, $user['password'])) {
        die('Old password is incorrect');
    }

    // 更新数据库中的密码
    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE userid = ?");
    $update_stmt->bind_param("si", $new_password_hash, $userid);
    $update_stmt->execute();

    if ($update_stmt->affected_rows == 0) {
        die('Password not updated');
    }
    // 注销用户
    logoutUser();
}
$conn->close();
?>