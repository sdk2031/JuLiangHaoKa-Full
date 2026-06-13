<?php

namespace app\index\service\shop;

use think\facade\Db;
use app\common\helper\SystemConfig;
use app\common\service\FeaturePolicyService;
use app\common\helper\ImageHelper;
use app\common\service\ImageTemplateService;
use app\common\service\MarkupSettlementService;
use app\common\service\payment\PaymentConfigService;

class ProductPageService
{
    private function mapProductTemplateToView(string $templateName): string
    {
        $mapping = [
            'product1' => 'shop/template/t1/product',
            'product2' => 'shop/template/t2/product',
            'product3' => 'shop/template/t3/product',
        ];
        return $mapping[$templateName] ?? 'shop/template/t1/product';
    }

    public function getProductTemplateView($templateName = 'default')
    {
        $templateName = $this->normalizeTemplateName($templateName);
        if ($templateName === 'default') {
            $templateName = $this->getConfiguredProductTemplateName();
        }

        $viewPath = $this->mapProductTemplateToView($templateName);
        $viewFile = app()->getRootPath() . 'app/index/view/' . $viewPath . '.html';
        if (is_file($viewFile)) {
            return $viewPath;
        }
        return 'shop/template/t1/product';
    }

    protected function getConfiguredProductTemplateName()
    {
        try {
            $configValue = Db::name('config_h5')
                ->where('config_key', 'shop_product_template')
                ->order('id', 'desc')
                ->value('config_value');

            if ($configValue === null || $configValue === '') {
                $configValue = Db::name('config_h5')
                ->where('config_key', 'product_template')
                ->order('id', 'desc')
                ->value('config_value');
            }

            return $this->normalizeTemplateName($configValue ?: 'product1');
        } catch (\Exception $e) {
            return 'product1';
        }
    }

    protected function normalizeTemplateName($templateName)
    {
        $templateName = trim((string)$templateName);
        $templateName = strtolower($templateName);
        $templateName = preg_replace('/[^a-z0-9_-]/', '', $templateName);

        $mapping = [
            '' => 'product1',
            'product1' => 'product1',
            'product2' => 'product2',
            'product3' => 'product3',
            // 兼容历史模板配置🆕
            'default' => 'product1',
            'product' => 'product1',
            'product-v1' => 'product1',
            'product-v2' => 'product2',
            'product-v3' => 'product3',
            'template1' => 'product2',
            'template2' => 'product3'
        ];

        return $mapping[$templateName] ?? 'product1';
    }

    public function getActiveShopByCode($shopCode)
    {
        if (empty($shopCode)) {
            return null;
        }

        return Db::table('agent_shop')
            ->where('shop_code', $shopCode)
            ->where('status', 1)
            ->find();
    }

    public function getOnlineProductById($productId)
    {
        if (empty($productId)) {
            return null;
        }

        $product = Db::table('product')->where('id', $productId)->find();
        if (!$product || intval($product['status'] ?? 0) !== 1) {
            return null;
        }

        return $product;
    }

    public function buildViewData(array $shop, array $product)
    {
        $enrichedProduct = $this->enrichProductForShop($shop, $product);
        $policyContext = [
            'product_id' => intval($enrichedProduct['id'] ?? 0),
            'shop_id' => intval($shop['id'] ?? 0),
            'channel_id' => intval($enrichedProduct['self_channel_id'] ?? 0)
        ];

        return [
            'shop' => $shop,
            'product' => $enrichedProduct,
            'detailImages' => ImageHelper::processDetailImages($enrichedProduct['detail_images'] ?? ''),
            'shopOrderVerify' => FeaturePolicyService::getMode('shop_order_verify', $policyContext, 'none'),
            'shopOrderSmartRecognitionEnabled' => SystemConfig::get('shop_order_smart_recognition', '1') === '1',
            'orderSecurityCheckEnabled' => FeaturePolicyService::isEnabled('order_security_check', $policyContext, true),
            'apiTypeCode' => $this->getApiTypeCode($enrichedProduct['api_name'] ?? ''),
            'shopPaymentMethods' => $this->getEnabledCheckoutPaymentMethods()
        ];
    }

