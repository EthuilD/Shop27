//1155197473

// 页面加载时，更新类别列表
document.addEventListener('DOMContentLoaded', () => {
    updateCategories();
    loadShoppingList();
});
document.addEventListener('click', function (event) {
    if (event.target.classList.contains('addToCart')) {
        // 尝试从最近的.product元素中获取产品信息
        const productElement = event.target.closest('.product');
        // 如果在.product中找不到，尝试从.product-info元素中获取
        const productInfoElement = event.target.closest('.product-info');

        // 确定使用哪个元素来获取产品 ID
        const targetElement = productElement || productInfoElement;

        if (targetElement) {
            const pid = targetElement.getAttribute('data-pid');
            if (pid) {
                addProductToList(pid, 1);
                updateQuantity(pid, 1); // 添加到购物车的默认数量为1
            } else {
                console.error('Product ID not found');
            }
        } else {
            console.error('Target element for product not found');
        }
    }
});

// 获取类别列表并更新页面
function updateCategories() {
    fetch('api/get_categories.php')
        .then(response => response.json())
        .then(data => {
            const categoryListElement = document.getElementById('category-list');
            const ulElement = categoryListElement.querySelector('ul');
            ulElement.innerHTML = ''; // 清空现有的列表
            data.forEach(category => {
                const liElement = document.createElement('li');
                liElement.className = 'category-item';
                liElement.textContent = category.name;
                liElement.setAttribute('data-category', category.catid);
                liElement.onclick = () => {
                    loadProducts(category.catid);
                    updateBreadcrumb(category.name); // 更新面包屑
                };
                ulElement.appendChild(liElement);
            });
        });
}

// 根据类别ID获取产品列表并更新页面
function loadProducts(catid) {
    fetch(`api/get_products.php?catid=${catid}`)
        .then(response => response.json())
        .then(data => {
            const productsElement = document.querySelector('.products');
            productsElement.innerHTML = ''; // 清空现有的列表
            data.forEach(product => {
                const productElement = document.createElement('div');
                productElement.className = 'product';
                productElement.setAttribute('data-pid', product.pid);
                productElement.setAttribute('data-category', product.catid);

                const linkElement = document.createElement('a');
                linkElement.href = `product.php?id=${product.pid}`;

                const imgElement = document.createElement('img');
                imgElement.src = `uploads/${product.image}`;
                imgElement.alt = product.name;

                const h2Element = document.createElement('h2');
                h2Element.textContent = product.name;

                const pElement = document.createElement('p');
                pElement.textContent = `$${product.price}`;

                const buttonElement = document.createElement('button');
                buttonElement.className = 'addToCart';
                buttonElement.textContent = 'Add to Cart';

                linkElement.appendChild(imgElement);
                linkElement.appendChild(h2Element);
                productElement.appendChild(linkElement);
                productElement.appendChild(pElement);
                productElement.appendChild(buttonElement);

                productsElement.appendChild(productElement);
            });
        });
}

function updateBreadcrumb(category) {
    const breadcrumb = document.getElementById('breadcrumb');
    breadcrumb.textContent = category;
}

function loadShoppingList() {
    const shoppingList = JSON.parse(localStorage.getItem('shoppingList')) || {};
    for (const pid in shoppingList) {
        addProductToList(pid, shoppingList[pid]);
    }
    updateTotal(); // 更新总金额
}

// 新增：添加商品到购物清单
function addProductToList(pid, quantity) {
    fetch(`api/get_products.php?pid=${pid}`) // 这个API返回商品的name和price
        .then(response => response.json())
        .then(data => {
            const shoppingListElement = document.getElementById('shopping-list');
            let productElement = shoppingListElement.querySelector(`li[data-pid="${pid}"]`);
            if (!productElement) {
                productElement = document.createElement('li');
                productElement.setAttribute('data-pid', pid);
                productElement.innerHTML = `
                    <span class="item-name">${data.name}</span>
                    <input class="item-quantity" type="number" value="${quantity}">
                    <span class="item-price">$${data.price}</span>
                    <button class="remove-item">Remove</button>
                `;
                shoppingListElement.appendChild(productElement);
            } else {
                const inputElement = productElement.querySelector('.item-quantity');
                inputElement.value = quantity;
            }
            updateTotal(); // 更新总金额

            // 更新数量的事件监听器
            const inputElement = productElement.querySelector('.item-quantity');
            inputElement.addEventListener('change', function () {
                updateQuantity(pid, this.value);
            });

            // 移除商品的事件监听器
            const removeButton = productElement.querySelector('.remove-item');
            removeButton.addEventListener('click', function () {
                removeProductFromList(pid);
            });
        });
}

// 新增：更新数量
function updateQuantity(pid, quantity) {
    const shoppingList = JSON.parse(localStorage.getItem('shoppingList')) || {};
    if (quantity > 0) {
        shoppingList[pid] = quantity;
    } else {
        delete shoppingList[pid];
    }
    localStorage.setItem('shoppingList', JSON.stringify(shoppingList));
    updateTotal(); // 更新总金额
}

// 新增：移除商品
function removeProductFromList(pid) {
    document.querySelector(`li[data-pid="${pid}"]`).remove();
    updateQuantity(pid, 0);
}

