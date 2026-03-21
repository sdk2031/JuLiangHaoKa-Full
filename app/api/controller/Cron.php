<?php
namespace app\api\controller;

use think\facade\Db;
use app\common\service\ImageTemplateService;

/**
 * 统一定时任务调度控制器🆕
 */
class Cron
{
    private function getApiDisplayName($apiType, $remark)
    {
        $apiNames = array(
            'hao172' => '172号卡',
            'mf58' => '58秒返',
            'lanchang' => '蓝畅号卡',
            'haoteam' => '号卡极团',
            'haoky' => '卡业联盟',
            'haoy' => '号易',
            'tiancheng' => '天城智控',
            'longbao' => '龙宝',
            'jikeyun' => '极客云',
            'guangmengyun' => '广梦云',
            'gchk' => '共创号卡',
            'jlcloud' => '巨量互联',
            'gth91' => '91敢探号',
        );
        
        $name = isset($apiNames[$apiType]) ? $apiNames[$apiType] : $apiType;
        if ($remark) {
            return $name . '(' . $remark . ')';
        }
        return $name;
    }

    private function getSecurityKey()
    {
        try {
            $value = Db::name('system_config')->where('config_key', 'security_key')->value('config_value');
            if ($value) {
                return $value;
            }
            return '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * 统一调度入口
     */
    public function run()
    {
        $securityKey = input('security_key', '');
        $configKey = $this->getSecurityKey();
        
        if (empty($securityKey) || $securityKey !== $configKey) {
            return json(array('code' => 1, 'msg' => '安全密钥验证失败'));
        }

        $debug = input('debug', 0);
        $debugInfo = array();
        $pendingTasks = array();

        ignore_user_abort(true);
        set_time_limit(0);

        $results = array();
        $now = date('Y-m-d H:i:s');

        // 每月1号0点0分重置月度统计
        if (date('d') === '01' && date('H:i') === '00:00') {
            try {
                \app\common\helper\AgentStatsHelper::resetMonthlyStats();
                $results[] = array(
                    'api' => '系统',
                    'task' => 'reset_monthly_stats',
                    'result' => '代理和店铺月度统计已重置'
                );
            } catch (\Exception $e) {
                $results[] = array(
                    'api' => '系统',
                    'task' => 'reset_monthly_stats',
                    'result' => '重置失败: ' . $e->getMessage()
                );
            }
        }
        
        // 每天0点0分重置今日统计
        if (date('H:i') === '00:00') {
            try {
                \app\common\helper\AgentStatsHelper::resetDailyStats();
                $results[] = array(
                    'api' => '系统',
                    'task' => 'reset_daily_stats',
                    'result' => '店铺今日统计已重置'
                );
            } catch (\Exception $e) {
                $results[] = array(
                    'api' => '系统',
                    'task' => 'reset_daily_stats',
                    'result' => '重置失败: ' . $e->getMessage()
                );
            }
        }

        // 每小时执行一次商品图片生成任务
        if (date('i') === '30') {
            try {
                $imageResult = $this->runImageGenerate();
                $results[] = array(
                    'api' => '系统',
                    'task' => 'generate_images',
                    'result' => $imageResult
                );
            } catch (\Exception $e) {
                $results[] = array(
                    'api' => '系统',
                    'task' => 'generate_images',
                    'result' => '生成失败: ' . $e->getMessage()
                );
            }
        }

        // 每分钟执行回调重试任务
        try {
            $callbackResult = $this->runCallbackRetry();
            if ($callbackResult['processed'] > 0) {
                $results[] = array(
                    'api' => '系统',
                    'task' => 'callback_retry',
                    'result' => "处理 {$callbackResult['processed']} 个回调，成功 {$callbackResult['success']} 个"
                );
            }
        } catch (\Exception $e) {
            $results[] = array(
                'api' => '系统',
                'task' => 'callback_retry',
                'result' => '回调重试失败: ' . $e->getMessage()
            );
        }

        $configs = Db::name('config_api')->where('status', 1)->select()->toArray();

        foreach ($configs as $config) {
            $apiType = $config['api_type'];
            $configId = $config['id'];
            $remark = isset($config['remark']) ? $config['remark'] : '';
            $displayName = $this->getApiDisplayName($apiType, $remark);
            
            $productSyncEnabled = isset($config['product_sync_enabled']) ? $config['product_sync_enabled'] : 0;
            $productLastTime = isset($config['product_sync_last_time']) ? $config['product_sync_last_time'] : null;
            $productInterval = isset($config['product_sync_interval']) ? $config['product_sync_interval'] : 60;
            $productSyncType = isset($config['product_sync_type']) ? $config['product_sync_type'] : 'light';
            $productShouldRun = $this->shouldRun($productLastTime, $productInterval);
            $productWillExecute = $productSyncEnabled && $productShouldRun;
            
            if ($debug) {
                $syncTypeNames = array('light' => '轻量同步', 'full' => '全量同步', 'online' => '已上架同步');
                $syncTypeName = isset($syncTypeNames[$productSyncType]) ? $syncTypeNames[$productSyncType] : $productSyncType;
                $debugInfo[] = array(
                    'api' => $displayName,
                    'config_id' => $configId,
                    'task' => '商品同步',
                    'sync_type' => $syncTypeName,
                    'enabled' => $productSyncEnabled ? '✓开启' : '✗关闭',
                    'last_time' => $productLastTime ? $productLastTime : '从未执行',
                    'interval' => $productInterval . '分钟',
                    'should_run' => $productShouldRun ? '✓到期' : '✗未到期',
                    'will_execute' => $productWillExecute ? '✓执行' : '✗跳过'
                );
                
                if ($productWillExecute) {
                    $pendingTasks[] = '[商品同步] ' . $displayName . ' - ' . $syncTypeName;
                }
            }
            
            if ($productWillExecute) {
                $result = $this->runProductSync($config);
                $results[] = array(
                    'api' => $displayName,
                    'task' => 'product_sync',
                    'result' => $result
                );
            }

            $orderSyncEnabled = isset($config['order_sync_enabled']) ? $config['order_sync_enabled'] : 0;
            $orderLastTime = isset($config['order_sync_last_time']) ? $config['order_sync_last_time'] : null;
            $orderInterval = isset($config['order_sync_interval']) ? $config['order_sync_interval'] : 10;
            $orderLimit = isset($config['order_sync_limit']) ? $config['order_sync_limit'] : 1000;
            $orderDays = isset($config['order_sync_days']) ? $config['order_sync_days'] : 120;
            $orderShouldRun = $this->shouldRun($orderLastTime, $orderInterval);
            $orderWillExecute = $orderSyncEnabled && $orderShouldRun;
            
            $callbackOnlyApis = array('lanchang', 'haoky', 'haoy', 'guangmengyun');
            $isCallbackOnly = in_array($apiType, $callbackOnlyApis);
            
            if ($debug) {
                $debugInfo[] = array(
                    'api' => $displayName,
                    'config_id' => $configId,
                    'task' => '订单查询',
                    'params' => '最近' . $orderDays . '天, 限' . $orderLimit . '条',
                    'enabled' => $orderSyncEnabled ? '✓开启' : '✗关闭',
                    'last_time' => $orderLastTime ? $orderLastTime : '从未执行',
                    'interval' => $orderInterval . '分钟',
                    'should_run' => $orderShouldRun ? '✓到期' : '✗未到期',
                    'callback_only' => $isCallbackOnly ? '✓仅回调' : '✗支持查询',
                    'will_execute' => ($orderWillExecute && !$isCallbackOnly) ? '✓执行' : '✗跳过'
                );
                
                if ($orderWillExecute && !$isCallbackOnly) {
                    $pendingTasks[] = '[订单查询] ' . $displayName . ' - 最近' . $orderDays . '天, 限' . $orderLimit . '条';
                }
            }
            
            if ($orderWillExecute) {
                $result = $this->runOrderSync($config);
                $results[] = array(
                    'api' => $displayName,
                    'task' => 'order_sync',
                    'result' => $result
                );
            }
        }

        $response = array(
            'code' => 0,
            'msg' => '调度完成',
            'executed_count' => count($results),
            'data' => $results,
            'time' => $now
        );
        
        if (count($results) > 0) {
            $logLines = array('[Cron] 调度完成，执行了 ' . count($results) . ' 个任务:');
            foreach ($results as $r) {
                $taskName = ($r['task'] == 'product_sync') ? '商品同步' : '订单查询';
                $logLines[] = '  - [' . $taskName . '] ' . $r['api'] . ': ' . $r['result'];
            }
            trace(implode("\n", $logLines), 'error');
        }
        
        if ($debug) {
            $response['pending_tasks'] = $pendingTasks;
            $response['pending_count'] = count($pendingTasks);
            $response['debug'] = $debugInfo;
        }
        
        return json($response);
    }

    /**
     * 判断是否应该执行
     */
    private function shouldRun($lastTime, $intervalMinutes)
    {
        if (empty($lastTime)) {
            return true;
        }
        
        $lastTimestamp = strtotime($lastTime);
        $nextTimestamp = $lastTimestamp + ($intervalMinutes * 60);
        
        return time() >= $nextTimestamp;
    }

    /**
     * 执行商品同步
     */
    private function runProductSync($config)
    {
        $apiType = $config['api_type'];
        $syncType = isset($config['product_sync_type']) ? $config['product_sync_type'] : 'light';
        $configId = $config['id'];
        $shopType = isset($config['product_shop_type']) ? $config['product_shop_type'] : 0;
        $filterKeywords = isset($config['product_filter_keywords']) ? $config['product_filter_keywords'] : '';

        try {
            $result = '';
            
            switch ($apiType) {
                case 'hao172':
                    $result = $this->callProductSync('hao172', $syncType);
                    break;
                case 'mf58':
                    $result = $this->callProductSync('mf58', $syncType);
                    break;
                case 'lanchang':
                    $result = $this->callProductSyncLanchang($syncType, $shopType);
                    break;
                case 'haoteam':
                    $result = $this->callProductSyncWithConfig('haoteam', $syncType, $configId);
                    break;
                case 'haoky':
                    $result = $this->callProductSync('haoky', $syncType);
                    break;
                case 'haoy':
                    $result = $this->callProductSync('haoy', $syncType);
                    break;
                case 'tiancheng':
                    $result = $this->callProductSync('tiancheng', $syncType);
                    break;
                case 'longbao':
                    $result = $this->callProductSync('longbao', $syncType);
                    break;
                case 'jikeyun':
                    $result = $this->callProductSyncWithConfig('jikeyun', $syncType, $configId);
                    break;
                case 'guangmengyun':
                    $result = $this->callProductSyncWithConfig('guangmengyun', $syncType, $configId);
                    break;
                case 'gchk':
                    $result = $this->callProductSync('gchk', $syncType);
                    break;
                case 'jlcloud':
                    $result = $this->callProductSyncWithConfig('jlcloud', $syncType, $configId);
                    break;
                case 'gth91':
                    $result = $this->callProductSync('gth91', $syncType);
                    break;
                default:
                    $result = '不支持的API类型';
            }

            // 根据过滤关键词下架匹配的产品
            $filterCount = 0;
            if (!empty($filterKeywords)) {
                $filterCount = $this->filterProductsByKeywords($configId, $apiType, $filterKeywords);
                if ($filterCount > 0) {
                    $result .= "，过滤下架 {$filterCount} 个";
                }
            }

            // 同步完成后自动转图（如果开启了转图功能）
            $imageResult = $this->autoGenerateImagesAfterSync();
            if ($imageResult) {
                $result .= "，" . $imageResult;
            }

            Db::name('config_api')->where('id', $configId)->update(array(
                'product_sync_last_time' => date('Y-m-d H:i:s'),
                'product_sync_result' => mb_substr($result, 0, 500)
            ));

            return $result;

        } catch (\Exception $e) {
            $errorMsg = '执行异常: ' . $e->getMessage();
            Db::name('config_api')->where('id', $configId)->update(array(
                'product_sync_last_time' => date('Y-m-d H:i:s'),
                'product_sync_result' => $errorMsg
            ));
            return $errorMsg;
        }
    }

    /**
     * 根据关键词过滤下架产品
     */
    private function filterProductsByKeywords($configId, $apiType, $keywords)
    {
        if (empty($keywords)) {
            return 0;
        }

        // 清理关键词中的特殊字符（BOM、零宽字符等）
        $keywords = preg_replace('/[\x00-\x1F\x7F]/u', '', $keywords);
        $keywords = trim($keywords);
        
        if (empty($keywords)) {
            return 0;
        }

        // 解析关键词（支持中英文逗号分隔）
        $keywordList = preg_split('/[,，]/u', $keywords);
        $keywordList = array_map('trim', $keywordList);
        $keywordList = array_filter($keywordList, function($k) {
            // 确保关键词至少有1个字符
            return mb_strlen($k) >= 1;
        });
        $keywordList = array_values($keywordList); // 重新索引

        if (empty($keywordList)) {
            return 0;
        }

        $filterCount = 0;
        
        // 查询该配置下所有上架的产品（同时使用api_config_id和api_name精确匹配）
        $query = Db::name('product')
            ->where('status', 1)
            ->field('id, name');
        
        // 多参数配置使用api_config_id，单参数配置使用api_name
        if ($configId > 0) {
            $query->where('api_config_id', $configId);
        } else {
            $query->where('api_name', $apiType);
        }
        
        $products = $query->select();

        foreach ($products as $product) {
            $productName = $product['name'];
            foreach ($keywordList as $keyword) {
                if (stripos($productName, $keyword) !== false) {
                    // 产品名包含关键词，下架
                    Db::name('product')->where('id', $product['id'])->update(array(
                        'status' => 0,
                        'update_time' => date('Y-m-d H:i:s')
                    ));
                    $filterCount++;
                    break;
                }
            }
        }

        return $filterCount;
    }

    /**
     * 调用商品同步方法
     */
    private function callProductSync($apiType, $syncType)
    {
        $className = 'app\\api\\controller\\kapi\\' . $apiType . '\\Product';
        
        if (!class_exists($className)) {
            return 'Product类不存在: ' . $className;
        }

        $product = new $className();
        
        switch ($syncType) {
            case 'full':
                $method = 'sync';
                break;
            case 'online':
                $method = 'autoSyncOnlineProducts';
                if (!method_exists($product, $method)) {
                    $method = 'lightSync';
                }
                break;
            case 'light':
            default:
                $method = 'lightSync';
                break;
        }

        if (!method_exists($product, $method)) {
            return '方法不存在: ' . $method;
        }

        $response = $product->$method();
        
        if (is_object($response) && method_exists($response, 'getContent')) {
            $data = json_decode($response->getContent(), true);
            $msg = isset($data['msg']) ? $data['msg'] : '执行完成';
            return $msg;
        }
        
        return '执行完成';
    }

    /**
     * 调用商品同步方法(带配置ID)
     */
    private function callProductSyncWithConfig($apiType, $syncType, $configId)
    {
        $className = 'app\\api\\controller\\kapi\\' . $apiType . '\\Product';
        
        if (!class_exists($className)) {
            return 'Product类不存在: ' . $className;
        }

        $product = new $className();
        
        $_POST['config_id'] = $configId;
        $_POST['from_admin'] = '1';
        
        switch ($syncType) {
            case 'full':
                $method = 'sync';
                break;
            case 'online':
                $method = 'autoSyncOnlineProducts';
                if (!method_exists($product, $method)) {
                    $method = 'lightSync';
                }
                break;
            case 'light':
            default:
                $method = 'lightSync';
                break;
        }

        if (!method_exists($product, $method)) {
            return '方法不存在: ' . $method;
        }

        $response = $product->$method();
        
        if (is_object($response) && method_exists($response, 'getContent')) {
            $data = json_decode($response->getContent(), true);
            $msg = isset($data['msg']) ? $data['msg'] : '执行完成';
            return $msg;
        }
        
        return '执行完成';
    }

    /**
     * 蓝畅专用商品同步
     */
    private function callProductSyncLanchang($syncType, $shopType)
    {
        $className = 'app\\api\\controller\\kapi\\lanchang\\Product';
        
        if (!class_exists($className)) {
            return 'Product类不存在: ' . $className;
        }

        $product = new $className();
        $_POST['shop_type'] = $shopType;
        
        switch ($syncType) {
            case 'full':
                $method = 'sync';
                break;
            case 'online':
                $method = 'autoSyncOnlineProducts';
                if (!method_exists($product, $method)) {
                    $method = 'lightSync';
                }
                break;
            case 'light':
            default:
                $method = 'lightSync';
                break;
        }

        if (!method_exists($product, $method)) {
            return '方法不存在: ' . $method;
        }

        $response = $product->$method();
        
        if (is_object($response) && method_exists($response, 'getContent')) {
            $data = json_decode($response->getContent(), true);
            $msg = isset($data['msg']) ? $data['msg'] : '执行完成';
            return $msg;
        }
        
        return '执行完成';
    }

    /**
     * 执行订单查询
     */
    private function runOrderSync($config)
    {
        $apiType = $config['api_type'];
        $configId = $config['id'];
        $limit = isset($config['order_sync_limit']) ? $config['order_sync_limit'] : 1000;
        $days = isset($config['order_sync_days']) ? $config['order_sync_days'] : 120;

        try {
            $result = $this->callOrderSync($apiType, $configId, $limit, $days);

            Db::name('config_api')->where('id', $configId)->update(array(
                'order_sync_last_time' => date('Y-m-d H:i:s'),
                'order_sync_result' => mb_substr($result, 0, 500)
            ));

            return $result;

        } catch (\Exception $e) {
            $errorMsg = '执行异常: ' . $e->getMessage();
            Db::name('config_api')->where('id', $configId)->update(array(
                'order_sync_last_time' => date('Y-m-d H:i:s'),
                'order_sync_result' => $errorMsg
            ));
            return $errorMsg;
        }
    }

    /**
     * 调用订单查询方法
     */
    private function callOrderSync($apiType, $configId, $limit, $days)
    {
        $callbackOnlyApis = array('lanchang', 'haoky', 'haoy', 'gth91');
        if (in_array($apiType, $callbackOnlyApis)) {
            return '该API仅支持回调模式，无需主动查询';
        }

        $classNames = array(
            'app\\api\\controller\\kapi\\' . $apiType . '\\Chaorder',
            'app\\api\\controller\\kapi\\' . $apiType . '\\Order'
        );

        $instance = null;
        foreach ($classNames as $className) {
            if (class_exists($className)) {
                $instance = new $className();
                break;
            }
        }

        if (!$instance) {
            return '订单查询类不存在或授权失败';
        }

        $_POST['config_id'] = $configId;
        $_GET['config_id'] = $configId;
        $_REQUEST['config_id'] = $configId;
        $_POST['limit'] = $limit;
        $_GET['limit'] = $limit;
        $_REQUEST['limit'] = $limit;
        $_POST['days'] = $days;
        $_GET['days'] = $days;
        $_REQUEST['days'] = $days;

        $methodsToTry = array('syncOrders', 'quickSyncNewOrders', 'autoSyncStatus', 'batchQuery', 'batchQueryOrder', 'queryPendingOrders', 'query');
        $skipAuthMethods = array('syncOrders', 'quickSyncNewOrders', 'autoSyncStatus');
        
        foreach ($methodsToTry as $method) {
            if (!method_exists($instance, $method)) {
                continue;
            }
            try {
                $skipAuth = in_array($method, $skipAuthMethods);
                if ($skipAuth) {
                    $response = call_user_func(array($instance, $method), true);
                } else {
                    $response = call_user_func(array($instance, $method));
                }
                
                if (is_object($response) && method_exists($response, 'getContent')) {
                    $data = json_decode($response->getContent(), true);
                    $msg = isset($data['msg']) ? $data['msg'] : '查询完成';
                    return $msg;
                }
                
                return '查询完成';
            } catch (\Exception $e) {
                // 方法存在但执行出错
                return '查询异常: ' . $e->getMessage();
            }
        }

        return '未找到订单查询方法';
    }

    /**
     * 手动触发商品同步
     */
    public function triggerProductSync()
    {
        $configId = input('config_id', 0);
        
        if (empty($configId)) {
            return json(array('code' => 1, 'msg' => '参数错误'));
        }

        $config = Db::name('config_api')->where('id', $configId)->find();
        if (!$config) {
            return json(array('code' => 1, 'msg' => '配置不存在'));
        }

        $result = $this->runProductSync($config);
        
        return json(array('code' => 0, 'msg' => $result));
    }

    /**
     * 手动触发订单查询
     */
    public function triggerOrderSync()
    {
        $configId = input('config_id', 0);
        
        if (empty($configId)) {
            return json(array('code' => 1, 'msg' => '参数错误'));
        }

        $config = Db::name('config_api')->where('id', $configId)->find();
        if (!$config) {
            return json(array('code' => 1, 'msg' => '配置不存在'));
        }

        $result = $this->runOrderSync($config);
        
        return json(array('code' => 0, 'msg' => $result));
    }

    /**
     * 重置代理月度统计
     */
    public function resetAgentMonthlyStats()
    {
        $securityKey = input('security_key', '');
        $configKey = $this->getSecurityKey();
        
        if (empty($securityKey) || $securityKey !== $configKey) {
            return json(array('code' => 1, 'msg' => '安全密钥验证失败'));
        }

        try {
            $result = \app\common\helper\AgentStatsHelper::resetMonthlyStats();
            
            if ($result) {
                trace('[Cron] 代理月度统计已重置', 'error');
                return json(array('code' => 0, 'msg' => '代理月度统计重置成功', 'time' => date('Y-m-d H:i:s')));
            } else {
                return json(array('code' => 1, 'msg' => '重置失败'));
            }
        } catch (\Exception $e) {
            trace('[Cron] 代理月度统计重置异常: ' . $e->getMessage(), 'error');
            return json(array('code' => 1, 'msg' => '重置异常: ' . $e->getMessage()));
        }
    }

    /**
     * 同步商品后自动生成图片
     * @return string|null 执行结果，未开启则返回null
     */
    private function autoGenerateImagesAfterSync()
    {
        // 检查是否有激活的模板（转图功能是否开启）
        $template = ImageTemplateService::getActiveTemplate();
        if (!$template) {
            return null; // 未开启转图功能，静默跳过
        }

        // 检查是否开启了API同步后自动转图
        if (empty($template['api_auto_generate'])) {
            return null; // 未开启API自动转图，静默跳过
        }

        // 获取已生成图片的商品ID
        $generatedIds = Db::name('product_custom_image')
            ->where('template_id', $template['id'])
            ->column('product_id');

        // 查询未生成图片的上架商品
        $query = Db::name('product')
            ->where('status', 1)
            ->order('id asc')
            ->limit(200); // 每次最多处理200个

        if (!empty($generatedIds)) {
            $query->whereNotIn('id', $generatedIds);
        }

        $products = $query->select()->toArray();

        if (empty($products)) {
            return null; // 没有待生成的商品
        }

        $success = 0;
        $fail = 0;

        foreach ($products as $product) {
            try {
                $result = ImageTemplateService::generateImage($product, $template['id'], false);
                if ($result) {
                    $success++;
                } else {
                    $fail++;
                }
            } catch (\Exception $e) {
                $fail++;
            }
        }

        if ($success > 0 || $fail > 0) {
            return "转图 {$success} 成功" . ($fail > 0 ? " {$fail} 失败" : "");
        }

        return null;
    }

    /**
     * 执行商品图片生成任务
     * @param int $limit 每次生成数量限制
     * @return string 执行结果
     */
    private function runImageGenerate($limit = 100)
    {
        // 获取当前激活的模板
        $template = ImageTemplateService::getActiveTemplate();
        if (!$template) {
            return '没有激活的图片模板';
        }

        // 获取已生成图片的商品ID
        $generatedIds = Db::name('product_custom_image')
            ->where('template_id', $template['id'])
            ->column('product_id');

        // 查询未生成图片的商品
        $query = Db::name('product')
            ->where('status', 1)
            ->order('id asc')
            ->limit($limit);

        if (!empty($generatedIds)) {
            $query->whereNotIn('id', $generatedIds);
        }

        $products = $query->select()->toArray();

        if (empty($products)) {
            return '没有待生成的商品';
        }

        $success = 0;
        $fail = 0;

        foreach ($products as $product) {
            try {
                $result = ImageTemplateService::generateImage($product, $template['id'], false);
                if ($result) {
                    $success++;
                } else {
                    $fail++;
                }
            } catch (\Exception $e) {
                $fail++;
            }
        }

        $msg = "图片生成完成：成功 {$success} 个，失败 {$fail} 个";
        trace('[Cron] ' . $msg, 'error');
        return $msg;
    }

    /**
     * 手动触发商品图片生成
     */
    public function triggerImageGenerate()
    {
        $securityKey = input('security_key', '');
        $configKey = $this->getSecurityKey();
        
        if (empty($securityKey) || $securityKey !== $configKey) {
            return json(array('code' => 1, 'msg' => '安全密钥验证失败'));
        }

        $limit = input('limit', 500);
        $force = input('force', 0);
        $yys = input('yys', '');

        try {
            // 获取当前激活的模板
            $template = ImageTemplateService::getActiveTemplate();
            if (!$template) {
                return json(array('code' => 1, 'msg' => '没有激活的图片模板'));
            }

            // 构建查询条件
            $query = Db::name('product')
                ->where('status', 1)
                ->order('id asc')
                ->limit($limit);

            if ($yys) {
                $query->where('yys', $yys);
            }

            // 如果不是强制模式，只查询未生成图片的商品
            if (!$force) {
                $generatedIds = Db::name('product_custom_image')
                    ->where('template_id', $template['id'])
                    ->column('product_id');

                if (!empty($generatedIds)) {
                    $query->whereNotIn('id', $generatedIds);
                }
            }

            $products = $query->select()->toArray();

            if (empty($products)) {
                return json(array('code' => 0, 'msg' => '没有待生成的商品'));
            }

            $success = 0;
            $fail = 0;

            foreach ($products as $product) {
                try {
                    $result = ImageTemplateService::generateImage($product, $template['id'], $force);
                    if ($result) {
                        $success++;
                    } else {
                        $fail++;
                    }
                } catch (\Exception $e) {
                    $fail++;
                }
            }

            $msg = "图片生成完成：成功 {$success} 个，失败 {$fail} 个";
            trace('[Cron] 手动触发 - ' . $msg, 'error');
            
            return json(array(
                'code' => 0,
                'msg' => $msg,
                'data' => array(
                    'success' => $success,
                    'fail' => $fail,
                    'template' => $template['name']
                )
            ));
        } catch (\Exception $e) {
            return json(array('code' => 1, 'msg' => '执行异常: ' . $e->getMessage()));
        }
    }

    /**
     * 执行回调重试任务
     * @return array 执行结果
     */
    private function runCallbackRetry()
    {
        $result = array(
            'processed' => 0,
            'success' => 0,
            'pending' => 0
        );

        try {
            // 检查partner_callbacks表是否存在
            $tableExists = Db::query("SHOW TABLES LIKE 'partner_callbacks'");
            if (empty($tableExists)) {
                return $result;
            }

            // 快速检查是否有需要处理的回调
            $pendingCount = Db::name('partner_callbacks')
                ->whereIn('status', ['pending', 'failed'])
                ->where('retry_count', '<', 5)
                ->where('next_retry_time', '<=', time())
                ->whereNotNull('next_retry_time')
                ->count();

            if ($pendingCount == 0) {
                return $result;
            }

            $result['pending'] = $pendingCount;

            // 查询需要重试的回调记录
            $callbacks = Db::name('partner_callbacks')
                ->whereIn('status', ['pending', 'failed'])
                ->where('retry_count', '<', 5)
                ->where('next_retry_time', '<=', time())
                ->whereNotNull('next_retry_time')
                ->order('next_retry_time', 'asc')
                ->limit(20)
                ->select()
                ->toArray();

            foreach ($callbacks as $callback) {
                $callbackResult = $this->processCallback($callback);
                $result['processed']++;
                
                if ($callbackResult) {
                    $result['success']++;
                }
                
                // 避免过于频繁的请求
                usleep($callbackResult ? 50000 : 200000);
            }

            // 清理超过7天的失败回调记录
            $cleanupTime = time() - (7 * 24 * 60 * 60);
            Db::name('partner_callbacks')
                ->where('status', 'failed')
                ->where('retry_count', '>=', 5)
                ->where('updated_time', '<', $cleanupTime)
                ->delete();

        } catch (\Exception $e) {
            trace('[Cron] 回调重试异常: ' . $e->getMessage(), 'error');
        }

        return $result;
    }

    /**
     * 处理单个回调
     */
    private function processCallback($callback)
    {
        try {
            // 解析回调数据
            $callbackData = json_decode($callback['callback_data'], true);
            if (!$callbackData) {
                throw new \Exception('回调数据格式错误');
            }

            // 确保回调数据中包含agent_id（签名需要）
            if (empty($callbackData['agent_id']) && !empty($callback['agent_id'])) {
                $callbackData['agent_id'] = $callback['agent_id'];
            }

            // 发送回调
            $sendResult = $this->sendCallback($callback['callback_url'], $callbackData);

            // 更新回调记录
            $updateData = array(
                'updated_time' => time(),
                'error_msg' => isset($sendResult['error']) ? $sendResult['error'] : '',
                'response_code' => isset($sendResult['http_code']) ? $sendResult['http_code'] : null,
                'response_data' => isset($sendResult['response_data']) ? $sendResult['response_data'] : null
            );

            if ($sendResult['success']) {
                // 回调成功
                $updateData['status'] = 'success';
                $updateData['next_retry_time'] = null;
            } else {
                // 回调失败，增加重试次数
                $newRetryCount = $callback['retry_count'] + 1;

                if ($newRetryCount >= 5) {
                    // 达到最大重试次数
                    $updateData['status'] = 'failed';
                    $updateData['retry_count'] = $newRetryCount;
                    $updateData['next_retry_time'] = null;
                } else {
                    // 设置下次重试时间
                    $updateData['retry_count'] = $newRetryCount;
                    $updateData['next_retry_time'] = $this->calculateNextRetryTime($newRetryCount);
                }
            }

            Db::name('partner_callbacks')->where('id', $callback['id'])->update($updateData);

            return $sendResult['success'];

        } catch (\Exception $e) {
            Db::name('partner_callbacks')->where('id', $callback['id'])->update(array(
                'error_msg' => $e->getMessage(),
                'updated_time' => time()
            ));
            return false;
        }
    }

    /**
     * 计算下次重试时间
     */
    private function calculateNextRetryTime($retryCount)
    {
        $delays = array(1, 2, 5, 10, 30); // 分钟
        $delayIndex = min($retryCount - 1, count($delays) - 1);
        $delayMinutes = $delays[$delayIndex];

        return time() + ($delayMinutes * 60);
    }

    /**
     * 发送回调请求
     */
    private function sendCallback($url, $data)
    {
        $startTime = microtime(true);

        try {
            // 生成签名（4个参数：agent_id, order_no, partner_order_no, timestamp）
            if (!empty($data['order_no'])) {
                $secretKey = \app\common\service\OrderCallbackService::getAgentSecretKey($data['order_no']);
                $signData = array(
                    'agent_id' => isset($data['agent_id']) ? $data['agent_id'] : '',
                    'order_no' => isset($data['order_no']) ? $data['order_no'] : '',
                    'partner_order_no' => isset($data['partner_order_no']) ? $data['partner_order_no'] : '',
                    'timestamp' => isset($data['timestamp']) ? $data['timestamp'] : time()
                );
                ksort($signData);
                $signString = '';
                foreach ($signData as $key => $value) {
                    $signString .= $key . '=' . $value . '&';
                }
                $signString = rtrim($signString, '&') . $secretKey;
                $data['sign'] = strtoupper(md5($signString));
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/x-www-form-urlencoded',
                'User-Agent: OrderCallback/1.0'
            ));

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            $responseTime = round((microtime(true) - $startTime) * 1000);

            if ($error) {
                return array(
                    'success' => false,
                    'error' => 'CURL Error: ' . $error,
                    'http_code' => 0,
                    'response_time' => $responseTime,
                    'response_data' => ''
                );
            }

            // 检查HTTP状态码和响应内容
            // 成功条件：HTTP 200 且响应包含 success/ok/true 等常见成功标识
            $responseBody = strtolower(trim($response));
            $success = ($httpCode == 200 && (
                $responseBody === 'ok' ||
                $responseBody === 'success' ||
                stripos($response, 'success') !== false ||
                stripos($response, '"code":0') !== false ||
                stripos($response, '"code": 0') !== false
            ));

            return array(
                'success' => $success,
                'error' => $success ? '' : "HTTP {$httpCode}: " . substr($response, 0, 200),
                'http_code' => $httpCode,
                'response_time' => $responseTime,
                'response_data' => substr($response, 0, 500)
            );

        } catch (\Exception $e) {
            return array(
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage(),
                'http_code' => 0,
                'response_time' => round((microtime(true) - $startTime) * 1000),
                'response_data' => ''
            );
        }
    }

    /**
     * 手动触发回调重试
     * GET /api/cron/triggerCallbackRetry?security_key=xxx
     */
    public function triggerCallbackRetry()
    {
        $securityKey = input('security_key', '');
        $configKey = $this->getSecurityKey();

        if (empty($securityKey) || $securityKey !== $configKey) {
            return json(array('code' => 1, 'msg' => '安全密钥验证失败'));
        }

        try {
            $result = $this->runCallbackRetry();

            return json(array(
                'code' => 0,
                'msg' => '执行完成',
                'data' => $result,
                'time' => date('Y-m-d H:i:s')
            ));
        } catch (\Exception $e) {
            return json(array('code' => 1, 'msg' => '执行失败: ' . $e->getMessage()));
        }
    }

    /**
     * 查看回调任务状态
     * GET /api/cron/callbackStatus?security_key=xxx
     */
    public function callbackStatus()
    {
        $securityKey = input('security_key', '');
        $configKey = $this->getSecurityKey();

        if (empty($securityKey) || $securityKey !== $configKey) {
            return json(array('code' => 1, 'msg' => '安全密钥验证失败'));
        }

        try {
            // 检查表是否存在
            $tableExists = Db::query("SHOW TABLES LIKE 'partner_callbacks'");
            if (empty($tableExists)) {
                return json(array(
                    'code' => 0,
                    'msg' => '回调表不存在',
                    'data' => array()
                ));
            }

            // 统计回调状态（最近24小时）
            $stats = Db::name('partner_callbacks')
                ->where('created_time', '>', time() - 86400)
                ->field('status, COUNT(*) as count')
                ->group('status')
                ->select()
                ->toArray();

            // 待重试的回调数量
            $pendingCount = Db::name('partner_callbacks')
                ->where('status', 'failed')
                ->where('retry_count', '<', 5)
                ->where('next_retry_time', '<=', time())
                ->count();

            return json(array(
                'code' => 0,
                'msg' => '获取成功',
                'data' => array(
                    'stats' => $stats,
                    'pending_retry_count' => $pendingCount,
                    'current_time' => date('Y-m-d H:i:s')
                )
            ));

        } catch (\Exception $e) {
            return json(array('code' => 1, 'msg' => '获取失败: ' . $e->getMessage()));
        }
    }
}