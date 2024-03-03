<?php
require '../../includes/dbconfig.php';
// 查询所有类别
$sql = "SELECT catid, name FROM categories";
$result = $conn->query($sql);

$categories = array();
if ($result->num_rows > 0) {
    // 输出每行数据
    while($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    echo json_encode($categories);
} else {
    echo "0 results";
}
$conn->close();
?>
