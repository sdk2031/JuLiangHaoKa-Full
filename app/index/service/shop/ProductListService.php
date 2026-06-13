<?php

namespace app\index\service\shop;

use app\common\helper\ImageHelper;
use app\common\service\CommissionCalculationService;
use app\common\service\ImageTemplateService;
use app\common\service\MarkupSettlementService;
use app\common\service\ShopPublicLinkService;
use think\facade\Db;
use think\facade\Cache;

class ProductListService
{
    private function hasProductColumn(\PDO $pdo, array $config, string $column): bool
    {
        try {
            $table = $config['prefix'] . 'product';
            $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
            $stmt->execute([$column]);
            return (bool)$stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function getProductSelectFields(\PDO $pdo, array $config): string
    {
        $fields = [
            'id', 'name', 'product_image', 'guishudi', 'age', 'tags',
            'yuezu', 'flow', 'yys', 'selectNumber', 'kaika',
            'commission', 'card_type', 'card_price', 'kefa', 'jinfa', 'admin_sort_order', 'create_time'
        ];

        if ($this->hasProductColumn($pdo, $config, 'dingxiang')) {
            $fields[] = 'dingxiang';
        }
        if ($this->hasProductColumn($pdo, $config, 'call')) {
            $fields[] = '`call`';
        }
        if ($this->hasProductColumn($pdo, $config, 'visible_group_ids')) {
            $fields[] = 'visible_group_ids';
        }
        if ($this->hasProductColumn($pdo, $config, 'visible_group_ids')) {
            $fields[] = 'visible_group_ids';
        }
        if ($this->hasProductColumn($pdo, $config, 'product_type')) {
            $fields[] = 'product_type';
        }
        if ($this->hasProductColumn($pdo, $config, 'product_category')) {
            $fields[] = 'product_category';
        }
        if ($this->hasProductColumn($pdo, $config, 'category_id')) {
            $fields[] = 'category_id';
        }
        if ($this->hasProductColumn($pdo, $config, 'card_price_note')) {
            $fields[] = 'card_price_note';
        }
        if ($this->hasProductColumn($pdo, $config, 'card_price_user_note')) {
            $fields[] = 'card_price_user_note';
        }
        if ($this->hasProductColumn($pdo, $config, 'card_price_text')) {
            $fields[] = 'card_price_text';
        }
        if ($this->hasProductColumn($pdo, $config, 'product_popup')) {
            $fields[] = 'product_popup';
        }
        if ($this->hasProductColumn($pdo, $config, 'order_process')) {
            $fields[] = 'order_process';
        }

        return implode(', ', $fields);
    }

    private function mapIndexTemplateToView(string $templateName): string
    {
        $mapping = [
            'index1' => 'shop/template/t1/index',
            'index2' => 'shop/template/t2/index',
            'index3' => 'shop/template/t3/index',
        ];
        return $mapping[$templateName] ?? 'shop/template/t1/index';
    }

    public function getShopIndexTemplateView($templateName = 'default')
    {
        $templateName = $this->normalizeIndexTemplateName($templateName);
        if ($templateName === 'default') {
            $templateName = $this->getConfiguredIndexTemplateName();
        }

        $viewPath = $this->mapIndexTemplateToView($templateName);
        $viewFile = app()->getRootPath() . 'app/index/view/' . $viewPath . '.html';
        if (is_file($viewFile)) {
            return $viewPath;
        }
        return 'shop/template/t1/index';
    }

    protected function getConfiguredIndexTemplateName()
    {
        try {
            // 优先读取店铺首页模板配置🆕
            $configValue = Db::name('config_h5')
                ->where('config_key', 'shop_index_template')
                ->order('id', 'desc')
                ->value('config_value');

            if (!empty($configValue)) {
                return $this->normalizeIndexTemplateName($configValue);
            }

            // 兼容旧配置：读取产品模板配置并映射到首页模板
            $legacyValue = Db::name('config_h5')
                ->where('config_key', 'product_template')
                ->order('id', 'desc')
                ->value('config_value');

            $legacyValue = strtolower(trim((string)$legacyValue));
            if ($legacyValue === 'product-v2') {
                return 'index2';
            }
            if ($legacyValue === 'product-v3') {
                return 'index3';
            }

            return 'index1';
        } catch (\Exception $e) {
            return 'index1';
        }
    }

    protected function normalizeIndexTemplateName($templateName)
    {
        $templateName = trim((string)$templateName);
        $templateName = strtolower($templateName);
        $templateName = preg_replace('/[^a-z0-9_-]/', '', $templateName);

        $mapping = [
            '' => 'default',
            'default' => 'default',
            'index' => 'index1',
            'index1' => 'index1',
            'index2' => 'index2',
            'index3' => 'index3',
            // 兼容历史/别名写法
            'template1' => 'index2',
            'template2' => 'index3',
        ];

        return $mapping[$templateName] ?? 'index1';
    }

    public function getShopIndexViewData($shopCode)
    {
        if (empty($shopCode)) {
            throw new \Exception('店铺不存在');
        }
        $cacheVersion = $this->getShopProductsCacheVersion();
        $cacheKey = 'shop_products:index:public-link-v2:' . $cacheVersion . ':' . md5((string)$shopCode);
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && !empty($cached)) {
            return $cached;
        }

        $config = $this->getDbConfig();
        $pdo = $this->createPdo($config);

        $shopSql = "SELECT s.*, a.username as agent_username FROM {$config['prefix']}agent_shop s
                   LEFT JOIN {$config['prefix']}agents a ON s.agent_id = a.id
                   WHERE s.shop_code = ? AND s.status = 1 LIMIT 1";
        $shopStmt = $pdo->prepare($shopSql);
        $shopStmt->execute([$shopCode]);
        $shop = $shopStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$shop) {
            throw new \Exception('店铺不存在或已关闭');
        }

        $agentId = intval($shop['agent_id'] ?? 0);
        $productCategories = $this->getVisibleProductCategories($pdo, $config);
        $productScopeCounts = $this->getProductScopeCounts($pdo, $config, $agentId);
        $defaultProductScope = !empty($productCategories)
            ? ['category_id' => intval($productCategories[0]['id'] ?? 0), 'product_category' => '', 'card_type' => '']
            : $this->getDefaultProductScope($productScopeCounts);

        $selectFields = $this->getProductSelectFields($pdo, $config);
        $productParams = [];
        $productWhere = $this->buildProductScopeSql($pdo, $config, $defaultProductScope, $productParams);
        $productWhere .= $this->buildProductVisibilitySql($pdo, $config, intval($shop['agent_id'] ?? 0), $productParams);
        $productWhere .= $this->buildShopBlockSql($pdo, $config, intval($shop['agent_id'] ?? 0), $productParams);
        $sql = "SELECT {$selectFields} FROM {$config['prefix']}product WHERE {$productWhere} ORDER BY admin_sort_order DESC, id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($productParams);
            $allProducts = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            if (empty($allProducts)) {
                $fallbackSql = "SELECT * FROM {$config['prefix']}product WHERE {$productWhere} ORDER BY admin_sort_order DESC, id DESC";
                $fallbackStmt = $pdo->prepare($fallbackSql);
                $fallbackStmt->execute($productParams);
                $allProducts = $fallbackStmt->fetchAll(\PDO::FETCH_ASSOC);
            }
            $allProducts = $this->filterProductsByAgentCommission($allProducts, $agentId);

            $totalProductCount = count($allProducts);
        $sortResult = $this->applyAgentSort($shop['agent_id'], $allProducts);
        $sortedProducts = $sortResult['products'] ?? [];
        if ((empty($sortedProducts) || !is_array($sortedProducts)) && !empty($allProducts)) {
            $sortedProducts = $allProducts;
            $sortResult['hot_ids'] = [];
        }
        $products = array_slice($sortedProducts, 0, 12);
        $products = $this->decorateProducts($products, $shop['agent_id'], $config, $pdo, $sortResult['hot_ids'] ?? [], (string)$shopCode);

        $bannerImages = [];
        if (!empty($shop['banner_images'])) {
            $bannerImages = json_decode($shop['banner_images'], true) ?: [];
        }
        if (empty($bannerImages)) {
            $bannerImages = $this->getDefaultBannerImages();
        }
        $bannerImages = $this->normalizeBannerImages($bannerImages);

        $bannerLinks = [];
        if (!empty($shop['banner_links'])) {
            $bannerLinks = json_decode($shop['banner_links'], true) ?: [];
        }
        while (count($bannerLinks) < count($bannerImages)) {
            $bannerLinks[] = '';
        }

        $result = [
            'shop' => $shop,
            'products' => $products,
            'totalProductCount' => $totalProductCount,
            'productScopeCounts' => $productScopeCounts,
            'productCategories' => $productCategories,
            'defaultProductScope' => $defaultProductScope,
            'bannerImages' => $bannerImages,
            'bannerLinks' => $bannerLinks
        ];
        Cache::set($cacheKey, $result, 60);
        return $result;
    }

