/** EasyWeb iframe v3.1.7 date:2020-03-11 License By http://easyweb.vip */
layui.config({
    version: "320",
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

    function getTopNotice() {
        try {
            if (window.parent && window.parent !== window && window.parent.layui && window.parent.layui.notice) {
                return window.parent.layui.notice;
            }
            if (window.parent && window.parent !== window && window.parent.layui && window.parent.layui.use) {
                window.parent.layui.use(['notice'], function() {});
                if (window.parent.layui.notice) {
                    return window.parent.layui.notice;
                }
            }
        } catch (e) {}
        return notice;
    }

    /* 重写layer.msg方法，使用notice顶部提示样式 */
    if (notice && b) {
        var originalMsg = b.msg;
        b.msg = function(content, options, end) {
            if (typeof options === 'function') {
                end = options;
                options = {};
            }
            options = options || {};
            var icon = options.icon || 0;
            var timeout = options.time === 0 ? false : Math.max(options.time || 3000, 3000);

            // 对于加载提示，仍使用原始的layer.msg，因为需要支持layer.close()
            if (icon === 4 || icon === 16) {
                return originalMsg.call(this, content, options, end);
            }

            // 其他提示使用notice样式
            var noticeApi = getTopNotice();
            switch(icon) {
                case 1: // 成功
                    noticeApi.msg(content, {icon: 1, timeout: timeout});
                    break;
                case 2: // 错误
                    noticeApi.msg(content, {icon: 2, timeout: timeout});
                    break;
                case 3: // 询问/警告
                    noticeApi.msg(content, {icon: 3, timeout: timeout});
                    break;
                case 5: // 笑脸/信息
                    noticeApi.msg(content, {icon: 5, timeout: timeout});
                    break;
                case 6: // 哭脸
                    noticeApi.msg(content, {icon: 2, timeout: timeout}); // 使用错误样式
                    break;
                default: // 默认信息样式
                    noticeApi.msg(content, {icon: 5, timeout: timeout});
                    break;
            }
            if (end && timeout !== false) setTimeout(end, timeout);
            return Date.now();
        };
        console.log('Layer.msg 已替换为 notice 顶部提示样式（加载提示除外）');
    }

    installGlobalLoadingStyle(c);
    installGlobalDrawerLayer(b, c);
});

function installGlobalLoadingStyle($) {
    function inject(doc) {
        if (!doc || doc.getElementById('global-layer-loading-style')) return;
        var style = doc.createElement('style');
        style.id = 'global-layer-loading-style';
        style.textContent =
            '@keyframes globalLayerLoadingSpin{to{transform:rotate(360deg)}}' +
            '.layui-layer-loading{background:transparent!important;box-shadow:none!important;}' +
            '.layui-layer-loading .layui-layer-content{' +
            'position:relative!important;width:42px!important;height:42px!important;background:none!important;' +
            'animation:globalLayerLoadingSpin .85s linear infinite;}' +
            '.layui-layer-loading .layui-layer-content:before{' +
            'content:"";position:absolute;left:16px;top:3px;width:10px;height:10px;border-radius:50%;background:#5b84ff;' +
            'box-shadow:14px 14px 0 rgba(79,124,255,.82),0 28px 0 rgba(79,124,255,.58),-14px 14px 0 rgba(79,124,255,.38);}' +
            '.layui-layer-loading .layui-layer-content:after{' +
            'content:"";display:none;}' +
            '.layui-layer-dialog .layui-layer-ico16,.layui-layer-dialog .layui-layer-ico4{' +
            'width:30px!important;height:30px!important;background:none!important;animation:globalLayerLoadingSpin .85s linear infinite!important;}' +
            '.layui-layer-dialog .layui-layer-ico16:before,.layui-layer-dialog .layui-layer-ico4:before{' +
            'content:"";position:absolute;left:11px;top:1px;width:8px;height:8px;border-radius:50%;background:#5b84ff;' +
            'box-shadow:11px 11px 0 rgba(79,124,255,.82),0 22px 0 rgba(79,124,255,.58),-11px 11px 0 rgba(79,124,255,.38);}' +
            '.page-loading .ball-loader,.page-loading .rubik-loader,.page-loading .signal-loader,.page-loading .layui-loader{' +
            'position:absolute!important;left:50%!important;top:50%!important;width:42px!important;height:42px!important;margin:-21px 0 0 -21px!important;' +
            'background:none!important;border:0!important;transform:none!important;animation:globalLayerLoadingSpin .85s linear infinite!important;}' +
            '.page-loading .ball-loader:before,.page-loading .rubik-loader:before,.page-loading .signal-loader:before,.page-loading .layui-loader:before{' +
            'content:"";position:absolute;left:16px;top:3px;width:10px;height:10px;border-radius:50%;background:#5b84ff;' +
            'box-shadow:14px 14px 0 rgba(79,124,255,.82),0 28px 0 rgba(79,124,255,.58),-14px 14px 0 rgba(79,124,255,.38);}' +
            '.page-loading .ball-loader span,.page-loading .signal-loader span,.page-loading .layui-loader i{' +
            'display:none!important;}' +
            '.page-loading .rubik-loader:after,.page-loading .layui-loader:after{' +
            'content:"";display:none!important;}';
        doc.head.appendChild(style);
    }

    inject(document);
    try {
        if (window.parent && window.parent !== window) {
            inject(window.parent.document);
        }
    } catch (e) {}
}

