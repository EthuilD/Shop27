<!--1155197473-->
<!--类别下没有商品时 点击该类别不会切换-->
<?php
require '../includes/dbconfig.php';
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
    $productsQuery = "SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.catid = c.catid";
    $productsResult = $conn->query($productsQuery);
    while ($product = $productsResult->fetch_assoc()):
    ?>

    <div class="product" data-pid="<?php echo $product['pid']; ?>" data-category="<?php echo $product['category_name']; ?>">
        <a href="product.php?id=<?php echo $product['pid']; ?>">
            <img src="uploads/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
            <h2><?php echo $product['name']; ?></h2>
        </a>
        <p><?php echo $product['price']; ?>$</p>
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