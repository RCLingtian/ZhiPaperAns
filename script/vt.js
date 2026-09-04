document.addEventListener('DOMContentLoaded', function() {

    document.title = "纸条答案开源版 - 答案";

    // 隐藏所有class为"aiAssistant"的a元素
    document.querySelectorAll('a.aiAssistant').forEach(element => {
        element.style.display = 'none';
    });

    // 修改返回按钮
    document.querySelectorAll('.subNav a.subBack.fl').forEach(element => {
        const iconElement = element.querySelector('.icon-BackIcon');

        if (iconElement) {
            iconElement.style.display = 'none';
        }

        element.textContent = "返回首页";
        element.href = "/";
    });

    // 允许复制答案区域内容
    document.querySelectorAll('.fanyaMarking_left.whiteBg').forEach(container => {
        container.oncopy = null;

        container.addEventListener('copy', e => {
            e.stopPropagation();
        });
    });

    // 确保CSS允许文本选择
    const enableSelectionStyles = document.createElement('style');
    enableSelectionStyles.textContent = `
        .fanyaMarking_left.whiteBg,
        .fanyaMarking_left.whiteBg * {
            user-select: text !important;
            -webkit-user-select: text !important;
            -moz-user-select: text !important;
            -ms-user-select: text !important;
            pointer-events: auto !important;
        }
    `;
    document.head.appendChild(enableSelectionStyles);

    // 隐藏提示图标
    document.querySelectorAll('span.tipsIc').forEach(element => {
        element.style.display = 'none';
    });

    // 隐藏重做按钮
    document.querySelectorAll('a.redo').forEach(element => {
        element.style.display = 'none';
    });

    // 隐藏解析卡片
    document.querySelectorAll('.analysisCard.fl').forEach(element => {
        element.style.display = 'none';
    });

    // 移除所有 class="know-point" 的 p 标签下 a 标签的 href
    document.querySelectorAll('p.know-point a').forEach(element => {
        element.removeAttribute('href');
    });

    // 替换特定CSS链接
    const targetLinks = document.querySelectorAll(
        'link[href^="//mooc1.chaoxing.com/mooc-ans/mooc2/css/marking_icon.css"],' +
        'link[href^="//mooc1.chaoxing.com/exam-ans/mooc2/css/q_marking_icon.css"]'
    );

    targetLinks.forEach(link => {
        link.href = 'static/q_marking_icon.css';
    });

});

console.log("exam.js 已加载 - 版本: 2026-09-04");
