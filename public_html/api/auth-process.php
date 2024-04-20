<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// 生成并验证Nonce
function generateNonce(): string
{
    try {
        $nonce = bin2hex(random_bytes(16));
    } catch (Exception $e) {
        die('Error generating nonce: ' . $e->getMessage());
    }

    if (!isset($_SESSION)) {
        die('Session is not started');
    }

    $_SESSION['nonce'] = $nonce;
    return $nonce;
}
function verifyNonce($receivedNonce): bool
{
    if (isset($_SESSION['nonce']) && $receivedNonce === $_SESSION['nonce']) {
        unset($_SESSION['nonce']); // 验证后即销毁
        return true;
    }
    return false;
}
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
    } else {
        header('Location: ../index.php');
    }
    exit();
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

if (isset($_POST['login'])) {
    require '../../includes/dbconfig.php';
    $email = $_POST['email'];
    $password = $_POST['password'];
    $nonce = $_POST['nonce']; // 从表单中获取提交的Nonce

    // 验证Nonce
    if (!verifyNonce($nonce)) {
        die('CSRF detection failed.');
    }
    // 使用辅助函数验证电子邮件和密码
    validateEmail($email);
    validatePassword($password);

    // 准备查询
    $stmt = $conn->prepare("SELECT userid, username, password, google_id, is_admin FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // 检查是否有Google ID，如果有，则不允许使用传统方式登录
        if (!empty($user['google_id'])) {
            die('Please use Google to log in.');
        }

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
    $conn->close();
} elseif (isset($_POST['logout'])) { // 处理注销
    logoutUser();

}elseif (isset($_POST['action']) && $_POST['action'] == 'change_password') {
    require '../../includes/dbconfig.php';
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];
    $nonce = $_POST['nonce']; // 从表单中获取提交的Nonce

    // 验证Nonce
    if (!verifyNonce($nonce)) {
        die('CSRF detection failed.');
    }
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
    $conn->close();
}else if(isset($_POST['action']) && $_POST['action'] == 'get_orders'){
    require '../../includes/dbconfig.php';
    if (!isset($_SESSION['userid'])) {
        echo json_encode(['error' => 'User not authenticated']);
        exit;
    }
    $userid = $_SESSION['userid'];
    header('Content-Type: application/json');
    $userinfo = [];
    $userQuery = "SELECT username, email FROM users WHERE userid = ?";
    $userStmt = $conn->prepare($userQuery);
    $userStmt->bind_param('i', $userid);
    $userStmt->execute();
    $userResult = $userStmt->get_result();

    while ($row = $userResult->fetch_assoc()) {
        $userinfo = ['email' => $row['email'], 'userName' => $row['username']];
    }
    $orderQuery = "SELECT 
    o.order_id, 
    o.username, 
    o.created_at, 
    o.total_price, 
    o.status, 
    p.pid, 
    p.quantity, 
    p.price,
    prod.name AS product_name
    FROM orders o 
    JOIN order_items p ON o.order_id = p.order_id 
    JOIN products prod ON p.pid = prod.pid
    JOIN users ON o.userid = users.userid
    WHERE o.userid = ?
    ORDER BY o.created_at DESC, o.order_id, p.pid";

    $orderStmt = $conn->prepare($orderQuery);
    $orderStmt->bind_param('i', $userid);
    $orderStmt->execute();
    $orderResult = $orderStmt->get_result();

    $orders = [];

    while ($row = $orderResult->fetch_assoc()) {
        $orders[$row['order_id']]['order_info'] = [
            'username' => $row['username'],
            'created_at' => $row['created_at'],
            'total_price' => $row['total_price'],
            'status' => $row['status']
        ];
        $orders[$row['order_id']]['products'][] = [
            'product_id' => $row['pid'],
            'product_name' => $row['product_name'],
            'quantity' => $row['quantity'],
            'price' => $row['price']
        ];
    }
    echo json_encode(['orders' => $orders, 'userinfo' => $userinfo]);

}else if(isset($_POST['action']) && $_POST['action'] == 'google_login'){
    require '../../includes/dbconfig.php';
    require_once '../../vendor/autoload.php'; // 确保您安装了Google API客户端库
    $CLIENT_ID = '49074735272-2f27760hq276vqj6per693ja766bmm0g.apps.googleusercontent.com';
    $client = new Google_Client(['client_id' => $CLIENT_ID]);
    $id_token = $_POST['idtoken'];
    $payload = $client->verifyIdToken($id_token);
    header('Content-Type: application/json');

    if ($payload) {
        $google_id = $payload['sub'];
        $username = $payload['name'];
        $email = $payload['email'];

        $stmt = $conn->prepare("SELECT userid, username, is_admin FROM users WHERE google_id = ?");
        $stmt->bind_param("s", $google_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            session_regenerate_id(true);
            setAuthCookie();
            $_SESSION['userid'] = $user['userid'];
            $_SESSION['username'] = $user['username'];
            echo json_encode(['status' => 'success','redirect' => 'index.php']);
        } else {
            $is_admin = 0;
            $stmt = $conn->prepare("INSERT INTO users (google_id, username, email, is_admin) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $google_id, $username, $email, $is_admin);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $user_id = $stmt->insert_id;
                session_regenerate_id(true);
                setAuthCookie();
                $_SESSION['userid'] = $user_id;
                $_SESSION['username'] = $username;
                echo json_encode(['status' => 'success', 'redirect' => 'index.php']);
            } else {
                echo json_encode(['status' => 'error', 'message' => '新用户创建失败']);
            }
        }
        $conn->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Token verification failed']);
    }
}

?>