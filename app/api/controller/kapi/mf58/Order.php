<?php
namespace app\api\controller\kapi\mf58;

use app\api\controller\kapi\mf58\common\MF58Api;
use think\facade\Db;
use think\facade\Log;
use think\Request;
use app\common\service\OrderCallbackService;

/**
 * 58秒返订单处理
 */
class Order
{

    /**
     * Request实例
     * @var Request
     */
    protected $request;

    /**
     * 配置表名
     * @var string
     */
    protected $configTable = 'config_api';
    
    /**
     * API类型标识
     * @var string
     */
    protected $apiType = 'mf58';
    
    /**
     * 产品表名
     * @var string
     */
    protected $productTable = 'product';

    /**
     * 订单表名
     * @var string
     */
    protected $orderTable = 'order';

    /**
     * 构造函数
     */
    public function __construct()
    {
        
        $this->request = request();
    }

    /**
     * 初始化
     */
    public function initialize(): void
    {
        $validator = new \LicenseValidator();
        if (!$validator->validate()) {
            $this->error('授权验证失败');
        }
    }
    
    /**
     * 自动同步订单状态 - 用于宝塔定时任务
     * 接受参数：security_key 系统安全密钥, hours 同步多少小时内的订单(默认24小时), days 同步多少天内的订单
     * 使用示例：http://domain.com/api/kapi.mf58.order/autoSyncStatus?security_key=您的密钥&hours=24
     */
    public function autoSyncStatus($skipAuth = false)
    {
        // 获取安全密钥
        $securityKey = $this->request->param('security_key', '');
        if (empty($securityKey)) {
            $securityKey = $this->request->post('security_key', '');
        }
        
        // 获取同步时间范围（优先使用days参数，兼容hours参数）
        $days = intval($this->request->param('days', 0));
        $hours = intval($this->request->param('hours', 0));
        
        if ($days > 0) {
            $hours = $days * 24; // 将天数转换为小时
        } elseif ($hours <= 0) {
            $hours = 24; // 默认24小时
        }
        
        // 记录所有请求参数，便于调试
        if (function_exists('trace')) {
            trace('58秒返接收到autoSyncStatus请求', 'info', [
                'method' => $this->request->method(),
                'params' => $this->request->param(),
                'security_key' => substr($securityKey, 0, 5) . '****(隐藏)',
                'hours' => $hours
            ]);
        }
        
        // 跳过安全密钥验证（内部调用时使用）
        if (!$skipAuth) {
            // 从数据库获取安全密钥
            $configSecurityKey = $this->getSecurityKey();
        
            if (empty($configSecurityKey)) {
                if (function_exists('trace')) {
                    trace('58秒返未找到安全密钥配置', 'error');
                }
                return json([
                    'code' => 1,
                    'msg' => '系统未配置安全密钥',
                    'data' => null,
                    'time' => time()
                ]);
            }
            
            // 标记是否验证通过
            $keyMatched = false;
            
            // 1. 首先尝试完全匹配
            if (trim($securityKey) === trim($configSecurityKey)) {
                $keyMatched = true;
                if (function_exists('trace')) {
                    trace('58秒返密钥完全匹配成功', 'info');
                }
            }
            // 2. 如果不匹配，尝试忽略大小写匹配
            else if (strtolower(trim($securityKey)) === strtolower(trim($configSecurityKey))) {
                $keyMatched = true;
                if (function_exists('trace')) {
                    trace('58秒返密钥忽略大小写匹配成功', 'info');
                }
            }
            
            // 记录密钥验证结果
            if (function_exists('trace')) {
                trace('58秒返安全密钥验证结果', 'info', [
                    'matched' => $keyMatched,
                    'provided_length' => strlen($securityKey),
                    'config_length' => strlen($configSecurityKey),
                ]);
            }
            
            // 如果密钥不匹配，返回错误
            if (!$keyMatched) {
                // 记录验证失败日志
                if (function_exists('trace')) {
                    trace('58秒返自动同步订单状态验证失败', 'warning', [
                        'has_key' => !empty($securityKey)
                    ]);
                }
                return json([
                    'code' => 1,
                    'msg' => '安全密钥无效或不匹配',
                    'data' => null,
                    'time' => time()
                ]);
            }
        }
        
        // 密钥验证通过，开始同步订单状态
        if (function_exists('trace')) {
            trace('58秒返开始自动同步订单状态', 'info', [
                'from' => 'autoSyncStatus',
                'hours' => $hours
            ]);
        }
        
        // 获取API配置
        $config = $this->getConfig();
        
        if (empty($config['api_key']) || empty($config['api_url'])) {
            if (function_exists('trace')) {
                trace('58秒返API配置不完整', 'error');
            }
            return json([
                'code' => 1,
                'msg' => 'API配置不完整',
                'data' => null,
                'time' => time()
            ]);
        }

        if (!$config['status']) {
            if (function_exists('trace')) {
                trace('58秒返API未启用', 'error');
            }
            return json([
                'code' => 1,
                'msg' => 'API未启用',
                'data' => null,
                'time' => time()
            ]);
        }
        
        // 计算时间范围
        $startTime = date('Y-m-d H:i:s', time() - $hours * 3600);
        
        // 查询需要同步的订单
        // 只排除已激活(4)和已结算(5)的订单，其他状态都需要同步
        $orders = Db::name($this->orderTable)
            ->where('api_name', '58秒返')
            ->where('create_time', '>=', $startTime)
            ->where('order_status', 'not in', ['4', '5']) // 只排除已激活和已结算的订单
            ->select()
            ->toArray();
        
        if (function_exists('trace')) {
            trace('58秒返需要同步的订单数量: ' . count($orders), 'info');
        }
        
        if (empty($orders)) {
            return json([
                'code' => 0,
                'msg' => '没有需要同步的订单',
                'data' => [
                    'total' => 0,
                    'success' => 0,
                    'no_change' => 0,
                    'fail' => 0,
                    'updated_orders' => []
                ],
                'time' => time()
            ]);
        }
        
        // 初始化API
        $api = new MF58Api($config);
        
        // 同步结果统计
        $successCount = 0;
        $failCount = 0;
        $noChangeCount = 0;
        $updatedOrders = [];
        
        // 遍历订单进行同步
        foreach ($orders as $order) {
            try {
                // 检查上游订单号是否有效（不是错误信息）
                $upOrderNo = $order['up_order_no'] ?? '';
                $isValidUpOrderNo = false;

                if (!empty($upOrderNo)) {
                    // 检查是否是有效的订单号格式（通常是字母数字组合，不包含中文和特殊字符）
                    if (preg_match('/^[A-Z0-9]+$/i', $upOrderNo) && strlen($upOrderNo) >= 10) {
                        $isValidUpOrderNo = true;
                    } else {
                        // 记录无效的上游订单号
                        if (function_exists('trace')) {
                            trace('58秒返发现无效的上游订单号: ' . $upOrderNo . '，订单ID: ' . $order['id'], 'warning');
                        }
                    }
                }

                // 如果没有有效的上游订单号，跳过此订单
                if (!$isValidUpOrderNo) {
                    if (function_exists('trace')) {
                        trace('58秒返跳过无效订单: ' . $order['order_no'] . '，原因：无有效上游订单号', 'warning');
                    }
                    $noChangeCount++;
                    continue;
                }

                // 准备参数 - 使用有效的上游订单号
                $apiParams = [
                    'orderNo' => $upOrderNo
                ];

                if (function_exists('trace')) {
                    trace('58秒返查询订单参数: ' . json_encode($apiParams, JSON_UNESCAPED_UNICODE), 'info');
                }

                // 发起API请求
                $result = $api->queryOrderStatus($apiParams);
                
                // 记录API响应
                if (function_exists('trace')) {
                    trace('[MF58] API响应: ' . json_encode($result, JSON_UNESCAPED_UNICODE), 'info');
                }
                
                // 解析状态数据 - 处理多层嵌套的JSON结构
                $statusData = null;
                
                // 检查API调用是否成功
                // queryOrderStatus方法成功时返回code=1，失败时返回code=0
                if (isset($result['code']) && $result['code'] == 0) {
                    if (function_exists('trace')) {
                        trace('58秒返API返回错误: ' . ($result['msg'] ?? '未知错误'), 'warning');
                    }
                    $failCount++;
                    continue;
                }
                
                // 处理嵌套的数据结构

                $statusData = null;

                // 58秒返API的标准响应格式：{"code":0,"data":[{订单数据}],"msg":"查询成功"}
                if (isset($result['code']) && $result['code'] == 0 && isset($result['data']) && is_array($result['data'])) {
                    // 直接从data数组中获取第一个订单数据
                    if (count($result['data']) > 0) {
                        $statusData = $result['data'][0]; // 取第一个订单数据
                    }
                } elseif (isset($result['data']['data'])) {
                    // 嵌套结构
                    $statusData = $result['data']['data'];
                } elseif (isset($result['data']) && is_array($result['data']) && !isset($result['data']['code'])) {
                    // 直接数据结构
                    $statusData = $result['data'];
                }


                // 如果没有数据，可能是订单号不存在
                if (empty($statusData) || (is_array($statusData) && count($statusData) === 0)) {
                    if (function_exists('trace')) {
                        trace('58秒返API未返回订单数据，可能订单不存在: ' . $order['order_no'], 'warning');
                    }
                    $failCount++;
                    continue;
                }
                
                // 记录订单原始状态
                $originalStatus = $order['order_status'];

                // 强制记录调试信息
                if (function_exists('trace')) {
                    trace('58秒返准备更新订单状态: 订单=' . $order['order_no'] . ', 原状态=' . $originalStatus . ', 状态数据=' . json_encode($statusData, JSON_UNESCAPED_UNICODE), 'info');
                }

                // 更新订单状态
                $this->updateOrderStatus($order, $statusData);

                // 强制记录更新完成
                if (function_exists('trace')) {
                    trace('58秒返updateOrderStatus方法执行完成', 'info');
                }
                
                // 查询更新后的订单状态
                $updatedOrder = Db::name($this->orderTable)->where('id', $order['id'])->find();
                
                // 判断状态是否有变化
                if ($updatedOrder['order_status'] != $originalStatus) {
                    $successCount++;
                    $updatedOrders[] = [
                        'order_no' => $order['order_no'],
                        'old_status' => $originalStatus,
                        'new_status' => $updatedOrder['order_status']
                    ];
                } else {
                    $noChangeCount++;
                }
                
                // 避免请求过于频繁
                usleep(200000); // 休眠200毫秒
                
            } catch (\Exception $e) {
                if (function_exists('trace')) {
                    trace('58秒返同步订单异常: ' . $e->getMessage(), 'error');
                }
                $failCount++;
            }
        }
        
        // 返回同步结果
        return json([
            'code' => 0,
            'msg' => '订单状态同步完成',
            'data' => [
                'total' => count($orders),
                'success' => $successCount,
                'no_change' => $noChangeCount,
                'fail' => $failCount,
                'updated_orders' => $updatedOrders
            ],
            'time' => time()
        ]);
    }
    
