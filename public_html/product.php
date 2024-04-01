<?php
require '../includes/dbconfig.php';
// 获取URL参数中的产品ID
$pid = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 查询数据库以获取产品详细信息
$sql = "SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.catid = c.catid WHERE pid = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $pid);
$stmt->execute();
$result = $stmt->get_result();

// 检查是否找到产品
if ($result && $result->num_rows > 0) {
    $product = $result->fetch_assoc(); // 获取结果集中的一行数据
} else {
    die('Product not found!');
}// 如果没有找到产品，输出错误消息并退出脚本
?>

<!--1155197473-->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product 1 - My Shopping Site</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="shopping-list-container">
    <div class="shopping-list-toggle">Shopping List</div>
    <div class="shopping-list-details">
        <ul id="shopping-list"></ul>
        <div class="checkout">
            <span class="total-price">Total: $0.00</span>
            <button class="checkout-button">Checkout</button>
        </div>
    </div>
</div>

<header>
    <h3>My Shopping Site</h3>
    <nav>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="#"><?php echo $product['category_name']; ?></a></li>
            <li><?php echo $product['name']; ?></li>
        </ul>
    </nav>
</header>

<div id="category-list">
    <h3>Categories</h3>
    <ul>
        <li class="category-item" data-category="all">All</li>
    </ul>
</div>

<section class="product-container">
    <div class="product-image">
        <img src="uploads/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
    </div>
    <div class="product-info" data-pid="<?php echo $product['pid']; ?>">
        <h1><?php echo $product['name']; ?></h1>
        <h2 class="product-price"><?php echo $product['price']; ?> $</h2>
        <button class="addToCart">Add to Cart</button>
    </div>
    <div class="product-description">
        <div class="tab-list">
            <ul>
                <li class="current">Details</li>
                <li>Specifications</li>
            </ul>
        </div>
        <div class="tab-con">
            <p class="des-item" style="display: block"><?php echo $product['description']; ?></p>
            <p class="des-item"> Specifications of <?php echo $product['name']; ?></p>
        </div>
    </div>
</section>

<footer>
    <p>1155197473 LIAN Jialu</p>
</footer>

<script src="myScript.js"></script>

</body>
</html>