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
                    document.querySelector('input[name="order_phone"]').value = data.phone;
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
                        layui.layer.msg('已识别：' + filledFields.join('、') + '。省市区未识别，请手动选择', { icon: 0, time: 3000 });
                    } else {
                        layui.layer.msg('地址识别失败，请检查格式或手动填写', { icon: 2 });
                    }
                    return;
                }

                checkAreaSupport(data.province, data.city, district, data.provinceCode, data.cityCode, data.countyCode)
                    .then(function (areaMatch) {
                        if (!areaMatch) {
                            var debugStage = win.__smartAreaDebug && win.__smartAreaDebug.stage ? ('（' + win.__smartAreaDebug.stage + '）') : '';
                            layui.layer.msg('该商品暂不支持发货到 ' + [data.province, data.city, district].filter(Boolean).join('') + debugStage + '，请手动选择其他收货城市或联系客服', { icon: 0, time: 5000 });
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

            var applyLocalJinfaFilter = false;
            var jinfaList = [];

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

                    var matchedProvince = result.data.find(function (item) {
                        return provinceCode && item.code && String(item.code) === String(provinceCode);
                    }) || result.data.find(function (item) {
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

                            var matchedCity = cityResult.data.find(function (item) {
                                return cityCode && item.code && String(item.code) === String(cityCode);
                            }) || cityResult.data.find(function (item) {
                                return sameAreaName(item.name, city, normalizeCityName);
                            });

                            if (!matchedCity) {
                                var municipality = isMunicipalityName(matchedProvince.name || province);
                                if (municipality) {
                                    matchedCity = cityResult.data.find(function (item) {
                                        var n = String(item.name || '');
                                        return n.indexOf('市辖区') !== -1 || n.indexOf('城区') !== -1;
                                    }) || (cityResult.data.length === 1 ? cityResult.data[0] : null);
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
                                        if (!applyLocalJinfaFilter || jinfaList.length === 0) {
                                            return true;
                                        }
                                        return !jinfaList.some(function (word) {
                                            return String(item.name || '').indexOf(word) !== -1;
                                        });
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
        clearSmartAddress: clearSmartAddress
    };

    win.parseSmartAddress = parseSmartAddress;
    win.checkAreaSupport = checkAreaSupport;
    win.clearSmartAddress = clearSmartAddress;
})(window);
