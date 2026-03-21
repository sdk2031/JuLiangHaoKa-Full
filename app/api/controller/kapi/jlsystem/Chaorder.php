<?php
namespace app\api\controller\kapi\jlsystem;

use think\facade\Db;
use app\common\helper\PluginHelper;
use app\common\service\OrderCallbackService;

/**
 * 同系统API订单状态查询
 */
class Chaorder
{
    public function __construct()
    {
        PluginHelper::check('jlsystem');
    }

    protected function success($msg = '操作成功', $data = [], $code = 1)
    {
        return json(['code' => $code, 'msg' => $msg, 'data' => $data, 'time' => time()]);
    }

    protected function error($msg = '操作失败', $data = [], $code = 0)
    {
        return json(['code' => $code, 'msg' => $msg, 'data' => $data, 'time' => time()]);
    }

    /**
     * 获取配置ID
     */
    private function getConfigId()
    {
        return input('config_id', input('post.config_id', input('get.config_id', 0)));
    }

    /**
     * 批量查询订单状态
     */
    public function batchQuery()
    {
        $orderNos = input('post.orderNos', []);
        if (empty($orderNos) || !is_array($orderNos)) {
            return $this->error('订单号列表不能为空');
        }

        $configId = $this->getConfigId();
        $config = Config::getConfig($configId);
        if (!$config || empty($config['api_key']) || empty($config['api_secret'])) {
            return $this->error('请先配置同系统API');
        }

        $localOrders = Db::name('order')
            ->where('order_no', 'in', $orderNos)
            ->where('api_config_id', $config['id'])
            ->select()
            ->toArray();

        if (empty($localOrders)) {
            return $this->error('没有找到任何订单');
        }

        $queryResult = $this->batchQueryOrders($localOrders, $config);

        $results = [];
        foreach ($orderNos as $orderNo) {
            $found = false;
            foreach ($localOrders as $order) {
                if ($order['order_no'] == $orderNo) {
                    $results[$orderNo] = ['success' => true, 'message' => '查询完成'];
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $results[$orderNo] = ['success' => false, 'message' => '订单不存在'];
            }
        }

        return $this->success('批量查询完成，成功 ' . $queryResult['success'] . ' 个，失败 ' . $queryResult['error'] . ' 个', $results);
    }

    /**
     * 统一订单同步方法（定时任务调用）
     */
    public function syncOrders($skipAuth = false)
    {
        try {
            $days = intval(input('param.days', 120));
            if ($days <= 0 || $days > 365) $days = 120;

            $limit = intval(input('param.limit', 1000));
            if ($limit <= 0 || $limit > 10000) $limit = 1000;

            $configId = $this->getConfigId();
            $config = Config::getConfig($configId);
            if (!$config || empty($config['api_key']) || empty($config['api_secret'])) {
                return $this->error('同系统API配置不完整');
            }

            $startTime = date('Y-m-d H:i:s', time() - ($days * 24 * 3600));

            $orders = Db::name('order')
                ->where('api_config_id', $config['id'])
                ->where('create_time', '>=', $startTime)
                ->whereNotIn('order_status', ['5', '6', '7'])
                ->order('update_time', 'asc')
                ->limit($limit)
                ->select()
                ->toArray();

            if (empty($orders)) {
                return $this->success("没有需要同步的订单（查询范围：最近{$days}天）");
            }

            $result = $this->batchQueryOrders($orders, $config);

            return $this->success("订单同步完成，查询范围：最近{$days}天，共处理 {$result['total']} 个订单，成功 {$result['success']} 个，失败 {$result['error']} 个");

        } catch (\think\exception\HttpResponseException $e) {
            throw $e;
        } catch (\Exception $e) {
            return $this->error('订单同步异常：' . $e->getMessage());
        }
    }

    /**
     * 自动同步订单状态（兼容旧调用）
     */
    public function autoSyncStatus($skipAuth = false)
    {
        return $this->syncOrders($skipAuth);
    }

    /**
     * 批量查询订单
     */
    private function batchQueryOrders($orders, $config)
    {
        $totalCount = count($orders);
        $successCount = 0;
        $errorCount = 0;

        if (empty($orders)) {
            return ['total' => 0, 'success' => 0, 'error' => 0];
        }

        // 使用curl_multi并发请求
        $multiHandle = curl_multi_init();
        $curlHandles = [];
        $orderMap = [];

        $baseUrl = rtrim($config['api_url'], '/') . '/v1/order/query';

        foreach ($orders as $order) {
            $url = $baseUrl . '?partner_order_no=' . urlencode($order['order_no']);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Agent-Id: ' . $config['api_key'],
                'API-Key: ' . $config['api_secret']
            ]);

            curl_multi_add_handle($multiHandle, $ch);
            $curlHandles[(int)$ch] = $ch;
            $orderMap[(int)$ch] = $order;
        }

