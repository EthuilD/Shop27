<?php
include "../includes/dbconfig.php";

if (isset($_POST['update'])) {
    // 获取表单数据
    $category_id = $_POST['catid'];
    $name = $_POST['name'];

    // 构建SQL更新语句，如果有图片路径更新，则包含图片路径
    $stmt = $conn->prepare("UPDATE `categories` SET `name` = ? WHERE `catid` = ?");
    $stmt->bind_param("si",$name, $category_id); // 's' 指定 $name 为字符串，'i' 指定 $category_id 为整数
    // 执行查询
    $result = $stmt->execute();

    // 处理查询结果
    if ($result == TRUE) {
        echo "The record was updated successfully.";
        header('Location: AdminPanel.php'); // 成功后重定向到产品查看页面
    } else {
        echo "Error: " . $stmt->error; // 失败输出错误信息
    }
    $stmt->close();
}

if (isset($_GET['id'])) { // 如果通过GET请求传递了产品ID
    $catid = $_GET['id']; // 获取产品ID
    // 查询产品当前信息
    $stmt = $conn->prepare("SELECT * FROM categories WHERE catid = ?");
    $stmt->bind_param("i", $catid); // 'i' 指定 $catid 为整数
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) { // 如果查询到产品信息
        $row = $result->fetch_assoc(); // 获取产品信息
        // 设置变量，用于表单中显示当前值
        $catid = $row['catid'];
        $name = $row['name'];
        ?>

        <!-- HTML 表单部分 -->
        <h2>Update Category</h2>
        <form id="productForm" action="" method="post">
            <fieldset>
                <legend>Category Information:</legend>
                Name:<br>
                <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>">
                <input type="hidden" name="catid" value="<?php echo htmlspecialchars($catid); ?>">
                <br>
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