    private function buildProductScopeSql(\PDO $pdo, array $config, array $scope, array &$params): string
    {
        $where = "status = 1";
        $usingRealCategory = isset($scope['category_id']) && $scope['category_id'] !== '' && $this->hasProductColumn($pdo, $config, 'category_id');
        if ($this->hasProductColumn($pdo, $config, 'category_id')) {
            $where .= $this->buildVisibleCategorySql($config);
        }
        if ($usingRealCategory) {
            $where .= " AND category_id = ?";
            $params[] = intval($scope['category_id']);
        }
        if (!$usingRealCategory && $this->hasProductColumn($pdo, $config, 'product_category')) {
            $where .= " AND product_category = ?";
            $params[] = intval($scope['product_category'] ?? 0);
        }

        $cardType = (string)($scope['card_type'] ?? '');
        if ($cardType === 'paid') {
            $where .= " AND card_type = 1 AND card_price > 0";
        } elseif ($cardType === 'free') {
            $where .= " AND (card_type != 1 OR card_type IS NULL OR card_price <= 0 OR card_price IS NULL)";
        }

        return $where;
    }

    private function getProductScopeCounts(\PDO $pdo, array $config, int $agentId): array
    {
        $scopes = [
            'flow' => ['product_category' => 0, 'card_type' => 'free'],
            'campus' => ['product_category' => 0, 'card_type' => 'paid'],
            'broadband' => ['product_category' => 1, 'card_type' => ''],
        ];
        $counts = [];

        foreach ($scopes as $key => $scope) {
            $params = [];
            $where = $this->buildProductScopeSql($pdo, $config, $scope, $params);
            $where .= $this->buildProductVisibilitySql($pdo, $config, $agentId, $params);
            $where .= $this->buildShopBlockSql($pdo, $config, $agentId, $params);
            $stmt = $pdo->prepare("SELECT id, commission, card_type, card_price FROM {$config['prefix']}product WHERE {$where}");
            $stmt->execute($params);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $counts[$key] = count($this->filterProductsByAgentCommission(is_array($rows) ? $rows : [], $agentId));
        }

        return $counts;
    }

