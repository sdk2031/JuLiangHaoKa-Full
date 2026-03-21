<?php
namespace app\api\controller\kapi\hao172;

use think\facade\Db;
use think\facade\Log;
use app\common\service\OrderCallbackService;
use app\common\helper\OrderRemarkHelper;


/**
 * 172号卡回调处理主控制器🆕
 */
class Callback
{


    /**
     * 回调入口
     */
    public function index()
    {
        // 如果是GET请求，返回简单信息
        if (!request()->isPost()) {
            return json(['code' => 0, 'message' => 'Hello 172']);
        }

        // 处理POST回调
        return $this->handleNotify();
    }

    /**
     * 获取172号卡配置
     */
    private function getConfig()
    {
        return Db::name('config_api')->where('api_type', 'hao172')->find();
    }

    /**
     * 处理回调通知
     */
    protected function handleNotify()
    {
        $rawData = file_get_contents('php://input');
        
        // 记录原始请求数据
        Log::write('[172号卡回调] 收到回调请求 - 原始数据: ' . $rawData, 'info');
        Log::write('[172号卡回调] 请求头: ' . json_encode(request()->header()), 'info');

        // 解析请求数据
        $requestData = json_decode($rawData, true);
        if (!$requestData || !isset($requestData['RequidId']) || !isset($requestData['Data'])) {
            Log::write('[172号卡回调] 数据格式错误 - 原始数据: ' . $rawData, 'error');
            return json(['code' => -1, 'message' => '数据格式错误']);
        }

        Log::write('[172号卡回调] RequidId: ' . $requestData['RequidId'], 'info');

        // 获取签名，尝试多种方式
        $sign = request()->header('sign', '');
        if (empty($sign)) {
            $sign = request()->header('Sign', '');
        }
        if (empty($sign)) {
            $sign = isset($requestData['sign']) ? $requestData['sign'] : '';
        }
        if (empty($sign)) {
            $sign = isset($requestData['Sign']) ? $requestData['Sign'] : '';
        }

        if (empty($sign)) {
            Log::write('[172号卡回调] 缺少签名 - 所有请求头: ' . json_encode(request()->header()), 'error');
            return json(['code' => -1, 'message' => '缺少签名']);
        }
        
        Log::write('[172号卡回调] 获取到签名: ' . $sign, 'info');

        try {
            // 解析订单数据
            $orderData = json_decode($requestData['Data'], true);
            if (!$orderData) {
                Log::write('[172号卡回调] 订单数据解析失败 - Data内容: ' . $requestData['Data'], 'error');
                return json(['code' => -1, 'message' => '订单数据解析失败']);
            }
            
            Log::write('[172号卡回调] 订单数据: ' . json_encode($orderData, JSON_UNESCAPED_UNICODE), 'info');

            // 检查是否为测试订单
            $isTestOrder = ($orderData['OrderNo172'] === 'TestOrder172' && $orderData['Remark'] === '推送测试');
            $server = input('param.server', 0);
            
            if ($isTestOrder) {
                Log::write('[172号卡回调] 识别为测试订单，跳过签名验证', 'info');
            }

            // 获取配置（用于签名验证和同步结算开关）
            $config = $this->getConfig();

            if (!$server && !$isTestOrder) {
                // 验证签名（sign解密后得到的是合作方订单号OrderNo，不是172平台订单号OrderNo172）
                $verified = $this->verifySign($sign, isset($orderData['OrderNo']) ? $orderData['OrderNo'] : '', $config);
                if (!$verified) {
                    Log::write('[172号卡回调] 签名验证失败', 'error');
                    return json(['code' => -1, 'message' => '签名验证失败']);
                }
            }

            // 如果是测试订单，直接返回成功
            if ($isTestOrder) {
                Log::write('[172号卡回调] 测试订单处理完成，返回成功', 'info');
                return json(['code' => 0, 'message' => '上传成功']);
            }

            // 获取本地订单
            $localOrder = Db::name('order')
                ->where('order_no', isset($orderData['OrderNo']) ? $orderData['OrderNo'] : '')
                ->where('api_name', '172号卡')
                ->find();

            if (!$localOrder) {
                Log::write('[172号卡回调] 未找到对应的本地订单: ' . (isset($orderData['OrderNo']) ? $orderData['OrderNo'] : '无订单号'), 'error');
                return json(['code' => -1, 'message' => '未找到订单']);
            }

            // 更新订单信息
            $updateData = $this->prepareOrderUpdateData($orderData, $localOrder, $config);

            // 更新数据库
            if (!empty($updateData)) {
                Db::name('order')
                    ->where('id', $localOrder['id'])
                    ->update($updateData);
                
                // 处理状态变更后的业务逻辑
                $this->handlePostUpdateLogic($localOrder, $updateData, $config);
            }

            return json(['code' => 0, 'message' => '上传成功']);

        } catch (\Exception $e) {
            Log::write('[172号卡回调] 处理异常: ' . $e->getMessage(), 'error');
            return json(['code' => -1, 'message' => '处理异常']);
        }
    }

