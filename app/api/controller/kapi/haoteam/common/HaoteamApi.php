<?php
namespace app\api\controller\kapi\haoteam\common;

use think\facade\Log;

/**
 * 号卡极团API工具类🆕
 */
class HaoteamApi
{
    /**
     * 接口基础URL
     * @var string
     */
    private $baseUrl;
    
    /**
     * 接口账号
     * @var string
     */
    private $apiUser;
    
    /**
     * 接口密钥
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
        
        $this->baseUrl = rtrim($config['api_url'] ?? 'https://api.haoteam.com', '/');
        $this->apiUser = $config['api_key'] ?? '';
        $this->apiSecret = $config['api_secret'] ?? '';
        $this->status = intval($config['status'] ?? 0);
    }
    
    /**
     * 获取套餐列表
     * @return array
     */
    public function getPackageList()
    {
        return $this->request('order/api/haoteam/getlist');
    }
    
    /**
     * 获取号码
     * @param string $packageCode 套餐编号
     * @param string $province 省份（可选）
     * @param string $city 城市（可选）
     * @return array
     */
    public function getNumbers($packageCode, $province = '', $city = '')
    {
        return $this->request('order/api/haoteam/gethao', [
            'pid' => $packageCode,
            'province' => $province,
            'city' => $city
        ]);
    }
    
    /**
     * 提交订单
     * @param array $params 订单参数
     * @return array
     */
    public function submitOrder($params)
    {
        return $this->request('order/api/haoteam/pushorder', $params);
    }
    
    /**
     * 查询订单
     * @param string $orderNo 订单号
     * @return array
     */
    public function queryOrder($orderNo)
    {
        return $this->request('order/api/haoteam/query', [
            'orderNo' => $orderNo
        ]);
    }
    
    /**
     * 根据上游订单号查询订单 (haoteamquery接口)
     * @param string $apiOrderNo 上游订单号
     * @return array
     */
    public function haoteamQuery($apiOrderNo)
    {
        if (function_exists('trace')) {
            trace("[HaoteamApi] 开始查询订单，上游订单号: {$apiOrderNo}", 'info');
        }
        
        if (empty($apiOrderNo)) {
            if (function_exists('trace')) {
                trace("[HaoteamApi] 查询订单错误: 上游订单号为空", 'error');
            }
            return [
                'code' => 0,
                'msg' => '上游订单号不能为空',
                'data' => null
            ];
        }
        
        // 构建请求参数
        $params = [
            'api_sn' => $apiOrderNo // 上游订单号
        ];
        
        if (function_exists('trace')) {
            trace("[HaoteamApi] 查询订单请求参数: " . json_encode($params, JSON_UNESCAPED_UNICODE), 'info');
        }
        
        // 发送请求并返回结果
        $result = $this->request('order/api/haoteam/haoteamquery', $params);
        
        if (function_exists('trace')) {
            trace("[HaoteamApi] 查询订单响应结果: " . json_encode($result, JSON_UNESCAPED_UNICODE), 'info');
        }
        
        return $result;
    }
    
    /**
     * 临时文件MIME类型映射
     * @var array
     */
    private $tempFileMimeTypes = [];
    
