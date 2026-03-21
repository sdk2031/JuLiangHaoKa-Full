<?php
/**
 * 各渠道API路由配置
 * 显式路由可以解决代码加密后404的问题
 * 
 * 路径格式：/api/kapi.{接口类型}.{控制器}/{方法}
 */

use think\facade\Route;

// ============================================================
// 回调地址兼容路由（支持 .callback 格式）
// ============================================================
Route::rule('api/kapi.hao172.callback', 'api/kapi.hao172.Callback/index', 'GET|POST');
Route::rule('api/kapi.mf58.callback', 'api/kapi.mf58.Callback/index', 'GET|POST');
Route::rule('api/kapi.lanchang.callback/orderStatus', 'api/kapi.lanchang.Callback/orderStatus', 'GET|POST');
Route::rule('api/kapi.lanchang.callback/productStatus', 'api/kapi.lanchang.Callback/productStatus', 'GET|POST');
Route::rule('api/kapi.haoteam.callback/orderStatus', 'api/kapi.haoteam.Callback/orderStatus', 'GET|POST');
Route::rule('order/api/callback', 'api/kapi.haoteam.Callback/orderStatus', 'GET|POST');
Route::rule('api/kapi.haoky.callback', 'api/kapi.haoky.Callback/index', 'GET|POST');
Route::rule('api/kapi.haoy.callback', 'api/kapi.haoy.Callback/index', 'GET|POST');
Route::rule('api/kapi.longbao.callback/orderStatus', 'api/kapi.longbao.Callback/orderStatus', 'GET|POST');
Route::rule('api/kapi.longbao.callback/test', 'api/kapi.longbao.Callback/test', 'GET|POST');
Route::rule('api/kapi.jikeyun.callback/orderStatus', 'api/kapi.jikeyun.Callback/orderStatus', 'GET|POST');
Route::rule('api/kapi.guangmengyun.callback/orderStatus', 'api/kapi.guangmengyun.Callback/orderStatus', 'GET|POST');
Route::rule('api/kapi.guangmengyun.callback/test', 'api/kapi.guangmengyun.Callback/test', 'GET|POST');
Route::rule('api/kapi.gchk.callback', 'api/kapi.gchk.Callback/index', 'GET|POST');
Route::rule('api/kapi.gchk.callback/test', 'api/kapi.gchk.Callback/test', 'GET|POST');
Route::rule('api/kapi.tiancheng.callback/orderStatus', 'api/kapi.tiancheng.Callback/orderStatus', 'GET|POST');

// ============================================================
// 各渠道API路由
// ============================================================

