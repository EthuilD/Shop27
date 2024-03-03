<?php
require '../../includes/dbconfig.php';

$catid = isset($_GET['catid']) ? intval($_GET['catid']) : 0;

// 查询对应类别的产品
$sql = "SELECT * FROM products WHERE catid = {$catid}";
$result = $conn->query($sql);

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
$conn->close();
?>