        // 执行并发请求
        $running = null;
        do {
            curl_multi_exec($multiHandle, $running);
            curl_multi_select($multiHandle, 0.1);
        } while ($running > 0);

        // 处理所有响应
        foreach ($curlHandles as $chId => $ch) {
            $order = $orderMap[$chId];

            try {
                $response = curl_multi_getcontent($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                if ($response !== false && $httpCode == 200) {
                    $result = json_decode($response, true);

                    if (isset($result['code']) && $result['code'] == 0 && isset($result['data'])) {
                        $apiData = $result['data'];
                        $updateData = $this->buildOrderUpdateData($apiData, $order, $config);

                        if (!empty($updateData)) {
                            $updateData['update_time'] = date('Y-m-d H:i:s');
                            Db::name('order')->where('id', $order['id'])->update($updateData);
                            $this->handlePostUpdateLogic($order, $updateData, $config);
                        }
                        $successCount++;
                    } else {
                        $errorCount++;
                    }
                } else {
                    $errorCount++;
                }
            } catch (\Exception $e) {
                $errorCount++;
                \think\facade\Log::error('同系统API批量查询异常 - 订单号: ' . ($order['order_no'] ?? '') . ', 错误: ' . $e->getMessage());
            }

            curl_multi_remove_handle($multiHandle, $ch);
            curl_close($ch);
        }

        curl_multi_close($multiHandle);

        return ['total' => $totalCount, 'success' => $successCount, 'error' => $errorCount];
    }

    /**
     * 构建订单更新数据
     */
    private function buildOrderUpdateData($apiData, $currentOrder, $config)
    {
        $updateData = [];

        // 更新快递信息
        if (!empty($apiData['express_company'])) {
            $updateData['express_company'] = $apiData['express_company'];
        }
        if (!empty($apiData['tracking_number'])) {
            $updateData['tracking_number'] = $apiData['tracking_number'];
        }

        // 更新生产号码
        if (!empty($apiData['production_number'])) {
            $updateData['production_number'] = $apiData['production_number'];
        }

        // 更新备注
        if (!empty($apiData['remark'])) {
            $updateData['remark'] = \app\common\helper\OrderRemarkHelper::append(
                $currentOrder['remark'] ?? '',
                $apiData['remark']
            );
        }

        // 映射订单状态（字段完全一致）
        if (isset($apiData['order_status'])) {
            $newStatus = (string)$apiData['order_status'];
            $oldStatus = (string)$currentOrder['order_status'];

            // 检查是否开启同步结算
            $syncSettlement = ($config['sync_settlement'] ?? 0) == 1;

            // 状态优先级保护
            if (in_array($oldStatus, ['5', '6'])) {
                // 已结算/结算失败的订单不再更新状态
            } elseif ($oldStatus === '4' && !in_array($newStatus, ['5', '6'])) {
                // 已激活的订单只能更新为结算相关状态
            } else {
                // 如果未开启同步结算，不更新为结算状态
                if (!$syncSettlement && in_array($newStatus, ['5', '6'])) {
                    // 不更新结算状态
                } else {
                    $updateData['order_status'] = $newStatus;

                    // 激活时设置激活时间（只在jh_time为空时设置）
                    if ($newStatus === '4' && $oldStatus !== '4' && empty($currentOrder['jh_time'])) {
                        $updateData['jh_time'] = date('Y-m-d H:i:s');
                    }

                    // 结算时设置结算时间
                    if ($newStatus === '5' && $oldStatus !== '5') {
                        $updateData['js_time'] = date('Y-m-d H:i:s');
                    }
                }
            }
        }

        // 同步佣金（如果开启）
        $syncCommission = ($config['sync_commission'] ?? 0) == 1;
        if ($syncCommission && isset($apiData['commission'])) {
            $updateData['commission'] = floatval($apiData['commission']);
        }

        // 同步激活时间（如果上游有）
        if (!empty($apiData['jh_time']) && empty($currentOrder['jh_time'])) {
            $updateData['jh_time'] = $apiData['jh_time'];
        }

        // 同步结算时间（如果上游有）
        if (!empty($apiData['js_time']) && empty($currentOrder['js_time'])) {
            $updateData['js_time'] = $apiData['js_time'];
        }

        return $updateData;
    }

