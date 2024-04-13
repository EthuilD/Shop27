<?php
require_once '../includes/dbconfig.php';
session_start();
function generateNonce(): string
{
    $nonce = bin2hex(random_bytes(16));
    $_SESSION['nonce'] = $nonce;
    return $nonce;
}
// 验证Nonce
function verifyNonce($receivedNonce): bool
{
    if (isset($_SESSION['nonce']) && $receivedNonce === $_SESSION['nonce']) {
        unset($_SESSION['nonce']); // 验证后即销毁
        return true;
    }
    return false;
}
function test_input($data){
    $data = trim($data);
    $data = stripslashes($data);
    return htmlspecialchars($data);
}
// 身份验证
function isAuthenticated(): bool {
    if (!isset($_SESSION['auth_token'], $_COOKIE['auth'])) {
        return false;
    }
    return $_SESSION['auth_token'] === $_COOKIE['auth'];
}

// 重定向到登录页面
function redirectToLogin() {
    header('Location: ../public_html/login.php');
    exit;
}

// 获取所有类别
function getCategories($conn) {
    $categoriesQuery = "SELECT * FROM categories";
    $categoriesResult = $conn->query($categoriesQuery);
    return $categoriesResult;
}

// 获取所有产品
function getProducts($conn) {
    $productsQuery = "SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.catid = c.catid";
    $productsResult = $conn->query($productsQuery);
    if ($productsResult === false) {
        die("Query failed: " . $conn->error);
    }
    return $productsResult;
}
function fetchOrdersWithProducts($conn) {
    $query = "SELECT 
        o.order_id, 
        o.userid,
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
        ORDER BY o.created_at DESC, o.order_id, p.pid";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[$row['order_id']]['order_info'] = [
            'userid' => $row['userid'],
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
    return $orders;
}
// 检查用户是否通过验证，如果没有，则重定向
if (!isAuthenticated()) {
    redirectToLogin();
}

?>