// 新增：更新总金额
function updateTotal() {
    let total = 0;
    document.querySelectorAll('#shopping-list li').forEach(item => {
        const price = parseFloat(item.querySelector('.item-price').textContent.replace('$', ''));
        const quantity = parseInt(item.querySelector('.item-quantity').value);
        total += price * quantity;
    });
    document.querySelector('.total-price').textContent = `Total: $${total.toFixed(2)}`;
}
// 更新购物车表单以包含商品详情
function updateCartForm() {
    const shoppingListElement = document.getElementById('shopping-list');
    let idx = 1; // PayPal 要求项的索引从 1 开始
    shoppingListElement.querySelectorAll('li').forEach(item => {
        const pid = item.getAttribute('data-pid');
        const name = item.querySelector('.item-name').textContent;
        const quantity = item.querySelector('.item-quantity').value;
        const price = item.querySelector('.item-price').textContent.replace('$', '');

        // 创建隐藏的表单字段并添加到表单中
        const form = document.getElementById('paypal-cart-form');
        addHiddenInput(form, `item_name_${idx}`, name);
        addHiddenInput(form, `quantity_${idx}`, quantity);
        addHiddenInput(form, `amount_${idx}`, price);

        idx++;
    });
}

// 辅助函数：向表单添加隐藏的输入字段
function addHiddenInput(form, name, value) {
    let input = form.querySelector(`input[name="${name}"]`);
    if (!input) {
        input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        form.appendChild(input);
    }
    input.value = value;
}
// 购物车表单提交
document.querySelector('#paypal-cart-form').addEventListener('submit', function(event) {
    event.preventDefault(); // 阻止表单默认提交
    updateCartForm(); // 更新购物车表单
    // 收集购物车中的所有商品信息
    const items = document.querySelectorAll('#shopping-list li');
    const formData = {
        items: []
    };

    items.forEach((item, index) => {
        const pid = item.getAttribute('data-pid');
        const quantity = item.querySelector('.item-quantity').value;
        if (quantity > 0) {
            formData.items.push({pid: pid, quantity: quantity});
        }
    });

    // 通过AJAX向服务器传递产品ID和数量
    fetch('api/create_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData),
    })
        .then(response => response.json())
        .then(data => {
            // 服务器应该返回invoice和custom字段
            if (data.invoice && data.custom) {
                // 添加隐藏的invoice和custom字段到表单中
                const invoiceInput = document.createElement('input');
                invoiceInput.type = 'hidden';
                invoiceInput.name = 'invoice';
                invoiceInput.value = data.invoice;

                const customInput = document.createElement('input');
                customInput.type = 'hidden';
                customInput.name = 'custom';
                customInput.value = data.custom;

                const form = document.getElementById('paypal-cart-form');
                form.appendChild(invoiceInput);
                form.appendChild(customInput);

                // 清空客户端购物车
                localStorage.removeItem('shoppingList');
                document.getElementById('shopping-list').innerHTML = '';

                // 程序化提交表单到PayPal
                form.submit();
            }
        });
});


//以下为无限滚动代码，!整合到分页操作中
let page = 0;
const productsElement = document.querySelector('.products');
const endOfPageThreshold = window.innerHeight * 0.2; // 当用户滚动到距离底部20%时加载更多

//节流函数
function throttle(func, limit) {
    let inThrottle; //跟踪节流状态，节流发生时为true
    return function () {
        const args = arguments;
        const context = this;
        if (!inThrottle) {  //不在节流状态时执行
            func.apply(context, args); //apply 方法调用原函数 func
            inThrottle = true; //节流开始
            setTimeout(() => inThrottle = false, limit); //在指定的 limit 时间后，将 inThrottle 重置为 false
        }
    }
}

const throttledScrollHandler = throttle(() => {
    if ((window.innerHeight + window.pageYOffset) >= document.body.offsetHeight - endOfPageThreshold) {
        loadMoreProducts();
    }
}, 200);

// 使用节流的滚动事件处理器
window.addEventListener('scroll', throttledScrollHandler);

function loadMoreProducts() {
    page++;
    fetch(`api/get_products.php?page=${page}`)
        .then(response => response.json())
        .then(products => {
            if (products.length) {
                products.forEach(product => {
                    const productElement = document.createElement('div');
                    productElement.className = 'product';
                    productElement.setAttribute('data-pid', product.pid);
                    productElement.setAttribute('data-category', product.catid);
                    productElement.innerHTML = `
          <a href="product.php?id=${product.pid}">
            <img src="uploads/${product.image}" alt="${product.name}">
            <h2>${product.name}</h2>
          </a>
          <p>$${product.price}</p>
          <button class="addToCart">Add to Cart</button>
        `;
                    productsElement.appendChild(productElement);
                });
            } else {
                // 如果没有更多产品，则移除滚动事件监听以停止尝试加载更多
                window.removeEventListener('scroll', loadMoreProducts);
            }
        })
        .catch(error => {
            console.error('Error loading more products:', error);
        });
}
//商品详情页的tab菜单
let tabs = document.querySelectorAll('.tab-list li');
// 为每个 li 元素添加点击事件监听器
tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
        // 移除所有 tab 的 'current' 类
        tabs.forEach(function (tab) {
            tab.classList.remove('current');
        });

        // 为被点击的 tab 添加 'current' 类
        this.classList.add('current');

        // 获取所有的 '.des-item' 元素
        let desItems = document.querySelectorAll('.des-item');

        // 隐藏所有的 '.des-item' 元素
        desItems.forEach(function (item) {
            item.style.display = 'none';
        });

        // 显示与被点击的 tab 对应的 '.des-item' 元素
        let index = Array.from(tabs).indexOf(this);
        desItems[index].style.display = 'block';
    });
});