<?php
namespace app\api\controller\kapi\haoky\common;

use think\facade\Log;

/**
 * 卡业联盟API工具类
 */
class HaokyApi
{
    /**
     * 接口基础URL
     * @var string
     */
    private $baseUrl;

    /**
     * 接口密钥
     * @var string
     */
    private $apiKey;

    /**
     * 接口状态
     * @var int
     */
    private $status;

    /**
     * 响应缓存，防止重复处理
     * @var array
     */
    private static $responseCache = [];
    
    /**
     * 构造函数
     * @param array $config 配置信息
     */
    public function __construct($config = [])
    {
        $this->baseUrl = rtrim($config['api_url'] ?? 'https://server.gantanhao.com/api/api', '/');
        $this->apiKey = $config['api_key'] ?? '';
        $this->status = intval($config['status'] ?? 0);
    }
    
    /**
     * 获取商品列表
     * @param array $params 查询参数
     * @return array
     */
    public function getProductList($params = [])
    {
        // 构建默认参数
        $defaultParams = [
            'api_key' => $this->apiKey,
            'page' => 1,
            'pageSize' => 20
        ];

        // 合并参数
        $queryParams = array_merge($defaultParams, $params);
        
        // 发送请求
        $result = $this->request('/selectProduct', $queryParams, 'POST');
        
        // 处理返回的结果，将其转换为统一格式
        if ($result['code'] == 200) {
            return [
                'code' => 1, // 转为内部成功码
                'msg' => '获取商品列表成功',
                'data' => $result['result'] ?? [],
                'raw_response' => $result
            ];
        }
        
        // 详细记录错误信息
        $errorMsg = $result['msg'] ?? '获取商品列表失败';
        if (function_exists('trace')) {
            trace('卡业联盟获取套餐列表失败: ' . $errorMsg, 'error');
        }
        
        return [
            'code' => 0,
            'msg' => '获取产品列表失败: ' . $errorMsg, // 添加具体错误信息
            'data' => null,
            'raw_response' => $result
        ];
    }
    