    /**
     * 验证签名
     */
    protected function verifySign($sign, $orderNo, $config)
    {
        if (empty($sign) || empty($orderNo)) {
            Log::write('[172号卡回调] 签名验证参数为空 - sign: ' . $sign . ', orderNo: ' . $orderNo, 'error');
            return false;
        }

        if (empty($config) || empty($config['api_secret'])) {
            Log::write('[172号卡回调] 配置或密钥为空', 'error');
            return false;
        }

        try {
            // 从配置中获取密钥
            $secret = $config['api_secret'];
            $iv = 'MengLong172HaoKa';

            Log::write('[172号卡回调] 开始验证签名 - 原始sign: ' . $sign . ', 目标orderNo: ' . $orderNo, 'info');

            // 使用AES-256-CBC解密
            $decrypted = openssl_decrypt($sign, 'AES-256-CBC', $secret, 0, $iv);
            
            if ($decrypted !== false) {
                $decrypted = trim($decrypted);
                Log::write('[172号卡回调] 解密成功 - 解密结果: ' . $decrypted, 'info');
                
                if ($decrypted === $orderNo) {
                    Log::write('[172号卡回调] 签名验证成功', 'info');
                    return true;
                } else {
                    Log::write('[172号卡回调] 签名验证失败 - 解密结果与订单号不匹配', 'error');
                    return false;
                }
            } else {
                Log::write('[172号卡回调] 解密失败', 'error');
                return false;
            }

        } catch (\Exception $e) {
            Log::write('[172号卡回调] 签名验证异常: ' . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * 准备订单更新数据
     */
    protected function prepareOrderUpdateData($orderData, $localOrder, $config)
    {
        $updateData = [];
        
        // 检查是否开启同步结算
        $syncSettlementEnabled = (isset($config['sync_settlement']) ? $config['sync_settlement'] : 0) == 1;

        // 更新上游订单号
        if (!empty($orderData['OrderNo172'])) {
            $updateData['up_order_no'] = $orderData['OrderNo172'];
        }

        // 更新订单状态
        if (!empty($orderData['OrderStatus'])) {
            $orderStatus = $this->mapOrderStatus($orderData['OrderStatus'], $syncSettlementEnabled);
            if ($orderStatus !== null) {
                $updateData['order_status'] = $orderStatus;
            }
        }

        // 更新号码信息
        if (!empty($orderData['ThirdPhone'])) {
            $updateData['production_number'] = $orderData['ThirdPhone'];
        }

        // 更新物流信息
        if (!empty($orderData['ExpressName'])) {
            $updateData['express_company'] = $orderData['ExpressName'];
        }

        if (!empty($orderData['ExpressCode'])) {
            $updateData['tracking_number'] = $orderData['ExpressCode'];
        }

        // 更新备注信息
        if (!empty($orderData['Remark'])) {
            $updateData['remark'] = OrderRemarkHelper::append(
                isset($localOrder['remark']) ? $localOrder['remark'] : '',
                $orderData['Remark']
            );
        }

        // 更新激活状态
        if (!empty($orderData['CardStatus']) && $orderData['CardStatus'] === '已激活') {
            $updateData['order_status'] = '4';
            // 只在jh_time为空时设置，避免重复回调覆盖真实激活时间
            if (empty($localOrder['jh_time'])) {
                if (!empty($orderData['ActiveTime'])) {
                    $updateData['jh_time'] = $orderData['ActiveTime'];
                } else {
                    $updateData['jh_time'] = date('Y-m-d H:i:s');
                }
            }
        }

        // 更新ICCID
        if (!empty($orderData['Iccid'])) {
            $updateData['iccid'] = $orderData['Iccid'];
        }

        // 更新首充状态
        if (isset($orderData['IsFirstCharge']) && $orderData['IsFirstCharge'] == 1) {
            $updateData['recharge_status'] = '1'; // 1-已充值

            if (!empty($orderData['FirstCharge'])) {
                $updateData['recharge_amount'] = $orderData['FirstCharge'];
            }
        }

        // 更新时间戳
        $updateData['update_time'] = date('Y-m-d H:i:s');

        return $updateData;
    }

    /**
     * 映射订单状态
     * @param string $status 上游状态
     * @param bool $syncSettlementEnabled 是否开启同步结算
     */
    protected function mapOrderStatus($status, $syncSettlementEnabled = false)
    {
        // 基础状态映射
        $statusMap = [
            '正在进单' => '0',
            '已提运营商' => '1',
            '已发货' => '2',
            '已完成' => '2',
            '已撤单' => '7',
            '审核不通过' => '7',
            '审核失败' => '7',
            '已取消' => '7',
        ];
        
        // 结算状态只有开启同步结算时才映射
        if ($syncSettlementEnabled) {
            $statusMap['已结算'] = '5';
            $statusMap['无法结算'] = '6';
        }

        return isset($statusMap[$status]) ? $statusMap[$status] : null;
    }

    /**
     * 处理数据库更新后的业务逻辑
     */
    private function handlePostUpdateLogic($currentOrder, $updateData, $config)
    {
        // 触发回调通知（通知下游代理商）
        if (isset($updateData['order_status']) && $updateData['order_status'] != $currentOrder['order_status']) {
            try {
                OrderCallbackService::triggerCallback($currentOrder['id'], $updateData['order_status'], '172号卡上游回调更新');
            } catch (\Exception $e) {
                Log::write('[172号卡回调] 回调通知失败: ' . $currentOrder['order_no'] . ' - ' . $e->getMessage(), 'error');
            }
            
            $oldStatus = isset($currentOrder['order_status']) ? $currentOrder['order_status'] : '';
            $newStatus = $updateData['order_status'];
            $syncSettlementEnabled = (isset($config['sync_settlement']) ? $config['sync_settlement'] : 0) == 1;
            
            // 已激活时：始终更新代理统计，始终记录佣金（待结算状态）
            if ($newStatus == '4' && $oldStatus != '4') {
                // 更新代理激活统计
                if (!empty($currentOrder['agent_id'])) {
                    \app\common\helper\AgentStatsHelper::incrementActivationStats($currentOrder['agent_id']);
                }
                // 记录佣金（待结算状态）
                $this->callCommissionService($currentOrder);
            }
            
            // 已结算时触发佣金结算（需要开关，状态映射已控制）
            if ($newStatus == '5' && $oldStatus != '5') {
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
            $result = $commissionService->processOrderCommission($order['id']);
            
            if (!$result['success']) {
                Log::write('[172号卡回调] 佣金处理失败 - 订单号: ' . $order['order_no'] . ', 错误: ' . $result['message'], 'error');
            }
        } catch (\Exception $e) {
            Log::write('[172号卡回调] 佣金处理异常 - 订单号: ' . $order['order_no'] . ', 错误: ' . $e->getMessage(), 'error');
        }
    }
}