<?php
namespace app\index\controller;

use think\facade\Db;
use think\facade\View;
use think\facade\Log;
use think\facade\Cache;
use app\common\service\ImageService;
use app\index\service\shop\AreaService;
use app\index\service\shop\IpLocationService;
use app\index\service\shop\NumberSelectService;
use app\index\service\shop\PhotoUploadService;
use app\index\service\shop\OrderSupportService;
use app\index\service\shop\OrderSubmitService;
use app\index\service\shop\ProductListService;
use app\index\service\shop\ProductPosterService;
use app\index\service\shop\PublicPosterService;
use app\index\service\shop\ProductPageService;
use app\index\service\shop\VerifyCodeService;
use app\common\service\AgreementProtocolService;
use app\common\service\CommissionCalculationService;
use app\common\service\ShopPublicLinkService;

class Shop
{
    private function applyPageNoCacheHeaders(): void
    {
        header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
    }

    private function useIndexViewPath(): void
    {
        View::config([
            'view_path' => app()->getRootPath() . 'app/index/view' . DIRECTORY_SEPARATOR,
        ]);
    }

    private function buildVueHashUrl(string $path, array $query = []): string
    {
        $query = array_filter($query, function ($value) {
            return $value !== null && $value !== '';
        });
        $base = '/';
        $scheme = request()->header('X-Forwarded-Proto') ?: request()->scheme();
        $host = request()->header('X-Forwarded-Host') ?: request()->server('HTTP_HOST', request()->host());
        if (preg_match('/:(9000|8000)$/', (string)$host)) {
            $host = preg_replace('/:(9000|8000)$/', ':3006', (string)$host);
            $base = $scheme . '://' . $host . '/';
        }

        $url = $base . '#' . $path;
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        return $url;
    }

    private function buildPublicShopUrl(string $shopCode, array $query = []): string
    {
        $query = array_filter($query, function ($value) {
            return $value !== null && $value !== '';
        });
        $url = '/shop/' . rawurlencode($shopCode);
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        return $url;
    }

    private function isCleanShopPath(): bool
    {
        $segments = explode('/', trim(request()->pathinfo(), '/'));
        return count($segments) >= 2 && $segments[0] === 'shop';
    }

    private function isLegacyShopPath(): bool
    {
        $segments = explode('/', trim(request()->pathinfo(), '/'));
        $requestPath = (string)parse_url((string)request()->server('REQUEST_URI', ''), PHP_URL_PATH);
        if (preg_match('#/index/shop(?:/|$)#', $requestPath)) {
            return true;
        }

        return count($segments) >= 3
            && (
                ($segments[0] === 'index' && $segments[1] === 'shop')
                || ($segments[0] === 'shop' && ($segments[1] ?? '') === 'index')
            );
    }

    private function mapIndexTemplateToSet(string $indexTemplate): string
    {
        $mapping = [
            'index1' => 't1',
            'index2' => 't2',
            'index3' => 't3',
        ];
        return $mapping[$indexTemplate] ?? 't1';
    }

    private function normalizeShopTemplate(string $value): string
    {
        $value = strtolower(trim($value));
        $map = [
            '1' => 'template1',
            '2' => 'template2',
            '3' => 'template3',
            'template1' => 'template1',
            'template2' => 'template2',
            'template3' => 'template3',
            'index1' => 'template1',
            'index2' => 'template2',
            'index3' => 'template3',
            'product1' => 'template1',
            'product2' => 'template2',
            'product3' => 'template3',
            'product-v1' => 'template1',
            'product-v2' => 'template2',
            'product-v3' => 'template3',
        ];
        return $map[$value] ?? '';
    }

    private function shopTemplateNumber(string $shopTemplate): string
    {
        $shopTemplate = $this->normalizeShopTemplate($shopTemplate) ?: 'template1';
        return substr($shopTemplate, -1) ?: '1';
    }

    private function mapShopTemplateToIndex(string $shopTemplate): string
    {
        return 'index' . $this->shopTemplateNumber($shopTemplate);
    }

    private function mapShopTemplateToProduct(string $shopTemplate): string
    {
        return 'product' . $this->shopTemplateNumber($shopTemplate);
    }