    private function getEnabledCheckoutPaymentMethods(): array
    {
        try {
            PaymentConfigService::ensureCorePaymentDefinitions();
            $rows = Db::table('payment_methods')
                ->where('is_enabled', 1)
                ->order('sort_order', 'asc')
                ->select()
                ->toArray();

            $methods = [];
            $exists = [];
            foreach ($rows as $row) {
                $type = strtolower((string)($row['payment_type'] ?? ''));
                $name = (string)($row['payment_name'] ?? '');
                $typeOrName = $type . '|' . mb_strtolower($name);

                if (
                    strpos($typeOrName, 'wechat') !== false ||
                    strpos($typeOrName, 'wxpay') !== false ||
                    mb_strpos($typeOrName, '微信') !== false
                ) {
                    if (!isset($exists['wechat'])) {
                        $methods[] = ['type' => 'wechat', 'name' => '微信支付'];
                        $exists['wechat'] = true;
                    }
                    continue;
                }

                if (
                    strpos($typeOrName, 'alipay') !== false ||
                    mb_strpos($typeOrName, '支付宝') !== false
                ) {
                    if (!isset($exists['alipay'])) {
                        $methods[] = ['type' => 'alipay', 'name' => '支付宝支付'];
                        $exists['alipay'] = true;
                    }
                }
            }
            return $methods;
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function enrichProductForShop(array $shop, array $product)
    {
        $product = $this->applyPaidCardPricing($shop['agent_id'], $product);
        $product['product_type'] = $this->normalizeProductType($product);
        $product['product_type_text'] = $this->productTypeText($product['product_type']);
        $product['product_image'] = ImageHelper::processProductImage($product['product_image'] ?? '');
        $product['display_image'] = ImageTemplateService::getDisplayImage($product);
        $product['processed_tags'] = $this->processProductTags($product['tags'] ?? '');
        $product['card_price_note'] = trim((string)($product['card_price_note'] ?? ''));
        $product['card_price_user_note'] = trim((string)($product['card_price_user_note'] ?? ''));
        $product['card_price_text'] = trim((string)($product['card_price_text'] ?? ''));

        return $product;
    }

    private function normalizeProductType(array $row): string
    {
        $type = trim((string)($row['product_type'] ?? ''));
        if (in_array($type, ['flow_card', 'broadband', 'combo'], true)) {
            return $type;
        }

        return intval($row['product_category'] ?? 0) === 1 ? 'broadband' : 'flow_card';
    }

    private function productTypeText(string $type): string
    {
        $map = [
            'flow_card' => '流量卡',
            'broadband' => '宽带',
            'combo' => '融合套餐',
        ];

        return $map[$type] ?? '流量卡';
    }

    public function applyPaidCardPricing($agentId, array $product)
    {
        if (intval($product['card_type'] ?? 0) !== 1) {
            $product['total_price'] = 0;
            $product['markup_price'] = 0;
            return $product;
        }

        $cardPrice = floatval($product['card_price'] ?? 0);
        $totalMarkup = MarkupSettlementService::getTotalMarkupPrice(intval($agentId), intval($product['id'] ?? 0));
        $product['total_price'] = $cardPrice + $totalMarkup;
        $product['markup_price'] = $totalMarkup;

        return $product;
    }

    public function processProductTags($tags)
    {
        $productTags = [];

        if (!empty(trim($tags))) {
            $tagArray = json_decode($tags, true);
            if (!is_array($tagArray)) {
                $tagArray = explode(',', $tags);
            }

            foreach ($tagArray as $tag) {
                $tag = trim($tag);
                if ($tag !== '') {
                    $productTags[] = $tag;
                }
            }
        }

        return $productTags;
    }

    public function getApiTypeCode($apiName)
    {
        if (empty($apiName)) {
            return 1000;
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
        }

        return 0;
    }
}
