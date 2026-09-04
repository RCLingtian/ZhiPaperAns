document.addEventListener('DOMContentLoaded', function() {
    const lessonList = document.getElementById('lesson-list');
    const searchInput = document.getElementById('search');
    const searchEmpty = document.getElementById('search-empty');
    
    // 添加清空按钮的伪元素样式
    const style = document.createElement('style');
    style.textContent = `
        #search-empty{
            height: 100%;
            width: auto;
            display: flex;
            justify-content: center;
            align-items: center;
        }
    `;
    document.head.appendChild(style);
    
    // 监听输入变化
    searchInput.addEventListener('input', function() {
        searchEmpty.style.display = this.value.trim() ? 'flex' : 'none';
    });
    
    // 点击清空按钮
    searchEmpty.addEventListener('click', function() {
        searchInput.value = '';
        searchEmpty.style.display = 'none';
        searchInput.focus();
        // 手动触发input事件
        searchInput.dispatchEvent(new Event('input'));
    });

    // 显示加载状态
    lessonList.innerHTML = '<div class="loading">加载中...</div>';
    
    // 生成时间戳，添加到请求地址后
    const timestamp = new Date().getTime();
    fetch(`file/all.json?t=${timestamp}`) // 这一行已修改
        .then(response => {
            if (!response.ok) {
                throw new Error('网络响应不正常');
            }
            return response.json();
        })
        .then(data => {
            // 修改为降序排列（新的在前）
            data.sort((a, b) => b.t - a.t);
            
            const searchInput = document.getElementById('search');
            
            function renderLessons(items) {
                lessonList.innerHTML = '';
                items.forEach(item => {
                    const lessonDiv = document.createElement('div');
                    lessonDiv.className = 'lesson';
                    lessonDiv.setAttribute('data-lesson-times', item.t);
                    lessonDiv.setAttribute('data-lesson-type', item.type);
                    lessonDiv.setAttribute('data-lesson-names', item.name);
                    
                    const backgroundImage = item.background === 'none' ? 
                        'images/index/lesson-bk.jpg' : item.background;
                    
                    lessonDiv.innerHTML = `
                        <a href="exam.php?type=${item.type}&key=${item.key}" class="lesson-box-href" target="_blank">
                            <div class="lesson-img" style="background-image: url(${backgroundImage});"></div>
                            <div class="lesson-text">
                                <span class="lesson-title">${item.name}</span>
                                <span class="lesson-times unuse-div">发布时间：<span>${formatDate(item.t)}</span></span>
                            </div>
                        </a>
                    `;
                    
                    lessonList.appendChild(lessonDiv);
                });
            }
            
            // 初始渲染
            renderLessons(data);
            
            // 添加搜索功能
            searchInput.addEventListener('input', (e) => {
                const searchTerm = e.target.value.toLowerCase();
                const filtered = data.filter(item => 
                    item.name.toLowerCase().includes(searchTerm)
                );
                renderLessons(filtered);
            });
        })
        .catch(error => {
            console.error('加载JSON出错:', error);
            lessonList.innerHTML = `<div class="error">加载失败: ${error.message}</div>`;
        });
});

function formatDate(timestamp) {
    // 将时间戳格式化为YYYY-MM-DD
    const date = new Date(timestamp * 1000);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}