(function (win) {
    var uploadedImages = {
        id_card_front: null,
        id_card_back: null,
        id_card_face: null,
        id_card_four: null
    };

    function getUploadMeta(type) {
        var map = {
            id_card_front: { prefix: 'front', buttonId: 'id_card_front_btn' },
            id_card_back: { prefix: 'back', buttonId: 'id_card_back_btn' },
            id_card_face: { prefix: 'face', buttonId: 'id_card_face_btn' },
            id_card_four: { prefix: 'four', buttonId: 'id_card_four_btn' }
        };
        return map[type] || null;
    }

    function resetUploadItem(type) {
        var meta = getUploadMeta(type);
        if (!meta) {
            return;
        }

        uploadedImages[type] = null;
        var uploadItem = document.getElementById(meta.prefix + 'Upload');
        var previewWrap = document.getElementById(meta.prefix + 'Preview');
        var uploadBtn = document.getElementById(meta.buttonId);
        var fileInput = document.getElementById(type);

        if (uploadItem) {
            uploadItem.classList.remove('uploaded');
        }
        if (previewWrap) {
            previewWrap.innerHTML = '';
        }
        if (uploadBtn) {
            uploadBtn.textContent = '点击上传';
            uploadBtn.style.pointerEvents = 'auto';
        }
        if (fileInput) {
            fileInput.value = '';
        }
    }

    function handleImageUpload(input, type) {
        var file = input.files[0];
        if (!file) {
            return;
        }

        if (!file.type.startsWith('image/')) {
            layer.msg('请选择图片文件', { icon: 2 });
            return;
        }

        var maxSize = 5 * 1024 * 1024;
        var sizeMsg = '5MB';
        if (win.API_TYPE === 1003) {
            maxSize = 2 * 1024 * 1024;
            sizeMsg = '2MB';
        }

        if (file.size > maxSize) {
            layer.msg('图片大小不能超过' + sizeMsg, { icon: 2 });
            return;
        }

        var meta = getUploadMeta(type);
        if (!meta) {
            layer.msg('上传类型错误', { icon: 2 });
            return;
        }

        var uploadItem = document.getElementById(meta.prefix + 'Upload');
        var previewWrap = document.getElementById(meta.prefix + 'Preview');
        var uploadBtn = document.getElementById(meta.buttonId);
        if (uploadBtn) {
            uploadBtn.textContent = '上传中...';
            uploadBtn.style.pointerEvents = 'none';
        }

        var formData = new FormData();
        formData.append('file', file);
        formData.append('path', 'shop/idcard/' + type);

        var onSuccess = function (response) {
            if (response && response.code === 1) {
                uploadedImages[type] = response.data.url;
                if (uploadItem) {
                    uploadItem.classList.add('uploaded');
                }
                if (previewWrap) {
                    previewWrap.innerHTML = '<img src="' + response.data.url + '" class="upload-preview" alt="预览">';
                }
                if (uploadBtn) {
                    uploadBtn.textContent = '重新上传';
                    uploadBtn.style.pointerEvents = 'auto';
                }
                layer.msg('上传成功', { icon: 1, time: 1500 });
            } else {
                layer.msg((response && response.msg) || '上传失败', { icon: 2 });
                resetUploadItem(type);
            }
        };

        var onError = function () {
            layer.msg('上传失败，请重试', { icon: 2 });
            resetUploadItem(type);
        };

        // 兼容未引入 jQuery 的页面
        if (win.$ && typeof win.$.ajax === 'function') {
            win.$.ajax({
                url: '/common/Upload/single',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: onSuccess,
                error: onError
            });
            return;
        }

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/common/Upload/single', true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) {
                return;
            }
            if (xhr.status >= 200 && xhr.status < 300) {
                var response = null;
                try {
                    response = JSON.parse(xhr.responseText || '{}');
                } catch (e) {
                    onError();
                    return;
                }
                onSuccess(response);
            } else {
                onError();
            }
        };
        xhr.onerror = onError;
        xhr.send(formData);
    }

    win.uploadedImages = uploadedImages;
    win.triggerUpload = function (type) {
        document.getElementById(type).click();
    };
    win.handleImageUpload = handleImageUpload;
    win.removeImage = function (type) {
        resetUploadItem(type);
    };
    win.ShopProductUploadService = {
        uploadedImages: uploadedImages,
        getUploadMeta: getUploadMeta,
        resetUploadItem: resetUploadItem,
        handleImageUpload: handleImageUpload
    };
})(window);
