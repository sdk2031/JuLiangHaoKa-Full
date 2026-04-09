(function (win) {
    function getSubmitButton() {
        return document.querySelector('.submit-btn') || document.querySelector('button[onclick*="submitOrder"]');
    }

    function setButtonState(button, disabled, text) {
        if (!button) {
            return;
        }
        button.disabled = disabled;
        button.textContent = text;
    }

    function appendField(formData, name) {
        var field = document.querySelector('[name="' + name + '"]');
        formData.append(name, field ? field.value : '');
    }

    function getAvailablePaymentMethods() {
        var methods = win.SHOP_PAYMENT_METHODS;
        if (!Array.isArray(methods)) {
            return [];
        }
        return methods.filter(function (item) {
            return item && typeof item.type === 'string' && item.type.trim() !== '';
        });
    }

    function detectClientEnv() {
        var ua = (navigator.userAgent || '').toLowerCase();
        return ua.indexOf('micromessenger') !== -1 ? 'wechat' : 'browser';
    }

    function buildFormData() {
        var formData = new FormData();
        formData.append('shop_code', win.SHOP_CODE);
        formData.append('product_id', win.PRODUCT_ID);

        [
            'customer_name',
            'customer_phone',
            'order_phone',
            'verify_code',
            'verify_type',
            'customer_idcard',
            'customer_address',
            'customer_city',
            'remark',
            'province',
            'city',
            'district',
            'province_code',
            'city_code',
            'district_code'
        ].forEach(function (name) {
            appendField(formData, name);
        });

        var selectedNumberEl = document.querySelector('input[name="selected_number"]');
        if (selectedNumberEl && selectedNumberEl.value) {
            formData.append('selected_number', selectedNumberEl.value);
        }

        if (win.NEED_ID_PHOTO) {
            formData.append('id_card_front', win.uploadedImages.id_card_front || '');
            formData.append('id_card_back', win.uploadedImages.id_card_back || '');
            formData.append('id_card_face', win.uploadedImages.id_card_face || '');
        }

        if (win.NEED_FOUR_PHOTO) {
            formData.append('id_card_four', win.uploadedImages.id_card_four || '');
        }

        return formData;
    }

    function goPayPage(tempOrderNo, paymentType) {
        var query = [];
        if (paymentType) {
            query.push('payment_type=' + encodeURIComponent(paymentType));
        }
        query.push('client_env=' + encodeURIComponent(detectClientEnv()));
        var payUrl = '/index/pay/index/temp_order_no/' + tempOrderNo + (query.length ? '?' + query.join('&') : '');
        win.location.href = payUrl;
    }

    function selectPaymentAndGo(tempOrderNo) {
        var methods = getAvailablePaymentMethods();
        if (methods.length === 0) {
            layer.msg('暂无可用支付方式，请联系管理员配置', { icon: 2 });
            return;
        }

        var html = '<div style="padding:16px 0 10px;box-sizing:border-box;">'
            + '<div style="font-size:16px;font-weight:600;color:#222;margin-bottom:10px;text-align:center;">请选择支付方式</div>'
            + '<div id="payMethodList" style="padding:0 14px;">';

        methods.forEach(function (item) {
            var label = item.name || item.type;
            var isWechat = String(item.type || '').toLowerCase() === 'wechat';
            var iconSrc = isWechat ? '/static/images/shopimg/wpay.png' : '/static/images/shopimg/alipay.png';
            html += '<div class="pay-method-item" data-type="' + item.type + '"'
                + ' style="padding:16px 2px;display:flex;justify-content:space-between;align-items:center;cursor:pointer;border-bottom:1px solid #f0f0f0;">'
                + '<div style="display:flex;align-items:center;gap:10px;">'
                + '<img src="' + iconSrc + '" style="width:22px;height:22px;display:block;" alt="' + label + '">'
                + '<span style="font-size:15px;color:#333;">' + label + '</span>'
                + '</div>'
                + '<span style="color:#bfbfbf;font-size:18px;">›</span>'
                + '</div>';
        });

        html += '</div></div>';

        var index = layer.open({
            type: 1,
            title: false,
            closeBtn: 0,
            shadeClose: true,
            shade: 0.35,
            area: ['100%', 'auto'],
            offset: 'b',
            anim: 2,
            content: html,
            success: function (layero) {
                layero.css({
                    borderRadius: '18px 18px 0 0',
                    overflow: 'hidden'
                });
                layero.find('.layui-layer-content').css({
                    overflow: 'hidden',
                    borderRadius: '18px 18px 0 0'
                });
                layero.find('.pay-method-item').on('click', function () {
                    var type = String(this.getAttribute('data-type') || '').trim();
                    layer.close(index);
                    if (!type) {
                        layer.msg('支付方式无效', { icon: 2 });
                        return;
                    }
                    goPayPage(tempOrderNo, type);
                });
            }
        });
    }

    function handleSubmitSuccess(data) {
        if (data.code !== 1) {
            layer.msg(data.msg || '订单提交失败', { icon: 2 });
            return;
        }

        if (data.data.need_payment === true) {
            selectPaymentAndGo(data.data.temp_order_no);
            return;
        }

        var orderNo = data.data.order_no || '';
        if (!orderNo) {
            console.error('订单号为空', data);
            layer.msg('订单提交成功，但订单号获取失败', { icon: 1, time: 2000 });
            return;
        }

        fetch('/index/shop/setOrderAccess', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                order_no: orderNo
            })
        }).then(function () {
            layer.msg('订单提交成功！', { icon: 1, time: 2000 }, function () {
                win.location.href = '/index/pay/success/order_no/' + orderNo;
            });
        }).catch(function () {
            layer.msg('订单提交成功！', { icon: 1, time: 2000 }, function () {
                win.location.href = '/index/pay/success/order_no/' + orderNo;
            });
        });
    }

    function submitOrder() {
        var submitBtn = getSubmitButton();
        if (!submitBtn) {
            layer.msg('找不到提交按钮', { icon: 2 });
            return;
        }

        var originalText = submitBtn.textContent;
        setButtonState(submitBtn, true, '提交中...');
        var loadingIndex = layer.load(1, { shade: [0.3, '#000'] });

        var formData = buildFormData();
        if (!win.ShopProductValidators.validateCommonFields(formData, layer) ||
            !win.ShopProductValidators.validateApiSpecificFields(formData, win.API_TYPE, layer)) {
            layer.close(loadingIndex);
            setButtonState(submitBtn, false, originalText);
            return;
        }

        fetch('/index/shop/submitOrderWithPayment', {
            method: 'POST',
            body: formData
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                layer.close(loadingIndex);
                setButtonState(submitBtn, false, originalText);
                win.closeOrderDrawer();
                handleSubmitSuccess(data);
            })
            .catch(function (error) {
                layer.close(loadingIndex);
                setButtonState(submitBtn, false, originalText);
                layer.msg('网络错误，请重试', { icon: 2 });
                console.error('Order submission error:', error);
            });
    }

    win.ShopProductOrderService = {
        buildFormData: buildFormData,
        submitOrder: submitOrder
    };

    win.submitOrder = submitOrder;
})(window);
