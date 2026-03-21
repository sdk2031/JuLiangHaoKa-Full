<?php
namespace app\api\controller\kapi\jlsystem;

use think\facade\Db;
use app\common\helper\PluginHelper;

/**
 * 同系统API选号接口
 */
class SelectNumber
{
    public function __construct()
    {
        PluginHelper::check('jlsystem');
    }

    protected function success($msg = '操作成功', $data = [], $code = 1)
    {
        return json(['code' => $code, 'msg' => $msg, 'data' => $data, 'time' => time()]);
    }

    protected function error($msg = '操作失败', $data = [], $code = 0)
    {
        return json(['code' => $code, 'msg' => $msg, 'data' => $data, 'time' => time()]);
    }

    /**
     * 获取号码列表
     */
    public function getNumbers()
    {
        $productId = input('param.product_id', '');
        $province = input('param.province', '');
        $city = input('param.city', '');
        $district = input('param.district', '');

        if (empty($productId)) {
            return $this->error('产品ID不能为空');
        }

        // 获取产品信息
        $product = Db::name('product')->where([
            'id' => $productId,
            'api_name' => '同系统'
        ])->find();

        if (!$product) {
            return $this->error('产品不存在');
        }

        if (empty($product['number'])) {
            return $this->error('产品编号为空');
        }

        // 检查是否支持选号
        if ($product['selectNumber'] != 1) {
            return $this->error('该产品不支持选号');
        }

        $config = Config::getConfig();
        if (!$config || empty($config['api_key']) || empty($config['api_secret'])) {
            return $this->error('请先配置同系统API');
        }

        try {
            // 调用上游选号接口
            $url = rtrim($config['api_url'], '/') . '/v1/number/list';
            $params = [
                'product_id' => $product['number'],
                'province' => $province,
                'city' => $city,
                'district' => $district
            ];
            $url .= '?' . http_build_query($params);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Agent-Id: ' . $config['api_key'],
                'API-Key: ' . $config['api_secret']
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response === false) {
                return $this->error('获取号码列表失败：网络连接错误');
            }

            $result = json_decode($response, true);

            if ($httpCode == 200 && isset($result['code']) && $result['code'] == 0) {
                $numbers = $result['data']['list'] ?? [];
                $total = $result['data']['total'] ?? count($numbers);

                return $this->success('获取成功', [
                    'list' => $numbers,
                    'total' => $total
                ]);
            } else {
                $errorMsg = $result['message'] ?? '获取号码列表失败';
                return $this->error($errorMsg);
            }

        } catch (\Exception $e) {
            return $this->error('获取号码列表异常：' . $e->getMessage());
        }
    }

    /**
     * 获取号码列表（别名方法）
     */
    public function index()
    {
        return $this->getNumbers();
    }
}
