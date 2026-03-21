<?php
namespace app\api\controller\kapi\lanchang;

use think\facade\Db;

/**
 * 蓝畅号卡回调控制器🆕
 */
class Callback
{
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
     * 获取配置
     */
    protected function getConfig()
    {
        $defaultConfig = [
            'api_type' => 'lanchang',
            'name' => '蓝畅速享',
            'api_key' => '',
            'api_secret' => '',
            'api_url' => 'https://api.nnkj77.com',
            'status' => 1
        ];
        
        try {
            $config = Db::name('config_api')->where('api_type', 'lanchang')->find();
            if (!$config) return $defaultConfig;
            return array_merge($defaultConfig, $config);
        } catch (\Exception $e) {
            return $defaultConfig;
        }
    }
    

    public function orderStatus()
    {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            $_SERVER['HTTP_USER_AGENT'] = 'ServerCallback/1.0';
        }
        
        try {
        $rawInput = file_get_contents('php://input');
        $callbackData = [];
        
        if (!empty($rawInput)) {
            $jsonData = json_decode($rawInput, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
                $callbackData = $jsonData;
            }
        }
        
        if (empty($callbackData)) {
            $callbackData = request()->param();
        }
        

        $this->log('收到订单状态回调', $callbackData);
        

        if (empty($callbackData['shopid']) || empty($callbackData['from_order']) || !isset($callbackData['state']) || !isset($callbackData['state_name'])) {
            $this->log('回调参数缺失', $callbackData);
            return json(['code' => 200, 'msg' => '.NNSUCCESS']);
        }
            
            // 获取参数
            $productId = $callbackData['shopid'];     
            $orderNo = $callbackData['from_order'];    
            $state = $callbackData['state'];          
            $stateName = $callbackData['state_name'];  
            
            // 获取更多可选参数
            $phoneNumber = isset($callbackData['iphome_id']) ? $callbackData['iphome_id'] : '';          // 业务号码
            $expressNo = isset($callbackData['collect_express_id']) ? $callbackData['collect_express_id'] : '';   // 快递单号
            $expressCompany = isset($callbackData['collect_express']) ? $callbackData['collect_express'] : ''; // 快递公司
            $activationTime = isset($callbackData['activation_time']) ? $callbackData['activation_time'] : ''; // 激活时间
            $firstRecharge = isset($callbackData['activation_recharge']) ? $callbackData['activation_recharge'] : ''; // 首充金额
            $activationStatuscd = isset($callbackData['activation_statuscd']) ? $callbackData['activation_statuscd'] : ''; // 激活状态文字
            
            // 根据订单号获取订单
            $order = Db::name('order')->where('order_no', $orderNo)->find();
            
            if (!$order) {
                $this->log('订单不存在', ['order_no' => $orderNo]);
                return json(['code' => 200, 'msg' => '.NNSUCCESS']);
            }
            
            Db::startTrans();

            // 系统状态: 0-已提交, 1-待发货, 2-已发货, 3-待传照片, 4-已激活, 5-已结算, 6-结算失败, 7-审核失败
            $statusMap = [
                '0'     => '0',   // 待处理 -> 已提交
                '-1982' => '0',   // 下单待人工审核中 -> 已提交
                '1'     => '1',   // 下单成功 -> 已提交
                '2'     => '0',   // 无关联订单 -> 已提交
                '3'     => '2',   // 快递已发货 -> 已发货
                '4'     => '3',   // 证件待补充 -> 待传照片
                '5'     => '1',   // 证件已提交 -> 待发货
                '6'     => '1',   // 订单挂起 -> 待发货
                '99'    => '2',   // 已收卡业务进行中 -> 已发货
                '-2000' => '4',   // 订单完成 -> 已激活
                '-2'    => '7',   // 下单失败 -> 审核失败
                '-100'  => '1',   // 信息已核实 -> 待发货
                '-200'  => '1',   // 信息待核实 -> 待发货
                '-5'    => '1'    // 套餐已重推 -> 待发货
            ];
            

            $baseStatus = isset($statusMap[$state]) ? $statusMap[$state] : $order['order_status'];
            
            $newStatus = $baseStatus;
            $settleStatus = isset($callbackData['settle_status']) ? $callbackData['settle_status'] : '';
            

            if (strpos($activationStatuscd, '已激活') !== false) {
                $newStatus = '4'; // 已激活
            }
            
  
            $config = $this->getConfig();
            $syncSettlementEnabled = (isset($config['sync_settlement']) ? $config['sync_settlement'] : 0) == 1;
            
            if ($syncSettlementEnabled) {
                if (in_array($settleStatus, ['秒返已完成', '已月返'])) {
                    $newStatus = '5'; 
                } elseif ($settleStatus == '秒返失败') {
                    $newStatus = '6'; 
                }
            }
            
            $updateData = [];
            $currentStatus = $order['order_status'];
            
            if (!empty($stateName)) {
                $currentRemark = isset($order['remark']) ? $order['remark'] : '';
                $timeline = [];
                if (!empty($currentRemark)) {
                    $decoded = json_decode($currentRemark, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $timeline = $decoded;
                    } else {
                        $timeline = [['time' => '', 'content' => $currentRemark]];
                    }
                }
                $exists = false;
                foreach ($timeline as $item) {
                    if (isset($item['content']) && $item['content'] === $stateName) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $timeline[] = ['time' => date('Y-m-d H:i:s'), 'content' => $stateName];
                }
                $updateData['remark'] = json_encode($timeline, JSON_UNESCAPED_UNICODE);
            }
            

            if ($currentStatus == '5') {
            }

            elseif ($currentStatus == '4') {
                if ($newStatus == '5' || $newStatus == '6') {
                    $updateData['order_status'] = $newStatus;
                }
            }

            elseif ($newStatus !== $currentStatus) {
                $updateData['order_status'] = $newStatus;
            }
            

            if (!empty($phoneNumber) && empty($order['production_number'])) {
                $updateData['production_number'] = $phoneNumber;
            }
            

            if (!empty($expressNo)) {
                $updateData['tracking_number'] = $expressNo;
            }
            
            if (!empty($expressCompany)) {
                $updateData['express_company'] = $expressCompany;
            }
            

            if (!empty($firstRecharge)) {
                $updateData['recharge_amount'] = $firstRecharge;

                if (floatval($firstRecharge) > 0) {
                    $updateData['recharge_status'] = '1'; // 1-已充值
                }
            }
            
            if (strpos($activationStatuscd, '已激活') !== false) {
                $updateData['order_status'] = '4';
            }
            
            if (!empty($activationTime) && intval($activationTime) > 0 && empty($order['jh_time'])) {
                $updateData['jh_time'] = date('Y-m-d H:i:s', intval($activationTime));
            }
            
            // 更新订单
            if (!empty($updateData)) {
                $updateData['update_time'] = date('Y-m-d H:i:s', time());
                // 移除不存在的字段
                if (isset($updateData['status_remark'])) {
                    unset($updateData['status_remark']);
                }
                Db::name('order')->where('id', $order['id'])->update($updateData);
                
                // 记录状态更新
                $this->log('订单状态已更新', [
                    'order_no' => $orderNo,
                    'old_status' => $currentStatus,
                    'new_status' => $newStatus,
                    'updates' => $updateData
                ]);
                
                // 触发回调通知（如果状态发生变化）
                if (isset($updateData['order_status']) && $updateData['order_status'] !== $currentStatus) {
                    try {
                        \app\common\service\OrderCallbackService::triggerCallback(
                            $order['id'], 
                            $updateData['order_status'], 
                            ''
                        );
                    } catch (\Throwable $e) {
                        $this->log('触发回调失败', [
                            'order_no' => $orderNo,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
            
            Db::commit();
            
            // 处理数据库更新后的业务逻辑
            $finalStatus = isset($updateData['order_status']) ? $updateData['order_status'] : $currentStatus;
            $this->handleOrderStatusLogic($order, $finalStatus, $currentStatus);
            
            return json(['code' => 200, 'msg' => '.NNSUCCESS']);
            
        } catch (\Exception $e) {
            Db::rollback();
            
            $this->log('处理订单回调异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // 返回成功状态，避免蓝畅重复回调
            return json(['code' => 200, 'msg' => '.NNSUCCESS']);
        } catch (\Throwable $e) {
            // 最外层兜底，确保任何情况都返回正确格式
            return json(['code' => 200, 'msg' => '.NNSUCCESS']);
        }
    }
    
    /**
     * 商品状态回调
     * 处理蓝畅发送的商品上下架状态变更通知
     */
    public function productStatus()
    {
        // 兼容无User-Agent的服务器回调请求，防止加密文件检查报错
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            $_SERVER['HTTP_USER_AGENT'] = 'ServerCallback/1.0';
        }
        
        try {
        // 获取回调数据（支持GET和POST）
        $callbackData = request()->param();
        
        // 记录原始回调数据到日志
        $this->log('收到商品状态回调', $callbackData);
        
        // 验证必须的参数
        if (empty($callbackData['shopid']) || !isset($callbackData['state'])) {
            $this->log('回调参数缺失', $callbackData);
            return json(['code' => 200, 'msg' => '.NNSUCCESS']);
        }
            
            // 获取参数
            $productId = $callbackData['shopid'];    // 商品ID
            $state = $callbackData['state'];         // 商品状态 0:上架 1:下架
            
            // 根据商品ID查询商品
            // 在数据库number字段中查找包含商品ID的记录
            // number格式是LC3773750_MF或LC3773750_YF，需要匹配中间数字部分
            $product = null;
            
            // 首先尝试直接匹配number字段
            $directMatch = Db::name('product')
                ->where('number', $productId)
                ->find();
                
            if ($directMatch) {
                $product = $directMatch;
            } else {
                // 如果直接匹配失败，尝试模糊匹配
                $product = Db::name('product')
                    ->whereLike('number', '%' . $productId . '%')
                    ->find();
            }
            
            if (!$product) {
                $this->log('商品不存在', ['product_id' => $productId]);
                return json(['code' => 200, 'msg' => '.NNSUCCESS']);
            }
            
            // 更新商品状态
            $status = $state == 0 ? 1 : 0; // 0:上架 => 1:启用, 1:下架 => 0:禁用
            
            Db::name('product')
                ->where('id', $product['id'])
                ->update([
                    'status' => $status,
                    'update_time' => date('Y-m-d H:i:s', time())
                ]);
            
            $this->log('商品状态已更新', [
                'product_id' => $productId,
                'old_status' => $product['status'],
                'new_status' => $status
            ]);
            
            return json(['code' => 200, 'msg' => '.NNSUCCESS']);
            
        } catch (\Throwable $e) {
            $this->log('处理商品状态回调异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return json(['code' => 200, 'msg' => '.NNSUCCESS']);
        }
    }
    
    /**
     * 处理订单状态变更后的业务逻辑
     * @param array $order 订单数据
     * @param string $finalStatus 最终状态
     * @param string $oldStatus 原状态
     */
    protected function handleOrderStatusLogic($order, $finalStatus, $oldStatus)
    {
        try {
            // 检查同步结算开关
            $config = Db::name('config_api')->where('api_type', 'lanchang')->find();
            $syncSettlementEnabled = (isset($config['sync_settlement']) ? $config['sync_settlement'] : 0) == 1;
            
            // 已激活时：始终更新代理统计，始终记录佣金（待结算状态）
            if ($finalStatus === '4' && $oldStatus !== '4') {
                // 更新代理激活统计
                if (!empty($order['agent_id'])) {
                    \app\common\helper\AgentStatsHelper::incrementActivationStats($order['agent_id']);
                }
                // 记录佣金（待结算状态）
                // 检查是否已有待结算记录
                $hasPendingRecord = Db::name('agent_balance_logs')
                    ->where('order_id', $order['id'])
                    ->where('type', 'pending')
                    ->find();
                
                if (!$hasPendingRecord) {
                    $this->log('触发激活记录', ['order_no' => $order['order_no']]);
                    $this->callActivatedRecordService($order);
                }
            }
            
            // 已结算时触发佣金结算（需要开关）
            if ($finalStatus === '5' && $syncSettlementEnabled) {
                // 检查是否已有结算记录
                $hasSettlement = Db::name('agent_balance_logs')
                    ->where('order_id', $order['id'])
                    ->where('type', 'in')
                    ->where('sub_type', 'order')
                    ->find();
                
                if (!$hasSettlement) {
                    $this->log('触发佣金结算', ['order_no' => $order['order_no']]);
                    $this->callCommissionService($order);
                }
            }
        } catch (\Exception $e) {
            $this->log('业务逻辑处理异常', [
                'order_no' => $order['order_no'],
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 调用系统的佣金结算服务
     * @param array $order 订单数据
     */
    protected function callCommissionService($order)
    {
        try {
            // 调用佣金处理服务进行实际佣金分配
            $commissionService = new \app\common\service\OrderCommissionService();
            $commissionResult = $commissionService->processOrderCommission($order['id']);
            
            if ($commissionResult['success']) {
                $this->log('佣金结算成功', ['order_no' => $order['order_no']]);
            } else {
                $this->log('佣金结算失败', [
                    'order_no' => $order['order_no'],
                    'error' => $commissionResult['message']
                ]);
            }
        } catch (\Exception $e) {
            $this->log('佣金结算异常', [
                'order_no' => $order['order_no'],
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 调用系统的佣金处理服务（记录待结算佣金）
     * @param array $order 订单数据
     */
    protected function callActivatedRecordService($order)
    {
        try {
            // 调用佣金处理服务记录待结算佣金
            $commissionService = new \app\common\service\OrderCommissionService();
            $commissionResult = $commissionService->processOrderCommission($order['id']);
            
            if ($commissionResult['success']) {
                $this->log('待结算佣金记录成功', ['order_no' => $order['order_no']]);
            } else {
                $this->log('待结算佣金记录失败', [
                    'order_no' => $order['order_no'],
                    'error' => $commissionResult['message']
                ]);
            }
        } catch (\Exception $e) {
            $this->log('待结算佣金记录异常', [
                'order_no' => $order['order_no'],
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 记录日志
     * @param string $message 消息
     * @param array $data 日志数据
     * @param string $level 日志级别
     */
    protected function log($message, $data = [], $level = 'info')
    {
        $logMessage = '[蓝畅回调] ' . $message;
        
        if (!empty($data)) {
            $logMessage .= ' ' . json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        
        // 使用 ThinkPHP 标准日志系统
        \think\facade\Log::record($logMessage, $level);
    }
} 