// 172号卡
Route::group('api/kapi.hao172', function () {
    // Product
    Route::rule('product/sync', 'api/kapi.hao172.Product/sync', 'GET|POST');
    Route::rule('product/lightSync', 'api/kapi.hao172.Product/lightSync', 'GET|POST');
    Route::rule('product/autoSync', 'api/kapi.hao172.Product/autoSync', 'GET|POST');
    Route::rule('product/autoLightSync', 'api/kapi.hao172.Product/autoLightSync', 'GET|POST');
    Route::rule('product/autoSyncOnlineProducts', 'api/kapi.hao172.Product/autoSyncOnlineProducts', 'GET|POST');
    Route::rule('product/addSingleProduct', 'api/kapi.hao172.Product/addSingleProduct', 'GET|POST');
    Route::rule('product/syncSingle', 'api/kapi.hao172.Product/syncSingle', 'GET|POST');
    Route::rule('product/index', 'api/kapi.hao172.Product/index', 'GET|POST');
    Route::rule('product/up', 'api/kapi.hao172.Product/up', 'GET|POST');
    Route::rule('product/down', 'api/kapi.hao172.Product/down', 'GET|POST');
    Route::rule('product/upAll', 'api/kapi.hao172.Product/upAll', 'GET|POST');
    Route::rule('product/downAll', 'api/kapi.hao172.Product/downAll', 'GET|POST');
    Route::rule('product/queryNumbers', 'api/kapi.hao172.Product/queryNumbers', 'GET|POST');
    // Order
    Route::rule('order/submitOrder', 'api/kapi.hao172.Order/submitOrder', 'GET|POST');
    Route::rule('order/create', 'api/kapi.hao172.Order/create', 'GET|POST');
    Route::rule('order/status', 'api/kapi.hao172.Order/status', 'GET|POST');
    Route::rule('order/getNumbers', 'api/kapi.hao172.Order/getNumbers', 'GET|POST');
    Route::rule('order/getProvinces', 'api/kapi.hao172.Order/getProvinces', 'GET|POST');
    Route::rule('order/getCities', 'api/kapi.hao172.Order/getCities', 'GET|POST');
    Route::rule('order/getDistricts', 'api/kapi.hao172.Order/getDistricts', 'GET|POST');
    Route::rule('order/uploadIdPhoto', 'api/kapi.hao172.Order/uploadIdPhoto', 'GET|POST');
    Route::rule('order/upload', 'api/kapi.hao172.Order/upload', 'GET|POST');
    // Chaorder
    Route::rule('chaorder/syncOrders', 'api/kapi.hao172.Chaorder/syncOrders', 'GET|POST');
    Route::rule('chaorder/autoSyncStatus', 'api/kapi.hao172.Chaorder/autoSyncStatus', 'GET|POST');
    Route::rule('chaorder/quickSyncNewOrders', 'api/kapi.hao172.Chaorder/quickSyncNewOrders', 'GET|POST');
    // Callback
    Route::rule('callback', 'api/kapi.hao172.Callback/index', 'GET|POST');
    // SelectNumber
    Route::rule('selectnumber/queryNumbers', 'api/kapi.hao172.SelectNumber/queryNumbers', 'GET|POST');
    Route::rule('selectnumber/selectNumber', 'api/kapi.hao172.SelectNumber/selectNumber', 'GET|POST');
    Route::rule('selectnumber/releaseNumber', 'api/kapi.hao172.SelectNumber/releaseNumber', 'GET|POST');
    // Upload
    Route::rule('upload/uploadImages', 'api/kapi.hao172.Upload/uploadImages', 'GET|POST');
    Route::rule('upload/getUploadStatus', 'api/kapi.hao172.Upload/getUploadStatus', 'GET|POST');
    // Config
    Route::rule('config/index', 'api/kapi.hao172.Config/index', 'GET|POST');
    Route::rule('config/save', 'api/kapi.hao172.Config/save', 'GET|POST');
    Route::rule('config/test', 'api/kapi.hao172.Config/test', 'GET|POST');
    Route::rule('config/delete', 'api/kapi.hao172.Config/delete', 'GET|POST');
    Route::rule('config/getProductList', 'api/kapi.hao172.Config/getProductList', 'GET|POST');
});


// 58秒返
Route::group('api/kapi.mf58', function () {
    // Product
    Route::rule('product/sync', 'api/kapi.mf58.Product/sync', 'GET|POST');
    Route::rule('product/lightSync', 'api/kapi.mf58.Product/lightSync', 'GET|POST');
    Route::rule('product/autoSync', 'api/kapi.mf58.Product/autoSync', 'GET|POST');
    Route::rule('product/autoLightSync', 'api/kapi.mf58.Product/autoLightSync', 'GET|POST');
    Route::rule('product/autoSyncOnlineProducts', 'api/kapi.mf58.Product/autoSyncOnlineProducts', 'GET|POST');
    Route::rule('product/addSingleProduct', 'api/kapi.mf58.Product/addSingleProduct', 'GET|POST');
    Route::rule('product/index', 'api/kapi.mf58.Product/index', 'GET|POST');
    // Order
    Route::rule('order/submit', 'api/kapi.mf58.Order/submit', 'GET|POST');
    Route::rule('order/autoSyncStatus', 'api/kapi.mf58.Order/autoSyncStatus', 'GET|POST');
    Route::rule('order/getRegion', 'api/kapi.mf58.Order/getRegion', 'GET|POST');
    Route::rule('order/queryNumbers', 'api/kapi.mf58.Order/queryNumbers', 'GET|POST');
    Route::rule('order/test', 'api/kapi.mf58.Order/test', 'GET|POST');
    // Config
    Route::rule('config/index', 'api/kapi.mf58.Config/index', 'GET|POST');
    Route::rule('config/save', 'api/kapi.mf58.Config/save', 'GET|POST');
    Route::rule('config/test', 'api/kapi.mf58.Config/test', 'GET|POST');
    Route::rule('config/delete', 'api/kapi.mf58.Config/delete', 'GET|POST');
});

