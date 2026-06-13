(function (win) {
    var phoneRegex = /^1[3-9]\d{9}$/;
    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    var forbiddenWords = ['快递', '驿站', '公司', '学校', '小学', '中学', '大学', '酒店', '商业楼'];

    function formatMessageHtml(text) {
        var content = String(text == null ? '' : text);
        return content
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
            .replace(/\n/g, '<br>');
    }

    function msg(layer, text, options) {
        if (!layer) {
            return;
        }
        var rawText = String(text == null ? '' : text);
        if (rawText.indexOf('\n') !== -1 && typeof layer.msg === 'function') {
            layer.msg('<div style="white-space:normal;line-height:1.8;">' + formatMessageHtml(rawText) + '</div>', options || {});
            return;
        }

        if (typeof layer.msg === 'function') {
            layer.msg(rawText, options || {});
        }
    }

    function getRequiredPhotoFields() {
        var fields = [];
        if (win.NEED_ID_PHOTO) {
            fields.push(
                { key: 'id_card_front', label: '身份证正面' },
                { key: 'id_card_back', label: '身份证背面' },
                { key: 'id_card_face', label: '人像照片' }
            );
        }
        if (win.NEED_FOUR_PHOTO) {
            fields.push({
                key: 'id_card_four',
                label: win.FOUR_PHOTO_TITLE || '第四证照片'
            });
        }
        return fields;
    }

    function validateRequiredPhotos(layer) {
        var requiredFields = getRequiredPhotoFields();
        if (requiredFields.length === 0) {
            return true;
        }

        var uploadedImages = win.uploadedImages || {};
        for (var i = 0; i < requiredFields.length; i += 1) {
            var item = requiredFields[i];
            if (!uploadedImages[item.key]) {
                msg(layer, '请上传' + item.label);
                return false;
            }
        }

        return true;
    }

    function normalizeIdCard(idcard) {
        return String(idcard || '').trim().toUpperCase();
    }

    function getBirthDateFromIdCard(idcard) {
        var normalized = normalizeIdCard(idcard);
        if (!/^\d{17}[\dX]$/.test(normalized)) {
            return null;
        }

        var birthRaw = normalized.substr(6, 8);
        var year = parseInt(birthRaw.substr(0, 4), 10);
        var month = parseInt(birthRaw.substr(4, 2), 10);
        var day = parseInt(birthRaw.substr(6, 2), 10);
        var birthDate = new Date(year, month - 1, day);

        if (
            birthDate.getFullYear() !== year ||
            birthDate.getMonth() !== month - 1 ||
            birthDate.getDate() !== day
        ) {
            return null;
        }

        return birthDate;
    }

    function getAgeFromIdCard(idcard) {
        var birthDate = getBirthDateFromIdCard(idcard);
        if (!birthDate) {
            return 0;
        }

        var today = new Date();
        var age = today.getFullYear() - birthDate.getFullYear();
        var monthDiff = today.getMonth() - birthDate.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }

        return age > 0 ? age : 0;
    }

    function parseProductAgeRule(ruleText) {
        var rule = String(ruleText || '').trim();
        if (!rule) {
            return null;
        }

        var normalized = rule
            .replace(/\s+/g, '')
            .replace(/[—–~～]/g, '-')
            .replace(/周岁/g, '岁');

        var rangeMatch = normalized.match(/(\d{1,2})\s*(?:岁)?-(\d{1,2})\s*(?:岁)?/);
        if (!rangeMatch) {
            rangeMatch = normalized.match(/(\d{1,2})\s*(?:岁)?(?:至|到)(\d{1,2})\s*(?:岁)?/);
        }
        if (rangeMatch) {
            return {
                min: parseInt(rangeMatch[1], 10),
                max: parseInt(rangeMatch[2], 10)
            };
        }

        var minMatch = normalized.match(/(\d{1,2})\s*(?:岁)?(?:以上|及以上|起|起步|或以上)/);
        if (minMatch) {
            return {
                min: parseInt(minMatch[1], 10),
                max: null
            };
        }

        var maxMatch = normalized.match(/(\d{1,2})\s*(?:岁)?(?:以下|及以下|以内|内|封顶|不超过)/);
        if (maxMatch) {
            return {
                min: null,
                max: parseInt(maxMatch[1], 10)
            };
        }

        return null;
    }

    function validateProductAge(idcard, layer) {
        var ageRuleText = String(win.PRODUCT_AGE_RULE || '').trim();
        if (!ageRuleText) {
            return true;
        }

        var ageRule = parseProductAgeRule(ageRuleText);
        if (!ageRule) {
            return true;
        }

        var actualAge = getAgeFromIdCard(idcard);
        if (actualAge <= 0) {
            msg(layer, '身份证年龄识别失败，请检查身份证号是否正确');
            return false;
        }

        if (ageRule.min !== null && actualAge < ageRule.min) {
            msg(layer, '提交失败：年龄不符合\n产品要求下单年龄' + ageRuleText);
            return false;
        }

        if (ageRule.max !== null && actualAge > ageRule.max) {
            msg(layer, '提交失败：年龄不符合\n产品要求下单年龄' + ageRuleText);
            return false;
        }

        return true;
    }

    function getEffectiveVerifyType() {
        var verifyTypeEl = document.querySelector('input[name="verify_type"]');
        var verifyCodeEl = document.querySelector('input[name="verify_code"]');
        var verifyType = verifyTypeEl ? String(verifyTypeEl.value || '').toLowerCase() : 'none';

        if (!verifyCodeEl) {
            if (verifyTypeEl && verifyType !== 'none') {
                verifyTypeEl.value = 'none';
            }
            return 'none';
        }

        return verifyType === 'sms' || verifyType === 'image' ? verifyType : 'none';
    }

    function validateCommonFields(formData, layer) {
        var name = formData.get('customer_name');
        var phone = formData.get('customer_phone');
        var idcard = formData.get('customer_idcard');
        var orderPhone = formData.get('order_phone');
        var address = formData.get('customer_address');
        var city = formData.get('customer_city');
        var verifyType = getEffectiveVerifyType();

        if (!name) {
            msg(layer, '请输入姓名');
            return false;
        }

        if (!orderPhone) {
            msg(layer, '请输入下单手机号');
            return false;
        }

        if (!phoneRegex.test(orderPhone)) {
            msg(layer, '请输入正确的手机号码格式');
            return false;
        }

        if (verifyType === 'sms') {
            var contactPhone = phone || orderPhone;

            if (!contactPhone) {
                msg(layer, '请输入联系电话');
                return false;
            }
            if (!phoneRegex.test(contactPhone)) {
                msg(layer, '请输入正确的联系电话格式');
                return false;
            }

            var customerPhoneInput = document.querySelector('input[name="customer_phone"]');
            if (customerPhoneInput && customerPhoneInput.value !== contactPhone) {
                customerPhoneInput.value = contactPhone;
            }
        }

        if (!idcard) {
            msg(layer, '请输入身份证号');
            return false;
        }

        if (!/^\d{17}[\dXx]$/.test(idcard)) {
            msg(layer, '身份证号格式不正确');
            return false;
        }

        if (!validateProductAge(idcard, layer)) {
            return false;
        }

        if (!city) {
            msg(layer, '请选择收货城市');
            return false;
        }

        if (!address) {
            msg(layer, '请输入详细收货地址');
            return false;
        }

        for (var i = 0; i < forbiddenWords.length; i += 1) {
            if (address.indexOf(forbiddenWords[i]) !== -1) {
                msg(layer, '详细地址不能包含"' + forbiddenWords[i] + '"等非居住地址，请填写本人可收货的详细地址', {
                    icon: 2,
                    time: 3000
                });
                return false;
            }
        }

        try {
            var customConfig = JSON.parse(String(win.PRODUCT_CUSTOM_FIELDS || '').trim() || '[]');
            if (Array.isArray(customConfig)) {
                for (var j = 0; j < customConfig.length; j += 1) {
                    var item = customConfig[j] || {};
                    var fieldName = String(item.name || '').trim();
                    var fieldLabel = String(item.label || fieldName || '').trim();
                    var required = String(item.required || item.required === 0 ? item.required : '0') === '1';
                    if (!fieldName || !required) {
                        continue;
                    }
                    var fieldValue = formData.get('custom_order_fields');
                    var fieldMap = {};
                    try {
                        fieldMap = JSON.parse(String(fieldValue || '{}'));
                    } catch (e) {}
                    if (!String((fieldMap[fieldName] || '')).trim()) {
                        msg(layer, '请输入' + fieldLabel);
                        return false;
                    }
                }
            }
        } catch (e) {}

        return validateRequiredPhotos(layer);
    }

    function validateTianchengFields(layer) {
        var provinceCode = document.querySelector('input[name="province_code"]').value;
        var cityCode = document.querySelector('input[name="city_code"]').value;
        var districtCode = document.querySelector('input[name="district_code"]').value;

        if (!provinceCode || !cityCode || !districtCode) {
            msg(layer, '地区信息不完整，请手动选择省市区或重新进行智能识别', { icon: 0, time: 3000 });
            return false;
        }

        return true;
    }

    function validateSelfOperatedFields(formData, layer) {
        var verifyType = getEffectiveVerifyType();

        if (verifyType !== 'none') {
            var verifyCode = formData.get('verify_code');
            if (!verifyCode) {
                msg(layer, '请输入验证码');
                return false;
            }
        }

        return true;
    }

    function validateApiSpecificFields(formData, apiType, layer) {
        switch (apiType) {
            case 1001:
                return validateTianchengFields(layer);
            case 1005:
            case 1006:
            case 1008:
                return true;
            default:
                return validateSelfOperatedFields(formData, layer);
        }
    }

    win.ShopProductValidators = {
        validateCommonFields: validateCommonFields,
        validateApiSpecificFields: validateApiSpecificFields
    };
})(window);