function installGlobalDrawerLayer(layer, $) {
    if (!layer || layer.__globalDrawerPatched) return;
    layer.__globalDrawerPatched = true;

    var originalOpen = layer.open;
    var originalClose = layer.close;
    var originalCloseAll = layer.closeAll;
    var parentDrawerIndexes = {};

    function getHostWindow() {
        try {
            if (window.parent && window.parent !== window && ((window.parent.layui && window.parent.layui.layer) || window.parent.layer)) {
                return window.parent;
            }
        } catch (e) {}
        return window;
    }

    function getHostLayer() {
        var hostWindow = getHostWindow();
        return (hostWindow.layui && hostWindow.layui.layer) || hostWindow.layer || null;
    }

    function isDrawerLike(options) {
        if (!options || typeof options !== 'object') return false;
        var skin = String(options.skin || '');
        var offset = String(options.offset || '');
        var area = options.area || [];
        var height = Array.isArray(area) ? String(area[1] || '') : '';
        return skin.indexOf('drawer') !== -1 || ((offset === 'r' || offset === 'rt') && height === '100%');
    }

    function shouldOpenInParent(options) {
        if (!isDrawerLike(options)) return false;
        if (String(location.pathname || '').indexOf('/admin/system/config') !== -1) return false;
        if (String(options.skin || '').indexOf('payment-config-drawer') !== -1) return false;
        if (options.content && typeof options.content !== 'string') return false;
        return true;
    }

    function shouldFullscreenFrame(options) {
        return false;
    }

    function ensureDrawerStyles(hostDocument) {
        if (!hostDocument) return;
        var oldStyle = hostDocument.getElementById('global-drawer-layer-style');
        if (oldStyle) oldStyle.remove();
        var style = hostDocument.createElement('style');
        style.id = 'global-drawer-layer-style';
        style.textContent =
            '.layui-layer.global-parent-drawer,.layui-layer.layer-drawer-right{' +
            'position:fixed!important;top:0!important;right:0!important;bottom:0!important;height:100vh!important;max-height:100vh!important;border-radius:0!important;}' +
            '.layui-layer.global-parent-drawer .layui-layer-title,.layui-layer.layer-drawer-right .layui-layer-title{height:42px;line-height:42px;}' +
            '.layui-layer.global-parent-drawer .layui-layer-content,.layui-layer.layer-drawer-right .layui-layer-content{' +
            'height:calc(100vh - 42px)!important;max-height:calc(100vh - 42px)!important;overflow-y:auto;overflow-x:hidden;}' +
            '.layui-layer-shade.global-parent-drawer-shade{position:fixed!important;top:0!important;left:0!important;right:0!important;bottom:0!important;width:100vw!important;height:100vh!important;}';
        hostDocument.head.appendChild(style);
    }

    function cleanupCopiedPageStyles(hostDocument) {
        if (!hostDocument || hostDocument === document) return;
        $(hostDocument).find('[id^="global-drawer-link-"],[id^="global-drawer-style-"]').remove();
    }

    function scopedCss(css) {
        return String(css || '').replace(/([^{}]+)\{([^{}]*)\}/g, function(all, selectors, body) {
            var trimmed = $.trim(selectors);
            if (!trimmed || trimmed.charAt(0) === '@') return all;
            var scopedSelectors = trimmed.split(',').map(function(selector) {
                selector = $.trim(selector);
                if (!selector || selector.indexOf('.global-parent-drawer') === 0) return selector;
                return '.global-parent-drawer ' + selector;
            }).join(',');
            return scopedSelectors + '{' + body + '}';
        });
    }

    function copyScopedPageStyles(hostDocument) {
        if (!hostDocument || hostDocument === document) return;
        var pageKey = String(location.pathname || 'page').replace(/[^a-zA-Z0-9_-]/g, '_');
        $('style').each(function(index) {
            var css = this.textContent || '';
            if (!css.trim()) return;
            var id = 'global-drawer-scoped-style-' + pageKey + '-' + index;
            var oldStyle = hostDocument.getElementById(id);
            if (oldStyle) oldStyle.remove();
            var style = hostDocument.createElement('style');
            style.id = id;
            style.textContent = scopedCss(css);
            hostDocument.head.appendChild(style);
        });
    }

    function normalizeDrawerLayer(layero, index, hostDocument) {
        var $hostDocument = $(hostDocument);
        var $shade = $hostDocument.find('#layui-layer-shade' + index);
        $shade.addClass('global-parent-drawer-shade').css({
            position: 'fixed',
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
            width: '100vw',
            height: '100vh'
        });
        layero.addClass('global-parent-drawer').css({
            position: 'fixed',
            top: 0,
            right: 0,
            bottom: 0,
            height: '100vh',
            'max-height': '100vh'
        });
        layero.find('.layui-layer-content').css({
            'overflow-y': 'auto',
            'overflow-x': 'hidden',
            'height': 'calc(100vh - 42px)',
            'max-height': 'calc(100vh - 42px)'
        });
    }

    function hideLocalDrawerShade(index) {
        $('#layui-layer-shade' + index).css({
            background: 'transparent',
            opacity: 0
        });
    }

    function renderHostLayui(hostWindow) {
        try {
            if (hostWindow.layui && hostWindow.layui.form) hostWindow.layui.form.render();
            if (hostWindow.layui && hostWindow.layui.element) hostWindow.layui.element.render();
        } catch (e) {}
    }

    function getCurrentFrameElement(hostWindow) {
        try {
            var frames = hostWindow.document.getElementsByTagName('iframe');
            for (var i = 0; i < frames.length; i++) {
                if (frames[i].contentWindow === window) return frames[i];
            }
        } catch (e) {}
        return null;
    }

    function getDrawerWidth(options) {
        var area = options && options.area;
        if (Array.isArray(area) && area[0]) return String(area[0]);
        return '760px';
    }

    function lockFullscreenFrame(hostWindow, options) {
        var frame = getCurrentFrameElement(hostWindow);
        if (!frame || frame.__globalDrawerFullscreenLocked) return frame;
        frame.__globalDrawerFullscreenLocked = true;
        frame.__globalDrawerOldStyle = frame.getAttribute('style') || '';
        var shade = hostWindow.document.createElement('div');
        shade.className = 'global-frame-drawer-shade';
        shade.style.cssText = 'position:fixed;left:0;top:0;right:0;bottom:0;width:100vw;height:100vh;background:#000;opacity:.3;z-index:19891013;';
        hostWindow.document.body.appendChild(shade);
        frame.__globalDrawerShade = shade;
        frame.style.position = 'relative';
        frame.style.zIndex = '19891014';
        try {
            hostWindow.document.documentElement.style.overflow = 'hidden';
            hostWindow.document.body.style.overflow = 'hidden';
        } catch (e) {}
        return frame;
    }

    function unlockFullscreenFrame(hostWindow, frame) {
        if (!frame || !frame.__globalDrawerFullscreenLocked) return;
        if (frame.__globalDrawerShade && frame.__globalDrawerShade.parentNode) {
            frame.__globalDrawerShade.parentNode.removeChild(frame.__globalDrawerShade);
        }
        frame.setAttribute('style', frame.__globalDrawerOldStyle || '');
        delete frame.__globalDrawerShade;
        delete frame.__globalDrawerOldStyle;
        delete frame.__globalDrawerFullscreenLocked;
        try {
            hostWindow.document.documentElement.style.overflow = '';
            hostWindow.document.body.style.overflow = '';
        } catch (e) {}
    }

    layer.open = function(options) {
        var hostWindow = getHostWindow();
        var hostLayer = getHostLayer();
        if (shouldFullscreenFrame(options) && hostLayer && hostWindow !== window) {
            var fullscreenFrame;
            var frameOptions = $.extend({}, options);
            var frameSuccess = frameOptions.success;
            var frameEnd = frameOptions.end;
            frameOptions.success = function(layero, index) {
                fullscreenFrame = lockFullscreenFrame(hostWindow, options);
                normalizeDrawerLayer(layero, index, document);
                if (typeof frameSuccess === 'function') {
                    frameSuccess.call(this, layero, index);
                }
                normalizeDrawerLayer(layero, index, document);
            };
            frameOptions.end = function() {
                unlockFullscreenFrame(hostWindow, fullscreenFrame);
                if (typeof frameEnd === 'function') {
                    frameEnd.apply(this, arguments);
                }
            };
            return originalOpen.call(this, frameOptions);
        }

        if (!shouldOpenInParent(options) || !hostLayer || hostWindow === window) {
            return originalOpen.apply(this, arguments);
        }

        var hostDocument = hostWindow.document;
        cleanupCopiedPageStyles(hostDocument);
        ensureDrawerStyles(hostDocument);
        copyScopedPageStyles(hostDocument);

        var drawerOptions = $.extend({}, options);
        var originalSuccess = drawerOptions.success;
        var originalEnd = drawerOptions.end;
        drawerOptions.skin = $.trim((drawerOptions.skin || '') + ' global-parent-drawer');
        if (Array.isArray(drawerOptions.area)) {
            drawerOptions.area = [drawerOptions.area[0] || '700px', '100%'];
        }
        drawerOptions.success = function(layero, index) {
            normalizeDrawerLayer(layero, index, hostDocument);
            if (typeof originalSuccess === 'function') {
                originalSuccess.call(this, layero, index);
            }
            normalizeDrawerLayer(layero, index, hostDocument);
            renderHostLayui(hostWindow);
        };
        var openedIndex;
        drawerOptions.end = function() {
            delete parentDrawerIndexes[openedIndex];
            if (typeof originalEnd === 'function') {
                originalEnd.apply(this, arguments);
            }
        };

        openedIndex = hostLayer.open(drawerOptions);
        parentDrawerIndexes[openedIndex] = hostLayer;
        return openedIndex;
    };

    layer.close = function(index) {
        if (parentDrawerIndexes[index]) {
            parentDrawerIndexes[index].close(index);
            delete parentDrawerIndexes[index];
            return;
        }
        return originalClose.apply(this, arguments);
    };

    layer.closeAll = function(type) {
        if (!type || type === 'page') {
            $.each(parentDrawerIndexes, function(index, hostLayer) {
                hostLayer.close(index);
            });
            parentDrawerIndexes = {};
        }
        return originalCloseAll.apply(this, arguments);
    };
}
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
