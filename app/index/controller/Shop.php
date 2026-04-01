<?php
namespace app\index\controller;

use think\facade\Db;
use think\facade\View;
use think\facade\Log;
use app\common\service\ImageService;
use app\index\service\shop\AreaService;
use app\index\service\shop\IpLocationService;
use app\index\service\shop\NumberSelectService;
use app\index\service\shop\PhotoUploadService;
use app\index\service\shop\OrderSupportService;
use app\index\service\shop\OrderSubmitService;
use app\index\service\shop\ProductListService;
use app\index\service\shop\ProductPosterService;
use app\index\service\shop\ProductPageService;
use app\index\service\shop\VerifyCodeService;

// 引入QRcode库
require_once app()->getRootPath() . 'public/phpqrcode/qrlib.php';

class Shop
{
    private function mapIndexTemplateToSet(string $indexTemplate): string
    {
        $mapping = [
            'index1' => 't1',
            'index2' => 't2',
            'index3' => 't3',
        ];
        return $mapping[$indexTemplate] ?? 't1';
    }

    private function resolveTemplateSetForCommonPages(): string
    {
        // 预览态支持通过 template/tpl 指定套系
        $requested = strtolower(trim((string)request()->get('__tpl', request()->get('template', request()->get('tpl', '')))));
        if (in_array($requested, ['index1', 'index2', 'index3'], true)) {
            return $this->mapIndexTemplateToSet($requested);
        }

        // 正常态根据当前店铺首页模板配置决定套系
        return $this->mapIndexTemplateToSet($this->resolveConfiguredIndexTemplate());
    }

    private function getCommonPageView(string $pageName): string
    {
        $set = $this->resolveTemplateSetForCommonPages();
        $view = 'shop/template/' . $set . '/' . $pageName;
        $file = app()->getAppPath() . 'index/view/' . $view . '.html';
        if (is_file($file)) {
            return $view;
        }
        return 'shop/template/t1/' . $pageName;
    }

    private function getIndexTemplateView(string $template): string
    {
        $map = [
            'index1' => 'shop/template/t1/index',
            'index2' => 'shop/template/t2/index',
            'index3' => 'shop/template/t3/index',
        ];
        $view = $map[$template] ?? 'shop/template/t1/index';
        $file = app()->getRootPath() . 'app/index/view/' . $view . '.html';
        if (is_file($file)) {
            return $view;
        }
        return 'shop/template/t1/index';
    }

    private function getProductTemplateView(string $template): string
    {
        $map = [
            'product1' => 'shop/template/t1/product',
            'product2' => 'shop/template/t2/product',
            'product3' => 'shop/template/t3/product',
        ];
        $view = $map[$template] ?? 'shop/template/t1/product';
        $file = app()->getRootPath() . 'app/index/view/' . $view . '.html';
        if (is_file($file)) {
            return $view;
        }
        return 'shop/template/t1/product';
    }

    private function isPreviewRequest(): bool
    {
        return (string)request()->get('_preview', '') === '1';
    }

    private function shouldStripTemplateParams(): bool
    {
        $hasTemplateParams = (string)request()->get('__tpl', '') !== ''
            || (string)request()->get('template', '') !== ''
            || (string)request()->get('tpl', '') !== '';
        return $hasTemplateParams && !$this->isPreviewRequest();
    }

    private function buildRemainQuery(array $dropKeys = []): string
    {
        $query = request()->get();
        foreach ($dropKeys as $key) {
            if (array_key_exists($key, $query)) {
                unset($query[$key]);
            }
        }
        return http_build_query($query);
    }

    private function resolveConfiguredIndexTemplate(): string
    {
        try {
            $value = Db::name('config_h5')
                ->where('config_key', 'shop_index_template')
                ->where('status', 1)
                ->order('id', 'desc')
                ->value('config_value');

            $value = strtolower(trim((string)$value));
            if (in_array($value, ['index1', 'index2', 'index3'], true)) {
                return $value;
            }

            // 兼容旧键
            $legacy = Db::name('config_h5')
                ->where('config_key', 'product_template')
                ->where('status', 1)
                ->order('id', 'desc')
                ->value('config_value');
            $legacy = strtolower(trim((string)$legacy));
            if ($legacy === 'product-v2') {
                return 'index2';
            }
            if ($legacy === 'product-v3') {
                return 'index3';
            }
        } catch (\Throwable $e) {
        }

        return 'index1';
    }

