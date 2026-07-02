<?php
namespace app\api\controller\kapi\haoy\common;

use think\facade\Log;

/**
 * 号易API工具类
 */
class HaoyApi
{
    /**
     * 接口基础URL
     * @var string
     */
    private $baseUrl;
    
    /**
     * 商户ID
     * @var string
     */
    private $agentId;
    
    /**
     * API密钥
     * @var string
     */
    private $apiSecret;
    
    /**
     * 接口状态
     * @var int
     */
    private $status;
    
    /**
     * 构造函数
     * @param array $config 配置信息
     */
    public function __construct($config = [])
    {
        // 确保API URL正确设置，默认使用正确的号易API地址
        $this->baseUrl = rtrim($config['api_url'] ?? 'http://api.haoy.cn', '/');
        $this->agentId = $config['api_key'] ?? '';
        $this->apiSecret = $config['api_secret'] ?? '';
        $this->status = intval($config['status'] ?? 0);
        
        // 记录初始化信息
        try {
            $this->log('API初始化', [
                'baseUrl' => $this->baseUrl,
                'agentId' => $this->agentId ? substr($this->agentId, 0, 3) . '***' . substr($this->agentId, -3) : '',
                'apiSecret' => $this->apiSecret ? '******' : '',
                'status' => $this->status
            ]);
        } catch (\Throwable $e) {
            // 忽略日志错误
        }
    }
    
