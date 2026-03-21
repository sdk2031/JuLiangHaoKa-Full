<?php
declare (strict_types = 1);

namespace app\api\controller\kapi\haoy;

use think\facade\Log;
use think\facade\Request;
use think\facade\Db;
use think\Response;
use app\common\service\OrderCallbackService;

/**
 * 号易回调处理控制器🆕
 */
class Callback
{
    /**
     * 配置表名
     * @var string
     */
    protected $configTable = 'config_api';
    
    /**
     * 默认回调处理方法
     */
    public function index()
    {
        // 兼容无User-Agent的服务器回调请求
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            $_SERVER['HTTP_USER_AGENT'] = 'HaoyCallback/1.0';
        }
        
        // 关闭错误显示，避免干扰输出
        @ini_set('display_errors', '0');
        
        // 清除所有输出缓冲区
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        // 开启新的输出缓冲区
        ob_start();
        
        // 设置响应头
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        
        try {
            // 获取原始请求数据
            $rawData = file_get_contents('php://input');
            
            // 号易使用 application/x-www-form-urlencoded 格式
            // 优先从 $_POST 获取（最可靠）
            $params = $_POST;
            
            // 如果 $_POST 为空，尝试其他方式
            if (empty($params)) {
                // 尝试从 ThinkPHP Request 获取
                $params = Request::post();
            }
            
            // 如果还是为空，尝试从 GET 获取（测试消息可能用GET）
            if (empty($params)) {
                $params = Request::get();
                if (empty($params)) {
                    $params = $_GET;
                }
            }
            
            // 如果还是为空，尝试解析原始数据
            if (empty($params) && !empty($rawData)) {
                parse_str($rawData, $params);
            }
            
            // 记录回调数据到日志
            Log::record('[号易回调] 接收订单: ' . (isset($params['ext_order_sn']) ? $params['ext_order_sn'] : '未知'), 'info');
            
            // 检查请求是否为初始测试请求（GET方式：msg=check）
            if (isset($params['msg']) && $params['msg'] == 'check') {
                Log::record('[号易回调] 收到测试消息，返回success', 'info');
                ob_end_clean();
                echo 'success';
                exit;
            }
            
            // 验证关键参数
            if (!isset($params['order_sn']) || !isset($params['up_status']) || !isset($params['ext_order_sn'])) {
                Log::record('[号易回调] 参数不完整: ' . json_encode($params, JSON_UNESCAPED_UNICODE), 'error');
                ob_end_clean();
                echo 'success';
                exit;
            }
            
            // 获取API配置用于签名验证
            $config = $this->getConfig();
            
            // 根据文档，需要验证签名
            if (isset($params['sign'])) {
                $sign = $params['sign'];
                $signParams = $params;
                unset($signParams['sign']); // 移除签名以便重新计算
                
                // 完全按照号易文档的方式：使用 array_filter 过滤空参数
                // 注意：array_filter 会把 "0" 也过滤掉（PHP默认行为）
                $signParams = array_filter($signParams);
                
                // 排序
                ksort($signParams);
                
                // 拼接字符串（按号易规则：k=v 直接拼接，无分隔符）
                $str = '';
                foreach ($signParams as $k => $v) {
                    $str .= $k . '=' . $v;
                }
                
                // 添加密钥
                $str .= $config['api_secret'];
                
                // 计算签名
                $calcSign = strtolower(md5($str));
                
                if ($calcSign !== $sign) {
                    Log::record("[号易回调] 签名验证失败: 订单={$params['ext_order_sn']}", 'error');
                    ob_end_clean();
                    echo 'success';
                    exit;
                }
            }
            
            // 处理订单状态
            $upOrderNo = $params['order_sn']; // 上游订单号
            $localOrderNo = $params['ext_order_sn']; // 本地订单号
            $upStatus = (int)$params['up_status']; // 订单状态
            
            // 查询本地订单
            $order = Db::name('order')
                ->where('order_no', $localOrderNo)
                ->whereOr('up_order_no', $upOrderNo)
                ->find();

            if (!$order) {
                Log::record("[号易回调] 未找到订单: {$localOrderNo}", 'error');
                ob_end_clean();
                echo 'success';
                exit;
            }
            
            // 开始事务
            Db::startTrans();
            
            try {
                // 准备更新数据
                $updateData = [];

                // 如果上游订单号为空，则更新
                if (empty($order['up_order_no']) && !empty($upOrderNo)) {
                    $updateData['up_order_no'] = $upOrderNo;
                }

                // 映射号易订单状态到本地订单状态
                $orderStatus = $this->mapOrderStatus($upStatus);

                // 根据上游状态更新本地订单状态
                if ($orderStatus && $order['order_status'] != $orderStatus) {
                    $updateData['order_status'] = $orderStatus;

                    // 更新相关时间字段
                    if ($orderStatus == '2' && empty($order['ship_time'])) {
                        $updateData['ship_time'] = date('Y-m-d H:i:s');
                    }

                    if ($orderStatus == '4' && empty($order['activate_time'])) {
                        $updateData['activate_time'] = date('Y-m-d H:i:s');
                        if (empty($order['jh_time'])) {
                            $updateData['jh_time'] = date('Y-m-d H:i:s');
                        }
                    }
                }
                
                // 添加其他需要更新的字段
                if (!empty($params['number'])) {
                    $updateData['production_number'] = $params['number']; // 生产号码
                }

                // 处理备注信息 - 使用时间线格式存储，避免重复
                if (!empty($params['mark'])) {
                    $updateData['remark'] = \app\common\helper\OrderRemarkHelper::append(
                        $order['remark'] ?? '',
                        $params['mark']
                    );
                }

                // 处理物流信息
                if (!empty($params['express_name'])) {
                    $updateData['express_company'] = $params['express_name']; // 物流名称
                }

                if (!empty($params['express_sn'])) {
                    $updateData['tracking_number'] = $params['express_sn']; // 物流单号
                }

                // 处理首充和激活时间 - 适配新字段和状态值
                if (isset($params['first_recharge']) && $params['first_recharge'] > 0) {
                    $updateData['recharge_amount'] = $params['first_recharge']; // 首充金额
                    $updateData['recharge_status'] = '1'; // 新状态值：1-已充值 (原recharged)
                }

                if (!empty($params['activated_at'])) {
                    $updateData['activate_time'] = $params['activated_at']; // 激活时间
                }

                // 处理结算方式 - 适配新的数字状态
                if (!empty($params['settlement_method'])) {
                    $jsType = '1'; // 默认次月返
                    switch ((int)$params['settlement_method']) {
                        case 1:
                            $jsType = '0'; // 秒返
                            break;
                        case 2:
                            $jsType = '1'; // 次月返
                            break;
                    }
                    $updateData['js_type'] = $jsType;
                }
                
                // 添加更新时间
                $updateData['update_time'] = date('Y-m-d H:i:s');

                // 执行数据库更新
                $result = false;
                if (!empty($updateData)) {
                    $result = Db::name('order')
                        ->where('id', $order['id'])
                        ->update($updateData);
                }

                // 触发回调通知（如果订单状态发生变化）
                if ($result !== false && isset($updateData['order_status']) && $updateData['order_status'] != $order['order_status']) {
                    try {
                        OrderCallbackService::triggerCallback($order['id'], $updateData['order_status'], '号易上游回调更新');
                    } catch (\Exception $e) {
                        Log::record('[号易回调] 回调通知失败: ' . $e->getMessage(), 'error');
                    }
                }

                // 提交事务
                Db::commit();
                
                // 佣金处理必须在数据库更新提交之后执行
                if ($result !== false && isset($updateData['order_status'])) {
                    $oldStatus = $order['order_status'];
                    $newStatus = $updateData['order_status'];
                    $this->handleCommissionProcessing($order['id'], $order['order_no'], $oldStatus, $newStatus);
                }
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                
                // 记录处理异常日志
                Log::record('[号易回调] 处理异常: ' . $e->getMessage() . "\n" . $e->getTraceAsString(), 'error');
            }
        } catch (\Exception $e) {
            // 记录处理异常日志
            Log::record('[号易回调] 外层处理异常: ' . $e->getMessage() . "\n" . $e->getTraceAsString(), 'error');
        }
        
