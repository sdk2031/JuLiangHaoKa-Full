<?php
namespace app\api\controller\kapi\jlsystem;

use think\facade\Db;
use app\common\helper\PluginHelper;

/**
 * 同系统API产品管理
 * 支持多账号配置
 */
class Product
{
    public function __construct()
    {
        PluginHelper::check('jlsystem');
    }

    protected function success($msg = '操作成功', $data = [], $code = 0)
    {
        return json(['code' => $code, 'msg' => $msg, 'data' => $data, 'time' => time()]);
    }

    protected function error($msg = '操作失败', $data = [], $code = 1)
    {
        return json(['code' => $code, 'msg' => $msg, 'data' => $data, 'time' => time()]);
    }

    /**
     * 获取配置ID
     */
    private function getConfigId()
    {
        return input('config_id', input('post.config_id', input('get.config_id', 0)));
    }

    /**
     * 获取远程产品列表
     */
    public function index()
    {
        $configId = $this->getConfigId();
        $config = Config::getConfig($configId);
        if (!$config || empty($config['api_key']) || empty($config['api_secret'])) {
            return $this->error('请先配置同系统API');
        }

        try {
            $products = $this->fetchRemoteProducts($config);
            return $this->success('获取成功', $products);
        } catch (\Exception $e) {
            return $this->error('获取产品列表异常：' . $e->getMessage());
        }
    }

    /**
     * 全量同步产品
     */
    public function sync()
    {
        $configId = $this->getConfigId();
        $config = Config::getConfig($configId);
        if (!$config || empty($config['api_key']) || empty($config['api_secret'])) {
            return $this->error('请先配置同系统API');
        }

        try {
            $products = $this->fetchRemoteProducts($config);
            $result = $this->syncProducts($products, $config, false);
            return $this->success($result['message']);
        } catch (\think\exception\HttpResponseException $e) {
            throw $e;
        } catch (\Exception $e) {
            return $this->error('同步异常：' . $e->getMessage());
        }
    }

    /**
     * 轻量同步（保护字段不覆盖）
     */
    public function lightSync()
    {
        $configId = $this->getConfigId();
        $config = Config::getConfig($configId);
        if (!$config || empty($config['api_key']) || empty($config['api_secret'])) {
            return $this->error('请先配置同系统API');
        }

        try {
            $products = $this->fetchRemoteProducts($config);
            $result = $this->syncProducts($products, $config, true);
            return $this->success($result['message']);
        } catch (\think\exception\HttpResponseException $e) {
            throw $e;
        } catch (\Exception $e) {
            return $this->error('轻量同步异常：' . $e->getMessage());
        }
    }

    /**
     * 单品上架
     */
    public function addSingleProduct()
    {
        $productNumber = input('product_number', input('product_id', input('goods_id', '')));
        if (empty($productNumber)) {
            return $this->error('产品编号不能为空');
        }

        $configId = $this->getConfigId();
        $config = Config::getConfig($configId);
        if (!$config || empty($config['api_key']) || empty($config['api_secret'])) {
            return $this->error('请先配置同系统API');
        }

        try {
            $products = $this->fetchRemoteProducts($config, $productNumber);
            if (empty($products)) {
                return $this->error('未找到该产品');
            }
            $result = $this->syncProducts($products, $config, true, false);
            return $this->success($result['message']);
        } catch (\Exception $e) {
            return $this->error('单品上架异常：' . $e->getMessage());
        }
    }

    /**
     * 已上架同步（定时任务，只更新已上架产品）
     */
    public function autoSyncOnlineProducts()
    {
        $configId = $this->getConfigId();
        $config = Config::getConfig($configId);
        if (!$config || empty($config['api_key']) || empty($config['api_secret'])) {
            return $this->error('同系统API配置不完整');
        }

        try {
            $apiName = Config::getApiName($config);
            
            // 获取本地已上架的产品编号
            $onlineProducts = Db::name('product')
                ->where('api_config_id', $config['id'])
                ->where('status', 1)
                ->column('number');

            if (empty($onlineProducts)) {
                return $this->success('没有已上架的产品需要同步');
            }

            $products = $this->fetchRemoteProducts($config);
            
            // 只保留本地已上架的产品
            $filteredProducts = array_filter($products, function($p) use ($onlineProducts) {
                return in_array($p['id'], $onlineProducts);
            });

            $result = $this->syncProducts($filteredProducts, $config, true, true);
            return $this->success('已上架' . $result['message']);
        } catch (\Exception $e) {
            return $this->error('已上架同步异常：' . $e->getMessage());
        }
    }

