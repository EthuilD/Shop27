// 会员门户
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('member-portal')) {
        // 初始化会员门户特定功能
        fetch('api/auth-process.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_orders'
        })
            .then(response => response.json())
            .then(data => {
                if (data === undefined) {
                    console.error('No data received from server');
                    return;
                }
                console.log(data);
                displayOrders(data.orders);
                displayUserInfo(data.userinfo);
            })
            .catch(error => {
                console.error('Error fetching orders:', error);
                alert('Error fetching orders');
            });
    }
});
//tab菜单
let contents = document.querySelectorAll('ul.member .items');
contents.forEach(function (tab) {
    tab.addEventListener('click', function () {
        // 移除所有 tab 的 'current' 类
        contents.forEach(function (tab) {
            tab.classList.remove('current');
        });

        // 为被点击的 tab 添加 'current' 类
        this.classList.add('current');

        // 获取所有的 '.des-item' 元素
        let conItems = document.querySelectorAll('.content-section');

        // 隐藏所有的 '.des-item' 元素
        conItems.forEach(function (item) {
            item.style.display = 'none';
        });

        // 显示与被点击的 tab 对应的 '.des-item' 元素
        let index = Array.from(contents).indexOf(this);
        conItems[index].style.display = 'block';
    });
});
function displayOrders(orders) {
    const ordersContainer = document.querySelector('.orders');
    ordersContainer.innerHTML = '';  // 清空现有的订单内容

    Object.entries(orders).forEach(([order_id, order]) => {
        const orderDiv = document.createElement('div');
        orderDiv.className = 'order';

        const orderHeader = document.createElement('div');
        orderHeader.className = 'order-header';
        orderHeader.innerHTML = `
            <strong>Order ID:</strong> ${order_id}
            <strong>User Name:</strong> ${order.order_info.username}
            <strong>Status:</strong> ${order.order_info.status}
            <strong>Create Date:</strong> ${order.order_info.created_at}
            <strong>Total Price:</strong> ${order.order_info.total_price}
        `;
        orderDiv.appendChild(orderHeader);

        const productsDiv = document.createElement('div');
        productsDiv.className = 'order-products';
        const productHeader = document.createElement('div');
        productHeader.className = 'product-header';
        productHeader.innerHTML = `
            <span>Product ID</span>
            <span>Product Name</span>
            <span>Quantity</span>
            <span>Unit Price</span>
        `;
        productsDiv.appendChild(productHeader);

        order.products.forEach(product => {
            const productDetails = document.createElement('div');
            productDetails.className = 'order-item';
            productDetails.innerHTML = `
                <span>${product.product_id}</span>
                <span>${product.product_name}</span>
                <span>${product.quantity}</span>
                <span>${product.price}</span>
            `;
            productsDiv.appendChild(productDetails);
        });

        orderDiv.appendChild(productsDiv);
        ordersContainer.appendChild(orderDiv);
    });
}
function displayUserInfo(userinfo) {
    if (userinfo && userinfo.email && userinfo.userName) {
        // 更新页面元素显示用户信息
        document.querySelector('h2.username').textContent = userinfo.userName;
        document.querySelector('p.email').textContent = 'Email: ' + userinfo.email;
        document.querySelector('p.username').textContent = 'UserName: ' + userinfo.userName;
    } else {
        console.error('User information is incomplete or missing.');
    }
}