    /**
     * 获取安全密钥
     * @return string
     */
    protected function getSecurityKey()
    {
        // 尝试从多个可能的表中查找安全密钥
        $securityKey = '';
        $tables = ['system_config', 'config', 'config_api', 'settings'];

        foreach ($tables as $table) {
            try {
                // 对于 system_config 表，使用 config_key 字段
                if ($table === 'system_config') {
                    $result = Db::name($table)->where('config_key', 'security_key')->find();
                    if ($result && !empty($result['config_value'])) {
                        $securityKey = $result['config_value'];
                        if (function_exists('trace')) {
                            trace('58秒返从system_config表找到安全密钥', 'info');
                        }
                        break;
                    }
                } else {
                    // 对于其他表，使用 name 字段
                    $result = Db::name($table)->where('name', 'security_key')->find();
                    if ($result && !empty($result['value'])) {
                        $securityKey = $result['value'];
                        if (function_exists('trace')) {
                            trace('58秒返从' . $table . '表找到安全密钥', 'info');
                        }
                        break;
                    }
                }
            } catch (\Throwable $e) {
                // 表不存在或其他错误，继续检查下一个表
                if (function_exists('trace')) {
                    trace('58秒返检查表' . $table . '失败: ' . $e->getMessage(), 'warning');
                }
                continue;
            }
        }

        if (empty($securityKey)) {
            if (function_exists('trace')) {
                trace('58秒返未在任何表中找到安全密钥', 'error');
            }
        }

        return $securityKey;
    }
    
    /**
     * 测试API响应
     */
    public function test()
    {
        return json([
            'code' => 0,
            'msg' => '测试成功',
            'data' => ['test' => 'ok'],
            'time' => time()
        ]);
    }

    /**
     * 获取省市区
     */
    public function getRegion()
    {
        try {
            // 兼容前端传递的product_id参数
            $packageId = $this->request->param('packageId/d', 0);
            if (empty($packageId)) {
                $packageId = $this->request->param('product_id/d', 0);
            }
            $t = $this->request->param('t/d', 0); // 0=省份，1=城市，2=区域
            $code = $this->request->param('code/s', '0'); // 区域编码

            if (empty($packageId)) {
                return json([
                    'code' => 1,
                    'msg' => '套餐ID不能为空',
                    'data' => [],
                    'time' => time()
                ]);
            }

            // 根据产品ID获取产品number
            $product = Db::name('product')->where('id', $packageId)->field('number')->find();
            if (empty($product)) {
                return json([
                    'code' => 1,
                    'msg' => '产品不存在',
                    'data' => [],
                    'time' => time()
                ]);
            }

            $productNumber = $product['number'];
            if (empty($productNumber)) {
                return json([
                    'code' => 1,
                    'msg' => '产品编号为空',
                    'data' => [],
                    'time' => time()
                ]);
            }

            // 获取API配置
            $config = $this->getConfig();

            if (empty($config['api_key']) || empty($config['api_url'])) {
                return json([
                    'code' => 1,
                    'msg' => 'API配置不完整',
                    'data' => [],
                    'time' => time()
                ]);
            }

            if (!$config['status']) {
                return json([
                    'code' => 1,
                    'msg' => 'API未启用',
                    'data' => [],
                    'time' => time()
                ]);
            }

            // 初始化API类
            $api = new MF58Api($config);

            // 根据类型获取不同数据
            if ($t == 0) {
                // 获取省份，使用产品编号
                $result = $api->getProvinces($productNumber);
            } elseif ($t == 1) {
                // 获取城市，需要省份名称和编码
                $provinceName = $this->request->param('provinceName/s', '');
                $result = $api->getCities($productNumber, $provinceName, $code);
            } elseif ($t == 2) {
                // 获取区县，需要省份、城市名称和城市编码
                $provinceName = $this->request->param('provinceName/s', '');
                $cityName = $this->request->param('cityName/s', '');
                $result = $api->getDistricts($productNumber, $provinceName, $cityName, $code);
            } else {
                return json([
                    'code' => 1,
                    'msg' => '无效的数据类型',
                    'data' => [],
                    'time' => time()
                ]);
            }

            // 记录API返回结果
            Log::info('[MF58] getRegion API结果: ' . json_encode($result, JSON_UNESCAPED_UNICODE));

            if ($result['code'] != 0) {
                $errorMsg = $result['msg'] ?? '获取区域信息失败';
                if (strpos($errorMsg, '系统出错了') !== false) {
                    $errorMsg = "产品编号{$productNumber}在58秒返系统中不存在或已下架，请联系管理员更新产品配置";
                }
                Log::info('[MF58] getRegion 返回错误: ' . $errorMsg);
                return json([
                    'code' => 1,
                    'msg' => $errorMsg,
                    'data' => [],
                    'time' => time()
                ]);
            }

            // 提取实际的地区数据 - 58秒返API成功时code为0，数据直接在data字段中
            $regionData = $result['data'] ?? [];
            Log::info('[MF58] getRegion 提取的区域数据: ' . json_encode($regionData, JSON_UNESCAPED_UNICODE));

            // 返回区域信息
            $response = [
                'code' => 0,
                'msg' => '获取成功',
                'data' => $regionData,
                'time' => time()
            ];
            Log::info('[MF58] getRegion 最终响应: ' . json_encode($response, JSON_UNESCAPED_UNICODE));

            return json($response);

        } catch (\Exception $e) {
            return json([
                'code' => 1,
                'msg' => '获取区域信息异常: ' . $e->getMessage(),
                'data' => [],
                'time' => time()
            ]);
        }
    }
    