    private function resolveTemplateSetForCommonPages(): string
    {
        // 预览态支持通过 template/tpl 指定套系
        $requested = $this->normalizeShopTemplate((string)request()->get('__tpl', request()->get('template', request()->get('tpl', ''))));
        if ($requested !== '') {
            return $this->mapIndexTemplateToSet($this->mapShopTemplateToIndex($requested));
        }

        // 正常态根据当前店铺首页模板配置决定套系
        return $this->mapIndexTemplateToSet($this->mapShopTemplateToIndex($this->resolveConfiguredShopTemplate()));
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
        return $this->mapShopTemplateToIndex($this->resolveConfiguredShopTemplate());
    }

    private function resolveConfiguredShopTemplate(): string
    {
        try {
            $version = $this->getTemplateConfigCacheVersion();
            $cacheKey = 'shop_template:unified:' . $version;
            $value = Cache::get($cacheKey);
            if (!is_string($value) || $value === '') {
                $value = Db::name('config_h5')
                    ->where('config_key', 'shop_template')
                    ->where('status', 1)
                    ->order('id', 'desc')
                    ->value('config_value');
                Cache::set($cacheKey, (string)$value, 60);
            }

            $value = $this->normalizeShopTemplate((string)$value);
            if ($value !== '') {
                return $value;
            }

            // 兼容旧键
            $legacyKey = 'shop_template:unified_legacy:' . $version;
            $legacy = Cache::get($legacyKey);
            if (!is_array($legacy)) {
                $legacy = Db::name('config_h5')
                    ->whereIn('config_key', ['shop_index_template', 'shop_product_template', 'product_template'])
                    ->where('status', 1)
                    ->column('config_value', 'config_key');
                Cache::set($legacyKey, $legacy, 60);
            }

            foreach (['shop_index_template', 'shop_product_template', 'product_template'] as $key) {
                $mapped = $this->normalizeShopTemplate((string)($legacy[$key] ?? ''));
                if ($mapped !== '') {
                    return $mapped;
                }
            }
        } catch (\Throwable $e) {
        }

        return 'template1';
    }

    private function resolveConfiguredProductTemplate(): string
    {
        return $this->mapShopTemplateToProduct($this->resolveConfiguredShopTemplate());
    }

