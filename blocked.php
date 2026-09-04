<?php
// 移除URL的http/https前缀函数
function removeProtocol($url) {
    return preg_replace('/^https?:\/\//', '', $url);
}

// 处理from参数，无参数时默认zhi.koimst.cn（去协议）
$fromUrl = isset($_GET['from']) ? urldecode($_GET['from']) : 'zhi.koimst.cn';
$fromUrl = removeProtocol($fromUrl);
// 安全转义传递给JS
$safeFromUrl = json_encode($fromUrl);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>拒绝访问</title>
    <style>
        /* 极简主体样式 */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, 'Microsoft YaHei', sans-serif;
        }
        body {
            background: #f5f5f5;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 400px;
            margin: 50px auto;
            background: #fff;
            padding: 30px;
            border: 1px solid #eee;
            border-radius: 4px;
        }
        h1 {
            color: #dc3545;
            font-size: 20px;
            margin-bottom: 15px;
            font-weight: 600;
        }
        .desc {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .from-url {
            color: #007bff;
            font-size: 13px;
            margin-bottom: 25px;
            word-break: break-all;
        }
        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        button {
            flex: 1;
            padding: 8px 0;
            font-size: 14px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        #admitError {
            background: #dc3545;
            color: #fff;
        }
        #dontCare {
            background: #6c757d;
            color: #fff;
        }
        button:hover {
            opacity: 0.9;
        }

        /* 极简自定义弹窗样式 - 无花哨效果 */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 999;
            display: none;
        }
        .modal-content {
            background: #fff;
            width: 90%;
            max-width: 300px;
            padding: 20px;
            border: 1px solid #eee;
            border-radius: 4px;
        }
        .modal-text {
            font-size: 16px;
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }
        .modal-btn-group {
            display: flex;
            gap: 10px;
        }
        .modal-btn {
            flex: 1;
            padding: 6px 0;
            font-size: 14px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-confirm {
            background: #28a745;
            color: #fff;
        }
        .btn-cancel {
            background: #dc3545;
            color: #fff;
        }
        .alert-modal .modal-btn-group {
            justify-content: center;
        }
        .alert-modal .modal-btn {
            flex: none;
            padding: 6px 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>你的访问请求已被管理员拒绝</h1>
        <p class="desc">多次违规操作触发访问限制</p>
        <p class="from-url">来源地址: <?php echo htmlspecialchars($fromUrl); ?></p>
        
        <div class="btn-group">
            <button id="admitError">我错了！</button>
            <button id="dontCare">噢，0人在意</button>
        </div>
    </div>

    <!-- 自定义确认弹窗（隐藏） -->
    <div class="modal confirm-modal" id="confirmModal">
        <div class="modal-content">
            <p class="modal-text">你真的错了吗？</p>
            <div class="modal-btn-group">
                <button class="modal-btn btn-confirm" id="modalConfirm">真的错了</button>
                <button class="modal-btn btn-cancel" id="modalCancel">骗你的，我没错</button>
            </div>
        </div>
    </div>

    <!-- 自定义提示弹窗（隐藏） -->
    <div class="modal alert-modal" id="alertModal">
        <div class="modal-content">
            <p class="modal-text" id="alertText"></p>
            <div class="modal-btn-group">
                <button class="modal-btn btn-confirm" id="alertClose">确定</button>
            </div>
        </div>
    </div>

    <script>
        // 从PHP获取去协议后的from地址
        const fromUrl = <?php echo $safeFromUrl; ?>;
        const BLOCK_COOKIE_NAME = 'accessBlocked';
        // 跳转地址（补全协议）
        const HOME_PAGE_URL = fromUrl.includes('://') ? fromUrl : 'https://' + fromUrl;

        // 页面加载检测Cookie
        window.onload = function() {
            if (!getCookie(BLOCK_COOKIE_NAME)) {
                window.location.href = HOME_PAGE_URL;
            }
            // 绑定弹窗事件
            bindModalEvents();
        };

        // 绑定按钮点击事件
        document.getElementById('admitError').addEventListener('click', function() {
            // 显示确认弹窗
            showModal('confirmModal');
        });

        document.getElementById('dontCare').addEventListener('click', function() {
            // 显示提示弹窗
            showAlert('那你别看了');
        });

        // 绑定弹窗按钮事件
        function bindModalEvents() {
            // 确认弹窗-真的错了
            document.getElementById('modalConfirm').addEventListener('click', function() {
                hideModal('confirmModal');
                // 清除Cookie+重置次数
                deleteCookie(BLOCK_COOKIE_NAME);
                localStorage.removeItem('updateClickCount');
                // 显示提示并跳转
                showAlert('知错能改，善莫大焉！即将返回原页面...', function() {
                    setTimeout(() => {
                        window.location.href = HOME_PAGE_URL;
                    }, 1000);
                });
            });

            // 确认弹窗-骗你的
            document.getElementById('modalCancel').addEventListener('click', function() {
                hideModal('confirmModal');
                showAlert('那你别看了');
            });

            // 提示弹窗-关闭
            document.getElementById('alertClose').addEventListener('click', function() {
                hideModal('alertModal');
            });
        }

        // 显示自定义确认弹窗
        function showModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }

        // 隐藏弹窗
        function hideModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // 显示自定义提示弹窗
        function showAlert(text, callback) {
            document.getElementById('alertText').textContent = text;
            showModal('alertModal');
            // 可选回调（跳转用）
            if (callback) {
                // 移除原有事件，避免重复绑定
                document.getElementById('alertClose').onclick = function() {
                    hideModal('alertModal');
                    callback();
                };
            }
        }

        // Cookie操作函数
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

        function deleteCookie(name) {
            document.cookie = name + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
        }
    </script>
</body>
</html>