    /**
     * 查询可用号码
     */
    public function queryNumbers()
    {
        // 兼容前端发送的product_id参数
        $packageId = $this->request->param('packageId/d', 0);
        if (empty($packageId)) {
            $packageId = $this->request->param('product_id/d', 0);
        }
        $key = $this->request->param('key/s', ''); // 选号关键词
        $server = $this->request->param('server/d', 0); // 添加server参数
        $debug = $this->request->param('debug/b', false); // 添加debug参数
        
        if (empty($packageId)) {
            $this->error('套餐ID不能为空');
        }
        
        try {
            // 获取API配置
            $config = $this->getConfig();
            
            // 调试模式下返回配置信息
            if ($debug) {
                $safeConfig = $config;
                if (isset($safeConfig['api_key']) && $safeConfig['api_key']) {
                    $safeConfig['api_key'] = substr($safeConfig['api_key'], 0, 3) . '***' . substr($safeConfig['api_key'], -3);
                }
            }
            
            if (empty($config['api_key']) || empty($config['api_url'])) {
                $errorMsg = 'API配置不完整';
                if ($debug) {
                    $errorMsg .= ': ' . json_encode($safeConfig);
                }
                $this->error($errorMsg);
            }
            
            if (!$config['status']) {
                $this->error('API未启用');
            }
            
            // 查询产品中的number字段作为实际API调用参数
            $product = Db::name($this->productTable)->where('id', $packageId)->find();
            if (empty($product) || empty($product['number'])) {
                $this->error('未找到产品或产品未配置对接编号');
            }
            
            $apiPackageId = $product['number']; // 使用number字段作为API调用参数
            
            // 记录详细日志
            if (function_exists('trace')) {
                trace('[58秒返] 查询号码开始，参数: ' . json_encode([
                    'localPackageId' => $packageId,
                    'apiPackageId' => $apiPackageId,
                    'key' => $key,
                    'server' => $server,
                    'debug' => $debug,
                    'api_url' => $config['api_url'],
                    'api_key_length' => strlen($config['api_key'])
                ], JSON_UNESCAPED_UNICODE), 'info');
            }
            
            // 初始化API类
            $api = new MF58Api($config);
            
            // 查询号码 - 使用API对接编号
            $apiResult = $api->getNumbers($apiPackageId, $key);
            
            // 直接获取原始响应
            $rawResult = $apiResult['raw_response'] ?? null;
            
            // 记录API原始响应
            if (function_exists('trace')) {
                trace('[58秒返] API原始响应: ' . json_encode($rawResult, JSON_UNESCAPED_UNICODE), 'info');
            }
            
            // 检查API响应是否成功
            // 注意：58秒返API中code=0表示成功
            if (!isset($apiResult['code']) || $apiResult['code'] != 0) {
                // 记录错误详情
                if (function_exists('trace')) {
                    trace('[58秒返] 查询号码失败: ' . json_encode($apiResult, JSON_UNESCAPED_UNICODE), 'error');
                }
                
                $errorMsg = $apiResult['msg'] ?? '查询号码失败';
                
                // 调试模式下返回更多信息
                if ($debug) {
                    $errorMsg .= ' [Debug: ' . json_encode([
                        'api_url' => $config['api_url'],
                        'packageId' => $packageId,
                        'apiPackageId' => $apiPackageId,
                        'result' => $apiResult,
                        'raw_result' => $rawResult
                    ], JSON_UNESCAPED_UNICODE) . ']';
                }
                
                $this->error($errorMsg);
            }
            
            // 处理返回数据 - 从API结果中提取号码列表
            $result = $apiResult['data'] ?? [];
            $numbers = [];
            
            // 确保数据格式一致 - 兼容多种可能的返回格式
            if (isset($result['data']) && is_array($result['data'])) {
                // 如果data字段存在且是数组，使用它
                $numbers = $result['data'];
            } else if (isset($result['list']) && is_array($result['list'])) {
                // 如果list字段存在且是数组，使用它
                $numbers = $result['list'];
            } else if (isset($result['Msg']) && is_array($result['Msg'])) {
                // 如果Msg字段存在且是数组，使用它
                $numbers = $result['Msg'];
            } else if (is_array($result)) {
                // 如果result本身就是数组，尝试直接使用
                $numbers = $result;
            }
            
            // 记录成功日志
            if (function_exists('trace')) {
                trace('[58秒返] 查询号码成功，返回' . count($numbers) . '个号码', 'info');
            }
            
            // 按照前端期望的格式返回数据
            return json([
                'code' => 1,
                'msg' => '查询成功',
                'data' => [
                    'list' => $numbers,
                    'total' => count($numbers)
                ]
            ]);
            
        } catch (\Exception $e) {
            // 记录异常详情
            if (function_exists('trace')) {
                trace('[58秒返] 查询号码异常: ' . $e->getMessage() . "\n" . $e->getTraceAsString(), 'error');
            }
            
            $errorMsg = '查询号码异常: ' . $e->getMessage();
            
            // 调试模式下返回更多信息
            if ($debug) {
                $errorMsg .= ' [Debug: ' . json_encode([
                    'trace' => $e->getTraceAsString(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ], JSON_UNESCAPED_UNICODE) . ']';
            }
            
            $this->error($errorMsg);
        }
    }
    
    /**
     * 提交订单
     */
    public function submit()
    {
        // 参数验证
        $params = $this->request->param();
        $debug = $this->request->param('debug/b', false);
        
        if (empty($params['order_no'])) {
            $this->error('订单号不能为空');
        }
        
        if (empty($params['product_id'])) {
            $this->error('产品ID不能为空');
        }
        
        if (empty($params['customer_name'])) {
            $this->error('客户姓名不能为空');
        }
        
        // 兼容前端传递的多种电话字段
        $phone = $params['phone'] ?? $params['customer_phone'] ?? $params['order_phone'] ?? '';
        if (empty($phone)) {
            $this->error('联系电话不能为空');
        }
        // 统一使用phone字段
        $params['phone'] = $phone;
        
        // 兼容前端传递的customer_idcard参数
        $idNumber = $params['id_number'] ?? $params['customer_idcard'] ?? '';
        if (empty($idNumber)) {
            $this->error('身份证号不能为空');
        }

        // 验证身份证号
        if (!$this->validateIdNumber($idNumber)) {
            $this->error('身份证号格式不正确');
        }

        // 确保身份证号长度不超过18位（数据库字段限制）
        if (strlen($idNumber) > 18) {
            $idNumber = substr($idNumber, 0, 18);
        }

        // 统一使用id_number字段
        $params['id_number'] = $idNumber;
        
        // 兼容前端传递的customer_address参数
        $address = $params['address'] ?? $params['customer_address'] ?? '';
        if (empty($address)) {
            $this->error('地址不能为空');
        }
        // 统一使用address字段
        $params['address'] = $address;
        
        // 是否需要选号，0-否 1-是，默认不需要
        $selectNumber = isset($params['selectNumber']) ? intval($params['selectNumber']) : 0;
        
        // 如果需要选号，才验证号码是否为空
        if ($selectNumber && empty($params['number'])) {
            $this->error('号码不能为空');
        }
        
        if (empty($params['shop_code'])) {
            $this->error('店铺代码不能为空');
        }
        
        // 生成订单号
        if (empty($params['order_no'])) {
            $params['order_no'] = \app\common\helper\OrderHelper::generateOrderNo();
        }

        try {
            // 开启事务
            Db::startTrans();

            try {
                // 查询产品信息
                $product = Db::name($this->productTable)
                    ->where('id', $params['product_id'])
                    ->find();
                
                if (empty($product)) {
                    Db::rollback();
                    $this->error('产品不存在');
                }
                
                if ($product['api_name'] !== '58秒返') {
                    Db::rollback();
                    $this->error('产品类型不匹配');
                }
                
                if ($product['status'] != 1) {
                    Db::rollback();
                    $this->error('产品已下架');
                }
                
                // 获取API配置
                $config = $this->getConfig();
                
                if (empty($config['api_key']) || empty($config['api_url'])) {
                    Db::rollback();
                    $this->error('API配置不完整');
                }
                
                if (!$config['status']) {
                    Db::rollback();
                    $this->error('API未启用');
                }
                
                // 根据shop_code获取店铺所有者的agent_id
                $shopCode = $params['shop_code'] ?? '';
                $userId = 0; // 默认值
                
                if (!empty($shopCode)) {
                    // 查询店铺所有者
                    $shopInfo = Db::name('agent_shop')
                        ->where('shop_code', $shopCode)
                        ->find();
                        
                    if ($shopInfo && isset($shopInfo['agent_id'])) {
                        $userId = $shopInfo['agent_id'];
                        
                        if (function_exists('trace')) {
                            trace('58秒返找到店铺所有者: agent_id=' . $userId . ', shop_code=' . $shopCode, 'info');
                        }
                    } else {
                        if (function_exists('trace')) {
                            trace('58秒返警告: 未找到店铺所有者，shop_code=' . $shopCode, 'warning');
                        }
                    }
                } else {
                    // 直接从参数获取agent_id
                    $userId = $params['agent_id'] ?? 0;
                    if ($userId > 0 && function_exists('trace')) {
                        trace('58秒返使用参数中的agent_id: ' . $userId, 'info');
                    }
                }
                
                // 检查API响应是否包含重复下单提示
                try {
                    // 初始化API类
                    $api = new MF58Api($config);
                    
                    // 地区编码处理 - 如果没有编码，尝试获取或使用默认值
                    $provinceCode = $params['province_code'] ?? '';
                    $cityCode = $params['city_code'] ?? '';
                    $districtCode = $params['district_code'] ?? '';
                    
                    // 如果缺少地区编码，记录警告但不阻止提交
                    $missingCodes = [];
                    if (empty($provinceCode)) $missingCodes[] = 'ProvincesCode';
                    if (empty($cityCode)) $missingCodes[] = 'CityCode';
                    if (empty($districtCode)) $missingCodes[] = 'CountyCode';
                    
                    if (!empty($missingCodes) && function_exists('trace')) {
                        trace('[MF58] 订单提交缺少可选参数: ' . implode(', ', $missingCodes), 'warning');
                    }

                    // 准备提交参数 - 确保使用正确的大小写和字段名
                    $submitParams = [
                        'type' => $params['product_channel'] ?? $product['number'] ?? '',
                        'Key' => $config['api_key'],
                        'ProvincesCode' => $provinceCode,
                        'Provinces' => $params['province'] ?? '',
                        'CityCode' => $cityCode,
                        'City' => $params['city'] ?? '',
                        'CountyCode' => $districtCode,
                        'County' => $params['district'] ?? '',
                        'Address' => $params['detail_address'] ?? $params['address'] ?? $params['customer_address'] ?? '',
                        'Name' => $params['customer_name'],
                        'Phone' => $params['phone'],
                        'Idnum' => $params['id_number'],
                    ];
                    
                    // 添加选择的号码参数
                    $selectedNumber = $params['number'] ?? $params['selected_number'] ?? '';
                    if (!empty($selectedNumber)) {
                        $submitParams['Sim'] = $selectedNumber;
                    }
                    
                    // 安全记录参数，省略身份证后几位
                    $logParams = $submitParams;
                    $logParams['Idnum'] = substr($submitParams['Idnum'], 0, 4) . '**********' . substr($submitParams['Idnum'], -4);
                    $logParams['Key'] = substr($submitParams['Key'], 0, 3) . '***' . substr($submitParams['Key'], -3);
                    
                    // 记录提交参数
                    if (function_exists('trace')) {
                        trace('58秒返提交订单开始，原始请求参数: ' . json_encode($params, JSON_UNESCAPED_UNICODE), 'info');
                        trace('58秒返提交订单，处理后请求参数: ' . json_encode($params, JSON_UNESCAPED_UNICODE), 'info');
                        trace('58秒返提交订单参数: ' . json_encode($logParams, JSON_UNESCAPED_UNICODE), 'info');
                    }

                    // 先调用API提交订单，成功后再创建本地订单
                    $apiResult = $api->submitOrder($submitParams);
                    
                    // 记录API响应
                    if (function_exists('trace')) {
                        trace('58秒返API响应: ' . json_encode($apiResult, JSON_UNESCAPED_UNICODE), 'info');
                    }
                    
                    // 检查API调用结果
                    $isApiSuccess = false;
                    $upOrderNo = '';
                    $errorMessage = '';
                    
                    // 58秒返API判断：只要内层data.Code=0就表示业务成功（外层code可能是0或1）
                    if (isset($apiResult['data']['Code']) && (int)$apiResult['data']['Code'] === 0) {
                        
                        $isApiSuccess = true;

                        // 提取上游订单号 - 业务成功时data.Msg才是订单号
                        if (isset($apiResult['data']['Msg']) && is_string($apiResult['data']['Msg'])) {
                            $upOrderNo = $apiResult['data']['Msg'];
                        } else if (isset($apiResult['data']['orderid'])) {
                            $upOrderNo = $apiResult['data']['orderid'];
                        }
                        
                        if (function_exists('trace')) {
                            trace('58秒返API调用成功，上游订单号: ' . $upOrderNo, 'info');
                        }
                    } else {
                        // API调用失败，直接返回错误，不创建本地订单
                        // 优先从业务层获取错误信息
                        if (isset($apiResult['data']['Msg']) && is_string($apiResult['data']['Msg'])) {
                            $errorMessage = $apiResult['data']['Msg'];
                        } else {
                            $errorMessage = $apiResult['msg'] ?? '未知错误';
                        }
                        
                        // 检查是否是重复下单
                        if (strpos($errorMessage, '重复下单') !== false || strpos($errorMessage, '已提交过') !== false) {
                            if (function_exists('trace')) {
                                trace('58秒返检测到重复下单: ' . $params['order_no'], 'warning');
                            }
                            return json([
                                'code' => 0,
                                'msg' => $errorMessage . '，请查询历史订单',
                                'data' => ['duplicate' => true]
                            ]);
                        }
                        
                        if (function_exists('trace')) {
                            trace('58秒返API调用失败: ' . $errorMessage, 'error');
                        }
                        
                        return json([
                            'code' => 0,
                            'msg' => $errorMessage,
                            'data' => [
                                'order_no' => $params['order_no'],
                                'error_type' => 'api_failed'
                            ]
                        ]);
                    }
                    
                    // API调用成功，创建本地订单
                    
                    // 单独查询产品佣金信息（不暴露给前端）
                    $productCommissionData = Db::name($this->productTable)
                        ->field('commission')
                        ->where('id', $params['product_id'])
                        ->find();

                    $productCommission = floatval($productCommissionData['commission'] ?? 0);
                    
                    // 使用佣金计算服务计算显示佣金
                    $commissionService = new \app\common\service\CommissionCalculationService();
                    $commissionResult = $commissionService->calculateTotalDisplayCommission($productCommission, $userId, $params['product_id']);
                    
                    $actualCommission = $commissionResult['success'] ? $commissionResult['total_commission'] : $productCommission;
                    
                    if (function_exists('trace')) {
                        trace('58秒返计算佣金: 产品ID=' . $params['product_id'] . ', 原始佣金=' . $productCommission . ', 实际佣金=' . $actualCommission . ', 基础佣金=' . ($commissionResult['base_commission'] ?? 0) . ', 密价佣金=' . ($commissionResult['secret_price_commission'] ?? 0), 'info');
                    }
                    
                    $orderData = [
                        'order_no' => $params['order_no'],
                        'up_order_no' => '', // 上游订单号，提单成功后会更新
                        'shop_code' => $params['shop_code'] ?? '',
                        'product_id' => $params['product_id'],
                        'product_name' => $params['product_name'] ?? $product['name'],
                        'product_image' => $product['product_image'] ?? '',
                        'order_status' => '0', // 0-已提交
                        'customer_name' => $params['customer_name'],
                        // 地址字段拆分
                        'province' => $params['province'] ?? '',
                        'city' => $params['city'] ?? '',
                        'district' => $params['district'] ?? '',
                        'address' => $params['detail_address'] ?? $params['address'] ?? '',
                        'phone' => $params['phone'],
                        'idcard' => $params['id_number'],
                        'api_name' => '58秒返',
                        'create_time' => date('Y-m-d H:i:s'),
                        'update_time' => date('Y-m-d H:i:s'),
                        'remark' => $params['remark'] ?? '',
                        'photo_status' => '0', // 无需上传照片
                        'production_number' => $params['number'] ?? '',
                        'agent_id' => $userId, // 使用agent_id字段存储代理ID
                        'commission' => $actualCommission, // 保存计算后的实际佣金
                        'js_type' => '0', // 结算模式：0-秒返
                        'name_count' => Db::name($this->orderTable)->where('customer_name', $params['customer_name'])->count() + 1,
                        'id_card_count' => Db::name($this->orderTable)->where('idcard', $params['id_number'])->count() + 1,
                        'phone_count' => Db::name($this->orderTable)->where('phone', $params['phone'])->count() + 1,
                    ];

                    if (function_exists('trace')) {
                        trace('58秒返保存订单地址信息: ' . json_encode([
                            'address' => $orderData['address']
                        ], JSON_UNESCAPED_UNICODE), 'info');
                    }
                    
                    $saveResult = \app\common\service\PaidOrderSubmitSyncService::saveOrder($orderData, '58秒返', '', $this->orderTable);
                    $orderId = $saveResult['order_id'];
                    $isNewOrder = $saveResult['inserted'];
                    
                    // 生成订单快照
                    if ($isNewOrder && $orderId && $orderData['agent_id']) {
                        \app\common\service\CommissionCalculationService::generateOrderSnapshot($orderId, $orderData['agent_id']);
                    }
                    
                    if (!$orderId) {
                        Db::rollback();
                        $this->error('订单保存失败');
                    }
                    
                    // 更新代理订单统计
                    if ($isNewOrder && $orderId && $orderData['agent_id']) {
                        \app\common\helper\AgentStatsHelper::incrementOrderStats($orderData['agent_id']);
                    }
                    
                    // API已经成功，直接更新订单信息

                    // 更新上游订单号
                    if (!empty($upOrderNo)) {
                        Db::name($this->orderTable)->where('id', $orderId)->update([
                            'up_order_no' => $upOrderNo
                        ]);
                    } else {
                        // API成功但没有返回订单号
                        if (function_exists('trace')) {
                            trace('58秒返API调用成功但未返回上游订单号', 'warning');
                        }
                    }
                    
                    // 提交事务
                    Db::commit();
                    
                    // API调用成功，返回成功响应
                    return json([
                        'code' => 1,
                        'msg' => '订单提交成功',
                        'data' => [
                            'order_no' => $params['order_no'],
                            'up_order_no' => $upOrderNo
                        ]
                    ]);
                } catch (\Exception $apiException) {
                    Db::rollback();
                    if (function_exists('trace')) {
                        trace('58秒返API调用异常: ' . $apiException->getMessage() . "\n" . $apiException->getTraceAsString(), 'error');
                    }
                    
                    // 在调试模式下返回更详细的错误信息
                    if ($debug) {
                        $errorMsg = 'API调用异常: ' . $apiException->getMessage();
                        $debugInfo = [
                            'trace' => $apiException->getTraceAsString(),
                            'file' => $apiException->getFile(),
                            'line' => $apiException->getLine(),
                            'params' => $logParams ?? []
                        ];
                        
                        // 直接返回错误信息，不抛出异常
                        return json([
                            'code' => 0,
                            'msg' => $errorMsg,
                            'data' => [
                                'debug_info' => $debugInfo
                            ]
                        ]);
                    } else {
                        // 直接返回错误信息，不抛出异常
                        return json([
                            'code' => 0,
                            'msg' => 'API调用异常: ' . $apiException->getMessage(),
                            'data' => []
                        ]);
                    }
                }
            } catch (\Exception $dbException) {
                // 捕获数据库相关异常
                Db::rollback();
                
                // 记录错误
                if (function_exists('trace')) {
                    trace('58秒返数据库操作异常: ' . $dbException->getMessage() . "\n" . $dbException->getTraceAsString(), 'error');
                }
                
                // 返回错误
                if ($debug) {
                    // 直接返回错误信息，不抛出异常
                    return json([
                        'code' => 0,
                        'msg' => '数据库操作异常: ' . $dbException->getMessage(),
                        'data' => [
                            'debug_info' => [
                                'file' => $dbException->getFile(),
                                'line' => $dbException->getLine()
                            ]
                        ]
                    ]);
                } else {
                    // 直接返回错误信息，不抛出异常
                    return json([
                        'code' => 0,
                        'msg' => '数据库操作异常: ' . $dbException->getMessage(),
                        'data' => []
                    ]);
                }
            }
        } catch (\Exception $e) {
            // 回滚事务
            try {
                Db::rollback();
            } catch (\Exception $rollbackEx) {
                // 记录回滚事务异常
                if (function_exists('trace')) {
                    trace('58秒返回滚事务异常: ' . $rollbackEx->getMessage(), 'error');
                }
            }
            
            // 记录错误
            if (function_exists('trace')) {
                trace('58秒返提交订单异常: ' . $e->getMessage() . "\n" . $e->getTraceAsString(), 'error');
            }
            
            // 返回错误（增强错误信息）
            if ($debug) {
                // 直接返回错误信息，不抛出异常
                return json([
                    'code' => 0,
                    'msg' => '订单提交异常: ' . $e->getMessage(),
                    'data' => [
                        'debug_info' => [
                            'file' => $e->getFile(),
                            'line' => $e->getLine()
                        ]
                    ]
                ]);
            } else {
                // 直接返回错误信息，不抛出异常
                return json([
                    'code' => 0,
                    'msg' => '订单提交异常: ' . $e->getMessage(),
                    'data' => []
                ]);
            }
        }
    }
    
    /**
     * 获取订单列表
     */
    public function index()
    {
        $page = $this->request->param('page/d', 1);
        $limit = $this->request->param('limit/d', 10);
        $orderNo = $this->request->param('order_no');
        $phone = $this->request->param('phone');
        $name = $this->request->param('name');
        $status = $this->request->param('status');
        
        $where = [];
        
        // 根据订单号查询
        if ($orderNo) {
            $where[] = ['a.order_no|a.up_order_no', 'like', "%{$orderNo}%"];
        }
        
        // 根据手机号查询
        if ($phone) {
            $where[] = ['a.phone', 'like', "%{$phone}%"];
        }
        
        // 根据姓名查询
        if ($name) {
            $where[] = ['a.customer_name', 'like', "%{$name}%"];
        }
        
        // 根据状态查询
        if ($status) {
            $where[] = ['a.order_status', '=', $status];
        }
        
        $where[] = ['a.api_name', '=', '58秒返']; // 只查询58秒返的订单
        
        $total = Db::name($this->orderTable)
            ->alias('a')
            ->where($where)
            ->count();
        
        $list = Db::name($this->orderTable)
            ->alias('a')
            ->field('a.*, b.product_name as product_title')
            ->leftJoin($this->productTable.' b', 'a.product_id = b.id')
            ->where($where)
            ->page($page, $limit)
            ->order('a.id', 'desc')
            ->select()
            ->toArray();
        
        $this->success('获取成功', [
            'list' => $list,
            'total' => $total
        ]);
    }
    
    /**
     * 查询订单详细信息 - 用于定时任务和手动查询
     * 支持参数：
     * - security_key: 安全密钥（用于定时任务调用）
     * - order_no: 订单号（必需）
     * 使用示例：http://domain.com/api/kapi.mf58.order/queryOrder?security_key=您的密钥&order_no=订单号
     */
    public function queryOrder()
    {
        // 获取安全密钥（如果提供）
        $securityKey = $this->request->param('security_key', '');

        // 如果提供了安全密钥，进行验证
        if (!empty($securityKey)) {
            $configSecurityKey = $this->getSecurityKey();

            if (empty($configSecurityKey)) {
                $this->error('系统未配置安全密钥');
            }

            // 验证密钥
            $keyMatched = false;
            if (trim($securityKey) === trim($configSecurityKey)) {
                $keyMatched = true;
            } else if (strtolower(trim($securityKey)) === strtolower(trim($configSecurityKey))) {
                $keyMatched = true;
            }

            if (!$keyMatched) {
                $this->error('安全密钥无效或不匹配');
            }
        }

        $orderNo = $this->request->param('order_no');
        if (empty($orderNo)) {
            $this->error('订单号不能为空');
        }

        if (function_exists('trace')) {
            trace('开始查询58秒返订单详细信息: ' . $orderNo, 'info');
        }

        // 获取API配置
        $config = $this->getConfig();

        if (empty($config['api_key']) || empty($config['api_url'])) {
            $this->error('API配置不完整');
        }

        if (!$config['status']) {
            $this->error('API未启用');
        }

        // 初始化API
        $api = new MF58Api($config);

        try {
            // 调用58秒返订单查询API
            $result = $api->queryOrderDetail($orderNo);

            if (function_exists('trace')) {
                trace('[MF58] 订单查询API响应: ' . json_encode($result, JSON_UNESCAPED_UNICODE), 'info');
            }

            // 58秒返API成功时code为0
            if ($result['code'] != 0) {
                $this->error($result['msg'] ?? '查询订单失败');
            }

            $this->success('查询成功', $result['data'] ?? []);

        } catch (\Exception $e) {
            if (function_exists('trace')) {
                trace('58秒返查询订单异常: ' . $e->getMessage(), 'error');
            }
            $this->error('查询订单异常: ' . $e->getMessage());
        }
    }

    /**
     * 查询订单状态
     */
    public function status()
    {
        $orderNo = $this->request->param('order_no');
        $debug = $this->request->param('debug/b', false);

        if (empty($orderNo)) {
            $this->error('订单号不能为空');
        }

        if (function_exists('trace')) {
            trace('开始查询58秒返订单状态: ' . $orderNo, 'info');
        }
        
        try {
            // 查询本地订单
            $order = Db::name($this->orderTable)
                ->where('order_no', $orderNo)
                ->where('api_name', '58秒返')
                ->find();
            
            if (empty($order)) {
                if (function_exists('trace')) {
                    trace('本地订单不存在: ' . $orderNo, 'warning');
                }
                $this->error('订单不存在');
            }
            
            // 获取API配置
            $config = $this->getConfig();
            
            if (empty($config['api_key']) || empty($config['api_url'])) {
                if (function_exists('trace')) {
                    trace('58秒返API配置不完整', 'error');
                }
                $this->error('API配置不完整');
            }
            
            // 初始化API
            $api = new MF58Api($config);
            
            // 准备参数 - 使用上游订单号或本地订单号
            $apiParams = [
                'orderNo' => !empty($order['up_order_no']) ? $order['up_order_no'] : $orderNo
            ];
            
            if (function_exists('trace')) {
                trace('58秒返查询订单参数: ' . json_encode($apiParams, JSON_UNESCAPED_UNICODE), 'info');
            }
            
            // 发起API请求
            $result = $api->queryOrderStatus($apiParams);
            
            // 记录API响应
            if (function_exists('trace')) {
                trace('[MF58] API响应: ' . json_encode($result, JSON_UNESCAPED_UNICODE), 'info');
            }
            
            if ($debug) {
                // 调试模式，返回原始结果
                return json([
                    'code' => 1,
                    'msg' => '查询成功',
                    'time' => time(),
                    'data' => $result
                ]);
            }
            
            // 解析状态数据 - 处理多层嵌套的JSON结构
            $statusData = null;
            
            // 首先检查最外层 - 58秒返API成功时code为0
            if (isset($result['code']) && $result['code'] != 0) {
                if (function_exists('trace')) {
                    trace('58秒返API返回错误: ' . ($result['msg'] ?? '未知错误'), 'warning');
                }
                $this->error($result['msg'] ?? '查询失败');
            }
            
            // 处理嵌套的数据结构
            if (isset($result['data']['data'])) {
                $statusData = $result['data']['data'];
            } elseif (isset($result['data']) && is_array($result['data']) && !isset($result['data']['code'])) {
                $statusData = $result['data'];
            }
            
            // 如果没有数据，可能是订单号不存在
            if (empty($statusData) || (is_array($statusData) && count($statusData) === 0)) {
                if (function_exists('trace')) {
                    trace('58秒返API未返回订单数据，可能订单不存在', 'warning');
                }
                $this->error('未找到相关订单数据');
            }
            
            // 更新订单状态
            $this->updateOrderStatus($order, $statusData);
            
            // 查询最新订单状态
            $updatedOrder = Db::name($this->orderTable)->where('id', $order['id'])->find();
            
            // 返回成功结果
            $responseData = [
                'order_no' => $updatedOrder['order_no'],
                'up_order_no' => $updatedOrder['up_order_no'],
                'status' => $updatedOrder['order_status'],
                'express_company' => $updatedOrder['express_company'] ?? '',
                'tracking_number' => $updatedOrder['tracking_number'] ?? '',
                'raw_data' => $statusData
            ];
            
            return json([
                'code' => 1,
                'msg' => '查询成功',
                'time' => time(),
                'data' => $responseData
            ]);
            
        } catch (\Exception $e) {
            if (function_exists('trace')) {
                trace('58秒返查询订单异常: ' . $e->getMessage(), 'error');
                trace($e->getTraceAsString(), 'error');
            }
            
            if ($debug) {
                return json([
                    'code' => 0,
                    'msg' => '查询异常: ' . $e->getMessage(),
                    'time' => time(),
                    'data' => [
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ]
                ]);
            }
            
            return json([
                'code' => 0,
                'msg' => '查询异常: ' . $e->getMessage(),
                'time' => time(),
                'data' => null
            ]);
        }
    }
    
    /**
     * 获取当前配置
     * @return array
     */
    protected function getConfig()
    {
        $config = Db::name($this->configTable)->where('api_type', $this->apiType)->find();
        
        if ($config) {
            // 转换为需要的格式
            return [
                'api_key' => $config['api_key'] ?? '',
                'api_url' => $config['api_url'] ?? 'http://sdhk.xlxiot.cn/api',
                'status' => $config['status'] ?? 0
            ];
        }
        
        return [];
    }
    
    /**
     * 验证身份证号
     * @param string $id 身份证号
     * @return bool
     */
    protected function validateIdNumber($id)
    {
        $id = strtoupper($id);
        
        // 基础格式检查：18位或15位
        if (!preg_match('/^\d{15}$/', $id) && !preg_match('/^\d{17}[\dX]$/', $id)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * 更新订单状态
     * @param array $order 订单信息
     * @param array $statusData 状态信息
     */
    protected function updateOrderStatus($order, $statusData)
    {
        // 强制记录方法调用
        if (function_exists('trace')) {
            trace('58秒返updateOrderStatus方法被调用: 订单ID=' . $order['id'] . ', 订单号=' . $order['order_no'], 'info');
        }

        $status = null;
        $orderStatus = '';
        $expressCompany = '';
        $trackingNumber = '';
        $remarks = '';
        $simNumber = '';
        $isRecharged = null;
        $rechargedMoney = null;

        // 记录原始数据结构
        if (function_exists('trace')) {
            trace('处理订单状态数据: ' . json_encode($statusData, JSON_UNESCAPED_UNICODE), 'info');
        }
        
        // 正确设置订单表名
        $this->orderTable = 'order';
        
        // 尝试从不同格式的数据中提取状态和物流信息
        if (!empty($statusData) && is_array($statusData)) {
            // 如果是数组且第一个元素包含数据
            if (isset($statusData[0]) && is_array($statusData[0])) {
                $firstItem = $statusData[0];
                
                // 提取状态
                if (isset($firstItem['Status'])) {
                    $status = $firstItem['Status'];
                }
                
                // 提取物流信息
                if (isset($firstItem['ExpressCompany'])) {
                    $expressCompany = $firstItem['ExpressCompany'];
                }
                
                if (isset($firstItem['Express'])) {
                    $trackingNumber = $firstItem['Express'];
                }
                
                // 提取备注信息
                if (isset($firstItem['Demo']) && !empty($firstItem['Demo'])) {
                    $remarks = $firstItem['Demo'];
                }
                
                // 提取号码
                if (isset($firstItem['Sim']) && !empty($firstItem['Sim'])) {
                    $simNumber = $firstItem['Sim'];
                }
                
                // 提取充值信息
                if (isset($firstItem['IsRecharged'])) {
                    $isRecharged = $firstItem['IsRecharged'];
                }
                
                if (isset($firstItem['RechargedMoney'])) {
                    $rechargedMoney = $firstItem['RechargedMoney'];
                }
                
            } else if (isset($statusData['Status'])) {
                // 直接对象格式
                $status = $statusData['Status'];
                
                // 提取物流信息
                if (isset($statusData['ExpressCompany'])) {
                    $expressCompany = $statusData['ExpressCompany'];
                }
                
                if (isset($statusData['Express'])) {
                    $trackingNumber = $statusData['Express'];
                }
                
                // 提取备注信息
                if (isset($statusData['Demo']) && !empty($statusData['Demo'])) {
                    $remarks = $statusData['Demo'];
                }
                
                // 提取号码
                if (isset($statusData['Sim']) && !empty($statusData['Sim'])) {
                    $simNumber = $statusData['Sim'];
                }
                
                // 提取充值信息
                if (isset($statusData['IsRecharged'])) {
                    $isRecharged = $statusData['IsRecharged'];
                }
                
                if (isset($statusData['RechargedMoney'])) {
                    $rechargedMoney = $statusData['RechargedMoney'];
                }
                
            } else if (isset($statusData['status'])) {
                $status = $statusData['status'];
            }
        }
        
        // 如果没有提取到状态信息，直接返回
        if ($status === null) {
            if (function_exists('trace')) {
                trace('58秒返订单状态更新失败：未能提取到状态信息', 'warning');
            }
            return;
        }
        
        // 根据文档映射状态到数据库中的状态值
        // 58秒返状态: 1=待处理, 2=已提交, 3=运输中, 4=待激活, 5=已激活, 6=退单, 7=提交失败
        // 数据库状态: 0=已提交, 1=待发货, 2=已发货, 3=待传照片, 4=已激活, 5=已结算, 6=结算失败, 7=审核失败
        switch ($status) {
            case 1:
                $orderStatus = '0';  // 待处理 → 已提交
                break;
            case 2:
                $orderStatus = '1';  // 已提交 → 待发货
                break;
            case 3:
                $orderStatus = '2';  // 运输中 → 已发货
                break;
            case 4:
                $orderStatus = '2';  // 待激活 → 已发货
                break;
            case 5:
                $orderStatus = '4';  // 已激活 → 已激活
                break;
            case 6:
                $orderStatus = '7';  // 退单 → 审核失败
                break;
            case 7:
                $orderStatus = '7';  // 提交失败 → 审核失败
                break;
            default:
                $orderStatus = '';
                break;
        }
        
        // 准备更新数据
        $updateData = [];
        
        // 只有状态不同时才更新状态
        if ($orderStatus && $orderStatus != $order['order_status']) {
            $updateData['order_status'] = $orderStatus;
            $updateData['update_time'] = date('Y-m-d H:i:s');
            // 状态变为已激活时，设置激活时间（只在jh_time为空时设置）
            if ($orderStatus == '4' && empty($order['jh_time'])) {
                $updateData['jh_time'] = date('Y-m-d H:i:s');
            }

            if (function_exists('trace')) {
                trace('58秒返状态需要更新: 订单=' . $order['order_no'] . ', 原状态=' . $order['order_status'] . ', 新状态=' . $orderStatus, 'info');
            }
        } else {
            if (function_exists('trace')) {
                trace('58秒返状态无需更新: 订单=' . $order['order_no'] . ', 当前状态=' . $order['order_status'] . ', API状态=' . $orderStatus, 'info');
            }
        }
        
        // 如果有物流信息，一并更新
        if (!empty($expressCompany) && $expressCompany != ($order['express_company'] ?? '')) {
            $updateData['express_company'] = $expressCompany;
        }
        
        if (!empty($trackingNumber) && $trackingNumber != ($order['tracking_number'] ?? '')) {
            $updateData['tracking_number'] = $trackingNumber;
        }
        
        // 如果有备注信息，使用时间线格式存储（避免重复）
        if (!empty($remarks)) {
            $updateData['remark'] = \app\common\helper\OrderRemarkHelper::append(
                $order['remark'] ?? '',
                $remarks
            );
        }
        
        // 如果有号码信息且生产号码为空，更新号码
        if (!empty($simNumber) && empty($order['production_number'])) {
            $updateData['production_number'] = $simNumber;
        }
        
        // 充值状态更新 - 只有该字段存在时才更新
        if ($isRecharged !== null && isset($order['recharge_status'])) {
            $rechargeStatus = $isRecharged == 1 ? '1' : '0'; // 1-已充值, 0-待更新
            if ($rechargeStatus != $order['recharge_status']) {
                $updateData['recharge_status'] = $rechargeStatus;
            }
        }
        
        // 充值金额更新 - 只有该字段存在时才更新
        if ($rechargedMoney !== null && isset($order['recharge_amount']) && $rechargedMoney != $order['recharge_amount']) {
            $updateData['recharge_amount'] = $rechargedMoney;
        }
        
        // 如果状态变为已发货，更新发货时间
        if ($orderStatus == 'shipped' && empty($order['ship_time'])) {
            $updateData['ship_time'] = date('Y-m-d H:i:s');
                }
        
        // 只有有数据需要更新时才执行更新
        if (!empty($updateData)) {
            // 更新数据库
            
            $result = Db::name($this->orderTable)->where('id', $order['id'])->update($updateData);
            
            // 触发回调通知（如果订单状态发生变化）
            if ($result !== false && isset($updateData['order_status']) && $updateData['order_status'] != $order['order_status']) {
                try {
                    OrderCallbackService::triggerCallback($order['id'], $updateData['order_status'], '58秒返上游状态更新');
                    if (function_exists('trace')) {
                        trace('58秒返API已触发回调通知: ' . $order['order_no'] . ' 状态=' . $updateData['order_status'], 'info');
                    }
                } catch (\Exception $e) {
                    if (function_exists('trace')) {
                        trace('58秒返API回调通知失败: ' . $order['order_no'] . ' - ' . $e->getMessage(), 'error');
                    }
                }
            }
            
            // 检查是否需要触发佣金处理
            if ($result !== false && isset($updateData['order_status'])) {
                $oldStatus = $order['order_status'];
                $newStatus = $updateData['order_status'];
                $this->handleCommissionProcessing($order['id'], $order['order_no'], $oldStatus, $newStatus);
            }
            
            // 记录状态更新日志
            if (function_exists('trace')) {
                trace('58秒返订单状态更新结果: ' . $order['order_no'] . ', 状态: ' . ($updateData['order_status'] ?? $order['order_status']) . ', 更新结果: ' . ($result ? '成功' : '失败') . ', 表名: ' . $this->orderTable, 'info');
                
                if (isset($updateData['express_company']) || isset($updateData['tracking_number'])) {
                    trace('更新物流信息：' . 
                          (isset($updateData['express_company']) ? $updateData['express_company'] : ($order['express_company'] ?? '无')) . ' ' . 
                          (isset($updateData['tracking_number']) ? $updateData['tracking_number'] : ($order['tracking_number'] ?? '无')), 'info');
                }
                
                if (isset($updateData['production_number'])) {
                    trace('更新号码：' . $updateData['production_number'], 'info');
                }
                
                if (isset($updateData['recharge_status']) || isset($updateData['recharge_amount'])) {
                    trace('更新充值信息：状态=' . 
                          (isset($updateData['recharge_status']) ? $updateData['recharge_status'] : ($order['recharge_status'] ?? '无')) . 
                          ', 金额=' . 
                          (isset($updateData['recharge_amount']) ? $updateData['recharge_amount'] : ($order['recharge_amount'] ?? '0')), 'info');
                }
            }
        } else {
            if (function_exists('trace')) {
                trace('58秒返订单无需更新: ' . $order['order_no'] . ', 当前状态: ' . $order['order_status'] . ', 接收状态: ' . $orderStatus, 'info');
            }
        }
    }


    /**
     * 构建完整地址
     * 将省市区和详细地址拼接成完整地址
     */
    private function buildCompleteAddress($params)
    {
        $addressParts = [];

        // 添加省份
        if (!empty($params['province'])) {
            $addressParts[] = $params['province'];
        }

        // 添加城市
        if (!empty($params['city'])) {
            $addressParts[] = $params['city'];
        }

        // 添加区县
        if (!empty($params['district'])) {
            $addressParts[] = $params['district'];
        }

        // 添加详细地址
        $detailAddress = $params['detail_address'] ?? $params['address'] ?? '';
        if (!empty($detailAddress)) {
            $addressParts[] = $detailAddress;
        }

        // 如果没有任何地址信息，返回原始address字段
        if (empty($addressParts)) {
            return $params['address'] ?? '';
        }

        // 拼接完整地址
        return implode(' ', $addressParts);
    }

    /**
     * 处理佣金分配
     */
    protected function handleCommissionProcessing($orderId, $orderNo, $oldStatus, $newStatus)
    {
        try {
            // 已激活时：始终更新代理统计
            if ($newStatus == '4' && $oldStatus != '4') {
                $order = Db::name($this->orderTable)->where('id', $orderId)->find();
                if ($order && !empty($order['agent_id'])) {
                    \app\common\helper\AgentStatsHelper::incrementActivationStats($order['agent_id']);
                }
            }
            
            $needCommissionProcessing = false;
            $actionText = '';
            
            // 检查状态变化
            if ($newStatus == '4' && $oldStatus != '4') {
                // 订单状态变为"已激活"，记录待结算佣金
                $needCommissionProcessing = true;
                $actionText = '待结算佣金记录';
            } elseif ($newStatus == '5' && $oldStatus != '5') {
                // 订单状态变为"已结算"，实际分配佣金
                $needCommissionProcessing = true;
                $actionText = '佣金分配';
            }
            
            if ($needCommissionProcessing) {
                if (function_exists('trace')) {
                    trace('[58秒返回调] 触发' . $actionText . ': ' . $orderNo, 'info');
                }

                // 调用佣金处理服务
                $commissionService = new \app\common\service\OrderCommissionService();
                $commissionResult = $commissionService->processOrderCommission($orderId);

                if ($commissionResult['success']) {
                    if (function_exists('trace')) {
                        trace('[58秒返回调] ' . $actionText . '成功: ' . $orderNo, 'info');
                    }
                } else {
                    if (function_exists('trace')) {
                        trace('[58秒返回调] ' . $actionText . '失败: ' . $orderNo . ' - ' . $commissionResult['message'], 'error');
                    }
                }
            }
            
        } catch (\Exception $e) {
            if (function_exists('trace')) {
                trace('[58秒返回调] 佣金处理异常: ' . $orderNo . ' - ' . $e->getMessage(), 'error');
            }
        }
    }

    /**
     * 成功响应
     */
    protected function success($msg = '操作成功', $data = [], $code = 1)
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
    protected function error($msg = '操作失败', $data = [], $code = 0)
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
            'time' => time()
        ]);
    }
}