    private function resolveConfiguredProductTemplate(): string
    {
        try {
            $value = Db::name('config_h5')
                ->where('config_key', 'shop_product_template')
                ->where('status', 1)
                ->order('id', 'desc')
                ->value('config_value');

            $value = strtolower(trim((string)$value));
            if (in_array($value, ['product1', 'product2', 'product3'], true)) {
                return $value;
            }

            // 兼容旧键
            $legacy = Db::name('config_h5')
                ->where('config_key', 'product_template')
                ->where('status', 1)
                ->order('id', 'desc')
                ->value('config_value');
            $legacy = strtolower(trim((string)$legacy));
            if ($legacy === 'product-v2') {
                return 'product2';
            }
            if ($legacy === 'product-v3') {
                return 'product3';
            }
        } catch (\Throwable $e) {
        }

        return 'product1';
    }
    /**
     * 店铺首页展示（面向用户）🆕
     */
    public function index($shop_code = '')
    {
        // 显式读取 GET 参数，避免参数名在某些场景被框架解析链覆盖
        $template = request()->get('__tpl', request()->get('template', request()->get('tpl', input('param.template', input('template', 'default')))));
        if (empty($shop_code)) {
            $pathInfo = request()->pathinfo();
            $segments = explode('/', $pathInfo);
            if (count($segments) >= 3 && $segments[0] == 'index' && $segments[1] == 'shop') {
                $shop_code = $segments[2];
            }
        }

        if (empty($shop_code)) {
            return $this->error('店铺不存在');
        }

        // 对外访问统一使用干净链接，不暴露 template/tpl 后缀参数
        if ($this->shouldStripTemplateParams()) {
            $remainQuery = $this->buildRemainQuery(['template', 'tpl', '_preview', '_t']);
            $url = '/index/shop/index/shop_code/' . $shop_code;
            if ($remainQuery !== '') {
                $url .= '?' . $remainQuery;
            }
            return redirect($url);
        }

        try {
            $productListService = new ProductListService();
            $viewData = $productListService->getShopIndexViewData($shop_code);
            $shop = $viewData['shop'];

            $this->recordVisit($shop, 'shop');

            View::assign($viewData);
            View::assign('base_url', request()->domain());
            // URL 显式指定模板时，优先强制渲染指定模板（避免配置/缓存干扰）
            $requestedTemplate = strtolower(trim((string)$template));
            if ($this->isPreviewRequest() && in_array($requestedTemplate, ['index1', 'index2', 'index3'], true)) {
                $view = $this->getIndexTemplateView($requestedTemplate);
                if ($this->isPreviewRequest()) {
                    header('X-Shop-Template-Requested: ' . $requestedTemplate);
                    header('X-Shop-Template-Resolved: ' . $view);
                }
                Log::info('[ShopTemplateDebug] index preview', [
                    'shop_code' => $shop_code,
                    'requested' => $requestedTemplate,
                    'resolved_view' => $view,
                    'query' => request()->get()
                ]);
                return View::fetch($view);
            }

            $resolvedTemplate = $this->resolveConfiguredIndexTemplate();
            $resolvedView = $this->getIndexTemplateView($resolvedTemplate);
            if ($this->isPreviewRequest()) {
                header('X-Shop-Template-Requested: ' . $resolvedTemplate);
                header('X-Shop-Template-Resolved: ' . $resolvedView);
            }
            Log::info('[ShopTemplateDebug] index normal', [
                'shop_code' => $shop_code,
                'resolved_template' => $resolvedTemplate,
                'resolved_view' => $resolvedView,
                'query' => request()->get()
            ]);
            return View::fetch($resolvedView);
        } catch (\Exception $e) {
            Log::error('店铺首页加载失败', ['shop_code' => $shop_code, 'error' => $e->getMessage()]);
            return $this->error($e->getMessage());
        }
    }

