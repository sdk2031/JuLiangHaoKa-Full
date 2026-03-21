<?php
namespace app\index\controller;

use think\facade\Db;
use think\facade\View;
use think\facade\Log;
use app\common\service\ImageService;
use app\common\service\BlacklistService;
use app\common\helper\ImageHelper;
use app\common\service\ImageTemplateService;

// 引入QRcode库
require_once app()->getRootPath() . 'public/phpqrcode/qrlib.php';

class Shop
{
    /**
     * 店铺首页展示（面向用户）🆕
     */
    public function index($shop_code = '')
    {
        // 优先使用传入的参数（标准格式）
        if (empty($shop_code)) {
            // 尝试从URL路径获取（简化格式）
            $pathInfo = request()->pathinfo();
            $segments = explode('/', $pathInfo);

            // 简化格式：index/shop/店铺代码
            if (count($segments) >= 3 && $segments[0] == 'index' && $segments[1] == 'shop') {
                $shop_code = $segments[2];
            }
        }

        if (empty($shop_code)) {
            return $this->error('店铺不存在');
        }

        // 获取店铺信息
        $config = config('database.connections.mysql');
        $pdo = new \PDO(
            "mysql:host={$config['hostname']};dbname={$config['database']};charset={$config['charset']}", 
            $config['username'], 
            $config['password']
        );
        
        $shopSql = "SELECT s.*, a.username as agent_username FROM {$config['prefix']}agent_shop s 
                   LEFT JOIN {$config['prefix']}agents a ON s.agent_id = a.id 
                   WHERE s.shop_code = ? AND s.status = 1 LIMIT 1";
        $shopStmt = $pdo->prepare($shopSql);
        $shopStmt->execute([$shop_code]);
        $shop = $shopStmt->fetch(\PDO::FETCH_ASSOC);


        if (!$shop) {
            Log::error('店铺不存在', ['shop_code' => $shop_code]);
            return $this->error('店铺不存在或已关闭');
        }

        // 记录访问
        $this->recordVisit($shop, 'shop');

        // 获取产品列表（使用原生SQL，支持加密）
        $config = config('database.connections.mysql');
        $pdo = new \PDO(
            "mysql:host={$config['hostname']};dbname={$config['database']};charset={$config['charset']}", 
            $config['username'], 
            $config['password']
        );
        
        // 获取所有在售产品（包含付费卡字段和排序字段）
        $sql = "SELECT id, name, product_image, guishudi, age, tags, yuezu, flow, yys, selectNumber, kaika, card_type, card_price, admin_sort_order, create_time FROM {$config['prefix']}product WHERE status = 1 ORDER BY CASE WHEN admin_sort_order > 0 THEN admin_sort_order ELSE 999999 END ASC, create_time DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $allProducts = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // 记录总产品数量（用于前端判断是否还有更多数据）
        $totalProductCount = count($allProducts);
        
        // 应用代理的自定义排序，并获取热门产品列表
        $sortResult = $this->applyAgentSort($shop['agent_id'], $allProducts);
        $products = $sortResult['products'];
        $hotProductIds = $sortResult['hot_ids'];
        
        // 限制返回前12个产品
        $products = array_slice($products, 0, 12);

        // 处理产品图片URL、标记热门产品、计算付费卡价格
        if (is_array($products)) {
            $agentId = $shop['agent_id'];
            
            // 批量查询转图后的自定义图片
            $customImages = [];
            $activeTemplate = ImageTemplateService::getActiveTemplate();
            if ($activeTemplate) {
                $pIds = array_column($products, 'id');
                if (!empty($pIds)) {
                    $customImages = Db::name('product_custom_image')
                        ->where('template_id', $activeTemplate['id'])
                        ->whereIn('product_id', $pIds)
                        ->column('image_url', 'product_id');
                }
            }
            
            foreach ($products as &$product) {
                $product['product_image'] = ImageHelper::processProductImage($product['product_image']);
                // 转图后的自定义图片（优先显示）
                $product['display_image'] = $customImages[$product['id']] ?? '';
                // 标记是否为热门产品
                $product['is_hot'] = in_array($product['id'], $hotProductIds) ? 1 : 0;
                
                // 计算付费卡价格
                if (($product['card_type'] ?? 0) == 1) {
                    $cardPrice = floatval($product['card_price'] ?? 0);
                    $totalMarkup = 0;
                    
                    // 获取代理加价
                    $markupSql = "SELECT total_markup_price FROM {$config['prefix']}product_agent_markup WHERE agent_id = ? AND product_id = ? AND status = 1 LIMIT 1";
                    $markupStmt = $pdo->prepare($markupSql);
                    $markupStmt->execute([$agentId, $product['id']]);
                    $markup = $markupStmt->fetch(\PDO::FETCH_ASSOC);
                    if ($markup) {
                        $totalMarkup = floatval($markup['total_markup_price'] ?? 0);
                    }
                    
                    $product['total_price'] = $cardPrice + $totalMarkup;
                } else {
                    $product['total_price'] = 0;
                }
            }
        } else {
            $products = [];
        }

        // 处理Banner图片
        $bannerImages = [];
        if ($shop['banner_images']) {
            $bannerImages = json_decode($shop['banner_images'], true) ?: [];
        }
        // 如果没有Banner图片，使用默认Banner
        if (empty($bannerImages)) {
            $bannerImages = [
                '/static/images/shopimg/banner1.png',
                '/static/images/shopimg/banner2.png',
                '/static/images/shopimg/banner3.png'
            ];
        }

        // 处理Banner链接
        $bannerLinks = [];
        if ($shop['banner_links']) {
            $bannerLinks = json_decode($shop['banner_links'], true) ?: [];
        }

        // 确保链接数组长度与图片数组一致
        while (count($bannerLinks) < count($bannerImages)) {
            $bannerLinks[] = '';
        }

        View::assign([
            'shop' => $shop,
            'products' => $products,
            'totalProductCount' => $totalProductCount,
            'bannerImages' => $bannerImages,
            'bannerLinks' => $bannerLinks,
            'base_url' => request()->domain()
        ]);

        return View::fetch('shop/index');
    }

    /**
     * 产品详情页
     * 访问方式：/index/shop/product/shop_code/店铺代码/product_id/产品ID
     */
    public function product()
    {
        $shop_code = input('shop_code', '');
        $product_id = input('product_id', 0);

        if (empty($shop_code) || empty($product_id)) {
            return $this->error('参数错误');
        }

        // 获取店铺信息
        $shop = Db::table('agent_shop')->where('shop_code', $shop_code)->where('status', 1)->find();
        if (!$shop) {
            return $this->error('店铺不存在');
        }

        // 获取产品信息
        $product = Db::table('product')->where('id', $product_id)->find();
        if (!$product || $product['status'] != 1) {
            // 产品不存在或已下架,显示错误页面并跳转
            View::assign('redirect_url', '/index/shop/index/shop_code/' . $shop_code);
            View::assign('redirect_delay', 2000); // 2秒后跳转
            return $this->error('产品不存在或已下架');
        }

        // 计算付费卡价格（卡费 + 累计加价）
        if (($product['card_type'] ?? 0) == 1) {
            $cardPrice = floatval($product['card_price'] ?? 0);
            $totalMarkup = 0;
            
            // 获取代理的累计加价
            $agentMarkup = Db::name('product_agent_markup')
                ->where('agent_id', $shop['agent_id'])
                ->where('product_id', $product_id)
                ->where('status', 1)
                ->find();
            
            if ($agentMarkup) {
                $totalMarkup = floatval($agentMarkup['total_markup_price'] ?? 0);
            }
            
            $product['total_price'] = $cardPrice + $totalMarkup;
            $product['markup_price'] = $totalMarkup;
        } else {
            $product['total_price'] = 0;
            $product['markup_price'] = 0;
        }

        // 记录商品页面访问
        $this->recordVisit($shop, 'product', $product_id);

        // 处理产品图片URL
        $product['product_image'] = ImageHelper::processProductImage($product['product_image']);
        // 转图后的自定义图片（优先显示）
        $product['display_image'] = ImageTemplateService::getDisplayImage($product);

        // 处理产品详情图片
        $detailImages = ImageHelper::processDetailImages($product['detail_images']);
        
        // 处理产品标签
        $product['processed_tags'] = $this->processProductTags($product['tags'] ?? '');

        // 获取店铺下单验证配置
        $shopOrderVerify = \app\common\helper\SystemConfig::get('shop_order_verify', 'sms');

        // 将API名称转换为数值代码（隐藏真实API名称）
        $apiTypeCode = $this->getApiTypeCode($product['api_name'] ?? '');

        View::assign([
            'shop' => $shop,
            'product' => $product,
            'detailImages' => $detailImages,
            'shopOrderVerify' => $shopOrderVerify,
            'apiTypeCode' => $apiTypeCode  // 传递数值代码而非API名称
        ]);

        return View::fetch('shop/product');
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
        return View::fetch('shop/order_query');
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
        return View::fetch('shop/service');
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
        if (empty($shop_code)) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }

        if (!request()->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }

        // 获取店铺信息
        $shop = Db::table('agent_shop')->where('shop_code', $shop_code)->where('status', 1)->find();
        if (!$shop) {
            return json(['code' => 0, 'msg' => '店铺不存在']);
        }

        // 获取产品信息
        $productId = input('product_id', 0);
        $product = Db::table('product')->where('id', $productId)->where('status', 1)->find();
        if (!$product) {
            return json(['code' => 0, 'msg' => '产品不存在']);
        }

        // 获取订单信息
        // 计算代理实际佣金（考虑API抽佣和上级抽佣）
        $commissionService = new \app\common\service\CommissionCalculationService();
        $actualCommission = $commissionService->calculateTotalDisplayCommission(
            $productId,
            $shop['agent_id'],
            $product['api_name']
        );
        
        // 计算付费卡价格
        $cardType = intval($product['card_type'] ?? 0);
        $cardPrice = floatval($product['card_price'] ?? 0);
        $markupPrice = 0;
        $totalPrice = 0;
        
        if ($cardType == 1) {
            // 获取代理加价
            $agentMarkup = Db::name('product_agent_markup')
                ->where('agent_id', $shop['agent_id'])
                ->where('product_id', $productId)
                ->where('status', 1)
                ->find();
            
            if ($agentMarkup) {
                $markupPrice = floatval($agentMarkup['total_markup_price'] ?? 0);
            }
            
            $totalPrice = $cardPrice + $markupPrice;
        }
        
        $orderData = [
            'shop_code' => $shop_code,
            'agent_id' => $shop['agent_id'],
            'product_id' => $productId,
            'product_name' => $product['name'],
            'product_image' => isset($product['product_image']) ? $product['product_image'] : '',
            'customer_name' => input('customer_name', ''),
            'phone' => input('order_phone', ''), // 修改为order_phone字段
            'idcard' => input('customer_idcard', ''),
            'address' => input('customer_address', ''),
            'remark' => input('remark', ''),
            'commission' => $actualCommission,  // 使用计算后的实际佣金
            'card_type' => $cardType,
            'card_price' => $cardPrice,
            'markup_price' => $markupPrice,
            'total_price' => $totalPrice,
            'pay_status' => $cardType == 1 ? 0 : 0, // 0-未支付/免费
            'order_status' => '0', // 0-已提交
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s')
        ];

        // 处理照片状态和照片数据
        $orderData['photo_status'] = $this->calculatePhotoStatus($product);
        $orderData['id_card_front'] = input('id_card_front', '');
        $orderData['id_card_back'] = input('id_card_back', '');
        $orderData['id_card_face'] = input('id_card_face', '');
        $orderData['id_card_four'] = input('id_card_four', '');

        // 添加省市区字段
        $orderData['province'] = input('province', '');
        $orderData['city'] = input('city', '');
        $orderData['district'] = input('district', '');

        // 计算统计字段：同一姓名、身份证号、手机号的订单数量
        $orderData['name_count'] = Db::name('order')->where('customer_name', $orderData['customer_name'])->count() + 1;
        $orderData['id_card_count'] = Db::name('order')->where('idcard', $orderData['idcard'])->count() + 1;
        $orderData['phone_count'] = Db::name('order')->where('phone', $orderData['phone'])->count() + 1;

        // 调试：记录从前端接收到的所有参数
        error_log('前端提交参数: ' . json_encode(input('post.'), JSON_UNESCAPED_UNICODE));

        // 数据验证
        if (empty($orderData['customer_name'])) {
            return json(['code' => 0, 'msg' => '请输入姓名']);
        }
        if (empty($orderData['phone'])) {
            return json(['code' => 0, 'msg' => '请输入手机号']);
        }
        if (!preg_match('/^1[3-9]\d{9}$/', $orderData['phone'])) {
            return json(['code' => 0, 'msg' => '手机号格式不正确']);
        }
        if (empty($orderData['idcard'])) {
            return json(['code' => 0, 'msg' => '请输入身份证号']);
        }
        if (!preg_match('/^\d{17}[\dXx]$/', $orderData['idcard'])) {
            return json(['code' => 0, 'msg' => '身份证号格式不正确']);
        }
        if (empty($orderData['address'])) {
            return json(['code' => 0, 'msg' => '请输入收货地址']);
        }

