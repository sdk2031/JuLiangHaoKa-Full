/** EasyWeb iframe v3.1.7 date:2020-03-11 License By http://easyweb.vip */
layui.config({
    version: "318",
    base: getProjectUrl() + "assets/module/"
}).extend({
    steps: "steps/steps",
    notice: "notice/notice",
    cascader: "cascader/cascader",
    dropdown: "dropdown/dropdown",
    fileChoose: "fileChoose/fileChoose",
    Split: "Split/Split",
    Cropper: "Cropper/Cropper",
    tagsInput: "tagsInput/tagsInput",
    citypicker: "city-picker/city-picker",
    introJs: "introJs/introJs",
    zTree: "zTree/zTree",
    xmSelect: "xmSelect"
}).use(["layer", "admin", "notice"],
function() {
    var c = layui.jquery;
    var b = layui.layer;
    var a = layui.admin;
    var notice = layui.notice;

    /* 重写layer.msg方法，使用notice样式 - 已注释，恢复layer.msg原始样式
    if (notice && b) {
        var originalMsg = b.msg;
        b.msg = function(content, options, end) {
            if (typeof options === 'function') {
                end = options;
                options = {};
            }
            options = options || {};
            var icon = options.icon || 0;

            // 对于加载提示，仍使用原始的layer.msg，因为需要支持layer.close()
            if (icon === 4 || icon === 16) {
                return originalMsg.call(this, content, options, end);
            }

            // 其他提示使用notice样式
            switch(icon) {
                case 1: // 成功
                    notice.msg(content, {icon: 1});
                    break;
                case 2: // 错误
                    notice.msg(content, {icon: 2});
                    break;
                case 3: // 询问/警告
                    notice.msg(content, {icon: 3});
                    break;
                case 5: // 笑脸/信息
                    notice.msg(content, {icon: 5});
                    break;
                case 6: // 哭脸
                    notice.msg(content, {icon: 2}); // 使用错误样式
                    break;
                default: // 默认信息样式
                    notice.msg(content, {icon: 5});
                    break;
            }
            if (end) setTimeout(end, options.time || 3000);
            return Date.now();
        };
        console.log('✅ Layer.msg 已替换为 notice 样式（加载提示除外）');
    }
    */
});
function getProjectUrl() {
    var c = layui.cache.dir;
    if (!c) {
        var e = document.scripts,
        b = e.length - 1,
        f;
        for (var a = b; a > 0; a--) {
            if (e[a].readyState === "interactive") {
                f = e[a].src;
                break
            }
        }
        var d = f || e[b].src;
        c = d.substring(0, d.lastIndexOf("/") + 1)
    }
    return c.substring(0, c.indexOf("assets"))
};

/**
 * 公共二维码生成函数 - 使用PHP后端生成稳定的二维码
 * @param {string} text - 要生成二维码的文本
 * @param {function} callback - 回调函数，参数为生成的二维码DataURL
 * @param {object} options - 可选参数 {width: 200, height: 200}
 */
window.generateQRCode = function(text, callback, options) {
    options = options || {};
    var size = options.width || options.height || 200;

    if (!text || !text.trim()) {
        console.error('二维码文本不能为空');
        createPlaceholderQR(text || '空内容', callback, size);
        return;
    }

    try {
        // 使用PHP后端生成二维码
        var xhr = new XMLHttpRequest();
        var url = getProjectUrl() + 'qrcode.php?text=' + encodeURIComponent(text) + '&size=' + size + '&format=base64';

        xhr.open('GET', url, true);
        xhr.timeout = 10000; // 10秒超时

        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success && response.data) {
                            callback(response.data);
                            if (response.warning) {
                                console.warn('二维码生成警告:', response.warning);
                            }
                        } else {
                            console.error('二维码生成失败:', response.error || '未知错误');
                            createPlaceholderQR(text, callback, size);
                        }
                    } catch (e) {
                        console.error('解析二维码响应失败:', e);
                        createPlaceholderQR(text, callback, size);
                    }
                } else {
                    console.error('二维码请求失败:', xhr.status, xhr.statusText);
                    createPlaceholderQR(text, callback, size);
                }
            }
        };

        xhr.onerror = function() {
            console.error('二维码请求网络错误');
            createPlaceholderQR(text, callback, size);
        };

        xhr.ontimeout = function() {
            console.error('二维码请求超时');
            createPlaceholderQR(text, callback, size);
        };

        xhr.send();

    } catch (e) {
        console.error('二维码生成异常:', e);
        createPlaceholderQR(text, callback, size);
    }
};

/**
 * 创建占位二维码
 * @param {string} text - 文本内容
 * @param {function} callback - 回调函数
 * @param {number} size - 尺寸
 */
function createPlaceholderQR(text, callback, size) {
    size = size || 200;
    var canvas = document.createElement('canvas');
    var ctx = canvas.getContext('2d');
    canvas.width = size;
    canvas.height = size;

    // 白色背景
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, size, size);

    // 黑色边框
    ctx.strokeStyle = '#000000';
    ctx.lineWidth = 2;
    ctx.strokeRect(10, 10, size - 20, size - 20);

    // 添加文字
    ctx.fillStyle = '#000000';
    ctx.font = Math.floor(size / 14) + 'px Arial';
    ctx.textAlign = 'center';
    ctx.fillText('扫码访问', size / 2, size / 2 - 10);
    ctx.fillText('二维码', size / 2, size / 2 + 10);

    // 添加简单的图案
    var patternSize = size / 5;
    ctx.fillRect((size - patternSize) / 2, size / 2 + 20, patternSize, patternSize);
    ctx.fillStyle = '#ffffff';
    ctx.fillRect((size - patternSize) / 2 + 5, size / 2 + 25, patternSize - 10, patternSize - 10);

    callback(canvas.toDataURL());
}