// 蓝畅号卡
Route::group('api/kapi.lanchang', function () {
    // Product
    Route::rule('product/sync', 'api/kapi.lanchang.Product/sync', 'GET|POST');
    Route::rule('product/lightSync', 'api/kapi.lanchang.Product/lightSync', 'GET|POST');
    Route::rule('product/autoSync', 'api/kapi.lanchang.Product/autoSync', 'GET|POST');
    Route::rule('product/autoLightSync', 'api/kapi.lanchang.Product/autoLightSync', 'GET|POST');
    Route::rule('product/autoSyncOnlineProducts', 'api/kapi.lanchang.Product/autoSyncOnlineProducts', 'GET|POST');
    Route::rule('product/addSingleProduct', 'api/kapi.lanchang.Product/addSingleProduct', 'GET|POST');
    Route::rule('product/index', 'api/kapi.lanchang.Product/index', 'GET|POST');
    // Order
    Route::rule('order/create', 'api/kapi.lanchang.Order/create', 'GET|POST');
    Route::rule('order/submitOrder', 'api/kapi.lanchang.Order/submitOrder', 'GET|POST');
    Route::rule('order/submit_order', 'api/kapi.lanchang.Order/submit_order', 'GET|POST');
    Route::rule('order/getNumbers', 'api/kapi.lanchang.Order/getNumbers', 'GET|POST');
    Route::rule('order/selectNumber', 'api/kapi.lanchang.Order/selectNumber', 'GET|POST');
    Route::rule('order/uploadIdCard', 'api/kapi.lanchang.Order/uploadIdCard', 'GET|POST');
    // Callback
    Route::rule('callback/orderStatus', 'api/kapi.lanchang.Callback/orderStatus', 'GET|POST');
    Route::rule('callback/productStatus', 'api/kapi.lanchang.Callback/productStatus', 'GET|POST');
    // Config
    Route::rule('config/index', 'api/kapi.lanchang.Config/index', 'GET|POST');
    Route::rule('config/save', 'api/kapi.lanchang.Config/save', 'GET|POST');
    Route::rule('config/test', 'api/kapi.lanchang.Config/test', 'GET|POST');
    Route::rule('config/delete', 'api/kapi.lanchang.Config/delete', 'GET|POST');
});

// 号卡极团
Route::group('api/kapi.haoteam', function () {
    // Product
    Route::rule('product/sync', 'api/kapi.haoteam.Product/sync', 'GET|POST');
    Route::rule('product/lightSync', 'api/kapi.haoteam.Product/lightSync', 'GET|POST');
    Route::rule('product/autoSync', 'api/kapi.haoteam.Product/autoSync', 'GET|POST');
    Route::rule('product/autoLightSync', 'api/kapi.haoteam.Product/autoLightSync', 'GET|POST');
    Route::rule('product/autoSyncOnlineProducts', 'api/kapi.haoteam.Product/autoSyncOnlineProducts', 'GET|POST');
    Route::rule('product/addSingleProduct', 'api/kapi.haoteam.Product/addSingleProduct', 'GET|POST');
    Route::rule('product/index', 'api/kapi.haoteam.Product/index', 'GET|POST');
    // Order
    Route::rule('order/submit', 'api/kapi.haoteam.Order/submit', 'GET|POST');
    // Callback
    Route::rule('callback/orderStatus', 'api/kapi.haoteam.Callback/orderStatus', 'GET|POST');
    // Config
    Route::rule('config/index', 'api/kapi.haoteam.Config/index', 'GET|POST');
    Route::rule('config/save', 'api/kapi.haoteam.Config/save', 'GET|POST');
    Route::rule('config/test', 'api/kapi.haoteam.Config/test', 'GET|POST');
    Route::rule('config/delete', 'api/kapi.haoteam.Config/delete', 'GET|POST');
});

// 卡业联盟
Route::group('api/kapi.haoky', function () {
    // Product
    Route::rule('product/sync', 'api/kapi.haoky.Product/sync', 'GET|POST');
    Route::rule('product/lightSync', 'api/kapi.haoky.Product/lightSync', 'GET|POST');
    Route::rule('product/autoSync', 'api/kapi.haoky.Product/autoSync', 'GET|POST');
    Route::rule('product/autoLightSync', 'api/kapi.haoky.Product/autoLightSync', 'GET|POST');
    Route::rule('product/autoSyncOnlineProducts', 'api/kapi.haoky.Product/autoSyncOnlineProducts', 'GET|POST');
    Route::rule('product/addSingleProduct', 'api/kapi.haoky.Product/addSingleProduct', 'GET|POST');
    Route::rule('product/index', 'api/kapi.haoky.Product/index', 'GET|POST');
    Route::rule('product/statusCallback', 'api/kapi.haoky.Product/statusCallback', 'GET|POST');
    // Order
    Route::rule('order/submitOrder', 'api/kapi.haoky.Order/submitOrder', 'GET|POST');
    Route::rule('order/getNumbers', 'api/kapi.haoky.Order/getNumbers', 'GET|POST');
    Route::rule('order/uploadIdPhoto', 'api/kapi.haoky.Order/uploadIdPhoto', 'GET|POST');
    Route::rule('order/status', 'api/kapi.haoky.Order/status', 'GET|POST');
    Route::rule('order/index', 'api/kapi.haoky.Order/index', 'GET|POST');
    Route::rule('order/callback', 'api/kapi.haoky.Order/callback', 'GET|POST');
    Route::rule('order/resendThreePhotos', 'api/kapi.haoky.Order/resendThreePhotos', 'GET|POST');
    // Callback
    Route::rule('callback', 'api/kapi.haoky.Callback/index', 'GET|POST');
    // Config
    Route::rule('config/index', 'api/kapi.haoky.Config/index', 'GET|POST');
    Route::rule('config/save', 'api/kapi.haoky.Config/save', 'GET|POST');
    Route::rule('config/test', 'api/kapi.haoky.Config/test', 'GET|POST');
    Route::rule('config/delete', 'api/kapi.haoky.Config/delete', 'GET|POST');
});