    /**
     * 获取商品列表
     * @param int $page 页码
     * @param bool $getAll 是否获取全部商品
     * @return array
     */
    public function getProductList($page = 1, $getAll = false)
    {
        $params = [
            'agent_id' => $this->agentId,
            'page' => $page,
            'time' => time()
        ];
        
        // 添加签名
        $params['sign'] = $this->generateSign($params);
        
        // 记录请求参数
        try {
            $this->log('getProductList 请求参数', [
                'page' => $page,
                'getAll' => $getAll,
                'params' => $this->maskSensitiveData($params)
            ]);
        } catch (\Throwable $e) {
            // 忽略日志错误
        }
        
        $result = $this->request('/api/distribution/goods/product_list', $params);
        
        // 处理返回结果，确保data字段的统一性
        if ($result['code'] == 1) {
            // 对data字段进行规范化处理
            if (isset($result['data'])) {
                // 记录原始data结构
                $this->log('API返回data结构', array_keys($result['data']));
                
                // 标准化返回结构
                if (!isset($result['data']['data']) && is_array($result['data']) && isset($result['data'][0])) {
                    // 如果data字段直接是商品列表数组，将其规范为标准格式
                    $this->log('规范化商品数据格式', [
                        'before' => 'data字段是直接的商品列表',
                        'action' => '将其转换为标准的data.data格式'
                    ]);
                    
                    $result['data'] = [
                        'data' => $result['data'],
                        'current_page' => $page,
                        'last_page' => ceil(count($result['data']) / 15), // 假设每页15条
                        'total' => count($result['data'])
                    ];
                }
                
                // 打印完整的data结构信息，用于调试
                $this->log('完整data结构', [
                    'has_data_field' => isset($result['data']['data']),
                    'current_page' => $result['data']['current_page'] ?? 'unknown',
                    'last_page' => $result['data']['last_page'] ?? 'unknown',
                    'total' => $result['data']['total'] ?? 'unknown',
                    'items_count' => isset($result['data']['data']) ? count($result['data']['data']) : 'unknown'
                ]);
                
                // 如果需要获取全部商品，则继续获取剩余页面数据
                if ($getAll && isset($result['data']['last_page']) && $result['data']['last_page'] > $page) {
                    // 手动获取总页数和总记录数（号易API返回的是99条记录，分7页）
                    $totalPages = intval($result['data']['last_page']);
                    if ($totalPages < 1) {
                        $totalPages = 7; // 强制设置为7页，针对号易API的特殊处理
                        $this->log('手动设置总页数', [
                            '原总页数' => $result['data']['last_page'],
                            '新总页数' => $totalPages
                        ]);
                    }
                    
                    // 记录数据总量信息
                    $this->log('开始获取所有号易商品数据', [
                        '当前页' => $page,
                        '总页数' => $totalPages,
                        '总记录数' => $result['data']['total'],
                        '当前获取数量' => count($result['data']['data'])
                    ]);
                    
                    // 提取第一页的商品数据
                    $allProducts = isset($result['data']['data']) ? $result['data']['data'] : [];
                    
                    // 从下一页开始获取所有剩余页面
                    for ($nextPage = $page + 1; $nextPage <= $totalPages; $nextPage++) {
                        $this->log('正在获取号易第' . $nextPage . '页商品数据', [
                            '总页数' => $totalPages
                        ]);
                        
                        // 创建新的签名参数
                        $nextParams = [
                            'agent_id' => $this->agentId,
                            'page' => $nextPage,
                            'time' => time()
                        ];
                        $nextParams['sign'] = $this->generateSign($nextParams);
                        
                        // 请求下一页数据
                        $nextResult = $this->request('/api/distribution/goods/product_list', $nextParams);
                        
                        if ($nextResult['code'] == 1 && isset($nextResult['data'])) {
                            $this->log('成功获取号易第' . $nextPage . '页数据', [
                                'data_keys' => is_array($nextResult['data']) ? array_keys($nextResult['data']) : '非数组'
                            ]);
                            
                            // 提取下一页的商品数据
                            $nextProducts = [];
                            if (isset($nextResult['data']['data']) && is_array($nextResult['data']['data'])) {
                                $nextProducts = $nextResult['data']['data'];
                                $this->log('从data.data提取第' . $nextPage . '页数据', [
                                    '数据量' => count($nextProducts)
                                ]);
                            } elseif (is_array($nextResult['data']) && isset($nextResult['data'][0])) {
                                $nextProducts = $nextResult['data'];
                                $this->log('从data直接提取第' . $nextPage . '页数据', [
                                    '数据量' => count($nextProducts)
                                ]);
                            } else {
                                $this->log('无法从第' . $nextPage . '页响应中提取数据', [
                                    'data_type' => gettype($nextResult['data']),
                                    'data_sample' => is_array($nextResult['data']) ? json_encode(array_slice($nextResult['data'], 0, 1)) : $nextResult['data']
                                ]);
                            }
                            
                            // 合并商品数据
                            if (!empty($nextProducts)) {
                                $oldCount = count($allProducts);
                                $allProducts = array_merge($allProducts, $nextProducts);
                                $newCount = count($allProducts);
                                
                                $this->log('已合并号易第' . $nextPage . '页商品数据', [
                                    'page_products_count' => count($nextProducts),
                                    'total_before_merge' => $oldCount,
                                    'total_after_merge' => $newCount,
                                    'merged_count' => ($newCount - $oldCount)
                                ]);
                            }
                        } else {
                            $this->log('获取号易第' . $nextPage . '页商品数据失败', [
                                'code' => $nextResult['code'],
                                'msg' => $nextResult['msg'] ?? '未知错误',
                                'data_type' => isset($nextResult['data']) ? gettype($nextResult['data']) : '无data字段'
                            ]);
                            break; // 出错时停止获取
                        }
                        
                        // 避免请求过于频繁
                        usleep(200000); // 休眠200毫秒
                    }
                    
                    // 更新结果中的商品数据
                    $result['data']['data'] = $allProducts;
                    $result['data']['total'] = count($allProducts);
                    
                    $this->log('获取所有号易商品数据完成', [
                        'total_pages' => $totalPages,
                        'total_products' => count($allProducts),
                        'expected_total' => $result['data']['total'],
                        'actual_collected' => count($allProducts)
                    ]);
                }
            }
        }
        
        return $result;
    }
    
    /**
     * 选择号码
     * @param array $params 查询参数
     * @return array
     */
    public function selectNumbers($params)
    {
        $requestParams = [
            'agent_id' => $this->agentId,
            'time' => time(),
            'goods_id' => $params['goods_id'] ?? '',
            'qcellCore_province' => $params['qcellCore_province'] ?? '',
            'qcellCore_provinceid' => $params['qcellCore_provinceid'] ?? '',
        ];
        
        // 添加扩展参数
        if (!empty($params['ext'])) {
            $requestParams['ext'] = $params['ext'];
        }

        // 添加随机种子参数（用于获取不同的号码）
        if (!empty($params['random_seed'])) {
            $requestParams['random_seed'] = $params['random_seed'];
        }
        
        // 添加签名
        $requestParams['sign'] = $this->generateSign($requestParams);
        
        return $this->request('/api/distribution/goods/get_selection_number_list', $requestParams);
    }
    
