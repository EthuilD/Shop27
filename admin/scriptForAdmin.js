// 接受一个函数 func 和一个延迟时间 delay 作为参数，并返回一个新的函数。
// 这个返回的函数在被连续调用时会延迟执行传入的 func，直到停止调用它后的 delay 毫秒。
function debounce(func, delay) {
    let debounceTimer;
    return function() {
        const context = this;
        const args = arguments;
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => func.apply(context, args), delay);
    };
}
// 表单提交事件处理
function submitForm(event) {
    event.preventDefault(); // 防止表单实际提交
    // 禁用提交按钮，防止重复提交
    event.target.disabled = true;
}
// 获取表单元素
const form = document.getElementById('productForm');
// 将防抖应用于表单提交处理程序
const debouncedSubmitForm = debounce(submitForm, 1000); // 延迟1秒
// 监听表单的提交事件，并应用防抖
form.addEventListener('submit', debouncedSubmitForm);
