(function (win) {
    function normalizeProvinceName(name) {
        return String(name || '').replace(/省|市|自治区|特别行政区/g, '').trim();
    }

    function normalizeCityName(name) {
        return String(name || '').replace(/市|地区|州|盟/g, '').trim();
    }

    function normalizeDistrictName(name) {
        return String(name || '').replace(/区|县|旗|市/g, '').trim();
    }

    function sameAreaName(left, right, normalizer) {
        var a = normalizer(left);
        var b = normalizer(right);
        if (!a || !b) {
            return false;
        }
        return a === b || a.indexOf(b) !== -1 || b.indexOf(a) !== -1;
    }

    function isMunicipalityName(name) {
        var n = normalizeProvinceName(name);
        return n === '北京' || n === '上海' || n === '天津' || n === '重庆';
    }

    function ensureCityHintNode() {
        var formGroup = document.getElementById('cityPickerTrigger');
        if (!formGroup) {
            return null;
        }
        var container = formGroup.closest('.form-group');
        if (!container) {
            return null;
        }
        var hint = container.querySelector('.city-inline-hint');
        if (!hint) {
            hint = document.createElement('div');
            hint.className = 'city-inline-hint';
            hint.style.cssText = 'margin-left:78px;margin-top:6px;font-size:12px;color:#ff4d4f;line-height:1.4;';
            container.appendChild(hint);
        }
        return hint;
    }

    function showCityPickerHint(message) {
        var trigger = document.getElementById('cityPickerTrigger');
        if (trigger) {
            var wrapper = trigger.querySelector('.form-input-wrapper') || trigger;
            wrapper.style.borderColor = '#ff4d4f';
            wrapper.style.boxShadow = '0 0 0 2px rgba(255,77,79,0.12)';
            wrapper.style.background = '#fff';
        }

        var hint = ensureCityHintNode();
        if (hint) {
            hint.textContent = message || '请手动选择收货城市';
            hint.style.display = 'block';
        }
    }

    function clearCityPickerHint() {
        var trigger = document.getElementById('cityPickerTrigger');
        if (trigger) {
            var wrapper = trigger.querySelector('.form-input-wrapper') || trigger;
            wrapper.style.borderColor = '';
            wrapper.style.boxShadow = '';
            wrapper.style.background = '';
        }

        var hint = ensureCityHintNode();
        if (hint) {
            hint.textContent = '';
            hint.style.display = 'none';
        }
    }

    function openCityPickerForManualSelect() {
        if (typeof win.showCityPicker === 'function') {
            setTimeout(function () {
                win.showCityPicker();
            }, 150);
        }
    }

    function escapeHtml(text) {
        return String(text || '').replace(/[&<>"']/g, function (char) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            }[char];
        });
    }

    function showAreaMismatchPopup(areaText) {
        layui.layer.msg(
            '收货地址<br><span style="color:#ff4d4f;">' + escapeHtml(areaText || '') + '</span><br>地区不支持，请手动选择',
            {
                icon: 0,
                time: 2200
            }
        );
    }

    function showFriendlyAreaMismatchMessage(data) {
        var areaText = [data.province, data.city, data.county || ''].filter(Boolean).join(' ');
        showCityPickerHint('请手动选择收货城市');
        showAreaMismatchPopup(areaText);
        setTimeout(function () {
            openCityPickerForManualSelect();
        }, 350);
    }

    function normalizeRegionKeyword(name) {
        return String(name || '')
            .replace(/壮族自治区|回族自治区|维吾尔自治区|自治区|特别行政区|省|市|地区|自治州|州|盟|区|县|旗/g, '')
            .replace(/\s+/g, '')
            .trim();
    }

    function cleanAreaToken(token) {
        return String(token || '')
            .replace(/^[\s,，；;、|/]+|[\s,，；;、|/]+$/g, '')
            .replace(/^(可发(地区|区域|范围)?|禁发(地区|区域|范围)?|发货(地区|区域|范围)?|配送(地区|区域|范围)?|地区|区域|省份)\s*[:：]?\s*/g, '')
            .replace(/^(只发|仅发|仅限|只限|只能发|仅能发|限发)\s*/g, '')
            .replace(/[（(]/g, '')
            .replace(/[）)]/g, '')
            .replace(/等$/g, '')
            .replace(/\s+/g, '')
            .trim();
    }

    function splitAreaText(text) {
        var raw = String(text || '').trim();
        if (!raw || raw === '待更新') {
            return [];
        }
        raw = raw.replace(/[\r\n]+/g, ',').replace(/[\s,，；;、|/]+/g, ',');
        return raw.split(',').map(cleanAreaToken).filter(function (item) {
            return !!item && item !== '全国' && item !== '全国发货' && item !== '全国可发';
        });
    }

    function extractOnlyAllowText(text) {
        var raw = String(text || '').trim();
        var match = raw.match(/^(只发|仅发|仅限|只限|只能发|仅能发|限发)\s*(.+)$/);
        return match ? match[2] : '';
    }

    function isNationwideText(text) {
        var cleaned = cleanAreaToken(text);
        return !cleaned || cleaned === '待更新' || cleaned === '全国' || cleaned === '全国发货' || cleaned === '全国可发';
    }

    function buildAreaRules() {
        var needFilter = win.API_TYPE !== 1004 && win.API_TYPE !== 1006;
        if (!needFilter) {
            return { allowList: [], banList: [], hasFilter: false };
        }

        var kefaText = String(win.KEFA || '').trim();
        var jinfaText = String(win.JINFA || '').trim();
        var allowText = '';
        var banText = '';
        var onlyAllowText = extractOnlyAllowText(jinfaText) || extractOnlyAllowText(kefaText);

        if (onlyAllowText) {
            allowText = onlyAllowText;
        } else if (!isNationwideText(kefaText)) {
            allowText = kefaText;
        }

        if (!extractOnlyAllowText(jinfaText) && jinfaText && jinfaText !== '待更新') {
            banText = jinfaText;
        }

        var allowList = splitAreaText(allowText);
        var banList = splitAreaText(banText);

        return {
            allowList: allowList,
            banList: banList,
            hasFilter: allowList.length > 0 || banList.length > 0
        };
    }

    function matchesRegionKeyword(name, keyword) {
        var left = normalizeRegionKeyword(name);
        var right = normalizeRegionKeyword(keyword);
        if (!left || !right) {
            return false;
        }
        return left === right || left.indexOf(right) !== -1 || right.indexOf(left) !== -1;
    }

    function isAllowedByRules(name, rules) {
        if (rules.allowList.length > 0) {
            return rules.allowList.some(function (item) {
                return matchesRegionKeyword(name, item);
            });
        }
        return true;
    }

    function isBannedByRules(name, rules) {
        if (rules.banList.length > 0) {
            return rules.banList.some(function (item) {
                return matchesRegionKeyword(name, item);
            });
        }
        return false;
    }

    function parseSmartAddress() {
        var smartAddressInput = document.querySelector('textarea[name="smart_address"]');
        var addressText = smartAddressInput.value.trim();

        if (!addressText) {
            layui.layer.msg('请输入地址信息', { icon: 2 });
            return;
        }

        var loadingIndex = layui.layer.load(2, { shade: [0.3, '#000'] });

        fetch('/index/shop/parseAddress', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'address=' + encodeURIComponent(addressText)
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (result) {
                layui.layer.close(loadingIndex);

                if (result.code !== 1) {
                    layui.layer.msg(result.msg || '地址识别失败，请检查格式', { icon: 2 });
                    return;
                }

                var data = result.data;
                var filledFields = [];

                if (data.name) {
                    document.querySelector('input[name="customer_name"]').value = data.name;
                    filledFields.push('姓名');
                }
                if (data.phone) {
                    var orderPhoneInput = document.querySelector('input[name="order_phone"]');
                    var customerPhoneInput = document.querySelector('input[name="customer_phone"]');
                    if (orderPhoneInput) {
                        orderPhoneInput.value = data.phone;
                        orderPhoneInput.dispatchEvent(new Event('input', { bubbles: true }));
                        orderPhoneInput.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    if (customerPhoneInput) {
                        customerPhoneInput.value = data.phone;
                    }
                    filledFields.push('电话');
                }
                if (data.idCard) {
                    document.querySelector('input[name="customer_idcard"]').value = data.idCard;
                    filledFields.push('身份证');
                }
                if (data.address) {
                    document.querySelector('input[name="customer_address"]').value = data.address;
                    filledFields.push('详细地址');
                }

                var district = data.county || '';
                var hasAreaInfo = data.province || data.city || district;

                if (!hasAreaInfo) {
                    if (filledFields.length > 0) {
                        showCityPickerHint('未识别地区，请手动选择');
                        layui.layer.msg('未识别地区，请手动选择', { icon: 0, time: 1800 });
                        openCityPickerForManualSelect();
                    } else {
                        layui.layer.msg('地址识别失败，请检查格式或手动填写', { icon: 2 });
                    }
                    return;
                }

                checkAreaSupport(data.province, data.city, district, data.provinceCode, data.cityCode, data.countyCode)
                    .then(function (areaMatch) {
                        if (!areaMatch) {
                            showFriendlyAreaMismatchMessage(data);
                            return;
                        }

                        var displayText = [areaMatch.province, areaMatch.city, areaMatch.district].filter(Boolean).join(' ');
                        document.querySelector('input[name="customer_city"]').value = displayText;
                        document.querySelector('input[name="province"]').value = areaMatch.province || '';
                        document.querySelector('input[name="city"]').value = areaMatch.city || '';
                        document.querySelector('input[name="district"]').value = areaMatch.district || '';
                        document.querySelector('input[name="province_code"]').value = areaMatch.province_code || '';
                        document.querySelector('input[name="city_code"]').value = areaMatch.city_code || '';
                        document.querySelector('input[name="district_code"]').value = areaMatch.district_code || '';
                        clearCityPickerHint();

                        layui.layer.msg('地址识别成功', { icon: 1, time: 1500 });
                    });
            })
            .catch(function (error) {
                layui.layer.close(loadingIndex);
                console.error('地址解析API调用失败:', error);
                layui.layer.msg('网络错误，请重试', { icon: 2 });
            });
    }

    function checkAreaSupport(province, city, district, provinceCode, cityCode, districtCode) {
        return new Promise(function (resolve) {
            province = (province || '').trim();
            city = (city || '').trim();
            district = (district || '').trim();
            var debugInfo = {
                province: province,
                city: city,
                district: district,
                provinceCode: provinceCode || '',
                cityCode: cityCode || '',
                districtCode: districtCode || '',
                stage: 'init'
            };
            win.__smartAreaDebug = debugInfo;
            var areaRules = buildAreaRules();
            debugInfo.allowList = areaRules.allowList;
            debugInfo.banList = areaRules.banList;

            win.ShopProductRuntime.fetchAreaData({
                product_id: win.PRODUCT_ID,
                type: 'provinces'
            })
                .then(function (result) {
                    debugInfo.stage = 'provinces_loaded';
                    debugInfo.provinceCount = result && result.data ? result.data.length : 0;
                    if (result.code !== 0 || !result.data || result.data.length === 0) {
                        debugInfo.stage = 'provinces_empty';
                        resolve(false);
                        return;
                    }

                    var provinceList = result.data.filter(function (item) {
                        if (!areaRules.hasFilter) {
                            return true;
                        }
                        return isAllowedByRules(item.name, areaRules) && !isBannedByRules(item.name, areaRules);
                    });

                    if (!provinceList.length && !areaRules.hasFilter) {
                        provinceList = result.data;
                    }

                    var matchedProvince = provinceList.find(function (item) {
                        return provinceCode && item.code && String(item.code) === String(provinceCode);
                    }) || provinceList.find(function (item) {
                        return sameAreaName(item.name, province, normalizeProvinceName);
                    });

                    if (!matchedProvince) {
                        debugInfo.stage = 'province_not_matched';
                        resolve(false);
                        return;
                    }
                    debugInfo.matchedProvince = matchedProvince.name || '';
                    debugInfo.matchedProvinceCode = matchedProvince.code || '';

                    win.ShopProductRuntime.fetchAreaData({
                        product_id: win.PRODUCT_ID,
                        type: 'cities',
                        province_code: matchedProvince.code,
                        province_name: matchedProvince.name || province
                    })
                        .then(function (cityResult) {
                            debugInfo.stage = 'cities_loaded';
                            debugInfo.cityCount = cityResult && cityResult.data ? cityResult.data.length : 0;
                            if (cityResult.code !== 0 || !cityResult.data || cityResult.data.length === 0) {
                                debugInfo.stage = 'cities_empty';
                                resolve(false);
                                return;
                            }

                            var cityList = cityResult.data.filter(function (item) {
                                if (!areaRules.hasFilter) {
                                    return true;
                                }
                                return !isBannedByRules(item.name, areaRules);
                            });

                            if (!cityList.length && !areaRules.hasFilter) {
                                cityList = cityResult.data;
                            }

                            var matchedCity = cityList.find(function (item) {
                                return cityCode && item.code && String(item.code) === String(cityCode);
                            }) || cityList.find(function (item) {
                                return sameAreaName(item.name, city, normalizeCityName);
                            });

                            if (!matchedCity) {
                                var municipality = isMunicipalityName(matchedProvince.name || province);
                                if (municipality) {
                                    matchedCity = cityList.find(function (item) {
                                        var n = String(item.name || '');
                                        return n.indexOf('市辖区') !== -1 || n.indexOf('城区') !== -1;
                                    }) || (cityList.length === 1 ? cityList[0] : null);
                                }
                            }

                            if (!matchedCity) {
                                debugInfo.stage = 'city_not_matched';
                                resolve(false);
                                return;
                            }
                            debugInfo.matchedCity = matchedCity.name || '';
                            debugInfo.matchedCityCode = matchedCity.code || '';

                            win.ShopProductRuntime.fetchAreaData({
                                product_id: win.PRODUCT_ID,
                                type: 'districts',
                                province_code: matchedProvince.code,
                                city_code: matchedCity.code,
                                province_name: matchedProvince.name || province,
                                city_name: matchedCity.name || city
                            })
                                .then(function (districtResult) {
                                    debugInfo.stage = 'districts_loaded';
                                    debugInfo.districtCount = districtResult && districtResult.data ? districtResult.data.length : 0;
                                    if (districtResult.code !== 0 || !districtResult.data || districtResult.data.length === 0) {
                                        debugInfo.stage = 'districts_empty_fallback';
                                        resolve({
                                            province: matchedProvince.name || province,
                                            city: matchedCity.name || city,
                                            district: district,
                                            province_code: matchedProvince.code || '',
                                            city_code: matchedCity.code || '',
                                            district_code: ''
                                        });
                                        return;
                                    }

                                    var filteredDistricts = districtResult.data.filter(function (item) {
                                        if (!areaRules.hasFilter) {
                                            return true;
                                        }
                                        return !isBannedByRules(item.name, areaRules);
                                    });

                                    var matchedDistrict = filteredDistricts.find(function (item) {
                                        return districtCode && item.code && String(item.code) === String(districtCode);
                                    }) || filteredDistricts.find(function (item) {
                                        return sameAreaName(item.name, district, normalizeDistrictName);
                                    });
                                    debugInfo.matchedDistrict = matchedDistrict ? (matchedDistrict.name || '') : '';
                                    debugInfo.matchedDistrictCode = matchedDistrict ? (matchedDistrict.code || '') : '';
                                    debugInfo.stage = matchedDistrict ? 'district_matched' : 'district_not_matched_fallback';

                                    resolve({
                                        province: matchedProvince.name || province,
                                        city: matchedCity.name || city,
                                        district: matchedDistrict ? (matchedDistrict.name || district) : district,
                                        province_code: matchedProvince.code || '',
                                        city_code: matchedCity.code || '',
                                        district_code: matchedDistrict ? (matchedDistrict.code || '') : ''
                                    });
                                })
                                .catch(function () {
                                    debugInfo.stage = 'district_request_failed_fallback';
                                    resolve({
                                        province: matchedProvince.name || province,
                                        city: matchedCity.name || city,
                                        district: district,
                                        province_code: matchedProvince.code || '',
                                        city_code: matchedCity.code || '',
                                        district_code: ''
                                    });
                                });
                        })
                        .catch(function () {
                            debugInfo.stage = 'city_request_failed';
                            resolve(false);
                        });
                })
                .catch(function () {
                    debugInfo.stage = 'province_request_failed';
                    resolve(false);
                });
        });
    }

    function clearSmartAddress() {
        var smartAddressInput = document.querySelector('textarea[name="smart_address"]');
        if (smartAddressInput.value.trim()) {
            smartAddressInput.value = '';
            layui.layer.msg('已清空', { icon: 1 });
        }
    }

    win.ShopProductAddressService = {
        parseSmartAddress: parseSmartAddress,
        checkAreaSupport: checkAreaSupport,
        clearSmartAddress: clearSmartAddress,
        clearCityPickerHint: clearCityPickerHint
    };

    win.parseSmartAddress = parseSmartAddress;
    win.checkAreaSupport = checkAreaSupport;
    win.clearSmartAddress = clearSmartAddress;
    win.clearAddressAreaHint = clearCityPickerHint;
})(window);