    private function filterProductsByAgentCommission(array $products, int $agentId): array
    {
        if (empty($products) || $agentId <= 0) {
            return [];
        }

        $commissionService = new CommissionCalculationService();
        $filtered = [];
        foreach ($products as $product) {
            if (!is_array($product)) {
                continue;
            }
            $visibility = $commissionService->calculateProductVisibility($product, $agentId);
            if (!empty($visibility['can_agent_show'])) {
                $filtered[] = $product;
            }
        }

        return $filtered;
    }

    private function getDefaultProductScope(array $counts): array
    {
        $scopeMap = [
            'flow' => ['product_category' => 0, 'card_type' => 'free'],
            'campus' => ['product_category' => 0, 'card_type' => 'paid'],
            'broadband' => ['product_category' => 1, 'card_type' => ''],
        ];

        foreach ($scopeMap as $key => $scope) {
            if (intval($counts[$key] ?? 0) > 0) {
                return $scope;
            }
        }

        return $scopeMap['flow'];
    }

    private function appendRegionAvailabilitySql(\PDO $pdo, array $config, string $region, string &$whereCondition, array &$params): void
    {
        $terms = $this->getRegionMatchTerms($region);
        if (empty($terms)) {
            return;
        }

        if ($this->hasProductColumn($pdo, $config, 'jinfa')) {
            $banParts = [];
            foreach ($terms as $term) {
                $banParts[] = "jinfa LIKE ?";
                $params[] = '%' . $term . '%';
            }
            if (!empty($banParts)) {
                $whereCondition .= " AND (jinfa IS NULL OR jinfa = '' OR jinfa = '待更新' OR NOT (" . implode(' OR ', $banParts) . "))";
            }
        }

        if ($this->hasProductColumn($pdo, $config, 'kefa')) {
            $allowParts = [];
            foreach ($terms as $term) {
                $allowParts[] = "kefa LIKE ?";
                $params[] = '%' . $term . '%';
            }
            $whereCondition .= " AND (kefa IS NULL OR kefa = '' OR kefa = '待更新' OR kefa LIKE '%全国%' OR " . implode(' OR ', $allowParts) . ")";
        }
    }

    private function getRegionMatchTerms(string $region): array
    {
        $region = trim($region);
        if ($region === '' || $region === '全国') {
            return [];
        }

        $terms = [$region];
        $short = preg_replace('/(特别行政区|维吾尔自治区|壮族自治区|回族自治区|自治区|省|市)$/u', '', $region);
        if ($short && $short !== $region) {
            $terms[] = $short;
        }

        if (preg_match('/(.+?[省市区])(.+)$/u', $region, $matches)) {
            $province = trim($matches[1]);
            $city = trim($matches[2]);
            if ($province !== '') {
                $terms[] = $province;
                $provinceShort = preg_replace('/(特别行政区|维吾尔自治区|壮族自治区|回族自治区|自治区|省|市)$/u', '', $province);
                if ($provinceShort) {
                    $terms[] = $provinceShort;
                }
            }
            if ($city !== '') {
                $terms[] = $city;
                $cityShort = preg_replace('/(地区|自治州|盟|市|区|县)$/u', '', $city);
                if ($cityShort) {
                    $terms[] = $cityShort;
                }
            }
        }

        return array_values(array_unique(array_filter($terms, function ($term) {
            return is_string($term) && trim($term) !== '' && trim($term) !== '全国';
        })));
    }