    /**
     * 处理数据库更新后的业务逻辑
     */
    private function handlePostUpdateLogic($currentOrder, $updateData, $config)
    {
        // 获取同步结算开关状态
        $syncSettlementEnabled = ($config['sync_settlement'] ?? 0) == 1;

        // 触发回调通知
        if (isset($updateData['order_status']) && $updateData['order_status'] != $currentOrder['order_status']) {
            try {
                OrderCallbackService::triggerCallback($currentOrder['id'], $updateData['order_status'], '同系统API上游状态更新');
                \think\facade\Log::info('同系统API已触发回调通知: ' . $currentOrder['order_no'] . ' 状态=' . $updateData['order_status']);
            } catch (\Exception $e) {
                \think\facade\Log::error('同系统API回调通知失败: ' . $currentOrder['order_no'] . ' - ' . $e->getMessage());
            }
        }

        // 检查状态变更
        if (isset($updateData['order_status'])) {
            $oldStatus = $currentOrder['order_status'] ?? '';
            $newStatus = $updateData['order_status'];

            // 已激活时：始终更新代理统计，始终记录佣金（待结算状态）
            if ($newStatus === '4' && $oldStatus !== '4') {
                // 更新代理激活统计
                if (!empty($currentOrder['agent_id'])) {
                    \app\common\helper\AgentStatsHelper::incrementActivationStats($currentOrder['agent_id']);
                }
                // 记录佣金（待结算状态）
                $this->callActivatedRecordService($currentOrder);
            }

            // 已结算时触发佣金结算（需要开启同步结算开关）
            if ($newStatus === '5' && $oldStatus !== '5' && $syncSettlementEnabled) {
                $this->callCommissionService($currentOrder);
            }
        }
    }

    /**
     * 调用佣金结算服务
     */
    private function callCommissionService($order)
    {
        try {
            $commissionService = new \app\common\service\OrderCommissionService();
            $commissionResult = $commissionService->processOrderCommission($order['id']);

            if (!$commissionResult['success']) {
                \think\facade\Log::error('同系统API佣金结算失败 - 订单号: ' . ($order['order_no'] ?? '') . ', 错误: ' . $commissionResult['message']);
            }
        } catch (\Exception $e) {
            \think\facade\Log::error('同系统API佣金结算异常 - 订单号: ' . ($order['order_no'] ?? '') . ', 错误: ' . $e->getMessage());
        }
    }

    /**
     * 调用激活记录服务
     */
    private function callActivatedRecordService($order)
    {
        try {
            $commissionService = new \app\common\service\OrderCommissionService();
            $commissionResult = $commissionService->processOrderCommission($order['id']);

            if (!$commissionResult['success']) {
                \think\facade\Log::error('同系统API待结算佣金记录失败 - 订单号: ' . ($order['order_no'] ?? '') . ', 错误: ' . $commissionResult['message']);
            }
        } catch (\Exception $e) {
            \think\facade\Log::error('同系统API待结算佣金记录异常 - 订单号: ' . ($order['order_no'] ?? '') . ', 错误: ' . $e->getMessage());
        }
    }

    /**
     * 验证安全密钥
     */
    private function verifySecurityKey()
    {
        $securityKey = input('param.security_key', '');
        if (empty($securityKey)) return false;

        $configKey = Db::name('system_config')->where('config_key', 'security_key')->value('config_value');
        return !empty($configKey) && $securityKey === $configKey;
    }
}
