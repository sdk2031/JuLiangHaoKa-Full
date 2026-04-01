(function (win) {
    var phoneRegex = /^1[3-9]\d{9}$/;
    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    var forbiddenWords = ['快递', '驿站', '公司', '学校', '小学', '中学', '大学', '酒店', '商业楼'];

    function msg(layer, text, options) {
        if (layer && typeof layer.msg === 'function') {
            layer.msg(text, options || {});
        }
    }

    function validateCommonFields(formData, layer) {
        var name = formData.get('customer_name');
        var phone = formData.get('customer_phone');
        var idcard = formData.get('customer_idcard');
        var orderPhone = formData.get('order_phone');
        var address = formData.get('customer_address');
        var city = formData.get('customer_city');
        var verifyTypeEl = document.querySelector('input[name="verify_type"]');
        var verifyType = verifyTypeEl ? verifyTypeEl.value : 'none';

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

        if (verifyType === 'sms' || verifyType === 'both') {
            if (!phone) {
                msg(layer, '请输入联系电话');
                return false;
            }
            if (!phoneRegex.test(phone)) {
                msg(layer, '请输入正确的联系电话格式');
                return false;
            }
        }

        if (verifyType === 'email' || verifyType === 'both') {
            var email = formData.get('customer_email');
            if (!email) {
                msg(layer, '请输入邮箱地址');
                return false;
            }
            if (!emailRegex.test(email)) {
                msg(layer, '请输入正确的邮箱格式');
                return false;
            }
        }

        if (!idcard) {
            msg(layer, '请输入身份证号');
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

        return true;
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
        var verifyTypeEl = document.querySelector('input[name="verify_type"]');
        var verifyType = verifyTypeEl ? verifyTypeEl.value : 'none';

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