    public function loadProducts($shopCode, $page, $limit, $operator = '', $keyword = '', $cardType = '', $productCategory = '', $region = '')
    {
        try {
            if (empty($shopCode)) {
                return ['code' => 0, 'msg' => '参数错误'];
            }
            $cacheVersion = $this->getShopProductsCacheVersion();
            $cacheKey = 'shop_products:list:public-link-v2:' . $cacheVersion . ':' . md5(json_encode([
                'shop_code' => (string)$shopCode,
                'page' => intval($page),
                'limit' => intval($limit),
                'operator' => (string)$operator,
                'keyword' => (string)$keyword,
                'card_type' => (string)$cardType,
                'product_category' => (string)$productCategory,
                'region' => (string)$region
            ], JSON_UNESCAPED_UNICODE));
            $cached = Cache::get($cacheKey);
            if (is_array($cached) && isset($cached['code'])) {
                return $cached;
            }

            $shop = Db::table('agent_shop')->where('shop_code', $shopCode)->where('status', 1)->find();
            if (!$shop || !is_array($shop)) {
                return ['code' => 0, 'msg' => '店铺不存在'];
            }

            $page = max(1, intval($page));
            $limit = max(1, intval($limit));
            $offset = ($page - 1) * $limit;

            $config = $this->getDbConfig();
            $pdo = $this->createPdo($config);
            $agentId = intval($shop['agent_id'] ?? 0);

            $whereCondition = "status = 1";
            $params = [];
            if ($this->hasProductColumn($pdo, $config, 'category_id')) {
                $whereCondition .= $this->buildVisibleCategorySql($config);
            }

            if ($productCategory !== '' && $productCategory !== null) {
                $category = intval($productCategory);
                if ($this->hasProductColumn($pdo, $config, 'category_id') && $category > 0) {
                    $whereCondition .= " AND category_id = ?";
                    $params[] = $category;
                } elseif ($this->hasProductColumn($pdo, $config, 'product_category') && in_array($category, [0, 1], true)) {
                    $whereCondition .= " AND product_category = ?";
                    $params[] = $category;
                }
            }

            if (!empty($operator)) {
                $whereCondition .= " AND yys = ?";
                $params[] = $operator;
            }

            $this->appendRegionAvailabilitySql($pdo, $config, $region, $whereCondition, $params);

            $hasDingxiang = $this->hasProductColumn($pdo, $config, 'dingxiang');
            if (!empty($keyword)) {
                $whereCondition .= $hasDingxiang
                    ? " AND (name LIKE ? OR yuezu LIKE ? OR flow LIKE ? OR dingxiang LIKE ? OR guishudi LIKE ?)"
                    : " AND (name LIKE ? OR yuezu LIKE ? OR flow LIKE ? OR guishudi LIKE ?)";
                $keywordParam = '%' . $keyword . '%';
                $params[] = $keywordParam;
                $params[] = $keywordParam;
                $params[] = $keywordParam;
                $params[] = $keywordParam;
                if ($hasDingxiang) {
                    $params[] = $keywordParam;
                }
            }

            if (!empty($cardType)) {
                if ($cardType === 'paid') {
                    $whereCondition .= " AND card_type = 1 AND card_price > 0";
                } elseif ($cardType === 'free') {
                    $whereCondition .= " AND (card_type != 1 OR card_type IS NULL OR card_price <= 0 OR card_price IS NULL)";
                }
            }
            $whereCondition .= $this->buildProductVisibilitySql($pdo, $config, intval($shop['agent_id'] ?? 0), $params);
            $whereCondition .= $this->buildShopBlockSql($pdo, $config, intval($shop['agent_id'] ?? 0), $params);

            $selectFields = $this->getProductSelectFields($pdo, $config);
            $sql = "SELECT {$selectFields} FROM {$config['prefix']}product WHERE {$whereCondition}";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $allProducts = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            if (!is_array($allProducts)) {
                $allProducts = [];
            }
            if (empty($allProducts)) {
                $fallbackSql = "SELECT * FROM {$config['prefix']}product WHERE {$whereCondition}";
                $fallbackStmt = $pdo->prepare($fallbackSql);
                $fallbackStmt->execute($params);
                $allProducts = $fallbackStmt->fetchAll(\PDO::FETCH_ASSOC);
                if (!is_array($allProducts)) {
                    $allProducts = [];
                }
            }
            $allProducts = $this->filterProductsByAgentCommission($allProducts, $agentId);
            $total = count($allProducts);

            $sortResult = $this->applyAgentSort($agentId, $allProducts);
            if (!is_array($sortResult)) {
                $sortResult = ['products' => $allProducts, 'hot_ids' => []];
            }
            $sortedProducts = $sortResult['products'] ?? [];
            if ((empty($sortedProducts) || !is_array($sortedProducts)) && !empty($allProducts)) {
                $sortedProducts = $allProducts;
                $sortResult['hot_ids'] = [];
            }
            $products = array_slice($sortedProducts, $offset, $limit);
            if (empty($products) && $total > 0 && !empty($allProducts)) {
                $products = array_slice($allProducts, $offset, $limit);
                $sortResult['hot_ids'] = [];
            }
            $products = $this->decorateProducts($products, $agentId, $config, $pdo, $sortResult['hot_ids'] ?? [], (string)$shopCode);

            $result = [
                'code' => 1,
                'msg' => '获取成功',
                'data' => [
                    'products' => $products,
                    'hasMore' => ($offset + $limit) < $total,
                    'total' => $total,
                    'page' => $page
                ]
            ];
            Cache::set($cacheKey, $result, 45);
            return $result;
        } catch (\Throwable $e) {
            return ['code' => 0, 'msg' => '获取商品失败：' . $e->getMessage()];
        }
    }

