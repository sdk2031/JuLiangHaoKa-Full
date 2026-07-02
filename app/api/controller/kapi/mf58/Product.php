<?php
namespace app\api\controller\kapi\mf58;

use app\api\controller\kapi\mf58\common\MF58Api;
use app\api\controller\kapi\message;
use think\facade\Db;
use think\Request;
use app\common\service\ProductCategoryService;
use app\common\helper\PluginHelper;

/**
 * 58秒返套餐管理
 */
class Product
{

    use message;

    /**
     * Request实例
     * @var Request
     */
    protected $request;

    /**
     * 配置表名
     * @var string
     */
    protected $configTable = 'config_api';
    
    /**
     * API类型标识
     * @var string
     */
    protected $apiType = 'mf58';
    
    /**
     * 产品表名
     * @var string
     */
    protected $productTable = 'product';
    
    /**
     * 初始化
     */
    public function __construct()
    {
        PluginHelper::check('mf58');
        
        $this->request = request();
    }

    /**
     * API管理页获取上游商品列表（只读，不写入本地商品）
     */
    public function upstreamProducts()
    {
        $configId = intval(input('config_id', 0));
        if ($configId > 0) {
            $config = Db::name($this->configTable)
                ->where('id', $configId)
                ->where('api_type', $this->apiType)
                ->find();
            if ($config) {
                $config = [
                    'api_key' => $config['api_key'] ?? '',
                    'api_url' => $config['api_url'] ?? 'http://sdhk.xlxiot.cn/api',
                    'status' => $config['status'] ?? 0,
                ];
            }
        } else {
            $config = $this->getConfig();
        }

        if (empty($config['api_key']) || empty($config['api_url'])) {
            return json(['code' => 1, 'msg' => 'API配置不完整', 'data' => ['list' => [], 'total' => 0]]);
        }
        if (empty($config['status'])) {
            return json(['code' => 1, 'msg' => 'API未启用', 'data' => ['list' => [], 'total' => 0]]);
        }

        $api = new MF58Api($config);
        $result = $api->getPackageList();
        if (!isset($result['code']) || intval($result['code']) !== 0) {
            return json([
                'code' => 1,
                'msg' => '获取商品列表失败：' . ($result['msg'] ?? '未知错误'),
                'data' => ['list' => [], 'total' => 0],
            ]);
        }

        $list = isset($result['data']) && is_array($result['data']) ? $result['data'] : [];
        foreach ($list as $index => $package) {
            if (!is_array($package)) {
                continue;
            }
            if (!isset($package['status']) || trim((string)$package['status']) === '') {
                $list[$index]['status'] = 1;
            }
        }
        return json([
            'code' => 0,
            'msg' => '获取成功',
            'data' => [
                'list' => $list,
                'total' => count($list),
            ],
        ]);
    }

    
    /**
     * 自动同步商品 - 用于宝塔定时任务
     * 接受参数：security_key 系统安全密钥
     * 使用示例：http://domain.com/api/kapi.mf58.product/autoSync?security_key=您的密钥
     */
    public function autoSync()
    {
        // 获取安全密钥
        $securityKey = $this->request->param('security_key', '');
        if (empty($securityKey)) {
            $securityKey = $this->request->post('security_key', '');
        }
        
        // 记录所有请求参数，便于调试
        
        // 从数据库获取安全密钥
        $configSecurityKey = $this->getSecurityKey();
        
        if (empty($configSecurityKey)) {
            $this->error('系统未配置安全密钥');
        }
        
        // 标记是否验证通过
        $keyMatched = false;
        
        // 1. 首先尝试完全匹配
        if (trim($securityKey) === trim($configSecurityKey)) {
            $keyMatched = true;
        }
        // 2. 如果不匹配，尝试忽略大小写匹配
        else if (strtolower(trim($securityKey)) === strtolower(trim($configSecurityKey))) {
            $keyMatched = true;
        }
        
        // 记录密钥验证结果
        
        // 如果密钥不匹配，返回错误
        if (!$keyMatched) {
            // 记录验证失败日志
            $this->error('安全密钥无效或不匹配');
        }
        
        // 密钥验证通过，调用同步方法
        
        // 调用同步方法
        return $this->sync();
    }

    /**
     * 轻量自动同步商品 - 用于宝塔定时任务
     * 接受参数：security_key 系统安全密钥
     * 使用示例：http://domain.com/api/kapi.mf58.product/autoLightSync?security_key=您的密钥
     */
    public function autoLightSync()
    {
        // 获取安全密钥
        $securityKey = $this->request->param('security_key', '');
        if (empty($securityKey)) {
            $securityKey = $this->request->post('security_key', '');
        }

        // 记录所有请求参数，便于调试

        // 从数据库获取安全密钥
        $configSecurityKey = $this->getSecurityKey();

        if (empty($configSecurityKey)) {
            $this->error('系统未配置安全密钥');
        }

        // 标记是否验证通过
        $keyMatched = false;

        // 1. 首先尝试完全匹配
        if (trim($securityKey) === trim($configSecurityKey)) {
            $keyMatched = true;
        }
        // 2. 如果不匹配，尝试忽略大小写匹配
        else if (strtolower(trim($securityKey)) === strtolower(trim($configSecurityKey))) {
            $keyMatched = true;
        }

        // 记录密钥验证结果

        // 如果密钥不匹配，返回错误
        if (!$keyMatched) {
            // 记录验证失败日志
            $this->error('安全密钥无效或不匹配');
        }

        // 密钥验证通过，调用轻量同步方法

        // 调用轻量同步方法
        return $this->lightSync();
    }
    
    /**
     * 获取安全密钥
     * @return string
     */
    protected function getSecurityKey()
    {
        // 尝试从多个可能的表中查找安全密钥，优先从ba_config表查找
        $securityKey = '';
        $tables = ['config', 'ba_config', 'config_api', 'system_config', 'settings'];

        foreach ($tables as $table) {
            try {
                $result = Db::name($table)->where('name', 'security_key')->find();
                if ($result && !empty($result['value'])) {
                    $securityKey = $result['value'];
                    if (function_exists('trace')) {
                    }
                    break;
                }
            } catch (\Throwable $e) {
                // 表不存在或其他错误，继续检查下一个表
                if (function_exists('trace')) {
                }
                continue;
            }
        }

        if (empty($securityKey)) {
        }

        return $securityKey;
    }
    
    /**
     * 获取套餐列表
     */
    public function index()
    {
        $page = $this->request->param('page/d', 1);
        $limit = $this->request->param('limit/d', 10);
        $status = $this->request->param('status');
        
        $where = [
            ['api_name', '=', '58秒返'] // 只查询58秒返的产品
        ];
        
        if ($status !== null && $status !== '') {
            $where[] = ['status', '=', (int)$status];
        }
        
        $total = Db::name($this->productTable)->where($where)->count();
        $list = Db::name($this->productTable)
            ->where($where)
            ->page($page, $limit)
            ->order('id', 'desc')
            ->select()
            ->toArray();
        
        $this->success('获取成功', [
            'list' => $list,
            'total' => $total
        ]);
    }
    
