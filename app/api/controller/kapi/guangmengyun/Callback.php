<?php
namespace app\api\controller\kapi\guangmengyun;

use think\facade\Db;
use app\common\service\OrderCallbackService;

/**
 * 广梦云API回调插件
 * 处理广梦云订单状态变化的回调通知🆕
 */
class Callback
{
    public function __construct()
    {
        
    }


    /**
     * 成功响应
     */
    protected function success($msg = '操作成功', $data = [], $code = 0)
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
            'time' => time()
        ]);
    }

    /**
     * 失败响应
     */
    protected function error($msg = '操作失败', $data = [], $code = 1)
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
            'time' => time()
        ]);
    }

    /**
     * 订单状态回调处理
     * 广梦云回调参数与订单查询接口响应参数完全一致
     */
    public function orderStatus()
    {
        try {
            // GET请求返回测试信息（用于验证回调地址是否可访问）
            if (request()->isGet()) {
                return $this->success('广梦云回调接口正常', [
                    'status' => 'ready',
                    'method' => 'POST',
                    'description' => '此接口用于接收广梦云订单状态推送，请在广梦云后台配置此回调地址'
                ]);
            }
            
            // 获取回调数据
            $input = file_get_contents('php://input');
            trace('广梦云API回调原始数据: ' . $input, 'info');
            
            // 尝试解析JSON
            $callbackData = json_decode($input, true);
            $jsonError = json_last_error();

            if (!$callbackData || $jsonError !== JSON_ERROR_NONE) {
                trace('广梦云API回调JSON解析失败: ' . json_last_error_msg(), 'error');
                
                if (empty($input)) {
                    return $this->errorResponse('回调数据为空');
                }
                
                // 检查是否是URL编码的表单数据
                parse_str($input, $formData);
                if (!empty($formData)) {
                    $callbackData = $formData;
                } else {
                    return $this->errorResponse('回调数据格式错误');
                }
            }

            // 验证签名（可选，根据广梦云文档实现）
            $signValid = $this->verifySign($callbackData);
            if (!$signValid) {
                trace('广梦云API回调签名验证失败', 'warning');
                trace('广梦云API回调数据: ' . json_encode($callbackData, JSON_UNESCAPED_UNICODE), 'warning');
                
                // 检查是否启用严格签名验证
                $config = $this->getGuangmengyunConfig();
                $strictSignCheck = isset($config['strict_sign_check']) ? intval($config['strict_sign_check']) : 0;
                if ($strictSignCheck == 1) {
                    trace('广梦云API严格签名验证已启用，拒绝处理', 'error');
                    return $this->errorResponse('签名验证失败');
                } else {
                    trace('广梦云API签名验证失败，但继续处理（建议检查API配置）', 'warning');
                }
            }

            // 获取订单信息
            $keyNo = isset($callbackData['keyNo']) ? $callbackData['keyNo'] : '';
            $customNo = isset($callbackData['customNo']) ? $callbackData['customNo'] : '';

            if (empty($keyNo) && empty($customNo)) {
                trace('广梦云API回调参数缺失: ' . json_encode($callbackData, JSON_UNESCAPED_UNICODE), 'error');
                return $this->errorResponse('回调参数缺失：缺少订单号');
            }

            // 查找本地订单（使用LIKE匹配，支持带备注的api_name）
            $localOrder = null;
            try {
                // 优先通过customNo（本地订单号）查找
                if (!empty($customNo)) {
                    $orderArray = Db::query("SELECT * FROM `order` WHERE api_name LIKE '广梦云%' AND order_no = ? LIMIT 1", [$customNo]);
                    $localOrder = !empty($orderArray) ? $orderArray[0] : null;
                }
                
                // 如果没找到，再尝试通过keyNo（上游订单号）查找
                if (!$localOrder && !empty($keyNo)) {
                    $orderArray = Db::query("SELECT * FROM `order` WHERE api_name LIKE '广梦云%' AND up_order_no = ? LIMIT 1", [$keyNo]);
                    $localOrder = !empty($orderArray) ? $orderArray[0] : null;
                }
            } catch (\Exception $e) {
                trace('广梦云API回调查询订单失败: ' . $e->getMessage(), 'error');
            }

            if (!$localOrder) {
                trace('广梦云回调：未找到订单 keyNo=' . $keyNo . ', customNo=' . $customNo, 'warning');
                return $this->successResponse('订单不存在，已忽略');
            }

            // 构建更新数据
            $apiStatus = isset($callbackData['status']) ? intval($callbackData['status']) : 0;

            $updateData = [
                'order_status' => $this->convertOrderStatus($apiStatus),
                'production_number' => isset($callbackData['selectPhone']) ? $callbackData['selectPhone'] : $localOrder['production_number'],
                'express_company' => isset($callbackData['expressName']) ? $callbackData['expressName'] : '',
                'tracking_number' => isset($callbackData['expressNo']) ? $callbackData['expressNo'] : '',
                'update_time' => date('Y-m-d H:i:s')
            ];

            // 处理备注 - 拼接applyFailReason和flagRemark
            $applyFailReason = isset($callbackData['applyFailReason']) ? $callbackData['applyFailReason'] : '';
            $flagRemark = isset($callbackData['flagRemark']) ? $callbackData['flagRemark'] : '';
            
            $remarkParts = array();
            if (!empty($applyFailReason)) {
                $remarkParts[] = $applyFailReason;
            }
            if (!empty($flagRemark)) {
                $remarkParts[] = $flagRemark;
            }
            
            if (!empty($remarkParts)) {
                $combinedRemark = implode(' | ', $remarkParts);
                $updateData['remark'] = \app\common\helper\OrderRemarkHelper::append(
                    isset($localOrder['remark']) ? $localOrder['remark'] : '',
                    $combinedRemark
                );
            }

            // 处理首充状态
            if (isset($callbackData['firstRechargeStatus'])) {
                if ($callbackData['firstRechargeStatus'] == 2) {
                    $updateData['recharge_status'] = '1';
                    $updateData['recharge_amount'] = isset($callbackData['firstRechargeMoney']) ? $callbackData['firstRechargeMoney'] : 0;
                } else {
                    $updateData['recharge_status'] = '0';
                }
            }

            // 处理结算状态 - 需要先检查同步结算开关
            $finalStatus = $updateData['order_status'];
            $settleStatus = isset($callbackData['settleStatus']) ? $callbackData['settleStatus'] : 0;
            
            // 获取配置，检查是否开启同步结算
            $config = $this->getGuangmengyunConfig();
            $syncSettlementEnabled = (isset($config['sync_settlement']) ? $config['sync_settlement'] : 0) == 1;
            
            if (in_array($settleStatus, [2, 4])) {
                // 只有开启同步结算开关时，才同步结算状态
                if ($syncSettlementEnabled) {
                    if ($settleStatus == 2) {
                        // 已结算
                        $finalStatus = '5';
                        // 结算时间直接使用 settleAt
                        $settleAt = isset($callbackData['settleAt']) ? $callbackData['settleAt'] : '';
                        $updateData['js_time'] = !empty($settleAt) ? $settleAt : date('Y-m-d H:i:s');
                    } elseif ($settleStatus == 4) {
                        // 结算失败
                        $finalStatus = '6';
                    }
                } else {
                    trace('广梦云回调：同步结算开关未开启，忽略结算状态 settleStatus=' . $settleStatus, 'info');
                }
            }

            // 处理激活状态 - 结算状态优先级更高
            $isSettlementStatus = in_array($finalStatus, ['5', '6']);
            if ($apiStatus == 5 && !$isSettlementStatus) {
                $finalStatus = '4'; // 已激活
                // 只在jh_time为空时设置，避免重复回调覆盖真实激活时间
                if (empty($localOrder['jh_time'])) {
                    $updateData['jh_time'] = date('Y-m-d H:i:s');
                }
            }

            $finalStatus = $this->protectOrderStatus(isset($localOrder['order_status']) ? $localOrder['order_status'] : '', $finalStatus);

            $updateData['order_status'] = $finalStatus;

            // 更新订单
            try {
                // 记录更新前的数据用于调试
                trace('广梦云API回调更新数据: ' . json_encode($updateData, JSON_UNESCAPED_UNICODE), 'info');
                
                $setSql = [];
                $setParams = [];
                foreach ($updateData as $field => $value) {
                    // 跳过空值字段，避免覆盖已有数据
                    if ($value === null) {
                        continue;
                    }
                    $setSql[] = "`{$field}` = ?";
                    $setParams[] = $value;
                }
                
                if (empty($setSql)) {
                    trace('广梦云API回调：无需更新的字段', 'info');
                    return $this->successResponse('无需更新');
                }
                
                $setParams[] = $localOrder['id'];
                
                $updateSql = "UPDATE `order` SET " . implode(', ', $setSql) . " WHERE id = ?";
                
                // 记录SQL用于调试
                trace('广梦云API回调SQL: ' . $updateSql . ' | 参数: ' . json_encode($setParams, JSON_UNESCAPED_UNICODE), 'info');
                
                $result = Db::execute($updateSql, $setParams);
                trace('广梦云API回调更新结果: ' . $result, 'info');
                
                // 触发回调通知
                if (isset($updateData['order_status']) && $updateData['order_status'] != $localOrder['order_status']) {
                    try {
                        OrderCallbackService::triggerCallback($localOrder['id'], $updateData['order_status'], '广梦云上游回调更新');
                        trace('广梦云API已触发回调通知: ' . $localOrder['order_no'] . ' 状态=' . $updateData['order_status'], 'info');
                    } catch (\Exception $e) {
                        trace('广梦云API回调通知失败: ' . $localOrder['order_no'] . ' - ' . $e->getMessage(), 'error');
                    }
                }
            } catch (\Exception $e) {
                $updateSqlStr = isset($updateSql) ? $updateSql : '';
                $setParamsStr = isset($setParams) ? json_encode($setParams, JSON_UNESCAPED_UNICODE) : '[]';
                trace('广梦云API回调订单更新失败: ' . $e->getMessage() . ' | SQL: ' . $updateSqlStr . ' | 参数: ' . $setParamsStr, 'error');
                return $this->errorResponse('订单更新失败: ' . $e->getMessage());
            }

            // 处理状态变更后的业务逻辑
            $this->handlePostUpdateLogic($localOrder, $updateData, $callbackData);

            return $this->successResponse('回调处理成功');

        } catch (\Exception $e) {
            trace('广梦云回调处理异常：' . $e->getMessage(), 'error');
            return $this->errorResponse('回调处理失败：' . $e->getMessage());
        }
    }

    /**
     * 成功响应（返回HTTP 200状态码）
     */
    private function successResponse($msg = '操作成功')
    {
        // 广梦云要求回调成功后返回HTTP 200
        return response('OK', 200);
    }

    /**
     * 错误响应
     */
    private function errorResponse($msg = '操作失败')
    {
        return response($msg, 500);
    }

    /**
     * 验证签名
     * 签名算法：md5(appID + apiVersion + traceID + timestamp + API密钥)
     */
    private function verifySign($data)
    {
        // 获取请求头中的签名信息
        $headers = request()->header();
        $appId = isset($headers['appid']) ? $headers['appid'] : '';
        $apiVersion = isset($headers['apiversion']) ? $headers['apiversion'] : '';
        $traceId = isset($headers['traceid']) ? $headers['traceid'] : '';
        $timestamp = isset($headers['timestamp']) ? $headers['timestamp'] : '';
        $sign = isset($headers['sign']) ? $headers['sign'] : '';

        trace('[广梦云签名] 请求头信息: appid=' . $appId . ', apiVersion=' . $apiVersion . ', traceId=' . $traceId . ', timestamp=' . $timestamp . ', sign=' . $sign, 'info');

        if (empty($appId) || empty($apiVersion) || empty($traceId) || empty($timestamp) || empty($sign)) {
            trace('[广梦云签名] 签名信息不完整', 'warning');
            return false;
        }

        // 验证时间戳（与东8时区时差最大不能超过10分钟）
        $currentTimestamp = time();
        $timeDiff = abs($currentTimestamp - intval($timestamp));
        if ($timeDiff > 600) { // 10分钟 = 600秒
            trace('[广梦云签名] 时间戳过期，时差: ' . $timeDiff . '秒', 'warning');
            return false;
        }

        // 获取配置
        $config = $this->getGuangmengyunConfig();
        if (!$config) {
            trace('[广梦云签名] 未找到API配置', 'error');
            return false;
        }
        
        trace('[广梦云签名] 配置appId=' . $config['api_key'] . ', 请求appId=' . $appId, 'info');
        
        if ($config['api_key'] != $appId) {
            trace('[广梦云签名] appId不匹配', 'warning');
            return false;
        }

        // 验证签名：md5(appID + apiVersion + traceID + timestamp + API密钥)
        $signStr = $appId . $apiVersion . $traceId . $timestamp . $config['api_secret'];
        $expectedSign = strtolower(md5($signStr));
        
        trace('[广梦云签名] 签名字符串: ' . $signStr, 'info');
        trace('[广梦云签名] 计算签名: ' . $expectedSign, 'info');
        trace('[广梦云签名] 接收签名: ' . strtolower($sign), 'info');
        
        $result = $expectedSign === strtolower($sign);
        trace('[广梦云签名] 验证结果: ' . ($result ? '成功' : '失败'), 'info');
        
        return $result;
    }

    /**
     * 处理数据库更新后的业务逻辑
     */
    private function handlePostUpdateLogic($currentOrder, $updateData, $callbackData)
    {
        // 获取配置
        $config = $this->getGuangmengyunConfig();
        $syncSettlementEnabled = (isset($config['sync_settlement']) ? $config['sync_settlement'] : 0) == 1;

        // 触发回调通知
        if (isset($updateData['order_status']) && $updateData['order_status'] != $currentOrder['order_status']) {
            try {
                OrderCallbackService::triggerCallback($currentOrder['id'], $updateData['order_status'], '广梦云上游回调更新');
                trace('广梦云API已触发回调通知: ' . $currentOrder['order_no'] . ' 状态=' . $updateData['order_status'], 'info');
            } catch (\Exception $e) {
                trace('广梦云API回调通知失败: ' . $currentOrder['order_no'] . ' - ' . $e->getMessage(), 'error');
            }
        }

        if (isset($updateData['order_status'])) {
            $oldStatus = isset($currentOrder['order_status']) ? $currentOrder['order_status'] : '';
            $newStatus = $updateData['order_status'];

            // 已激活时：始终更新代理统计，始终记录佣金（待结算状态）
            if ($newStatus === '4' && $oldStatus !== '4') {
                // 更新代理激活统计
                if (!empty($currentOrder['agent_id'])) {
                    \app\common\helper\AgentStatsHelper::incrementActivationStats($currentOrder['agent_id']);
                }
                // 记录佣金（待结算状态）
                // 从settleDetails取month=0的settleReward作为结算佣金（顶层settleReward是month=-99的秒返金额）
                $settleReward = $this->extractSettleReward($callbackData);
                if ($settleReward > 0) {
                    $this->callGuangmengyunSettlement($currentOrder, $settleReward);
                } else {
                    $this->callActivatedRecordService($currentOrder);
                }
            }

            // 已结算状态触发佣金结算（需要开启同步结算开关）
            if ($newStatus === '5' && $oldStatus !== '5' && $syncSettlementEnabled) {
                $settleReward = $this->extractSettleReward($callbackData);
                if ($settleReward > 0) {
                    $this->callGuangmengyunSettlement($currentOrder, $settleReward);
                } else {
                    $this->callCommissionService($currentOrder);
                }
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
                $orderNo = isset($order['order_no']) ? $order['order_no'] : '';
                trace('广梦云API佣金结算失败 - 订单号: ' . $orderNo . ', 错误: ' . $commissionResult['message'], 'error');
            }
        } catch (\Exception $e) {
            $orderNo = isset($order['order_no']) ? $order['order_no'] : '';
            trace('广梦云API佣金结算异常 - 订单号: ' . $orderNo . ', 错误: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * 广梦云专用：用上游实际结算金额重新计算佣金
     */
    private function callGuangmengyunSettlement($order, $settleReward)
    {
        try {
            $commissionService = new \app\common\service\OrderCommissionService();
            $commissionResult = $commissionService->processGuangmengyunSettlement($order['id'], $settleReward);

            if (!$commissionResult['success']) {
                $orderNo = isset($order['order_no']) ? $order['order_no'] : '';
                trace('广梦云API结算(settleReward)失败 - 订单号: ' . $orderNo . ', settleReward=' . $settleReward . ', 错误: ' . $commissionResult['message'], 'error');
            }
        } catch (\Exception $e) {
            $orderNo = isset($order['order_no']) ? $order['order_no'] : '';
            trace('广梦云API结算(settleReward)异常 - 订单号: ' . $orderNo . ', 错误: ' . $e->getMessage(), 'error');
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
                $orderNo = isset($order['order_no']) ? $order['order_no'] : '';
                trace('广梦云API待结算佣金记录失败 - 订单号: ' . $orderNo . ', 错误: ' . $commissionResult['message'], 'error');
            }
        } catch (\Exception $e) {
            $orderNo = isset($order['order_no']) ? $order['order_no'] : '';
            trace('广梦云API待结算佣金记录异常 - 订单号: ' . $orderNo . ', 错误: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * 从settleDetails中提取month=0的settleReward作为结算佣金
     * 顶层settleReward对应的是month=-99（秒返），不应作为结算佣金
     * 
     * @param array $callbackData 回调数据
     * @return float 结算佣金
     */
    private function extractSettleReward($callbackData)
    {
        $settleDetails = isset($callbackData['settleDetails']) ? $callbackData['settleDetails'] : array();
        
        if (!empty($settleDetails) && is_array($settleDetails)) {
            foreach ($settleDetails as $detail) {
                $month = isset($detail['month']) ? intval($detail['month']) : -1;
                $reward = isset($detail['settleReward']) ? floatval($detail['settleReward']) : 0;
                if ($month === 0 && $reward > 0) {
                    $topReward = isset($callbackData['settleReward']) ? $callbackData['settleReward'] : 0;
                    trace('广梦云结算: 取month=0的settleReward=' . $detail['settleReward'] . ', 顶层settleReward=' . $topReward, 'info');
                    return floatval($detail['settleReward']);
                }
            }
        }
        
        // 兜底：settleDetails为空或无month=0项时，用顶层settleReward
        return floatval(isset($callbackData['settleReward']) ? $callbackData['settleReward'] : 0);
    }

    /**
     * 智能追加备注信息
     */
    private function appendRemarkIfNew(&$updateData, $localOrder, $newRemark)
    {
        if (empty($newRemark)) {
            return;
        }
        
        $currentRemark = isset($localOrder['remark']) ? $localOrder['remark'] : '';
        
        if ($newRemark !== $currentRemark) {
            $timestampedRemark = '[' . date('Y-m-d H:i:s') . '] ' . $newRemark;
            
            if (empty($currentRemark)) {
                $updateData['remark'] = $timestampedRemark;
            } else {
                if (strpos($currentRemark, $newRemark) === false) {
                    $updateData['remark'] = $currentRemark . '; ' . $timestampedRemark;
                }
            }
        }
    }

    /**
     * 转换订单状态
     * 广梦云状态码 -> 本地状态码
     * 本地状态：0=已提交, 1=待发货, 2=已发货, 3=已签收, 4=已激活, 5=已结算, 6=待上传, 7=异常/失败
     */
    private function convertOrderStatus($apiStatus)
    {
        $statusMap = [
            1 => '0',   // 初步审核 -> 已提交
            2 => '0',   // 审核中 -> 已提交
            3 => '7',   // 审核失败 -> 审核失败
            4 => '2',   // 已发货 -> 已发货
            5 => '4',   // 已激活 -> 已激活
            6 => '2',   // 已签收 -> 已发货
            7 => '6',   // 待上传证件 -> 待上传
            8 => '0',   // 证件审核中 -> 已提交
            10 => '2',  // 待激活 -> 已发货
            11 => '0',  // 客服外呼 -> 已提交
            13 => '1',  // 待发货 -> 待发货
            15 => '7',  // 已取消 -> 审核失败
            16 => '7',  // 停销户 -> 异常
            17 => '7',  // 已拒签 -> 已取消
            18 => '4',  // 激活未首充 -> 已激活
            999 => '0', // 待受理 -> 已提交
            9998 => '7', // 无效废单 -> 审核失败
            9999 => '0'  // 未知状态 -> 已提交
        ];
        
        return isset($statusMap[$apiStatus]) ? $statusMap[$apiStatus] : '0';
    }

    /**
     * 保护订单状态，避免结算态被普通激活回调覆盖
     */
    private function protectOrderStatus($currentStatus, $newStatus)
    {
        $currentStatus = (string)$currentStatus;
        $newStatus = (string)$newStatus;

        // 已结算后不允许回退到已激活/已发货/已提交等任意非已结算状态
        if ($currentStatus === '5' && $newStatus !== '5') {
            return '5';
        }

        // 结算失败允许前进到已结算，但不允许被普通激活回调回退
        if ($currentStatus === '6' && !in_array($newStatus, ['5', '6'], true)) {
            return '6';
        }

        return $newStatus;
    }

    /**
     * 获取广梦云API配置
     * @param int $configId 配置ID（可选，支持多配置）
     */
    private function getGuangmengyunConfig($configId = 0)
    {
        try {
            if ($configId > 0) {
                return Db::name('config_api')
                    ->where('api_type', 'guangmengyun')
                    ->where('id', $configId)
                    ->find();
            } else {
                return Db::name('config_api')
                    ->where('api_type', 'guangmengyun')
                    ->where('status', 1)
                    ->order('id', 'asc')
                    ->find();
            }
        } catch (\Exception $e) {
            trace('ORM查询失败，尝试原始SQL: ' . $e->getMessage(), 'error');
            try {
                if ($configId > 0) {
                    $configArray = Db::query("SELECT * FROM config_api WHERE api_type = 'guangmengyun' AND id = ? LIMIT 1", [$configId]);
                } else {
                    $configArray = Db::query("SELECT * FROM config_api WHERE api_type = 'guangmengyun' AND status = 1 ORDER BY id ASC LIMIT 1");
                }
                return !empty($configArray) ? $configArray[0] : null;
            } catch (\Exception $e2) {
                trace('原始SQL查询也失败: ' . $e2->getMessage(), 'error');
                return null;
            }
        }
    }

    /**
     * 测试回调接口
     */
    public function test()
    {
        return $this->success('广梦云回调接口正常');
    }
}