    /**
     * 产品详情页
     * 访问方式：/index/shop/product/shop_code/店铺代码/product_id/产品ID
     */
    public function product()
    {
        $shop_code = input('shop_code', '');
        $product_id = input('product_id', 0);
        // 显式读取 GET 参数，避免参数名在某些场景被框架解析链覆盖
        $template = request()->get('__tpl', request()->get('template', request()->get('tpl', input('param.template', input('template', 'default')))));
        $productPageService = new ProductPageService();

        if (empty($shop_code) || empty($product_id)) {
            return $this->error('参数错误');
        }

        // 对外访问统一使用干净链接，不暴露 template/tpl 后缀参数
        if ($this->shouldStripTemplateParams()) {
            $remainQuery = $this->buildRemainQuery(['template', 'tpl', '_preview', '_t', 'shop_code', 'product_id']);
            $url = '/index/shop/product/shop_code/' . $shop_code . '/product_id/' . $product_id;
            if ($remainQuery !== '') {
                $url .= '?' . $remainQuery;
            }
            return redirect($url);
        }

        // 获取店铺信息
        $shop = $productPageService->getActiveShopByCode($shop_code);
        if (!$shop) {
            return $this->error('店铺不存在');
        }

        // 获取产品信息
        $product = $productPageService->getOnlineProductById($product_id);
        if (!$product) {
            // 产品不存在或已下架：跳转到公开资料页
            return redirect('/index/product/detail/id/' . (int)$product_id);
        }

        // 记录商品页面访问
        $this->recordVisit($shop, 'product', $product_id);

        View::assign($productPageService->buildViewData($shop, $product));

        // URL 显式指定模板时，优先强制渲染指定模板（避免配置/缓存干扰）
        $requestedTemplate = strtolower(trim((string)$template));
        if ($this->isPreviewRequest() && in_array($requestedTemplate, ['product1', 'product2', 'product3'], true)) {
            $view = $this->getProductTemplateView($requestedTemplate);
            if ($this->isPreviewRequest()) {
                header('X-Shop-Template-Requested: ' . $requestedTemplate);
                header('X-Shop-Template-Resolved: ' . $view);
            }
            Log::info('[ShopTemplateDebug] product preview', [
                'shop_code' => $shop_code,
                'product_id' => $product_id,
                'requested' => $requestedTemplate,
                'resolved_view' => $view,
                'query' => request()->get()
            ]);
            return View::fetch($view);
        }

        $resolvedTemplate = $this->resolveConfiguredProductTemplate();
        $resolvedView = $this->getProductTemplateView($resolvedTemplate);
        if ($this->isPreviewRequest()) {
            header('X-Shop-Template-Requested: ' . $resolvedTemplate);
            header('X-Shop-Template-Resolved: ' . $resolvedView);
        }
        Log::info('[ShopTemplateDebug] product normal', [
            'shop_code' => $shop_code,
            'product_id' => $product_id,
            'resolved_template' => $resolvedTemplate,
            'resolved_view' => $resolvedView,
            'query' => request()->get()
        ]);
        return View::fetch($resolvedView);
    }

    // 查询订单页面
    public function order_query()
    {
        $shopCode = input('shop_code', '');
        if (empty($shopCode)) {
            $this->error('店铺不存在');
        }

        $shop = Db::table('agent_shop')->where('shop_code', $shopCode)->find();
        if (!$shop) {
            $this->error('店铺不存在');
        }

        // 获取快递查询启用状态
        $expressEnabled = \app\common\helper\SystemConfig::get('express_enabled', '0');

        View::assign('shop', $shop);
        View::assign('express_enabled', $expressEnabled);
        View::assign('base_url', request()->domain());
        return View::fetch($this->getCommonPageView('order_query'));
    }

