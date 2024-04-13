<?php
require '../../includes/dbconfig.php';

// Improve error handling by defining a custom function
function handleError($message, $terminate = true) {
    error_log($message);
    if ($terminate) {
        exit(0);
    }
}

function getOrderInfo($invoice) {
    global $conn; // Ensure $conn is accessible
    $stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ?");
    $stmt->bind_param('s', $invoice);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        return null;
    }
}

$paypal_url = "https://ipnpb.sandbox.paypal.com/cgi-bin/webscr";
$raw_post_data = file_get_contents('php://input');
$raw_post_array = explode('&', $raw_post_data);
$myPost = array();
foreach ($raw_post_array as $keyval) {
    $keyval = explode('=', $keyval);
    if (count($keyval) == 2) {
        $myPost[$keyval[0]] = urldecode($keyval[1]);
    }
}

$req = 'cmd=_notify-validate';
foreach ($myPost as $key => $value) {
    if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc() == 1) {
        $value = urlencode(stripslashes($value));
    } else {
        $value = urlencode($value);
    }
    $req .= "&$key=$value";
}

$ch = curl_init($paypal_url);
curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
$res = curl_exec($ch);
if ($res === false) {
    $errno = curl_errno($ch);
    $errstr = curl_error($ch);
    curl_close($ch);
    handleError("cURL error: [$errno] $errstr");
}

$info = curl_getinfo($ch);
$httpCode = $info['http_code'];
if ($httpCode != 200) {
    curl_close($ch);
    handleError("PayPal responded with http code $httpCode");
}

curl_close($ch);
if (strcmp($res, "VERIFIED") == 0) {
    // IPN message was verified by PayPal; proceed to process the message

    $payment_status = $_POST['payment_status'];
    $receiver_email = $_POST['receiver_email'];
    $mc_gross = $_POST['mc_gross'];
    $mc_currency = $_POST['mc_currency'];
    $invoice = $_POST['invoice'];
    $orderDigest = $_POST['custom'];
    $txn_id = $_POST['txn_id'];
    $txn_type = $_POST['txn_type'];

    if ($receiver_email != "sb-ntpge29911898@business.example.com") {
        handleError("Receiver email does not match: $receiver_email");
    }

    $orderInfo = getOrderInfo($invoice);
    if (!$orderInfo) {
        handleError("Order not found: $invoice");
    }
    // 重新生成摘要
    $salt = $orderInfo['salt'];
    // 获取订单项信息
    $sqlItems = "SELECT * FROM order_items WHERE order_id = ?";
    $stmtItems = $conn->prepare($sqlItems);
    $stmtItems->bind_param('i', $invoice);
    $stmtItems->execute();
    $resultItems = $stmtItems->get_result();
    $orderItems = [];
    while($row = $resultItems->fetch_assoc()) {
        $orderItems[] = $row;
    }

    // 将订单项数组转换为字符串形式
    $orderItemsString = implode(',', array_map(function ($item) {
        return $item['pid'] . ':' . $item['quantity'] . ':' . $item['price'];
    }, $orderItems));

    // 重新生成摘要
    $reconstructedDigest = hash('sha256', implode('|', [
        $mc_currency,
        $receiver_email,
        $salt,
        $orderItemsString,
        $mc_gross
    ]));

    // 验证摘要
    if ($orderDigest != $reconstructedDigest) {
        // 记录日志或者进行错误处理
        error_log("Invalid digest for order: " . $invoice);
        exit(0);
    }

    if ($txn_type != 'cart') {
        handleError("Transaction type is not cart: $txn_type");
    }

    // 检查 txn_id 是否已经存在于数据库中
    $stmtCheckTxn = $conn->prepare("SELECT txn_id FROM orders WHERE txn_id = ?");
    $stmtCheckTxn->bind_param('s', $txn_id);
    $stmtCheckTxn->execute();
    $resultCheckTxn = $stmtCheckTxn->get_result();
    if ($resultCheckTxn->num_rows > 0) {
        handleError("Transaction ID already exists: $txn_id");
    }

    // 如果所有检查都通过，则在数据库中插入 txn_id 并更新订单状态
    $sqlUpdateOrder = "UPDATE orders SET status = 'completed', txn_id = ? WHERE order_id = ?";
    $stmtUpdateOrder = $conn->prepare($sqlUpdateOrder);
    $stmtUpdateOrder->bind_param('si', $txn_id, $invoice);

    if (!($stmtUpdateOrder->execute())) {
        // 更新失败，记录错误
        error_log("Failed to update order status: " . $stmtUpdateOrder->error);
    }

} elseif (strcmp($res, "INVALID") == 0) {
    handleError("Received an invalid IPN message.", false);
} else {
    handleError("Unknown response from PayPal.", false);
}