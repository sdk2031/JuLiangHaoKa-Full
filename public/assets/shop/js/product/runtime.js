(function (win) {
    var areaCache = {};

    function toQuery(params) {
        var parts = [];
        Object.keys(params || {}).forEach(function (key) {
            var value = params[key];
            if (value === undefined || value === null || value === '') {
                return;
            }
            parts.push(encodeURIComponent(key) + '=' + encodeURIComponent(value));
        });
        return parts.join('&');
    }

    function buildAreaUrl(params) {
        return '/index/shop/proxyGetArea?' + toQuery(params);
    }

    function fetchAreaData(params) {
        var cacheKey = JSON.stringify(params || {});
        if (areaCache[cacheKey]) {
            return areaCache[cacheKey];
        }

        areaCache[cacheKey] = fetch(buildAreaUrl(params))
            .then(function (response) {
                return response.json();
            })
            .catch(function (error) {
                delete areaCache[cacheKey];
                throw error;
            });

        return areaCache[cacheKey];
    }

    win.ShopProductRuntime = {
        getSubmitUrl: function () {
            return '/index/shop/proxySubmitOrder';
        },
        getUploadUrl: function () {
            return '/index/shop/proxyUploadCertificate';
        },
        buildAreaUrl: buildAreaUrl,
        fetchAreaData: fetchAreaData,
        clearAreaCache: function () {
            areaCache = {};
        }
    };
})(window);
