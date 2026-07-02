<?php
/* *
 * 彩虹易支付SDK服务类
 * 说明：
 * 包含发起支付、查询订单、回调验证等功能
 */

class EpayCore
{
    private $apiurl;
    private $pid;
    private $platform_public_key;
    private $merchant_private_key;
    
    private $sign_type = 'RSA';

    function __construct($config){
        $this->apiurl = $config['apiurl'];
        $this->pid = $config['pid'];
        $this->platform_public_key = $config['platform_public_key'];
        $this->merchant_private_key = $config['merchant_private_key'];
    }

    // 发起支付（页面跳转）
    public function pagePay($param_tmp, $button='正在跳转'){
        $requrl = $this->apiurl.'api/pay/submit';
        $param = $this->buildRequestParam($param_tmp);

        $html = '<form id="dopay" action="'.$this->escapeHtml($requrl).'" method="post">';
        foreach ($param as $k=>$v) {
            $html.= '<input type="hidden" name="'.$this->escapeHtml($k).'" value="'.$this->escapeHtml($v).'"/>';
        }
        $html .= '<input type="submit" value="'.$this->escapeHtml($button).'"></form><script>
        // 确保DOM加载完成后再提交表单
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", function() {
                setTimeout(function() {
                    document.getElementById("dopay").submit();
                }, 100);
            });
        } else {
            setTimeout(function() {
                document.getElementById("dopay").submit();
            }, 100);
        }
        </script>';

        return $html;
    }

    private function escapeHtml($value){
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    // 发起支付（获取链接）
    public function getPayLink($param_tmp){
        $requrl = $this->apiurl.'api/pay/submit';
        $param = $this->buildRequestParam($param_tmp);
        $url = $requrl.'?'.http_build_query($param);
        return $url;
    }

    // 发起支付（API接口）
    public function apiPay($params){
        return $this->execute('api/pay/create', $params);
    }

    // 发起API请求
    public function execute($path, $params){
        $path = ltrim($path, '/');
        $requrl = $this->apiurl.$path;
        $param = $this->buildRequestParam($params);
        $response = $this->getHttpResponse($requrl, http_build_query($param));
        $arr = json_decode($response, true);
        if($arr && $arr['code'] == 0){
            if(!$this->verify($arr)){
                throw new \Exception('返回数据验签失败');
            }
            return $arr;
        }else{
            throw new \Exception($arr ? $arr['msg'] : '请求失败');
        }
    }

    // 回调验证
    public function verify($arr){
        if(empty($arr) || empty($arr['sign'])) return false;

        if(empty($arr['timestamp']) || abs(time() - $arr['timestamp']) > 300) return false;

        $sign = $arr['sign'];
        
        return $this->rsaPublicVerify($this->getSignContent($arr), $sign);
    }

    // 查询订单支付状态
    public function orderStatus($trade_no){
        $result = $this->queryOrder($trade_no);
        if($result && $result['status']==1){
            return true;
        }else{
            return false;
        }
    }

    // 查询订单
    public function queryOrder($trade_no){
        $params = [
            'trade_no' => $trade_no,
        ];
        return $this->execute('api/pay/query', $params);
    }

    // 订单退款
    public function refund($out_refund_no, $trade_no, $money){
        $params = [
            'trade_no' => $trade_no,
            'money' => $money,
            'out_refund_no' => $out_refund_no,
        ];
        return $this->execute('api/pay/refund', $params);
    }

    private function buildRequestParam($params){
        $params['pid'] = $this->pid;
        $params['timestamp'] = time().'';
        $mysign = $this->getSign($params);
        $params['sign'] = $mysign;
        $params['sign_type'] = $this->sign_type;
        \think\facade\Log::info('易支付v2接口 - 使用RSA签名');
        return $params;
    }

    // 生成签名
    private function getSign($params){
        return $this->rsaPrivateSign($this->getSignContent($params));
    }

    // 获取待签名字符串
    private function getSignContent($params){
        ksort($params);
        $signstr = '';
        foreach ($params as $k => $v) {
            if(is_array($v) || $this->isEmpty($v) || $k == 'sign' || $k == 'sign_type') continue;
            $signstr .= '&' . $k . '=' . $v;
        }
        $signstr = substr($signstr, 1);
        
        // 详细记录签名相关信息
        \think\facade\Log::info('易支付签名字符串: ' . $signstr);
        \think\facade\Log::info('易支付签名参数总数: ' . count($params));
        \think\facade\Log::info('易支付商户ID(PID): ' . $this->pid);
        \think\facade\Log::info('易支付API地址: ' . $this->apiurl);
        
        return $signstr;
    }

    private function isEmpty($value)
    {
        return $value === null || trim($value) === '';
    }

    // 商户私钥签名
    private function rsaPrivateSign($data){
        // 直接使用传入的私钥，EpayService已经处理了格式
        $key = $this->merchant_private_key;
        
        // 使用ThinkPHP日志输出调试信息
        \think\facade\Log::info('易支付RSA签名详细调试', [
            'sign_data' => $data,
            'sign_data_length' => strlen($data),
            'sign_data_bytes' => strlen($data),
            'sign_data_utf8_check' => mb_check_encoding($data, 'UTF-8'),
            'merchant_id' => $this->pid,
            'private_key_length' => strlen($this->merchant_private_key),
            'key_has_begin_marker' => strpos($key, '-----BEGIN') !== false,
            'key_has_end_marker' => strpos($key, '-----END') !== false,
            'api_url' => $this->apiurl
        ]);
        
        $privatekey = openssl_get_privatekey($key);
        if(!$privatekey){
            $error = openssl_error_string();
            \think\facade\Log::error('易支付私钥解析失败', [
                'error' => $error,
                'key_length' => strlen($key),
                'key_preview' => substr($key, 0, 100) . '...'
            ]);
            throw new \Exception('签名失败，商户私钥错误');
        }
        
        $result = openssl_sign($data, $sign, $privatekey, OPENSSL_ALGO_SHA256);
        if(!$result){
            $error = openssl_error_string();
            \think\facade\Log::error('易支付签名计算失败', ['error' => $error]);
            throw new \Exception('签名计算失败');
        }
        
        $signature = base64_encode($sign);
        \think\facade\Log::info('易支付RSA签名生成成功', [
            'signature_length' => strlen($signature),
            'signature_preview' => substr($signature, 0, 50) . '...',
            'raw_sign_length' => strlen($sign)
        ]);
        
        return $signature;
    }

    // 平台公钥验签
    private function rsaPublicVerify($data, $sign){
        // 处理平台公钥格式
        $key = trim($this->platform_public_key);
        if (strpos($key, '-----BEGIN') === false) {
            // 纯Base64格式，添加公钥头尾标识
            $key = "-----BEGIN PUBLIC KEY-----\n" .
                wordwrap($key, 64, "\n", true) .
                "\n-----END PUBLIC KEY-----";
        }
        
        \think\facade\Log::info('易支付验签调试', [
            'platform_key_length' => strlen($this->platform_public_key),
            'formatted_key_length' => strlen($key),
            'key_preview' => substr($key, 0, 100) . '...',
            'sign_length' => strlen($sign),
            'data_length' => strlen($data)
        ]);
        
        $publickey = openssl_get_publickey($key);
        if (!$publickey) {
            $error = openssl_error_string();
            \think\facade\Log::error('平台公钥解析失败', [
                'error' => $error,
                'key_preview' => substr($key, 0, 200)
            ]);
            throw new \Exception("验签失败，平台公钥错误: " . $error);
        }
        
        $result = openssl_verify($data, base64_decode($sign), $publickey, OPENSSL_ALGO_SHA256);
        \think\facade\Log::info('易支付验签结果', [
            'result' => $result,
            'expected' => 1,
            'success' => $result === 1
        ]);
        
        return $result === 1;
    }

    // 请求外部资源
    private function getHttpResponse($url, $post = false, $timeout = 10){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $httpheader[] = "Accept: */*";
        $httpheader[] = "Accept-Language: zh-CN,zh;q=0.8";
        $httpheader[] = "Connection: close";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if($post){
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
}