    /**
     * 查询可用号码
     * @param array $params 查询参数
     * @return array
     */
    public function queryNumbers($params = [])
    {
        // 验证必要参数
        if (empty($params['productNumber'])) {
            return [
                'code' => 0,
                'msg' => '产品编码不能为空',
                'data' => null
            ];
        }
        
        if (empty($params['address_province']) || empty($params['address_city'])) {
            return [
                'code' => 0,
                'msg' => '归属地省份和城市不能为空',
                'data' => null
            ];
        }
        
        // 自动添加省份后缀，确保格式符合API要求
        if (!empty($params['address_province']) && 
            !preg_match('/(省|市|自治区|特别行政区)$/', $params['address_province'])) {
            // 特殊处理北京、上海、天津、重庆直辖市
            if (in_array($params['address_province'], ['北京', '上海', '天津', '重庆'])) {
                $params['address_province'] .= '市';
            } 
            // 特殊处理自治区
            else if (strpos($params['address_province'], '内蒙古') === 0 || 
                     strpos($params['address_province'], '广西') === 0 || 
                     strpos($params['address_province'], '宁夏') === 0 || 
                     strpos($params['address_province'], '新疆') === 0 || 
                     strpos($params['address_province'], '西藏') === 0) {
                $params['address_province'] .= '自治区';
            }
            // 特殊处理特别行政区
            else if ($params['address_province'] === '香港' || $params['address_province'] === '澳门') {
                $params['address_province'] .= '特别行政区';
            }
            // 默认添加省
            else {
                $params['address_province'] .= '省';
            }
        }
        
        // 自动添加城市后缀，确保格式符合API要求
        if (!empty($params['address_city']) && 
            !preg_match('/(市|地区|自治州|盟)$/', $params['address_city'])) {
            // 处理少数民族自治州
            if (strpos($params['address_city'], '自治州') !== false) {
                // 已经包含"自治州"，不做处理
            }
            // 处理地区
            else if (strpos($params['address_city'], '地区') !== false) {
                // 已经包含"地区"，不做处理
            } 
            // 默认添加市
            else {
                $params['address_city'] .= '市';
            }
        }
        
        // 严格按照卡业联盟API文档构建请求参数
        $requestData = [
            'api_key' => $this->apiKey,
            'page' => intval($params['page'] ?? 1),
            'productNumber' => $params['productNumber'],
            'address_province' => $params['address_province'],
            'address_city' => $params['address_city']
        ];
        
        // 如果有搜索关键词，添加到请求参数
        if (!empty($params['search_name'])) {
            $requestData['search_name'] = $params['search_name'];
        }
        
        // 记录请求日志
        if (function_exists('trace')) {
            $logParams = $requestData;
            if (isset($logParams['api_key'])) {
                $logParams['api_key'] = substr($logParams['api_key'], 0, 3) . '***' . substr($logParams['api_key'], -3);
            }
            trace('[卡业联盟] 查询号码参数: ' . json_encode($logParams, JSON_UNESCAPED_UNICODE), 'info');
            trace('[卡业联盟] 发起请求: ' . $this->baseUrl . '/selectNumber, 方法: POST, 参数: ' . json_encode($logParams, JSON_UNESCAPED_UNICODE), 'info');
        }
        
        try {
            // 设置请求选项
            $ch = curl_init();
            // 构建完整URL
            $requestUrl = $this->baseUrl . '/selectNumber';
            curl_setopt($ch, CURLOPT_URL, $requestUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            
            // 准备请求数据
            $jsonData = json_encode($requestData, JSON_UNESCAPED_UNICODE);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $jsonError = json_last_error_msg();
                if (function_exists('trace')) {
                    trace('[卡业联盟] 请求数据JSON编码错误：' . $jsonError, 'error');
                }
                return [
                    'code' => 0,
                    'msg' => '请求数据编码错误：' . $jsonError,
                    'data' => [],
                    'raw_request' => $requestData
                ];
            }
            
            // 添加调试日志
            if (function_exists('trace')) {
                trace('[卡业联盟] 发送请求：' . $requestUrl, 'debug');
                trace('[卡业联盟] 发送请求头：Content-Type: application/json, Accept: application/json', 'debug');
                trace('[卡业联盟] 发送请求体：' . $jsonData, 'debug');
            }
            
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60); // 增加超时时间到60秒
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15); // 连接超时设置为15秒
            
            // 执行请求
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            
            // 记录详细的curl信息
            if (function_exists('trace')) {
                trace('[卡业联盟] HTTP状态码：' . $httpCode, 'debug');
                if ($error) {
                    trace('[卡业联盟] CURL错误：' . $error . ' (错误码：' . $errno . ')', 'error');
                }
                trace('[卡业联盟] 响应长度：' . strlen($response) . ' 字节', 'debug');
            }
            
            curl_close($ch);
            
            // 检查请求是否成功
            if ($error) {
                Log::record('[卡业联盟] 请求失败: ' . $error, 'error');
                return [
                    'code' => 0,
                    'msg' => '网络请求失败: ' . $error,
                    'data' => [],
                    'raw_response' => ['error' => $error, 'httpCode' => $httpCode]
                ];
            }
            
            // 解析响应
            $result = json_decode($response, true);
            if (!$result || !is_array($result)) {
                Log::record('[卡业联盟] 解析响应失败: ' . $response, 'error');
                return [
                    'code' => 0,
                    'msg' => '解析响应失败',
                    'data' => [],
                    'raw_response' => $response
                ];
            }
            
            // 记录响应
            if (function_exists('trace')) {
                trace('[卡业联盟] 响应内容: ' . substr($response, 0, 1000) . (strlen($response) > 1000 ? '...' : ''), 'info');
                trace('[卡业联盟] 完整响应内容: ' . $response, 'debug');
                trace('[卡业联盟] HTTP状态码: ' . $httpCode, 'debug');
            }
            