    private function getShopProductsCacheVersion(): int
    {
        $key = 'shop_products_cache_version';
        $version = Cache::get($key);
        if (empty($version)) {
            $version = 1;
            Cache::set($key, $version, 0);
        }
        return intval($version);
    }

    public function getCollectionViewData($shopCode, $collectionId)
    {
        if (empty($shopCode) || empty($collectionId)) {
            throw new \Exception('参数错误');
        }

        $shop = Db::table('agent_shop')
            ->alias('s')
            ->leftJoin('agents a', 's.agent_id = a.id')
            ->field('s.*, a.username as agent_username')
            ->where('s.shop_code', $shopCode)
            ->where('s.status', 1)
            ->find();

        if (!$shop) {
            throw new \Exception('店铺不存在');
        }

        $collection = Db::table('product_collection')
            ->where('id', $collectionId)
            ->where('status', 1)
            ->find();

        if (!$collection) {
            throw new \Exception('合集不存在');
        }

        if (!$this->isCollectionVisibleToAgent($collection, (int)$shop['agent_id'])) {
            throw new \Exception('无权访问该合集');
        }

        $products = Db::table('product_collection_item')
            ->alias('pci')
            ->leftJoin('product p', 'pci.product_id = p.id')
            ->where('pci.collection_id', $collectionId)
            ->where('p.status', 1);
        $this->applyProductVisibilityFilter($products, (int)$shop['agent_id'], 'p');
        $this->applyShopBlockFilter($products, (int)$shop['agent_id'], 'p');
        $products = $products
            ->field('p.*, pci.sort as collection_sort')
            ->order('pci.sort', 'asc')
            ->order('pci.id', 'desc')
            ->select()
            ->toArray();
        $products = $this->filterProductsByAgentCommission($products, (int)$shop['agent_id']);
        $config = $this->getDbConfig();
        $pdo = $this->createPdo($config);
        $products = $this->decorateProducts($products, (int)$shop['agent_id'], $config, $pdo, [], (string)$shopCode);

        return [
            'shop' => $shop,
            'collection' => $collection,
            'products' => $products,
            'shop_code' => $shopCode
        ];
    }

