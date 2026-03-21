<?php
/**
 * Open API 路由配置
 */

use think\facade\Route;

// 基础认证接口 - 使用v1前缀避免与现有模块冲突
Route::group('v1', function () {
    Route::post('auth/test', 'app\open\controller\Auth@test');                    // 连通性测试
    Route::get('auth/balance', 'app\open\controller\Auth@getBalance');           // 查询余额
    Route::get('auth/stats', 'app\open\controller\Auth@getStats');               // 使用统计
    Route::get('auth/info', 'app\open\controller\Auth@getInfo');                 // 合作伙伴信息
    Route::post('auth/callback', 'app\open\controller\Auth@updateCallback');      // 更新回调URL
});

// 商品接口
Route::group('v1/product', function () {
    Route::get('list', 'app\open\controller\Product@list');             // 商品列表和详情
});

// 订单接口
Route::group('v1/order', function () {
    Route::post('submit', 'app\open\controller\Order@submit');             // 提交订单
    Route::get('query', 'app\open\controller\Order@query');               // 查询订单
    Route::get('list', 'app\open\controller\Order@getList');              // 订单列表
});

// 选号接口
Route::group('v1/number', function () {
    Route::get('list', 'app\open\controller\Number@getList');             // 号码列表
    Route::get('areas', 'app\open\controller\Number@getAreas');           // 地区列表
});

// 上传接口
Route::group('v1/upload', function () {
    Route::post('photo', 'app\open\controller\Upload@reupload');             // 重传照片
    Route::get('status', 'app\open\controller\Upload@getStatus');         // 上传状态
    Route::post('delete', 'app\open\controller\Upload@delete');           // 删除文件
});

// 回调通知接口
Route::group('v1/notify', function () {
    Route::get('logs', 'app\open\controller\Notify@getLogs');             // 回调日志
    Route::post('retry', 'app\open\controller\Notify@retry');             // 重试回调
    Route::rule('test', 'app\open\controller\Notify@test', 'GET|POST');   // 测试回调地址
});

// 定时任务接口
Route::group('cron', function () {
    Route::get('callback_retry', 'app\open\controller\Cron@callback_retry'); // 回调重试任务
    Route::get('status', 'app\open\controller\Cron@status');                 // 任务状态查询
});
