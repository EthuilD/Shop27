<?php
// 在这里处理向PayPal发送的订单请求！
require '../../includes/dbconfig.php';
session_start();
$paypalConfig = [
    'business' => 'sb-ntpge29911898@business.example.com',
    'return' => 'https://secure.s27.iems5718.ie.cuhk.edu.hk/payment_success.html',
    'cancel_return' => 'https://secure.s27.iems5718.ie.cuhk.edu.hk/payment_cancelled.html',
    'notify_url' => 'https://secure.s27.iems5718.ie.cuhk.edu.hk/api/ipn_listener.php',
    'cmd' => '_cart',
    'upload' => '1',
    'currency_code' => 'HKD',
];
$paypalUrl = 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr';
// 获取JSON格式的请求体
$json = file_get_contents('php://input');
// 解码JSON数据到PHP数组
$data = json_decode($json, true);

$orderItems = [];
$totalPrice = 0.00; // 总价初始化

// 开始事务
$conn->begin_transaction();

try {
    if (isset($data['items'])) {
        $data['business'] = $paypalConfig['email'];
        $data['return'] = stripslashes($paypalConfig['return']);
        $data['cancel_return'] = stripslashes($paypalConfig['cancel_return']);
        $data['notify_url'] = stripslashes($paypalConfig['notify_url']);
        $data['currency_code'] = $paypalConfig['currency_code'];
        // 插入订单到 orders 表
        $orderStmt = $conn->prepare("INSERT INTO orders (userid, username, status, total_price, currency, digest, salt) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $userid = $_SESSION['userid']; // 获取用户ID
        $username = $_SESSION['username'] ?? "guest"; // 获取用户名
        $status = 'pending'; // 初始状态
        $currency = $paypalConfig['currency_code']; // 货币
        $merchantEmail = $paypalConfig['business']; // 商家邮箱
        $salt = bin2hex(random_bytes(8)); // 随机盐值

        // 执行订单插入
        $orderStmt->bind_param("issdsss", $userid, $username, $status, $totalPrice, $currency, $orderDigest, $salt);
        $orderStmt->execute();
        $orderId = $conn->insert_id; // 获取新创建的订单ID
        $paypalItems = [];
        // 循环处理每个产品
        foreach ($data['items'] as $product) {
            $pid = intval($product['pid']);
            $quantity = intval($product['quantity']);

            // 检查产品数量是否为正数
            if ($quantity <= 0) {
                throw new Exception("产品数量必须为正数");
            }

            // 从数据库获取产品价格
            $productStmt = $conn->prepare("SELECT name, price FROM products WHERE pid = ?");
            $productStmt->bind_param("i", $pid);
            $productStmt->execute();
            $result = $productStmt->get_result();
            $row = $result->fetch_assoc();
            $productName = $row['name'];
            $price = $row['price'];
            $totalPrice += $price * $quantity;
            $orderItems[] = implode(':', [$pid, $quantity, $price]);

            // 插入到 order_items 表
            $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, pid, quantity, price) VALUES (?, ?, ?, ?)");
            $itemStmt->bind_param("iiid", $orderId, $pid, $quantity, $price);
            $itemStmt->execute();

            // 准备 PayPal URL 中的商品信息
            $paypalItems[] = [
                'name' => $productName,
                'price' => $price,
                'quantity' => $quantity
            ];
        }
        // 更新订单总价格
        $updateStmt = $conn->prepare("UPDATE orders SET total_price = ? WHERE order_id = ?");
        $updateStmt->bind_param("di", $totalPrice, $orderId);
        $updateStmt->execute();

        // 生成订单摘要字符串
        $orderDigest = hash('sha256', implode('|', [$currency, $merchantEmail, $salt, implode(',', $orderItems), $totalPrice]));
        // 更新订单摘要
        $digestStmt = $conn->prepare("UPDATE orders SET digest = ? WHERE order_id = ?");
        $digestStmt->bind_param("si", $orderDigest, $orderId);
        $digestStmt->execute();
        $paypalConfig['invoice'] = $orderId;
        $paypalConfig['custom'] = $orderDigest;
        // 生成包含商品信息的查询参数
        $queryString = http_build_query($paypalConfig);
        foreach ($paypalItems as $index => $item) {
            $adjustedIndex = $index + 1;
            $queryString .= "&item_name_$adjustedIndex=" . urlencode($item['name']);
            $queryString .= "&amount_$adjustedIndex=" . $item['price'];
            $queryString .= "&quantity_$adjustedIndex=" . $item['quantity'];
        }

        // 生成重定向URL
        $redirectUrl = $paypalUrl . '?' . $queryString;
        echo json_encode(['url' => $redirectUrl]);
        // 提交事务
        $conn->commit();
    } else {
        throw new Exception("订单中没有产品");
    }

} catch (Exception $e) {
    // 发生异常，回滚事务
    $conn->rollback();
    echo json_encode(['error' => '订单创建失败: ' . $e->getMessage()]);
    exit;
}

// 关闭连接
$conn->close();