    private function isCollectionVisibleToAgent(array $collection, int $agentId): bool
    {
        if ((int)($collection['agent_id'] ?? 0) !== 0) {
            return (int)($collection['agent_id'] ?? 0) === $agentId;
        }

        $visibleGroupIds = trim((string)($collection['visible_group_ids'] ?? ''));
        if ($visibleGroupIds === '') {
            return true;
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

    private function buildProductVisibilitySql(\PDO $pdo, array $config, int $agentId, array &$params): string
    {
        if (!$this->hasProductColumn($pdo, $config, 'visible_group_ids')) {
            return '';
        }

        $agentGroupId = $this->getAgentGroupIdByPdo($pdo, $config, $agentId);
        if ($agentGroupId > 0) {
            $params[] = $agentGroupId;
            return " AND (visible_group_ids = '' OR FIND_IN_SET(?, visible_group_ids))";
        }

        return " AND visible_group_ids = ''";
    }

    private function buildShopBlockSql(\PDO $pdo, array $config, int $agentId, array &$params, string $alias = ''): string
    {
        if ($agentId <= 0 || !$this->agentProductBlockTableExists($pdo, $config)) {
            return '';
        }

        $field = $alias !== '' ? ($alias . '.id') : ($config['prefix'] . 'product.id');
        $ancestorIds = $this->getAncestorAgentIdsByPdo($pdo, $config, $agentId);
        $subAgentIds = $ancestorIds;
        if (!empty($subAgentIds)) {
            $placeholders = implode(',', array_fill(0, count($subAgentIds), '?'));
            $params[] = $agentId;
            foreach ($subAgentIds as $id) {
                $params[] = $id;
            }
            return " AND NOT EXISTS (
                SELECT 1 FROM {$config['prefix']}agent_product_block apb
                WHERE apb.product_id = {$field}
                AND ((apb.agent_id = ? AND apb.block_shop = 1) OR (apb.agent_id IN ({$placeholders}) AND apb.block_sub_agent = 1))
            )";
        }

        $params[] = $agentId;
        return " AND NOT EXISTS (
            SELECT 1 FROM {$config['prefix']}agent_product_block apb
            WHERE apb.product_id = {$field}
            AND apb.agent_id = ?
            AND apb.block_shop = 1
        )";
    }

