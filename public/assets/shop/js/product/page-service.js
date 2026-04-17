(function (win) {
    var currentLayer = null;
    var currentJquery = null;

    function fillInputWithJquery(name, value) {
        if (!currentJquery) {
            return;
        }
        var $input = currentJquery('input[name="' + name + '"]');
        if ($input.length && value) {
            $input.val(value);
            $input.trigger('input').trigger('change');
        }
    }

    function syncOrderPhone() {
        if (!currentJquery) {
            return;
        }
        currentJquery(document).on('input change', 'input[name="order_phone"]', function () {
            var orderPhone = currentJquery(this).val();
            var customerPhoneInput = currentJquery('input[name="customer_phone"]');
            if (customerPhoneInput.length > 0) {
                customerPhoneInput.val(orderPhone).trigger('input').trigger('change');
            }
        });
    }

    function setupResubmit() {
        if (!currentJquery || !currentLayer) {
            return;
        }

        var urlParams = new URLSearchParams(win.location.search);
        var resubmitFrom = urlParams.get('resubmit_from');
        var resubmitToken = urlParams.get('token');

        if (!resubmitFrom || !resubmitToken) {
            return;
        }

        currentJquery.post('/index/shop/getResubmitOrderData', {
            order_id: resubmitFrom,
            token: resubmitToken
        }, function (res) {
            if (res.code === 1 && res.data) {
                var order = res.data;
                fillInputWithJquery('customer_name', order.customer_name);
                fillInputWithJquery('order_phone', order.phone);
                fillInputWithJquery('customer_idcard', order.idcard);
                fillInputWithJquery('customer_address', order.address);

                if (order.province && order.city && order.district) {
                    fillInputWithJquery('customer_city', order.province + ' ' + order.city + ' ' + order.district);
                    fillInputWithJquery('province', order.province);
                    fillInputWithJquery('city', order.city);
                    fillInputWithJquery('district', order.district);
                    fillInputWithJquery('province_code', order.province_code);
                    fillInputWithJquery('city_code', order.city_code);
                    fillInputWithJquery('district_code', order.district_code);
                }

                setTimeout(function () {
                    if (typeof win.showOrderDrawer === 'function') {
                        win.showOrderDrawer();
                    }
                    currentLayer.msg('已自动填充原订单信息，请补全剩余资料', { icon: 1, time: 3000 });
                }, 800);
            } else {
                currentLayer.msg(res.msg || '获取订单数据失败', { icon: 2 });
            }
        }).fail(function (xhr, status, error) {
            console.error('获取重提订单数据失败:', error);
            currentLayer.msg('网络错误，请重试', { icon: 2 });
        });
    }

    function showOrderDrawer() {
        document.body.style.overflow = 'hidden';
        document.body.style.position = 'fixed';
        document.body.style.width = '100%';
        document.getElementById('orderDrawer').style.display = 'block';
        setTimeout(function () {
            document.querySelector('.drawer').classList.add('show');
        }, 10);
    }

    function closeOrderDrawer() {
        document.body.style.overflow = '';
        document.body.style.position = '';
        document.body.style.width = '';
        document.querySelector('.drawer').classList.remove('show');
        setTimeout(function () {
            document.getElementById('orderDrawer').style.display = 'none';
        }, 300);
    }

    function parseCustomFieldConfig() {
        var raw = String(win.PRODUCT_CUSTOM_FIELDS || '').trim();
        if (!raw) {
            return [];
        }
        try {
            var parsed = JSON.parse(raw);
            if (!Array.isArray(parsed)) {
                return [];
            }
            return parsed.map(function (item) {
                item = item || {};
                var normalized = {
                    name: String(item.name || '').trim(),
                    label: String(item.label || '').trim(),
                    type: String(item.type || 'text').trim() || 'text',
                    required: String(item.required || item.required === 0 ? item.required : '0') === '1',
                    placeholder: String(item.placeholder || '').trim(),
                    options: []
                };
                if (normalized.type !== 'select' && normalized.type !== 'textarea' && normalized.type !== 'number' && normalized.type !== 'date') {
                    normalized.type = 'text';
                }
                if (Array.isArray(item.options)) {
                    normalized.options = item.options.map(function (option) {
                        if (typeof option === 'string') {
                            return {value: option.trim(), label: option.trim()};
                        }
                        if (option && typeof option === 'object') {
                            var value = String(option.value || option.label || '').trim();
                            var label = String(option.label || option.value || '').trim();
                            return value ? {value: value, label: label || value} : null;
                        }
                        return null;
                    }).filter(Boolean);
                }
                return normalized;
            }).filter(function (item) {
                return item.name && item.label;
            });
        } catch (e) {
            return [];
        }
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function renderCustomOrderFields() {
        var container = document.getElementById('customOrderFieldsContainer');
        if (!container) {
            return;
        }
        var fields = parseCustomFieldConfig();
        if (!fields.length) {
            container.innerHTML = '';
            return;
        }

        var html = '';
        fields.forEach(function (field) {
            var inputHtml = '';
            var placeholder = escapeHtml(field.placeholder || ('请输入' + field.label));
            var label = escapeHtml(field.label);
            var requiredAttr = field.required ? ' required' : '';
            var requiredMark = field.required ? '<span class="required">*</span>' : '';
            if (field.type === 'textarea') {
                inputHtml = '<textarea name="custom_field__' + field.name + '" class="form-input" placeholder="' + placeholder + '"' + requiredAttr + ' rows="3" style="resize: vertical; min-height: 60px;"></textarea>';
            } else if (field.type === 'select') {
                inputHtml = '<select name="custom_field__' + field.name + '" class="form-input"' + requiredAttr + '><option value="">请选择' + label + '</option>' +
                    field.options.map(function (option) {
                        return '<option value="' + escapeHtml(option.value) + '">' + escapeHtml(option.label) + '</option>';
                    }).join('') + '</select>';
            } else {
                var inputType = field.type === 'number' ? 'number' : (field.type === 'date' ? 'date' : 'text');
                inputHtml = '<input type="' + inputType + '" name="custom_field__' + field.name + '" class="form-input" placeholder="' + placeholder + '"' + requiredAttr + '>';
            }

            html += '<div class="form-group custom-order-field-group" data-custom-field="' + field.name + '">' +
                '<div class="form-row">' +
                '<label class="form-label">' + label + requiredMark + '</label>' +
                '<div class="form-input-wrapper">' + inputHtml + '</div>' +
                '</div>' +
                '</div>';
        });
        container.innerHTML = html;
    }

    function setupVerifyCode() {
        if (!currentLayer) {
            return;
        }

        var countdown = 0;
        var verifyTypeField = document.querySelector('input[name="verify_type"]');
        if (!verifyTypeField) {
            return;
        }

        var verifyType = verifyTypeField.value;
        if (verifyType === 'none') {
            return;
        }

        function refreshImageCaptcha() {
            var captchaImg = document.getElementById('orderImageCaptcha');
            if (captchaImg) {
                captchaImg.src = '/captcha?_t=' + Date.now();
            }
        }

        if (verifyType === 'image') {
            refreshImageCaptcha();
            var refreshBtn = document.getElementById('refreshOrderCaptchaBtn');
            if (refreshBtn) {
                refreshBtn.addEventListener('click', function () {
                    refreshImageCaptcha();
                });
            }
            return;
        }

        var button = document.getElementById('sendOrderCodeBtn');
        if (!button) {
            return;
        }

        function startCountdown() {
            countdown = 60;
            button.disabled = true;

            var timer = setInterval(function () {
                countdown--;
                button.textContent = countdown + 's后重发';
                if (countdown <= 0) {
                    clearInterval(timer);
                    button.disabled = false;
                    button.textContent = '发送验证码';
                }
            }, 1000);
        }

        button.addEventListener('click', function () {
            if (countdown > 0) {
                return;
            }

            var target = '';
            var targetName = '';
            var url = '';
            var param = {};

            if (verifyType === 'sms') {
                var customerPhoneInput = document.querySelector('input[name="customer_phone"]');
                var orderPhoneInput = document.querySelector('input[name="order_phone"]');
                target = (customerPhoneInput && customerPhoneInput.value) || (orderPhoneInput && orderPhoneInput.value) || '';
                targetName = '手机号';
                if (!target || !/^1[3-9]\d{9}$/.test(target)) {
                    currentLayer.msg('请输入正确的手机号码');
                    return;
                }
                if (customerPhoneInput && customerPhoneInput.value !== target) {
                    customerPhoneInput.value = target;
                }
                url = '/index/shop/sendVerifyCode';
                param.phone = target;
                param.shop_code = win.SHOP_CODE;
            }

            var loadIndex = currentLayer.load(2);
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams(param)
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    currentLayer.close(loadIndex);
                    if (data.code === 1) {
                        if (data.code_for_fill) {
                            var verifyInput = document.querySelector('input[name="verify_code"]');
                            if (verifyInput) {
                                verifyInput.value = data.code_for_fill;
                            }
                            currentLayer.msg(data.msg || '验证码已发送到您的' + targetName + '，已自动填入验证码', { icon: 1, time: 3000 });
                        } else {
                            currentLayer.msg('验证码已发送到您的' + targetName, { icon: 1 });
                        }
                        startCountdown();
                    } else {
                        currentLayer.msg(data.msg || '发送失败', { icon: 2 });
                    }
                })
                .catch(function () {
                    currentLayer.close(loadIndex);
                    currentLayer.msg('网络错误，请稍后重试', { icon: 2 });
                });
        });
    }

    function isLikelyImageUrl(url) {
        var normalizedUrl = String(url || '').trim();
        if (!normalizedUrl) {
            return false;
        }

        if (/^data:image\//i.test(normalizedUrl)) {
            return true;
        }

        var cleanUrl = normalizedUrl.split('#')[0].split('?')[0];
        if (/\.(jpg|jpeg|png|gif|bmp|webp|svg)$/i.test(cleanUrl)) {
            return true;
        }

        return /\/uploads\/products\/four_photo\//i.test(cleanUrl);
    }

    function handleFourPhotoQuery(url) {
        if (!currentLayer) {
            return;
        }
        if (!url) {
            currentLayer.msg('查询链接为空');
            return;
        }

        var isImage = isLikelyImageUrl(url);
        if (isImage) {
            currentLayer.open({
                type: 1,
                title: win.FOUR_PHOTO_TITLE,
                area: ['320px', '380px'],
                shade: 0.6,
                shadeClose: true,
                content: '<div style="padding: 15px; text-align: center;">' +
                    '<div style="margin-bottom: 10px; font-size: 14px; color: #333;">请扫描下方二维码进行查询</div>' +
                    '<img src="' + url + '" alt="查询二维码" style="width: 250px; height: 250px; border: 1px solid #ddd; border-radius: 8px; object-fit: contain;">' +
                    '<div style="margin-top: 10px; font-size: 12px; color: #999;">请使用微信或浏览器扫码查询</div>' +
                    '</div>'
            });
            return;
        }

        win.open(url, '_blank');
    }

    function showPaidCardSuccess(orderNo, totalPrice) {
        if (!currentLayer) {
            return;
        }
        currentLayer.open({
            type: 1,
            title: false,
            closeBtn: 1,
            shadeClose: true,
            skin: 'layui-layer-nobg',
            content: '<div style="padding: 30px; text-align: center; background: #fff; border-radius: 12px;">' +
                '<div style="font-size: 50px; color: #52c41a; margin-bottom: 15px;">✓</div>' +
                '<div style="font-size: 18px; font-weight: bold; margin-bottom: 10px; color: #333;">支付成功</div>' +
                '<div style="font-size: 14px; color: #666; margin-bottom: 5px;">订单号：' + orderNo + '</div>' +
                '<div style="font-size: 14px; color: #52c41a; margin-bottom: 15px;">已支付：¥' + totalPrice.toFixed(2) + '</div>' +
                '<div style="font-size: 13px; color: #999; margin-bottom: 20px;">此卡需要联系客服审核后发货</div>' +
                (win.SHOP_KEFU_QRCODE ? '<div style="font-size: 13px; color: #999; margin-bottom: 10px;">请联系客服</div><img src="' + win.SHOP_KEFU_QRCODE + '" style="width: 150px; height: 150px; border: 1px solid #eee; border-radius: 8px;">' : '') +
                '<button onclick="location.href=\'/index/shop/order_query/shop_code/' + win.SHOP_CODE + '\'" style="width: 100%; padding: 12px; background: var(--theme-color); color: #fff; border: none; border-radius: 25px; font-size: 16px; cursor: pointer; margin-top: 20px;">查询订单</button>' +
                '</div>'
        });
    }

    function showFreeCardSuccess(orderNo) {
        if (!currentLayer) {
            return;
        }
        currentLayer.open({
            type: 1,
            title: false,
            closeBtn: 1,
            shadeClose: true,
            skin: 'layui-layer-nobg',
            content: '<div style="padding: 30px; text-align: center; background: #fff; border-radius: 12px;">' +
                '<div style="font-size: 50px; color: #52c41a; margin-bottom: 15px;">✓</div>' +
                '<div style="font-size: 18px; font-weight: bold; margin-bottom: 10px; color: #333;">订单提交成功</div>' +
                '<div style="font-size: 14px; color: #666; margin-bottom: 20px;">预计24小时内审核发货</div>' +
                (win.SHOP_KEFU_QRCODE ? '<div style="font-size: 13px; color: #999; margin-bottom: 10px;">如有问题请联系客服</div><img src="' + win.SHOP_KEFU_QRCODE + '" style="width: 150px; height: 150px; border: 1px solid #eee; border-radius: 8px;">' : '') +
                '<button onclick="location.href=\'/index/shop/order_query/shop_code/' + win.SHOP_CODE + '\'" style="width: 100%; padding: 12px; background: var(--theme-color); color: #fff; border: none; border-radius: 25px; font-size: 16px; cursor: pointer; margin-top: 20px;">查询订单</button>' +
                '</div>'
        });
    }

    function findAreaCodesInTianchengData(areaData, province, city, district) {
        var codes = {
            province_code: '',
            city_code: '',
            district_code: ''
        };

        for (var i = 0; i < areaData.length; i++) {
            var provinceItem = areaData[i];
            var provinceName = provinceItem.label || provinceItem.name || '';

            if (provinceName && (
                provinceName.indexOf(province) !== -1 ||
                province.indexOf(provinceName) !== -1 ||
                provinceName.replace('省', '') === province.replace('省', '')
            )) {
                codes.province_code = provinceItem.id;
                if (provinceItem.children && Array.isArray(provinceItem.children)) {
                    for (var j = 0; j < provinceItem.children.length; j++) {
                        var cityItem = provinceItem.children[j];
                        var cityName = cityItem.label || cityItem.name || '';
                        if (cityName && (
                            cityName.indexOf(city) !== -1 ||
                            city.indexOf(cityName) !== -1 ||
                            cityName.replace('市', '') === city.replace('市', '')
                        )) {
                            codes.city_code = cityItem.id;
                            if (district && cityItem.children && Array.isArray(cityItem.children)) {
                                for (var k = 0; k < cityItem.children.length; k++) {
                                    var districtItem = cityItem.children[k];
                                    var districtName = districtItem.label || districtItem.name || '';
                                    if (districtName && (
                                        districtName.indexOf(district) !== -1 ||
                                        district.indexOf(districtName) !== -1 ||
                                        districtName.replace(/[区县市]/, '') === district.replace(/[区县市]/, '')
                                    )) {
                                        codes.district_code = districtItem.id;
                                        break;
                                    }
                                }
                            } else if (cityItem.children && cityItem.children.length > 0) {
                                codes.district_code = cityItem.children[0].id;
                            }
                            break;
                        }
                    }
                }
                break;
            }
        }

        return codes;
    }

    function getAreaCodesForTiancheng(province, city, district) {
        if (!currentLayer) {
            return;
        }
        fetch('/api/kapi.tiancheng.product/queryArea', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                product_id: win.PRODUCT_ID
            })
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (result) {
                if (result.code === 0 && result.data && Array.isArray(result.data)) {
                    var codes = findAreaCodesInTianchengData(result.data, province, city, district);
                    if (codes.province_code) {
                        document.querySelector('input[name="province_code"]').value = codes.province_code;
                    }
                    if (codes.city_code) {
                        document.querySelector('input[name="city_code"]').value = codes.city_code;
                    }
                    if (codes.district_code) {
                        document.querySelector('input[name="district_code"]').value = codes.district_code;
                    }
                    currentLayer.msg('位置获取成功，已自动匹配地区编码', { icon: 1 });
                } else {
                    currentLayer.msg('位置获取成功，但无法获取地区编码', { icon: 2 });
                }
            })
            .catch(function () {
                currentLayer.msg('位置获取成功，但地区编码获取失败', { icon: 2 });
            });
    }

    function getLocationByIP() {
        if (!currentLayer) {
            return;
        }
        var cityInput = document.querySelector('input[name="customer_city"]');
        var btn = document.querySelector('.get-location-btn');
        if (!cityInput || !btn) {
            return;
        }

        btn.textContent = '获取中...';
        btn.disabled = true;

        fetch('/index/shop/getIpLocationApi')
            .then(function (response) {
                return response.json();
            })
            .then(function (result) {
                if (!(result.code === 1 && result.data && result.data.rgeo)) {
                    throw new Error(result.msg || '无法获取位置信息');
                }

                var rgeo = result.data.rgeo;
                if (rgeo.country !== '中国') {
                    throw new Error('非中国地址');
                }

                var location = '';
                if (rgeo.province) {
                    location = rgeo.province;
                    if (!location.endsWith('省') && !location.endsWith('市') && !location.endsWith('区')) {
                        location += '省';
                    }
                }

                if (rgeo.city && rgeo.city !== rgeo.province) {
                    var city = rgeo.city;
                    if (!city.endsWith('市') && !city.endsWith('区') && !city.endsWith('县')) {
                        city += '市';
                    }
                    location += ' ' + city;
                }

                if (rgeo.district && rgeo.district !== rgeo.city) {
                    location += ' ' + rgeo.district;
                }

                cityInput.value = location;
                var cityName = rgeo.city || '';
                if (cityName && !cityName.endsWith('市') && !cityName.endsWith('区') && !cityName.endsWith('县')) {
                    cityName += '市';
                }

                document.querySelector('input[name="province"]').value = rgeo.province || '';
                document.querySelector('input[name="city"]').value = cityName;
                document.querySelector('input[name="district"]').value = rgeo.district || '';

                if (win.API_TYPE === 1001) {
                    getAreaCodesForTiancheng(rgeo.province || '', cityName, rgeo.district || '');
                }
            })
            .catch(function () {
                currentLayer.msg('位置获取失败，请手动选择', { icon: 2 });
            })
            .finally(function () {
                btn.textContent = '获取';
                btn.disabled = false;
            });
    }

    function showServiceQrcode(qrcodeUrl, serviceText) {
        if (!currentLayer) {
            return;
        }
        var content = '<div style="text-align: center; padding: 20px;">' +
            '<img src="' + qrcodeUrl + '" style="width: 200px; height: 200px; border-radius: 8px;" alt="客服二维码">' +
            '<div style="margin-top: 15px; color: #666; font-size: 14px;">扫描二维码' + serviceText + '</div>' +
            '</div>';

        currentLayer.open({
            type: 1,
            title: serviceText,
            content: content,
            area: ['280px', 'auto'],
            shadeClose: true
        });
    }

    function callPhone(phone) {
        win.location.href = 'tel:' + phone;
    }

    function contactService(link) {
        if (link.startsWith('http')) {
            win.open(link);
            return;
        }
        win.location.href = link;
    }

    function generateProductPoster(shopCode, productId, templateId) {
        if (!currentLayer) {
            return;
        }
        templateId = templateId || 1;
        var loadIndex = currentLayer.load(2);

        fetch('/index/shop/generateProductPoster', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: new URLSearchParams({
                shop_code: shopCode,
                product_id: productId,
                template_id: templateId
            })
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (response) {
                currentLayer.close(loadIndex);
                if (response.code !== 1) {
                    currentLayer.msg(response.msg || '海报生成失败', { icon: 2 });
                    return;
                }

                var isMobile = win.innerWidth <= 768;
                var areaWidth = isMobile ? '70%' : '350px';
                var areaHeight = isMobile ? '48%' : '550px';
                var imgStyle = isMobile ? 'width: 100%; height: auto; max-height: 45vh;' : 'max-width: 100%; max-height: 300px;';

                currentLayer.open({
                    type: 1,
                    title: '产品推广海报',
                    content: '<div style="text-align: center; padding: 15px; overflow-y: auto; max-height: calc(100vh - 120px);">' +
                        '<img src="' + response.data.poster_url + '" style="' + imgStyle + '" alt="产品推广海报">' +
                        '</div>',
                    area: [areaWidth, areaHeight],
                    shadeClose: true
                });
            })
            .catch(function () {
                currentLayer.close(loadIndex);
                currentLayer.msg('网络错误，海报生成失败', { icon: 2 });
            });
    }

    function bindOrderBarActions() {
        var orderBarItems = document.querySelectorAll('.order-bar-item[data-action]');
        orderBarItems.forEach(function (item) {
            item.addEventListener('click', function () {
                var action = this.getAttribute('data-action');
                if (action === 'home') {
                    win.location.href = '/index/shop/index/shop_code/' + win.SHOP_CODE;
                } else if (action === 'share') {
                    generateProductPoster(win.SHOP_CODE, win.PRODUCT_ID, 1);
                } else if (action === 'order') {
                    showOrderDrawer();
                }
            });
        });

        var fourPhotoBtns = document.querySelectorAll('[data-four-photo]');
        fourPhotoBtns.forEach(function (button) {
            button.addEventListener('click', function (event) {
                if (event && typeof event.preventDefault === 'function') {
                    event.preventDefault();
                }
                handleFourPhotoQuery(this.getAttribute('data-four-photo') || this.getAttribute('href'));
            });
        });
    }

    function bindStaticEvents() {
        var tabButtons = document.querySelectorAll('[data-tab-target]');
        tabButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                switchTab(this.getAttribute('data-tab-target'), this);
            });
        });

        var tipCloseButton = document.getElementById('tipCloseBtn');
        if (tipCloseButton) {
            tipCloseButton.addEventListener('click', closeTipPopup);
        }

        var orderDrawerOverlay = document.getElementById('orderDrawer');
        if (orderDrawerOverlay) {
            orderDrawerOverlay.addEventListener('click', function (event) {
                if (event.target === this) {
                    closeOrderDrawer();
                }
            });
        }

        var orderDrawerClose = document.getElementById('orderDrawerCloseBtn');
        if (orderDrawerClose) {
            orderDrawerClose.addEventListener('click', closeOrderDrawer);
        }

        var smartClearBtn = document.getElementById('smartClearBtn');
        if (smartClearBtn) {
            smartClearBtn.addEventListener('click', function () {
                win.clearSmartAddress();
            });
        }

        var smartParseBtn = document.getElementById('smartParseBtn');
        if (smartParseBtn) {
            smartParseBtn.addEventListener('click', function () {
                win.parseSmartAddress();
            });
        }

        var cityTrigger = document.getElementById('cityPickerTrigger');
        if (cityTrigger) {
            cityTrigger.addEventListener('click', function () {
                win.showCityPicker();
            });
        }

        var autoLocationBtn = document.getElementById('autoLocationBtn');
        if (autoLocationBtn) {
            autoLocationBtn.addEventListener('click', function (event) {
                event.stopPropagation();
                getLocationByIP();
            });
        }

        var numberTrigger = document.getElementById('numberPickerTrigger');
        if (numberTrigger) {
            numberTrigger.addEventListener('click', function () {
                win.showNumberPicker();
            });
        }

        var cityPickerOverlay = document.getElementById('cityPickerOverlay');
        if (cityPickerOverlay) {
            cityPickerOverlay.addEventListener('click', function (event) {
                if (event.target === this) {
                    win.closeCityPicker();
                }
            });
        }

        var cityPickerClose = document.getElementById('cityPickerCloseBtn');
        if (cityPickerClose) {
            cityPickerClose.addEventListener('click', function () {
                win.closeCityPicker();
            });
        }

        var citySearchInput = document.getElementById('citySearchInput');
        if (citySearchInput) {
            citySearchInput.addEventListener('input', function () {
                win.filterCityList();
            });
        }

        var cityBreadcrumbRoot = document.getElementById('cityBreadcrumbRoot');
        if (cityBreadcrumbRoot) {
            cityBreadcrumbRoot.addEventListener('click', function () {
                win.goToLevel(0);
            });
        }

        var cityLevelTabs = document.querySelectorAll('.city-picker-tab[data-level]');
        cityLevelTabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                win.handleTabClick.call(this);
            });
        });

        var numberPickerOverlay = document.getElementById('numberPickerOverlay');
        if (numberPickerOverlay) {
            numberPickerOverlay.addEventListener('click', function (event) {
                if (event.target === this) {
                    win.closeNumberPicker();
                }
            });
        }

        var numberPickerClose = document.getElementById('numberPickerCloseBtn');
        if (numberPickerClose) {
            numberPickerClose.addEventListener('click', function () {
                win.closeNumberPicker();
            });
        }

        var numberSearchInput = document.getElementById('numberSearchInput');
        if (numberSearchInput) {
            numberSearchInput.addEventListener('input', function () {
                win.filterNumbers();
            });
        }

        var quickSearchTags = document.querySelectorAll('[data-quick-search]');
        quickSearchTags.forEach(function (tag) {
            tag.addEventListener('click', function () {
                win.quickSearch(this.getAttribute('data-quick-search'));
            });
        });

        var refreshNumbersBtn = document.getElementById('refreshNumbersBtn');
        if (refreshNumbersBtn) {
            refreshNumbersBtn.addEventListener('click', function () {
                win.refreshNumbers();
            });
        }

        var uploadInputs = document.querySelectorAll('input[type="file"][data-upload-type]');
        uploadInputs.forEach(function (input) {
            input.addEventListener('change', function () {
                win.handleImageUpload(this, this.getAttribute('data-upload-type'));
            });
        });

        var submitOrderBtn = document.getElementById('submitOrderBtn');
        if (submitOrderBtn) {
            submitOrderBtn.addEventListener('click', function () {
                win.submitOrder();
            });
        }
    }

    function setupTipPopup() {
        var tipPopup = document.getElementById('tipPopup');
        if (!tipPopup) {
            return false;
        }

        tipPopup.addEventListener('click', function (e) {
            if (e.target === this) {
                closeTipPopup();
            }
        });

        var openTipPopup = function () {
            tipPopup.style.display = 'flex';
        };

        if (win.requestAnimationFrame) {
            win.requestAnimationFrame(openTipPopup);
        } else {
            setTimeout(openTipPopup, 0);
        }

        return true;
    }

    function closeTipPopup() {
        var tipPopup = document.getElementById('tipPopup');
        if (tipPopup) {
            tipPopup.style.display = 'none';
        }

        var processDrawer = document.getElementById('processDrawer');
        if (processDrawer && typeof win.showProcessDrawer === 'function') {
            setTimeout(function () {
                win.showProcessDrawer();
            }, 120);
        }
    }

    function setupInitialPopups() {
        var hasTipPopup = setupTipPopup();
        if (hasTipPopup) {
            return;
        }

        if (document.getElementById('processDrawer') && typeof win.showProcessDrawer === 'function') {
            setTimeout(function () {
                win.showProcessDrawer();
            }, 0);
        }
    }

    function switchTab(tabName, trigger) {
        var tabContents = document.querySelectorAll('.tab-content');
        tabContents.forEach(function (content) {
            content.classList.remove('active');
        });

        var tabItems = document.querySelectorAll('.tab-item');
        tabItems.forEach(function (item) {
            item.classList.remove('active');
        });

        var targetContent = document.getElementById(tabName);
        if (targetContent) {
            targetContent.classList.add('active');
        }

        var activeTrigger = trigger || (win.event && win.event.target ? win.event.target : null);
        if (activeTrigger) {
            activeTrigger.classList.add('active');
        }
    }

    function initializeProductPage(layer, $) {
        currentLayer = layer;
        currentJquery = $;
        renderCustomOrderFields();
        setupResubmit();
        syncOrderPhone();
        setupVerifyCode();
        bindOrderBarActions();
        setupInitialPopups();
        bindStaticEvents();
    }

    win.ShopProductPageService = {
        initialize: initializeProductPage,
        showOrderDrawer: showOrderDrawer,
        closeOrderDrawer: closeOrderDrawer,
        handleFourPhotoQuery: handleFourPhotoQuery,
        showPaidCardSuccess: showPaidCardSuccess,
        showFreeCardSuccess: showFreeCardSuccess,
        getLocationByIP: getLocationByIP,
        showServiceQrcode: showServiceQrcode,
        callPhone: callPhone,
        contactService: contactService,
        generateProductPoster: generateProductPoster,
        closeTipPopup: closeTipPopup,
        switchTab: switchTab
    };

    win.showOrderDrawer = showOrderDrawer;
    win.closeOrderDrawer = closeOrderDrawer;
    win.handleFourPhotoQuery = handleFourPhotoQuery;
    win.showPaidCardSuccess = showPaidCardSuccess;
    win.showFreeCardSuccess = showFreeCardSuccess;
    win.getLocationByIP = getLocationByIP;
    win.showServiceQrcode = showServiceQrcode;
    win.callPhone = callPhone;
    win.contactService = contactService;
    win.generateProductPoster = generateProductPoster;
    win.closeTipPopup = closeTipPopup;
    win.switchTab = switchTab;
})(window);