        // 黑名单检查
        $blacklistResult = BlacklistService::checkBlacklist($orderData['phone'], $orderData['idcard']);
        if ($blacklistResult['is_blacklisted']) {
            return json(['code' => 0, 'msg' => '系统繁忙，请稍后再试！']);
        }

        try {
            // 检查产品类型和API名称，决定订单创建策略
            $apiName = $product['api_name'] ?? '';
            
            // 付费卡：在主流程中创建订单，等待支付
            if ($cardType == 1 && $totalPrice > 0) {
                // 生成唯一订单号
                $orderData['order_no'] = \app\common\helper\OrderHelper::generateUniqueOrderNo();

                if (!$orderData['order_no']) {
                    return json(['code' => 0, 'msg' => '订单号生成失败，请重试']);
                }

                // 添加API相关字段
                $orderData['api_name'] = $product['api_name'];
                $orderData['up_order_no'] = ''; // 上游订单号，稍后更新
                $orderData['js_type'] = $product['js_type'] ; // 添加js_type字段

                // 添加省市区字段
                $orderData['province'] = input('province', '');
                $orderData['city'] = input('city', '');
                $orderData['district'] = input('district', '');

                // 添加选号字段（如果有）
                $orderData['production_number'] = input('selected_number', '');

                // 过滤掉非标量值（防止空数组导致插入失败）
                foreach ($orderData as $key => $value) {
                    if (is_array($value)) {
                        $orderData[$key] = is_array($value) && empty($value) ? '' : json_encode($value, JSON_UNESCAPED_UNICODE);
                    }
                }
                
                try {
                    // 插入订单到order表
                    $orderId = Db::table('order')->insertGetId($orderData);
                } catch (\Exception $insertEx) {
                    Log::error('订单插入异常: ' . $insertEx->getMessage());
                    throw $insertEx;
                }

                if ($orderId) {
                    // 生成订单快照（锁定代理链）
                    if (!empty($orderData['agent_id'])) {
                        \app\common\service\CommissionCalculationService::generateOrderSnapshot($orderId, $orderData['agent_id']);
                        
                        // 记录溢价链信息
                        \app\common\service\MarkupSettlementService::recordMarkupChain($orderId, $orderData['product_id']);
                    }
                    
                    // 更新店铺订单统计
                    Db::table('agent_shop')->where('id', $shop['id'])->inc('total_orders');
                    Db::table('agent_shop')->where('id', $shop['id'])->inc('today_orders');
                    
                    // 更新代理订单统计
                    if (!empty($orderData['agent_id'])) {
                        \app\common\helper\AgentStatsHelper::incrementOrderStats($orderData['agent_id']);
                    }

                    // 付费卡订单，等待支付
                    Db::table('order')
                        ->where('id', $orderId)
                        ->update([
                            'order_status' => 'pending_pay', // 待支付状态
                            'update_time' => date('Y-m-d H:i:s')
                        ]);
                    
                    return json([
                        'code' => 1, 
                        'msg' => '订单创建成功，请完成支付', 
                        'data' => [
                            'order_no' => $orderData['order_no'],
                            'need_pay' => true,
                            'total_price' => $totalPrice
                        ]
                    ]);
                } else {
                    return json(['code' => 0, 'msg' => '订单保存失败']);
                }
            }
            
            // 免费卡的自营产品：在主流程中创建订单
            elseif ($apiName === '自营' || empty($apiName)) {
                // 生成唯一订单号
                $orderData['order_no'] = \app\common\helper\OrderHelper::generateUniqueOrderNo();

                if (!$orderData['order_no']) {
                    return json(['code' => 0, 'msg' => '订单号生成失败，请重试']);
                }

                // 添加API相关字段
                $orderData['api_name'] = '自营';
                $orderData['up_order_no'] = $orderData['order_no']; // 自营订单使用本地订单号
                $orderData['js_type'] = $product['js_type'] ?? 1; // 添加js_type字段

                // 添加省市区字段
                $orderData['province'] = input('province', '');
                $orderData['city'] = input('city', '');
                $orderData['district'] = input('district', '');

                // 添加选号字段（如果有）
                $orderData['production_number'] = input('selected_number', '');

                // 过滤掉非标量值（防止空数组导致插入失败）
                foreach ($orderData as $key => $value) {
                    if (is_array($value)) {
                        $orderData[$key] = is_array($value) && empty($value) ? '' : json_encode($value, JSON_UNESCAPED_UNICODE);
                    }
                }
                
                try {
                    // 插入订单到order表
                    $orderId = Db::table('order')->insertGetId($orderData);
                } catch (\Exception $insertEx) {
                    Log::error('订单插入异常: ' . $insertEx->getMessage());
                    throw $insertEx;
                }

                if ($orderId) {
                    // 生成订单快照（锁定代理链）
                    if (!empty($orderData['agent_id'])) {
                        \app\common\service\CommissionCalculationService::generateOrderSnapshot($orderId, $orderData['agent_id']);
                    }
                    
                    // 更新店铺订单统计
                    Db::table('agent_shop')->where('id', $shop['id'])->inc('total_orders');
                    Db::table('agent_shop')->where('id', $shop['id'])->inc('today_orders');
                    
                    // 更新代理订单统计
                    if (!empty($orderData['agent_id'])) {
                        \app\common\helper\AgentStatsHelper::incrementOrderStats($orderData['agent_id']);
                    }

                    // 如果有选号，将号码标记为已使用
                    if (!empty($orderData['production_number'])) {
                        $this->markNumberAsUsed($orderData['production_number'], $orderId, $orderData['agent_id']);
                    }

                    // 自营订单：直接更新为已提交状态
                    Db::table('order')
                        ->where('id', $orderId)
                        ->update([
                            'order_status' => '0', // 0-已提交
                            'update_time' => date('Y-m-d H:i:s')
                        ]);
                    
                    return json([
                        'code' => 1, 
                        'msg' => '订单提交成功', 
                        'data' => [
                            'order_no' => $orderData['order_no'],
                            'need_payment' => false
                        ]
                    ]);
                } else {
                    return json(['code' => 0, 'msg' => '订单保存失败']);
                }
            }
            
            // 免费卡的第三方API产品：不在主流程中创建订单，直接调用API处理器
            else {
                // 第三方API：直接调用proxySubmitOrder，让API处理器自行创建订单
                try {
                    $response = $this->proxySubmitOrder();
                    $result = $response->getData();
                    
                    if ($result && isset($result['code']) && $result['code'] == 1) {
                        return json([
                            'code' => 1, 
                            'msg' => '订单提交成功', 
                            'data' => [
                                'order_no' => $result['data']['order_no'] ?? '',
                                'need_payment' => false
                            ]
                        ]);
                    } else {
                        // API调用失败
                        $errorMsg = $result['msg'] ?? '上游API调用失败';
                        return json(['code' => 0, 'msg' => '订单提交失败：' . $errorMsg]);
                    }
                } catch (\Exception $e) {
                    return json(['code' => 0, 'msg' => '订单提交失败：' . $e->getMessage()]);
                }
            }

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
        if (empty($shop_code)) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }

        // 获取店铺信息
        $shop = Db::table('agent_shop')->where('shop_code', $shop_code)->where('status', 1)->find();
        if (!$shop) {
            return json(['code' => 0, 'msg' => '店铺不存在']);
        }

        // 获取产品信息
        $productId = input('product_id', 0);
        $product = Db::table('product')->where('id', $productId)->where('status', 1)->find();
        if (!$product) {
            return json(['code' => 0, 'msg' => '产品不存在']);
        }

        // 数据验证（与免费卡保持一致）
        $customerName = input('customer_name', '');
        $orderPhone = input('order_phone', '');
        $customerIdcard = input('customer_idcard', '');
        $customerAddress = input('customer_address', '');
        
        if (empty($customerName)) {
            return json(['code' => 0, 'msg' => '请输入姓名']);
        }
        if (empty($orderPhone)) {
            return json(['code' => 0, 'msg' => '请输入手机号']);
        }
        if (!preg_match('/^1[3-9]\d{9}$/', $orderPhone)) {
            return json(['code' => 0, 'msg' => '手机号格式不正确']);
        }
        if (empty($customerIdcard)) {
            return json(['code' => 0, 'msg' => '请输入身份证号']);
        }
        if (!preg_match('/^\d{17}[\dXx]$/', $customerIdcard)) {
            return json(['code' => 0, 'msg' => '身份证号格式不正确']);
        }
        if (empty($customerAddress)) {
            return json(['code' => 0, 'msg' => '请输入收货地址']);
        }

        // 黑名单检查
        $blacklistResult = BlacklistService::checkBlacklist($orderPhone, $customerIdcard);
        if ($blacklistResult['is_blacklisted']) {
            return json(['code' => 0, 'msg' => '系统繁忙，请稍后再试！']);
        }

