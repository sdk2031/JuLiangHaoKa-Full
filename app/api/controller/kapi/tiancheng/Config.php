<?php
namespace app\api\controller\kapi\tiancheng;
use think\facade\Db;
use app\common\helper\PluginHelper;
use app\api\controller\kapi\RequiresAdminAuth;

/**
 * 天城智控配置管理
 */
class Config
{
    use RequiresAdminAuth;

    public function __construct()
    {
        $this->assertAdminLogin();
        // 插件授权检查
        PluginHelper::check('tiancheng');
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
     * 获取配置
     */
    public function index()
    {
        $config = Db::name('config_api')->where('api_type', 'tiancheng')->find();

        if (!$config) {
            return $this->success('获取成功', [
                'api_key' => '',
                'api_secret' => '',
                'api_url' => 'http://hk.shandonghuacheng.cn/nhk',
                'status' => 1,
                'commission_deduction_amount' => 0
            ]);
        }

        return $this->success('获取成功', [
            'api_key' => $config['api_key'] ?? '',
            'api_secret' => $config['api_secret'] ?? '',
            'api_url' => $config['api_url'] ?? 'http://hk.shandonghuacheng.cn/nhk',
            'status' => $config['status'] ?? 1,
            'commission_deduction_amount' => $config['commission_deduction_amount'] ?? 0
        ]);
    }

    /**
     * 保存配置
     */
    public function save()
    {
        $data = $this->request->post();

        // 验证必填字段
        if (empty($data['api_key']) || !isset($data['api_secret']) || $data['api_secret'] === '' || $data['api_secret'] === 'undefined') {
            return $this->error('用户ID和密钥不能为空');
        }

        if (empty($data['api_url'])) {
            return $this->error('API地址不能为空');
        }

        try {
            $config = Db::name('config_api')->where('api_type', 'tiancheng')->find();

            $saveData = [
                'api_key' => $data['api_key'],
                'api_secret' => $data['api_secret'],
                'api_url' => $data['api_url'],
                'status' => isset($data['status']) ? ($data['status'] ? 1 : 0) : 1,
                'commission_deduction_amount' => intval($data['commission_deduction_amount'] ?? 0),
                'update_time' => time()
            ];

            if ($config) {
                // 更新配置
                $result = Db::name('config_api')->where('api_type', 'tiancheng')->update($saveData);
                // 对于更新操作，即使返回0（没有行被影响）也认为是成功的
                if (function_exists('trace')) {
                    trace('天城智控配置更新成功，准备响应', 'info');
                }
                return $this->success('保存成功');
            } else {
                // 新增配置
                $saveData['api_type'] = 'tiancheng';
                $saveData['name'] = '天城智控API';
                $saveData['create_time'] = time();
                $result = Db::name('config_api')->insert($saveData);

                if ($result) {
                    return $this->success('保存成功');
                } else {
                    return $this->error('保存失败');
                }
            }
        } catch (\think\exception\HttpResponseException $e) {
            // 这是正常的响应异常，直接抛出
            throw $e;
        } catch (\Exception $e) {
            if (function_exists('trace')) {
                trace('天城智控配置保存异常: ' . $e->getMessage(), 'error');
            }
            return $this->error('保存失败：' . $e->getMessage());
        }
    }

    /**
     * 测试连接
     */
    public function test()
    {
        try {
            $config = Db::name('config_api')->where('api_type', 'tiancheng')->find();

            if (!$config || empty($config['api_key']) || empty($config['api_secret'])) {
                return $this->error('请先配置用户ID和密钥');
            }

            // 实际测试API连接
            $timeStamp = time() * 1000;
            $signParams = ['userid' => $config['api_key']];
            $signStr = $config['api_key'] . json_encode($signParams, JSON_UNESCAPED_UNICODE) . $config['api_secret'];
            $sign = strtoupper(md5($signStr));

            $params = [
                'userId' => $config['api_key'],
                'timeStamp' => $timeStamp,
                'sign' => $sign
            ];

            $url = rtrim($config['api_url'], '/') . '/nnk/openapi/downstream/getGoodsInfo';
            $queryString = http_build_query($params);
            $fullUrl = $url . '?' . $queryString;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $fullUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                return $this->error('网络连接失败：' . $error);
            }

            if ($httpCode == 404) {
                return $this->error('API接口不存在，请联系天城智控技术支持确认最新的API地址。当前地址：' . $config['api_url']);
            } elseif ($httpCode == 401 || $httpCode == 403) {
                return $this->error('认证失败，请检查用户ID和密钥是否正确');
            } elseif ($httpCode == 200) {
                $result = json_decode($response, true);
                if (isset($result['code']) && $result['code'] == 200) {
                    return $this->success('连接测试成功，API正常');
                } else {
                    $msg = isset($result['msg']) ? $result['msg'] : '未知错误';
                    return $this->error('API返回错误：' . $msg);
                }
            } else {
                return $this->error('服务器错误，HTTP状态码：' . $httpCode);
            }

        } catch (\think\exception\HttpResponseException $e) {
            // 这是正常的响应异常，直接抛出
            throw $e;
        } catch (\Exception $e) {
            return $this->error('测试失败：' . $e->getMessage());
        }
    }

    /**
     * 删除配置
     */
    public function delete()
    {
        try {
            $result = Db::name('config_api')->where('api_type', 'tiancheng')->delete();

            if ($result) {
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败，配置不存在');
            }
        } catch (\think\exception\HttpResponseException $e) {
            // 这是正常的响应异常，直接抛出
            throw $e;
        } catch (\Exception $e) {
            return $this->error('删除失败：' . $e->getMessage());
        }
    }
}
