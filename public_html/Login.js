document.addEventListener('DOMContentLoaded', () => {
    startApp();
});

// Google登录处理
function startApp() {
    google.accounts.id.initialize({
        client_id: '49074735272-2f27760hq276vqj6per693ja766bmm0g.apps.googleusercontent.com',
        callback: handleCredentialResponse
    });
    google.accounts.id.renderButton(
        document.getElementById('signInButton'),  // 确保您有一个放置按钮的容器
        { theme: 'outline', size: 'large' }  // 自定义按钮样式
    );
}

function handleCredentialResponse(response) {
    //console.log('ID Token: ' + response.credential);

    // 使用 fetch API 发送 ID 令牌到后端处理
    fetch('api/auth-process.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=google_login&idtoken=' + encodeURIComponent(response.credential)
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                if (data.redirect) {
                    window.location.href = data.redirect;  // 执行重定向
                }
            } else {
                // 处理错误情况
                console.error('Error:', data.message);
            }
        })
        .catch(error => {
            console.error('Error parsing JSON from backend:', error);
        });
}