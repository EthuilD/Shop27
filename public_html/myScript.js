//1155197473
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
                productElement.innerHTML = `
          <a href="product.php?id=${product.pid}">
            <img src="uploads/${product.image}" alt="${product.name}">
            <h2>${product.name}</h2>
          </a>
          <p>$${product.price}</p>
          <button>Add to Cart</button>
        `;
                productsElement.appendChild(productElement);
            });
        });
}

// 页面加载时，更新类别列表
document.addEventListener('DOMContentLoaded', () => {
    updateCategories();
});

function updateBreadcrumb(category) {
    const breadcrumb = document.getElementById('breadcrumb');
    breadcrumb.textContent = category;
}

