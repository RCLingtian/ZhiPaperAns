// 初始化点击次数（从localStorage读取）
let clickCount = localStorage.getItem('updateClickCount') ? Number(localStorage.getItem('updateClickCount')) : 0;
// 限制访问的Cookie名称
const BLOCK_COOKIE_NAME = 'accessBlocked';
// 被限制后跳转的PHP页面地址
const BLOCK_PAGE_URL = '../blocked.php';
// 主页地址
const HOME_PAGE_URL = '/';

// 移除URL的http/https前缀
function removeProtocol(url) {
    return url.replace(/^https?:\/\//, '').replace(/^\/\//, '');
}

// 页面加载时检测Cookie，若存在则跳转（带参）
window.onload = function() {
    if (getCookie(BLOCK_COOKIE_NAME)) {
        const currentUrl = removeProtocol(window.location.href);
        window.location.href = `${BLOCK_PAGE_URL}?from=${encodeURIComponent(currentUrl)}`;
    }
};

// 绑定按钮点击事件（使用原生弹窗）
document.getElementById('update').addEventListener('click', function() {
    clickCount++;
    localStorage.setItem('updateClickCount', clickCount);

    // 原生弹窗提示
    switch(clickCount) {
        case 1:
            alert('这个功能没有研发');
            break;
        case 2:
            alert('你确定要点吗，都说了没做！');
            break;
        case 3:
            alert('别在点了！');
            break;
        case 4:
            alert('最后警告！即将跳转到限制页面');
            // 设置Cookie并跳转
            setCookie(BLOCK_COOKIE_NAME, '1', 1);
            const currentUrl = removeProtocol(window.location.href);
            setTimeout(() => {
                window.location.href = `${BLOCK_PAGE_URL}?from=${encodeURIComponent(currentUrl)}`;
            }, 1000);
            break;
        default:
            alert('禁止访问！');
            window.location.href = BLOCK_PAGE_URL;
    }
});

// Cookie操作函数
function setCookie(name, value, days) {
    const date = new Date();
    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    const expires = "expires=" + date.toUTCString();
    document.cookie = name + "=" + value + ";" + expires + ";path=/";
}

function getCookie(name) {
    const nameEQ = name + "=";
    const ca = document.cookie.split(';');
    for(let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) === ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}