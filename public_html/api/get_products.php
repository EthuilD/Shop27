<?php
require '../../includes/dbconfig.php';

$catid = isset($_GET['catid']) ? intval($_GET['catid']) : 0;
$pid = isset($_GET['pid']) ? intval($_GET['pid']) : null;

if ($pid !== null) {
    // 获取单个产品的信息
    $stmt = $conn->prepare("SELECT * FROM products WHERE pid = ?");
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // 如果找到产品，返回产品信息
        echo json_encode($row);
    } else {
        // 如果没有找到产品，返回错误信息
        echo json_encode(['error' => 'Product not found']);
    }
    $stmt->close();

} elseif ($catid !== null) {
    // 获取特定类别下的所有产品
    $stmt = $conn->prepare("SELECT * FROM products WHERE catid = ?");
    $stmt->bind_param("i", $catid);
    $stmt->execute();
    $result = $stmt->get_result();

    $products = array();
    if ($result->num_rows > 0) {
        // 输出每行数据
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        echo json_encode($products);
    } else {
        echo "0 results";
    }
} else {
    // 如果没有提供任何有效参数，返回错误信息
    echo json_encode(['error' => 'No valid parameter provided']);
}
$conn->close();
?>