/**
 * 独立的二维码生成器
 * 不依赖任何框架，使用phpqrcode库生成真实二维码
 */

/**
 * 获取项目URL
 */
function getProjectUrl() {
    var path = window.location.pathname;
    var pathArray = path.split('/');
    pathArray.pop(); // 移除文件名
    return window.location.protocol + '//' + window.location.host + pathArray.join('/') + '/';
}

/**
 * 生成真实二维码
 * @param {string} text - 要生成二维码的文本
 * @param {function} callback - 回调函数，参数为生成的二维码DataURL
 * @param {object} options - 可选参数 {width: 200, height: 200}
 */
function generateQRCode(text, callback, options) {
    options = options || {};
    var size = options.width || options.height || 200;

    if (!text || !text.trim()) {
        console.error('二维码文本不能为空');
        if (callback) callback(null);
        return;
    }

    try {
        var xhr = new XMLHttpRequest();
        var url = getProjectUrl() + 'qrcode.php?text=' + encodeURIComponent(text) + '&size=' + size + '&format=base64';
        
        xhr.open('GET', url, true);
        xhr.timeout = 15000; // 15秒超时
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success && response.data) {
                            if (callback) callback(response.data);
                            if (response.warning) {
                                console.warn('二维码生成警告:', response.warning);
                            }
                        } else {
                            console.error('二维码生成失败:', response.error || '未知错误');
                            if (callback) callback(null);
                        }
                    } catch (e) {
                        console.error('解析二维码响应失败:', e);
                        if (callback) callback(null);
                    }
                } else {
                    console.error('二维码请求失败:', xhr.status, xhr.statusText);
                    if (callback) callback(null);
                }
            }
        };
        
        xhr.onerror = function() {
            console.error('二维码请求网络错误');
            if (callback) callback(null);
        };
        
        xhr.ontimeout = function() {
            console.error('二维码请求超时');
            if (callback) callback(null);
        };
        
        xhr.send();
        
    } catch (e) {
        console.error('二维码生成异常:', e);
        if (callback) callback(null);
    }
}

/**
 * 生成二维码并显示在指定元素中
 * @param {string|HTMLElement} container - 容器元素或选择器
 * @param {string} text - 要生成二维码的文本
 * @param {object} options - 可选参数
 */
function generateQRCodeToElement(container, text, options) {
    options = options || {};
    
    // 获取容器元素
    var element;
    if (typeof container === 'string') {
        element = document.querySelector(container);
    } else {
        element = container;
    }
    
    if (!element) {
        console.error('找不到容器元素');
        return;
    }
    
    // 显示加载状态
    element.innerHTML = '<p>正在生成二维码...</p>';
    
    // 生成二维码
    generateQRCode(text, function(dataUrl) {
        if (dataUrl) {
            var img = document.createElement('img');
            img.src = dataUrl;
            img.alt = '二维码';
            img.style.maxWidth = '100%';
            img.style.height = 'auto';
            
            element.innerHTML = '';
            element.appendChild(img);
            
            // 添加文本说明（如果需要）
            if (options.showText !== false) {
                var textP = document.createElement('p');
                textP.textContent = '内容: ' + text;
                textP.style.marginTop = '10px';
                textP.style.fontSize = '12px';
                textP.style.color = '#666';
                element.appendChild(textP);
            }
        } else {
            element.innerHTML = '<p style="color: red;">二维码生成失败</p>';
        }
    }, options);
}

/**
 * 批量生成二维码
 * @param {Array} items - 二维码项目数组，每项包含 {text, container, options}
 */
function generateMultipleQRCodes(items) {
    if (!Array.isArray(items)) {
        console.error('参数必须是数组');
        return;
    }
    
    items.forEach(function(item, index) {
        if (item.text && item.container) {
            // 延迟生成，避免同时发送太多请求
            setTimeout(function() {
                generateQRCodeToElement(item.container, item.text, item.options);
            }, index * 100);
        }
    });
}

// 导出到全局作用域
if (typeof window !== 'undefined') {
    window.generateQRCode = generateQRCode;
    window.generateQRCodeToElement = generateQRCodeToElement;
    window.generateMultipleQRCodes = generateMultipleQRCodes;
}