    /**
     * 轻量同步套餐 - 不覆盖已修改的产品名称、产品图、详情图
     */
    public function lightSync()
    {
        // 记录请求参数

        // 获取API配置
        $config = $this->getConfig();

        if (empty($config['api_key']) || empty($config['api_url'])) {
            // 即使配置不完整也返回成功
            return json([
                'code' => 0,
                'msg' => '轻量同步成功',
                'data' => null,
                'time' => time()
            ]);
        }

        if (!$config['status']) {
            // 即使API未启用也返回成功
            return json([
                'code' => 0,
                'msg' => '轻量同步成功',
                'data' => null,
                'time' => time()
            ]);
        }

        // 记录错误信息
        $errors = [];
        $successCount = 0;

        try {
            // 初始化API类
            $api = new MF58Api($config);

            // 获取套餐列表
            try {
                $result = $api->getPackageList();

                // 记录返回结果

                // API返回code=0是成功状态
                if ($result['code'] != 0) {
                    return json([
                        'code' => 0,
                        'msg' => '轻量同步成功，但API返回错误',
                        'data' => null,
                        'time' => time()
                    ]);
                }

                // 处理同步
                $packages = $result['data'] ?? [];
                if (empty($packages)) {
                    return json([
                        'code' => 0,
                        'msg' => '轻量同步成功，但没有获取到套餐',
                        'data' => null,
                        'time' => time()
                    ]);
                }

                // 获取首页数据（包含产品图片）
                $productImages = [];
                try {
                    $homeResult = $api->getHomeData();

                    // API返回code=0是成功状态
                    if (isset($homeResult['data']['code']) && $homeResult['data']['code'] == 0) {
                        $homeData = $homeResult['data']['data'] ?? [];
                    } elseif (isset($homeResult['code']) && $homeResult['code'] == 0) {
                        // 直接返回的结构
                        $homeData = $homeResult['data'] ?? [];
                    } else {
                        $homeData = [];
                    }

                    // 构建产品ID与图片的映射
                    if (!empty($homeData)) {
                        foreach ($homeData as $item) {
                            $productId = $item['Type'] ?? $item['GoodId'] ?? 0;
                            $picUrl = $item['PicUrl'] ?? '';
                            if ($productId && $picUrl) {
                                // 添加OSS前缀
                                if (strpos($picUrl, 'http') !== 0) {
                                    $picUrl = 'https://yihaon.oss-cn-beijing.aliyuncs.com/' . $picUrl;
                                }
                                $productImages[$productId] = $picUrl;

                                if (function_exists('trace')) {
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                }

                // 不需要获取每个套餐的可发区信息，直接设置为全国
                $packageProvinces = [];

                // 开始轻量同步
                try {
                    $syncResult = $this->lightSyncPackagesToDb($packages, $productImages, $packageProvinces);
                    $successCount = $syncResult['success_count'] ?? count($packages);
                } catch (\Exception $e) {
                }
            } catch (\Exception $e) {
            }
        } catch (\Exception $e) {
        }

        // 无论如何都返回成功
        return json([
            'code' => 0,
            'msg' => '轻量同步成功，共同步 ' . $successCount . ' 个套餐',
            'data' => [
                'success_count' => $successCount
            ],
            'time' => time()
        ]);
    }

    /**
     * 同步套餐
     */
    public function sync()
    {
        // 记录请求参数

        // 获取API配置
        $config = $this->getConfig();

        if (empty($config['api_key']) || empty($config['api_url'])) {
            // 即使配置不完整也返回成功
            return json([
                'code' => 0,
                'msg' => '同步成功',
                'data' => null,
                'time' => time()
            ]);
        }

        if (!$config['status']) {
            // 即使API未启用也返回成功
            return json([
                'code' => 0,
                'msg' => '同步成功',
                'data' => null,
                'time' => time()
            ]);
        }

        // 记录错误信息
        $errors = [];
        $successCount = 0;

        try {
            // 初始化API类
            $api = new MF58Api($config);

            // 获取套餐列表
            try {
                $result = $api->getPackageList();

                // 记录返回结果

                // API返回code=0是成功状态
                if ($result['code'] != 0) {
                    return json([
                        'code' => 0,
                        'msg' => '同步成功，但API返回错误',
                        'data' => null,
                        'time' => time()
                    ]);
                }

                // 处理同步
                $packages = $result['data'] ?? [];
                if (empty($packages)) {
                    return json([
                        'code' => 0,
                        'msg' => '同步成功，但没有获取到套餐',
                        'data' => null,
                        'time' => time()
                    ]);
                }

                // 获取首页数据（包含产品图片）
                $productImages = [];
                try {
                    $homeResult = $api->getHomeData();

                    // API返回code=0是成功状态
                    if (isset($homeResult['data']['code']) && $homeResult['data']['code'] == 0) {
                        $homeData = $homeResult['data']['data'] ?? [];
                    } elseif (isset($homeResult['code']) && $homeResult['code'] == 0) {
                        // 直接返回的结构
                        $homeData = $homeResult['data'] ?? [];
                    } else {
                        $homeData = [];
                    }

                    // 构建产品ID与图片的映射
                    if (!empty($homeData)) {
                        foreach ($homeData as $item) {
                            $productId = $item['Type'] ?? $item['GoodId'] ?? 0;
                            $picUrl = $item['PicUrl'] ?? '';
                            if ($productId && $picUrl) {
                                // 添加OSS前缀
                                if (strpos($picUrl, 'http') !== 0) {
                                    $picUrl = 'https://yihaon.oss-cn-beijing.aliyuncs.com/' . $picUrl;
                                }
                                $productImages[$productId] = $picUrl;

                                if (function_exists('trace')) {
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    if (function_exists('trace')) {
                        trace('MF58获取图片数据失败: ' . $e->getMessage(), 'error');
                    }
                }

                if (function_exists('trace')) {
                    trace('MF58图片数据获取结果: 共' . count($productImages) . '个图片', 'info');
                }

                // 不需要获取每个套餐的可发区信息，直接设置为全国
                $packageProvinces = [];

                // 开始同步
                try {
                    $syncResult = $this->syncPackagesToDb($packages, $productImages, $packageProvinces);
                    $successCount = $syncResult['success_count'] ?? count($packages);
                } catch (\Exception $e) {
                }
            } catch (\Exception $e) {
            }
        } catch (\Exception $e) {
        }

        // 无论如何都返回成功
        return json([
            'code' => 0,
            'msg' => '同步成功，共同步 ' . $successCount . ' 个套餐',
            'data' => [
                'success_count' => $successCount
            ],
            'time' => time()
        ]);
    }
    
    /**
     * 上架套餐
     */
    public function up()
    {
        $id = $this->request->param('id');
        if (empty($id)) {
            $this->error('参数错误');
        }
        
        try {
            $product = Db::name($this->productTable)->where('id', $id)->find();
            if (!$product) {
                $this->error('套餐不存在');
            }
            
            Db::name($this->productTable)->where('id', $id)->update([
                'status' => 1,
                'update_time' => date('Y-m-d H:i:s')
            ]);
            
            $this->success('上架成功');
        } catch (\Exception $e) {
            $this->error('上架失败：' . $e->getMessage());
        }
    }
    
    /**
     * 下架套餐
     */
    public function down()
    {
        $id = $this->request->param('id');
        if (empty($id)) {
            $this->error('参数错误');
        }
        
        try {
            $product = Db::name($this->productTable)->where('id', $id)->find();
            if (!$product) {
                $this->error('套餐不存在');
            }
            
            Db::name($this->productTable)->where('id', $id)->update([
                'status' => 0,
                'update_time' => date('Y-m-d H:i:s')
            ]);
            
            $this->success('下架成功');
        } catch (\Exception $e) {
            $this->error('下架失败：' . $e->getMessage());
        }
    }
    
    /**
     * 批量上架
     */
    public function upAll()
    {
        $ids = $this->request->param('ids');
        if (empty($ids)) {
            $this->error('参数错误');
        }
        
        if (!is_array($ids)) {
            $ids = explode(',', $ids);
        }
        
        try {
            Db::name($this->productTable)->whereIn('id', $ids)->update([
                'status' => 1,
                'update_time' => date('Y-m-d H:i:s')
            ]);
            
            $this->success('批量上架成功');
        } catch (\Exception $e) {
            $this->error('批量上架失败：' . $e->getMessage());
        }
    }
    
    /**
     * 批量下架
     */
    public function downAll()
    {
        $ids = $this->request->param('ids');
        if (empty($ids)) {
            $this->error('参数错误');
        }
        
        if (!is_array($ids)) {
            $ids = explode(',', $ids);
        }
        
        try {
            Db::name($this->productTable)->whereIn('id', $ids)->update([
                'status' => 0,
                'update_time' => date('Y-m-d H:i:s')
            ]);
            
            $this->success('批量下架成功');
        } catch (\Exception $e) {
            $this->error('批量下架失败：' . $e->getMessage());
        }
    }
    
    /**
     * 从产品名称中解析运营商
     * @param string $productName 产品名称
     * @return string 运营商名称
     */
    protected function parseOperator($productName)
    {
        if (strpos($productName, '移动') !== false) {
            return '移动';
        } elseif (strpos($productName, '联通') !== false) {
            return '联通';
        } elseif (strpos($productName, '电信') !== false) {
            return '电信';
        } elseif (strpos($productName, '广电') !== false) {
            return '广电';
        }

        return '移动'; // 默认移动
    }

    /**
     * 映射58秒返的IsShowNum到数据库的selectNumber字段
     * @param int $isShowNum 58秒返API返回的IsShowNum值（0、1、2）
     * @return int 数据库selectNumber字段值（0或1）
     */
    protected function mapSelectNumber($isShowNum)
    {
        // 58秒返API: 0=不可选号, 1=支持选号, 2=不可选号
        // 数据库字段: 0=不支持选号, 1=支持选号
        return ($isShowNum == 1) ? 1 : 0;
    }
    
    /**
     * 同步套餐到数据库
     * @param array $packages 套餐数据
     * @param array $productImages 产品图片映射 [产品ID => 图片URL]
     * @param array $packageProvinces 套餐可发区信息 [套餐ID => 可发区信息]
     * @return array 同步结果 ['success_count' => 成功数量, 'errors' => 错误信息数组]
     */
    protected function syncPackagesToDb($packages, $productImages = [], $packageProvinces = [])
    {
        // 获取已有套餐
        try {
            $existProducts = Db::name($this->productTable)
                ->where('api_name', '58秒返')
                ->column('*', 'number');
        } catch (\Exception $e) {
            $existProducts = [];
        }

        $now = date('Y-m-d H:i:s');
        $insertList = [];
        $updateCount = 0;
        $errors = [];

        foreach ($packages as $package) {
            try {
                $packageId = $package['Id'];
                $packageName = $package['Name'];
                $isShowNum = $package['IsShowNum'] ?? 0;

                // 替换套餐名称中的(秒返)为MF
                $packageName = str_replace('(秒返)', 'MF', $packageName);

                // 获取产品图片URL
                $productImage = $productImages[$packageId] ?? '';
                // 添加OSS前缀
                if ($productImage && strpos($productImage, 'http') !== 0) {
                    $productImage = 'https://yihaon.oss-cn-beijing.aliyuncs.com/' . $productImage;
                }

                // 如果已存在，则更新
                if (isset($existProducts[$packageId])) {
                    $product = $existProducts[$packageId];

                    // 更新套餐信息 - 使用新的字段名
                    $updateData = [
                        'name' => $packageName,
                        'selectNumber' => $this->mapSelectNumber($package['IsShowNum'] ?? 0),
                        'yys' => $this->parseOperator($packageName),
                        'status' => 1, // 从API返回的商品都是在售商品，默认上架
                        'jinfa' => '待更新', // 禁发区
                        'update_time' => $now
                    ];

                    // 如果有图片，更新图片
                    if ($productImage) {
                        $updateData['product_image'] = $productImage;
                    }

                    // 如果有可发区信息，更新可发区
                    if (isset($packageProvinces[$packageId])) {
                        $updateData['kefa'] = $packageProvinces[$packageId];
                    }

                    // 处理图片（根据配置选择处理方式）
                    if (!empty($updateData['product_image'])) {
                        try {
                            $imageProcessService = new \app\common\service\ImageProcessService();
                            $originalData = $updateData;
                            $updateData = $imageProcessService->processProductImages($updateData, 'mf58');
                            if (function_exists('trace')) {
                                trace("MF58全量同步图片处理完成: 原始={$originalData['product_image']}, 处理后={$updateData['product_image']}", 'info');
                            }
                        } catch (\Exception $e) {
                            if (function_exists('trace')) {
                                trace('MF58全量同步处理图片异常: ' . $e->getMessage(), 'error');
                            }
                        }
                    }

                    try {
                        Db::name($this->productTable)->where('id', $product['id'])->update($updateData);
                        $updateCount++;
                    } catch (\Exception $e) {
                        if (function_exists('trace')) {
                        }
                    }

                    // 记录已处理
                    unset($existProducts[$packageId]);
                } else {
                    // 本地不存在的商品，从API返回的都是在售商品，直接同步
                    // 先检查是否存在相同number的记录
                    try {
                        $exists = Db::name($this->productTable)
                            ->where('number', $packageId)
                            ->where('api_name', '58秒返')
                            ->find();

                        if ($exists) {
                            // 已存在，更新它 - 使用新的字段名
                            $updateData = [
                                'name' => $packageName,
                                'selectNumber' => $package['IsShowNum'] ?? 0,
                                'yys' => $this->parseOperator($packageName),
                                'status' => 1, // 从API返回的商品都是在售商品，默认上架
                                'jinfa' => '待更新', // 禁发区
                                'update_time' => $now
                            ];

                            // 如果有图片，更新图片
                            if ($productImage) {
                                $updateData['product_image'] = $productImage;
                            }

                            // 如果有可发区信息，更新可发区
                            if (isset($packageProvinces[$packageId])) {
                                $updateData['kefa'] = $packageProvinces[$packageId];
                            }

                            Db::name($this->productTable)->where('id', $exists['id'])->update($updateData);
                            $updateCount++;
                        } else {
                            // 本地不存在的商品，从API返回的都是在售商品，直接同步
                            // 新增 - 使用新的字段名
                            $insertList[] = [
                                'number' => $packageId,
                                'name' => $packageName,
                                'selectNumber' => $package['IsShowNum'] ?? 0,
                                'status' => 1, // 在售商品设置为上架状态
                                'api_name' => '58秒返',
                                'js_type' => 1, // 秒返
                                'yys' => $this->parseOperator($packageName),
                                'yuezu' => 0, // 使用新字段名：月租价格
                                'flow' => 0, // 使用新字段名：流量
                                'call' => 0, // 使用新字段名：通话
                                'sms' => 0, // 短信
                                'commission' => 0, // 默认佣金0
                                'jinfa' => '待更新', // 禁发区
                                'kefa' => $packageProvinces[$packageId] ?? '全国', // 可发区
                                'product_image' => $productImage, // 产品图片
                                'create_time' => $now,
                                'update_time' => $now
                            ];
                        }
                    } catch (\Exception $e) {
                        if (function_exists('trace')) {
                        }
                    }
                }
            } catch (\Exception $e) {
            }
        }
        
        // 批量插入新套餐 - 使用事务和异常处理
        $insertCount = 0;
        if (!empty($insertList)) {
            try {
                Db::startTrans();
                
                // 分批插入，避免一次插入过多数据
                $batchSize = 100;
                $batches = array_chunk($insertList, $batchSize);
                
                foreach ($batches as $batch) {
                    // 逐条插入，跳过冲突的记录
                    foreach ($batch as $item) {
                        try {
                            Db::name($this->productTable)->insert($item);
                            $insertCount++;
                        } catch (\Exception $e) {
                            // 记录错误但继续处理
                            if (function_exists('trace')) {
                            }
                            
                            // 如果是唯一键冲突，尝试更新
                            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                                try {
                                    $exists = Db::name($this->productTable)
                                        ->where('number', $item['number'])
                                        ->where('api_name', '58秒返')
                                        ->find();
                                    
                                    if ($exists) {
                                        Db::name($this->productTable)->where('id', $exists['id'])->update([
                                            'name' => $item['name'],
                                            'selectNumber' => $item['selectNumber'],
                                            'yys' => $item['yys'],
                                            'status' => 1, // 设置为上架状态
                                            'jinfa' => '待更新', // 禁发区
                                            'kefa' => $item['kefa'],
                                            'product_image' => $item['product_image'],
                                            'update_time' => $now
                                        ]);
                                        $updateCount++;
                                    }
                                } catch (\Exception $e2) {
                                    if (function_exists('trace')) {
                                    }
                                }
                            }
                        }
                    }
                }
                
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
            }
        }
        
        // 处理不在API返回列表中的套餐（下架）
        $downCount = 0;
        if (!empty($existProducts)) {
            $downIds = [];
            foreach ($existProducts as $product) {
                $downIds[] = $product['id'];
            }
            
            if (!empty($downIds)) {
                try {
                    Db::name($this->productTable)
                        ->whereIn('id', $downIds)
                        ->update([
                            'status' => 0, // 设置为下架状态
                            'update_time' => $now
                        ]);
                    
                    $downCount = count($downIds);
                } catch (\Exception $e) {
                }
            }
        }
        
        $successCount = $insertCount + $updateCount + $downCount;
        return [
            'success_count' => $successCount,
            'errors' => $errors,
            'insert_count' => $insertCount,
            'update_count' => $updateCount,
            'down_count' => $downCount
        ];
    }
    
    /**
     * 获取当前配置
     * @return array
     */
    protected function getConfig()
    {
        $config = Db::name($this->configTable)->where('api_type', $this->apiType)->find();
        
        if ($config) {
            // 转换为需要的格式
            return [
                'api_key' => $config['api_key'] ?? '',
                'api_url' => $config['api_url'] ?? 'http://sdhk.xlxiot.cn/api',
                'status' => $config['status'] ?? 0
            ];
        }
        
        return [];
    }
    
    /**
     * 轻量同步套餐到数据库 - 不覆盖已修改的产品名称、产品图、详情图
     * @param array $packages 套餐数据
     * @param array $productImages 产品图片映射 [产品ID => 图片URL]
     * @param array $packageProvinces 套餐可发区信息 [套餐ID => 可发区信息]
     * @return array 同步结果 ['success_count' => 成功数量, 'errors' => 错误信息数组]
     */
    protected function lightSyncPackagesToDb($packages, $productImages = [], $packageProvinces = [])
    {
        // 获取已有套餐
        try {
            $existProducts = Db::name($this->productTable)
                ->where('api_name', '58秒返')
                ->column('*', 'number');
        } catch (\Exception $e) {
            $existProducts = [];
        }

        $now = date('Y-m-d H:i:s');
        $insertList = [];
        $updateCount = 0;
        $errors = [];

        foreach ($packages as $package) {
            try {
                $packageId = $package['Id'];
                $packageName = $package['Name'];
                $isShowNum = $package['IsShowNum'] ?? 0;

                // 替换套餐名称中的(秒返)为MF
                $packageName = str_replace('(秒返)', 'MF', $packageName);

                // 获取产品图片URL
                $productImage = $productImages[$packageId] ?? '';
                // 添加OSS前缀
                if ($productImage && strpos($productImage, 'http') !== 0) {
                    $productImage = 'https://yihaon.oss-cn-beijing.aliyuncs.com/' . $productImage;
                }

                // 如果已存在，则轻量更新
                if (isset($existProducts[$packageId])) {
                    $product = $existProducts[$packageId];

                    // 轻量更新套餐信息 - 除了产品名称、产品图、详情图之外，其他数据都正常覆盖
                    $updateData = [
                        'api_name' => '58秒返',
                        'number' => $packageId,
                        'selectNumber' => $package['IsShowNum'] ?? 0,
                        'yys' => $this->parseOperator($packageName),
                        'status' => 1, // 从API返回的商品都是在售商品，默认上架
                        'js_type' => 1, // 秒返
                        'yuezu' => 0, // 使用新字段名：月租价格
                        'flow' => 0, // 使用新字段名：流量
                        'call' => 0, // 使用新字段名：通话
                        'sms' => 0, // 短信
                        'commission' => 0, // 默认佣金0
                        'jinfa' => '待更新', // 禁发区
                        'update_time' => $now
                    ];

                    // 如果有可发区信息，更新可发区
                    if (isset($packageProvinces[$packageId])) {
                        $updateData['kefa'] = $packageProvinces[$packageId];
                    } else {
                        $updateData['kefa'] = '全国'; // 默认可发区
                    }

                    // 只有当产品名称为空或者是默认值时才更新（保护已修改的产品名称）
                    if (empty($product['name']) || $product['name'] === '默认产品名称' || $product['name'] === '未命名产品') {
                        $updateData['name'] = $packageName;
                    }

                    // 只有当产品图片为空时才更新（保护已设置的产品图片）
                    if (empty($product['product_image']) && $productImage) {
                        $updateData['product_image'] = $productImage;
                    }

                    // 只有当详情图为空时才更新（保护已设置的详情图片）
                    if (empty($product['detail_image']) && empty($product['detail_images'])) {
                        // 如果有详情图数据，可以在这里添加
                        // $updateData['detail_image'] = $detailImage;
                    }

                    // 处理图片（根据配置选择处理方式）
                    if (!empty($updateData['product_image'])) {
                        if (function_exists('trace')) {
                            trace('MF58轻量同步开始处理图片', 'info', [
                                'product_id' => $product['id'],
                                'original_image' => $updateData['product_image']
                            ]);
                        }
                        try {
                            $imageProcessService = new \app\common\service\ImageProcessService();
                            $originalData = $updateData;
                            $updateData = $imageProcessService->processProductImages($updateData, 'mf58');
                            if (function_exists('trace')) {
                                trace('MF58轻量同步图片处理完成', 'info', [
                                    'product_id' => $product['id'],
                                    'original_image' => $originalData['product_image'],
                                    'processed_image' => $updateData['product_image']
                                ]);
                            }
                        } catch (\Exception $e) {
                            if (function_exists('trace')) {
                                trace('MF58轻量同步处理图片异常: ' . $e->getMessage(), 'error');
                            }
                        }
                    }

                    try {
                        Db::name($this->productTable)->where('id', $product['id'])->update($updateData);
                        $updateCount++;

                        if (function_exists('trace')) {
                        }
                    } catch (\Exception $e) {
                        if (function_exists('trace')) {
                        }
                    }

                    // 记录已处理
                    unset($existProducts[$packageId]);
                } else {
                    // 本地不存在的商品，从API返回的都是在售商品，直接同步
                    // 先检查是否存在相同number的记录
                    try {
                        $exists = Db::name($this->productTable)
                            ->where('number', $packageId)
                            ->where('api_name', '58秒返')
                            ->find();

                        if ($exists) {
                            // 已存在，全量更新它 - 覆盖所有字段，不做任何保护
                            $updateData = [
                                'api_name' => '58秒返',
                                'number' => $packageId,
                                'selectNumber' => $package['IsShowNum'] ?? 0,
                                'yys' => $this->parseOperator($packageName),
                                'status' => 1, // 从API返回的商品都是在售商品，默认上架
                                'js_type' => 1, // 秒返
                                'yuezu' => 0, // 月租价格
                                'flow' => 0, // 流量
                                'call' => 0, // 通话
                                'sms' => 0, // 短信
                                'commission' => 0, // 默认佣金0
                                'jinfa' => '待更新', // 禁发区
                                'update_time' => $now
                            ];

                            // 如果有可发区信息，更新可发区（这个字段正常覆盖）
                            if (isset($packageProvinces[$packageId])) {
                                $updateData['kefa'] = $packageProvinces[$packageId];
                            } else {
                                $updateData['kefa'] = '全国'; // 默认可发区
                            }

                            // 全量同步：强制更新产品名称（不保护）
                            $updateData['name'] = $packageName;

                            // 全量同步：强制更新产品图片（不保护）
                            if ($productImage) {
                                $updateData['product_image'] = $productImage;
                            }

                            // 全量同步：如果有详情图数据，强制更新（不保护）
                            // 目前MF58没有详情图数据，暂时不处理

                            // 处理图片（根据配置选择处理方式）
                            if (!empty($updateData['product_image'])) {
                                try {
                                    $imageProcessService = new \app\common\service\ImageProcessService();
                                    $updateData = $imageProcessService->processProductImages($updateData, 'mf58');
                                } catch (\Exception $e) {
                                    if (function_exists('trace')) {
                                        trace('MF58全量同步处理图片异常: ' . $e->getMessage(), 'error');
                                    }
                                }
                            }

                            Db::name($this->productTable)->where('id', $exists['id'])->update($updateData);
                            $updateCount++;

                            if (function_exists('trace')) {
                            }
                        } else {
                            // 本地不存在的商品，从API返回的都是在售商品，直接同步
                            // 新增 - 新产品可以正常添加所有信息
                            $insertList[] = [
                                'number' => $packageId,
                                'name' => $packageName,
                                'selectNumber' => $package['IsShowNum'] ?? 0,
                                'status' => 1, // 设置为上架状态
                                'api_name' => '58秒返',
                                'js_type' => 1, // 秒返
                                'yys' => $this->parseOperator($packageName),
                                'yuezu' => 0, // 使用新字段名：月租价格
                                'flow' => 0, // 使用新字段名：流量
                                'call' => 0, // 使用新字段名：通话
                                'sms' => 0, // 短信
                                'commission' => 0, // 默认佣金0
                                'jinfa' => '待更新', // 禁发区
                                'kefa' => $packageProvinces[$packageId] ?? '全国', // 可发区
                                'product_image' => $productImage, // 产品图片
                                'create_time' => $now,
                                'update_time' => $now
                            ];
                        }
                    } catch (\Exception $e) {
                        if (function_exists('trace')) {
                        }
                    }
                }
            } catch (\Exception $e) {
            }
        }

        // 批量插入新套餐
        $insertCount = 0;
        if (!empty($insertList)) {
            // 处理图片（根据配置选择处理方式）
            try {
                $imageProcessService = new \app\common\service\ImageProcessService();
                foreach ($insertList as &$insertData) {
                    if (!empty($insertData['product_image'])) {
                        $insertData = $imageProcessService->processProductImages($insertData, 'mf58');
                    }
                }
                unset($insertData); // 清除引用
            } catch (\Exception $e) {
                if (function_exists('trace')) {
                    trace('MF58全量同步批量处理图片异常: ' . $e->getMessage(), 'error');
                }
            }

            try {
                Db::startTrans();

                // 分批插入，避免一次插入过多数据
                $batchSize = 100;
                $batches = array_chunk($insertList, $batchSize);

                foreach ($batches as $batch) {
                    // 逐条插入，跳过冲突的记录
                    foreach ($batch as $item) {
                        try {
                            Db::name($this->productTable)->insert($item);
                            $insertCount++;
                        } catch (\Exception $e) {
                            // 记录错误但继续处理
                            if (function_exists('trace')) {
                            }
                        }
                    }
                }

                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
            }
        }

        $successCount = $insertCount + $updateCount;
        return [
            'success_count' => $successCount,
            'errors' => $errors,
            'insert_count' => $insertCount,
            'update_count' => $updateCount
        ];
    }

    /**
     * 单品上架功能
     * 支持参数：
     * - security_key: 安全密钥（用于定时任务调用）
     * - product_number: 产品编号（必需）
     * - name: 产品名称（可选，不传则使用API返回的名称）
     * - yuezu: 产品月租价格（可选，不传则使用API返回的价格）
     * - status: 产品状态（可选，默认为1启用）
     * 使用示例：http://domain.com/api/kapi.mf58.product/addSingleProduct?security_key=您的密钥&product_number=299
     */
    public function addSingleProduct()
    {
        try {
            // 获取安全密钥（如果提供）
            $securityKey = $this->request->param('security_key', '');

            // 如果提供了安全密钥，进行验证
            if (!empty($securityKey)) {
                $systemSecurityKey = config('app.security_key', '');
                if (empty($systemSecurityKey) || $securityKey !== $systemSecurityKey) {
                    $this->error('安全密钥验证失败');
                }
            }

            // 获取产品编号（支持GET和POST两种方式）
            $productNumber = $this->request->param('product_number', '') ?: input('post.product_number', '') ?: ($_POST['product_number'] ?? '');
            if (empty($productNumber)) {
                // 检查是否是来自管理界面的调用
                if (isset($_POST['product_number']) && !isset($_GET['security_key'])) {
                    // 来自管理界面，返回标准JSON格式
                    return json([
                        'code' => 1, // ThinkPHP error格式使用code=1表示失败
                        'msg' => '产品编号不能为空'
                    ]);
                } else {
                    // 来自API调用，使用原有格式
                    $this->error('产品编号不能为空');
                }
            }

            // 获取可选参数
            $customName = $this->request->param('name', '');
            $customYuezu = $this->request->param('yuezu', '');
            $customStatus = $this->request->param('status', 1);
            $categoryId = intval($this->request->param('category_id', input('post.category_id', 0)));

            if (function_exists('trace')) {
                trace('58秒返单品上架开始', 'info', [
                    'product_number' => $productNumber,
                    'custom_name' => $customName,
                    'custom_yuezu' => $customYuezu,
                    'custom_status' => $customStatus
                ]);
            }

            // 获取API配置
            $config = $this->getConfig();

            if (empty($config['api_key']) || empty($config['api_url'])) {
                $this->error('API配置不完整');
            }

            if (!$config['status']) {
                $this->error('API未启用');
            }

            // 初始化API
            $api = new MF58Api($config);

            // 调用58秒返API获取产品信息
            $result = $api->getPackageList();

            if ($result['code'] != 0) {
                $this->error('获取产品列表失败: ' . ($result['msg'] ?? '未知错误'));
            }

            // 查找指定的产品
            $targetProduct = null;
            if (isset($result['data']) && is_array($result['data'])) {
                foreach ($result['data'] as $product) {
                    // 58秒返API返回的产品字段是Id，不是type
                    if (isset($product['Id']) && $product['Id'] == $productNumber) {
                        $targetProduct = $product;
                        break;
                    }
                }
            }

            if (!$targetProduct) {
                // 检查是否是来自管理界面的调用
                if (isset($_POST['product_number']) && !isset($_GET['security_key'])) {
                    // 来自管理界面，返回标准JSON格式
                    return json([
                        'code' => 1, // 使用code=1表示失败，与其他接口保持一致
                        'msg' => '未找到产品编号为 ' . $productNumber . ' 的产品'
                    ]);
                } else {
                    // 来自API调用，使用原有格式
                    $this->error('未找到产品编号为 ' . $productNumber . ' 的产品');
                }
            }

            // 检查产品是否已存在
            $existingProduct = Db::name($this->productTable)
                ->where('number', $productNumber)
                ->where('api_name', '58秒返')
                ->find();

            // 准备产品数据（使用API返回的原始信息，与全量同步保持一致）
            $productName = $targetProduct['Name'] ?? '58秒返产品'; // 使用API返回的原始名称

            // 使用统一的映射函数
            $selectNumber = $this->mapSelectNumber($targetProduct['IsShowNum'] ?? 0);

            // 获取产品图片（简化逻辑，直接拼接URL）
            $productImage = '';
            try {
                $homeResult = $api->getHomeData();

                // API返回code=0是成功状态（修复：应该是$homeResult['code']而不是$homeResult['data']['code']）
                if (isset($homeResult['code']) && $homeResult['code'] == 0) {
                    $homeData = $homeResult['data'] ?? [];

                    foreach ($homeData as $item) {
                        // 获取产品ID和图片URL
                        $productId = $item['Type'] ?? $item['GoodId'] ?? 0;
                        $picUrl = $item['PicUrl'] ?? '';

                        // 找到匹配的产品
                        if ($productId == $productNumber && $picUrl) {
                            // 直接拼接OSS前缀，就像您说的那样
                            $productImage = 'https://yihaon.oss-cn-beijing.aliyuncs.com/' . $picUrl;
                            if (function_exists('trace')) {
                                trace('58秒返单品上架找到产品图片: ' . $productImage, 'info');
                            }
                            break;
                        }
                    }

                    if (empty($productImage) && function_exists('trace')) {
                        trace('58秒返单品上架未找到产品' . $productNumber . '的图片', 'warning');
                    }
                }
            } catch (\Exception $e) {
                if (function_exists('trace')) {
                    trace('58秒返单品上架获取产品图片失败: ' . $e->getMessage(), 'warning');
                }
            }

            // 处理产品名称（与全量同步保持一致）
            $finalProductName = str_replace('(秒返)', 'MF', $productName);

            $productData = [
                'number' => $productNumber,
                'name' => $finalProductName, // 使用处理后的产品名称
                'selectNumber' => $selectNumber, // 正确映射：0=不支持选号, 1=支持选号
                'status' => 1, // 从API返回的商品都是在售商品，默认上架
                'api_name' => '58秒返',
                'js_type' => 1, // 秒返
                'yys' => $this->parseOperator($productName), // 从API原始名称解析运营商
                'yuezu' => 0, // 使用全量同步的默认值
                'flow' => 0, // 流量
                'call' => 0, // 通话
                'sms' => 0, // 短信
                'commission' => 0, // 默认佣金0，与全量同步一致
                'jinfa' => '待更新', // 禁发区，与全量同步一致
                'kefa' => '待更新', // 可发区，与全量同步一致
                'update_time' => date('Y-m-d H:i:s')
            ];
            if ($categoryId > 0) {
                $productData = array_merge($productData, ProductCategoryService::productFieldsForCategory($categoryId));
            }

            // 如果有产品图片，添加到数据中
            if ($productImage) {
                $productData['product_image'] = $productImage;
            }

            // 处理图片（根据配置选择处理方式）
            if (!empty($productData['product_image'])) {
                try {
                    $imageProcessService = new \app\common\service\ImageProcessService();
                    $productData = $imageProcessService->processProductImages($productData, 'mf58');
                } catch (\Exception $e) {
                    if (function_exists('trace')) {
                        trace('MF58单品上架处理图片异常: ' . $e->getMessage(), 'error');
                    }
                }
            }

            if ($existingProduct) {
                // 更新现有产品
                Db::name($this->productTable)
                    ->where('id', $existingProduct['id'])
                    ->update($productData);

                $message = '产品更新成功';
                $productId = $existingProduct['id'];
            } else {
                // 插入新产品
                $productData['create_time'] = date('Y-m-d H:i:s');
                $productId = Db::name($this->productTable)->insertGetId($productData);
                $message = '产品添加成功';
            }

            if (function_exists('trace')) {
                trace('58秒返单品上架完成', 'info', [
                    'product_id' => $productId,
                    'product_number' => $productNumber,
                    'action' => $existingProduct ? 'update' : 'insert'
                ]);
            }

            // 检查是否是来自管理界面的调用
            if (isset($_POST['product_number']) && !isset($_GET['security_key'])) {
                // 来自管理界面，返回标准JSON格式
                if (function_exists('trace')) {
                    trace('58秒返单品上架管理界面返回', 'info', [
                        'message' => $message,
                        'product_data' => $productData
                    ]);
                }
                return json([
                    'code' => 0, // 使用code=0表示成功，与其他接口保持一致
                    'msg' => $message,
                    'data' => [
                        'product_id' => $productId,
                        'product_number' => $productNumber,
                        'name' => $productData['name'],
                        'yuezu' => $productData['yuezu'],
                        'status' => $productData['status']
                    ]
                ]);
            } else {
                // 来自API调用，使用原有格式
                if (function_exists('trace')) {
                    trace('58秒返单品上架API返回', 'info', [
                        'message' => $message,
                        'product_data' => $productData
                    ]);
                }
                $this->success($message, [
                    'product_id' => $productId,
                    'product_number' => $productNumber,
                    'name' => $productData['name'],
                    'yuezu' => $productData['yuezu'],
                    'status' => $productData['status']
                ]);
            }

        } catch (\Exception $e) {
            if (function_exists('trace')) {
                trace('58秒返单品上架异常: ' . $e->getMessage(), 'error');
            }

            // 检查是否是来自管理界面的调用
            if (isset($_POST['product_number']) && !isset($_GET['security_key'])) {
                // 来自管理界面，返回标准JSON格式
                return json([
                    'code' => 0, // ThinkPHP error格式使用code=0表示失败
                    'msg' => '单品上架失败: ' . $e->getMessage()
                ]);
            } else {
                // 来自API调用，使用原有格式
                $this->error('单品上架失败: ' . $e->getMessage());
            }
        }
    }

    /**
     * 已上架商品同步 - 只同步系统中已上架的商品，维护商品资料和状态
     */
    public function syncOnlineProducts()
    {
        try {
            if (function_exists('trace')) {
                trace('58秒返已上架商品同步开始', 'info');
            }

            // 获取API配置
            $config = $this->getConfig();

            if (empty($config['api_key']) || empty($config['api_url'])) {
                if (function_exists('trace')) {
                    trace('58秒返API配置不完整，无法同步', 'warning');
                }
                return json([
                    'code' => 1,
                    'msg' => 'API配置不完整，请先完成配置',
                    'data' => null,
                    'time' => time()
                ]);
            }

            if (!$config['status']) {
                if (function_exists('trace')) {
                    trace('58秒返API未启用，无法同步', 'warning');
                }
                return json([
                    'code' => 1,
                    'msg' => 'API未启用，请先启用',
                    'data' => null,
                    'time' => time()
                ]);
            }

            // 初始化API
            $api = new MF58Api($config);

            // 获取系统中已上架的58秒返商品
            $onlineProducts = Db::name($this->productTable)
                ->where('api_name', '58秒返')
                ->where('status', 1) // 只获取已上架的商品
                ->where('number', '<>', '') // 必须有对接编号
                ->field('id,number,name,status')
                ->select()
                ->toArray();

            if (empty($onlineProducts)) {
                if (function_exists('trace')) {
                    trace('系统中没有已上架的58秒返商品', 'info');
                }
                return json([
                    'code' => 0,
                    'msg' => '系统中没有已上架的58秒返商品需要同步',
                    'data' => null,
                    'time' => time()
                ]);
            }

            if (function_exists('trace')) {
                trace('找到 ' . count($onlineProducts) . ' 个已上架的58秒返商品需要同步', 'info');
            }

            $successCount = 0;
            $offlineCount = 0;
            $updateCount = 0;
            $errors = [];

            // 获取API中的所有商品数据
            $result = $api->getPackageList();

            if ($result['code'] != 0 || !isset($result['data'])) {
                if (function_exists('trace')) {
                    trace('获取API商品列表失败: ' . ($result['msg'] ?? '未知错误'), 'error');
                }
                return json([
                    'code' => 1,
                    'msg' => '获取API商品列表失败: ' . ($result['msg'] ?? '未知错误'),
                    'data' => null,
                    'time' => time()
                ]);
            }

            $apiProducts = $result['data'];

            // 创建API商品的映射表，以商品ID为键
            $apiProductMap = [];
            foreach ($apiProducts as $apiProduct) {
                $apiProductMap[$apiProduct['Id']] = $apiProduct;
            }

            if (function_exists('trace')) {
                trace('API返回 ' . count($apiProducts) . ' 个商品', 'info');
            }

            // 获取首页数据（包含产品图片）
            $productImages = [];
            try {
                $homeResult = $api->getHomeData();

                // API返回code=0是成功状态
                if (isset($homeResult['data']['code']) && $homeResult['data']['code'] == 0) {
                    $homeData = $homeResult['data']['data'] ?? [];
                } elseif (isset($homeResult['code']) && $homeResult['code'] == 0) {
                    // 直接返回的结构
                    $homeData = $homeResult['data'] ?? [];
                } else {
                    $homeData = [];
                }

                // 构建产品ID与图片的映射
                if (!empty($homeData)) {
                    foreach ($homeData as $item) {
                        $productId = $item['Type'] ?? $item['GoodId'] ?? 0;
                        $picUrl = $item['PicUrl'] ?? '';
                        if ($productId && $picUrl) {
                            // 添加OSS前缀
                            if (strpos($picUrl, 'http') !== 0) {
                                $picUrl = 'https://yihaon.oss-cn-beijing.aliyuncs.com/' . $picUrl;
                            }
                            $productImages[$productId] = $picUrl;
                        }
                    }
                }
            } catch (\Exception $e) {
                if (function_exists('trace')) {
                    trace('获取产品图片失败: ' . $e->getMessage(), 'warning');
                }
            }

            // 遍历系统中已上架的商品
            foreach ($onlineProducts as $localProduct) {
                $productId = $localProduct['number'];

                try {

                    // 检查API中是否还有这个商品
                    if (!isset($apiProductMap[$productId])) {
                        // API中没有这个商品，将本地商品下架
                        Db::name($this->productTable)
                            ->where('id', $localProduct['id'])
                            ->update([
                                'status' => 0,
                                'update_time' => date('Y-m-d H:i:s')
                            ]);

                        $offlineCount++;
                        if (function_exists('trace')) {
                            trace("商品 {$productId} 在API中不存在，已自动下架", 'info');
                        }
                        continue;
                    }

                    // API中有这个商品，更新商品信息
                    $apiProduct = $apiProductMap[$productId];

                    // 获取当前商品的完整信息，用于轻量更新判断
                    $existProduct = Db::name($this->productTable)
                        ->where('id', $localProduct['id'])
                        ->find();

                    // 处理套餐名称中的(秒返)为MF
                    $packageName = str_replace('(秒返)', 'MF', $apiProduct['Name']);

                    // 获取产品图片URL
                    $productImage = $productImages[$productId] ?? '';

                    // 准备轻量更新数据 - 应用保护逻辑
                    $updateData = [
                        'selectNumber' => $this->mapSelectNumber($apiProduct['IsShowNum'] ?? 0),
                        'yys' => $this->parseOperator($packageName),
                        'status' => 1, // 从API返回的商品都是在售商品，保持上架状态
                        'js_type' => 1, // 秒返
                        'jinfa' => '待更新', // 禁发区
                        'kefa' => '全国', // 可发区
                        'update_time' => date('Y-m-d H:i:s')
                    ];

                    // 产品名称保护逻辑：如果数据库中为空才更新
                    if (empty($existProduct['name'])) {
                        $updateData['name'] = $packageName;
                    }

                    // 产品首图保护逻辑：如果数据库中为空且API有数据才更新
                    if (empty($existProduct['product_image']) && !empty($productImage)) {
                        $updateData['product_image'] = $productImage;
                    }

                    // 佣金保护逻辑：如果数据库中为空或等于0.00才更新
                    $existingCommission = floatval($existProduct['commission'] ?? 0);
                    if ($existingCommission <= 0) {
                        // 佣金为空或0，保持默认值0（不需要更新）
                    } else {
                        // 佣金大于0，保护不更新
                    }

                    // 处理图片（根据配置选择处理方式）
                    if (!empty($updateData['product_image'])) {
                        if (function_exists('trace')) {
                            trace('MF58已上架商品同步开始处理图片', 'info', [
                                'product_id' => $localProduct['id'],
                                'original_image' => $updateData['product_image']
                            ]);
                        }
                        try {
                            $imageProcessService = new \app\common\service\ImageProcessService();
                            $originalData = $updateData;
                            $updateData = $imageProcessService->processProductImages($updateData, 'mf58');
                            if (function_exists('trace')) {
                                trace('MF58已上架商品同步图片处理完成', 'info', [
                                    'product_id' => $localProduct['id'],
                                    'original_image' => $originalData['product_image'],
                                    'processed_image' => $updateData['product_image']
                                ]);
                            }
                        } catch (\Exception $e) {
                            if (function_exists('trace')) {
                                trace('MF58已上架商品同步处理图片异常: ' . $e->getMessage(), 'error');
                            }
                        }
                    }

                    // 更新商品信息
                    $updateResult = Db::name($this->productTable)
                        ->where('id', $localProduct['id'])
                        ->update($updateData);

                    if ($updateResult !== false) {
                        $updateCount++;
                        $successCount++;

                    } else {
                        $errors[] = "商品 {$productId} 更新失败";
                        if (function_exists('trace')) {
                            trace("商品 {$productId} 更新失败", 'error');
                        }
                    }

                } catch (\Exception $e) {
                    $errors[] = "商品 {$productId} 处理异常: " . $e->getMessage();
                    if (function_exists('trace')) {
                        trace("商品 {$productId} 处理异常: " . $e->getMessage(), 'error');
                    }
                }
            }

            // 返回同步结果
            $message = "已上架商品同步完成";
            if ($updateCount > 0) {
                $message .= "，更新 {$updateCount} 个商品";
            }
            if ($offlineCount > 0) {
                $message .= "，下架 {$offlineCount} 个商品";
            }
            if (count($errors) > 0) {
                $message .= "，其中 " . count($errors) . " 个商品处理异常";
            }

            if (function_exists('trace')) {
                trace("58秒返已上架商品同步完成: 更新={$updateCount}, 下架={$offlineCount}, 异常=" . count($errors), 'info');
            }

            return json([
                'code' => 0,
                'msg' => $message,
                'data' => [
                    'update_count' => $updateCount,
                    'offline_count' => $offlineCount,
                    'error_count' => count($errors),
                    'errors' => $errors
                ],
                'time' => time()
            ]);

        } catch (\Exception $e) {
            if (function_exists('trace')) {
                trace('58秒返已上架商品同步异常: ' . $e->getMessage(), 'error');
            }
            return json([
                'code' => 1,
                'msg' => '已上架商品同步异常: ' . $e->getMessage(),
                'data' => null,
                'time' => time()
            ]);
        }
    }

    /**
     * 自动同步已上架商品 - 用于宝塔定时任务
     * 接受参数：security_key 系统安全密钥
     * 使用示例：http://domain.com/api/kapi.mf58.product/autoSyncOnlineProducts?security_key=您的密钥
     */
    public function autoSyncOnlineProducts()
    {
        // 获取安全密钥
        $securityKey = $this->request->param('security_key', '');
        if (empty($securityKey)) {
            $securityKey = $this->request->post('security_key', '');
        }

        // 记录所有请求参数，便于调试
        if (function_exists('trace')) {
            trace('58秒返接收到autoSyncOnlineProducts请求', 'info', [
                'method' => $this->request->method(),
                'params' => $this->request->param(),
                'security_key' => substr($securityKey, 0, 5) . '****(隐藏)',
            ]);
        }

        // 从数据库获取安全密钥
        $configSecurityKey = $this->getSecurityKey();

        if (empty($configSecurityKey)) {
            if (function_exists('trace')) {
                trace('58秒返未找到安全密钥配置', 'error');
            }
            $this->error('系统未配置安全密钥');
        }

        // 标记是否验证通过
        $keyMatched = false;

        // 1. 首先尝试完全匹配
        if (trim($securityKey) === trim($configSecurityKey)) {
            $keyMatched = true;
            if (function_exists('trace')) {
                trace('58秒返密钥完全匹配成功', 'info');
            }
        }
        // 2. 如果不匹配，尝试忽略大小写匹配
        else if (strtolower(trim($securityKey)) === strtolower(trim($configSecurityKey))) {
            $keyMatched = true;
            if (function_exists('trace')) {
                trace('58秒返密钥忽略大小写匹配成功', 'info');
            }
        }

        // 记录密钥验证结果
        if (function_exists('trace')) {
            trace('58秒返安全密钥验证结果', 'info', [
                'matched' => $keyMatched,
                'provided_length' => strlen($securityKey),
                'config_length' => strlen($configSecurityKey),
            ]);
        }

        if (!$keyMatched) {
            if (function_exists('trace')) {
                trace('58秒返已上架商品同步验证失败', 'warning', [
                    'has_key' => !empty($securityKey)
                ]);
            }
            $this->error('安全密钥无效或不匹配');
        }

        // 密钥验证通过，调用已上架商品同步方法
        if (function_exists('trace')) {
            trace('58秒返开始自动同步已上架商品', 'info', [
                'from' => 'autoSyncOnlineProducts'
            ]);
        }

        // 调用已上架商品同步方法
        return $this->syncOnlineProducts();
    }

    /**
     * 记录日志
     * @param string $message 日志消息
     * @param array $data 日志数据
     */
    protected function log($message, $data = [])
    {
        // 日志功能已禁用
    }
}
