<?php
require 'dbconfig.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (isset($_GET['id'])) {
    $pid = $_GET['id'];

    // 删除与产品关联的文件
    $fileQuery = "SELECT image FROM products WHERE pid = ?";
    $stmt = $conn->prepare($fileQuery);
    $stmt->bind_param('i', $pid);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    if ($product && file_exists($product['image'])) {
        unlink($product['image']); // 删除文件
    }
    $stmt->close();

    // 删除产品数据
    $deleteQuery = "DELETE FROM products WHERE pid = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param('i', $pid);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        header('Location: AdminPanel.php'); // 重定向回首页
    } else {
        echo "产品删除失败：" . $conn->error;
    }

    $stmt->close();
    $conn->close();
} else {
    die('未提供产品ID。');
}
?>
