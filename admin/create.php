<?php
include "../includes/dbconfig.php";
function generateNonce() {
    $nonce = bin2hex(random_bytes(16));
    $_SESSION['nonce'] = $nonce;
    return $nonce;
}
// 验证Nonce
function verifyNonce($receivedNonce) {
    if (isset($_SESSION['nonce']) && $receivedNonce === $_SESSION['nonce']) {
        unset($_SESSION['nonce']); // 验证后即销毁
        return true;
    }
    return false;
}
function test_input($data){
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
//会话开始
session_start();
$action = isset($_GET['action']) ? $_GET['action'] : '';
// 确保PHP代码正确地处理用户输入
$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);

if (isset($_POST['createCategory'])){
    if (!verifyNonce($_POST['nonce'])) {
        die('CSRF detection failed.');
    }
    // 获取表单数据
    $name = test_input($_POST['name']);

    // 构建SQL插入语句
    $sql = "INSERT INTO `categories` (`name`) VALUES (?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $name);
    $result = $stmt->execute();

    // 处理查询结果
    if ($result == TRUE) {
        echo "New category created successfully.";
        header('Location: AdminPanel.php'); // 成功后重定向到产品查看页面
    } else {
        echo "Error:" . $sql . "<br>" . $conn->error; // 失败输出错误信息
    }
}else if(isset($_POST['createProduct'])){
    if (!verifyNonce($_POST['nonce'])) {
        die('CSRF detection failed.');
    }
    // 获取表单数据
    $catid = test_input($_POST['catid']);
    $name = test_input($_POST['name']);
    $price = test_input($_POST['price']);
    $description = test_input($_POST['description']);
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
        $uploadDirPath = '../public_html/uploads/';
        $allowedTypes = ['image/jpeg', 'image/gif', 'image/png'];
        $allowedSize = 10 * 1024 * 1024; // 10 MB
        $imageType = $_FILES['image']['type'];
        $imageSize = $_FILES['image']['size'];

        if (in_array($imageType, $allowedTypes) && $imageSize <= $allowedSize) {
            $imageExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $newFileName = uniqid('img_', true) . '.' . $imageExtension;
            $imagePath = $uploadDirPath . $newFileName;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
                // 文件上传成功
                $imagePath = $newFileName;
            } else {
                die('File upload failed.');
            }
        } else {
            die('Unsupported file type or file is too large.');
        }
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
$nonce = generateNonce();
// 根据'action'参数的值来决定加载哪个部分的代码
if ($action == 'create_category') {
    // 加载创建类别的HTML表单
    ?>
    <h2>Create New Category</h2>
    <form action="" method="post">
        <input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce); ?>">
        <fieldset>
            <legend>Category Information:</legend>
            Name:<br>
            <input type="text" name="name" required minlength="1" maxlength="100">
            <br>
            <input type="submit" value="Create" name="createCategory" id="submitButton">
        </fieldset>
    </form>
    <script src="scriptForAdmin.js"></script>
    <?php
} elseif ($action == 'create_product') {
    $sql = "SELECT * FROM categories";
    $result = $conn->query($sql);
    $categories = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
    // 加载创建产品的HTML表单
    ?>
    <h2>Create New Product</h2>
    <form id="productForm" action="" method="post" enctype="multipart/form-data">
        <input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce); ?>">
        <fieldset>
            <legend>Product Information:</legend>
            Name:<br>
            <input type="text" name="name" required minlength="1" maxlength="200">
            <br>
            Category:<br>
            <select name="catid" required>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo htmlspecialchars($category['catid']); ?>">
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <br>
            Price:<br>
            <input type="text" name="price" required pattern="^\d+(\.\d{1,2})?$">
            <br>
            Description:<br>
            <textarea name="description"></textarea>
            <br>
            Image:<br>
            <input type="file" name="image" accept="image/png, image/jpeg">
            <br><br>
            <input type="submit" value="Create" name="createProduct" id="submitButton">
        </fieldset>
    </form>
    <script src="scriptForAdmin.js"></script>
    <?php
} else {
    // 默认情况或显示错误消息
    echo '<p>Please select an action.</p>';
}
