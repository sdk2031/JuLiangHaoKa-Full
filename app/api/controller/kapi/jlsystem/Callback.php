<?php
namespace app\api\controller\kapi\jlsystem;

use think\facade\Db;
use app\common\service\OrderCallbackService;
use app\common\helper\OrderRemarkHelper;

/**
 * 同系统API回调处理
 * 接收上游系统的订单状态推送
 */
class Callback
{
    /**
     * 回调入口
     */
    public function index()
    {
        // 获取回调数据
        $params = request()->param();

        \think\facade\Log::info('同系统API回调接收: ' . json_encode($params, JSON_UNESCAPED_UNICODE));

        // 验证必要参数
        $orderNo = $params['order_no'] ?? '';
        $partnerOrderNo = $params['partner_order_no'] ?? '';
        $sign = $params['sign'] ?? '';
        $timestamp = $params['timestamp'] ?? '';
        $agentId = $params['agent_id'] ?? '';

        if (empty($partnerOrderNo)) {
            return $this->response('fail', '缺少订单号');
        }

        // 查找本地订单（使用LIKE匹配，支持带备注的api_name）
        $order = null;
        try {
            $orderArray = Db::query("SELECT * FROM `order` WHERE api_name LIKE '同系统%' AND order_no = ? LIMIT 1", [$partnerOrderNo]);
            $order = !empty($orderArray) ? $orderArray[0] : null;
        } catch (\Exception $e) {
            \think\facade\Log::error('同系统API回调查询订单失败: ' . $e->getMessage());
        }

        if (!$order) {
            \think\facade\Log::error('同系统API回调订单不存在: ' . $partnerOrderNo);
            return $this->response('fail', '订单不存在');
        }

        // 获取配置（通过订单的api_config_id）
        $configId = $order['api_config_id'] ?? 0;
        $config = Config::getConfig($configId);
        if (!$config || empty($config['api_secret'])) {
            \think\facade\Log::error('同系统API回调失败: 配置不完整');
            return $this->response('fail', '配置错误');
        }

        // 验证签名
        if (!$this->verifySign($params, $config['api_secret'])) {
            \think\facade\Log::error('同系统API回调签名验证失败: ' . $partnerOrderNo);
            return $this->response('fail', '签名验证失败');
        }

        // 准备更新数据
        $oldStatus = $order['order_status'];
        $updateData = ['update_time' => date('Y-m-d H:i:s')];

        // 更新上游订单号
        if (!empty($orderNo) && empty($order['up_order_no'])) {
            $updateData['up_order_no'] = $orderNo;
        }

        // 更新订单状态（字段完全一致）
        if (isset($params['order_status'])) {
            $newStatus = (string)$params['order_status'];

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
                    if ($newStatus === '4' && $oldStatus !== '4' && empty($order['jh_time'])) {
                        $updateData['jh_time'] = date('Y-m-d H:i:s');
                    }

                    // 结算时设置结算时间
                    if ($newStatus === '5' && $oldStatus !== '5') {
                        $updateData['js_time'] = date('Y-m-d H:i:s');
                    }
                }
            }
        }

        // 更新快递信息
        if (!empty($params['express_company'])) {
            $updateData['express_company'] = $params['express_company'];
        }
        if (!empty($params['tracking_number'])) {
            $updateData['tracking_number'] = $params['tracking_number'];
        }

        // 更新生产号码
        if (!empty($params['production_number'])) {
            $updateData['production_number'] = $params['production_number'];
        }

        // 更新照片状态
        if (isset($params['photo_status'])) {
            $updateData['photo_status'] = $params['photo_status'];
        }

        // 更新充值状态
        if (isset($params['recharge_status'])) {
            $updateData['recharge_status'] = $params['recharge_status'];
        }
        if (isset($params['recharge_amount'])) {
            $updateData['recharge_amount'] = floatval($params['recharge_amount']);
        }

        // 同步佣金（如果开启）
        $syncCommission = ($config['sync_commission'] ?? 0) == 1;
        if ($syncCommission && isset($params['commission'])) {
            $updateData['commission'] = floatval($params['commission']);
        }

        // 更新备注（使用时间线格式）
        if (!empty($params['remark'])) {
            $updateData['remark'] = OrderRemarkHelper::append(
                $order['remark'] ?? '',
                $params['remark']
            );
        }

        // 执行数据库更新
        try {
            Db::name('order')->where('id', $order['id'])->update($updateData);

            // 处理状态变更后的业务逻辑
            $this->handlePostUpdateLogic($order, $updateData, $config);

            \think\facade\Log::info('同系统API回调处理成功: ' . $partnerOrderNo . ' 状态=' . ($updateData['order_status'] ?? $oldStatus));

            return $this->response('success');

        } catch (\Exception $e) {
            \think\facade\Log::error('同系统API回调更新失败: ' . $partnerOrderNo . ' - ' . $e->getMessage());
            return $this->response('fail', '更新失败');
        }
    }

    /**
     * 验证签名
     * 签名规则：agent_id=xxx&order_no=xxx&partner_order_no=xxx&timestamp=xxx + api_secret
     * MD5后转大写
     */
    private function verifySign($params, $apiSecret)
    {
        $sign = $params['sign'] ?? '';
        if (empty($sign)) {
            return false;
        }

        // 按照文档规则构建签名字符串
        // 排序: agent_id, order_no, partner_order_no, timestamp
        $signParams = [
            'agent_id' => $params['agent_id'] ?? '',
            'order_no' => $params['order_no'] ?? '',
            'partner_order_no' => $params['partner_order_no'] ?? '',
            'timestamp' => $params['timestamp'] ?? ''
        ];

        // 按key排序
        ksort($signParams);

        // 拼接字符串
        $signStr = '';
        foreach ($signParams as $key => $value) {
            $signStr .= $key . '=' . $value . '&';
        }
        $signStr = rtrim($signStr, '&');

        // 拼接密钥
        $signStr .= $apiSecret;

        // MD5并转大写
        $calculatedSign = strtoupper(md5($signStr));

        \think\facade\Log::debug('同系统API签名验证: signStr=' . $signStr . ', calculated=' . $calculatedSign . ', received=' . $sign);

        return $calculatedSign === strtoupper($sign);
    }

    /**
     * 处理数据库更新后的业务逻辑
     */
    private function handlePostUpdateLogic($currentOrder, $updateData, $config)
    {
        // 获取同步结算开关状态
        $syncSettlementEnabled = ($config['sync_settlement'] ?? 0) == 1;

        // 触发下游回调通知
        if (isset($updateData['order_status']) && $updateData['order_status'] != $currentOrder['order_status']) {
            try {
                OrderCallbackService::triggerCallback($currentOrder['id'], $updateData['order_status'], '同系统API回调状态变更');
                \think\facade\Log::info('同系统API已触发下游回调: ' . $currentOrder['order_no'] . ' 状态=' . $updateData['order_status']);
            } catch (\Exception $e) {
                \think\facade\Log::error('同系统API下游回调失败: ' . $currentOrder['order_no'] . ' - ' . $e->getMessage());
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
     * 返回响应
     */
    private function response($status, $message = '')
    {
        // 上游要求返回小写的success
        if ($status === 'success') {
            return 'success';
        }
        return $status;
    }
}
