<?php
include "../includes/dbconfig.php";

if (isset($_POST['update'])) {
    // 获取表单数据
    $product_id = $_POST['pid'];
    $category_id = $_POST['catid'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $imagePath = null; // 初始化图片路径变量

    // 如果有新图片上传，处理图片上传逻辑
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        //删除旧图像
        $uploadDirPath = '../public_html/uploads/';
        $fileQuery = "SELECT image FROM products WHERE pid = ?";
        $stmt = $conn->prepare($fileQuery);
        $stmt->bind_param('i', $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        if ($product) {
            $oldImagePath = $uploadDirPath . $product['image'];
            // 检查文件是否存在，并尝试删除它
            if (file_exists($oldImagePath)) {
                $deleted = unlink($oldImagePath);
                if (!$deleted) {
                    // 如果文件删除失败
                    error_log("Unable to delete file: {$oldImagePath}");
                }
            } else {
                // 如果文件不存在
                error_log("File not found: {$oldImagePath}");
            }
        }
        $stmt->close();

        $allowedTypes = ['image/jpeg', 'image/gif', 'image/png'];
        $allowedSize = 10 * 1024 * 1024; // 10 MB
        $imageType = $_FILES['image']['type'];
        $imageSize = $_FILES['image']['size'];

        if (in_array($imageType, $allowedTypes) && $imageSize <= $allowedSize) {
            $imagePath = '../public_html/uploads/' . basename($_FILES['image']['name']);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
                // 文件上传成功
            } else {
                die('File upload failed.');
            }
        } else {
            die('Unsupported file type or file is too large.');
        }
        $imagePath = basename($_FILES['image']['name']);
    }

    // 构建SQL更新语句，如果有图片路径更新，则包含图片路径
    $sql = "UPDATE `products` SET `catid`='$category_id', `name`='$name', `price`='$price', `description`='$description'"
        .($imagePath ? ", `image`='$imagePath'" : "")." WHERE `pid`='$product_id'";

    // 执行查询
    $result = $conn->query($sql);

    // 处理查询结果
    if ($result == TRUE) {
        echo "The record was updated successfully.";
        header('Location: AdminPanel.php'); // 成功后重定向到产品查看页面
    } else {
        echo "Error:" . $sql . "<br>" . $conn->error; // 失败输出错误信息
    }
}

if (isset($_GET['id'])) { // 如果通过GET请求传递了产品ID
    $pid = $_GET['id']; // 获取产品ID
    // 查询产品当前信息
    $sql = "SELECT * FROM products WHERE pid='$pid'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) { // 如果查询到产品信息
        $row = $result->fetch_assoc(); // 获取产品信息
        // 设置变量，用于表单中显示当前值
        $catid = $row['catid'];
        $name = $row['name'];
        $price = $row['price'];
        $description = $row['description'];
        $image = $row['image'];
        $categoriesQuery = "SELECT * FROM categories";
        $categoriesResult = $conn->query($categoriesQuery);
        ?>

        <!-- HTML 表单部分 -->
        <h2>Update Product</h2>
        <form id="productForm" action="" method="post" enctype="multipart/form-data">
            <fieldset>
                <legend>Product Information:</legend>
                Name:<br>
                <input type="text" name="name" value="<?php echo $name; ?>">
                <input type="hidden" name="pid" value="<?php echo $pid; ?>">
                <br>
                Category:<br>
                <select id="catid" name="catid">
                    <?php while($category = $categoriesResult->fetch_assoc()): ?>
                        <option value="<?php echo $category['catid']; ?>"><?php echo $category['name']; ?></option>
                    <?php endwhile; ?>
                </select>
                <br>
                Price:<br>
                <input type="text" name="price" value="<?php echo $price; ?>">
                <br>
                Description:<br>
                <textarea name="description"><?php echo $description; ?></textarea>
                <br>
                Image:<br>
                <input type="file" name="image">
                <br><br>
                <input type="submit" value="Update" name="update" id="submitButton">
            </fieldset>
        </form>
        <script src="scriptForAdmin.js"></script>
        <?php
    } else {
        header('Location: AdminPanel.php'); // 如果没有找到产品，重定向到产品查看页面
    }
}
?>