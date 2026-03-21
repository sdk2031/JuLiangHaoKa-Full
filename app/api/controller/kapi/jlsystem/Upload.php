<?php
namespace app\api\controller\kapi\jlsystem;

use think\facade\Db;
use app\common\helper\PluginHelper;

/**
 * 同系统API照片上传/重传
 */
class Upload
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
     * 照片重传
     */
    public function reupload()
    {
        $data = input('post.');

        $orderNo = $data['order_no'] ?? '';
        if (empty($orderNo)) {
            return $this->error('订单号不能为空');
        }

        // 查找本地订单
        $order = Db::name('order')
            ->where('order_no', $orderNo)
            ->where('api_name', '同系统')
            ->find();

        if (!$order) {
            return $this->error('订单不存在');
        }

        // 检查订单状态是否允许重传
        if ($order['order_status'] != '3') {
            return $this->error('当前订单状态不允许重传照片');
        }

        // 获取上游订单号
        $upOrderNo = $order['up_order_no'] ?? '';
        if (empty($upOrderNo)) {
            return $this->error('缺少上游订单号');
        }

        $config = Config::getConfig();
        if (!$config || empty($config['api_key']) || empty($config['api_secret'])) {
            return $this->error('请先配置同系统API');
        }

        // 准备上传数据
        $postFields = ['order_no' => $upOrderNo];

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

        // 检查是否有照片
        if (count($postFields) <= 1) {
            return $this->error('请至少上传一张照片');
        }

        try {
            // 调用上游照片重传接口
            $url = rtrim($config['api_url'], '/') . '/v1/upload/photo';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Agent-Id: ' . $config['api_key'],
                'API-Key: ' . $config['api_secret']
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response === false) {
                return $this->error('照片上传失败：网络连接错误');
            }

            $result = json_decode($response, true);

            if ($httpCode == 200 && isset($result['code']) && $result['code'] == 0) {
                // 更新本地订单照片状态
                $updateData = [
                    'photo_status' => '4', // 已重新上传
                    'update_time' => date('Y-m-d H:i:s')
                ];

                // 保存照片到本地
                if (!empty($data['id_card_front'])) {
                    $updateData['id_card_front'] = \app\common\service\ImageService::saveImageAsPng($data['id_card_front'], 'id_card_front', 'jlsystem') ?: $data['id_card_front'];
                }
                if (!empty($data['id_card_back'])) {
                    $updateData['id_card_back'] = \app\common\service\ImageService::saveImageAsPng($data['id_card_back'], 'id_card_back', 'jlsystem') ?: $data['id_card_back'];
                }
                if (!empty($data['id_card_face'])) {
                    $updateData['id_card_face'] = \app\common\service\ImageService::saveImageAsPng($data['id_card_face'], 'id_card_face', 'jlsystem') ?: $data['id_card_face'];
                }
                if (!empty($data['id_card_four'])) {
                    $updateData['id_card_four'] = \app\common\service\ImageService::saveImageAsPng($data['id_card_four'], 'id_card_four', 'jlsystem') ?: $data['id_card_four'];
                }

                // 更新备注
                $updateData['remark'] = \app\common\helper\OrderRemarkHelper::append(
                    $order['remark'] ?? '',
                    '照片已重新上传'
                );

                Db::name('order')->where('id', $order['id'])->update($updateData);

                return $this->success('照片上传成功');
            } else {
                $errorMsg = $result['message'] ?? '照片上传失败';
                return $this->error($errorMsg);
            }

        } catch (\Exception $e) {
            return $this->error('照片上传异常：' . $e->getMessage());
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

    /**
     * 照片重传（别名方法）
     */
    public function index()
    {
        return $this->reupload();
    }
}