            // 检查API返回状态码 - 卡业联盟API返回200表示成功
            if ($result['code'] == 200) {
                // 记录更详细的日志
                if (function_exists('trace')) {
                    trace('[卡业联盟] API返回成功，代码: ' . $result['code'], 'debug');
                    trace('[卡业联盟] API返回消息: ' . ($result['msg'] ?? '无消息'), 'debug');
                }
                
                // 处理成功响应，提取号码
                $numbers = [];
                if (isset($result['result']) && is_array($result['result'])) {
                    trace('[卡业联盟] 原始号码数据: ' . json_encode($result['result'], JSON_UNESCAPED_UNICODE), 'debug');
                    
                    foreach ($result['result'] as $item) {
                        if (isset($item['mobile']) && !empty($item['mobile'])) {
                            $numberObj = [
                                'number' => $item['mobile'], // 使用number作为统一的键
                                'mobile' => $item['mobile'],
                                'label' => $item['mobile']   // 添加label方便前端显示
                            ];
                            
                            // 提取其他可能的信息
                            if (isset($item['remark'])) {
                                $numberObj['remark'] = $item['remark'];
                                $numberObj['label'] .= ' - ' . $item['remark']; // 号码备注添加到显示
                            }
                            
                            if (isset($item['check_code'])) {
                                $numberObj['check_code'] = $item['check_code'];
                            }
                            
                            $numbers[] = $numberObj;
                        }
                    }
                }
                
                if (function_exists('trace')) {
                    trace('[卡业联盟] 查询号码成功，获取到 ' . count($numbers) . ' 个号码', 'info');
                }
                
                return [
                    'code' => 1, // 内部成功状态码
                    'msg' => $result['msg'] ?? '获取号码成功',
                    'data' => $numbers,
                    'raw_response' => $result
                ];
            } else {
                // 处理失败响应
                $errorMessage = $result['msg'] ?? '未知错误';
                $errorCode = $result['code'] ?? 0;
                
                // 记录详细错误信息
                Log::record('[卡业联盟] API返回错误: ' . json_encode($result, JSON_UNESCAPED_UNICODE), 'error');
                if (function_exists('trace')) {
                    trace('[卡业联盟] API错误详情: ' . json_encode($result, JSON_UNESCAPED_UNICODE), 'error');
                }
                
                return [
                    'code' => 0,
                    'msg' => "获取号码失败: [{$errorCode}] {$errorMessage}",
                    'data' => [],
                    'raw_response' => $result,
                    'error_details' => $result
                ];
            }
        } catch (\Exception $e) {
            // 处理异常
            Log::record('[卡业联盟] 查询号码异常: ' . $e->getMessage(), 'error');
            return [
                'code' => 0,
                'msg' => '查询号码异常: ' . $e->getMessage(),
                'data' => [],
                'exception' => $e->getMessage(),
                'raw_response' => null
            ];
        }
    }
    
    /**
     * 提交订单
     * @param array $params 订单参数
     * @return array
     */
    public function submitOrder($params)
    {
        // 验证必要参数
        $requiredParams = [
            'productNumber', 'apiOrderId', 'receiverName', 'receiverPhone', 
            'receiverIdCard', 'receiverProvince', 'receiverCity', 
            'receiverDistrict', 'receiverAddress'
        ];
        
        $missingParams = [];
        foreach ($requiredParams as $param) {
            if (!isset($params[$param]) || (is_string($params[$param]) && trim($params[$param]) === '')) {
                $missingParams[] = $param;
            }
        }
        
        if (!empty($missingParams)) {
            $errorMsg = '订单提交缺少必要参数: ' . implode(', ', $missingParams);
            if (function_exists('trace')) {
                trace('[卡业联盟] ' . $errorMsg, 'warning');
            }
            return [
                'code' => 0,
                'msg' => $errorMsg,
                'data' => null,
                'raw_response' => null
            ];
        }
        
        // 构建订单数据
        $orderData = [
            'api_key' => $this->apiKey,
            'productNumber' => $params['productNumber'],
            'apiOrderId' => $params['apiOrderId'],
            'receiverName' => $params['receiverName'],
            'receiverPhone' => $params['receiverPhone'],
            'receiverIdCard' => $params['receiverIdCard'],
            'receiverProvince' => $params['receiverProvince'],
            'receiverCity' => $params['receiverCity'],
            'receiverDistrict' => $params['receiverDistrict'],
            'receiverAddress' => $params['receiverAddress']
        ];
        
        // 可选参数：选号
        if (!empty($params['selectNumber'])) {
            $orderData['selectNumber'] = $params['selectNumber'];
        }
        
        // 可选参数：身份证照片
        if (!empty($params['idCardFront'])) {
            $orderData['idCardFront'] = $params['idCardFront'];
        }
        
        if (!empty($params['idCardBack'])) {
            $orderData['idCardBack'] = $params['idCardBack'];
        }
        
        if (!empty($params['idCardHand'])) {
            $orderData['idCardHand'] = $params['idCardHand'];
        }

        // 可选参数：第四照片（一证通查截图）
        $fourPhotos = $params['fourPhotos'] ?? '';

        // 详细记录第四张照片处理情况
        if (function_exists('trace')) {
            trace('[卡业联盟] 第四张照片处理: ' . json_encode([
                'fourPhotos_exists' => isset($params['fourPhotos']),
                'final_fourPhotos_length' => strlen($fourPhotos),
                'will_add_to_order' => !empty($fourPhotos)
            ], JSON_UNESCAPED_UNICODE), 'info');
        }

        if (!empty($fourPhotos)) {
            $orderData['fourPhotos'] = $fourPhotos;
            if (function_exists('trace')) {
                trace('[卡业联盟] ✅ 第四张照片已添加到订单数据，长度: ' . strlen($fourPhotos), 'info');
            }
        } else {
            if (function_exists('trace')) {
                trace('[卡业联盟] 📝 第四张照片为空（如产品不需要则正常）', 'info');
            }
        }
        
        // 记录请求信息（敏感信息脱敏）
        if (function_exists('trace')) {
            $logData = $orderData;
            if (isset($logData['api_key'])) {
                $logData['api_key'] = substr($logData['api_key'], 0, 3) . '***' . substr($logData['api_key'], -3);
            }
            if (isset($logData['receiverIdCard'])) {
                $logData['receiverIdCard'] = substr($logData['receiverIdCard'], 0, 4) . '**********' . substr($logData['receiverIdCard'], -4);
            }
            trace('[卡业联盟] 提交订单参数: ' . json_encode($logData, JSON_UNESCAPED_UNICODE), 'info');
        }
        
        try {
            // 发送订单请求
            $result = $this->request('/submitOrder', $orderData, 'POST', true);
            
            // 处理返回结果
            if ($result['code'] == 200) {
                return [
                    'code' => 1,
                    'msg' => '提交订单成功',
                    'data' => $result['result'] ?? [],
                    'raw_response' => $result
                ];
            } else {
                return [
                    'code' => 0,
                    'msg' => $result['msg'] ?? '提交订单失败',
                    'data' => null,
                    'raw_response' => $result
                ];
            }
        } catch (\Exception $e) {
            $errorMsg = '订单提交异常: ' . $e->getMessage();
            if (function_exists('trace')) {
                trace('[卡业联盟] ' . $errorMsg, 'error');
            }
            return [
                'code' => 0,
                'msg' => $errorMsg,
                'data' => null,
                'exception' => [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ],
                'raw_response' => null
            ];
        }
    }
    
    /**
     * 查询订单状态
     * @param string $orderIds 订单ID，支持批量查询，用英文逗号分隔
     * @return array
     */
    public function queryOrderStatus($orderIds)
    {
        if (empty($orderIds)) {
            return [
                'code' => 0,
                'msg' => '订单ID不能为空',
                'data' => null,
                'raw_response' => null
            ];
        }
        
        $params = [
            'api_key' => $this->apiKey,
            'apiOrderId' => $orderIds
        ];
        
        // 记录查询请求
        if (function_exists('trace')) {
            trace('[卡业联盟] 查询订单状态: ' . $orderIds, 'info');
        }
        
        // 发送查询请求
        $result = $this->request('/selectOrder', $params, 'POST');
        
        // 处理返回结果
        if ($result['code'] == 200) {
            return [
                'code' => 1,
                'msg' => '查询成功',
                'data' => $result['result'] ?? [],
                'raw_response' => $result
            ];
        }
        
        return [
            'code' => 0,
            'msg' => $result['msg'] ?? '查询失败',
            'data' => null,
            'raw_response' => $result
        ];
    }
    
    /**
     * 发送API请求
     * @param string $endpoint 接口路径
     * @param array $params 请求参数
     * @param string $method 请求方法 (GET|POST)
     * @param bool $isJson 是否使用JSON格式发送请求
     * @return array
     */
    protected function request($endpoint, $params = [], $method = 'GET', $isJson = true)
    {
        // 构建完整的API URL
        $url = $this->baseUrl . $endpoint;

        // 生成请求缓存键
        $cacheKey = md5($url . serialize($params) . $method);

        // 检查是否已经有相同的请求在处理中
        if (isset(self::$responseCache[$cacheKey])) {
            if (function_exists('trace')) {
                trace('[卡业联盟] 发现重复请求，使用缓存响应: ' . $cacheKey, 'info');
            }
            return self::$responseCache[$cacheKey];
        }

        // 记录请求日志
        if (function_exists('trace')) {
            $logParams = $params;
            if (isset($logParams['api_key'])) {
                $logParams['api_key'] = substr($logParams['api_key'], 0, 3) . '***' . substr($logParams['api_key'], -3);
            }
            trace('[卡业联盟] 发起请求: ' . $url . ', 方法: ' . $method . ', 参数: ' . json_encode($logParams, JSON_UNESCAPED_UNICODE), 'info');
        }
        
        try {
            $ch = curl_init();
            
            // 设置请求选项
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60); // 增加超时时间到60秒
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // 允许重定向
            curl_setopt($ch, CURLOPT_ENCODING, ''); // 接受所有支持的编码
            
            // 根据请求方法设置不同选项
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                
                // 根据文档要求，使用JSON格式发送数据
                if ($isJson) {
                    $postData = json_encode($params, JSON_UNESCAPED_UNICODE);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/json',
                        'Content-Length: ' . strlen($postData)
                    ]);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                    
                    // 记录完整的JSON请求体
                    if (function_exists('trace')) {
                        trace('[卡业联盟] 请求体JSON: ' . $postData, 'debug');
                    }
                } else {
                    // 使用表单格式
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/x-www-form-urlencoded'
                    ]);
                }
            } else {
                // GET请求，将参数添加到URL
                if (!empty($params)) {
                    $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
                    curl_setopt($ch, CURLOPT_URL, $url);
                }
            }
            
            // 增加调试选项
            curl_setopt($ch, CURLINFO_HEADER_OUT, true); // 启用时会将请求头信息作为字符串返回
            
            // 执行请求并获取响应
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            $requestHeader = curl_getinfo($ch, CURLINFO_HEADER_OUT); // 获取发送的请求头
            
            // 记录HTTP请求头和响应头（调试用）
            if (function_exists('trace')) {
                trace('[卡业联盟] 请求头: ' . $requestHeader, 'debug');
                trace('[卡业联盟] 响应状态码: ' . $httpCode, 'debug');
            }
            
            curl_close($ch);
            
            // 记录响应
            if (function_exists('trace')) {
                trace('[卡业联盟] 响应内容: ' . substr($response, 0, 1000) . (strlen($response) > 1000 ? '...' : ''), 'info');
            }
            
            // 如果有错误，返回错误信息
            if ($error) {
                return [
                    'code' => 0,
                    'msg' => 'CURL错误: ' . $error,
                    'http_code' => $httpCode,
                    'data' => null,
                    'debug_info' => [
                        'request_url' => $url,
                        'request_method' => $method,
                        'request_header' => $requestHeader
                    ],
                    'raw_response' => $response
                ];
            }
            
            // 解析JSON响应
            $result = json_decode($response, true);
            
            // 检查JSON解析是否成功
            if ($result === null && json_last_error() !== JSON_ERROR_NONE) {
                // JSON解析失败，可能是返回了非JSON格式
                return [
                    'code' => 0,
                    'msg' => 'JSON解析错误: ' . json_last_error_msg() . ', 原始响应: ' . substr($response, 0, 100),
                    'http_code' => $httpCode,
                    'data' => null,
                    'raw_response' => $response,
                    'debug_info' => [
                        'request_url' => $url,
                        'request_method' => $method,
                        'request_header' => $requestHeader
                    ]
                ];
            }
            
            // 为了调试，保留原始的HTTP状态码和请求信息
            $result['http_code'] = $httpCode;
            $result['debug_url'] = $url;
            $result['debug_method'] = $method;

            // 缓存成功的响应（避免重复请求）
            if (isset($result['code']) && $result['code'] == 200) {
                self::$responseCache[$cacheKey] = $result;

                // 限制缓存大小，避免内存溢出
                if (count(self::$responseCache) > 50) {
                    // 移除最早的缓存项
                    $firstKey = array_key_first(self::$responseCache);
                    unset(self::$responseCache[$firstKey]);
                }
            }

            return $result;
        } catch (\Exception $e) {
            // 记录异常
            if (function_exists('trace')) {
                trace('[卡业联盟] 请求异常: ' . $e->getMessage(), 'error');
            }
            
            return [
                'code' => 0,
                'msg' => '请求异常: ' . $e->getMessage(),
                'data' => null,
                'exception' => [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ],
                'raw_response' => null
            ];
        }
    }

    /**
     * 查询产品接口 - 支持按商品编号精确查询
     * @param array $params 查询参数
     * @return array
     */
    public function selectProduct($params = [])
    {
        // 构建默认参数，根据接口文档要求
        $defaultParams = [
            'api_key' => $this->apiKey,
            'page' => 1,
            'pageSize' => 20,
            'isOnSale' => 1  // 只查询在售商品
        ];

        // 合并参数
        $queryParams = array_merge($defaultParams, $params);

        // 发送请求到查询产品接口（使用JSON格式）
        $result = $this->request('/selectProduct', $queryParams, 'POST', true);

        if (function_exists('trace')) {
            trace('[HaokyApi] 查询产品请求: ' . json_encode($queryParams, JSON_UNESCAPED_UNICODE), 'info');
            trace('[HaokyApi] 查询产品响应: ' . json_encode($result, JSON_UNESCAPED_UNICODE), 'info');
        }

        // 处理返回的结果
        if ($result && isset($result['code']) && $result['code'] == 200) {
            return [
                'code' => 1, // 转为内部成功码，与getProductList保持一致
                'msg' => '查询产品成功',
                'data' => $result['result'] ?? []
            ];
        } else {
            return [
                'code' => 0,
                'msg' => $result['message'] ?? $result['msg'] ?? '查询产品失败',
                'data' => null
            ];
        }
    }

    /**
     * 重传三照
     * @param array $data 照片数据
     * @return array
     */
    public function resendThreePhotos($data)
    {
        $url = 'https://server.gantanhao.com/api/api/resendThreePhotos';

        // 构建请求参数
        $params = [
            'orderNumber' => $data['orderNumber'],
            'picFaceUrl' => $data['picFaceUrl'],
            'picBackUrl' => $data['picBackUrl'],
            'picHandUrl' => $data['picHandUrl']
        ];

        // 如果有第四照
        if (!empty($data['fourPhotos'])) {
            $params['fourPhotos'] = $data['fourPhotos'];
        }

        try {
            $response = $this->makeRequest($url, $params, 'POST');

            if (function_exists('trace')) {
                trace('[HaokyApi] 重传三照请求: ' . json_encode($params, JSON_UNESCAPED_UNICODE), 'info');
                trace('[HaokyApi] 重传三照响应: ' . json_encode($response, JSON_UNESCAPED_UNICODE), 'info');
            }

            return $response;

        } catch (\Exception $e) {
            if (function_exists('trace')) {
                trace('[HaokyApi] 重传三照异常: ' . $e->getMessage(), 'error');
            }

            return [
                'code' => 0,
                'msg' => '请求异常: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
}