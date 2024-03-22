//1155197473

// 页面加载时，更新类别列表
document.addEventListener('DOMContentLoaded', () => {
    updateCategories();
    loadShoppingList();
});
document.addEventListener('click', function(event) {
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
            console.log(data);
            const productsElement = document.querySelector('.products');
            productsElement.innerHTML = ''; // 清空现有的列表
            data.forEach(product => {
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
            inputElement.addEventListener('change', function() {
                updateQuantity(pid, this.value);
            });

            // 移除商品的事件监听器
            const removeButton = productElement.querySelector('.remove-item');
            removeButton.addEventListener('click', function() {
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

//以下为无限滚动代码，!整合到分页操作中
let page = 0;
const productsElement = document.querySelector('.products');
const endOfPageThreshold = 300; // 当用户滚动到距离底部300px时加载更多

//节流函数
function throttle(func, limit) {
    let inThrottle; //跟踪节流状态，节流发生时为true
    return function() {
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
            console.log(products);
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

