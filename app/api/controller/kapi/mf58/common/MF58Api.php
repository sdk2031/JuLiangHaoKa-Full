<?php
namespace app\api\controller\kapi\mf58\common;

use think\facade\Log;

/**
 * 58秒返API工具类
 */
class MF58Api
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
     * 构造函数
     * @param array $config 配置信息
     */
    public function __construct($config = [])
    {
        
        $this->baseUrl = rtrim($config['api_url'] ?? 'http://sdhk.xlxiot.cn/api', '/');
        $this->apiKey = $config['api_key'] ?? '';
        $this->status = intval($config['status'] ?? 0);
    }
    
    /**
     * 获取首页数据
     * @return array
     */
    public function getHomeData()
    {
        // 使用apiKey参数调用接口
        return $this->request('/newSim/queryGood', [
            'Key' => $this->apiKey
        ], 'POST', true);
    }
    
    /**
     * 获取套餐列表
     * @return array
     */
    public function getPackageList()
    {
        // 根据接口文档使用正确的API接口URL
        $result = $this->request('/apiList/queryPackage', [], 'POST', true);
        
        // 特殊处理58秒返API返回格式，将实际数据从嵌套结构中提取出来
        if ($result['code'] == 1 && isset($result['data']['code']) && $result['data']['code'] == 0) {
            return [
                'code' => 0, // 将code转为0表示成功
                'msg' => $result['data']['msg'] ?? '查询成功',
                'data' => $result['data']['data'] ?? [],
                'raw_response' => $result['raw_response'] ?? null
            ];
        }
        
        return $result;
    }
    
    /**
     * 获取可用省份
     * @param int $packageId 套餐ID
     * @return array
     */
    public function getProvinces($packageNumber)
    {
        return $this->request('/newSim/GetListProvince', [
            't' => 0,
            'type' => $packageNumber,
            'code' => '0'
        ], 'GET');
    }
    
    /**
     * 获取可用城市
     * @param int $packageId 套餐ID
     * @param string $province 省份名称
     * @param string $provinceCode 省份编码
     * @return array
     */
    public function getCities($packageNumber, $province, $provinceCode)
    {
        return $this->request('/newSim/GetListProvince', [
            't' => 1,
            'type' => $packageNumber,
            'code' => $provinceCode
        ], 'GET');
    }

    /**
     * 获取区县
     * @param string $packageNumber 套餐编号
     * @param string $province 省份名称
     * @param string $city 城市名称
     * @param string $cityCode 城市编码
     * @return array
     */
    public function getDistricts($packageNumber, $province, $city, $cityCode)
    {
        return $this->request('/newSim/GetListProvince', [
            't' => 2,
            'type' => $packageNumber,
            'code' => $cityCode
        ], 'GET');
    }
    
    /**
     * 号码选择
     * @param int $packageId 套餐ID
     * @param string $key 选号关键词(可选)
     * @return array
     */
    public function getNumbers($packageId, $key = '')
    {
        if (function_exists('trace')) {
            trace('[MF58] 调用getNumbers方法, packageId: ' . $packageId . ', key: ' . $key . ', baseUrl: ' . $this->baseUrl . ', apiKey长度: ' . strlen($this->apiKey), 'info');
        }
        
        // 构建参数
        $params = [
            't' => $packageId,
            'Key' => $this->apiKey, // 使用大写K的Key参数名
            'server' => 1 // 添加server参数获取详细错误信息
        ];
        
        if (!empty($key)) {
            $params['key'] = $key; // 选号关键词使用小写k的key参数名
        }
        
        if (function_exists('trace')) {
            $logParams = $params;
            if (isset($logParams['Key'])) {
                $logParams['Key'] = substr($logParams['Key'], 0, 3) . '***' . substr($logParams['Key'], -3);
            }
            trace('[MF58] getNumbers参数: ' . json_encode($logParams, JSON_UNESCAPED_UNICODE), 'info');
        }
        
        $result = $this->request('/newSim/queryNum', $params, 'POST', true);
        
        // 处理响应，确保前端能正确解析结果
        if ($result['code'] == 1 && isset($result['data'])) {
            // 确保data字段包含list数组
            if (!isset($result['data']['list']) && isset($result['data']['Msg']) && is_array($result['data']['Msg'])) {
                $result['data']['list'] = $result['data']['Msg'];
            }
        }
        
        return $result;
    }
    
    /**
     * 提交订单
     * @param array $params 订单参数
     * @return array
     */
    public function submitOrder($params)
    {
        // 记录关键参数是否存在
        $missingParams = [];
        // 基本必需参数（编码参数改为可选，因为58秒返API可能可以处理没有编码的情况）
        $requiredParams = ['type', 'Provinces', 'City', 'County', 'Address', 'Name', 'Phone', 'Idnum', 'Key'];
        // 可选但建议的参数
        $optionalParams = ['ProvincesCode', 'CityCode', 'CountyCode'];

        foreach ($requiredParams as $param) {
            if (!isset($params[$param]) || (is_string($params[$param]) && trim($params[$param]) === '')) {
                $missingParams[] = $param;
            }
        }

        // 检查可选参数并记录警告
        $missingOptionalParams = [];
        foreach ($optionalParams as $param) {
            if (!isset($params[$param]) || (is_string($params[$param]) && trim($params[$param]) === '')) {
                $missingOptionalParams[] = $param;
            }
        }

        if (!empty($missingOptionalParams)) {
            if (function_exists('trace')) {
                trace('[MF58] 订单提交缺少可选参数: ' . implode(', ', $missingOptionalParams), 'warning');
            }
        }

        if (!empty($missingParams)) {
            $errorMsg = '订单提交缺少必要参数: ' . implode(', ', $missingParams);
            if (function_exists('trace')) {
                trace('[MF58] ' . $errorMsg, 'error');
            }
            return [
                'code' => 0,
                'msg' => $errorMsg,
                'data' => null,
                'raw_response' => null
            ];
        }
        
        // 确保server=1参数被添加，用于获取详细错误信息
        if (!isset($params['server'])) {
            $params['server'] = 1;
        }
        
        // 记录完整请求
        if (function_exists('trace')) {
            $logParams = $params;
            if (isset($logParams['Key'])) {
                $logParams['Key'] = substr($logParams['Key'], 0, 3) . '***' . substr($logParams['Key'], -3);
            }
            if (isset($logParams['Idnum'])) {
                $logParams['Idnum'] = substr($logParams['Idnum'], 0, 4) . '**********' . substr($logParams['Idnum'], -4);
            }
            trace('[MF58] 提交订单参数: ' . json_encode($logParams, JSON_UNESCAPED_UNICODE), 'info');
            trace('[MF58] 提交订单URL: ' . $this->baseUrl . '/apiList/addOrder', 'info');
        }
        
        try {
            $result = $this->request('/apiList/addOrder', $params, 'POST', true);
            
            // 处理特殊错误信息 - "请勿重复下单"
            if ($result['code'] == 0 && $result['msg'] == '请勿重复下单') {
                // 这是一个常见错误，可以给前端一个更友好的提示
                $result['msg'] = '该订单已提交过，请勿重复下单';
                
                // 记录重复下单情况
                if (function_exists('trace')) {
                    trace('[MF58] 检测到重复下单: ' . json_encode([
                        'phone' => $params['Phone'] ?? '',
                        'id_number' => isset($params['Idnum']) ? (substr($params['Idnum'], 0, 4) . '**********' . substr($params['Idnum'], -4)) : '',
                        'number' => $params['Sim'] ?? ''
                    ], JSON_UNESCAPED_UNICODE), 'warning');
                }
            }
            
            return $result;
        } catch (\Exception $e) {
            $errorMsg = '订单提交异常: ' . $e->getMessage();
            if (function_exists('trace')) {
                trace('[MF58] ' . $errorMsg, 'error');
                trace('[MF58] 异常堆栈: ' . $e->getTraceAsString(), 'error');
            }
            return [
                'code' => 0,
                'msg' => $errorMsg,
                'data' => null,
                'raw_response' => null,
                'exception' => [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ];
        }
    }
    
    /**
     * 查询订单状态
     * @param array $params 查询参数
     * @return array
     */
    public function queryOrderStatus($params)
    {
        // 构建API所需的参数格式
        $apiParams = [
            'OrderSn' => $params['orderNo'] ?? '',
            'Key' => $this->apiKey
        ];
        
        $result = $this->request('/apiList/queryOrder', $apiParams, 'POST');
        
        // 确保返回格式一致
        if (isset($result['code']) && $result['code'] == 0 && isset($result['data']) && is_array($result['data'])) {
            // 这是标准的直接返回格式，直接返回
            return [
                'code' => 1,
                'msg' => '请求成功',
                'data' => $result
            ];
        } else if (isset($result['data']) && isset($result['data']['code']) && isset($result['data']['data'])) {
            // 已经是嵌套格式，直接返回
            return $result;
        }
        
        // 其他情况，返回标准错误格式
        return [
            'code' => 0,
            'msg' => isset($result['msg']) ? $result['msg'] : '未知错误',
            'data' => $result
        ];
    }
    
    /**
     * 发送API请求
     * @param string $endpoint 接口路径
     * @param array $params 请求参数
     * @param string $method 请求方法
     * @param bool $isJson 是否使用JSON格式
     * @return array
     */
    protected function request($endpoint, $params = [], $method = 'GET', $isJson = true)
    {
        $url = rtrim($this->baseUrl, '/') . $endpoint;

        // 对于GET请求，将参数添加到URL查询字符串中
        if ($method == 'GET' && !empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        // 调试记录
        if (function_exists('trace')) {
            $logParams = $params;
            if (isset($logParams['Key'])) {
                $logParams['Key'] = substr($logParams['Key'], 0, 3) . '***' . substr($logParams['Key'], -3);
            }
            if (isset($logParams['Idnum'])) {
                $logParams['Idnum'] = substr($logParams['Idnum'], 0, 4) . '********' . substr($logParams['Idnum'], -4);
            }

            trace("[MF58] API请求: {$url}, 参数: " . json_encode($logParams, JSON_UNESCAPED_UNICODE), 'info');
        }

        try {
            $ch = curl_init();
            if ($ch === false) {
                throw new \Exception('初始化CURL失败');
            }

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            if ($method == 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                
                if ($isJson) {
                    // 使用JSON格式提交
                    $jsonData = json_encode($params, JSON_UNESCAPED_UNICODE);
                    if ($jsonData === false) {
                        throw new \Exception('JSON编码失败: ' . json_last_error_msg());
                    }
                    
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/json',
                        'Content-Length: ' . strlen($jsonData)
                    ]);
                    
                } else {
                    // 使用表单格式提交
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
                }
            }
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            // 记录响应
            if (function_exists('trace')) {
                trace("[MF58] API响应: HTTP {$httpCode}, 响应: " . substr($response, 0, 1000) . (strlen($response) > 1000 ? '...(截断)' : ''), 'info');
            }
        
            if ($response === false) {
                $error = curl_error($ch);
                $errno = curl_errno($ch);
                curl_close($ch);
                
                if (function_exists('trace')) {
                    trace("[MF58] CURL错误: 错误码 {$errno}, 错误信息 {$error}", 'error');
                }
                    
                return [
                    'code' => 0,
                    'msg' => "网络请求失败: [{$errno}] {$error}",
                    'data' => null,
                    'raw_response' => null,
                    'http_code' => $httpCode
                ];
            }
            
            curl_close($ch);
            
            // 解析JSON响应
            $result = json_decode($response, true);
        
            if (json_last_error() !== JSON_ERROR_NONE) {
                if (function_exists('trace')) {
                    trace("[MF58] JSON解析错误: " . json_last_error_msg(), 'error');
                }
                
                return [
                    'code' => 0,
                    'msg' => '无法解析响应: ' . json_last_error_msg(),
                    'data' => null,
                    'raw_response' => $response,
                    'http_code' => $httpCode
                ];
            }
            
            if (is_array($result)) {
                // 处理58秒返API的标准响应
                if (isset($result['Code'])) {
                    // 修正：58秒返API中Code=0表示成功，Code=1表示失败
                    return [
                        'code' => ($result['Code'] === 0) ? 1 : 0, // 将API的成功码0转为我们的成功码1，将API的错误码1转为0
                        'msg' => is_string($result['Msg']) ? $result['Msg'] : json_encode($result['Msg'], JSON_UNESCAPED_UNICODE),
                        'data' => $result,
                        'raw_response' => $response,
                        'http_code' => $httpCode
                    ];
                }
                
                // 其他响应格式 - 直接返回58秒返API的响应结构
                return $result;
            }
            
            // 非JSON响应或解析失败
            return [
                'code' => 0,
                'msg' => '无法解析响应',
                'data' => null,
                'raw_response' => $response,
                'http_code' => $httpCode
            ];
            
        } catch (\Exception $e) {
            if (function_exists('trace')) {
                trace("[MF58] 请求异常: " . $e->getMessage() . "\n" . $e->getTraceAsString(), 'error');
            }
            
            return [
                'code' => 0,
                'msg' => '请求异常: ' . $e->getMessage(),
                'data' => null,
                'raw_response' => null,
                'exception' => [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ];
        }
    }

    /**
     * 查询订单详细信息
     * 根据58秒返API文档：http://sdhk.xlxiot.cn/api/apiList/queryOrder
     * @param string $orderSn 订单号
     * @return array
     */
    public function queryOrderDetail($orderSn)
    {
        // 构建API所需的参数格式
        $apiParams = [
            'OrderSn' => $orderSn,
            'Key' => $this->apiKey
        ];

        $result = $this->request('/apiList/queryOrder', $apiParams, 'POST');

        return $result;
    }
}
