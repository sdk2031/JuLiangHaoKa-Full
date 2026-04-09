(function (win) {
    var cityData = {};
    var areaRules = { allowList: [], banList: [], hasFilter: false };
    var selectedProvince = '';
    var selectedCity = '';
    var selectedDistrict = '';
    var selectedProvinceCode = '';
    var selectedCityCode = '';
    var selectedDistrictCode = '';
    var currentLevel = 0;

    function normalizeRegionName(name) {
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

    function matchAreaKeyword(name, keyword) {
        var left = normalizeRegionName(name);
        var right = normalizeRegionName(keyword);
        if (!left || !right) {
            return false;
        }
        return left === right || left.indexOf(right) !== -1 || right.indexOf(left) !== -1;
    }

    function isAllowedByRules(name) {
        if (areaRules.allowList.length > 0) {
            return areaRules.allowList.some(function (item) {
                return matchAreaKeyword(name, item);
            });
        }
        return true;
    }

    function isBannedByRules(name) {
        if (areaRules.banList.length > 0) {
            return areaRules.banList.some(function (item) {
                return matchAreaKeyword(name, item);
            });
        }
        return false;
    }

    function updateBreadcrumb() {
        var breadcrumb = document.getElementById('cityBreadcrumb');
        var html = '';

        if (currentLevel === 0) {
            html = '<span onclick="goToLevel(0)">请选择省份</span>';
        } else if (currentLevel === 1) {
            html = '<span onclick="goToLevel(0)">' + selectedProvince + '</span><span class="separator">></span><span onclick="goToLevel(1)">请选择城市</span>';
        } else if (currentLevel === 2) {
            html = '<span onclick="goToLevel(0)">' + selectedProvince + '</span><span class="separator">></span><span onclick="goToLevel(1)">' + selectedCity + '</span><span class="separator">></span><span onclick="goToLevel(2)">请选择区县</span>';
        }

        breadcrumb.innerHTML = html;
    }

    function updateTabs() {
        var tabs = document.querySelectorAll('.city-picker-tab');
        tabs.forEach(function (tab, index) {
            if (index === currentLevel) {
                tab.classList.add('active');
            } else {
                tab.classList.remove('active');
            }
        });
    }

    function displayCurrentLevelData() {
        if (currentLevel === 0) {
            showProvinceList();
        } else if (currentLevel === 1) {
            showCityList(selectedProvinceCode);
        } else if (currentLevel === 2) {
            showDistrictList(selectedCityCode);
        }
    }

    function selectSearchResult(item) {
        if (item.type === 'province') {
            selectProvince(item.code, item.name);
        } else if (item.type === 'city') {
            selectedProvince = cityData['86'][item.provinceCode];
            selectedProvinceCode = item.provinceCode;
            selectCity(item.code, item.name);
        } else if (item.type === 'district') {
            selectedProvince = cityData['86'][item.provinceCode];
            selectedProvinceCode = item.provinceCode;
            selectedCity = cityData[item.provinceCode][item.cityCode];
            selectedCityCode = item.cityCode;
            selectDistrict(item.code, item.name);
        }
    }

    function loadCityData() {
        win.ShopProductRuntime.fetchAreaData({
            product_id: win.PRODUCT_ID,
            type: 'provinces'
        })
            .then(function (result) {
                if (result.code !== 0 || !result.data) {
                    throw new Error(result.msg || '获取省份数据失败');
                }

                cityData = { '86': {} };
                areaRules = buildAreaRules();

                result.data.forEach(function (province) {
                    var provinceName = province.name;
                    if (areaRules.hasFilter) {
                        if (!isAllowedByRules(provinceName) || isBannedByRules(provinceName)) {
                            return;
                        }
                    }

                    cityData['86'][province.code] = province.name;

                    if (province.ess_code) {
                        if (!win.guangmengyunEssCodes) {
                            win.guangmengyunEssCodes = {};
                        }
                        win.guangmengyunEssCodes[province.code] = province.ess_code;
                    }
                });

                if (!areaRules.hasFilter && Object.keys(cityData['86']).length === 0 && result.data.length > 0) {
                    result.data.forEach(function (province) {
                        cityData['86'][province.code] = province.name;
                    });
                }

                displayCurrentLevelData();
            })
            .catch(function () {
                layer.msg('加载省份数据失败', { icon: 2 });
            });
    }

    function showProvinceList() {
        currentLevel = 0;
        updateTabs();

        var list = document.getElementById('cityPickerList');
        list.innerHTML = '';

        var provinces = cityData['86'];
        if (!provinces || Object.keys(provinces).length === 0) {
            list.innerHTML = '<li class="city-picker-item">暂无可发省份</li>';
            return;
        }
        for (var code in provinces) {
            var li = document.createElement('li');
            li.className = 'city-picker-item';
            li.textContent = provinces[code];
            li.setAttribute('data-code', code);
            li.onclick = function () {
                selectProvince(this.getAttribute('data-code'), this.textContent);
            };
            list.appendChild(li);
        }
    }

    function selectProvince(code, name) {
        selectedProvince = name;
        selectedProvinceCode = code;
        selectedCity = '';
        selectedCityCode = '';
        selectedDistrict = '';
        selectedDistrictCode = '';
        currentLevel = 1;
        updateBreadcrumb();
        updateTabs();
        showCityList(code);
    }

    function showCityList(provinceCode) {
        currentLevel = 1;
        updateTabs();

        var list = document.getElementById('cityPickerList');
        list.innerHTML = '<li class="city-picker-item">加载中...</li>';

        win.ShopProductRuntime.fetchAreaData({
            product_id: win.PRODUCT_ID,
            type: 'cities',
            province_code: provinceCode,
            province_name: selectedProvince
        })
            .then(function (result) {
                list.innerHTML = '';
                if (result.code !== 0 || !result.data) {
                    list.innerHTML = '<li class="city-picker-item">获取城市失败: ' + (result.msg || '未知错误') + '</li>';
                    return;
                }

                var hasValidCity = false;
                result.data.forEach(function (city) {
                    if (areaRules.hasFilter && isBannedByRules(city.name)) {
                        return;
                    }

                    hasValidCity = true;
                    var li = document.createElement('li');
                    li.className = 'city-picker-item';
                    li.textContent = city.name;
                    li.setAttribute('data-code', city.code);
                    li.onclick = function () {
                        selectCity(this.getAttribute('data-code'), this.textContent);
                    };
                    list.appendChild(li);

                    if (city.ess_code) {
                        if (!win.guangmengyunEssCodes) {
                            win.guangmengyunEssCodes = {};
                        }
                        win.guangmengyunEssCodes[city.code] = city.ess_code;
                    }
                });

                if (!hasValidCity) {
                    list.innerHTML = '<li class="city-picker-item">该省份暂无可发城市</li>';
                }
            })
            .catch(function () {
                list.innerHTML = '<li class="city-picker-item">获取城市失败</li>';
            });
    }

    function selectCity(code, name) {
        selectedCity = name;
        selectedCityCode = code;
        selectedDistrict = '';
        selectedDistrictCode = '';
        currentLevel = 2;
        updateBreadcrumb();
        updateTabs();
        showDistrictList(code);
    }

    function showDistrictList(cityCode) {
        currentLevel = 2;
        updateTabs();

        var list = document.getElementById('cityPickerList');
        list.innerHTML = '<li class="city-picker-item">加载中...</li>';

        win.ShopProductRuntime.fetchAreaData({
            product_id: win.PRODUCT_ID,
            type: 'districts',
            province_code: selectedProvinceCode,
            city_code: cityCode,
            province_name: selectedProvince,
            city_name: selectedCity
        })
            .then(function (result) {
                list.innerHTML = '';
                if (result.code !== 0 || !result.data) {
                    list.innerHTML = '<li class="city-picker-item">获取区县失败: ' + (result.msg || '未知错误') + '</li>';
                    return;
                }

                var filteredDistricts = [];
                result.data.forEach(function (district) {
                    if (areaRules.hasFilter && isBannedByRules(district.name)) {
                        return;
                    }
                    filteredDistricts.push(district);
                });

                if (filteredDistricts.length === 0) {
                    list.innerHTML = '<li class="city-picker-item" onclick="completeSelection()">该城市无区县数据，点击确认选择</li>';
                    return;
                }

                filteredDistricts.forEach(function (district) {
                    var li = document.createElement('li');
                    li.className = 'city-picker-item';
                    li.textContent = district.name;
                    li.setAttribute('data-code', district.code);
                    li.onclick = function () {
                        selectDistrict(this.getAttribute('data-code'), this.textContent);
                    };
                    list.appendChild(li);

                    if (district.ess_code) {
                        if (!win.guangmengyunEssCodes) {
                            win.guangmengyunEssCodes = {};
                        }
                        win.guangmengyunEssCodes[district.code] = district.ess_code;
                    }
                });
            })
            .catch(function () {
                list.innerHTML = '<li class="city-picker-item">获取区县失败</li>';
            });
    }

    function selectDistrict(code, name) {
        selectedDistrict = name;
        selectedDistrictCode = code;
        updateBreadcrumb();
        completeSelection();
    }

    function completeSelection() {
        var result = selectedProvince;
        if (selectedCity) {
            result += ' ' + selectedCity;
        }
        if (selectedDistrict) {
            result += ' ' + selectedDistrict;
        }

        document.querySelector('input[name="customer_city"]').value = result;
        document.querySelector('input[name="province"]').value = selectedProvince || '';
        document.querySelector('input[name="city"]').value = selectedCity || '';
        document.querySelector('input[name="district"]').value = selectedDistrict || '';
        document.querySelector('input[name="province_code"]').value = selectedProvinceCode || '';
        document.querySelector('input[name="city_code"]').value = selectedCityCode || '';
        document.querySelector('input[name="district_code"]').value = selectedDistrictCode || '';
        if (typeof win.clearAddressAreaHint === 'function') {
            win.clearAddressAreaHint();
        }

        closeCityPicker();
    }

    function bindTabClickEvents() {
        var tabs = document.querySelectorAll('.city-picker-tab');
        tabs.forEach(function (tab) {
            tab.removeEventListener('click', win.handleTabClick);
            tab.addEventListener('click', win.handleTabClick);
        });
    }

    function findProvinceCode(provinceName) {
        var provinces = cityData['86'];
        for (var code in provinces) {
            if (provinces[code] === provinceName) {
                return code;
            }
        }
        return null;
    }

    function findCityCode(cityName) {
        for (var provinceCode in cityData) {
            if (provinceCode === '86') {
                continue;
            }
            var cities = cityData[provinceCode];
            for (var code in cities) {
                if (cities[code] === cityName) {
                    return code;
                }
            }
        }
        return null;
    }

    win.goToLevel = function (level) {
        document.getElementById('citySearchInput').value = '';
        currentLevel = level;
        updateTabs();
        updateBreadcrumb();

        if (level === 0) {
            showProvinceList();
        } else if (level === 1 && selectedProvinceCode) {
            showCityList(selectedProvinceCode);
        } else if (level === 2 && selectedCityCode) {
            showDistrictList(selectedCityCode);
        }
    };

    win.filterCityList = function () {
        var searchTerm = document.getElementById('citySearchInput').value.toLowerCase().trim();
        var list = document.getElementById('cityPickerList');

        if (!searchTerm) {
            displayCurrentLevelData();
            return;
        }

        var searchResults = [];
        if (cityData && cityData['86']) {
            for (var provinceCode in cityData['86']) {
                var provinceName = cityData['86'][provinceCode];
                if (provinceName.toLowerCase().includes(searchTerm)) {
                    searchResults.push({
                        type: 'province',
                        code: provinceCode,
                        name: provinceName,
                        fullPath: provinceName
                    });
                }

                if (cityData[provinceCode]) {
                    for (var cityCode in cityData[provinceCode]) {
                        var cityName = cityData[provinceCode][cityCode];
                        if (cityName.toLowerCase().includes(searchTerm)) {
                            searchResults.push({
                                type: 'city',
                                provinceCode: provinceCode,
                                code: cityCode,
                                name: cityName,
                                fullPath: provinceName + ' > ' + cityName
                            });
                        }

                        if (cityData[cityCode]) {
                            for (var districtCode in cityData[cityCode]) {
                                var districtName = cityData[cityCode][districtCode];
                                if (districtName.toLowerCase().includes(searchTerm)) {
                                    searchResults.push({
                                        type: 'district',
                                        provinceCode: provinceCode,
                                        cityCode: cityCode,
                                        code: districtCode,
                                        name: districtName,
                                        fullPath: provinceName + ' > ' + cityName + ' > ' + districtName
                                    });
                                }
                            }
                        }
                    }
                }
            }
        }

        list.innerHTML = '';
        if (searchResults.length > 0) {
            searchResults.forEach(function (item) {
                var li = document.createElement('li');
                li.className = 'city-picker-item';
                li.innerHTML = '<div style="font-weight: 500;">' + item.name + '</div><div style="font-size: 12px; color: #999; margin-top: 2px;">' + item.fullPath + '</div>';
                li.onclick = function () {
                    selectSearchResult(item);
                };
                list.appendChild(li);
            });
        } else {
            list.innerHTML = '<li class="city-picker-item" style="color: #999; text-align: center;">未找到匹配的地区</li>';
        }
    };

    win.handleTabClick = function () {
        var level = parseInt(this.getAttribute('data-level'));

        if (level === 0) {
            showProvinceList();
        } else if (level === 1 && selectedProvince) {
            var provinceCode = findProvinceCode(selectedProvince);
            if (provinceCode) {
                showCityList(provinceCode);
            }
        } else if (level === 2 && selectedCity) {
            var cityCode = findCityCode(selectedCity);
            if (cityCode) {
                showDistrictList(cityCode);
            }
        }
    };

    win.showCityPicker = function () {
        document.body.style.overflow = 'hidden';
        document.body.style.position = 'fixed';
        document.body.style.width = '100%';

        var provinceInput = document.querySelector('input[name="province"]');
        var cityInput = document.querySelector('input[name="city"]');
        var districtInput = document.querySelector('input[name="district"]');
        var provinceCodeInput = document.querySelector('input[name="province_code"]');
        var cityCodeInput = document.querySelector('input[name="city_code"]');
        var districtCodeInput = document.querySelector('input[name="district_code"]');

        var hasProvinceName = provinceInput && provinceInput.value;
        var hasProvinceCode = provinceCodeInput && provinceCodeInput.value;
        var hasCityName = cityInput && cityInput.value;
        var hasCityCode = cityCodeInput && cityCodeInput.value;
        var hasDistrictName = districtInput && districtInput.value;
        var hasDistrictCode = districtCodeInput && districtCodeInput.value;

        if ((hasProvinceName && !hasProvinceCode) || (hasCityName && !hasCityCode) || (hasDistrictName && !hasDistrictCode)) {
            selectedProvince = '';
            selectedProvinceCode = '';
            selectedCity = '';
            selectedCityCode = '';
            selectedDistrict = '';
            selectedDistrictCode = '';
            currentLevel = 0;
        } else {
            if (hasProvinceName && hasProvinceCode) {
                selectedProvince = provinceInput.value;
                selectedProvinceCode = provinceCodeInput.value;
            }
            if (hasCityName && hasCityCode) {
                selectedCity = cityInput.value;
                selectedCityCode = cityCodeInput.value;
            }
            if (hasDistrictName && hasDistrictCode) {
                selectedDistrict = districtInput.value;
                selectedDistrictCode = districtCodeInput.value;
            }

            if (selectedDistrict) {
                currentLevel = 2;
            } else if (selectedCity) {
                currentLevel = 1;
            } else {
                currentLevel = 0;
            }
        }

        document.getElementById('citySearchInput').value = '';
        updateBreadcrumb();
        updateTabs();
        loadCityData();

        document.getElementById('cityPickerOverlay').style.display = 'block';
        bindTabClickEvents();
        setTimeout(function () {
            document.querySelector('.city-picker').classList.add('show');
        }, 10);
    };

    win.closeCityPicker = function () {
        document.body.style.overflow = '';
        document.body.style.position = '';
        document.body.style.width = '';

        document.querySelector('.city-picker').classList.remove('show');
        setTimeout(function () {
            document.getElementById('cityPickerOverlay').style.display = 'none';
        }, 300);
    };

    win.completeSelection = completeSelection;
})(window);