    private function getTemplateConfigCacheVersion(): int
    {
        $version = Cache::get('shop_template_config_version');
        if (empty($version)) {
            $version = 1;
            Cache::set('shop_template_config_version', $version, 0);
        }
        return intval($version);
    }
    /**
     * 店铺首页展示（面向用户）🆕
     */
    public function index($shop_code = '')
    {
        $this->applyPageNoCacheHeaders();
        $this->useIndexViewPath();

        // 显式读取 GET 参数，避免参数名在某些场景被框架解析链覆盖
        $template = request()->get('__tpl', request()->get('template', request()->get('tpl', input('param.template', input('template', 'default')))));
        if (empty($shop_code)) {
            $shop_code = input('shop_code', '');
        }
        if (empty($shop_code)) {
            $pathInfo = request()->pathinfo();
            $segments = explode('/', trim($pathInfo, '/'));
            $shopCodeIndex = array_search('shop_code', $segments, true);
            if ($shopCodeIndex !== false && !empty($segments[$shopCodeIndex + 1])) {
                $shop_code = $segments[$shopCodeIndex + 1];
            } elseif (count($segments) >= 3 && $segments[0] == 'index' && $segments[1] == 'shop') {
                if (($segments[2] ?? '') !== 'index') {
                    $shop_code = $segments[2];
                }
            }
            if (empty($shop_code) && count($segments) >= 2 && $segments[0] === 'shop') {
                $shop_code = $segments[1];
            }
        }

        if (empty($shop_code)) {
            return $this->error('店铺不存在');
        }
        if ($this->isLegacyShopPath() && !$this->isPreviewRequest() && (int)input('_legacy', 0) !== 1) {
            return redirect($this->buildPublicShopUrl((string)$shop_code, [
                'template' => request()->get('template', request()->get('tpl', request()->get('__tpl', '')))
            ]));
        }
        if (!$this->isPreviewRequest() && (int)input('_legacy', 0) !== 1) {
            if ($this->isCleanShopPath()) {
                // 短链接直接渲染店铺，保持浏览器地址为 /shop/{shop_code}
            } else {
                try {
                    return redirect((new ShopPublicLinkService())->buildShopUrl((string)$shop_code));
                } catch (\Throwable $e) {
                    return redirect($this->buildVueHashUrl('/shop/' . rawurlencode((string)$shop_code), [
                        'template' => request()->get('template', request()->get('tpl', request()->get('__tpl', '')))
                    ]));
                }
            }
        }

        // 对外访问统一使用干净链接，不暴露 template/tpl 后缀参数
        if ($this->shouldStripTemplateParams()) {
            $remainQuery = $this->buildRemainQuery(['__tpl', 'template', 'tpl', '_preview', '_t']);
            $url = $this->buildPublicShopUrl((string)$shop_code);
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
            $requestedShopTemplate = $this->normalizeShopTemplate((string)$template);
            if ($this->isPreviewRequest() && $requestedShopTemplate !== '') {
                $requestedTemplate = $this->mapShopTemplateToIndex($requestedShopTemplate);
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
     * Vue 店铺首页数据
     */
    public function vueIndex()
    {
        $this->applyPageNoCacheHeaders();

        $shopCode = input('shop_code', '');
        if (empty($shopCode)) {
            return json(['code' => 0, 'msg' => '店铺不存在', 'data' => null]);
        }

        try {
            $productListService = new ProductListService();
            $viewData = $productListService->getShopIndexViewData($shopCode);
            $shop = $viewData['shop'] ?? [];
            if (!empty($shop)) {
                $shop['online_service_url'] = $this->getOnlineServiceUrl();
                $this->recordVisit($shop, 'shop');
            }

            $requestedTemplate = $this->normalizeShopTemplate((string)request()->get('__tpl', request()->get('template', request()->get('tpl', ''))));
            $indexTemplate = $requestedTemplate !== ''
                ? $this->mapShopTemplateToIndex($requestedTemplate)
                : $this->resolveConfiguredIndexTemplate();
            $productTemplate = $this->resolveConfiguredProductTemplate();
            $publicLinkService = new ShopPublicLinkService();
            $publicLinks = $publicLinkService->publicLinks((string)$shopCode);

            return json([
                'code' => 1,
                'msg' => '获取成功',
                'data' => [
                    'shop' => $shop,
                    'products' => $viewData['products'] ?? [],
                    'totalProductCount' => (int)($viewData['totalProductCount'] ?? 0),
                    'productScopeCounts' => $viewData['productScopeCounts'] ?? [],
                    'productCategories' => $viewData['productCategories'] ?? [],
                    'defaultProductScope' => $viewData['defaultProductScope'] ?? ['product_category' => 0, 'card_type' => 'free'],
                    'bannerImages' => $viewData['bannerImages'] ?? [],
                    'bannerLinks' => $viewData['bannerLinks'] ?? [],
                    'baseUrl' => request()->domain(),
                    'publicShopUrl' => $publicLinks['shop_url'] ?? '',
                    'publicShopToken' => $publicLinks['shop_token'] ?? '',
                    'shopTemplate' => $this->normalizeShopTemplate($requestedTemplate) ?: $this->resolveConfiguredShopTemplate(),
                    'indexTemplate' => $indexTemplate,
                    'productTemplate' => $productTemplate,
                    'availableShopTemplates' => ['template1', 'template2', 'template3'],
                    'availableIndexTemplates' => ['index1', 'index2', 'index3'],
                    'availableProductTemplates' => ['product1', 'product2', 'product3']
                ]
            ]);
        } catch (\Throwable $e) {
            Log::error('Vue店铺首页数据加载失败', ['shop_code' => $shopCode, 'error' => $e->getMessage()]);
            return json(['code' => 0, 'msg' => $e->getMessage(), 'data' => null]);
        }
    }

    /**
     * Vue 商品详情页数据
     */
    public function vueProduct()
    {
        $this->applyPageNoCacheHeaders();

        $shopCode = input('shop_code', '');
        $productId = input('product_id', 0);
        $productToken = trim((string)input('product_token', ''));
        if (empty($shopCode) || empty($productId)) {
            return json(['code' => 0, 'msg' => '参数错误', 'data' => null]);
        }

        try {
            $productPageService = new ProductPageService();
            $publicLinkService = new ShopPublicLinkService();
            $shop = $productPageService->getActiveShopByCode($shopCode);
            if (!$shop) {
                return json(['code' => 0, 'msg' => '店铺不存在', 'data' => null]);
            }

            $product = $productPageService->getOnlineProductById($productId);
            if (!$product) {
                return json(['code' => 0, 'msg' => '产品不存在或已下架', 'data' => null]);
            }
            if (!$this->isProductVisibleToAgent($product, (int)($shop['agent_id'] ?? 0))) {
                return json(['code' => 0, 'msg' => '无权访问该产品', 'data' => null]);
            }
            $commissionVisibility = (new CommissionCalculationService())->calculateProductVisibility($product, (int)($shop['agent_id'] ?? 0));
            if (empty($commissionVisibility['can_agent_show'])) {
                return json(['code' => 0, 'msg' => '无权访问该产品', 'data' => null]);
            }
            $isTokenProductEntry = $this->isMatchingProductToken($publicLinkService, $productToken, (string)$shopCode, (int)$productId);
            $isShopProductBlocked = $this->isShopProductBlocked((int)($shop['agent_id'] ?? 0), (int)$productId);
            if ($this->isProductBlockedByAncestor((int)($shop['agent_id'] ?? 0), (int)$productId)) {
                return json(['code' => 0, 'msg' => '无权访问该产品', 'data' => null]);
            }
            if ($isShopProductBlocked && !$isTokenProductEntry) {
                return json(['code' => 0, 'msg' => '商品暂不可访问', 'data' => null]);
            }

            $this->recordVisit($shop, 'product', $productId);
            $viewData = $productPageService->buildViewData($shop, $product);

            $requestedTemplate = $this->normalizeShopTemplate((string)request()->get('__tpl', request()->get('template', request()->get('tpl', ''))));
            $productTemplate = $requestedTemplate !== ''
                ? $this->mapShopTemplateToProduct($requestedTemplate)
                : $this->resolveConfiguredProductTemplate();
            $publicLinks = $publicLinkService->publicLinks((string)$shopCode, (int)$productId);

            return json([
                'code' => 1,
                'msg' => '获取成功',
                'data' => [
                    'shop' => $viewData['shop'] ?? $shop,
                    'product' => $viewData['product'] ?? $product,
                    'detailImages' => $viewData['detailImages'] ?? [],
                    'shopOrderVerify' => $viewData['shopOrderVerify'] ?? 'none',
                    'shopOrderSmartRecognitionEnabled' => $viewData['shopOrderSmartRecognitionEnabled'] ?? true,
                    'orderSecurityCheckEnabled' => $viewData['orderSecurityCheckEnabled'] ?? true,
                    'apiTypeCode' => $viewData['apiTypeCode'] ?? 0,
                    'shopPaymentMethods' => $viewData['shopPaymentMethods'] ?? [],
                    'orderProtocols' => AgreementProtocolService::formatPublicList(
                        AgreementProtocolService::orderProtocolsForProduct((int)$productId)
                    ),
                    'baseUrl' => request()->domain(),
                    'publicShopUrl' => $publicLinks['shop_url'] ?? '',
                    'publicShopToken' => $publicLinks['shop_token'] ?? '',
                    'publicProductUrl' => $publicLinks['product_url'] ?? '',
                    'publicProductToken' => $publicLinks['product_token'] ?? '',
                    'isShopProductBlocked' => $isShopProductBlocked ? 1 : 0,
                    'isTokenProductEntry' => $isTokenProductEntry ? 1 : 0,
                    'showHomeEntry' => $isShopProductBlocked && $isTokenProductEntry ? 0 : 1,
                    'shopTemplate' => $requestedTemplate ?: $this->resolveConfiguredShopTemplate(),
                    'productTemplate' => $productTemplate,
                    'availableShopTemplates' => ['template1', 'template2', 'template3'],
                    'availableProductTemplates' => ['product1', 'product2', 'product3']
                ]
            ]);
        } catch (\Throwable $e) {
            Log::error('Vue商品详情数据加载失败', ['shop_code' => $shopCode, 'product_id' => $productId, 'error' => $e->getMessage()]);
            return json(['code' => 0, 'msg' => $e->getMessage(), 'data' => null]);
        }
    }

    /**
     * 产品详情页
     * 访问方式：/index/shop/product/shop_code/店铺代码/product_id/产品ID
     */
    public function product()
    {
        $this->applyPageNoCacheHeaders();

        $shop_code = input('shop_code', '');
        $product_id = input('product_id', 0);
        // 显式读取 GET 参数，避免参数名在某些场景被框架解析链覆盖
        $template = request()->get('__tpl', request()->get('template', request()->get('tpl', input('param.template', input('template', 'default')))));
        $productPageService = new ProductPageService();

        if (empty($shop_code) || empty($product_id)) {
            return $this->error('参数错误');
        }
        if (!$this->isPreviewRequest() && (int)input('_legacy', 0) !== 1) {
            $product = $productPageService->getOnlineProductById($product_id);
            if (!$product) {
                return redirect($this->buildVueHashUrl('/product/' . (int)$product_id));
            }
            try {
                return redirect((new ShopPublicLinkService())->buildProductUrl((string)$shop_code, (int)$product_id));
            } catch (\Throwable $e) {
                return redirect($this->buildVueHashUrl(
                    '/shop/' . rawurlencode((string)$shop_code) . '/product/' . (int)$product_id,
                    ['template' => request()->get('template', request()->get('tpl', request()->get('__tpl', '')))]
                ));
            }
        }

        // 对外访问统一使用干净链接，不暴露 template/tpl 后缀参数
        if ($this->shouldStripTemplateParams()) {
            $remainQuery = $this->buildRemainQuery(['__tpl', 'template', 'tpl', '_preview', '_t', 'shop_code', 'product_id']);
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
        if (!$this->isProductVisibleToAgent($product, (int)($shop['agent_id'] ?? 0))) {
            return $this->error('无权访问该产品');
        }

        // 记录商品页面访问
        $this->recordVisit($shop, 'product', $product_id);

        View::assign($productPageService->buildViewData($shop, $product));

        // URL 显式指定模板时，优先强制渲染指定模板（避免配置/缓存干扰）
        $requestedTemplate = $this->normalizeShopTemplate((string)$template);
        if ($this->isPreviewRequest() && $requestedTemplate !== '') {
            $productPreviewTemplate = $this->mapShopTemplateToProduct($requestedTemplate);
            $view = $this->getProductTemplateView($productPreviewTemplate);
            if ($this->isPreviewRequest()) {
                header('X-Shop-Template-Requested: ' . $productPreviewTemplate);
                header('X-Shop-Template-Resolved: ' . $view);
            }
            Log::info('[ShopTemplateDebug] product preview', [
                'shop_code' => $shop_code,
                'product_id' => $product_id,
                'requested' => $productPreviewTemplate,
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
        if ((int)input('_legacy', 0) !== 1) {
            return redirect($this->buildVueHashUrl('/shop/' . rawurlencode((string)$shopCode) . '/orders'));
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
        if (strtolower(trim((string)request()->get('__tpl', ''))) === 'index3') {
            return View::fetch('shop/template/t3/order_query');
        }
        return View::fetch($this->getCommonPageView('order_query'));
    }

    // 订单查询结果页面
    public function order_result()
    {
        $shopCode = input('shop_code', '');
        if (empty($shopCode)) {
            $this->error('店铺不存在');
        }
        if ((int)input('_legacy', 0) !== 1) {
            return redirect($this->buildVueHashUrl('/shop/' . rawurlencode((string)$shopCode) . '/orders', [
                'phone' => input('phone', ''),
                'idcard' => input('idcard', '')
            ]));
        }

        $shop = Db::table('agent_shop')->where('shop_code', $shopCode)->find();
        if (!$shop) {
            $this->error('店铺不存在');
        }

        $expressEnabled = \app\common\helper\SystemConfig::get('express_enabled', '0');

        View::assign('shop', $shop);
        View::assign('express_enabled', $expressEnabled);
        View::assign('base_url', request()->domain());
        View::assign('query_phone', input('phone', ''));
        View::assign('query_idcard', input('idcard', ''));
        if (strtolower(trim((string)request()->get('__tpl', ''))) === 'index3') {
            return View::fetch('shop/template/t3/order_result');
        }
        return View::fetch($this->getCommonPageView('order_result'));
    }

    // 客服页面
    public function service()
    {
        $shopCode = input('shop_code', '');
        if (empty($shopCode)) {
            $this->error('店铺不存在');
        }
        if ((int)input('_legacy', 0) !== 1) {
            return redirect($this->buildVueHashUrl('/shop/' . rawurlencode((string)$shopCode) . '/service'));
        }

        $shop = Db::table('agent_shop')->where('shop_code', $shopCode)->find();
        if (!$shop) {
            $this->error('店铺不存在');
        }

        View::assign('shop', $shop);
        View::assign('base_url', request()->domain());
        return View::fetch($this->getCommonPageView('service'));
    }

    // 一键通查页面
    public function one_card_query()
    {
        $shopCode = input('shop_code', '');
        if (empty($shopCode)) {
            $this->error('店铺不存在');
        }
        if ((int)input('_legacy', 0) !== 1) {
            return redirect($this->buildVueHashUrl('/shop/' . rawurlencode((string)$shopCode) . '/one-card-query'));
        }

        $shop = Db::table('agent_shop')->where('shop_code', $shopCode)->find();
        if (!$shop) {
            $this->error('店铺不存在');
        }

        View::assign('shop', $shop);
        View::assign('base_url', request()->domain());
        return View::fetch('shop/one_card_query');
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
        if ((int)input('_legacy', 0) !== 1) {
            return redirect($this->buildVueHashUrl('/shop/' . rawurlencode((string)$shopCode) . '/broadband'));
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
        if ((int)input('_legacy', 0) !== 1) {
            try {
                return redirect((new ShopPublicLinkService())->buildProductUrl((string)$shop_code, (int)$product_id));
            } catch (\Throwable $e) {
                return redirect($this->buildVueHashUrl('/shop/' . rawurlencode((string)$shop_code) . '/product/' . (int)$product_id));
            }
        }

        // 获取店铺和产品信息
        $shop = Db::table('agent_shop')->where('shop_code', $shop_code)->where('status', 1)->find();
        $product = Db::table('product')->where('id', $product_id)->where('status', 1)->find();

        if (!$shop || !$product) {
            return $this->error('店铺或产品不存在');
        }
        if (!$this->isProductVisibleToAgent($product, (int)($shop['agent_id'] ?? 0))) {
            return $this->error('无权访问该产品');
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
        if ((int)input('_legacy', 0) !== 1) {
            return redirect($this->buildVueHashUrl('/shop/upload/photos', [
                'order_id' => $orderId,
                'order_no' => $orderNo
            ]));
        }

        try {
            $photoUploadService = new PhotoUploadService();
            View::assign($photoUploadService->getUploadPageData($orderId, $orderNo));
            return view('upload/photos');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Vue上传照片页初始化数据
     */
    public function vueUploadPhotos()
    {
        $orderId = input('order_id', '');
        $orderNo = input('order_no', '');

        try {
            $photoUploadService = new PhotoUploadService();
            return json([
                'code' => 1,
                'msg' => 'success',
                'data' => $photoUploadService->getUploadPageData($orderId, $orderNo)
            ]);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => $e->getMessage()]);
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
            $productId = input('post.product_id', 0);
        }
        if (empty($productId) && isset($_POST['product_id'])) {
            $productId = $_POST['product_id'];
        }
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
        try {
            // 读取地区数据文件
            $dataFile = __DIR__ . '/../controller/data.json';

            if (!file_exists($dataFile)) {
                return json([
                    'code' => 0,
                    'msg' => '地区数据文件不存在',
                    'data' => []
                ]);
            }

            $jsonData = file_get_contents($dataFile);
            $data = json_decode($jsonData, true);

            if ($data === null) {
                return json([
                    'code' => 0,
                    'msg' => '地区数据格式错误',
                    'data' => []
                ]);
            }

            return json([
                'code' => 1,
                'msg' => '获取成功',
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return json([
                'code' => 0,
                'msg' => '获取地区数据失败：' . $e->getMessage(),
                'data' => []
            ]);
        }
    }

    public function resolveShopToken()
    {
        $token = (string)input('token', '');
        try {
            $linkService = new ShopPublicLinkService();
            $shop = $linkService->resolveShopToken($token);
            if (!$shop) {
                return json(['code' => 0, 'msg' => '店铺链接不存在', 'data' => null]);
            }
            $links = $linkService->publicLinks((string)$shop['shop_code']);
            return json([
                'code' => 1,
                'msg' => '获取成功',
                'data' => [
                    'shop_code' => (string)$shop['shop_code'],
                    'shop_url' => $links['shop_url'] ?? '',
                    'shop_token' => $links['shop_token'] ?? '',
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('公开店铺链接解析失败', ['token' => $token, 'error' => $e->getMessage()]);
            return json(['code' => 0, 'msg' => $e->getMessage(), 'data' => null]);
        }
    }

    public function resolveProductToken()
    {
        $token = (string)input('token', '');
        try {
            $linkService = new ShopPublicLinkService();
            $resolved = $linkService->resolveProductToken($token);
            if (!$resolved) {
                return json(['code' => 0, 'msg' => '商品链接不存在', 'data' => null]);
            }
            $shopCode = (string)$resolved['shop_code'];
            $productId = (int)$resolved['product_id'];
            $links = $linkService->publicLinks($shopCode, $productId);
            return json([
                'code' => 1,
                'msg' => '获取成功',
                'data' => [
                    'shop_code' => $shopCode,
                    'product_id' => $productId,
                    'shop_url' => $links['shop_url'] ?? '',
                    'shop_token' => $links['shop_token'] ?? '',
                    'product_url' => $links['product_url'] ?? '',
                    'product_token' => $links['product_token'] ?? '',
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('公开商品链接解析失败', ['token' => $token, 'error' => $e->getMessage()]);
            return json(['code' => 0, 'msg' => $e->getMessage(), 'data' => null]);
        }
    }

    public function getPublicLinks()
    {
        $shopCode = (string)input('shop_code', '');
        $productId = (int)input('product_id', 0);
        try {
            if ($shopCode === '') {
                return json(['code' => 0, 'msg' => '店铺不存在', 'data' => null]);
            }
            $links = (new ShopPublicLinkService())->publicLinks($shopCode, $productId > 0 ? $productId : null);
            return json(['code' => 1, 'msg' => '获取成功', 'data' => $links]);
        } catch (\Throwable $e) {
            Log::error('公开链接生成失败', ['shop_code' => $shopCode, 'product_id' => $productId, 'error' => $e->getMessage()]);
            return json(['code' => 0, 'msg' => $e->getMessage(), 'data' => null]);
        }
    }

    public function onlineServiceConfig()
    {
        return json([
            'code' => 1,
            'msg' => '获取成功',
            'data' => [
                'online_service_url' => $this->getOnlineServiceUrl(),
            ],
        ]);
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
        $productCategory = input('product_category', ''); // 商品分类：0=流量卡, 1=宽带
        $region = input('region', ''); // 地区筛选：按可发区/禁发区过滤

        Log::info('loadProducts请求', compact('shop_code', 'page', 'limit', 'operator', 'keyword', 'cardType', 'productCategory', 'region'));

        $productListService = new ProductListService();
        return json($productListService->loadProducts($shop_code, $page, $limit, $operator, $keyword, $cardType, $productCategory, $region));
    }

    /**
     * 生成产品推广海报
     */
    public function generateProductPoster()
    {
        $shop_code = input('shop_code', '');
        $product_id = input('product_id', 0);
        $template_id = input('template_id', 1);
        $share_url = input('share_url', '');
        $posterService = new ProductPosterService();
        return json($posterService->generate($shop_code, $product_id, $template_id, $share_url));
    }

    /**
     * 统一生成公开页二维码海报
     */
    public function generatePoster()
    {
        $posterService = new PublicPosterService();
        return json($posterService->generate([
            'scene' => input('scene', 'product'),
            'shop_code' => input('shop_code', ''),
            'product_id' => input('product_id', 0),
            'template_id' => input('template_id', 1),
            'share_url' => input('share_url', '')
        ]));
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
     * 获取换卡下单数据（安全接口，不直接暴露admin/agent路径）
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
        if (empty($shop_code)) {
            $shop_code = input('shop_code', '');
        }
        if (empty($collection_id)) {
            $collection_id = input('collection_id', 0);
        }
        if ((int)input('_legacy', 0) !== 1 && !empty($shop_code) && !empty($collection_id)) {
            return redirect($this->buildVueHashUrl('/shop/' . rawurlencode((string)$shop_code) . '/collection/' . (int)$collection_id));
        }

        try {
            $productListService = new ProductListService();
            View::assign($productListService->getCollectionViewData($shop_code, $collection_id));
            return View::fetch($this->getCommonPageView('collection'));
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Vue 合集页数据
     */
    public function vueCollection()
    {
        $this->applyPageNoCacheHeaders();

        $shopCode = input('shop_code', '');
        $collectionId = input('collection_id', 0);
        if (empty($shopCode) || empty($collectionId)) {
            return json(['code' => 0, 'msg' => '参数错误', 'data' => null]);
        }

        try {
            $productListService = new ProductListService();
            $viewData = $productListService->getCollectionViewData($shopCode, $collectionId);
            $shop = $viewData['shop'] ?? [];
            if (!empty($shop)) {
                $this->recordVisit($shop, 'shop');
            }

            return json([
                'code' => 1,
                'msg' => '获取成功',
                'data' => [
                    'shop' => $shop,
                    'collection' => $viewData['collection'] ?? [],
                    'products' => $viewData['products'] ?? [],
                    'shopCode' => $shopCode,
                    'baseUrl' => request()->domain()
                ]
            ]);
        } catch (\Throwable $e) {
            Log::error('Vue合集页数据加载失败', [
                'shop_code' => $shopCode,
                'collection_id' => $collectionId,
                'error' => $e->getMessage()
            ]);
            return json(['code' => 0, 'msg' => $e->getMessage(), 'data' => null]);
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

    private function isProductVisibleToAgent(array $product, int $agentId): bool
    {
        $visibleGroupIds = trim((string)($product['visible_group_ids'] ?? ''));
        if ($visibleGroupIds === '') {
            return true;
        }

        if ($agentId <= 0) {
            return false;
        }

        $agentGroupId = 0;
        try {
            if (!empty(Db::query("SHOW COLUMNS FROM `agents` LIKE 'group_id'"))) {
                $agentGroupId = (int)(Db::table('agents')->where('id', $agentId)->value('group_id') ?? 0);
            }
        } catch (\Throwable $e) {
            $agentGroupId = 0;
        }

        if ($agentGroupId <= 0) {
            return false;
        }

        $ids = array_filter(array_map('intval', explode(',', $visibleGroupIds)));
        return in_array($agentGroupId, $ids, true);
    }

    private function isMatchingProductToken(ShopPublicLinkService $linkService, string $token, string $shopCode, int $productId): bool
    {
        if ($token === '' || $shopCode === '' || $productId <= 0) {
            return false;
        }

        try {
            $resolved = $linkService->resolveProductToken($token);
            if (!$resolved) {
                return false;
            }

            return (string)($resolved['shop_code'] ?? '') === $shopCode
                && (int)($resolved['product_id'] ?? 0) === $productId;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function agentProductBlockTableExists(): bool
    {
        try {
            return !empty(Db::query("SHOW TABLES LIKE 'agent_product_block'"));
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function isShopProductBlocked(int $agentId, int $productId): bool
    {
        if ($agentId <= 0 || $productId <= 0 || !$this->agentProductBlockTableExists()) {
            return false;
        }

        try {
            return (bool)Db::table('agent_product_block')
                ->where('agent_id', $agentId)
                ->where('product_id', $productId)
                ->where('block_shop', 1)
                ->find();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function getAncestorAgentIds(int $agentId): array
    {
        if ($agentId <= 0) {
            return [];
        }

        $ancestorIds = [];
        $currentId = $agentId;
        $visited = [];
        try {
            for ($i = 0; $i < 20; $i++) {
                if (isset($visited[$currentId])) {
                    break;
                }
                $visited[$currentId] = true;
                $parentId = (int)(Db::table('agents')->where('id', $currentId)->value('parent_id') ?? 0);
                if ($parentId <= 0) {
                    break;
                }
                $ancestorIds[] = $parentId;
                $currentId = $parentId;
            }
        } catch (\Throwable $e) {
            return [];
        }

        return array_values(array_unique(array_filter($ancestorIds)));
    }

    private function isProductBlockedByAncestor(int $agentId, int $productId): bool
    {
        if ($agentId <= 0 || $productId <= 0 || !$this->agentProductBlockTableExists()) {
            return false;
        }

        $ancestorIds = $this->getAncestorAgentIds($agentId);
        if (empty($ancestorIds)) {
            return false;
        }

        try {
            return (bool)Db::table('agent_product_block')
                ->whereIn('agent_id', $ancestorIds)
                ->where('product_id', $productId)
                ->where('block_sub_agent', 1)
                ->find();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function getOnlineServiceUrl(): string
    {
        $config = Db::table('system_config')
            ->where('config_key', 'online_service_url')
            ->find();
        if ($config !== null) {
            return trim((string)($config['config_value'] ?? ''));
        }

        return trim((string)(Db::table('config_h5')
            ->where('config_key', 'online_service_url')
            ->where('status', 1)
            ->value('config_value') ?? ''));
    }
}