    /**
     * 上传照片 - 号卡极团重传照片接口
     * 照片格式要求：PNG/JPG图片，不大于2MB
     * @param string $orderNo 订单号（上游订单号）
     * @param array $photos 照片数据 ['face' => path/url, 'back' => path/url, 'hand' => path/url, 'custom' => path/url]
     * @param array $context 调试上下文（本地订单号、配置ID等）
     * @return array
     */
    public function uploadPhoto($orderNo, $photos, $context = [])
    {
        // 号卡极团的重传照片接口
        $url = rtrim($this->baseUrl, '/') . '/order/api/haoteam/getimg';
        $traceId = $context['trace_id'] ?? ('HTUP' . date('YmdHis') . mt_rand(1000, 9999));

        if (function_exists('trace')) {
            $safeContext = [
                'trace_id' => $traceId,
                'local_order_id' => $context['local_order_id'] ?? '',
                'local_order_no' => $context['local_order_no'] ?? '',
                'up_order_no' => $orderNo,
                'api_config_id' => $context['api_config_id'] ?? '',
                'api_config_remark' => $context['api_config_remark'] ?? '',
                'base_url' => $this->baseUrl,
                'request_url' => $url,
                'photo_meta' => $this->buildPhotoInputMeta($photos),
            ];
            trace('[HaoteamApi] 上传照片请求上下文: ' . json_encode($safeContext, JSON_UNESCAPED_UNICODE), 'info');
        }

        // 基础三张照片必须上传，第四张 custom 按需上传
        $requiredPhotos = ['face', 'back', 'hand'];
        foreach ($requiredPhotos as $photoType) {
            if (empty($photos[$photoType])) {
                return [
                    'code' => 0,
                    'msg' => '基础三张照片必须全部上传！缺少: ' . $photoType,
                    'data' => null
                ];
            }
        }

        try {
            // 构建multipart/form-data请求
            $postFields = [
                'order_id' => $orderNo  // 文档要求参数名是 order_id
            ];

            // 处理照片文件
            $tempFiles = [];
            $preparedFileMeta = [];
            foreach ($photos as $type => $photoInput) {
                if (!empty($photoInput)) {
                    // 将图片路径/URL转换为临时文件
                    $tempFile = $this->photoPathToTempFile($photoInput, $type);
                    if ($tempFile) {
                        // 检查文件大小（不大于2MB）
                        $fileSize = filesize($tempFile);
                        if ($fileSize > 2 * 1024 * 1024) {
                            // 清理已创建的临时文件
                            foreach ($tempFiles as $tf) {
                                @unlink($tf);
                            }
                            @unlink($tempFile);
                            return [
                                'code' => 0,
                                'msg' => $type . '照片文件过大，不能超过2MB',
                                'data' => null
                            ];
                        }
                        
                        $tempFiles[] = $tempFile;
                        // 使用检测到的MIME类型
                        $mimeType = $this->tempFileMimeTypes[$tempFile] ?? 'image/jpeg';
                        $preparedFileMeta[$type] = [
                            'tmp_file' => basename($tempFile),
                            'size' => $fileSize,
                            'mime' => $mimeType
                        ];
                        
                        // 只允许 PNG/JPG 格式
                        if ($mimeType !== 'image/jpeg' && $mimeType !== 'image/png') {
                            // 清理临时文件
                            foreach ($tempFiles as $tf) {
                                @unlink($tf);
                            }
                            return [
                                'code' => 0,
                                'msg' => $type . '照片格式不正确，只支持PNG/JPG格式',
                                'data' => null
                            ];
                        }
                        
                        $extension = pathinfo($tempFile, PATHINFO_EXTENSION);
                        $postFields[$type] = new \CURLFile($tempFile, $mimeType, $type . '.' . $extension);
                    } else {
                        return [
                            'code' => 0,
                            'msg' => $type . '照片路径无效或文件不存在',
                            'data' => null
                        ];
                    }
                }
            }

            // 发送multipart/form-data请求
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postFields,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 60,  // 上传文件可能需要更长时间
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_HTTPHEADER => [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                ]
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $info = curl_getinfo($ch);
            $errno = curl_errno($ch);
            $error = curl_error($ch);
            curl_close($ch);

            // 清理临时文件
            foreach ($tempFiles as $tempFile) {
                @unlink($tempFile);
            }

            if ($error) {
                if (function_exists('trace')) {
                    $curlErrLog = [
                        'trace_id' => $traceId,
                        'local_order_id' => $context['local_order_id'] ?? '',
                        'local_order_no' => $context['local_order_no'] ?? '',
                        'up_order_no' => $orderNo,
                        'api_config_id' => $context['api_config_id'] ?? '',
                        'errno' => $errno,
                        'error' => $error,
                        'http_code' => $httpCode,
                        'primary_ip' => $info['primary_ip'] ?? '',
                        'total_time' => $info['total_time'] ?? 0,
                        'prepared_file_meta' => $preparedFileMeta,
                    ];
                    trace('[HaoteamApi] 上传照片CURL错误: ' . json_encode($curlErrLog, JSON_UNESCAPED_UNICODE), 'error');
                }
                return [
                    'code' => 0,
                    'msg' => 'CURL错误: ' . $error,
                    'data' => null
                ];
            }

            if (function_exists('trace')) {
                $responseLog = [
                    'trace_id' => $traceId,
                    'local_order_id' => $context['local_order_id'] ?? '',
                    'local_order_no' => $context['local_order_no'] ?? '',
                    'up_order_no' => $orderNo,
                    'api_config_id' => $context['api_config_id'] ?? '',
                    'http_code' => $httpCode,
                    'primary_ip' => $info['primary_ip'] ?? '',
                    'total_time' => $info['total_time'] ?? 0,
                    'request_size' => $info['request_size'] ?? 0,
                    'upload_content_length' => $info['upload_content_length'] ?? 0,
                    'size_upload' => $info['size_upload'] ?? 0,
                    'response_len' => strlen((string)$response),
                    'prepared_file_meta' => $preparedFileMeta,
                    'response_preview' => mb_substr((string)$response, 0, 500),
                ];
                trace('[HaoteamApi] 上传照片响应详情: ' . json_encode($responseLog, JSON_UNESCAPED_UNICODE), 'info');
            }

            // 解析响应
            $result = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'code' => 0,
                    'msg' => '响应解析失败',
                    'data' => null,
                    'raw_response' => $response,
                    'http_code' => $httpCode
                ];
            }
            if (function_exists('trace')) {
                $resultSummary = [
                    'trace_id' => $traceId,
                    'local_order_id' => $context['local_order_id'] ?? '',
                    'local_order_no' => $context['local_order_no'] ?? '',
                    'up_order_no' => $orderNo,
                    'api_config_id' => $context['api_config_id'] ?? '',
                    'result_code' => $result['code'] ?? '',
                    'result_msg' => $result['msg'] ?? ($result['message'] ?? ''),
                ];
                trace('[HaoteamApi] 上传照片解析结果: ' . json_encode($resultSummary, JSON_UNESCAPED_UNICODE), 'info');
            }

