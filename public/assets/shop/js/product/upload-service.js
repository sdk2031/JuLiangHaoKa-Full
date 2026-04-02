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

        var uploadFile = file;
        var finished = false;

        function doneSuccess(response) {
            if (finished) {
                return;
            }
            finished = true;
            if (response && response.code === 1 && response.data && response.data.url) {
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
                return;
            }
            layer.msg((response && response.msg) || '上传失败', { icon: 2 });
            resetUploadItem(type);
        }

        function doneError(msg) {
            if (finished) {
                return;
            }
            finished = true;
            layer.msg(msg || '上传失败，请重试', { icon: 2 });
            resetUploadItem(type);
        }

        var continueUpload = function () {
            var formData = new FormData();
            formData.append('file', uploadFile);
            formData.append('path', 'shop/idcard/' + type);

            // 兼容未引入 jQuery 的页面
            if (win.$ && typeof win.$.ajax === 'function') {
                win.$.ajax({
                    url: '/common/Upload/single',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    timeout: 30000,
                    success: doneSuccess,
                    error: function () {
                        doneError('上传超时或失败，请重试');
                    }
                });
                return;
            }

            var xhr = new XMLHttpRequest();
            xhr.open('POST', '/common/Upload/single', true);
            xhr.timeout = 30000;
            xhr.onreadystatechange = function () {
                if (xhr.readyState !== 4) {
                    return;
                }
                if (xhr.status >= 200 && xhr.status < 300) {
                    var response = null;
                    try {
                        response = JSON.parse(xhr.responseText || '{}');
                    } catch (e) {
                        doneError('上传响应异常，请重试');
                        return;
                    }
                    doneSuccess(response);
                } else {
                    doneError('上传失败，请重试');
                }
            };
            xhr.onerror = function () {
                doneError('网络异常，上传失败');
            };
            xhr.ontimeout = function () {
                doneError('上传超时，请重试');
            };
            xhr.send(formData);
        };

        compressImageForUpload(file, type, function (compressedFile) {
            uploadFile = compressedFile || file;
            continueUpload();
        }, function () {
            uploadFile = file;
            continueUpload();
        });
    }

    function compressImageForUpload(file, type, onDone, onFail) {
        try {
            var ext = (file.name || '').split('.').pop().toLowerCase();
            var mime = (file.type || '').toLowerCase();
            if (ext === 'heic' || ext === 'heif' || mime === 'image/heic' || mime === 'image/heif') {
                onDone(file);
                return;
            }

            var shouldCompress = file.size > 350 * 1024;
            if (!shouldCompress) {
                onDone(file);
                return;
            }

            var settled = false;
            var watchdog = setTimeout(function () {
                if (settled) {
                    return;
                }
                settled = true;
                onDone(file);
            }, 8000);

            function finishOk(resultFile) {
                if (settled) {
                    return;
                }
                settled = true;
                clearTimeout(watchdog);
                onDone(resultFile);
            }

            function finishFail(err) {
                if (settled) {
                    return;
                }
                settled = true;
                clearTimeout(watchdog);
                onFail(err);
            }

            var reader = new FileReader();
            reader.onload = function (e) {
                var img = new Image();
                img.onload = function () {
                    try {
                        var maxEdge = type === 'id_card_face' ? 1600 : 1800;
                        var quality = type === 'id_card_face' ? 0.78 : 0.82;
                        var width = img.width;
                        var height = img.height;

                        if (width > maxEdge || height > maxEdge) {
                            if (width > height) {
                                height = Math.round(height * (maxEdge / width));
                                width = maxEdge;
                            } else {
                                width = Math.round(width * (maxEdge / height));
                                height = maxEdge;
                            }
                        }

                        var canvas = document.createElement('canvas');
                        canvas.width = width;
                        canvas.height = height;
                        var ctx = canvas.getContext('2d');
                        if (!ctx) {
                            finishOk(file);
                            return;
                        }

                        ctx.drawImage(img, 0, 0, width, height);
                        canvas.toBlob(function (blob) {
                            if (!blob) {
                                finishOk(file);
                                return;
                            }
                            var compressed = new File([blob], file.name, { type: 'image/jpeg' });
                            finishOk(compressed.size < file.size ? compressed : file);
                        }, 'image/jpeg', quality);
                    } catch (err) {
                        finishFail(err);
                    }
                };
                img.onerror = finishFail;
                img.src = e.target.result;
            };
            reader.onerror = finishFail;
            reader.readAsDataURL(file);
        } catch (ex) {
            onFail(ex);
        }
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
