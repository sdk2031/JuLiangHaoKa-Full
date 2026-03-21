<?php
namespace app\api\controller\kapi\jlsystem;

use think\facade\Db;
use app\common\helper\PluginHelper;

/**
 * 同系统API订单管理
 */
class Order
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
     * 创建订单（兼容旧接口）
     */
    public function create()
    {
        return $this->addOrder();
    }

    /**
     * 提交订单（前端调用）
     */
    public function submitOrder()
    {
        return $this->addOrder();
    }

    /**
     * 获取号码列表
     */
    public function getNumbers()
    {
        $selectNumber = new SelectNumber();
        return $selectNumber->getNumbers();
    }

    /**
     * 提交订单（主要接口）
     */
    public function addOrder()
    {
        $data = input('post.');

        trace('同系统API订单提交开始');

        // ========== 第1步：验证参数和获取产品信息 ==========
        $productId = $data['product_id'] ?? '';
        if (empty($productId)) {
            return $this->error('产品ID不能为空');
        }

        $product = Db::name('product')->where('id', $productId)->find();

        if (!$product) {
            return $this->error('产品不存在');
        }

        if (empty($product['number'])) {
            return $this->error('产品编号为空，请重新同步产品数据');
        }

        // 获取配置（通过产品的api_config_id）
        $configId = $product['api_config_id'] ?? 0;
        $config = Config::getConfig($configId);
        if (!$config || empty($config['api_key']) || empty($config['api_secret'])) {
            return $this->error('请先配置同系统API');
        }
        
        $apiName = Config::getApiName($config);

        // ========== 第2步：准备API请求参数 ==========
        $customerName = $data['customer_name'] ?? '';
        $customerIdcard = $data['customer_idcard'] ?? '';
        $orderPhone = $data['order_phone'] ?? '';
        $province = $data['province'] ?? '';
        $city = $data['city'] ?? '';
        $district = $data['district'] ?? '';
        $address = $data['customer_address'] ?? '';
        $selectedNumber = $data['selected_number'] ?? '';
        $shopCode = $data['shop_code'] ?? '';

        // 验证必填字段
        if (empty($customerName)) return $this->error('客户姓名不能为空');
        if (empty($customerIdcard)) return $this->error('身份证号不能为空');
        if (empty($orderPhone)) return $this->error('手机号不能为空');
        if (empty($province) || empty($city) || empty($district)) return $this->error('地区信息不完整');
        if (empty($address)) return $this->error('详细地址不能为空');

        // 验证身份证照片（如果需要）
        if ($product['is_id_photo'] == 1) {
            if (empty($data['id_card_front'])) return $this->error('请上传身份证正面照片');
            if (empty($data['id_card_back'])) return $this->error('请上传身份证反面照片');
            if (empty($data['id_card_face'])) return $this->error('请上传手持身份证照片');
            if ($product['is_four_photo'] == 1 && empty($data['id_card_four'])) {
                return $this->error('请上传第四证照片');
            }
        }

        // 生成本地订单号
        $localOrderNo = 'HK' . date('YmdHis') . rand(1000, 9999);

        // 构建回调URL
        $callbackUrl = request()->domain() . '/api/kapi.jlsystem.callback/index';

        // ========== 第3步：调用上游API ==========
        $url = rtrim($config['api_url'], '/') . '/v1/order/submit';
        $queryParams = [
            'product_id' => $product['number'],
            'partner_order_no' => $localOrderNo,
            'phone' => $orderPhone,
            'customer_name' => $customerName,
            'idcard' => $customerIdcard,
            'province' => $province,
            'city' => $city,
            'district' => $district,
            'address' => $address,
            'phone_number' => $selectedNumber,
            'callback_url' => $callbackUrl
        ];
        $url .= '?' . http_build_query($queryParams);

        // 准备multipart/form-data请求
        $postFields = [];
        if ($product['is_id_photo'] == 1) {
            if (!empty($data['id_card_front'])) {
                $postFields['id_card_front'] = $this->prepareImageForUpload($data['id_card_front'], 'front.jpg');
            }
            if (!empty($data['id_card_back'])) {
                $postFields['id_card_back'] = $this->prepareImageForUpload($data['id_card_back'], 'back.jpg');
            }
            if (!empty($data['id_card_face'])) {
                $postFields['id_card_face'] = $this->prepareImageForUpload($data['id_card_face'], 'face.jpg');
            }
            if (!empty($data['id_card_four'])) {
                $postFields['id_card_four'] = $this->prepareImageForUpload($data['id_card_four'], 'four.jpg');
            }
        }

        trace('同系统API请求URL: ' . $url);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        if (!empty($postFields)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Agent-Id: ' . $config['api_key'],
            'API-Key: ' . $config['api_secret']
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        trace('同系统API响应: ' . $response);

        if ($response === false) {
            return $this->error('网络连接错误：' . $curlError);
        }

        $result = json_decode($response, true);
        if ($result === null) {
            return $this->error('上游响应格式异常');
        }

        if ($httpCode != 200 || !isset($result['code']) || $result['code'] != 0) {
            $errorMsg = $result['message'] ?? '订单提交失败';
            return $this->error($errorMsg);
        }

        $upOrderNo = $result['data']['order_no'] ?? '';
        if (empty($upOrderNo)) {
            return $this->error('上游系统未返回订单号');
        }

        trace('上游返回订单号: ' . $upOrderNo);

        // ========== 第4步：创建本地订单 ==========
        try {
            Db::startTrans();

            // 获取店铺代理ID
            $agentId = 0;
            if (!empty($shopCode)) {
                $shopInfo = Db::name('agent_shop')->where('shop_code', $shopCode)->find();
                if ($shopInfo && isset($shopInfo['agent_id'])) {
                    $agentId = $shopInfo['agent_id'];
                }
            }

            // 计算佣金
            $commission = floatval($product['commission'] ?? 0);
            if ($commission > 0 && !empty($agentId)) {
                $commissionService = new \app\common\service\CommissionCalculationService();
                $commissionResult = $commissionService->calculateTotalDisplayCommission($commission, $agentId, $productId);
                if ($commissionResult['success']) {
                    $commission = $commissionResult['total_commission'];
                }
            }

            // 处理身份证照片保存
            $photoData = [];
            if ($product['is_id_photo'] == 1) {
                if (!empty($data['id_card_front'])) {
                    $photoData['id_card_front'] = \app\common\service\ImageService::saveImageAsPng($data['id_card_front'], 'id_card_front', 'jlsystem') ?: $data['id_card_front'];
                }
                if (!empty($data['id_card_back'])) {
                    $photoData['id_card_back'] = \app\common\service\ImageService::saveImageAsPng($data['id_card_back'], 'id_card_back', 'jlsystem') ?: $data['id_card_back'];
                }
                if (!empty($data['id_card_face'])) {
                    $photoData['id_card_face'] = \app\common\service\ImageService::saveImageAsPng($data['id_card_face'], 'id_card_face', 'jlsystem') ?: $data['id_card_face'];
                }
                if (!empty($data['id_card_four'])) {
                    $photoData['id_card_four'] = \app\common\service\ImageService::saveImageAsPng($data['id_card_four'], 'id_card_four', 'jlsystem') ?: $data['id_card_four'];
                }
            }

            // 构建订单数据
            $orderData = [
                'api_name' => $apiName,
                'api_config_id' => $config['id'],
                'order_no' => $localOrderNo,
                'up_order_no' => $upOrderNo,
                'agent_id' => $agentId,
                'customer_name' => $customerName,
                'phone' => $orderPhone,
                'idcard' => $customerIdcard,
                'province' => $province,
                'city' => $city,
                'district' => $district,
                'address' => $address,
                'product_id' => $productId,
                'product_name' => $product['name'] ?? '',
                'product_image' => $product['product_image'] ?? '',
                'shop_code' => $shopCode,
                'commission' => $commission,
                'remark' => $data['remark'] ?? '',
                'photo_status' => ($product['is_id_photo'] == 1 && !empty($photoData)) ? '2' : '0',
                'js_type' => $product['js_type'] ?? 2,
                'production_number' => $selectedNumber,
                'order_status' => '0',
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s'),
                'name_count' => Db::name('order')->where('customer_name', $customerName)->count() + 1,
                'id_card_count' => Db::name('order')->where('idcard', $customerIdcard)->count() + 1,
                'phone_count' => Db::name('order')->where('phone', $orderPhone)->count() + 1,
            ];

            $orderData = array_merge($orderData, $photoData);

            $orderId = Db::name('order')->insertGetId($orderData);

            // 生成订单快照
            if ($orderId && $orderData['agent_id']) {
                \app\common\service\CommissionCalculationService::generateOrderSnapshot($orderId, $orderData['agent_id']);
            }

            // 更新代理订单统计
            if ($orderId && $orderData['agent_id']) {
                \app\common\helper\AgentStatsHelper::incrementOrderStats($orderData['agent_id']);
            }

            if (!$orderId) {
                Db::rollback();
                return $this->error('保存本地订单失败');
            }

            Db::commit();

            trace('同系统API订单创建成功: 本地ID=' . $orderId . ', 上游订单号=' . $upOrderNo);

            return json([
                'code' => 1,
                'msg' => '订单提交成功',
                'time' => time(),
                'data' => [
                    'order_id' => $orderId,
                    'order_no' => $localOrderNo,
                    'up_order_no' => $upOrderNo
                ]
            ]);

        } catch (\Exception $e) {
            Db::rollback();
            trace('创建本地订单异常: ' . $e->getMessage());
            return $this->error('创建订单异常：' . $e->getMessage());
        }
    }

    /**
     * 查询订单状态
     */
    public function query()
    {
        $orderNo = input('post.orderNo', '');
        if (empty($orderNo)) {
            return $this->error('订单号不能为空');
        }

        // 查找本地订单
        $order = Db::name('order')
            ->where('order_no', $orderNo)
            ->find();

        if (!$order) {
            return $this->error('订单不存在');
        }

        // 获取配置（通过订单的api_config_id）
        $configId = $order['api_config_id'] ?? 0;
        $config = Config::getConfig($configId);
        if (!$config || empty($config['api_key']) || empty($config['api_secret'])) {
            return $this->error('请先配置同系统API');
        }

        // 调用上游查询接口
        $url = rtrim($config['api_url'], '/') . '/v1/order/query?partner_order_no=' . $orderNo;

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
            return $this->error('查询订单失败：网络连接错误');
        }

        $result = json_decode($response, true);

        if ($httpCode == 200 && isset($result['code']) && $result['code'] == 0) {
            return $this->success('查询成功', $result['data'] ?? []);
        } else {
            $errorMsg = $result['message'] ?? '查询订单失败';
            return $this->error($errorMsg);
        }
    }

    /**
     * 准备图片上传
     */
    private function prepareImageForUpload($imageData, $filename)
    {
        // 如果是base64数据
        if (strpos($imageData, 'base64,') !== false) {
            $imageData = explode('base64,', $imageData)[1];
        }

        // 如果是base64编码
        if (preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $imageData)) {
            $tempFile = tempnam(sys_get_temp_dir(), 'img_');
            file_put_contents($tempFile, base64_decode($imageData));
            return new \CURLFile($tempFile, 'image/jpeg', $filename);
        }

        // 如果是URL
        if (filter_var($imageData, FILTER_VALIDATE_URL)) {
            return $imageData;
        }

        // 如果是本地文件路径
        if (file_exists($imageData)) {
            return new \CURLFile($imageData, 'image/jpeg', $filename);
        }

        return $imageData;
    }
}