    /**
     * 从远程获取产品列表
     */
    private function fetchRemoteProducts($config, $productId = null)
    {
        $allProducts = [];
        $page = 1;
        $limit = 100;

        do {
            $url = rtrim($config['api_url'], '/') . '/v1/product/list';
            $params = ['page' => $page, 'limit' => $limit];
            if ($productId) {
                $params['product_id'] = $productId;
            }
            $url .= '?' . http_build_query($params);

            // 调试日志
            trace('[同系统API] 请求URL: ' . $url, 'info');
            trace('[同系统API] Agent-Id: ' . $config['api_key'] . ', API-Key: ' . substr($config['api_secret'], 0, 8) . '...', 'info');

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Agent-Id: ' . $config['api_key'],
                'API-Key: ' . $config['api_secret']
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            // 调试日志
            trace('[同系统API] HTTP状态码: ' . $httpCode . ', 响应: ' . mb_substr($response, 0, 500), 'info');
            if ($curlError) {
                trace('[同系统API] CURL错误: ' . $curlError, 'error');
            }

            if ($response === false || $httpCode != 200) {
                // 返回更详细的错误信息
                $errorDetail = "URL: {$url}, HTTP状态码: {$httpCode}";
                if ($curlError) {
                    $errorDetail .= ", CURL错误: {$curlError}";
                }
                if ($response !== false) {
                    $errorDetail .= ", 响应内容: " . mb_substr($response, 0, 200);
                }
                throw new \Exception('获取产品列表失败，' . $errorDetail);
            }

            $result = json_decode($response, true);
            if (!isset($result['code']) || $result['code'] != 0) {
                throw new \Exception($result['message'] ?? $result['msg'] ?? '获取产品列表失败');
            }

            $list = $result['data']['list'] ?? [];
            $allProducts = array_merge($allProducts, $list);

            $total = $result['data']['total'] ?? 0;
            $page++;

            // 如果是单品查询或已获取全部，退出循环
            if ($productId || count($allProducts) >= $total) {
                break;
            }
        } while (!empty($list));

        return $allProducts;
    }

    /**
     * 同步产品到本地数据库
     */
    private function syncProducts($products, $config, $lightMode = false, $handleOffline = true)
    {
        $syncCount = 0;
        $downCount = 0;
        $syncedNumbers = [];
        
        $configId = $config['id'];
        $apiName = Config::getApiName($config);

        // 获取现有产品（按api_config_id查询）
        $existingProducts = Db::name('product')
            ->where('api_config_id', $configId)
            ->column('id', 'number');

        foreach ($products as $product) {
            $productNumber = (string)($product['id'] ?? '');
            if (empty($productNumber)) continue;

            $syncedNumbers[] = $productNumber;

            // 构建产品数据（字段完全一致，直接映射）
            $productData = [
                'api_name' => $apiName,
                'api_config_id' => $configId,
                'number' => $productNumber,
                'name' => $product['name'] ?? '',
                'yys' => $product['yys'] ?? '',
                'status' => ($product['status'] ?? 0) == 1 ? 1 : 0,
                'yuezu' => floatval($product['yuezu'] ?? 0),
                'commission' => floatval($product['commission'] ?? 0),
                'selectNumber' => intval($product['selectNumber'] ?? 0),
                'is_id_photo' => intval($product['is_id_photo'] ?? 0),
                'is_four_photo' => intval($product['is_four_photo'] ?? 0),
                'four_photo_title' => $product['four_photo_title'] ?? '',
                'four_photo' => $product['four_photo'] ?? '',
                'flow' => intval($product['flow'] ?? 0),
                'call' => intval($product['call'] ?? 0),
                'sms' => intval($product['sms'] ?? 0),
                'first_chongzhi' => intval($product['first_chongzhi'] ?? 0),
                'age' => $product['age'] ?? '',
                'jinfa' => $product['jinfa'] ?? '',
                'kefa' => $product['kefa'] ?? '待更新',
                'guishudi' => $product['guishudi'] ?? '待更新',
                'tags' => $product['tags'] ?? '',
                'js_type' => intval($product['js_type'] ?? 2),
                'js_require' => $product['js_require'] ?? '',
                'rule' => $product['rule'] ?? '',
                'peisong' => $product['peisong'] ?? '',
                'kaika' => $product['kaika'] ?? '',
                'heyue' => $product['heyue'] ?? '',
                'mark' => $product['mark'] ?? '',
                'update_time' => date('Y-m-d H:i:s')
            ];

            // 处理图片
            if (!$lightMode) {
                $productData['product_image'] = $product['product_image'] ?? '';
                $detailImages = $product['detail_images'] ?? [];
                $productData['detail_images'] = is_array($detailImages) ? json_encode($detailImages) : $detailImages;
            }

            // 按api_config_id和number查找现有产品
            $existProduct = Db::name('product')->where([
                'api_config_id' => $configId,
                'number' => $productNumber
            ])->find();

            if ($existProduct) {
                // 轻量模式保护字段
                if ($lightMode) {
                    unset($productData['name'], $productData['product_image'], $productData['detail_images'], $productData['commission']);
                }
                Db::name('product')->where('id', $existProduct['id'])->update($productData);
            } else {
                $productData['create_time'] = date('Y-m-d H:i:s');
                if (!$lightMode) {
                    $detailImages = $product['detail_images'] ?? [];
                    $productData['detail_images'] = is_array($detailImages) ? json_encode($detailImages) : $detailImages;
                }
                Db::name('product')->insert($productData);
            }
            $syncCount++;
        }

        // 处理下架
        if ($handleOffline) {
            $missingNumbers = array_diff(array_keys($existingProducts), $syncedNumbers);
            if (!empty($missingNumbers)) {
                $missingIds = array_map(function($n) use ($existingProducts) {
                    return $existingProducts[$n];
                }, $missingNumbers);

                $downCount = Db::name('product')
                    ->where('id', 'in', $missingIds)
                    ->update(['status' => 0, 'update_time' => date('Y-m-d H:i:s')]);
            }
        }

        $message = "同步成功，共处理 {$syncCount} 个产品";
        if ($downCount > 0) {
            $message .= "，下架 {$downCount} 个不存在的产品";
        }

        return ['success' => true, 'message' => $message, 'sync_count' => $syncCount, 'down_count' => $downCount];
    }
}