        try {
            // 计算付费卡价格
            $cardType = intval($product['card_type'] ?? 0);
            $cardPrice = floatval($product['card_price'] ?? 0);
            $markupPrice = 0;
            $totalPrice = 0;
            
            if ($cardType == 1) {
                // 获取代理加价
                $agentMarkup = Db::name('product_agent_markup')
                    ->where('agent_id', $shop['agent_id'])
                    ->where('product_id', $productId)
                    ->where('status', 1)
                    ->find();
                
                if ($agentMarkup) {
                    $markupPrice = floatval($agentMarkup['total_markup_price'] ?? 0);
                }
                
                $totalPrice = $cardPrice + $markupPrice;
            }

            if ($cardType == 1 && $totalPrice > 0) {
                // 付费卡：暂存订单数据，不立即创建订单记录
                $tempOrderData = $this->preparePaidCardOrderData($shop, $product, $totalPrice);
                
                if ($tempOrderData['success']) {
                    // 简化流程：暂存订单数据，支付成功后才创建订单记录
                    return json([
                        'code' => 1,
                        'msg' => '订单数据准备完成',
                        'data' => [
                            'temp_order_no' => $tempOrderData['temp_order_no'],
                            'need_payment' => true,
                            'total_price' => $totalPrice
                        ]
                    ]);
                } else {
                    return json(['code' => 0, 'msg' => '订单数据准备失败']);
                }
            } else {
                // 免费卡调用API代理，根据产品API类型路由到正确的控制器
                $result = $this->proxySubmitOrder();
                
                // 统一处理返回格式，确保前端能正确解析
                if ($result instanceof \think\response\Json) {
                    $data = $result->getData();
                    
                    // 记录调试日志
                    Log::info('proxySubmitOrder返回数据', [
                        'data' => $data,
                        'api_name' => $product['api_name'] ?? ''
                    ]);
                    
                    // 判断是否成功：code=0（龙宝等）或 code=1（其他API）都可能表示成功
                    $isSuccess = false;
                    if (isset($data['code'])) {
                        // code=0 且有 data 且 data 不为空数组 表示成功（龙宝API）
                        // 但需要排除 status=fail 的情况（172号卡等API的失败响应）
                        if ($data['code'] === 0 && isset($data['data']) && !empty($data['data'])) {
                            // 检查是否有status字段且为fail
                            $status = isset($data['data']['status']) ? $data['data']['status'] : '';
                            if ($status !== 'fail' && $status !== 'error') {
                                $isSuccess = true;
                            }
                        }
                        // code=1 表示成功（其他API）
                        if ($data['code'] === 1) {
                            $isSuccess = true;
                        }
                    }
                    
                    Log::info('订单提交结果判断', [
                        'isSuccess' => $isSuccess,
                        'code' => $data['code'] ?? null,
                        'hasData' => isset($data['data']),
                        'dataEmpty' => empty($data['data'])
                    ]);
                    
                    if ($isSuccess && isset($data['data'])) {
                        // 统一订单号字段名：优先使用order_no，其次local_order_sn
                        $orderNo = $data['data']['order_no'] ?? $data['data']['local_order_sn'] ?? '';
                        
                        Log::info('提取订单号', [
                            'orderNo' => $orderNo,
                            'data_order_no' => $data['data']['order_no'] ?? 'not set',
                            'data_local_order_sn' => $data['data']['local_order_sn'] ?? 'not set'
                        ]);
                        
                        if (empty($orderNo)) {
                            Log::error('订单号为空', ['data' => $data]);
                        }
                        
                        return json([
                            'code' => 1,
                            'msg' => $data['msg'] ?? '订单提交成功',
                            'data' => [
                                'order_no' => $orderNo,
                                'need_payment' => false
                            ]
                        ]);
                    } else {
                        // 订单提交失败，返回错误信息
                        $errorMsg = $data['msg'] ?? $data['message'] ?? '订单提交失败';
                        Log::error('订单提交失败', ['errorMsg' => $errorMsg, 'data' => $data]);
                        return json(['code' => 0, 'msg' => $errorMsg]);
                    }
                } else {
                    // 如果不是Json响应，记录日志
                    Log::error('proxySubmitOrder返回非Json响应', ['type' => gettype($result)]);
                    return json(['code' => 0, 'msg' => '订单提交失败：响应格式错误']);
                }
            }

        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '提交失败：' . $e->getMessage()]);
        }
    }

    /**
     * 准备付费卡订单数据（暂存到temp_orders表，不创建正式订单记录）
     */
    private function preparePaidCardOrderData($shop, $product, $totalPrice)
    {
        try {
            // 计算代理实际佣金
            $commissionService = new \app\common\service\CommissionCalculationService();
            $actualCommission = $commissionService->calculateTotalDisplayCommission(
                floatval($product['commission']),  // 使用产品的佣金字段，而不是产品ID
                $shop['agent_id'],
                $product['id']  
            );
            

            $orderPrefix = '';
            $configResult = Db::table('system_config')->where('config_key', 'order_prefix')->find();
            if ($configResult) {
                $orderPrefix = $configResult['config_value'];
            }
            if (empty($orderPrefix)) {
                $orderPrefix = 'HK';
            }
            
            do {
                $tempOrderNo = $orderPrefix . date('YmdHis') . rand(1000, 9999);
                $exists = Db::table('order')->where('order_no', $tempOrderNo)->find();
            } while ($exists);
            
            // 准备订单数据
            $orderData = [
                'shop_code' => $shop['shop_code'],
                'agent_id' => $shop['agent_id'],
                'product_id' => $product['id'],
                'product_name' => $product['name'],
                'product_image' => isset($product['product_image']) ? $product['product_image'] : '',
                'customer_name' => input('customer_name', ''),
                'phone' => input('order_phone', ''),
                'idcard' => input('customer_idcard', ''),
                'address' => input('customer_address', ''),
                'remark' => input('remark', ''),
                'commission' => $actualCommission,
                'card_type' => 1,
                'card_price' => floatval($product['card_price'] ?? 0),
                'markup_price' => $totalPrice - floatval($product['card_price'] ?? 0),
                'total_price' => $totalPrice,
                'photo_status' => $this->calculatePhotoStatus($product),
                'js_type' => $product['js_type'] , // 从产品中读取结算模式
                'create_time' => date('Y-m-d H:i:s')
            ];

            // 处理选号数据
            $orderData['production_number'] = input('selected_number', '');

            // 处理照片数据
            $this->addPhotoDataToOrder($orderData);

            // 处理地址数据
            $this->addAddressDataToOrder($orderData);
            
            // 直接存储到数据库
            Db::table('temp_orders')->insert([
                'temp_order_no' => $tempOrderNo,
                'order_data' => json_encode($orderData),
                'create_time' => time(),
                'expire_time' => time() + 3600 // 1小时过期
            ]);
            
            return [
                'success' => true,
                'temp_order_no' => $tempOrderNo
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '订单数据准备失败：' . $e->getMessage()
            ];
        }
    }


    /**
     * 处理照片数据
     */
    private function addPhotoDataToOrder(&$orderData)
    {
        // 处理身份证正面
        if (request()->has('id_card_front')) {
            $orderData['id_card_front'] = input('id_card_front', '');
        }
        
        // 处理身份证反面
        if (request()->has('id_card_back')) {
            $orderData['id_card_back'] = input('id_card_back', '');
        }
        
        // 处理人脸照片
        if (request()->has('id_card_face')) {
            $orderData['id_card_face'] = input('id_card_face', '');
        }
        
        // 处理四要素照片
        if (request()->has('id_card_four')) {
            $orderData['id_card_four'] = input('id_card_four', '');
        }
    }

    /**
     * 处理地址数据
     */
    private function addAddressDataToOrder(&$orderData)
    {
        // 处理省市区
        $orderData['province'] = input('province', '');
        $orderData['city'] = input('city', '');
        $orderData['district'] = input('district', '');
        
        // 获取详细地址（用户的字段名是customer_address）
        $detailedAddress = input('customer_address', '');
        $orderData['detailed_address'] = $detailedAddress; // 保存详细地址字段
        
        
        // 如果有详细地址，组合完整地址；否则只存储详细地址部分
        if (!empty($detailedAddress)) {
            $fullAddress = trim($orderData['province'] . ' ' . $orderData['city'] . ' ' . $orderData['district'] . ' ' . $detailedAddress);
            $orderData['address'] = $fullAddress;
        } else {
            // 如果没有详细地址，address字段存储空值，保持省市区字段独立
            $orderData['address'] = '';
        }
    }

  

    /**
     * 提交订单到上游API（公开方法，供支付回调调用）
     */
    public function submitToUpstreamPublic($orderId, $orderData, $product)
    {
        return $this->submitToUpstream($orderId, $orderData, $product);
    }
    
    /**
     * 提交订单到上游API
     */
    private function submitToUpstream($orderId, $orderData, $product)
    {
        $apiName = $product['api_name'];

        // 判断是否为自营产品
        if ($apiName === '自营' || empty($apiName)) {
            // 自营产品：直接保存到本地，不需要调用第三方API
            Db::table('order')
                ->where('id', $orderId)
                ->update([
                    'order_status' => '0', // 0-已提交（自营订单状态）
                    'up_order_no' => $orderData['order_no'], // 自营订单使用本地订单号
                    'api_name' => '自营', // 确保标记为自营
                    'update_time' => date('Y-m-d H:i:s')
                ]);

            // 记录日志
            error_log('[自营订单] 订单ID: ' . $orderId . ', 订单号: ' . $orderData['order_no'] . ' 已保存到本地');

            return ['success' => true, 'message' => '自营订单提交成功，已保存到本地'];
        } else {
            // 第三方API产品：调用现有的代理提交方法
            error_log('[第三方API订单] 订单ID: ' . $orderId . ', API: ' . $apiName . ', 开始调用上游接口');

            try {
                // 设置产品ID到请求中，供proxySubmitOrder使用
                request()->withInput(['product_id' => $product['id']]);
                
                // 调用现有的代理提交方法
                $response = $this->proxySubmitOrder();
                $result = $response->getData();
                
                if ($result && isset($result['code']) && $result['code'] == 1) {
                    // API调用成功，更新订单状态
                    $updateData = [
                        'order_status' => '1', // 1-处理中
                        'up_order_no' => $result['data']['order_no'] ?? $result['data']['up_order_no'] ?? $orderData['order_no'],
                        'update_time' => date('Y-m-d H:i:s')
                    ];
                    
                    Db::table('order')->where('id', $orderId)->update($updateData);
                    
                    error_log('[第三方API订单] 订单ID: ' . $orderId . ', 上游订单号: ' . ($updateData['up_order_no'] ?? '无'));
                    
                    return ['success' => true, 'message' => 'API订单提交成功'];
                } else {
                    // API调用失败
                    $errorMsg = $result['msg'] ?? '上游API调用失败';
                    error_log('[第三方API订单] 订单ID: ' . $orderId . ', API调用失败: ' . $errorMsg);
                    
                    return ['success' => false, 'message' => $errorMsg];
                }
            } catch (\Exception $e) {
                error_log('[第三方API订单] 订单ID: ' . $orderId . ', API调用异常: ' . $e->getMessage());
                return ['success' => false, 'message' => 'API调用异常: ' . $e->getMessage()];
            }
        }
    }

    /**
     * 计算照片状态
     */
    private function calculatePhotoStatus($product)
    {
        // 如果产品不需要上传身份证照片
        if (empty($product['is_id_photo']) || $product['is_id_photo'] == 0) {
            return '0'; // 无需上传
        }

        // 产品需要上传身份证照片，检查是否已上传
        $idCardFront = input('id_card_front', '');
        $idCardBack = input('id_card_back', '');
        $idCardFace = input('id_card_face', '');
        $idCardFour = input('id_card_four', '');

        // 检查是否有任何照片内容
        $hasPhotos = !empty($idCardFront) || !empty($idCardBack) || !empty($idCardFace) || !empty($idCardFour);

        if ($hasPhotos) {
            return '2'; // 已上传
        } else {
            return '1'; // 需要上传但未上传
        }
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
            $type = input('post.type', ''); // 照片类型：id_card_front, id_card_back, id_card_face, id_card_four
            $file = request()->file('file'); // 参考admin上传，使用file字段

            if (empty($type) || empty($file)) {
                return json(['code' => 0, 'msg' => '请选择要上传的文件']);
            }

            // 验证照片类型
            $allowedTypes = ['id_card_front', 'id_card_back', 'id_card_face', 'id_card_four'];
            if (!in_array($type, $allowedTypes)) {
                return json(['code' => 0, 'msg' => '不支持的照片类型']);
            }

            // 验证文件类型
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $extension = strtolower($file->getOriginalExtension());
            if (!in_array($extension, $allowedExtensions)) {
                return json(['code' => 0, 'msg' => '只允许上传图片文件']);
            }

            // 验证文件大小（最大5MB）
            if ($file->getSize() > 5 * 1024 * 1024) {
                return json(['code' => 0, 'msg' => '文件大小不能超过5MB']);
            }

            // 使用通用上传服务，但指定前端专用路径
            $uploadService = new \app\common\service\UploadService();
            
            // 保存文件信息（在移动文件前）
            $originalName = $file->getOriginalName();
            $fileSize = $file->getSize();
            $fileExtension = $file->getOriginalExtension();

            // 生成临时文件路径
            $tempDir = runtime_path() . 'temp/';
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            $tempName = uniqid() . '_' . $type . '.' . $fileExtension;
            $tempPath = $tempDir . $tempName;
            
            $file->move($tempDir, $tempName);

            try {
                // 使用前端专用路径：shop_uploads/idcard
                $uploadPath = 'shop_uploads/idcard';
                $result = $uploadService->getOssService()->upload($tempPath, $uploadPath);

                // 清理临时文件
                if (file_exists($tempPath)) {
                    unlink($tempPath);
                }

                return json([
                    'code' => 1,
                    'msg' => '上传成功',
                    'data' => [
                        'url' => $result['url'],
                        'path' => $result['path'],
                        'provider' => $result['provider'],
                        'type' => $type,
                        'original_name' => $originalName,
                        'size' => $fileSize,
                        'extension' => $fileExtension
                    ]
                ]);
                
            } catch (\Exception $e) {
                // 清理临时文件
                if (file_exists($tempPath)) {
                    unlink($tempPath);
                }
                throw $e;
            }

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

        if (empty($orderId) || empty($orderNo)) {
            $this->error('链接参数错误');
        }

        // 查询订单信息并关联产品信息
        $order = Db::name('order')
            ->alias('o')
            ->join('product p', 'o.product_id = p.id')
            ->where('o.id', $orderId)
            ->where('o.order_no', $orderNo)
            ->field('o.*, p.is_four_photo, p.four_photo_title, p.four_photo')
            ->find();

        if (!$order) {
            $this->error('订单不存在');
        }

        // 强拦截模式：
        // 1) photo_status=4 时，链接直接失效
        // 2) 仅允许待传照片状态(3)进入页面
        if ((string)($order['photo_status'] ?? '') === '4') {
            $this->error('该订单证件照片已补传完成，链接已失效');
        }
        if ((string)($order['order_status'] ?? '') !== '3') {
            $this->error('当前订单状态无需补传证件照片');
        }

        // 判断是否需要四证
        $isFourPhoto = !empty($order['is_four_photo']) && $order['is_four_photo'] == 1;
        $fourPhotoTitle = !empty($order['four_photo_title']) ? (string)$order['four_photo_title'] : '一证通查';
        $fourPhotoQueryUrl = !empty($order['four_photo']) ? (string)$order['four_photo'] : 'https://getsimnum.caict.ac.cn/';
        $customerName = trim((string)($order['customer_name'] ?? ''));
        $phone = trim((string)($order['phone'] ?? ''));

        $maskedName = '-';
        if ($customerName !== '') {
            $nameLen = mb_strlen($customerName, 'UTF-8');
            if ($nameLen <= 1) {
                $maskedName = '*';
            } else {
                $maskedName = mb_substr($customerName, 0, 1, 'UTF-8') . str_repeat('*', max($nameLen - 1, 1));
            }
        }

        $maskedPhone = '-';
        if ($phone !== '') {
            if (preg_match('/^1[3-9]\d{9}$/', $phone)) {
                $maskedPhone = substr($phone, 0, 3) . '****' . substr($phone, -4);
            } elseif (strlen($phone) > 4) {
                $maskedPhone = substr($phone, 0, 2) . str_repeat('*', max(strlen($phone) - 4, 2)) . substr($phone, -2);
            } else {
                $maskedPhone = str_repeat('*', strlen($phone));
            }
        }

        View::assign([
            'order_id' => $orderId,
            'order_no' => $orderNo,
            'is_four_photo' => $isFourPhoto,
            'four_photo_title' => $fourPhotoTitle,
            'four_photo_query_url' => $fourPhotoQueryUrl,
            'masked_name' => $maskedName,
            'masked_phone' => $maskedPhone
        ]);

        return view('upload/photos');
    }

    /**
     * 验证订单信息（用于上传照片前的验证）
     */
    public function verifyOrderForUpload()
    {
        $params = json_decode(file_get_contents('php://input'), true);

        $orderId = $params['order_id'] ?? '';
        $orderNo = $params['order_no'] ?? '';
        $phone = $params['phone'] ?? '';
        $idcardLast4 = $params['idcard_last4'] ?? '';

        if (empty($orderId) || empty($orderNo) || empty($phone) || empty($idcardLast4)) {
            return json(['code' => 0, 'msg' => '参数不完整']);
        }

        try {
            // 查找订单
            $order = Db::name('order')
                ->alias('o')
                ->leftJoin('product p', 'o.product_id = p.id')
                ->field('o.*, p.is_four_photo, p.four_photo_title, p.four_photo')
                ->where('o.id', $orderId)
                ->where('o.order_no', $orderNo)
                ->find();

            if (!$order) {
                return json(['code' => 0, 'msg' => '订单不存在']);
            }

            // 二次校验：防止已完成补传后继续通过旧链接重复进入
            if ((string)($order['photo_status'] ?? '') === '4') {
                return json(['code' => 0, 'msg' => '该订单证件照片已补传完成，链接已失效']);
            }
            if ((string)($order['order_status'] ?? '') !== '3') {
                return json(['code' => 0, 'msg' => '当前订单状态无需补传证件照片']);
            }

            // 验证手机号
            if ($order['phone'] !== $phone) {
                return json(['code' => 0, 'msg' => '手机号不匹配']);
            }

            // 验证身份证后4位
            if (strlen($order['idcard']) >= 4) {
                $orderIdcardLast4 = strtoupper(substr($order['idcard'], -4));
                if ($orderIdcardLast4 !== strtoupper($idcardLast4)) {
                    return json(['code' => 0, 'msg' => '身份证后4位不匹配']);
                }
            } else {
                return json(['code' => 0, 'msg' => '订单身份证信息不完整']);
            }

            // 验证通过
            $customerName = trim((string)($order['customer_name'] ?? ''));
            $orderPhone = trim((string)($order['phone'] ?? ''));

            $maskedName = '-';
            if ($customerName !== '') {
                $nameLen = mb_strlen($customerName, 'UTF-8');
                if ($nameLen <= 1) {
                    $maskedName = '*';
                } else {
                    $maskedName = mb_substr($customerName, 0, 1, 'UTF-8') . str_repeat('*', max($nameLen - 1, 1));
                }
            }

            $maskedPhone = '-';
            if ($orderPhone !== '') {
                if (preg_match('/^1[3-9]\d{9}$/', $orderPhone)) {
                    $maskedPhone = substr($orderPhone, 0, 3) . '****' . substr($orderPhone, -4);
                } elseif (strlen($orderPhone) > 4) {
                    $maskedPhone = substr($orderPhone, 0, 2) . str_repeat('*', max(strlen($orderPhone) - 4, 2)) . substr($orderPhone, -2);
                } else {
                    $maskedPhone = str_repeat('*', strlen($orderPhone));
                }
            }

            return json([
                'code' => 1,
                'msg' => '验证成功',
                'data' => [
                    'order_id' => $order['id'],
                    'order_no' => $order['order_no'],
                    'customer_name' => $order['customer_name'],
                    'masked_name' => $maskedName,
                    'masked_phone' => $maskedPhone,
                    'is_four_photo' => (!empty($order['is_four_photo']) && intval($order['is_four_photo']) === 1) ? 1 : 0,
                    'four_photo_title' => !empty($order['four_photo_title']) ? (string)$order['four_photo_title'] : '一证通查',
                    'four_photo_query_url' => !empty($order['four_photo']) ? (string)$order['four_photo'] : 'https://getsimnum.caict.ac.cn/'
                ]
            ]);

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

            $orderId = $params['order_id'] ?? '';
            $photoType = $params['photo_type'] ?? '';
            $photoData = $params['photo_data'] ?? '';

            if (empty($orderId) || empty($photoType) || empty($photoData)) {
                return json(['code' => 1, 'msg' => '参数不完整']);
            }

            // 查询订单
            $order = Db::name('order')
                ->where('id', $orderId)
                ->field('id, order_no, api_name, id_card_front, id_card_back, id_card_face, id_card_four, up_order_no')
                ->find();

            if (!$order) {
                return json(['code' => 1, 'msg' => '订单不存在']);
            }

            $apiName = $order['api_name'] ?? '';

            // 解析照片数据
            $photoDataArray = json_decode($photoData, true);
            if (!$photoDataArray && $photoType === 'all') {
                return json(['code' => 1, 'msg' => '照片数据格式错误']);
            }

            // 根据API类型分发处理
            switch ($apiName) {
                case '号卡极团':
                    $result = $this->reuploadForHaoteam($order, $photoType, $photoData, $photoDataArray);
                    break;

                case '卡业联盟':
                    $result = $this->reuploadForHaoky($order, $photoType, $photoData, $photoDataArray);
                    break;

                default:
                    // 自营及其他：直接更新订单表照片字段
                    $result = $this->reuploadForLocal($order, $photoType, $photoData, $photoDataArray);
                    break;
            }

            // 统一处理成功后的本地状态：补传完成 -> 已提交(0)，并写入照片字段，防止链接重复使用
            $resultData = method_exists($result, 'getData') ? $result->getData() : $result;
            if (($resultData['code'] ?? 1) == 0) {
                $this->markOrderPhotoUploaded($order['id'], $photoType, $photoData, $photoDataArray);
            }

            return method_exists($result, 'getData') ? json($resultData) : $result;

        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '照片上传失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 统一落库补传成功结果：
     * - photo_status=4（已上传）
     * - order_status=0（已提交）
     * - 写入证件照片地址（尽可能）
     */
    private function markOrderPhotoUploaded($orderId, $photoType, $photoData, $photoDataArray)
    {
        $order = Db::name('order')
            ->where('id', $orderId)
            ->field('id,agent_id,id_card_front,id_card_back,id_card_face,id_card_four')
            ->find();

        // 先备份旧照片到历史表（不影响主流程）
        if ($order) {
            $this->backupOrderPhotoHistory($order, $photoDataArray);
        }

        $updateData = [
            'photo_status' => '4',
            'order_status' => '0',
            'update_time' => date('Y-m-d H:i:s')
        ];

        if ($photoType === 'all' && is_array($photoDataArray)) {
            if (!empty($photoDataArray['front'])) $updateData['id_card_front'] = $photoDataArray['front'];
            if (!empty($photoDataArray['back'])) $updateData['id_card_back'] = $photoDataArray['back'];
            if (!empty($photoDataArray['face'])) $updateData['id_card_face'] = $photoDataArray['face'];
            if (!empty($photoDataArray['four'])) $updateData['id_card_four'] = $photoDataArray['four'];
        } else {
            switch ($photoType) {
                case 'front':
                case 'face':
                    $updateData['id_card_front'] = $photoData;
                    break;
                case 'back':
                    $updateData['id_card_back'] = $photoData;
                    break;
                case 'hand':
                    $updateData['id_card_face'] = $photoData;
                    break;
                case 'four':
                    $updateData['id_card_four'] = $photoData;
                    break;
            }
        }

        Db::name('order')->where('id', $orderId)->update($updateData);
    }

    /**
     * 备份旧证件照片到历史表
     */
    private function backupOrderPhotoHistory($order, $photoDataArray = [])
    {
        try {
            // 检查历史表是否存在
            $tableExists = Db::query("SHOW TABLES LIKE 'order_photo_history'");
            if (empty($tableExists)) {
                return;
            }

            $photoFields = ['id_card_front', 'id_card_back', 'id_card_face', 'id_card_four'];
            $photoPaths = [];
            foreach ($photoFields as $field) {
                if (!empty($order[$field])) {
                    $photoPaths[$field] = [
                        'old' => $order[$field],
                        'new' => is_array($photoDataArray) ? ($photoDataArray[str_replace('id_card_', '', $field)] ?? '') : ''
                    ];
                }
            }

            if (empty($photoPaths)) {
                return;
            }

            $historyData = [
                'order_id' => $order['id'],
                'photo_type' => 'batch',
                'photo_paths' => json_encode($photoPaths, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'batch_id' => 'batch_' . $order['id'] . '_' . date('YmdHis'),
                'agent_id' => $order['agent_id'] ?? 0,
                'ip_address' => request()->ip(),
                'upload_type' => 'replaced',
                'status' => 'replaced'
            ];

            Db::name('order_photo_history')->insert($historyData);
        } catch (\Exception $e) {
            // 历史备份失败不影响主流程
            \think\facade\Log::warning('[Shop重传照片] 备份历史照片失败: ' . $e->getMessage());
        }
    }

    /**
     * 号卡极团照片重传
     */
    private function reuploadForHaoteam($order, $photoType, $photoData, $photoDataArray)
    {
        $orderController = new \app\api\controller\kapi\haoteam\Order();

        if ($photoType === 'all' && $photoDataArray) {
            $photoTypes = [
                'front' => 'face',
                'back' => 'back',
                'face' => 'hand',
                'four' => 'four'
            ];

            $successCount = 0;
            $errors = [];

            foreach ($photoTypes as $dataKey => $apiType) {
                if (!empty($photoDataArray[$dataKey])) {
                    $_POST['order_id'] = $order['id'];
                    $_POST['photo_type'] = $apiType;
                    $_POST['photo_data'] = $photoDataArray[$dataKey];

                    $result = $orderController->reuploadPhoto();
                    $resultData = method_exists($result, 'getData') ? $result->getData() : $result;

                    if (($resultData['code'] ?? 1) == 0) {
                        $successCount++;
                    } else {
                        $errors[] = $apiType . ': ' . ($resultData['msg'] ?? '上传失败');
                    }
                }
            }

            if ($successCount > 0 && empty($errors)) {
                return json(['code' => 0, 'msg' => '所有照片上传成功']);
            } elseif ($successCount > 0) {
                return json(['code' => 0, 'msg' => "部分照片上传成功({$successCount}张)，失败: " . implode(', ', $errors)]);
            } else {
                return json(['code' => 1, 'msg' => '照片上传失败: ' . implode(', ', $errors)]);
            }
        }

        $_POST['order_id'] = $order['id'];
        $_POST['photo_type'] = $photoType;
        $_POST['photo_data'] = $photoData;
        $result = $orderController->reuploadPhoto();
        return method_exists($result, 'getData') ? json($result->getData()) : $result;
    }

    /**
     * 卡业联盟照片重传
     */
    private function reuploadForHaoky($order, $photoType, $photoData, $photoDataArray)
    {
        $orderController = new \app\api\controller\kapi\haoky\Order();
        $orderNo = $order['up_order_no'] ?: $order['order_no'];

        if ($photoType === 'all' && $photoDataArray) {
            $_POST['orderNumber'] = $orderNo;
            $_POST['picFaceUrl'] = $photoDataArray['front'] ?? '';
            $_POST['picBackUrl'] = $photoDataArray['back'] ?? '';
            $_POST['picHandUrl'] = $photoDataArray['face'] ?? '';
            if (!empty($photoDataArray['four'])) {
                $_POST['fourPhotos'] = $photoDataArray['four'];
            }
        } else {
            $_POST['orderNumber'] = $orderNo;
            $_POST['picFaceUrl'] = '';
            $_POST['picBackUrl'] = '';
            $_POST['picHandUrl'] = '';
            switch ($photoType) {
                case 'front': case 'face': $_POST['picFaceUrl'] = $photoData; break;
                case 'back': $_POST['picBackUrl'] = $photoData; break;
                case 'hand': $_POST['picHandUrl'] = $photoData; break;
                case 'four': $_POST['fourPhotos'] = $photoData; break;
            }
        }

        $result = $orderController->resendThreePhotos();
        return method_exists($result, 'getData') ? json($result->getData()) : $result;
    }

    /**
     * 自营/默认照片更新（直接写入订单表）
     */
    private function reuploadForLocal($order, $photoType, $photoData, $photoDataArray)
    {
        if ($photoType === 'all' && $photoDataArray) {
            $updateData = [];
            if (!empty($photoDataArray['front'])) $updateData['id_card_front'] = $photoDataArray['front'];
            if (!empty($photoDataArray['back'])) $updateData['id_card_back'] = $photoDataArray['back'];
            if (!empty($photoDataArray['face'])) $updateData['id_card_face'] = $photoDataArray['face'];
            if (!empty($photoDataArray['four'])) $updateData['id_card_four'] = $photoDataArray['four'];

            if (!empty($updateData)) {
                $updateData['photo_status'] = '4';
                $updateData['update_time'] = date('Y-m-d H:i:s');
                Db::name('order')->where('id', $order['id'])->update($updateData);
            }

            return json(['code' => 0, 'msg' => '照片上传成功']);
        }

        return json(['code' => 1, 'msg' => '暂不支持单张照片上传']);
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
        if (empty($productId)) {
            return json(['code' => 0, 'msg' => '参数错误', 'data' => []]);
        }

        // 获取产品信息
        $product = Db::table('product')->where('id', $productId)->where('status', 1)->find();
        if (!$product) {
            return json(['code' => 0, 'msg' => '产品不存在', 'data' => []]);
        }

        $apiName = $product['api_name'] ?? '';
        $apiType = $this->getApiTypeCode($apiName);
        
        // 调试日志
        Log::info('proxySelectNumber 路由', [
            'product_id' => $productId,
            'api_name' => $apiName,
            'api_type' => $apiType
        ]);
        
        try {
            // 根据API名称路由到对应的控制器
            $result = null;
            if (strpos($apiName, '58秒返') !== false) {
                $result = app('app\api\controller\kapi\mf58\Order')->queryNumbers();
            } elseif (strpos($apiName, '172号卡') !== false) {
                $result = app('app\api\controller\kapi\hao172\Order')->getNumbers();
            } elseif (strpos($apiName, '卡业联盟') !== false) {
                $result = app('app\api\controller\kapi\haoky\Order')->getNumbers();
            } elseif (strpos($apiName, '号卡极团') !== false) {
                $result = app('app\api\controller\kapi\haoteam\Order')->getNumbers(request());
            } elseif (strpos($apiName, '号易') !== false) {
                $result = app('app\api\controller\kapi\haoy\HaoyOrder')->getNumbers();
            } elseif (strpos($apiName, '蓝畅') !== false) {
                $result = app('app\api\controller\kapi\lanchang\Order')->getNumbers();
            } elseif (strpos($apiName, '天城智控') !== false) {
                $result = app('app\api\controller\kapi\tiancheng\Order')->getNumbers();
            } elseif (strpos($apiName, '龙宝') !== false) {
                $result = app('app\api\controller\kapi\longbao\SelectNumber')->getNumbers();
            } elseif (strpos($apiName, '广梦云') !== false) {
                $result = app('app\api\controller\kapi\guangmengyun\SelectNumber')->getNumbers();
            } elseif (strpos($apiName, '共创号卡') !== false) {
                $result = app('app\api\controller\kapi\gchk\SelectNumber')->getNumbers();
            } elseif (strpos($apiName, '巨量互联') !== false) {
                $result = app('app\api\controller\kapi\jlcloud\SelectNumber')->getNumbers();
            } elseif (strpos($apiName, '91敢探号') !== false) {
                // 91敢探号不支持选号API
                return json(['code' => 0, 'msg' => '该产品不支持选号功能', 'data' => []]);
            } elseif ($apiName === '自营' || empty($apiName)) {
                // 自营产品或未指定API的产品，从号码池获取
                $result = $this->getSelfOperatedNumbers($productId);
            } else {
                return json(['code' => 0, 'msg' => '该产品不支持选号功能', 'data' => []]);
            }
            
            // 统一转换返回格式
            $data = json_decode($result->getContent(), true);
            return $this->normalizeNumbersResponse($data, $apiType);
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '选号失败: ' . $e->getMessage(), 'data' => []]);
        }
    }
    
    /**
     * 统一号码列表返回格式
     * 返回格式: {code: 0, data: [{number: xxx, desc: xxx, price: 0}, ...]}
     */
    private function normalizeNumbersResponse($data, $apiType)
    {
        // 调试日志
        Log::info('normalizeNumbersResponse 输入', [
            'apiType' => $apiType,
            'data' => $data
        ]);
        
        // 判断原始响应是否成功
        // 天城智控(1001)、龙宝(1002)、巨量互联(1012) 返回 code=0 表示成功
        // 其他API返回 code=1 表示成功
        $isSuccess = false;
        if ($apiType === 1001 || $apiType === 1002 || $apiType === 1012) {
            $isSuccess = (isset($data['code']) && $data['code'] === 0 && isset($data['data']));
        } else {
            $isSuccess = (isset($data['code']) && $data['code'] === 1 && isset($data['data']));
        }
        
        Log::info('normalizeNumbersResponse 成功判断', [
            'isSuccess' => $isSuccess,
            'code' => $data['code'] ?? 'not set'
        ]);
        
        if (!$isSuccess) {
            return json(['code' => 0, 'msg' => $data['msg'] ?? $data['message'] ?? '获取号码失败', 'data' => []]);
        }
        
        $rawNumbers = $data['data'];
        $numbers = [];
        
        // 提取号码数组
        if (is_array($rawNumbers)) {
            if (isset($rawNumbers['list']) && is_array($rawNumbers['list'])) {
                $rawNumbers = $rawNumbers['list'];
            } elseif (isset($rawNumbers['numbers']) && is_array($rawNumbers['numbers'])) {
                $rawNumbers = $rawNumbers['numbers'];
            }
        }
        
        Log::info('normalizeNumbersResponse 提取号码', [
            'rawNumbers_count' => is_array($rawNumbers) ? count($rawNumbers) : 0,
            'rawNumbers_sample' => is_array($rawNumbers) ? array_slice($rawNumbers, 0, 3) : $rawNumbers
        ]);
        
        // 统一转换为标准格式
        foreach ($rawNumbers as $item) {
            if (is_string($item)) {
                // 纯字符串号码
                if (preg_match('/^1\d{10}$/', $item)) {
                    $numbers[] = ['number' => $item, 'desc' => '普通号码', 'price' => 0];
                }
            } elseif (is_array($item)) {
                // 对象格式号码
                $number = $item['numbers'] ?? $item['number'] ?? $item['phone'] ?? $item['mobile'] ?? '';
                $desc = $item['type_msg'] ?? $item['desc'] ?? $item['description'] ?? '普通号码';
                $price = $item['price'] ?? $item['cost'] ?? 0;
                
                if (!empty($number)) {
                    $numbers[] = ['number' => $number, 'desc' => $desc, 'price' => floatval($price)];
                }
            }
        }
        
        Log::info('normalizeNumbersResponse 输出', [
            'numbers_count' => count($numbers)
        ]);
        
        return json(['code' => 0, 'msg' => '获取成功', 'data' => $numbers]);
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
        
        if (empty($productId)) {
            return json(['code' => 1, 'msg' => '参数错误', 'data' => []]);
        }

        // 获取产品信息
        $product = Db::table('product')->where('id', $productId)->where('status', 1)->find();
        if (!$product) {
            return json(['code' => 1, 'msg' => '产品不存在', 'data' => []]);
        }

        $apiName = $product['api_name'] ?? '';
        $apiType = $this->getApiTypeCode($apiName);
        
        try {
            $data = [];
            
            // 根据API类型获取地区数据
            if ($apiType === 1004) {
                // 58秒返
                $data = $this->get58mfAreaData($type, $productId, $provinceCode, $cityCode);
            } elseif ($apiType === 1001) {
                // 天城智控
                $data = $this->getTianchengAreaData($type, $productId, $provinceCode, $cityCode);
            } elseif ($apiType === 1002) {
                // 龙宝
                $data = $this->getLongbaoAreaData($type, $provinceCode, $cityCode);
            } elseif ($apiType === 1010) {
                // 广梦云
                $data = $this->getGuangmengyunAreaData($type, $product, $provinceCode, $cityCode);
            } else {
                // 其他API使用通用地区数据（自营、号易、蓝畅、极客云等）
                $data = $this->getCommonAreaData($type, $provinceCode, $cityCode);
            }
            
            return json(['code' => 0, 'msg' => '获取成功', 'data' => $data]);
            
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '获取地区数据失败: ' . $e->getMessage(), 'data' => []]);
        }
    }
    
    /**
     * 获取通用地区数据
     */
    private function getCommonAreaData($type, $provinceCode, $cityCode)
    {
        $areaController = app('app\api\controller\kapi\Area');
        
        if ($type === 'provinces') {
            $result = $areaController->getProvinces();
        } elseif ($type === 'cities') {
            $_GET['province_code'] = $provinceCode;
            $result = $areaController->getCities();
        } else {
            $_GET['city_code'] = $cityCode;
            $result = $areaController->getDistricts();
        }
        
        $data = json_decode($result->getContent(), true);
        // Area控制器返回code=1表示成功
        if (isset($data['code']) && $data['code'] == 1) {
            return $data['data'] ?? [];
        }
        return [];
    }
    
    /**
     * 获取58秒返地区数据
     */
    private function get58mfAreaData($type, $productId, $provinceCode, $cityCode)
    {
        // 58秒返API需要省份名称和城市名称
        $provinceName = input('province_name', '');
        $cityName = input('city_name', '');
        
        // 直接调用MF58Api获取地区数据
        $config = $this->get58mfConfig();
        if (empty($config)) {
            return [];
        }
        
        $api = new \app\api\controller\kapi\mf58\common\MF58Api($config);
        
        // 获取产品编号
        $product = Db::name('product')->where('id', $productId)->field('number')->find();
        if (empty($product) || empty($product['number'])) {
            return [];
        }
        $productNumber = $product['number'];
        
        if ($type === 'provinces') {
            $result = $api->getProvinces($productNumber);
        } elseif ($type === 'cities') {
            $result = $api->getCities($productNumber, $provinceName, $provinceCode);
        } else {
            $result = $api->getDistricts($productNumber, $provinceName, $cityName, $cityCode);
        }
        
        // 转换格式 Code/Name -> code/name
        $items = [];
        $regionData = [];
        if (isset($result['code']) && $result['code'] == 0 && isset($result['data'])) {
            $regionData = $result['data'];
        } elseif (isset($result['data']) && is_array($result['data'])) {
            $regionData = $result['data'];
        }
        
        if (is_array($regionData)) {
            foreach ($regionData as $item) {
                if (is_array($item)) {
                    $items[] = [
                        'code' => $item['Code'] ?? $item['code'] ?? '',
                        'name' => $item['Name'] ?? $item['name'] ?? ''
                    ];
                }
            }
        }
        return $items;
    }
    
    /**
     * 获取58秒返配置
     */
    private function get58mfConfig()
    {
        try {
            return Db::name('config_api')
                ->where('api_type', 'mf58')
                ->where('status', 1)
                ->find();
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * 获取天城智控地区数据
     */
    private function getTianchengAreaData($type, $productId, $provinceCode, $cityCode)
    {
        $productController = app('app\api\controller\kapi\tiancheng\Product');
        $_POST['product_id'] = $productId;
        
        $result = $productController->queryArea();
        $data = json_decode($result->getContent(), true);
        
        // 天城智控返回完整的省市区树形结构
        $items = [];
        if (isset($data['data']) && is_array($data['data'])) {
            if ($type === 'provinces') {
                foreach ($data['data'] as $province) {
                    $items[] = [
                        'code' => $province['id'] ?? '',
                        'name' => $province['label'] ?? ''
                    ];
                }
            } elseif ($type === 'cities') {
                foreach ($data['data'] as $province) {
                    if (($province['id'] ?? '') == $provinceCode && isset($province['children'])) {
                        foreach ($province['children'] as $city) {
                            $items[] = [
                                'code' => $city['id'] ?? '',
                                'name' => $city['label'] ?? ''
                            ];
                        }
                        break;
                    }
                }
            } else {
                foreach ($data['data'] as $province) {
                    if (isset($province['children'])) {
                        foreach ($province['children'] as $city) {
                            if (($city['id'] ?? '') == $cityCode && isset($city['children'])) {
                                foreach ($city['children'] as $district) {
                                    $items[] = [
                                        'code' => $district['id'] ?? '',
                                        'name' => $district['label'] ?? ''
                                    ];
                                }
                                break 2;
                            }
                        }
                    }
                }
            }
        }
        return $items;
    }
    
    /**
     * 获取龙宝地区数据
     */
    private function getLongbaoAreaData($type, $provinceCode, $cityCode)
    {
        $areaController = app('app\api\controller\kapi\longbao\Area');
        
        if ($type === 'provinces') {
            $result = $areaController->getProvinces();
        } elseif ($type === 'cities') {
            $_POST['province_code'] = $provinceCode;
            $result = $areaController->getCities();
        } else {
            $_POST['province_code'] = $provinceCode;
            $_POST['city_code'] = $cityCode;
            $result = $areaController->getDistricts();
        }
        
        $data = json_decode($result->getContent(), true);
        return $data['data'] ?? [];
    }
    
    /**
     * 获取广梦云地区数据
     */
    private function getGuangmengyunAreaData($type, $product, $provinceCode, $cityCode)
    {
        $_GET['goods_id'] = $product['number'] ?? '';
        $_GET['config_id'] = $product['api_config_id'] ?? 0;
        // 广梦云需要商品的province_code来获取对应的地区JSON文件
        $gmyProvinceCode = $product['province_code'] ?? '';
        
        // 如果产品没有province_code，尝试使用默认的uniAreaAddSuffix
        if (empty($gmyProvinceCode)) {
            $gmyProvinceCode = 'uniAreaAddSuffix';
        }
        $_GET['province_code'] = $gmyProvinceCode;
        
        // 调试日志
        \think\facade\Log::info('[广梦云地区] type=' . $type . ', goods_id=' . $_GET['goods_id'] . ', gmy_province_code=' . $gmyProvinceCode . ', province_value=' . $provinceCode . ', city_value=' . $cityCode);
        
        $areaController = app('app\api\controller\kapi\guangmengyun\Area');
        
        if ($type === 'provinces') {
            $result = $areaController->getProvinces();
        } elseif ($type === 'cities') {
            $_GET['province_value'] = $provinceCode;
            $result = $areaController->getCities();
        } else {
            $_GET['province_value'] = $provinceCode;
            $_GET['city_value'] = $cityCode;
            $result = $areaController->getDistricts();
        }
        
        $data = json_decode($result->getContent(), true);
        \think\facade\Log::info('[广梦云地区] 返回: code=' . ($data['code'] ?? 'null') . ', msg=' . ($data['msg'] ?? '') . ', data_count=' . count($data['data'] ?? []));
        
        // 广梦云可能有额外的ess_code字段，保留它
        return $data['data'] ?? [];
    }

    /**
     * 获取IP位置信息（代理美团API）- 前端调用接口
     */
    public function getIpLocationApi()
    {
        // 设置响应头
        header('Content-Type: application/json; charset=utf-8');

        try {
            // 获取客户端真实IP
            $clientIp = $this->getRealIp();

            // 调用美团API
            $apiUrl = 'https://apimobile.meituan.com/locate/v2/ip/loc?client_source=yourAppKey&rgeo=true&ip=' . $clientIp;

            // 使用curl请求
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response === false || $httpCode !== 200) {
                throw new \Exception('API请求失败');
            }

            $data = json_decode($response, true);

            if ($data && isset($data['data']) && isset($data['data']['rgeo'])) {
                echo json_encode([
                    'code' => 1,
                    'msg' => '获取成功',
                    'data' => $data['data']
                ], JSON_UNESCAPED_UNICODE);
            } else {
                throw new \Exception('API返回数据格式错误');
            }

        } catch (\Exception $e) {
            echo json_encode([
                'code' => 0,
                'msg' => '获取IP位置失败：' . $e->getMessage(),
                'data' => []
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * 获取客户端真实IP
     */
    private function getRealIp()
    {
        $ip = '';

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        // 如果是多个IP，取第一个
        if (strpos($ip, ',') !== false) {
            $ip = explode(',', $ip)[0];
        }

        return trim($ip);
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

        // 添加调试日志
        Log::info('loadProducts请求', [
            'shop_code' => $shop_code,
            'page' => $page,
            'limit' => $limit,
            'operator' => $operator,
            'keyword' => $keyword,
            'card_type' => $cardType
        ]);

        if (empty($shop_code)) {
            Log::error('loadProducts参数错误', ['shop_code' => $shop_code]);
            return json(['code' => 0, 'msg' => '参数错误']);
        }

        // 验证店铺是否存在
        $shop = Db::table('agent_shop')->where('shop_code', $shop_code)->where('status', 1)->find();
        if (!$shop) {
            return json(['code' => 0, 'msg' => '店铺不存在']);
        }

        // 计算偏移量
        $offset = ($page - 1) * $limit;

        // 使用原生SQL查询（支持加密）
        $config = config('database.connections.mysql');
        $pdo = new \PDO(
            "mysql:host={$config['hostname']};dbname={$config['database']};charset={$config['charset']}", 
            $config['username'], 
            $config['password']
        );
        
        // 构建WHERE条件
        $whereCondition = "status = 1";
        $params = [];
        if (!empty($operator)) {
            $whereCondition .= " AND yys = ?";
            $params[] = $operator;
        }
        if (!empty($keyword)) {
            $whereCondition .= " AND (name LIKE ? OR yuezu LIKE ? OR flow LIKE ? OR guishudi LIKE ?)";
            $keywordParam = '%' . $keyword . '%';
            $params[] = $keywordParam;
            $params[] = $keywordParam;
            $params[] = $keywordParam;
            $params[] = $keywordParam;
        }
        // 添加卡片类型筛选
        if (!empty($cardType)) {
            if ($cardType === 'paid') {
                // 付费卡：card_type=1 且 card_price>0
                $whereCondition .= " AND card_type = 1 AND card_price > 0";
            } elseif ($cardType === 'free') {
                // 免费卡：card_type!=1 或 card_price<=0 或 card_type为空
                $whereCondition .= " AND (card_type != 1 OR card_type IS NULL OR card_price <= 0 OR card_price IS NULL)";
            }
        }
        
        // 获取总数量
        $countSql = "SELECT COUNT(*) as total FROM {$config['prefix']}product WHERE {$whereCondition}";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetch(\PDO::FETCH_ASSOC)['total'];
        
        // 获取所有符合条件的产品（包含排序所需字段），然后应用代理排序
        $sql = "SELECT id, name, product_image, guishudi, age, tags, yuezu, flow, yys, selectNumber, kaika, card_type, card_price, admin_sort_order, create_time FROM {$config['prefix']}product WHERE {$whereCondition}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $allProducts = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // 应用代理自定义排序（包含热门置顶）
        $agentId = $shop['agent_id'];
        $sortResult = $this->applyAgentSort($agentId, $allProducts);
        $sortedProducts = $sortResult['products'];
        $hotProductIds = $sortResult['hot_ids'];
        
        // 手动分页
        $products = array_slice($sortedProducts, $offset, $limit);

        // 批量查询转图后的自定义图片
        $customImages = [];
        $activeTemplate = ImageTemplateService::getActiveTemplate();
        if ($activeTemplate && !empty($products)) {
            $pIds = array_column($products, 'id');
            if (!empty($pIds)) {
                $customImages = Db::name('product_custom_image')
                    ->where('template_id', $activeTemplate['id'])
                    ->whereIn('product_id', $pIds)
                    ->column('image_url', 'product_id');
            }
        }

        // 计算付费卡价格并标记热门产品
        foreach ($products as &$product) {
            // 转图后的自定义图片（优先显示）
            $product['display_image'] = $customImages[$product['id']] ?? '';
            // 标记是否为热门产品
            $product['is_hot'] = in_array($product['id'], $hotProductIds) ? 1 : 0;
            
            if (($product['card_type'] ?? 0) == 1) {
                $cardPrice = floatval($product['card_price'] ?? 0);
                $totalMarkup = 0;
                
                // 获取代理加价
                $markupSql = "SELECT total_markup_price FROM {$config['prefix']}product_agent_markup WHERE agent_id = ? AND product_id = ? AND status = 1 LIMIT 1";
                $markupStmt = $pdo->prepare($markupSql);
                $markupStmt->execute([$agentId, $product['id']]);
                $markup = $markupStmt->fetch(\PDO::FETCH_ASSOC);
                if ($markup) {
                    $totalMarkup = floatval($markup['total_markup_price'] ?? 0);
                }
                
                $product['total_price'] = $cardPrice + $totalMarkup;
            } else {
                $product['total_price'] = 0;
            }
        }

        // 判断是否还有更多数据
        $hasMore = ($offset + $limit) < $total;

        return json([
            'code' => 1,
            'msg' => '获取成功',
            'data' => [
                'products' => $products,
                'hasMore' => $hasMore,
                'total' => $total,
                'page' => $page
            ]
        ]);
    }

    /**
     * 生成产品推广海报
     */
    public function generateProductPoster()
    {
        $shop_code = input('shop_code', '');
        $product_id = input('product_id', 0);
        $template_id = input('template_id', 1);

        if (empty($shop_code) || empty($product_id)) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }

        // 获取店铺信息
        $shop = Db::table('agent_shop')->where('shop_code', $shop_code)->where('status', 1)->find();
        if (!$shop) {
            return json(['code' => 0, 'msg' => '店铺不存在']);
        }

        // 获取产品信息
        $product = Db::table('product')->where('id', $product_id)->where('status', 1)->find();
        if (!$product) {
            return json(['code' => 0, 'msg' => '产品不存在']);
        }

        try {
            // 生成产品链接
            $productUrl = request()->domain() . '/index/shop/product/shop_code/' . $shop['shop_code'] . '/product_id/' . $product_id;

            // 生成二维码
            $qrPath = 'uploads/qrcode/';
            if (!is_dir(app()->getRootPath() . 'public/' . $qrPath)) {
                mkdir(app()->getRootPath() . 'public/' . $qrPath, 0755, true);
            }

            $qrFileName = 'product_qr_' . $product_id . '_' . $shop['shop_code'] . '_' . time() . '.png';
            $qrFilePath = app()->getRootPath() . 'public/' . $qrPath . $qrFileName;

            // 生成二维码
            \QRcode::png($productUrl, $qrFilePath, 'L', 6, 2);

            // 获取模板背景图
            $bgImagePath = $this->getProductTemplateBackground($template_id);
            if (!$bgImagePath || !file_exists($bgImagePath)) {
                return json(['code' => 0, 'msg' => '模板背景图不存在']);
            }

            // 创建海报
            $posterPath = 'uploads/poster/';
            if (!is_dir(app()->getRootPath() . 'public/' . $posterPath)) {
                mkdir(app()->getRootPath() . 'public/' . $posterPath, 0755, true);
            }

            $posterFileName = 'product_poster_' . $product_id . '_' . $shop['shop_code'] . '_t' . $template_id . '_' . time() . '.png';
            $posterFilePath = app()->getRootPath() . 'public/' . $posterPath . $posterFileName;

            // 使用模板创建产品海报
            $poster = $this->createProductPosterWithTemplate($product, $shop, $qrFilePath, $bgImagePath, $template_id);
            if (!$poster) {
                return json(['code' => 0, 'msg' => '海报生成失败']);
            }

            // 保存海报
            imagepng($poster, $posterFilePath);

            // 清理资源
            imagedestroy($poster);

            $posterUrl = '/' . $posterPath . $posterFileName;

            return json([
                'code' => 1,
                'msg' => '产品海报生成成功',
                'data' => [
                    'poster_url' => $posterUrl,
                    'qr_url' => '/' . $qrPath . $qrFileName,
                    'product_url' => $productUrl
                ]
            ]);

        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '生成失败：' . $e->getMessage()]);
        }
    }

    /**
     * 获取产品海报模板背景图路径
     */
    private function getProductTemplateBackground($templateId)
    {
        $backgrounds = [
            1 => app()->getRootPath() . 'public/static/images/shopimg/demo1.png',
            2 => app()->getRootPath() . 'public/static/images/shopimg/demo2.png',
            3 => app()->getRootPath() . 'public/static/images/shopimg/demo3.png'
        ];

        return $backgrounds[$templateId] ?? $backgrounds[1];
    }

    /**
     * 使用模板创建产品海报
     */
    private function createProductPosterWithTemplate($product, $shop, $qrFilePath, $bgImagePath, $templateId)
    {
        // 获取背景图信息
        $bgInfo = getimagesize($bgImagePath);
        if (!$bgInfo) {
            return false;
        }

        // 根据文件类型创建背景图资源
        switch ($bgInfo[2]) {
            case IMAGETYPE_JPEG:
                $bgImage = imagecreatefromjpeg($bgImagePath);
                break;
            case IMAGETYPE_PNG:
                $bgImage = imagecreatefrompng($bgImagePath);
                break;
            default:
                return false;
        }

        if (!$bgImage) {
            return false;
        }

        // 设置标准海报尺寸 800*1100 (4:5比例)
        $posterWidth = 800;
        $posterHeight = 1100;

        // 获取背景图尺寸
        $bgWidth = imagesx($bgImage);
        $bgHeight = imagesy($bgImage);

        // 创建海报画布（使用标准尺寸）
        $poster = imagecreatetruecolor($posterWidth, $posterHeight);

        // 保持透明度
        imagealphablending($poster, false);
        imagesavealpha($poster, true);

        // 将背景图缩放并复制到海报上
        imagecopyresampled($poster, $bgImage, 0, 0, 0, 0, $posterWidth, $posterHeight, $bgWidth, $bgHeight);

        // 添加产品图片
        $this->addProductImageToPoster($poster, $product, $templateId, $posterWidth, $posterHeight);

        // 加载二维码
        $qrImage = imagecreatefrompng($qrFilePath);
        if ($qrImage) {
            // 根据不同模板设置二维码位置和大小
            $qrConfig = $this->getProductQrCodeConfig($templateId, $posterWidth, $posterHeight);

            // 调整二维码大小
            $qrResized = imagecreatetruecolor($qrConfig['size'], $qrConfig['size']);
            imagecopyresampled($qrResized, $qrImage, 0, 0, 0, 0,
                             $qrConfig['size'], $qrConfig['size'],
                             imagesx($qrImage), imagesy($qrImage));

            // 将二维码贴到海报上
            imagecopy($poster, $qrResized, $qrConfig['x'], $qrConfig['y'], 0, 0, $qrConfig['size'], $qrConfig['size']);

            imagedestroy($qrResized);
            imagedestroy($qrImage);
        }

        // 添加产品信息文字
        $this->addProductInfoToTemplate($poster, $product, $shop, $templateId, $posterWidth, $posterHeight);

        // 清理背景图资源
        imagedestroy($bgImage);

        return $poster;
    }

    /**
     * 获取产品海报二维码配置（位置和大小）
     */
    private function getProductQrCodeConfig($templateId, $bgWidth, $bgHeight)
    {
        $configs = [
            1 => [ // 模板1：右下角，避开产品图片
                'size' => min($bgWidth, $bgHeight) * 0.3,
                'x' => $bgWidth * 0.03,
                'y' => $bgHeight * 0.75
            ],
            2 => [ // 模板2：右下角
                'size' => min($bgWidth, $bgHeight) * 0.28,
                'x' => $bgWidth * 0.06,
                'y' => $bgHeight * 0.77
            ],
            3 => [ // 模板3：左下角，避开产品图片
                'size' => min($bgWidth, $bgHeight) * 0.28,
                'x' => $bgWidth * 0.03,
                'y' => $bgHeight * 0.76
            ]
        ];

        $config = $configs[$templateId] ?? $configs[1];

        // 确保坐标和尺寸为整数
        return [
            'size' => intval($config['size']),
            'x' => intval($config['x']),
            'y' => intval($config['y'])
        ];
    }

    /**
     * 添加产品图片到海报
     */
    private function addProductImageToPoster($poster, $product, $templateId, $bgWidth, $bgHeight)
    {
        // 优先使用转图后的自定义图片
        $productImageUrl = ImageTemplateService::getDisplayImage($product);
        if (empty($productImageUrl)) {
            return; // 如果没有产品图片，直接返回
        }

        // 处理图片URL，支持相对路径和绝对路径
        if (strpos($productImageUrl, 'http') !== 0) {
            // 如果是相对路径，转换为绝对路径
            if (strpos($productImageUrl, '/') === 0) {
                $productImageUrl = request()->domain() . $productImageUrl;
            } else {
                $productImageUrl = request()->domain() . '/' . $productImageUrl;
            }
        }

        // 下载图片到临时文件
        $tempImagePath = $this->downloadImageToTemp($productImageUrl);
        if (!$tempImagePath || !file_exists($tempImagePath)) {
            return; // 下载失败，直接返回
        }

        // 根据模板设置产品图片位置和大小
        $imageConfig = $this->getProductImageConfig($templateId, $bgWidth, $bgHeight);

        // 加载产品图片
        $productImage = $this->createImageFromFile($tempImagePath);
        if ($productImage) {
            // 调整产品图片大小
            $resizedImage = imagecreatetruecolor($imageConfig['width'], $imageConfig['height']);

            // 保持透明度
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);

            imagecopyresampled($resizedImage, $productImage, 0, 0, 0, 0,
                             $imageConfig['width'], $imageConfig['height'],
                             imagesx($productImage), imagesy($productImage));

            // 添加圆角效果
            $roundedImage = $this->addRoundedCorners($resizedImage, 20);

            // 将产品图片贴到海报上
            imagecopy($poster, $roundedImage, $imageConfig['x'], $imageConfig['y'], 0, 0, $imageConfig['width'], $imageConfig['height']);

            imagedestroy($roundedImage);

            imagedestroy($resizedImage);
            imagedestroy($productImage);
        }

        // 清理临时文件
        if (file_exists($tempImagePath)) {
            unlink($tempImagePath);
        }
    }

    /**
     * 获取产品图片配置（位置和大小）
     */
    private function getProductImageConfig($templateId, $bgWidth, $bgHeight)
    {
        $configs = [
            1 => [ // 模板1：左上角
                'width' => intval($bgWidth * 1),
                'height' => intval($bgHeight * 0.72),
                'x' => intval($bgWidth * 0),
                'y' => intval($bgHeight * 0)
            ],
            2 => [ // 模板2：中央
                'width' => intval($bgWidth * 0.35),
                'height' => intval($bgHeight * 0.35),
                'x' => intval($bgWidth * 0.325),
                'y' => intval($bgHeight * 0.15)
            ],
            3 => [ // 模板3：右上角
                'width' => intval($bgWidth * 0.4),
                'height' => intval($bgHeight * 0.4),
                'x' => intval($bgWidth * 0.55),
                'y' => intval($bgHeight * 0.1)
            ]
        ];

        return $configs[$templateId] ?? $configs[1];
    }

    /**
     * 添加产品信息到模板
     */
    private function addProductInfoToTemplate($poster, $product, $shop, $templateId, $bgWidth, $bgHeight)
    {
        // 使用店铺主题色
        $themeColor = $shop['theme_color'] ?? '#1890ff';
        $themeRgb = $this->hexToRgb($themeColor);
        $primaryColor = imagecolorallocate($poster, $themeRgb['r'], $themeRgb['g'], $themeRgb['b']);
        $textColor = imagecolorallocate($poster, 51, 51, 51);
        $whiteColor = imagecolorallocate($poster, 255, 255, 255);
        $redColor = imagecolorallocate($poster, 255, 87, 34);

        // 添加产品名称和价格等信息（简化版本，可根据需要扩展）
        $productName = $product['name'] ?? '';
        $price = '¥' . ($product['yuezu'] ?? 0) . '/月';

        // 这里可以添加更多文字信息到海报上
        // 由于字体文件路径可能不同，这里简化处理
    }

    /**
     * 十六进制颜色转RGB
     */
    private function hexToRgb($hex)
    {
        $hex = ltrim($hex, '#');
        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2))
        ];
    }

    /**
     * 下载图片到临时文件
     */
    private function downloadImageToTemp($imageUrl)
    {
        try {
            $tempDir = app()->getRootPath() . 'runtime/temp/';
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $tempFile = $tempDir . 'product_img_' . time() . '_' . rand(1000, 9999) . '.tmp';

            // 对URL路径中的中文字符进行编码
            $imageUrl = $this->encodeUrlPath($imageUrl);

            // 使用curl下载，支持更多场景
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $imageUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
            $imageData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($imageData !== false && $httpCode == 200) {
                file_put_contents($tempFile, $imageData);
                return $tempFile;
            }
        } catch (\Exception $e) {
            // 下载失败，返回false
        }

        return false;
    }

    /**
     * 对URL路径中的中文字符进行编码
     */
    private function encodeUrlPath($url)
    {
        $parsed = parse_url($url);
        if (!$parsed || empty($parsed['path'])) {
            return $url;
        }

        // 对路径部分进行编码（保留/）
        $path = $parsed['path'];
        $encodedPath = implode('/', array_map('rawurlencode', explode('/', $path)));

        // 重新组装URL
        $result = '';
        if (!empty($parsed['scheme'])) {
            $result .= $parsed['scheme'] . '://';
        }
        if (!empty($parsed['host'])) {
            $result .= $parsed['host'];
        }
        if (!empty($parsed['port'])) {
            $result .= ':' . $parsed['port'];
        }
        $result .= $encodedPath;
        if (!empty($parsed['query'])) {
            $result .= '?' . $parsed['query'];
        }

        return $result;
    }

    /**
     * 从文件创建图片资源
     */
    private function createImageFromFile($filePath)
    {
        $imageInfo = getimagesize($filePath);
        if (!$imageInfo) {
            return false;
        }

        switch ($imageInfo[2]) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($filePath);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($filePath);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($filePath);
            default:
                return false;
        }
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
        
        // 验证店铺代码（基本的安全检查）
        if (empty($shop_code)) {
            return json(['code' => 0, 'msg' => '店铺参数错误']);
        }
        
        // 验证店铺是否存在且有效
        $shop = Db::table('agent_shop')->where('shop_code', $shop_code)->where('status', 1)->find();
        if (!$shop) {
            return json(['code' => 0, 'msg' => '店铺不存在或已关闭']);
        }
        
        // 验证手机号
        if (empty($phone) || !preg_match('/^1[3-9]\d{9}$/', $phone)) {
            return json(['code' => 0, 'msg' => '请输入正确的手机号码']);
        }
        
        try {
            // 获取自动回填配置
            $config = \app\common\helper\SystemConfig::get();
            $autoFillEnabled = ($config['auto_fill_verify_code'] ?? '0') === '1';
            $autoFillTriggerCount = intval($config['auto_fill_trigger_count'] ?? 3);

            // 统计该手机号今天的发送次数
            $today = date('Y-m-d');
            $sendCount = Db::table('sms_logs')
                ->where('phone', $phone)
                ->where('send_time', '>=', strtotime($today))
                ->where('send_time', '<', strtotime($today . ' +1 day'))
                ->where('status', 1)
                ->count();

            // 调用公共短信控制器
            $result = \app\common\controller\Sms::sendCode($phone, null, 'order_verify');

            if ($result['success']) {
                $response = ['code' => 1, 'msg' => '验证码发送成功'];

                // 检查是否需要自动回填验证码
                if ($autoFillEnabled && ($sendCount + 1) >= $autoFillTriggerCount && isset($result['code'])) {
                    $response['code_for_fill'] = $result['code'];
                    $response['msg'] = '验证码已发送到您的手机，已自动填入验证码';
                }

                return json($response);
            } else {
                return json(['code' => 0, 'msg' => $result['message']]);
            }

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
        return View::fetch('shop/error');
    }

    /**
     * 为图片添加圆角效果
     */
    private function addRoundedCorners($image, $radius)
    {
        $width = imagesx($image);
        $height = imagesy($image);

        // 创建带透明背景的新图片
        $rounded = imagecreatetruecolor($width, $height);
        imagealphablending($rounded, false);
        imagesavealpha($rounded, true);
        
        // 填充透明背景
        $transparent = imagecolorallocatealpha($rounded, 255, 255, 255, 127);
        imagefill($rounded, 0, 0, $transparent);
        
        // 创建圆角遮罩
        $mask = imagecreatetruecolor($width, $height);
        $maskBg = imagecolorallocate($mask, 255, 255, 255);
        $maskFg = imagecolorallocate($mask, 0, 0, 0);
        imagefill($mask, 0, 0, $maskBg);
        
        // 绘制圆角矩形
        imagefilledrectangle($mask, $radius, 0, $width - $radius - 1, $height - 1, $maskFg);
        imagefilledrectangle($mask, 0, $radius, $width - 1, $height - $radius - 1, $maskFg);
        
        // 绘制四个圆角
        imagefilledellipse($mask, $radius, $radius, $radius * 2, $radius * 2, $maskFg);
        imagefilledellipse($mask, $width - $radius - 1, $radius, $radius * 2, $radius * 2, $maskFg);
        imagefilledellipse($mask, $radius, $height - $radius - 1, $radius * 2, $radius * 2, $maskFg);
        imagefilledellipse($mask, $width - $radius - 1, $height - $radius - 1, $radius * 2, $radius * 2, $maskFg);
        
        // 应用遮罩
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $maskPixel = imagecolorat($mask, $x, $y);
                if ($maskPixel == $maskFg) {
                    $originalPixel = imagecolorat($image, $x, $y);
                    imagesetpixel($rounded, $x, $y, $originalPixel);
                }
            }
        }
        
        imagedestroy($mask);
        return $rounded;
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
        
        if (empty($orderId) || empty($token)) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }

        try {
            // 验证token（token = md5(订单ID + 当前日期 + 系统密钥)）
            $securityKey = Db::name('system_config')->where('config_key', 'security_key')->value('config_value') ?? '';
            $expectedToken = md5($orderId . date('Y-m-d') . $securityKey);
            
            if ($token !== $expectedToken) {
                return json(['code' => 0, 'msg' => '无权访问']);
            }

            // 获取订单信息
            $order = Db::name('order')->where('id', $orderId)->find();
            
            if (!$order) {
                return json(['code' => 0, 'msg' => '订单不存在']);
            }

            // 从URL路径中提取 shop_code
            // URL格式：/index/shop/product/shop_code/xxx/product_id/xxx
            $pathInfo = request()->pathinfo();
            $pathParts = explode('/', trim($pathInfo, '/'));
            $shopCode = '';
            $shopCodeIndex = array_search('shop_code', $pathParts);
            if ($shopCodeIndex !== false && isset($pathParts[$shopCodeIndex + 1])) {
                $shopCode = $pathParts[$shopCodeIndex + 1];
            }

            // 根据订单是否有 agent_id 判断是代理订单还是管理员订单
            // 如果有 agent_id，说明是代理订单，需要验证 shop_code 对应的 agent_id
            if (!empty($order['agent_id'])) {
                // 代理订单：验证 shop_code 对应的 agent_id 是否匹配
                if (!empty($shopCode)) {
                    $shop = Db::table('agent_shop')->where('shop_code', $shopCode)->where('status', 1)->find();
                    if (!$shop) {
                        return json(['code' => 0, 'msg' => '店铺不存在']);
                    }
                    if ($order['agent_id'] != $shop['agent_id']) {
                        return json(['code' => 0, 'msg' => '无权访问该订单']);
                    }
                } else {
                    // 如果没有 shop_code，但订单有 agent_id，说明可能是代理订单
                    // 这种情况下，允许访问（因为token已经验证了权限）
                }
            }
            // 如果订单没有 agent_id，说明可能是管理员订单，允许访问

            return json([
                'code' => 1,
                'msg' => '获取成功',
                'data' => [
                    'customer_name' => $order['customer_name'],
                    'phone' => $order['phone'],
                    'idcard' => $order['idcard'],
                    'province' => $order['province'],
                    'city' => $order['city'],
                    'district' => $order['district'],
                    'address' => $order['address'],
                    'province_code' => $order['province_code'] ?? '',
                    'city_code' => $order['city_code'] ?? '',
                    'district_code' => $order['district_code'] ?? ''
                ]
            ]);

        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '获取失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 应用代理自定义排序
     * @param int $agentId 代理ID
     * @param array $products 产品列表
     * @return array ['products' => 排序后的产品列表, 'hot_ids' => 热门产品ID数组]
     */
    private function applyAgentSort($agentId, $products)
    {
        $hotIds = [];
        
        if (empty($products)) {
            return ['products' => $products, 'hot_ids' => $hotIds];
        }
        
        // 获取该代理的自定义排序
        $sortRecord = Db::table('agent_product_sort')
            ->where('agent_id', $agentId)
            ->find();
        
        // 如果没有自定义排序，按总后台排序逻辑：
        // 1. 热门产品优先置顶
        // 2. admin_sort_order > 0 的按排序值升序，= 0 的放最后按创建时间倒序
        if (empty($sortRecord) || empty($sortRecord['sort_data'])) {
            usort($products, function($a, $b) {
                $sortA = isset($a['admin_sort_order']) ? (int)$a['admin_sort_order'] : 0;
                $sortB = isset($b['admin_sort_order']) ? (int)$b['admin_sort_order'] : 0;
                
                // 如果都有排序值（> 0），按排序值升序
                if ($sortA > 0 && $sortB > 0) {
                    if ($sortA != $sortB) {
                        return $sortA - $sortB;
                    }
                    // 排序值相同，按创建时间倒序
                    $timeA = isset($a['create_time']) ? strtotime($a['create_time']) : 0;
                    $timeB = isset($b['create_time']) ? strtotime($b['create_time']) : 0;
                    return $timeB - $timeA;
                }
                
                // 有排序值的排前面
                if ($sortA > 0 && $sortB == 0) {
                    return -1;
                }
                if ($sortA == 0 && $sortB > 0) {
                    return 1;
                }
                
                // 都没有排序值，按创建时间倒序
                $timeA = isset($a['create_time']) ? strtotime($a['create_time']) : 0;
                $timeB = isset($b['create_time']) ? strtotime($b['create_time']) : 0;
                return $timeB - $timeA;
            });
            return ['products' => $products, 'hot_ids' => $hotIds];
        }
        
        // 解析JSON排序数据
        $sortData = json_decode($sortRecord['sort_data'], true);
        
        // 兼容旧格式（纯数组）和新格式（包含sort和hot的对象）
        $sortArray = [];
        if (is_array($sortData)) {
            // 新格式：{"sort": [...], "hot": [...]}
            if (isset($sortData['sort']) && is_array($sortData['sort'])) {
                $sortArray = $sortData['sort'];
                // 获取热门产品ID列表
                if (isset($sortData['hot']) && is_array($sortData['hot'])) {
                    $hotIds = array_map('intval', array_filter($sortData['hot']));
                }
            } 
            // 旧格式：[product_id1, product_id2, ...]
            else if (isset($sortData[0])) {
                $sortArray = $sortData;
            }
        }
        
        if (empty($sortArray)) {
            usort($products, function($a, $b) use ($hotIds) {
                // 首先按热门状态排序，热门的置顶
                $hotA = in_array($a['id'], $hotIds) ? 1 : 0;
                $hotB = in_array($b['id'], $hotIds) ? 1 : 0;
                if ($hotA != $hotB) {
                    return $hotB - $hotA; // 热门的排前面
                }
                
                $sortA = isset($a['admin_sort_order']) ? (int)$a['admin_sort_order'] : 0;
                $sortB = isset($b['admin_sort_order']) ? (int)$b['admin_sort_order'] : 0;
                
                if ($sortA > 0 && $sortB > 0) {
                    if ($sortA != $sortB) {
                        return $sortA - $sortB;
                    }
                    $timeA = isset($a['create_time']) ? strtotime($a['create_time']) : 0;
                    $timeB = isset($b['create_time']) ? strtotime($b['create_time']) : 0;
                    return $timeB - $timeA;
                }
                if ($sortA > 0 && $sortB == 0) {
                    return -1;
                }
                if ($sortA == 0 && $sortB > 0) {
                    return 1;
                }
                $timeA = isset($a['create_time']) ? strtotime($a['create_time']) : 0;
                $timeB = isset($b['create_time']) ? strtotime($b['create_time']) : 0;
                return $timeB - $timeA;
            });
            return ['products' => $products, 'hot_ids' => $hotIds];
        }
        
        // 确保数组元素都是整数（产品ID）
        $sortArray = array_map('intval', array_filter($sortArray));
        
        // 创建产品ID到排序位置的映射
        $sortMap = array_flip($sortArray);
        
        // 应用自定义排序，但热门产品优先
        usort($products, function($a, $b) use ($sortMap, $hotIds) {
            // 首先按热门状态排序，热门的置顶
            $hotA = in_array($a['id'], $hotIds) ? 1 : 0;
            $hotB = in_array($b['id'], $hotIds) ? 1 : 0;
            if ($hotA != $hotB) {
                return $hotB - $hotA; // 热门的排前面
            }
            
            $sortA = isset($sortMap[$a['id']]) ? $sortMap[$a['id']] : 999999;
            $sortB = isset($sortMap[$b['id']]) ? $sortMap[$b['id']] : 999999;
            
            // 如果排序值相同，按创建时间倒序
            if ($sortA == $sortB) {
                $timeA = isset($a['create_time']) ? strtotime($a['create_time']) : 0;
                $timeB = isset($b['create_time']) ? strtotime($b['create_time']) : 0;
                return $timeB - $timeA;
            }
            
            return $sortA - $sortB;
        });
        
        return ['products' => $products, 'hot_ids' => $hotIds];
    }

    /**
     * 合集展示页面
     */
    public function collection($shop_code = '', $collection_id = 0)
    {
        if (empty($shop_code) || empty($collection_id)) {
            return $this->error('参数错误');
        }

        // 获取店铺信息
        $shop = Db::table('agent_shop')
            ->alias('s')
            ->leftJoin('agents a', 's.agent_id = a.id')
            ->field('s.*, a.username as agent_username')
            ->where('s.shop_code', $shop_code)
            ->where('s.status', 1)
            ->find();

        if (!$shop) {
            return $this->error('店铺不存在');
        }

        // 获取合集信息
        $collection = Db::table('product_collection')
            ->where('id', $collection_id)
            ->where('status', 1)
            ->find();

        if (!$collection) {
            return $this->error('合集不存在');
        }

        // 检查合集是否属于该代理（总后台的或该代理的）
        if ($collection['agent_id'] != 0 && $collection['agent_id'] != $shop['agent_id']) {
            return $this->error('无权访问该合集');
        }

        // 获取合集中的产品
        $products = Db::table('product_collection_item')
            ->alias('pci')
            ->leftJoin('product p', 'pci.product_id = p.id')
            ->where('pci.collection_id', $collection_id)
            ->where('p.status', 1)
            ->field('p.*, pci.sort as collection_sort')
            ->order('pci.sort', 'asc')
            ->order('pci.id', 'desc')
            ->select()
            ->toArray();

        // 传递数据到视图
        View::assign([
            'shop' => $shop,
            'collection' => $collection,
            'products' => $products,
            'shop_code' => $shop_code
        ]);

        return View::fetch('shop/collection');
    }

    /**
     * 获取自营产品的可选号码（从号码池）
     */
    private function getSelfOperatedNumbers($productId)
    {
        try {
            // 获取请求参数
            $province = input('province', '');
            $city = input('city', '');
            $page = input('page', 1);
            $limit = input('limit', 20);
            
            // 调试日志
            Log::info('getSelfOperatedNumbers 参数', [
                'product_id' => $productId,
                'province' => $province,
                'city' => $city,
                'page' => $page
            ]);
            
            // 验证必要参数
            if (empty($province) || empty($city)) {
                return json(['code' => 0, 'msg' => '省份和城市不能为空']);
            }
            
            // 限制每页数量
            if ($limit > 50) $limit = 50;
            $offset = ($page - 1) * $limit;
            
            // 查询号码池中的可用号码
            // 条件：1. 状态启用 2. 未被使用 3. 匹配地区
            $where = [
                ['status', '=', 1],
                ['is_used', '=', 0]
            ];
            
            // 省市匹配支持模糊匹配（处理"浙江省"和"浙江"的差异）
            $provinceClean = str_replace(['省', '市', '自治区', '特别行政区'], '', $province);
            $cityClean = str_replace(['市', '区', '县', '自治州'], '', $city);
            
            // 优先精确匹配，然后模糊匹配
            $total = 0;
            $numbers = [];
            
            // 1. 先尝试精确匹配产品+地区
            $exactWhere = array_merge($where, [
                ['product_id', '=', $productId],
                ['province', 'like', '%' . $provinceClean . '%'],
                ['city', 'like', '%' . $cityClean . '%']
            ]);
            $total = Db::table('available_numbers')->where($exactWhere)->count();
            
            Log::info('精确匹配产品号码', ['where' => $exactWhere, 'total' => $total]);
            
            // 2. 如果没有，尝试通用号码(product_id=0)
            if ($total == 0) {
                $exactWhere = array_merge($where, [
                    ['product_id', '=', 0],
                    ['province', 'like', '%' . $provinceClean . '%'],
                    ['city', 'like', '%' . $cityClean . '%']
                ]);
                $total = Db::table('available_numbers')->where($exactWhere)->count();
                Log::info('通用号码匹配', ['where' => $exactWhere, 'total' => $total]);
            }
            
            // 3. 如果还没有，只按地区匹配（不限产品）
            if ($total == 0) {
                $exactWhere = array_merge($where, [
                    ['province', 'like', '%' . $provinceClean . '%'],
                    ['city', 'like', '%' . $cityClean . '%']
                ]);
                $total = Db::table('available_numbers')->where($exactWhere)->count();
                Log::info('仅地区匹配', ['where' => $exactWhere, 'total' => $total]);
            }
            
            // 查询号码列表
            if ($total > 0) {
                $numbers = Db::table('available_numbers')
                    ->where($exactWhere)
                    ->field('id, number, operator, province, city, number_type, description, sort')
                    ->order('sort', 'desc')
                    ->order('id', 'asc')
                    ->limit($offset, $limit)
                    ->select()
                    ->toArray();
            }
            
            // 转换为前端期望的格式（号码字符串数组）
            $numberList = [];
            foreach ($numbers as $number) {
                $numberList[] = $number['number'];
            }
            
            return json([
                'code' => 1,
                'msg' => '查询成功',
                'data' => [
                    'list' => $numberList,
                    'total' => (int)$total
                ]
            ]);
            
        } catch (\Exception $e) {
            \think\facade\Log::error('自营产品选号失败: ' . $e->getMessage());
            return json(['code' => 0, 'msg' => '获取号码失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 将号码标记为已使用
     */
    private function markNumberAsUsed($phoneNumber, $orderId, $agentId = null)
    {
        try {
            
            $updateData = [
                'is_used' => 1,
                'updated_time' => time()
            ];
            
            // 如果有代理ID，记录是哪个代理的客户使用的
            if ($agentId) {
                $updateData['agent_id'] = $agentId;
            }
            
            $result = Db::table('available_numbers')
                ->where('number', $phoneNumber)
                ->update($updateData);
                
            
            return $result;
        } catch (\Exception $e) {
            \think\facade\Log::error('Shop.markNumberAsUsed: 标记号码失败', [
                'phone_number' => $phoneNumber,
                'order_id' => $orderId,
                'agent_id' => $agentId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 处理产品标签
     * @param string $tags
     * @return array
     */
    private function processProductTags($tags)
    {
        $productTags = [];
        
        if (!empty(trim($tags))) {
            $tagArray = json_decode($tags, true);
            if (!is_array($tagArray)) {
                $tagArray = explode(',', $tags);
            }

            foreach ($tagArray as $tag) {
                $tag = trim($tag);
                if (!empty($tag)) {
                    $productTags[] = $tag;
                }
            }
        }

        return $productTags;
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
        if (empty($address)) {
            return json(['code' => 0, 'msg' => '地址不能为空']);
        }

        try {
            // 使用智能地址解析服务
            $result = \app\common\service\AddressParseService::parse($address);
            
            return json([
                'code' => 1,
                'msg' => '解析成功',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            \think\facade\Log::error('地址解析失败: ' . $e->getMessage());
            return json(['code' => 0, 'msg' => '解析失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 简化版地址解析（不加载完整数据，速度更快）
     * POST /index/shop/parseAddressSimple
     */
    public function parseAddressSimple()
    {
        if (!request()->isPost()) {
            return json(['code' => 0, 'msg' => '请求方式错误']);
        }

        $address = input('address', '');
        if (empty($address)) {
            return json(['code' => 0, 'msg' => '地址不能为空']);
        }

        try {
            $result = \app\common\service\AddressParseService::parseSimple($address);
            
            return json([
                'code' => 1,
                'msg' => '解析成功',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '解析失败']);
        }
    }
}
