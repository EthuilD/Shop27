<?php
require '../includes/dbconfig.php';
// 获取所有类别
$categoriesQuery = "SELECT * FROM categories";
$categoriesResult = $conn->query($categoriesQuery);

// 获取所有产品
$productsQuery = "SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.catid = c.catid";
$productsResult = $conn->query($productsQuery);
if ($productsResult === false) {
    die("Query failed: " . $conn->error);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shopping Site Admin Panel</title>
    <link rel="stylesheet" href="panelStyle.css">
</head>
<body>
<h1>Category List</h1>
<a href="CreateCategory.php">Create Category</a>
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
            <td><?php echo $category['catid']; ?></td>
            <td><?php echo $category['name']; ?></td>
            <td>
                <a href="updateCategory.php?id=<?php echo $category['catid']; ?>">Update</a>
                <a href="deleteCategory.php?id=<?php echo $category['catid']; ?>">Delete</a>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
<h1>Product List</h1>
<a href="CreateProduct.php">Create Product</a>
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
            <td><?php echo $product['pid']; ?></td>
            <td><?php echo $product['category_name']; ?></td>
            <td><?php echo $product['name']; ?></td>
            <td><?php echo $product['price']; ?></td>
            <td><?php echo $product['description']; ?></td>
            <td><img src="../public_html/uploads/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="thumbnail" /></td>
            <td>
                <a href="updateProduct.php?id=<?php echo $product['pid']; ?>">Update</a>
                <a href="deleteProduct.php?id=<?php echo $product['pid']; ?>">Delete</a>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
</body>
</html>

<?php
$conn->close();
?>