    // 客服页面
    public function service()
    {
        $shopCode = input('shop_code', '');
        if (empty($shopCode)) {
            $this->error('店铺不存在');
        }

        $shop = Db::table('agent_shop')->where('shop_code', $shopCode)->find();
        if (!$shop) {
            $this->error('店铺不存在');
        }

        View::assign('shop', $shop);
        View::assign('base_url', request()->domain());
        return View::fetch($this->getCommonPageView('service'));
    }

    /**
     * 宽带页面
     */
    public function broadband()
    {
        $shopCode = input('shop_code', '');
        if (empty($shopCode)) {
            $this->error('店铺不存在');
        }

        $shop = Db::table('agent_shop')->where('shop_code', $shopCode)->find();
        if (!$shop) {
            $this->error('店铺不存在');
        }

        View::assign('shop', $shop);
        View::assign('base_url', request()->domain());

        $view = 'shop/template/' . $this->resolveTemplateSetForCommonPages() . '/broadband';
        $file = app()->getAppPath() . 'index/view/' . $view . '.html';
        if (!is_file($file)) {
            // 目前宽带页仅在 t2 提供，其他套系兜底到 t2
            $view = 'shop/template/t2/broadband';
        }

        return View::fetch($view);
    }

    /**
     * 下单页面
     * 访问方式：/index/shop/order/shop_code/店铺代码/product_id/产品ID
     */
    public function order()
    {
        $shop_code = input('shop_code', '');
        $product_id = input('product_id', 0);

        if (empty($shop_code) || empty($product_id)) {
            return $this->error('参数错误');
        }

        // 获取店铺和产品信息
        $shop = Db::table('agent_shop')->where('shop_code', $shop_code)->where('status', 1)->find();
        $product = Db::table('product')->where('id', $product_id)->where('status', 1)->find();

        if (!$shop || !$product) {
            return $this->error('店铺或产品不存在');
        }

        // 记录下单页面访问
        $this->recordVisit($shop, 'product', $product_id);

        View::assign([
            'shop' => $shop,
            'product' => $product
        ]);

        return View::fetch('shop/order');
    }

