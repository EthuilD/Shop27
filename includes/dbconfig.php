<?php
$servername = "localhost";
$username = "shop27";
$password = "123456";
$dbname = "shop27";

// 创建连接
$conn = new mysqli($servername, $username, $password, $dbname);

// 检测连接
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>