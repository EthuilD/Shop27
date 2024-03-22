<?php
require '../../includes/dbconfig.php';

// 设置返回的内容类型为JSON
header('Content-Type: application/json');

$limit = 4; // 每页显示的产品数量
$catid = isset($_GET['catid']) ? intval($_GET['catid']) : null;
$pid = isset($_GET['pid']) ? intval($_GET['pid']) : null;
$page = isset($_GET['page']) ? intval($_GET['page']) : 0;
$offset = $page * $limit;

if ($pid !== null) {
    // 获取单个产品的信息
    $stmt = $conn->prepare("SELECT * FROM products WHERE pid = ? ");
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
    $stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.catid = c.catid WHERE p.catid = ?");
    $stmt->bind_param("i", $catid);
    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        echo json_encode($products);
    } else {
        // 如果分类下没有产品，返回提示信息
        echo json_encode(['message' => 'No products found in this category']);
    }
    $stmt->close();
} elseif ($page >= 0) {
    // 获取所有产品的分页信息
    $productsQuery = "SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.catid = c.catid LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($productsQuery);
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $productsResult = $stmt->get_result();

    $products = [];
    while ($product = $productsResult->fetch_assoc()) {
        $products[] = $product;
    }
    echo json_encode($products);
} else {
    // 如果没有提供任何有效参数，返回错误信息
    echo json_encode(['error' => 'No valid parameter provided']);
}

$conn->close();
?>