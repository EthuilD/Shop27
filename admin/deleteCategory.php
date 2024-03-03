<?php
require '../includes/dbconfig.php';

if (isset($_GET['id'])) {
    $catid = $_GET['id'];

    // 删除产品数据
    $deleteQuery = "DELETE FROM categories WHERE catid = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param('i', $catid);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        header('Location: AdminPanel.php'); // 重定向回首页
    } else {
        echo "Category deletion failed:" . $conn->error;
    }

    $stmt->close();
    $conn->close();
} else {
    die('No category ID provided.');
}
?>
