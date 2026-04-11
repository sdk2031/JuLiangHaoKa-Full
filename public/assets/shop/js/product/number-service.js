(function (win) {
    var availableNumbers = [];
    var filteredNumbers = [];
    var currentPage = 1;

    function showEmptyState() {
        document.getElementById('numberLoading').style.display = 'none';
        document.getElementById('numberList').style.display = 'none';
        document.getElementById('numberEmpty').style.display = 'block';
    }

    function updateNumberStats() {
        var statsElement = document.getElementById('numberStats');
        var filteredCountElement = document.getElementById('filteredCount');

        if (availableNumbers.length > 0) {
            filteredCountElement.textContent = filteredNumbers.length;
            statsElement.style.display = 'block';
        } else {
            statsElement.style.display = 'none';
        }
    }

    function highlightConsecutiveDigits(numberStr) {
        return String(numberStr).replace(/(\d)\1{1,}/g, function (match) {
            return '<span style="color: #ff4d4f; font-weight: bold;border-radius: 2px;">' + match + '</span>';
        });
    }

    function displayNumbers(numbers) {
        document.getElementById('numberLoading').style.display = 'none';
        document.getElementById('numberEmpty').style.display = 'none';
        document.getElementById('numberList').style.display = 'block';

        var listHtml = '';
        numbers.forEach(function (item) {
            var numberValue = item.number || item;
            var numberDesc = item.desc || '普通号码';
            var numberPrice = item.price || 0;
            var highlightedNumber = highlightConsecutiveDigits(numberValue);

            listHtml += '<div class="number-item" onclick="selectNumber(\'' + numberValue + '\', \'' + numberDesc + '\', \'' + numberPrice + '\')"><div class="number-value">' + highlightedNumber + '</div></div>';
        });

        document.getElementById('numberList').innerHTML = listHtml;
    }

    function loadAvailableNumbers(page) {
        currentPage = page || 1;
        document.getElementById('numberLoading').style.display = 'flex';
        document.getElementById('numberList').style.display = 'none';
        document.getElementById('numberEmpty').style.display = 'none';

        var provinceInput = document.querySelector('input[name="province"]');
        var cityInput = document.querySelector('input[name="city"]');
        var province = provinceInput ? provinceInput.value : '';
        var city = cityInput ? cityInput.value : '';

        var requestData = {
            product_id: win.PRODUCT_ID,
            shop_code: win.SHOP_CODE,
            page: currentPage
        };

        if (province) {
            requestData.province = province;
        }
        if (city) {
            requestData.city = city;
        }

        var districtCodeInput = document.querySelector('input[name="district_code"]');
        var provinceCodeInput = document.querySelector('input[name="province_code"]');
        var cityCodeInput = document.querySelector('input[name="city_code"]');

        if (districtCodeInput && districtCodeInput.value) {
            requestData.district_code = districtCodeInput.value;
            requestData.areaId = districtCodeInput.value;
        }
        if (provinceCodeInput && provinceCodeInput.value) {
            requestData.province_code = provinceCodeInput.value;
        }
        if (cityCodeInput && cityCodeInput.value) {
            requestData.city_code = cityCodeInput.value;
        }

        fetch('/index/shop/proxySelectNumber', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(requestData).toString()
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (data.code === 0 && data.data && data.data.length > 0) {
                    availableNumbers = data.data;
                    filteredNumbers = data.data;
                    document.getElementById('numberSearchInput').value = '';
                    updateNumberStats();
                    displayNumbers(filteredNumbers);
                } else {
                    showEmptyState();
                }
            })
            .catch(function () {
                showEmptyState();
            });
    }

    win.showNumberPicker = function () {
        document.body.style.overflow = 'hidden';
        document.body.style.position = 'fixed';
        document.body.style.width = '100%';

        document.getElementById('numberPickerOverlay').style.display = 'block';
        setTimeout(function () {
            document.querySelector('.number-picker').classList.add('show');
        }, 10);

        loadAvailableNumbers(1);
    };

    win.closeNumberPicker = function () {
        document.body.style.overflow = '';
        document.body.style.position = '';
        document.body.style.width = '';

        document.querySelector('.number-picker').classList.remove('show');
        setTimeout(function () {
            document.getElementById('numberPickerOverlay').style.display = 'none';
        }, 300);
    };

    win.filterNumbers = function () {
        var searchTerm = document.getElementById('numberSearchInput').value.trim().toLowerCase();
        if (searchTerm === '') {
            filteredNumbers = availableNumbers;
        } else {
            filteredNumbers = availableNumbers.filter(function (item) {
                var numberValue = item.number || item;
                return String(numberValue).toLowerCase().includes(searchTerm);
            });
        }

        updateNumberStats();
        displayNumbers(filteredNumbers);
    };

    win.quickSearch = function (term) {
        document.getElementById('numberSearchInput').value = term;
        win.filterNumbers();
    };

    win.selectNumber = function (number, desc, price) {
        document.querySelector('input[name="selected_number"]').value = number;
        win.selectedNumber = number;
        win.closeNumberPicker();

        if (price > 0) {
            layer.msg('已选择号码：' + number + '，号码费用：¥' + price, { icon: 1 });
        } else {
            layer.msg('已选择号码：' + number, { icon: 1 });
        }
    };

    win.refreshNumbers = function () {
        currentPage += 1;
        loadAvailableNumbers(currentPage);
        layer.msg('正在为您换一批号码...', { icon: 16, time: 1000 });
    };

    win.ShopProductNumberService = {
        loadAvailableNumbers: loadAvailableNumbers,
        displayNumbers: displayNumbers,
        showEmptyState: showEmptyState
    };
})(window);
