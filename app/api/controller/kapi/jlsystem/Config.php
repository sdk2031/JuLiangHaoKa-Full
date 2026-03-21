<?php
namespace app\api\controller\kapi\jlsystem;

use think\facade\Db;
use app\common\helper\PluginHelper;

/**
 * 同系统API配置管理
 * 用于对接相同系统架构的上游平台
 * 支持多账号配置
 */
class Config
{
    public function __construct()
    {
        // 插件授权检查
        PluginHelper::check('jlsystem');
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
        $configId = input('config_id', 0);
        
        if ($configId > 0) {
            $config = Db::name('config_api')->where('id', $configId)->where('api_type', 'jlsystem')->find();
        } else {
            $config = Db::name('config_api')->where('api_type', 'jlsystem')->find();
        }

        if (!$config) {
            return $this->success('获取成功', [
                'api_key' => '',
                'api_secret' => '',
                'api_url' => '',
                'status' => 1,
                'commission_deduction_amount' => 0,
                'sync_settlement' => 0,
                'sync_commission' => 0
            ]);
        }

        return $this->success('获取成功', [
            'id' => $config['id'],
            'api_key' => $config['api_key'] ?? '',
            'api_secret' => $config['api_secret'] ?? '',
            'api_url' => $config['api_url'] ?? '',
            'status' => $config['status'] ?? 1,
            'commission_deduction_amount' => $config['commission_deduction_amount'] ?? 0,
            'sync_settlement' => $config['sync_settlement'] ?? 0,
            'sync_commission' => $config['sync_commission'] ?? 0,
            'remark' => $config['remark'] ?? ''
        ]);
    }

    /**
     * 测试连接
     */
    public function test()
    {
        $configId = input('config_id', 0);
        
        try {
            if ($configId > 0) {
                $config = Db::name('config_api')->where('id', $configId)->where('api_type', 'jlsystem')->find();
            } else {
                $config = Db::name('config_api')->where('api_type', 'jlsystem')->find();
            }

            if (!$config || empty($config['api_key']) || empty($config['api_secret'])) {
                return $this->error('请先配置代理ID和API密钥');
            }

            // 调用产品列表接口测试连通性
            $url = rtrim($config['api_url'], '/') . '/v1/product/list?page=1&limit=1';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Agent-Id: ' . $config['api_key'],
                'API-Key: ' . $config['api_secret']
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                return $this->error('网络连接失败：' . $error);
            }

            if ($httpCode == 404) {
                return $this->error('API接口不存在，请检查API地址是否正确');
            }

            $result = json_decode($response, true);

            if ($httpCode == 200 && isset($result['code']) && $result['code'] == 0) {
                $total = $result['data']['total'] ?? 0;
                return $this->success('连接测试成功，共有 ' . $total . ' 个产品');
            } else {
                $msg = $result['message'] ?? $result['msg'] ?? '未知错误';
                return $this->error('API返回错误：' . $msg);
            }

        } catch (\think\exception\HttpResponseException $e) {
            throw $e;
        } catch (\Exception $e) {
            return $this->error('测试失败：' . $e->getMessage());
        }
    }

    /**
     * 获取API配置（内部调用，支持多配置）
     * @param int $configId 配置ID，为0时获取第一个配置
     */
    public static function getConfig($configId = 0)
    {
        if ($configId > 0) {
            return Db::name('config_api')->where('id', $configId)->where('api_type', 'jlsystem')->find();
        }
        return Db::name('config_api')->where('api_type', 'jlsystem')->find();
    }

    /**
     * 获取所有配置
     */
    public static function getAllConfigs()
    {
        return Db::name('config_api')->where('api_type', 'jlsystem')->where('status', 1)->select()->toArray();
    }

    /**
     * 根据配置生成API名称
     */
    public static function getApiName($config)
    {
        $baseName = '同系统';
        if (!empty($config['remark'])) {
            return $baseName . ' (' . $config['remark'] . ')';
        }
        return $baseName;
    }
}