    /**
     * 提交订单
     * @param array $params 订单参数
     * @return array
     */
    public function submitOrder($params)
    {
        // 记录接收到的参数
        if (function_exists('trace')) {
            trace('[号易API] submitOrder接收到的参数: ' . json_encode($params, JSON_UNESCAPED_UNICODE), 'info');
        }
        
        // 检查必填字段
        $requiredFields = ['ext_order_sn', 'username', 'card', 'moblie', 'province', 'provinceid', 'detailedAddress', 'goods_id'];
        foreach ($requiredFields as $field) {
            if (empty($params[$field])) {
                return [
                    'code' => 0,
                    'msg' => "字段 {$field} 不能为空",
                    'data' => null
                ];
            }
        }
        
        // 添加公共参数
        $requestParams = array_merge($params, [
            'agent_id' => $this->agentId,
            'time' => time()
        ]);
        
        // 记录照片参数
        if (function_exists('trace')) {
            trace('[号易API] 照片参数检查: face=' . (isset($requestParams['face']) ? '有' : '无') .
                  ', id_back=' . (isset($requestParams['id_back']) ? '有' : '无') .
                  ', zhenren=' . (isset($requestParams['zhenren']) ? '有' : '无') .
                  ', hand=' . (isset($requestParams['hand']) ? '有' : '无'), 'info');
        }
        
        // 添加签名
        $requestParams['sign'] = $this->generateSign($requestParams);
        
        // 记录最终请求参数（隐藏敏感信息）
        if (function_exists('trace')) {
            $logParams = $requestParams;
            if (isset($logParams['card'])) {
                $logParams['card'] = substr($logParams['card'], 0, 6) . '****' . substr($logParams['card'], -4);
            }
            trace('[号易API] 最终请求参数: ' . json_encode($logParams, JSON_UNESCAPED_UNICODE), 'info');
        }
        
        return $this->request('/api/distribution/order/submit_order', $requestParams);
    }

    /**
     * 获取商品详情基础配置
     * @param string $goodsId 商品编码
     * @return array 响应结果
     */
    public function getProductDetailsConfig($goodsId)
    {
        // 构建请求参数
        $requestParams = [
            'time' => time(),
            'agent_id' => $this->agentId,
            'goods_id' => $goodsId
        ];

        // 添加签名
        $requestParams['sign'] = $this->generateSign($requestParams);

        // 记录请求参数
        try {
            $this->log('getProductDetailsConfig 请求参数', [
                'goods_id' => $goodsId,
                'agent_id' => $this->agentId,
                'time' => $requestParams['time'],
                'sign' => substr($requestParams['sign'], 0, 8) . '...'
            ]);
        } catch (\Throwable $e) {
            // 日志记录失败不影响主要功能
        }

        return $this->request('/api/distribution/goods/product_details_config', $requestParams);
    }

    /**
     * 生成签名
     * @param array $params 参数数组
     * @return string 签名结果
     */
    protected function generateSign($params)
    {
        // 去除签名参数和空参数
        $params = array_filter($params, function($value, $key) {
            return $key !== 'sign' && $value !== '';
        }, ARRAY_FILTER_USE_BOTH);
        
        // 按键名升序排序
        ksort($params);
        
        // 拼接字符串
        $str = '';
        foreach ($params as $k => $v) {
            $str .= $k . '=' . $v;
        }
        
        // 拼接API密钥并计算MD5
        $sign = strtolower(md5($str . $this->apiSecret));
        
        return $sign;
    }
    
    /**
     * 发送请求
     * @param string $endpoint API端点
     * @param array $params 请求参数
     * @param string $method 请求方法 (GET或POST)
     * @return array 响应结果
     */
    protected function request($endpoint, $params = [], $method = 'POST')
    {
        // 构建完整URL
        $url = $this->baseUrl . $endpoint;
        
        $this->log('API请求', [
            'url' => $url,
            'method' => $method,
            'params' => $this->maskSensitiveData($params)
        ]);
        
        try {
            // 添加请求开始计时
            $startTime = microtime(true);
            
            // 发送HTTP请求
            $ch = curl_init();
            
            // 设置请求选项
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_VERBOSE, true); // 启用详细信息
            
            // 创建临时文件存储详细日志
            $verbose = fopen('php://temp', 'w+');
            curl_setopt($ch, CURLOPT_STDERR, $verbose);
            
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            }
            
