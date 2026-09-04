<?php
// 检查是否存在有效的请求参数
if (isset($_GET['type']) && isset($_GET['key'])) {
    $key = $_GET['key'];
    //  sanitize key以防止目录遍历攻击
    $sanitizedKey = basename($key);
    
    // 根据type参数确定文件目录
    $subDir = ($_GET['type'] === 'exam') ? 'exam' : 'homework';
    // 构建目标文件路径
    $filePath = 'file/' . $subDir . '/' . $sanitizedKey . '.html';
    
    // 检查文件是否存在且可读
    if (file_exists($filePath) && is_readable($filePath)) {
        // 读取HTML文件内容
        $htmlContent = file_get_contents($filePath);
        
        $htmlContent = str_replace('</head>', '<link rel="stylesheet" href="./style.css">' . "\n" . '</head>', $htmlContent);

        // 定义要插入的div内容
       $insertDiv = '    <div class="y-sc">
            <div id="sharh" onclick="copyShareLink()" title="分享给你的好友！">
                <svg t="1763794347018" class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="12676"><path d="M763.84 896c-47.128 0-85.333-38.205-85.333-85.333s38.205-85.333 85.333-85.333c47.128 0 85.333 38.205 85.333 85.333 0 47.128-38.205 85.333-85.333 85.333M329.92 558.848c-14.895 26.231-42.641 43.638-74.453 43.638-47.128 0-85.333-38.205-85.333-85.333 0-16.097 4.457-31.152 12.204-44 14.935-24.769 42.098-41.333 73.13-41.333 47.128 0 85.333 38.205 85.333 85.333 0 15.317-4.035 29.691-11.101 42.117M763.84 128c47.128 0 85.333 38.205 85.333 85.333s-38.205 85.333-85.333 85.333c-47.128 0-85.333-38.205-85.333-85.333 0-47.128 38.205-85.333 85.333-85.333M763.84 682.667c-0.021 0-0.047 0-0.072 0-39.16 0-74.203 17.626-97.628 45.378l-289.885-167.063c4.932-13.101 7.787-28.245 7.787-44.055 0-0.105 0-0.209 0-0.314 0-0.072 0-0.177 0-0.281 0-15.81-2.855-30.953-8.077-44.942l295.544-169.566c23.265 24.363 56.001 39.509 92.275 39.509 0.020 0 0.039 0 0.059 0 70.689 0 127.997-57.308 127.997-128 0-70.692-57.308-128-128-128-70.692 0-128 57.308-128 128 0 18.965 4.224 36.907 11.627 53.099l-292.288 168.747c-23.653-28.833-59.285-47.084-99.18-47.084-70.692 0-128 57.308-128 128 0 0.188 0 0.376 0.001 0.564-0.001 0.123-0.001 0.304-0.001 0.484 0 70.692 57.308 128 128 128 39.895 0 75.526-18.251 99.001-46.86l289.373 166.752c-5.397 13.568-8.529 29.29-8.533 45.743 0 70.582 57.308 127.889 128 127.889 70.692 0 128-57.308 128-128 0-70.692-57.308-128-128-128z" fill="#2c2c2c" p-id="12676"></path></svg>
            </div>
        </div>
        <div id="copy-toast" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.7); color: white; padding: 10px 20px; border-radius: 4px; opacity: 0; transition: opacity 0.3s; pointer-events: none;">分享链接已复制到剪贴板</div>
        <script src="./script/share.js"></script>
        <script src="./script/vt.js?v=20260904-1"></script>
        <script src="./script/main.js"></script>';
        
        // 插入到body结束标签前
        $modifiedContent = str_replace('</body>', $insertDiv . '</body>', $htmlContent);
        
        // 输出修改后的内容
        echo $modifiedContent;
        exit; // 确保加载文件后终止脚本执行
    }
}

// 参数缺失、无效或文件不存在时重定向到index.html
header('Location: /');
exit;
?>