// 号易
Route::group('api/kapi.haoy', function () {
    // Product
    Route::rule('product/sync', 'api/kapi.haoy.Product/sync', 'GET|POST');
    Route::rule('product/lightSync', 'api/kapi.haoy.Product/lightSync', 'GET|POST');
    Route::rule('product/autoSync', 'api/kapi.haoy.Product/autoSync', 'GET|POST');
    Route::rule('product/autoLightSync', 'api/kapi.haoy.Product/autoLightSync', 'GET|POST');
    Route::rule('product/autoSyncOnlineProducts', 'api/kapi.haoy.Product/autoSyncOnlineProducts', 'GET|POST');
    Route::rule('product/addSingleProduct', 'api/kapi.haoy.Product/addSingleProduct', 'GET|POST');
    Route::rule('product/index', 'api/kapi.haoy.Product/index', 'GET|POST');
    Route::rule('product/getProductDetailsConfig', 'api/kapi.haoy.Product/getProductDetailsConfig', 'GET|POST');
    Route::rule('product/syncProductDetailsConfig', 'api/kapi.haoy.Product/syncProductDetailsConfig', 'GET|POST');
    // HaoyOrder
    Route::rule('haoyorder/submit', 'api/kapi.haoy.HaoyOrder/submit', 'GET|POST');
    Route::rule('haoyorder/getNumbers', 'api/kapi.haoy.HaoyOrder/getNumbers', 'GET|POST');
    Route::rule('haoyorder/uploadIdPhoto', 'api/kapi.haoy.HaoyOrder/uploadIdPhoto', 'GET|POST');
    Route::rule('haoyorder/test', 'api/kapi.haoy.HaoyOrder/test', 'GET|POST');
    // Callback
    Route::rule('callback', 'api/kapi.haoy.Callback/index', 'GET|POST');
    // Config
    Route::rule('config/index', 'api/kapi.haoy.Config/index', 'GET|POST');
    Route::rule('config/save', 'api/kapi.haoy.Config/save', 'GET|POST');
    Route::rule('config/test', 'api/kapi.haoy.Config/test', 'GET|POST');
    Route::rule('config/delete', 'api/kapi.haoy.Config/delete', 'GET|POST');
});

