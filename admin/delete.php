<?php
require_once 'admin-main.php';

if (isset($_POST['type'])) {
    if ($_POST['type'] == 'product' && isset($_POST['pid'])) {
        $pid = $_POST['pid'];

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
            echo "Product deletion failed: " . $conn->error;
        }
        $stmt->close();
    } elseif ($_POST['type'] == 'category' && isset($_POST['catid'])) {
        $catid = $_POST['catid'];

        // 删除类别数据
        $deleteQuery = "DELETE FROM categories WHERE catid = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param('i', $catid);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            header('Location: AdminPanel.php'); // 重定向回首页
        } else {
            echo "Category deletion failed: " . $conn->error;
        }
        $stmt->close();
    }
    $conn->close();
} else {
    die('No type provided.');
}
?>