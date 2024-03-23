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

if (isset($_POST['update'])) {
    if (!verifyNonce($_POST['nonce'])) {
        die('CSRF detection failed.');
    }
    // 获取表单数据
    $product_id = test_input($_POST['pid']);
    $category_id = test_input($_POST['catid']);
    $name = test_input($_POST['name']);
    $price = test_input($_POST['price']);
    $description = test_input($_POST['description']);
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
            // 生成随机文件名以避免文件名冲突和安全风险
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
    }

    // 构建参数化的SQL更新语句
    $sql = "UPDATE `products` SET `catid`=?, `name`=?, `price`=?, `description`=?" . ($imagePath ? ", `image`=?" : "") . " WHERE `pid`=?";
    $stmt = $conn->prepare($sql);
    $params = [$category_id, $name, $price, $description];
    if ($imagePath) {
        $params[] = $imagePath;
    }
    $params[] = $product_id;

    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    $result = $stmt->execute();

    // 处理查询结果
    if ($result == TRUE) {
        echo "The record was updated successfully.";
        header('Location: AdminPanel.php'); // 成功后重定向到产品查看页面
    } else {
        error_log("Error:" . $conn->error); // 将错误记录到日志
        echo "An error occurred. Please try again."; // 向用户显示通用错误消息
    }
}

if (isset($_GET['pid'])) { // 如果通过GET请求传递了产品ID
    $pid = $_GET['pid']; // 获取产品ID
    // 查询产品当前信息
    $stmt = $conn->prepare("SELECT * FROM products WHERE pid=?");
    $stmt->bind_param("i",$pid);
    $stmt->execute();
    $result = $stmt->get_result();

    $nonce = generateNonce();

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
            <input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce); ?>">
            <fieldset>
                <legend>Product Information:</legend>
                Name:<br>
                <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" required minlength="1" maxlength="200">
                <input type="hidden" name="pid" value="<?php echo htmlspecialchars($pid); ?>">
                <br>
                Category:<br>
                <select id="catid" name="catid" required>
                    <?php while($category = $categoriesResult->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($category['catid']); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                    <?php endwhile; ?>
                </select>
                <br>
                Price:<br>
                <input type="text" name="price" value="<?php echo htmlspecialchars($price); ?> " required pattern="^\d+(\.\d{1,2})?$">
                <br>
                Description:<br>
                <textarea name="description"><?php echo htmlspecialchars($description); ?></textarea>
                <br>
                Image:<br>
                <input type="file" name="image" accept="image/png, image/jpeg">
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