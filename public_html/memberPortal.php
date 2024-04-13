<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'api/auth-process.php';
$username = $_SESSION['username'];
$userid = $_SESSION['userid'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Site</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body id="member-portal">
<div class="shopping-list-container">
    <div class="shopping-list-toggle">Shopping List</div>
    <!-- Enclose the shopping list within a form element -->
    <form id="paypal-cart-form">
        <div class="shopping-list-details">
            <ul id="shopping-list"></ul>
            <div class="checkout">
                <span class="total-price">Total: $0.00</span>
                <!-- Checkout button of type submit -->
                <button type="submit" class="checkout-button">Checkout via PayPal</button>
            </div>
        </div>
    </form>
</div>

<header>
    <h3>My Shopping Site</h3>
    <nav>
        <ul>
            <li><a href="index.php" id="nav-home">Home</a></li>
        </ul>
    </nav>
</header>

<div id="sidebar">
    <div class="user-info">
        <h2 class="username"></h2>
        <p>Welcome!</p>
    </div>
    <ul class="member user-info">
        <li class="items">Account</li>
        <li class="items">Historical Orders</li>
        <li>
            <form method="POST" action="api/auth-process.php">
                <button type="submit" name="logout">Logout</button>
            </form>
        </li>
    </ul>
</div>
<div id="main-content">
    <div id="profile" class="content-section" style="display: block;">
        <h3>Account Information</h3>
        <p class="email">Email: </p>
        <p class="username">UserName: </p>
        <button><a href="changePassword.php">Change Password</a></button>
    </div>
    <div id="orders" class="content-section">
        <h3>Historical Orders</h3>
        <!-- 历史订单信息 -->
        <div class="orders">
        </div>
    </div>
</div>
<script src="memberScript.js"></script>
</body>