// 天城智控
Route::group('api/kapi.tiancheng', function () {
    // Product
    Route::rule('product/sync', 'api/kapi.tiancheng.Product/sync', 'GET|POST');
    Route::rule('product/lightSync', 'api/kapi.tiancheng.Product/lightSync', 'GET|POST');
    Route::rule('product/autoSync', 'api/kapi.tiancheng.Product/autoSync', 'GET|POST');
    Route::rule('product/autoLightSync', 'api/kapi.tiancheng.Product/autoLightSync', 'GET|POST');
    Route::rule('product/autoSyncOnlineProducts', 'api/kapi.tiancheng.Product/autoSyncOnlineProducts', 'GET|POST');
    Route::rule('product/index', 'api/kapi.tiancheng.Product/index', 'GET|POST');
    Route::rule('product/queryArea', 'api/kapi.tiancheng.Product/queryArea', 'GET|POST');
    Route::rule('product/addSingleProduct', 'api/kapi.tiancheng.Product/addSingleProduct', 'GET|POST');
    // Order
    Route::rule('order/submitOrder', 'api/kapi.tiancheng.Order/submitOrder', 'GET|POST');
    Route::rule('order/create', 'api/kapi.tiancheng.Order/create', 'GET|POST');
    Route::rule('order/addOrder', 'api/kapi.tiancheng.Order/addOrder', 'GET|POST');
    Route::rule('order/getNumbers', 'api/kapi.tiancheng.Order/getNumbers', 'GET|POST');
    Route::rule('order/query', 'api/kapi.tiancheng.Order/query', 'GET|POST');
    Route::rule('order/uploadIdPhoto', 'api/kapi.tiancheng.Order/uploadIdPhoto', 'GET|POST');
    // Chaorder
    Route::rule('chaorder/syncOrders', 'api/kapi.tiancheng.Chaorder/syncOrders', 'GET|POST');
    Route::rule('chaorder/autoSyncStatus', 'api/kapi.tiancheng.Chaorder/autoSyncStatus', 'GET|POST');
    Route::rule('chaorder/quickSyncNewOrders', 'api/kapi.tiancheng.Chaorder/quickSyncNewOrders', 'GET|POST');
    Route::rule('chaorder/batchQuery', 'api/kapi.tiancheng.Chaorder/batchQuery', 'GET|POST');
    Route::rule('chaorder/localList', 'api/kapi.tiancheng.Chaorder/localList', 'GET|POST');
    // SelectNumber
    Route::rule('selectnumber/getNumbers', 'api/kapi.tiancheng.SelectNumber/getNumbers', 'GET|POST');
    Route::rule('selectnumber/selectNumber', 'api/kapi.tiancheng.SelectNumber/selectNumber', 'GET|POST');
    // Upload
    Route::rule('upload/uploadIdPhoto', 'api/kapi.tiancheng.Upload/uploadIdPhoto', 'GET|POST');
    Route::rule('upload/convertToBase64', 'api/kapi.tiancheng.Upload/convertToBase64', 'GET|POST');
    Route::rule('upload/deletePhoto', 'api/kapi.tiancheng.Upload/deletePhoto', 'GET|POST');
    Route::rule('upload/getPhotoInfo', 'api/kapi.tiancheng.Upload/getPhotoInfo', 'GET|POST');
    // Config
    Route::rule('config/index', 'api/kapi.tiancheng.Config/index', 'GET|POST');
    Route::rule('config/save', 'api/kapi.tiancheng.Config/save', 'GET|POST');
    Route::rule('config/test', 'api/kapi.tiancheng.Config/test', 'GET|POST');
    Route::rule('config/delete', 'api/kapi.tiancheng.Config/delete', 'GET|POST');
});

// 龙宝
Route::group('api/kapi.longbao', function () {
    // Product
    Route::rule('product/sync', 'api/kapi.longbao.Product/sync', 'GET|POST');
    Route::rule('product/lightSync', 'api/kapi.longbao.Product/lightSync', 'GET|POST');
    Route::rule('product/autoSync', 'api/kapi.longbao.Product/autoSync', 'GET|POST');
    Route::rule('product/autoLightSync', 'api/kapi.longbao.Product/autoLightSync', 'GET|POST');
    Route::rule('product/autoSyncOnlineProducts', 'api/kapi.longbao.Product/autoSyncOnlineProducts', 'GET|POST');
    Route::rule('product/index', 'api/kapi.longbao.Product/index', 'GET|POST');
    Route::rule('product/test', 'api/kapi.longbao.Product/test', 'GET|POST');
    Route::rule('product/addSingleProduct', 'api/kapi.longbao.Product/addSingleProduct', 'GET|POST');
    // Order
    Route::rule('order/commitOrder', 'api/kapi.longbao.Order/commitOrder', 'GET|POST');
    Route::rule('order/queryOrder', 'api/kapi.longbao.Order/queryOrder', 'GET|POST');
    // Chaorder
    Route::rule('chaorder/syncOrders', 'api/kapi.longbao.Chaorder/syncOrders', 'GET|POST');
    Route::rule('chaorder/autoSyncStatus', 'api/kapi.longbao.Chaorder/autoSyncStatus', 'GET|POST');
    Route::rule('chaorder/quickSyncNewOrders', 'api/kapi.longbao.Chaorder/quickSyncNewOrders', 'GET|POST');
    Route::rule('chaorder/batchQuery', 'api/kapi.longbao.Chaorder/batchQuery', 'GET|POST');
    // Callback
    Route::rule('callback/orderStatus', 'api/kapi.longbao.Callback/orderStatus', 'GET|POST');
    Route::rule('callback/test', 'api/kapi.longbao.Callback/test', 'GET|POST');
    // SelectNumber
    Route::rule('selectnumber/getNumbers', 'api/kapi.longbao.SelectNumber/getNumbers', 'GET|POST');
    Route::rule('selectnumber/validateNumber', 'api/kapi.longbao.SelectNumber/validateNumber', 'GET|POST');
    // Area
    Route::rule('area/getAddressList', 'api/kapi.longbao.Area/getAddressList', 'GET|POST');
    Route::rule('area/getProvinces', 'api/kapi.longbao.Area/getProvinces', 'GET|POST');
    Route::rule('area/getCities', 'api/kapi.longbao.Area/getCities', 'GET|POST');
    Route::rule('area/getDistricts', 'api/kapi.longbao.Area/getDistricts', 'GET|POST');
    // Upload
    Route::rule('upload/uploadCertificate', 'api/kapi.longbao.Upload/uploadCertificate', 'GET|POST');
    Route::rule('upload/uploadLocal', 'api/kapi.longbao.Upload/uploadLocal', 'GET|POST');
    // Config
    Route::rule('config/index', 'api/kapi.longbao.Config/index', 'GET|POST');
    Route::rule('config/save', 'api/kapi.longbao.Config/save', 'GET|POST');
    Route::rule('config/test', 'api/kapi.longbao.Config/test', 'GET|POST');
    Route::rule('config/delete', 'api/kapi.longbao.Config/delete', 'GET|POST');
    Route::rule('config/testAuth', 'api/kapi.longbao.Config/testAuth', 'GET|POST');
    Route::rule('config/checkTable', 'api/kapi.longbao.Config/checkTable', 'GET|POST');
    Route::rule('config/debug', 'api/kapi.longbao.Config/debug', 'GET|POST');
});

