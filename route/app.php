<?php

use think\facade\Route;

// 统一图形验证码入口：直接输出 PNG，避免前端访问裸 /captcha 命中第三方验证码字体依赖
Route::get('agent/graphiccaptcha/generate', 'app\agent\controller\Graphiccaptcha@generate');
Route::get('agent/graphiccaptcha/image', 'app\agent\controller\Graphiccaptcha@image');
Route::get('admin/slidercaptcha/generate', 'app\admin\controller\Slidercaptcha@generate');
Route::post('admin/slidercaptcha/verify', 'app\admin\controller\Slidercaptcha@verify');
Route::get('agent/slidercaptcha/generate', 'app\agent\controller\Slidercaptcha@generate');
Route::post('agent/slidercaptcha/verify', 'app\agent\controller\Slidercaptcha@verify');
Route::get('captcha/image', 'app\agent\controller\Captcha@image');
Route::get('captcha/generate', 'app\agent\controller\Captcha@generate');
Route::get('agent/captcha/image', 'app\agent\controller\Captcha@image');
Route::get('agent/captcha/generate', 'app\agent\controller\Captcha@generate');
Route::get('index/captcha/image', 'app\index\controller\Captcha@image');

// 店铺公开短链接：/shop/{shop_code}
Route::get('shop/:shop_code', 'app\index\controller\Shop@index');
