
<?php
include "../includes/dbconfig.php"; // 包含数据库配置文件
// 查询所有类别
$sql = "SELECT * FROM categories";
$result = $conn->query($sql);
$categories = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

if (isset($_POST['create'])) {
    // 获取表单数据
    $catid = $_POST['catid'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $imagePath = null; // 初始化图片路径变量
    //输入验证部分
    if (empty($name)) {
        $errors[] = 'The name of the product cannot be empty.';
    }
    // 验证价格是否为有效数字
    if (!is_numeric($price) || floatval($price) < 0) {
        $errors[] = 'Invalid price. Price must be a number and cannot be negative.';
    }
    if (empty($description)) {
        $errors[] = 'The description of the product cannot be empty.';
    }

    // 如果有新图片上传，处理图片上传逻辑
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
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
    } else {
        $imagePath = ''; // 如果没有上传图片，设置为空字符串
    }

    // 构建SQL插入语句
    $stmt = $conn->prepare("INSERT INTO products (catid, name, price, description, image) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isdss",$catid, $name, $price, $description, $imagePath);
    $result = $stmt->execute();

    // 处理查询结果
    if ($result == TRUE) {
        echo "New product created successfully.";
        header('Location: AdminPanel.php'); // 成功后重定向到产品查看页面
    } else {
        echo "Error: " . $stmt->error; // 失败输出错误信息
    }
    $stmt->close();
}
?>

<!-- HTML 表单部分 -->
<h2>Create New Product</h2>
<form id="productForm" action="" method="post" enctype="multipart/form-data">
    <fieldset>
        <legend>Product Information:</legend>
        Name:<br>
        <input type="text" name="name">
        <br>
        Category:<br>
        <select name="catid">
            <?php foreach ($categories as $category): ?>
                <option value="<?php echo $category['catid']; ?>">
                    <?php echo $category['name']; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <br>
        Price:<br>
        <input type="text" name="price">
        <br>
        Description:<br>
        <textarea name="description"></textarea>
        <br>
        Image:<br>
        <input type="file" name="image">
        <br><br>
        <input type="submit" value="Create" name="create" id="submitButton">
    </fieldset>
</form>
<script src="scriptForAdmin.js"></script>