    /**
     * 提交订单
     * 访问方式：POST /index/shop/submit
     */
    public function submit()
    {
        $shop_code = input('shop_code', '');
        if (!request()->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }

        try {
            $orderSubmitService = new OrderSubmitService();
            return json($orderSubmitService->submit($shop_code, function () {
                return $this->proxySubmitOrder();
            }));
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '提交失败：' . $e->getMessage()]);
        }
    }

    /**
     * 统一订单提交接口（处理付费卡和免费卡）
     */
    public function submitOrderWithPayment()
    {
        if (!request()->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }

        $shop_code = input('shop_code', '');

        try {
            $orderSubmitService = new OrderSubmitService();
            return json($orderSubmitService->submitOrderWithPayment($shop_code, function () {
                return $this->proxySubmitOrder();
            }));
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '提交失败：' . $e->getMessage()]);
        }
    }

    /**
     * 提交订单到上游API（公开方法，供支付回调调用）
     */
    public function submitToUpstreamPublic($orderId, $orderData, $product)
    {
        $orderSubmitService = new OrderSubmitService();
        return $orderSubmitService->submitToUpstream($orderId, $orderData, $product, function () {
            return $this->proxySubmitOrder();
        });
    }

    /**
     * 记录店铺访问
     */
    private function recordVisit($shop, $visitType = 'shop', $productId = null)
    {
        try {
            $visitorIp = request()->ip();

            $visitData = [
                'shop_id' => $shop['id'],
                'agent_id' => $shop['agent_id'],
                'visitor_ip' => $visitorIp,
                'location' => $this->getIpLocation($visitorIp),
                'user_agent' => request()->header('User-Agent'),
                'referer' => request()->header('Referer', ''),
                'visit_type' => $visitType,
                'product_id' => $productId,
                'visit_time' => time(),
                'visit_date' => date('Y-m-d')
            ];

            // 检查是否是同一IP在短时间内的重复访问（防刷，1分钟内）
            $recentVisit = Db::table('agent_shop_visits')
                ->where('shop_id', $shop['id'])
                ->where('visitor_ip', $visitData['visitor_ip'])
                ->where('visit_time', '>', time() - 60)
                ->find();

            if (!$recentVisit) {
                Db::table('agent_shop_visits')->insert($visitData);
                
                // 更新店铺访问统计（依赖定时任务每日/每月重置）
                Db::table('agent_shop')->where('id', $shop['id'])->inc('total_visits');
                Db::table('agent_shop')->where('id', $shop['id'])->inc('today_visits');
                Db::table('agent_shop')->where('id', $shop['id'])->inc('month_visits');
            }

        } catch (\Exception $e) {
            error_log('记录店铺访问失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取IP地理位置
     */
    private function getIpLocation($ip)
    {
        // 检查是否是内网IP
        if ($this->isPrivateIp($ip)) {
            return '内网IP';
        }

        // 检查是否是本地IP
        if ($ip == '127.0.0.1' || $ip == '::1') {
            return '本地';
        }

        try {
            // 使用美团API获取IP位置信息
            $apiUrl = 'https://apimobile.meituan.com/locate/v2/ip/loc?client_source=yourAppKey&rgeo=true&ip=' . $ip;

            // 使用curl请求
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response !== false && $httpCode === 200) {
                $data = json_decode($response, true);

                if ($data && isset($data['data']) && isset($data['data']['rgeo'])) {
                    $rgeo = $data['data']['rgeo'];
                    $location = '';

                    // 构建地址字符串
                    if (!empty($rgeo['province'])) {
                        $location .= $rgeo['province'];
                    }
                    if (!empty($rgeo['city']) && $rgeo['city'] != $rgeo['province']) {
                        if ($location) $location .= ' ';
                        $location .= $rgeo['city'];
                    }
                    if (!empty($rgeo['district']) && $rgeo['district'] != $rgeo['city']) {
                        if ($location) $location .= ' ';
                        $location .= $rgeo['district'];
                    }

                    return $location ?: '中国';
                }
            }
        } catch (\Exception $e) {
            // 忽略定位错误
        }

        return ''; // 返回空字符串，前端显示为 -
    }

    /**
     * 检查是否是内网IP
     */
    private function isPrivateIp($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    /**
     * 上传身份证照片
     */
    public function uploadIdPhoto()
    {
        try {
            $photoUploadService = new PhotoUploadService();
            return json($photoUploadService->uploadIdPhoto(request()));
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '上传失败：' . $e->getMessage()]);
        }
    }

    /**
     * 显示上传照片页面
     */
    public function uploadPhotos()
    {
        $orderId = input('order_id', '');
        $orderNo = input('order_no', '');

        try {
            $photoUploadService = new PhotoUploadService();
            View::assign($photoUploadService->getUploadPageData($orderId, $orderNo));
            return view('upload/photos');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 验证订单信息（用于上传照片前的验证）
     */
    public function verifyOrderForUpload()
    {
        $params = json_decode(file_get_contents('php://input'), true);
        try {
            $photoUploadService = new PhotoUploadService();
            return json($photoUploadService->verifyOrderForUpload(is_array($params) ? $params : []));
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '验证失败：' . $e->getMessage()]);
        }
    }

    /**
     * 公开的照片重传接口（供上传照片页面使用，无需登录）
     * 通过订单号验证身份，直接处理照片更新
     */
    public function reuploadPhoto()
    {
        try {
            $params = json_decode(file_get_contents('php://input'), true);
            if (empty($params)) {
                $params = input('post.');
            }

            $photoUploadService = new PhotoUploadService();
            return json($photoUploadService->reuploadPhoto(is_array($params) ? $params : []));
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '照片上传失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 将API名称转换为数值代码（完全隐藏API名称）
     * API类型代码映射：
     * 0 - 未知/默认类型
     * 1000 - 自营
     * 1001 - 天城智控
     * 1002 - 龙宝
     * 1003 - 号易
     * 1004 - 58秒返
     * 1005 - 172号卡
     * 1006 - 卡业联盟
     * 1007 - 号卡极团
     * 1008 - 蓝畅
     * 1009 - 极客云
     * 1010 - 广梦云
     * 1011 - 共创号卡
     * 1012 - 巨量互联
     * 1013 - 91敢探号
     */
    private function getApiTypeCode($apiName)
    {
        if (empty($apiName)) {
            return 1000; // 空API名称默认为自营
        }
        
        if (strpos($apiName, '自营') !== false) {
            return 1000;
        } elseif (strpos($apiName, '天城智控') !== false) {
            return 1001;
        } elseif (strpos($apiName, '龙宝') !== false) {
            return 1002;
        } elseif (strpos($apiName, '号易') !== false) {
            return 1003;
        } elseif (strpos($apiName, '58秒返') !== false) {
            return 1004;
        } elseif (strpos($apiName, '172号卡') !== false) {
            return 1005;
        } elseif (strpos($apiName, '卡业联盟') !== false) {
            return 1006;
        } elseif (strpos($apiName, '号卡极团') !== false) {
            return 1007;
        } elseif (strpos($apiName, '蓝畅') !== false) {
            return 1008;
        } elseif (strpos($apiName, '极客云') !== false) {
            return 1009;
        } elseif (strpos($apiName, '广梦云') !== false) {
            return 1010;
        } elseif (strpos($apiName, '共创号卡') !== false) {
            return 1011;
        } elseif (strpos($apiName, '巨量互联') !== false) {
            return 1012;
        } elseif (strpos($apiName, '91敢探号') !== false) {
            return 1013;
        } else {
            return 0; // 未知类型
        }
    }

    /**
     * 选号代理 - 隐藏API路径和名称
     */
    public function proxySelectNumber()
    {
        $productId = input('product_id', 0);

        $numberSelectService = new NumberSelectService();
        return json($numberSelectService->selectNumbers($productId, request()));
    }

    /**
     * 上传证件代理 - 隐藏API路径和名称
     */
    public function proxyUploadCertificate()
    {
        $productId = input('product_id', 0);
        if (empty($productId)) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }

        // 获取产品信息
        $product = Db::table('product')->where('id', $productId)->where('status', 1)->find();
        if (!$product) {
            return json(['code' => 0, 'msg' => '产品不存在']);
        }

        $apiName = $product['api_name'] ?? '';
        
        // 根据API名称路由到对应的控制器
        if (strpos($apiName, '58秒返') !== false) {
            return app('app\api\controller\kapi\mf58\Order')->uploadIdPhoto();
        } elseif (strpos($apiName, '172号卡') !== false) {
            return app('app\api\controller\kapi\hao172\Order')->uploadIdPhoto();
        } elseif (strpos($apiName, '卡业联盟') !== false) {
            return app('app\api\controller\kapi\haoky\Order')->uploadIdPhoto();
        } elseif (strpos($apiName, '号卡极团') !== false) {
            return app('app\api\controller\kapi\haoteam\Order')->uploadIdPhoto(request());
        } elseif (strpos($apiName, '号易') !== false) {
            return app('app\api\controller\kapi\haoy\HaoyOrder')->uploadIdPhoto();
        } elseif (strpos($apiName, '蓝畅') !== false) {
            return app('app\api\controller\kapi\lanchang\Order')->uploadIdPhoto();
        } elseif (strpos($apiName, '天城智控') !== false) {
            return app('app\api\controller\kapi\tiancheng\Order')->uploadIdPhoto();
        } elseif (strpos($apiName, '龙宝') !== false) {
            return app('app\api\controller\kapi\longbao\Upload')->uploadCertificate();
        } elseif (strpos($apiName, '91敢探号') !== false) {
            return app('app\api\controller\kapi\gth91\Order')->uploadIdPhoto();
        } else {
            return $this->uploadIdPhoto();
        }
    }

    /**
     * 设置订单访问权限（安全机制）
     */
    public function setOrderAccess()
    {
        if (!request()->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $orderNo = $input['order_no'] ?? '';

        if (empty($orderNo)) {
            return json(['code' => 0, 'msg' => '订单号不能为空']);
        }

        // 设置session访问权限，有效期5分钟
        $sessionKey = 'order_access_' . $orderNo;
        session($sessionKey, true);
        
        return json(['code' => 1, 'msg' => '权限设置成功']);
    }

    /**
     * 提交订单代理 - 隐藏API路径和名称
     */
    public function proxySubmitOrder()
    {
        $productId = input('product_id', 0);
        if (empty($productId)) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }

        // 获取产品信息
        $product = Db::table('product')->where('id', $productId)->where('status', 1)->find();
        if (!$product) {
            return json(['code' => 0, 'msg' => '产品不存在']);
        }

        $apiName = $product['api_name'] ?? '';
        
        // 根据API名称路由到对应的控制器
        if (strpos($apiName, '58秒返') !== false) {
            return app('app\api\controller\kapi\mf58\Order')->submit();
        } elseif (strpos($apiName, '172号卡') !== false) {
            return app('app\api\controller\kapi\hao172\Order')->submitOrder();
        } elseif (strpos($apiName, '卡业联盟') !== false) {
            return app('app\api\controller\kapi\haoky\Order')->submitOrder();
        } elseif (strpos($apiName, '号卡极团') !== false) {
            return app('app\api\controller\kapi\haoteam\Order')->submitOrder(request());
        } elseif (strpos($apiName, '号易') !== false) {
            return app('app\api\controller\kapi\haoy\HaoyOrder')->submit();
        } elseif (strpos($apiName, '蓝畅') !== false) {
            return app('app\api\controller\kapi\lanchang\Order')->submitOrder();
        } elseif (strpos($apiName, '天城智控') !== false) {
            return app('app\api\controller\kapi\tiancheng\Order')->submitOrder();
        } elseif (strpos($apiName, '龙宝') !== false) {
            return app('app\api\controller\kapi\longbao\Order')->commitOrder();
        } elseif (strpos($apiName, '极客云') !== false) {
            return app('app\api\controller\kapi\jikeyun\Order')->submitOrder();
        } elseif (strpos($apiName, '广梦云') !== false) {
            return app('app\api\controller\kapi\guangmengyun\Order')->commitOrder();
        } elseif (strpos($apiName, '共创号卡') !== false) {
            return app('app\api\controller\kapi\gchk\Order')->submitOrder(request());
        } elseif (strpos($apiName, '巨量互联') !== false) {
            // 巨量互联API对接
            Log::info('路由到巨量互联Order', ['api_name' => $apiName, 'product_id' => $productId]);
            return app('app\api\controller\kapi\jlcloud\Order')->submitOrder();
        } elseif (strpos($apiName, '91敢探号') !== false) {
            return app('app\api\controller\kapi\gth91\Order')->submitOrder();
        } else {
            return $this->submit();
        }
    }

    /**
     * 获取地区数据代理 - 统一返回格式
     * 返回格式: {code: 0, data: [{code: xxx, name: xxx}, ...]}
     */
    public function proxyGetArea()
    {
        $productId = input('product_id', 0);
        $type = input('type', 'provinces'); // provinces/cities/districts
        $provinceCode = input('province_code', '');
        $cityCode = input('city_code', '');

        $areaService = new AreaService();
        $result = $areaService->getAreaData($productId, $type, $provinceCode, $cityCode, request()->param());

        return json($result);
    }

    /**
     * 获取IP位置信息（代理美团API）- 前端调用接口
     */
    public function getIpLocationApi()
    {
        try {
            $ipLocationService = new IpLocationService();
            return json([
                'code' => 1,
                'msg' => '获取成功',
                'data' => $ipLocationService->getLocationData()
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 0,
                'msg' => '获取IP位置失败：' . $e->getMessage(),
                'data' => []
            ]);
        }
    }

    /**
     * 获取地区数据
     */
    public function getData()
    {
        // 设置响应头
        header('Content-Type: application/json; charset=utf-8');

        try {
            // 读取地区数据文件
            $dataFile = __DIR__ . '/../controller/data.json';

            if (!file_exists($dataFile)) {
                echo json_encode([
                    'code' => 0,
                    'msg' => '地区数据文件不存在',
                    'data' => []
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $jsonData = file_get_contents($dataFile);
            $data = json_decode($jsonData, true);

            if ($data === null) {
                echo json_encode([
                    'code' => 0,
                    'msg' => '地区数据格式错误',
                    'data' => []
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            echo json_encode([
                'code' => 1,
                'msg' => '获取成功',
                'data' => $data
            ], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            echo json_encode([
                'code' => 0,
                'msg' => '获取地区数据失败：' . $e->getMessage(),
                'data' => []
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * 分页加载产品列表
     */
    public function loadProducts()
    {
        $shop_code = input('shop_code', '');
        $page = input('page', 1);
        $limit = input('limit', 12);
        $operator = input('operator', ''); // 运营商筛选
        $keyword = input('keyword', ''); // 搜索关键词
        $cardType = input('card_type', ''); // 卡片类型筛选：free=免费卡, paid=付费卡

        Log::info('loadProducts请求', compact('shop_code', 'page', 'limit', 'operator', 'keyword', 'cardType'));

        $productListService = new ProductListService();
        return json($productListService->loadProducts($shop_code, $page, $limit, $operator, $keyword, $cardType));
    }

    /**
     * 生成产品推广海报
     */
    public function generateProductPoster()
    {
        $shop_code = input('shop_code', '');
        $product_id = input('product_id', 0);
        $template_id = input('template_id', 1);
        $posterService = new ProductPosterService();
        return json($posterService->generate($shop_code, $product_id, $template_id));
    }

    /**
     * 发送验证码（安全的前端接口）
     */
    public function sendVerifyCode()
    {
        if (!request()->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }

        $phone = input('phone', '');
        $shop_code = input('shop_code', '');

        try {
            $verifyCodeService = new VerifyCodeService();
            return json($verifyCodeService->send($phone, $shop_code));
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '发送失败：' . $e->getMessage()]);
        }
    }

    /**
     * 错误页面
     */
    private function error($message)
    {
        View::assign('message', $message);
        return View::fetch($this->getCommonPageView('error'));
    }

    /**
     * 获取重提订单数据（安全接口，不直接暴露admin/agent路径）
     * 访问方式：POST /index/shop/getResubmitOrderData
     * 自动从URL路径中提取shop_code，并根据订单信息判断来源
     */
    public function getResubmitOrderData()
    {
        if (!request()->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }

        $orderId = input('order_id', 0);
        $token = input('token', '');

        try {
            $orderSupportService = new OrderSupportService();
            return json($orderSupportService->getResubmitOrderData($orderId, $token, request()->pathinfo()));
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '获取失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 合集展示页面
     */
    public function collection($shop_code = '', $collection_id = 0)
    {
        try {
            $productListService = new ProductListService();
            View::assign($productListService->getCollectionViewData($shop_code, $collection_id));
            return View::fetch($this->getCommonPageView('collection'));
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 智能地址解析接口
     * POST /index/shop/parseAddress
     * 参数: address - 地址文本
     * 返回: {code: 1, data: {name, phone, idCard, province, city, county, street, address}}
     */
    public function parseAddress()
    {
        if (!request()->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }

        $address = input('address', '');

        try {
            $orderSupportService = new OrderSupportService();
            return json($orderSupportService->parseAddress($address));
        } catch (\Exception $e) {
            \think\facade\Log::error('地址解析失败: ' . $e->getMessage());
            return json(['code' => 0, 'msg' => '解析失败: ' . $e->getMessage()]);
        }
    }

    public function parseAddressSimple()
    {
        if (!request()->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }

        $address = input('address', '');

        try {
            $orderSupportService = new OrderSupportService();
            return json($orderSupportService->parseAddressSimple($address));
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '解析失败']);
        }
    }
}
