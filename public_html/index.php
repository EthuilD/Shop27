<!--1155197473-->
<!--类别下没有商品时 点击该类别不会切换-->
<?php
require '../includes/dbconfig.php';
$limit = 4; // 每页显示的产品数量
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Site</title>
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
            <li><a href="index.php" id="nav-home">Home</a></li>
            <li id="breadcrumb"></li>
        </ul>
    </nav>
</header>
<!-- Category List -->
<div id="category-list">
    <h3>Categories</h3>
    <ul>
        <li class="category-item" data-category="All">All</li>
    </ul>
</div>

<section class="products">
    <?php
    $stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.catid = c.catid LIMIT ?");
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $productsResult = $stmt->get_result();
    while ($product = $productsResult->fetch_assoc()):
    ?>

    <div class="product" data-pid="<?php echo htmlspecialchars($product['pid'], ENT_QUOTES, 'UTF-8'); ?>" data-category="<?php echo htmlspecialchars($product['category_name'], ENT_QUOTES, 'UTF-8'); ?>">
        <a href="product.php?id=<?php echo htmlspecialchars($product['pid'], ENT_QUOTES, 'UTF-8'); ?>">
            <img src="uploads/<?php echo htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>">
            <h2><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
        </a>
        <p><?php echo htmlspecialchars($product['price'], ENT_QUOTES, 'UTF-8'); ?>$</p>
        <button class="addToCart">Add to Cart</button>
    </div>

    <?php endwhile; ?>
</section>

<footer>
    <p>1155197473 LIAN Jialu</p>
    <a href="../admin/AdminPanel.php">Temporary administrator channel for test</a>
</footer>

<script src="myScript.js"></script>

</body>
</html>