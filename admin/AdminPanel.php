<?php
require_once 'admin-main.php';
// 准备数据以供显示
$categoriesResult = getCategories($conn);
$productsResult = getProducts($conn);
$orders = fetchOrdersWithProducts($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shopping Site Admin Panel</title>
    <link rel="stylesheet" href="panelStyle.css">
</head>
<body>
<h1>Shopping Site Admin Panel</h1>
<nav>
    <ul>
        <li><a href="#categoryList">Category List</a></li>
        <li><a href="#productList">Product List</a></li>
        <li><a href="#orderList">Order List</a></li>
    </ul>
</nav>
<h2 id="categoryList">Category List</h2>
<a href="create.php?action=create_category">Create Category</a>
<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Operation</th>
    </tr>
    </thead>
    <tbody>
    <?php while($category = $categoriesResult->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($category['catid']); ?></td>
            <td><?php echo htmlspecialchars($category['name']); ?></td>
            <td>
                <form action="updateCategory.php" method="get" style="display: inline;">
                    <input type="hidden" name="catid" value="<?php echo htmlspecialchars($category['catid']); ?>">
                    <input type="submit" class="button-link update-button" value="Update">
                </form>
                <form action="delete.php" method="post" style="display: inline;">
                    <input type="hidden" name="type" value="category">
                    <input type="hidden" name="catid" value="<?php echo htmlspecialchars($category['catid']); ?>">
                    <input type="submit" class="button-link delete-button" value="Delete">
                </form>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
<h2 id="productList">Product List</h2>
<a href="create.php?action=create_product">Create Product</a>
<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>Category</th>
        <th>Name</th>
        <th>Price</th>
        <th>Description</th>
        <th>Image</th>
        <th>Operation</th>
    </tr>
    </thead>
    <tbody>
    <?php while($product = $productsResult->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($product['pid']); ?></td>
            <td><?php echo htmlspecialchars($product['category_name']); ?></td>
            <td><?php echo htmlspecialchars($product['name']); ?></td>
            <td><?php echo htmlspecialchars($product['price']); ?></td>
            <td><?php echo htmlspecialchars($product['description']); ?></td>
            <!--服务器中，此处路径改为/uploads/-->
            <td><img src="/uploads/<?php echo htmlspecialchars($product['image']); ?>"
                     alt="<?php echo htmlspecialchars($product['name']); ?>" class="thumbnail" /></td>
            <td>
                <form action="updateProduct.php" method="get" style="display: inline;">
                    <input type="hidden" name="pid" value="<?php echo htmlspecialchars($product['pid']); ?>">
                    <input type="submit" class="button-link update-button" value="Update">
                </form>
                <form action="delete.php" method="post" style="display: inline;">
                    <input type="hidden" name="type" value="product">
                    <input type="hidden" name="pid" value="<?php echo htmlspecialchars($product['pid']); ?>">
                    <input type="submit" class="button-link delete-button" value="Delete">
                </form>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<h2 id="orderList">Order List</h2>
<table>
    <?php foreach ($orders as $order_id => $order): ?>
        <tr>
            <td colspan="5">
                <strong>Order ID:</strong> <?= htmlspecialchars($order_id) ?> <br>
                <strong>User ID:</strong> <?= htmlspecialchars($order['order_info']['userid']) ?> <br>
                <strong>User Name:</strong> <?= htmlspecialchars($order['order_info']['username']) ?> <br>
                <strong>Status:</strong> <?= htmlspecialchars($order['order_info']['status']) ?> <br>
                <strong>Create Date:</strong> <?= htmlspecialchars($order['order_info']['created_at']) ?> <br>
                <strong>Total Price:</strong> <?= htmlspecialchars($order['order_info']['total_price']) ?> <br>
                <table class="product-table">
                    <tr>
                        <th>Product ID</th>
                        <th>Product Name</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                    </tr>
                    <?php foreach ($order['products'] as $product): ?>
                        <tr>
                            <td><?= htmlspecialchars($product['product_id']) ?></td>
                            <td><?= htmlspecialchars($product['product_name']) ?></td>
                            <td><?= htmlspecialchars($product['quantity']) ?></td>
                            <td><?= htmlspecialchars($product['price']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

</body>
</html>

<?php
$conn->close();
?>