            return $result;

        } catch (\Exception $e) {
            if (function_exists('trace')) {
                trace('[HaoteamApi] 上传照片异常: ' . $e->getMessage(), 'error');
            }

            return [
                'code' => 0,
                'msg' => '上传异常: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * 将图片路径转换为临时文件（仅支持URL或本地路径，不支持base64）
     * @param string $photoPath 图片URL或本地路径
     * @param string $type 照片类型
     * @return string|false 临时文件路径
     */
    private function photoPathToTempFile($photoPath, $type)
    {
        try {
            $photoPath = trim((string)$photoPath);
            if ($photoPath === '' || strpos($photoPath, 'data:image') === 0) {
                return false;
            }

            $imageData = false;
            if (stripos($photoPath, 'http://') === 0 || stripos($photoPath, 'https://') === 0) {
                $imageData = @file_get_contents($photoPath);
            } else {
                $localPath = $photoPath;
                if (strpos($photoPath, '/') === 0) {
                    $localPath = public_path() . ltrim($photoPath, '/');
                }
                if (is_file($localPath)) {
                    $imageData = @file_get_contents($localPath);
                }
            }

            if ($imageData === false || $imageData === '') {
                return false;
            }

            $header = substr($imageData, 0, 8);
            if (substr($header, 0, 3) === "\xFF\xD8\xFF") {
                $mimeType = 'image/jpeg';
                $extension = 'jpg';
            } elseif (substr($header, 0, 8) === "\x89PNG\r\n\x1a\n") {
                $mimeType = 'image/png';
                $extension = 'png';
            } else {
                return false;
            }

            $tempFile = sys_get_temp_dir() . '/haoteam_' . $type . '_' . uniqid() . '.' . $extension;
            if (file_put_contents($tempFile, $imageData) === false) {
                return false;
            }

            $this->tempFileMimeTypes[$tempFile] = $mimeType;
            return $tempFile;

        } catch (\Exception $e) {
            if (function_exists('trace')) {
                trace('[HaoteamApi] 图片路径转临时文件异常: ' . $e->getMessage(), 'error');
            }
            return false;
        }
    }

    /**
     * 构建上传输入摘要日志（不输出敏感图片原文）
     * @param array $photos
     * @return array
     */
    private function buildPhotoInputMeta(array $photos): array
    {
        $meta = [];
        foreach (['face', 'back', 'hand', 'custom'] as $type) {
            $value = trim((string)($photos[$type] ?? ''));
            if ($value === '') {
                $meta[$type] = ['present' => false];
                continue;
            }

            $isUrl = (stripos($value, 'http://') === 0 || stripos($value, 'https://') === 0);
            $meta[$type] = [
                'present' => true,
                'source' => $isUrl ? 'url' : 'path',
                'len' => strlen($value),
                'tail' => substr($value, -40),
                'sha1' => sha1($value),
            ];
        }
        return $meta;
    }
    
    /**
     * 发送API请求
     * @param string $endpoint 接口路径
     * @param array $params 请求参数
     * @param string $method 请求方法
     * @return array
     */
    protected function request($endpoint, $params = [], $method = 'POST')
    {
        // 确保baseUrl以http或https开头
        if (strpos($this->baseUrl, 'http') !== 0) {
            $this->baseUrl = 'https://' . $this->baseUrl;
        }
        
        // 移除URL中可能存在的多余协议
        $this->baseUrl = preg_replace('#http[s]?://localhost:[0-9]+/https?://#', 'https://', $this->baseUrl);
        $this->baseUrl = preg_replace('#http[s]?://localhost:[0-9]+/#', '', $this->baseUrl);
        
        // 正确拼接URL，确保中间只有一个斜杠
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');
        
        // 添加公共参数
        $params['timestamp'] = time();
        $params['apiuser'] = $this->apiUser;
        $params['apipwd'] = md5($this->apiSecret);
        
        // 调试记录
        if (function_exists('trace')) {
            $logParams = $this->sanitizeRequestLogParams($params);
            if (isset($logParams['apipwd'])) {
                $logParams['apipwd'] = substr($logParams['apipwd'], 0, 3) . '***' . substr($logParams['apipwd'], -3);
            }
            if (isset($logParams['idCard'])) {
                $logParams['idCard'] = substr($logParams['idCard'], 0, 4) . '********' . substr($logParams['idCard'], -4);
            }
            if (isset($logParams['order_IDCard'])) {
                $logParams['order_IDCard'] = substr($logParams['order_IDCard'], 0, 4) . '********' . substr($logParams['order_IDCard'], -4);
            }
            
            trace("[HaoteamApi] API请求: {$url}, 参数: " . json_encode($logParams, JSON_UNESCAPED_UNICODE), 'info');
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
                
                // 检查endpoint确定提交方式
                if (strpos($endpoint, 'pushorder') !== false) {
                    // 使用multipart/form-data方式提交
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                    // 不设置Content-Type，让curl自动设置为multipart/form-data
                    if (function_exists('trace')) {
                        trace("[HaoteamApi] 使用multipart/form-data方式提交数据", 'info');
                    }
                } else if (strpos($endpoint, 'haoteamquery') !== false) {
                    // 查询接口使用表单方式提交，并设置Content-Type
                    $postFields = http_build_query($params);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
                        'Content-Length: ' . strlen($postFields)
                    ]);
                    if (function_exists('trace')) {
                        trace("[HaoteamApi] 使用application/x-www-form-urlencoded方式提交数据: " . $postFields, 'info');
                    }
                } else {
                    // 其他接口使用表单方式提交数据
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/x-www-form-urlencoded;charset=UTF-8'
                    ]);
                }
            } else {
                // GET请求，将参数拼接到URL
                $url .= '?' . http_build_query($params);
                curl_setopt($ch, CURLOPT_URL, $url);
            }
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            // 记录响应
            if (function_exists('trace')) {
                trace("[HaoteamApi] API响应: HTTP {$httpCode}, 响应: " . substr($response, 0, 1000) . (strlen($response) > 1000 ? '...(截断)' : ''), 'info');
                
                // 记录详细的请求信息
                $info = curl_getinfo($ch);
                trace("[HaoteamApi] 请求详情: " . json_encode($info, JSON_UNESCAPED_UNICODE), 'info');
            }
            
            if ($response === false) {
                $error = curl_error($ch);
                $errno = curl_errno($ch);
                curl_close($ch);
                
                if (function_exists('trace')) {
                    trace("[HaoteamApi] CURL错误: 错误码 {$errno}, 错误信息 {$error}", 'error');
                }
                
                return [
                    'code' => 0, // 错误代码保持为0
                    'msg' => "网络请求失败: [{$errno}] {$error}",
                    'data' => null,
                    'raw_response' => null,
                    'http_code' => $httpCode
                ];
            }
            
            curl_close($ch);
            
            // 检查是否为空响应
            if (empty($response)) {
                if (function_exists('trace')) {
                    trace("[HaoteamApi] 空响应", 'error');
                }
                
                return [
                    'code' => 0, // 错误代码保持为0
                    'msg' => '服务器返回空响应',
                    'data' => null,
                    'raw_response' => '',
                    'http_code' => $httpCode
                ];
            }
            
            // 处理可能包含PHP错误信息的响应
            $cleanResponse = $response;

            // 如果响应包含HTML错误信息，尝试提取JSON部分
            if (strpos($response, '<br />') !== false || strpos($response, '<b>') !== false) {
                // 查找最后一个完整的JSON对象
                $jsonStart = strrpos($response, '{');
                if ($jsonStart !== false) {
                    $cleanResponse = substr($response, $jsonStart);
                    if (function_exists('trace')) {
                        trace("[HaoteamApi] 检测到混合响应，提取JSON部分: " . $cleanResponse, 'info');
                    }
                }
            }

            // 解析JSON响应
            $result = json_decode($cleanResponse, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                if (function_exists('trace')) {
                    trace("[HaoteamApi] JSON解析错误: " . json_last_error_msg(), 'error');
                    trace("[HaoteamApi] 原始响应内容: " . substr($response, 0, 1000), 'error'); // 只显示前1000字符
                    trace("[HaoteamApi] 清理后响应内容: " . $cleanResponse, 'error');
                    trace("[HaoteamApi] HTTP状态码: " . $httpCode, 'error');
                }

                return [
                    'code' => 0, // 错误代码保持为0
                    'msg' => '无法解析响应: ' . json_last_error_msg(),
                    'data' => null,
                    'raw_response' => $response,
                    'http_code' => $httpCode
                ];
            }
            
            // 记录完整解析后的数据结构
            if (function_exists('trace')) {
                trace("[HaoteamApi] 解析后的数据结构: " . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'info');
            }
            
            // 对getNumbers请求的特殊处理
            if (strpos($endpoint, 'gethao') !== false) {
                if (is_array($result)) {
                    // 返回数组格式的号码列表
                    return [
                        'code' => 1, // 修改成功代码为1，与前端期望一致
                        'msg' => '请求成功',
                        'data' => $result,
                        'raw_response' => $response,
                        'http_code' => $httpCode
                    ];
                } else if ($result === null) {
                    // API返回null，表示该地区暂无可用号码
                    if (function_exists('trace')) {
                        trace("[HaoteamApi] 获取号码返回null，该地区暂无可用号码", 'info');
                    }
                    return [
                        'code' => 1, // 仍然返回成功，但数据为空
                        'msg' => '该地区暂无可用号码',
                        'data' => [],
                        'raw_response' => $response,
                        'http_code' => $httpCode
                    ];
                }
            }
            
            // 对提交订单接口的特殊处理
            if (strpos($endpoint, 'pushorder') !== false && is_array($result)) {
                // 增加日志记录完整请求参数和响应
                if (function_exists('trace')) {
                    $logParams = $this->sanitizeRequestLogParams($params);
                    if (isset($logParams['order_IDCard'])) {
                        $logParams['order_IDCard'] = substr($logParams['order_IDCard'], 0, 4) . '********' . substr($logParams['order_IDCard'], -4);
                    }
                    trace("[HaoteamApi] 提交订单请求详细参数: " . json_encode($logParams, JSON_UNESCAPED_UNICODE), 'info');
                    trace("[HaoteamApi] 提交订单响应详细数据: " . json_encode($result, JSON_UNESCAPED_UNICODE), 'info');
                }
                
                // 1. 明确的成功情况 - code=200
                if (isset($result['code']) && ($result['code'] === '200' || $result['code'] === 200)) {
                    if (function_exists('trace')) {
                        trace("[HaoteamApi] 订单提交成功，上游订单号: " . ($result['apiorder'] ?? '未返回'), 'info');
                    }
                    
                    // 转换为内部成功代码
                    return [
                        'code' => 1, // 修改为前端期望的成功代码
                        'msg' => '提交订单成功',
                        'data' => $result,
                        'raw_response' => $response,
                        'http_code' => $httpCode,
                        'apiorder' => $result['apiorder'] ?? '' // 确保上游订单号可以被正确提取
                    ];
                }
                // 2. 其他可能的成功情况处理 - 某些接口可能返回不同的成功码
                else if (isset($result['apiorder']) && !empty($result['apiorder'])) {
                    if (function_exists('trace')) {
                        trace("[HaoteamApi] 订单提交成功（非标准格式），上游订单号: " . $result['apiorder'], 'info');
                    }
                    
                    return [
                        'code' => 1, // 强制设为成功
                        'msg' => '提交订单成功',
                        'data' => $result,
                        'raw_response' => $response,
                        'http_code' => $httpCode,
                        'apiorder' => $result['apiorder']
                    ];
                } 
                // 3. 明确失败的情况
                else {
                    // 订单提交失败，记录详细错误
                    if (function_exists('trace')) {
                        trace("[HaoteamApi] 订单提交失败，错误码: " . ($result['code'] ?? 'unknown') . ", 错误信息: " . ($result['reason'] ?? ($result['msg'] ?? '未知错误')), 'error');
                    }
                    
                    // 订单提交失败
                    return [
                        'code' => 0, // 统一使用0表示错误
                        'msg' => $result['reason'] ?? ($result['msg'] ?? '提交订单失败: 未知错误'),
                        'data' => $result,
                        'raw_response' => $response,
                        'http_code' => $httpCode
                    ];
                }
            }
            
            if (is_array($result)) {
                // 处理号卡极团API的标准响应
                if (isset($result[0]['id'])) {
                    // 这是产品列表格式的数据
                    return [
                        'code' => 1, // 修改成功代码为1，与前端期望一致
                        'msg' => '请求成功',
                        'data' => $result,
                        'raw_response' => $response,
                        'http_code' => $httpCode
                    ];
                } else if (isset($result['id'])) {
                    // 单个产品或其他对象
                    return [
                        'code' => 1, // 修改成功代码为1，与前端期望一致
                        'msg' => '请求成功',
                        'data' => [$result], // 转为数组格式保持一致性
                        'raw_response' => $response,
                        'http_code' => $httpCode
                    ];
                } else {
                    // 其他响应格式
                    return [
                        'code' => isset($result['code']) && (
                            $result['code'] === '200' || 
                            $result['code'] === 200 || 
                            $result['code'] === '1' || 
                            $result['code'] === 1
                        ) ? 1 : (isset($result['code']) ? intval($result['code']) : 1),
                        'msg' => isset($result['msg']) ? $result['msg'] : '请求成功',
                        'data' => isset($result['data']) ? $result['data'] : $result,
                        'raw_response' => $response,
                        'http_code' => $httpCode
                    ];
                }
            }
            
            // 非JSON响应或解析失败
            return [
                'code' => 0, // 错误代码保持为0
                'msg' => '无法解析响应',
                'data' => null,
                'raw_response' => $response,
                'http_code' => $httpCode
            ];
            
        } catch (\Exception $e) {
            if (function_exists('trace')) {
                trace("[HaoteamApi] 请求异常: " . $e->getMessage() . "\n" . $e->getTraceAsString(), 'error');
            }
            
            return [
                'code' => 0, // 错误代码保持为0
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
     * 脱敏请求日志参数，避免输出服务器本地路径与敏感内容
     * @param array $params
     * @return array
     */
    private function sanitizeRequestLogParams(array $params)
    {
        foreach ($params as $key => $value) {
            if ($value instanceof \CURLFile) {
                $params[$key] = '[CURLFile:' . $value->getPostFilename() . ']';
                continue;
            }

            if (is_array($value)) {
                $params[$key] = $this->sanitizeRequestLogParams($value);
            }
        }

        return $params;
    }

    /**
     * 查询单个产品
     * @param string $packageCode 套餐编号
     * @return array
     */
    public function getProductByCode($packageCode)
    {
        // 号卡极团没有单独的查询接口，需要从套餐列表中查找
        $result = $this->getPackageList();

        if (function_exists('trace')) {
            trace('[HaoteamApi] 查询单个产品: ' . $packageCode . ', API返回: ' . json_encode($result, JSON_UNESCAPED_UNICODE), 'info');
        }

        if ($result && isset($result['code']) && $result['code'] == 1) {
            $packages = $result['data'] ?? [];

            if (function_exists('trace')) {
                $samplePackages = array_slice($packages, 0, 3); // 只显示前3个套餐的结构
                trace('[HaoteamApi] 套餐列表示例: ' . json_encode($samplePackages, JSON_UNESCAPED_UNICODE), 'info');
            }

            // 查找指定编号的套餐 - 使用id字段进行匹配
            foreach ($packages as $package) {
                $packageId = $package['id'] ?? '';

                if ($packageId === $packageCode) {
                    if (function_exists('trace')) {
                        trace('[HaoteamApi] 找到匹配套餐: ' . $packageCode . ' - ' . ($package['shop_name'] ?? ''), 'info');
                    }

                    return [
                        'code' => 1,
                        'msg' => '查询产品成功',
                        'data' => [$package] // 返回数组格式，保持一致性
                    ];
                }
            }

            if (function_exists('trace')) {
                trace('[HaoteamApi] 未找到套餐: ' . $packageCode . ', 总共' . count($packages) . '个套餐', 'info');
            }

            return [
                'code' => 0,
                'msg' => '未找到指定编号的产品',
                'data' => []
            ];
        }

        return [
            'code' => 0,
            'msg' => '获取产品列表失败',
            'data' => []
        ];
    }
}