    private function buildVisibleCategorySql(array $config): string
    {
        try {
            $table = $config['prefix'] . 'product_categories';
            $exists = Db::query("SHOW TABLES LIKE '" . addslashes($table) . "'");
            if (empty($exists)) {
                return '';
            }
            return " AND (category_id = 0 OR category_id IN (SELECT id FROM {$table} WHERE status = 1))";
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function agentProductBlockTableExists(\PDO $pdo, array $config): bool
    {
        try {
            $table = $config['prefix'] . 'agent_product_block';
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            return (bool)$stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function getAncestorAgentIdsByPdo(\PDO $pdo, array $config, int $agentId): array
    {
        if ($agentId <= 0) {
            return [];
        }

        $table = $config['prefix'] . 'agents';
        $ancestorIds = [];
        $currentId = $agentId;
        $visited = [];
        try {
            for ($i = 0; $i < 20; $i++) {
                if (isset($visited[$currentId])) {
                    break;
                }
                $visited[$currentId] = true;
                $stmt = $pdo->prepare("SELECT parent_id FROM {$table} WHERE id = ? LIMIT 1");
                $stmt->execute([$currentId]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                $parentId = intval($row['parent_id'] ?? 0);
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

    private function getVisibleProductCategories(\PDO $pdo, array $config): array
    {
        try {
            $table = $config['prefix'] . 'product_categories';
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            if (!$stmt->fetch(\PDO::FETCH_ASSOC) || !$this->hasProductColumn($pdo, $config, 'category_id')) {
                return [];
            }

            $hasTypeColumn = false;
            try {
                $columnStmt = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE 'product_type'");
                $columnStmt->execute();
                $hasTypeColumn = (bool)$columnStmt->fetch(\PDO::FETCH_ASSOC);
            } catch (\Throwable $e) {
                $hasTypeColumn = false;
            }

            $typeField = $hasTypeColumn ? ', c.product_type' : '';
            $sql = "SELECT c.id, c.name, c.description, c.sort_order, c.is_priority{$typeField}
                    FROM {$table} c
                    WHERE c.status = 1
                    ORDER BY c.sort_order ASC, c.id ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            if (!is_array($rows)) {
                return [];
            }

            return array_map(function ($row) {
                return [
                    'id' => intval($row['id'] ?? 0),
                    'name' => (string)($row['name'] ?? ''),
                    'label' => (string)($row['name'] ?? ''),
                    'value' => intval($row['id'] ?? 0),
                    'description' => (string)($row['description'] ?? ''),
                    'sort_order' => intval($row['sort_order'] ?? 0),
                    'is_priority' => intval($row['is_priority'] ?? 0),
                    'product_type' => $this->normalizeProductType($row),
                    'product_type_text' => $this->productTypeText($this->normalizeProductType($row)),
                ];
            }, $rows);
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function getAgentGroupIdByPdo(\PDO $pdo, array $config, int $agentId): int
    {
        if ($agentId <= 0) {
            return 0;
        }

        try {
            $table = $config['prefix'] . 'agents';
            $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE 'group_id'");
            $stmt->execute();
            if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
                return 0;
            }

            $stmt = $pdo->prepare("SELECT group_id FROM `{$table}` WHERE id = ? LIMIT 1");
            $stmt->execute([$agentId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return intval($row['group_id'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function applyProductVisibilityFilter($query, int $agentId, string $alias = ''): void
    {
        try {
            if (empty(Db::query("SHOW COLUMNS FROM `product` LIKE 'visible_group_ids'"))) {
                return;
            }
        } catch (\Throwable $e) {
            return;
        }

        $agentGroupId = 0;
        try {
            if (!empty(Db::query("SHOW COLUMNS FROM `agents` LIKE 'group_id'"))) {
                $agentGroupId = (int)(Db::table('agents')->where('id', $agentId)->value('group_id') ?? 0);
            }
        } catch (\Throwable $e) {
            $agentGroupId = 0;
        }

        $field = $alias !== '' ? ($alias . '.visible_group_ids') : 'visible_group_ids';
        if ($agentGroupId > 0) {
            $query->whereRaw("({$field} = '' OR FIND_IN_SET(" . intval($agentGroupId) . ", {$field}))");
        } else {
            $query->where($field, '=', '');
        }
    }

    private function applyShopBlockFilter($query, int $agentId, string $alias = ''): void
    {
        if ($agentId <= 0) {
            return;
        }

        try {
            if (empty(Db::query("SHOW TABLES LIKE 'agent_product_block'"))) {
                return;
            }
        } catch (\Throwable $e) {
            return;
        }

        $field = $alias !== '' ? ($alias . '.id') : 'id';
        $ancestorIds = $this->getAncestorAgentIds($agentId);
        $query->whereRaw("NOT EXISTS (
            SELECT 1 FROM agent_product_block apb
            WHERE apb.product_id = {$field}
            AND apb.agent_id = " . intval($agentId) . "
            AND apb.block_shop = 1
        )");

        if (!empty($ancestorIds)) {
            $ids = implode(',', array_map('intval', $ancestorIds));
            $query->whereRaw("NOT EXISTS (
                SELECT 1 FROM agent_product_block apb2
                WHERE apb2.product_id = {$field}
                AND apb2.agent_id IN ({$ids})
                AND apb2.block_sub_agent = 1
            )");
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

    protected function decorateProducts(array $products, $agentId, array $config, \PDO $pdo, array $hotProductIds, string $shopCode = '')
    {
        if (empty($products)) {
            return [];
        }

        $customImages = ImageTemplateService::getDisplayImageMapForProducts($products);
        $publicLinkService = null;
        if ($shopCode !== '') {
            $publicLinkService = new ShopPublicLinkService();
        }

        foreach ($products as &$product) {
            if (!isset($product['dingxiang'])) {
                $product['dingxiang'] = 0;
            }
            if (!isset($product['call'])) {
                $product['call'] = 0;
            }
            $product['product_type'] = $this->normalizeProductType($product);
            $product['product_type_text'] = $this->productTypeText($product['product_type']);
            $product['card_price_note'] = trim((string)($product['card_price_note'] ?? ''));
            $product['card_price_user_note'] = trim((string)($product['card_price_user_note'] ?? ''));
            $product['card_price_text'] = trim((string)($product['card_price_text'] ?? ''));
            $product['product_image'] = ImageHelper::processProductImage($product['product_image']);
            $product['display_image'] = $customImages[$product['id']] ?? '';
            $product['is_hot'] = in_array($product['id'], $hotProductIds) ? 1 : 0;
            $product['product_tags'] = $this->processProductTags($product['tags'] ?? '');
            if ($publicLinkService) {
                try {
                    $token = $publicLinkService->getProductToken($shopCode, (int)$product['id']);
                    $product['public_product_token'] = $token;
                    $product['public_product_path'] = '/p/' . rawurlencode($token);
                } catch (\Throwable $e) {
                    $product['public_product_token'] = '';
                    $product['public_product_path'] = '';
                }
            }

            if (($product['card_type'] ?? 0) == 1) {
                $totalMarkup = MarkupSettlementService::getTotalMarkupPrice(intval($agentId), intval($product['id'] ?? 0));
                $product['total_price'] = floatval($product['card_price'] ?? 0) + $totalMarkup;
            } else {
                $product['total_price'] = 0;
            }
        }
        unset($product);

        return $products;
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

    protected function applyAgentSort($agentId, array $products)
    {
        $hotIds = [];
        $shopSortIds = [];

        if (empty($products)) {
            return ['products' => $products, 'hot_ids' => $hotIds];
        }

        $sortRecord = Db::table('agent_product_sort')
            ->where('agent_id', $agentId)
            ->find();

        if (empty($sortRecord) || empty($sortRecord['sort_data'])) {
            usort($products, [$this, 'comparePlatformProductSort']);

            return ['products' => $products, 'hot_ids' => $hotIds];
        }

        $sortData = json_decode($sortRecord['sort_data'], true);
        if (is_array($sortData)) {
            if (isset($sortData['hot']) && is_array($sortData['hot'])) {
                $hotIds = array_map('intval', array_filter($sortData['hot']));
            }
            if (isset($sortData['shop_sort']) && is_array($sortData['shop_sort'])) {
                $shopSortIds = array_map('intval', array_filter($sortData['shop_sort']));
            }
        }

        $shopSortMap = array_flip($shopSortIds);
        usort($products, function ($a, $b) use ($hotIds, $shopSortMap) {
            $hotA = in_array($a['id'], $hotIds) ? 1 : 0;
            $hotB = in_array($b['id'], $hotIds) ? 1 : 0;
            if ($hotA !== $hotB) {
                return $hotB - $hotA;
            }

            $hasShopSortA = isset($shopSortMap[$a['id']]);
            $hasShopSortB = isset($shopSortMap[$b['id']]);
            if ($hasShopSortA && $hasShopSortB) {
                return $shopSortMap[$a['id']] - $shopSortMap[$b['id']];
            }
            if ($hasShopSortA) {
                return -1;
            }
            if ($hasShopSortB) {
                return 1;
            }

            return $this->comparePlatformProductSort($a, $b);
        });

        return ['products' => $products, 'hot_ids' => $hotIds];
    }

    protected function comparePlatformProductSort(array $a, array $b): int
    {
        $sortA = isset($a['admin_sort_order']) ? (int)$a['admin_sort_order'] : 0;
        $sortB = isset($b['admin_sort_order']) ? (int)$b['admin_sort_order'] : 0;
        if ($sortA !== $sortB) {
            return $sortB - $sortA;
        }

        return (int)($b['id'] ?? 0) - (int)($a['id'] ?? 0);
    }

    protected function processProductTags($tags)
    {
        $productTags = [];
        if (!empty(trim((string)$tags))) {
            $tagArray = json_decode($tags, true);
            if (!is_array($tagArray)) {
                $tagArray = explode(',', $tags);
            }

            foreach ($tagArray as $tag) {
                $tag = trim((string)$tag);
                if ($tag !== '') {
                    $productTags[] = $tag;
                }
            }
        }

        return $productTags;
    }

    protected function createPdo(array $config)
    {
        $host = (string)($config['hostname'] ?? '');
        $port = (string)($config['hostport'] ?? '');
        $database = (string)($config['database'] ?? '');
        $charset = (string)($config['charset'] ?? 'utf8mb4');
        $username = (string)($config['username'] ?? '');
        $password = (string)($config['password'] ?? '');

        if ($host === '' || $database === '') {
            throw new \RuntimeException('数据库连接参数不完整');
        }

        $portPart = $port !== '' ? ";port={$port}" : '';
        return new \PDO(
            "mysql:host={$host}{$portPart};dbname={$database};charset={$charset}",
            $username,
            $password
        );
    }

    private function getDefaultBannerImages(): array
    {
        return [
            '/static/images/shopimg/banner1.png',
            '/static/images/shopimg/banner2.png',
            '/static/images/shopimg/banner3.png'
        ];
    }

    private function normalizeBannerImages(array $bannerImages): array
    {
        $defaults = $this->getDefaultBannerImages();
        $normalized = [];

        foreach (array_values($bannerImages) as $index => $image) {
            $image = trim((string)$image);
            if ($image === '') {
                continue;
            }

            $path = parse_url($image, PHP_URL_PATH);
            $path = is_string($path) ? $path : $image;
            if ($this->isMissingLocalUpload($path)) {
                $normalized[] = $defaults[$index % count($defaults)];
                continue;
            }

            $normalized[] = $image;
        }

        return !empty($normalized) ? $normalized : $defaults;
    }

    private function isMissingLocalUpload(string $path): bool
    {
        $path = '/' . ltrim($path, '/');
        if (stripos($path, '/uploads/') !== 0) {
            return false;
        }

        $publicPath = app()->getRootPath() . 'public' . str_replace('/', DIRECTORY_SEPARATOR, $path);
        return !is_file($publicPath);
    }

    protected function getDbConfig(): array
    {
        $config = config('database.connections.mysql');

        if (!is_array($config) || empty($config)) {
            $default = (string)config('database.default', 'mysql');
            $defaultConfig = config('database.connections.' . $default);
            if (is_array($defaultConfig) && !empty($defaultConfig)) {
                $config = $defaultConfig;
            }
        }

        if (!is_array($config) || empty($config)) {
            // 兼容旧版单连接结构
            $legacy = config('database');
            if (is_array($legacy) && isset($legacy['hostname'], $legacy['database'])) {
                $config = $legacy;
            }
        }

        if (!is_array($config) || empty($config)) {
            throw new \RuntimeException('读取数据库配置失败');
        }

        $config['prefix'] = (string)($config['prefix'] ?? '');
        return $config;
    }
}