// 极客云
Route::group('api/kapi.jikeyun', function () {
    // Product
    Route::rule('product/sync', 'api/kapi.jikeyun.Product/sync', 'GET|POST');
    Route::rule('product/lightSync', 'api/kapi.jikeyun.Product/lightSync', 'GET|POST');
    Route::rule('product/autoSync', 'api/kapi.jikeyun.Product/autoSync', 'GET|POST');
    Route::rule('product/autoLightSync', 'api/kapi.jikeyun.Product/autoLightSync', 'GET|POST');
    Route::rule('product/autoSyncOnlineProducts', 'api/kapi.jikeyun.Product/autoSyncOnlineProducts', 'GET|POST');
    Route::rule('product/index', 'api/kapi.jikeyun.Product/index', 'GET|POST');
    Route::rule('product/syncSingle', 'api/kapi.jikeyun.Product/syncSingle', 'GET|POST');
    Route::rule('product/checkProduct', 'api/kapi.jikeyun.Product/checkProduct', 'GET|POST');
    // Order
    Route::rule('order/submit', 'api/kapi.jikeyun.Order/submit', 'GET|POST');
    Route::rule('order/submitOrder', 'api/kapi.jikeyun.Order/submitOrder', 'GET|POST');
    Route::rule('order/testSubmit', 'api/kapi.jikeyun.Order/testSubmit', 'GET|POST');
    // Chaorder
    Route::rule('chaorder/syncOrders', 'api/kapi.jikeyun.Chaorder/syncOrders', 'GET|POST');
    Route::rule('chaorder/autoSyncStatus', 'api/kapi.jikeyun.Chaorder/autoSyncStatus', 'GET|POST');
    Route::rule('chaorder/quickSyncNewOrders', 'api/kapi.jikeyun.Chaorder/quickSyncNewOrders', 'GET|POST');
    Route::rule('chaorder/batchQuery', 'api/kapi.jikeyun.Chaorder/batchQuery', 'GET|POST');
    Route::rule('chaorder/query', 'api/kapi.jikeyun.Chaorder/query', 'GET|POST');
    Route::rule('chaorder/getStatusMapping', 'api/kapi.jikeyun.Chaorder/getStatusMapping', 'GET|POST');
    // Config
    Route::rule('config/index', 'api/kapi.jikeyun.Config/index', 'GET|POST');
    Route::rule('config/save', 'api/kapi.jikeyun.Config/save', 'GET|POST');
    Route::rule('config/test', 'api/kapi.jikeyun.Config/test', 'GET|POST');
    Route::rule('config/delete', 'api/kapi.jikeyun.Config/delete', 'GET|POST');
    Route::rule('config/testAuth', 'api/kapi.jikeyun.Config/testAuth', 'GET|POST');
    Route::rule('config/debug', 'api/kapi.jikeyun.Config/debug', 'GET|POST');
});