        // 清除输出缓冲区并输出success
        ob_end_clean();
        echo 'success';
        exit;
    }
    
    /**
     * 获取号易API配置
     * @return array
     */
    protected function getConfig()
    {
        $config = Db::name($this->configTable)
            ->where('api_type', 'haoy')
            ->find();
            
        if ($config) {
            return [
                'api_key' => $config['api_key'] ?? '',
                'api_secret' => $config['api_secret'] ?? '',
                'api_url' => $config['api_url'] ?? 'http://hyapi.yunhaoka.cn',
                'status' => $config['status'] ?? 0
            ];
        }
        
        return [
            'api_key' => '',
            'api_secret' => '',
            'api_url' => 'http://hyapi.yunhaoka.cn',
            'status' => 0
        ];
    }
    
    /**
     * 映射号易订单状态到本地订单状态 - 适配新数字状态
     * @param int $upStatus 上游订单状态
     * @return string|null
     */
    protected function mapOrderStatus($upStatus)
    {
        // 号易状态映射到新的数字状态:
        // 0=已提交, 1=待发货, 2=已发货, 3=待传照片, 4=已激活, 5=已结算, 6=结算失败, 7=审核失败
        switch ($upStatus) {
            case 0:
                return '0'; // 未处理 -> 已提交
            case 1:
                return '1'; // 开卡中 -> 待发货
            case 3:
                return '2'; // 已发货 -> 已发货
            case 4:
                return '2'; // 待激活 -> 已发货 (等待激活阶段)
            case 5:
            case 11:
                return '3'; // 待上传证件 -> 待传照片
            case 6:
                return '7'; // 开卡失败 -> 审核失败
            case 8:
                return '4'; // 已激活 -> 已激活
            case 10:
                return '7'; // 已取消 -> 审核失败
            default:
                return null; // 未知状态，不更新
        }
    }
    

    protected function handleCommissionProcessing($orderId, $orderNo, $oldStatus, $newStatus)
    {
        try {
            // 已激活时：始终更新代理统计
            if ($newStatus == '4' && $oldStatus != '4') {
                $order = Db::name('order')->where('id', $orderId)->find();
                if ($order && !empty($order['agent_id'])) {
                    \app\common\helper\AgentStatsHelper::incrementActivationStats($order['agent_id']);
                }
                
                // 记录待结算佣金
                $commissionService = new \app\common\service\OrderCommissionService();
                $commissionResult = $commissionService->processOrderCommission($orderId);

                if (!$commissionResult['success']) {
                    Log::record("[号易回调] 佣金记录失败: {$orderNo}", 'error');
                }
            }
        } catch (\Exception $e) {
            Log::record("[号易回调] 佣金处理异常: {$orderNo}", 'error');
        }
    }
} 