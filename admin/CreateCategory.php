<?php
include "../includes/dbconfig.php";

if (isset($_POST['create'])) {
    // 获取表单数据
    $name = $_POST['name'];

    // 构建SQL插入语句
    $sql = "INSERT INTO `categories` (`name`) VALUES ('$name')";

    // 执行查询
    $result = $conn->query($sql);

    // 处理查询结果
    if ($result == TRUE) {
        echo "New category created successfully.";
        header('Location: AdminPanel.php'); // 成功后重定向到产品查看页面
    } else {
        echo "Error:" . $sql . "<br>" . $conn->error; // 失败输出错误信息
    }
}
?>

<!-- HTML 表单部分 -->
<h2>Create New Category</h2>
<form action="" method="post">
    <fieldset>
        <legend>Category Information:</legend>
        Name:<br>
        <input type="text" name="name">
        <br>
        <input type="submit" value="Create" name="create">
    </fieldset>
</form>