// 广梦云
Route::group('api/kapi.guangmengyun', function () {
    // Product
    Route::rule('product/sync', 'api/kapi.guangmengyun.Product/sync', 'GET|POST');
    Route::rule('product/lightSync', 'api/kapi.guangmengyun.Product/lightSync', 'GET|POST');
    Route::rule('product/autoSync', 'api/kapi.guangmengyun.Product/autoSync', 'GET|POST');
    Route::rule('product/autoLightSync', 'api/kapi.guangmengyun.Product/autoLightSync', 'GET|POST');
    Route::rule('product/autoSyncOnlineProducts', 'api/kapi.guangmengyun.Product/autoSyncOnlineProducts', 'GET|POST');
    Route::rule('product/getGoodsDetails', 'api/kapi.guangmengyun.Product/getGoodsDetails', 'GET|POST');
    Route::rule('product/getGoodsDetailsData', 'api/kapi.guangmengyun.Product/getGoodsDetailsData', 'GET|POST');
    Route::rule('product/syncSingleProduct', 'api/kapi.guangmengyun.Product/syncSingleProduct', 'GET|POST');
    Route::rule('product/addSingleProduct', 'api/kapi.guangmengyun.Product/addSingleProduct', 'GET|POST');
    // Order
    Route::rule('order/commitOrder', 'api/kapi.guangmengyun.Order/commitOrder', 'GET|POST');
    Route::rule('order/queryOrder', 'api/kapi.guangmengyun.Order/queryOrder', 'GET|POST');
    Route::rule('order/batchQueryOrder', 'api/kapi.guangmengyun.Order/batchQueryOrder', 'GET|POST');
    Route::rule('order/uploadCert', 'api/kapi.guangmengyun.Order/uploadCert', 'GET|POST');
    Route::rule('order/autoQueryOrders', 'api/kapi.guangmengyun.Order/autoQueryOrders', 'GET|POST');
    // Callback
    Route::rule('callback/orderStatus', 'api/kapi.guangmengyun.Callback/orderStatus', 'GET|POST');
    Route::rule('callback/test', 'api/kapi.guangmengyun.Callback/test', 'GET|POST');
    // SelectNumber
    Route::rule('selectnumber/getNumbers', 'api/kapi.guangmengyun.SelectNumber/getNumbers', 'GET|POST');
    Route::rule('selectnumber/getNumberRegions', 'api/kapi.guangmengyun.SelectNumber/getNumberRegions', 'GET|POST');
    // Area
    Route::rule('area/getAddressList', 'api/kapi.guangmengyun.Area/getAddressList', 'GET|POST');
    Route::rule('area/getProvinces', 'api/kapi.guangmengyun.Area/getProvinces', 'GET|POST');
    Route::rule('area/getCities', 'api/kapi.guangmengyun.Area/getCities', 'GET|POST');
    Route::rule('area/getDistricts', 'api/kapi.guangmengyun.Area/getDistricts', 'GET|POST');
    // Config
    Route::rule('config/index', 'api/kapi.guangmengyun.Config/index', 'GET|POST');
    Route::rule('config/save', 'api/kapi.guangmengyun.Config/save', 'GET|POST');
    Route::rule('config/test', 'api/kapi.guangmengyun.Config/test', 'GET|POST');
    Route::rule('config/delete', 'api/kapi.guangmengyun.Config/delete', 'GET|POST');
    Route::rule('config/testAuth', 'api/kapi.guangmengyun.Config/testAuth', 'GET|POST');
    Route::rule('config/debug', 'api/kapi.guangmengyun.Config/debug', 'GET|POST');
    Route::rule('config/getGuangmengyunConfig', 'api/kapi.guangmengyun.Config/getGuangmengyunConfig', 'GET|POST');
});

