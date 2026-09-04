function copyShareLink() {
            // 获取当前用户ID（从cookie中）
            function getCookie(name) {
                const value = `; ${document.cookie}`;
                const parts = value.split(`; ${name}=`);
                if (parts.length === 2) return parts.pop().split(';').shift();
            }
            const userId = getCookie('user_id');
            
            // 复制当前页面URL到剪贴板，并添加用户学号注释
            const shareUrl = window.location.href + (userId ? `#来自纸条用户${userId}的分享！` : '');
            navigator.clipboard.writeText(shareUrl).then(function() {
                // 显示自定义提示
                const toast = document.getElementById("copy-toast");
                toast.style.opacity = "1";
                
                // 3秒后隐藏提示
                setTimeout(function() {
                    toast.style.opacity = "0";
                }, 3000);
            }).catch(function(err) {
                console.error("无法复制: ", err);
            });
        }