            // 执行请求
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            $curlInfo = curl_getinfo($ch);
            curl_close($ch);
            
            // 计算请求时间
            $endTime = microtime(true);
            $requestTime = round(($endTime - $startTime) * 1000, 2); // 毫秒
            
            // 获取详细日志
            rewind($verbose);
            $verboseLog = stream_get_contents($verbose);
            fclose($verbose);
            
            if ($error) {
                $this->log('请求错误', [
                    'error' => $error,
                    'http_code' => $httpCode,
                    'request_time' => $requestTime . 'ms',
                    'curl_info' => $curlInfo,
                    'verbose' => $verboseLog
                ]);
                
                return [
                    'code' => 0,
                    'msg' => '请求失败: ' . $error,
                    'data' => null
                ];
            }
            
            // 记录原始响应
            $this->log('API原始响应', [
                'response' => $response,
                'http_code' => $httpCode,
                'request_time' => $requestTime . 'ms'
            ]);
            
            // 解析响应
            $result = json_decode($response, true);
            
            if (!$result) {
                $this->log('响应解析失败', [
                    'response' => $response,
                    'http_code' => $httpCode,
                    'request_time' => $requestTime . 'ms',
                    'json_error' => json_last_error_msg()
                ]);
                
                return [
                    'code' => 0,
                    'msg' => '响应解析失败: ' . json_last_error_msg(),
                    'data' => null
                ];
            }
            
            // 记录响应结果
            $this->log('API响应处理后', [
                'result' => $result,
                'http_code' => $httpCode,
                'request_time' => $requestTime . 'ms'
            ]);
            
            // 返回统一格式
            if (isset($result['code']) && $result['code'] === 1) {
                // 商品列表接口的分页字段位于顶层，不能只返回 data，
                // 否则 last_page/current_page/total 会丢失，导致业务层只能翻到固定前几页。
                $responseData = $result['data'] ?? $result;
                if ($endpoint === '/api/distribution/goods/product_list') {
                    $responseData = $result;
                }

                return [
                    'code' => 1,
                    'msg' => '请求成功',
                    'data' => $responseData
                ];
            } else {
                return [
                    'code' => 0,
                    'msg' => $result['msg'] ?? '请求失败',
                    'data' => null
                ];
            }
            
        } catch (\Throwable $e) {
            $this->log('请求异常', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'code' => 0,
                'msg' => '请求异常: ' . $e->getMessage() . ' [' . $e->getFile() . ':' . $e->getLine() . ']',
                'data' => null
            ];
        }
    }
    
    /**
     * 脱敏敏感数据
     * @param array $data 数据
     * @return array
     */
    protected function maskSensitiveData($data)
    {
        $result = $data;
        
        // 脱敏身份证
        if (isset($result['card']) && strlen($result['card']) > 10) {
            $result['card'] = substr($result['card'], 0, 6) . '******' . substr($result['card'], -4);
        }
        
        // 脱敏签名
        if (isset($result['sign'])) {
            $result['sign'] = substr($result['sign'], 0, 8) . '********' . substr($result['sign'], -8);
        }
        
        // 脱敏API密钥
        if (isset($result['api_secret'])) {
            $result['api_secret'] = '********';
        }
        
        return $result;
    }
    
    /**
     * 记录日志
     * @param string $message 日志消息
     * @param array $data 日志数据
     */
    protected function log($message, $data = [])
    {
        if (!$this->shouldLog($message)) {
            return;
        }

        try {
            if (function_exists('trace')) {
                trace('[号易] ' . $message, 'info', $data);
            } else {
                \think\facade\Log::info('[号易] ' . $message . ': ' . json_encode($data, JSON_UNESCAPED_UNICODE));
            }
        } catch (\Throwable $e) {
            // 日志记录失败，但不影响主要功能
            \think\facade\Log::error('[号易] 日志记录失败: ' . $e->getMessage());
        }
    }

    /**
     * 仅保留异常/失败类日志，关闭高频调试日志
     *
     * @param string $message
     * @return bool
     */
    protected function shouldLog($message)
    {
        $message = (string)$message;
        foreach (['错误', '失败', '异常', 'warning', 'error', 'failed', 'exception'] as $keyword) {
            if (stripos($message, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }
}