// 共创号卡
Route::group('api/kapi.gchk', function () {
    // Product
    Route::rule('product/sync', 'api/kapi.gchk.Product/sync', 'GET|POST');
    Route::rule('product/lightSync', 'api/kapi.gchk.Product/lightSync', 'GET|POST');
    Route::rule('product/autoSync', 'api/kapi.gchk.Product/autoSync', 'GET|POST');
    Route::rule('product/autoLightSync', 'api/kapi.gchk.Product/autoLightSync', 'GET|POST');
    Route::rule('product/autoSyncOnlineProducts', 'api/kapi.gchk.Product/autoSyncOnlineProducts', 'GET|POST');
    Route::rule('product/singleSync', 'api/kapi.gchk.Product/singleSync', 'GET|POST');
    Route::rule('product/addSingleProduct', 'api/kapi.gchk.Product/addSingleProduct', 'GET|POST');
    // Order
    Route::rule('order/submit', 'api/kapi.gchk.Order/submit', 'GET|POST');
    Route::rule('order/submitOrder', 'api/kapi.gchk.Order/submitOrder', 'GET|POST');
    // Chaorder
    Route::rule('chaorder/syncOrders', 'api/kapi.gchk.Chaorder/syncOrders', 'GET|POST');
    Route::rule('chaorder/autoSyncStatus', 'api/kapi.gchk.Chaorder/autoSyncStatus', 'GET|POST');
    Route::rule('chaorder/quickSyncNewOrders', 'api/kapi.gchk.Chaorder/quickSyncNewOrders', 'GET|POST');
    Route::rule('chaorder/queryOrder', 'api/kapi.gchk.Chaorder/queryOrder', 'GET|POST');
    // Callback
    Route::rule('callback', 'api/kapi.gchk.Callback/index', 'GET|POST');
    Route::rule('callback/test', 'api/kapi.gchk.Callback/test', 'GET|POST');
    // SelectNumber
    Route::rule('selectnumber/getNumbers', 'api/kapi.gchk.SelectNumber/getNumbers', 'GET|POST');
    Route::rule('selectnumber/getPhoneList', 'api/kapi.gchk.SelectNumber/getPhoneList', 'GET|POST');
    // Config
    Route::rule('config/getConfig', 'api/kapi.gchk.Config/getConfig', 'GET|POST');
    Route::rule('config/generateSign', 'api/kapi.gchk.Config/generateSign', 'GET|POST');
    Route::rule('config/verifyCallbackSign', 'api/kapi.gchk.Config/verifyCallbackSign', 'GET|POST');
    Route::rule('config/sendRequest', 'api/kapi.gchk.Config/sendRequest', 'GET|POST');
});

// 91敢探号
Route::rule('api/kapi.gth91.callback', 'api/kapi.gth91.Callback/index', 'GET|POST');
Route::rule('api/kapi.gth91.callback/orderStatus', 'api/kapi.gth91.Callback/orderStatus', 'GET|POST');
Route::rule('api/kapi.gth91.callback/productStatus', 'api/kapi.gth91.Callback/productStatus', 'GET|POST');
Route::group('api/kapi.gth91', function () {
    // Product
    Route::rule('product/sync', 'api/kapi.gth91.Product/sync', 'GET|POST');
    Route::rule('product/lightSync', 'api/kapi.gth91.Product/lightSync', 'GET|POST');
    Route::rule('product/autoSync', 'api/kapi.gth91.Product/autoSync', 'GET|POST');
    Route::rule('product/autoLightSync', 'api/kapi.gth91.Product/autoLightSync', 'GET|POST');
    Route::rule('product/autoSyncOnlineProducts', 'api/kapi.gth91.Product/autoSyncOnlineProducts', 'GET|POST');
    Route::rule('product/addSingleProduct', 'api/kapi.gth91.Product/addSingleProduct', 'GET|POST');
    Route::rule('product/index', 'api/kapi.gth91.Product/index', 'GET|POST');
    // Order
    Route::rule('order/submitOrder', 'api/kapi.gth91.Order/submitOrder', 'GET|POST');
    Route::rule('order/create', 'api/kapi.gth91.Order/create', 'GET|POST');
    Route::rule('order/uploadIdPhoto', 'api/kapi.gth91.Order/uploadIdPhoto', 'GET|POST');
    // Upload
    Route::rule('upload/getStrRand', 'api/kapi.gth91.Upload/getStrRand', 'GET|POST');
    Route::rule('upload/uploadPhotos', 'api/kapi.gth91.Upload/uploadPhotos', 'GET|POST');
    // Callback
    Route::rule('callback', 'api/kapi.gth91.Callback/index', 'GET|POST');
    Route::rule('callback/orderStatus', 'api/kapi.gth91.Callback/orderStatus', 'GET|POST');
    Route::rule('callback/productStatus', 'api/kapi.gth91.Callback/productStatus', 'GET|POST');
    // Config
    Route::rule('config/index', 'api/kapi.gth91.Config/index', 'GET|POST');
    Route::rule('config/save', 'api/kapi.gth91.Config/save', 'GET|POST');
    Route::rule('config/test', 'api/kapi.gth91.Config/test', 'GET|POST');
    Route::rule('config/delete', 'api/kapi.gth91.Config/delete', 'GET|POST');
    Route::rule('config/getSuppliers', 'api/kapi.gth91.Config/getSuppliers', 'GET|POST');
});

// ============================================================
// Cron定时任务路由
// ============================================================
Route::group('api/cron', function () {
    Route::rule('run', 'api/Cron/run', 'GET|POST');
    Route::rule('triggerProductSync', 'api/Cron/triggerProductSync', 'GET|POST');
    Route::rule('triggerOrderSync', 'api/Cron/triggerOrderSync', 'GET|POST');
    Route::rule('resetAgentMonthlyStats', 'api/Cron/resetAgentMonthlyStats', 'GET|POST');
});
