<?php
namespace app\agent\controller;

use app\common\helper\SystemConfig;
use app\common\service\AgentService;
use app\common\service\AgentDomainBrandService;
use app\common\service\EmployeeAgentService;
use think\facade\Db;
use think\facade\View;
use think\facade\Session;

class Index extends Base
{
    /**
     * 批量检查插件状态
     * @param array $pluginKeys
     * @return array
     */
    protected function checkPluginsStatus($pluginKeys)
    {
        $result = [];
        try {
            $enabled = Db::name('plugin_license')
                ->whereIn('plugin_key', $pluginKeys)
                ->where('status', 1)
                ->column('plugin_key');
            foreach ($pluginKeys as $key) {
                $result[$key] = in_array($key, $enabled);
            }
        } catch (\Exception $e) {
            foreach ($pluginKeys as $key) {
                $result[$key] = false;
            }
        }
        return $result;
    }

    // 主框架页面📝
    public function main()
    {
        // 确保Session启动
        if (session_status() == PHP_SESSION_NONE) {
            $sessionPath = app()->getRuntimePath() . 'session';
            if (!is_dir($sessionPath)) {
                mkdir($sessionPath, 0755, true);
            }
            session_save_path($sessionPath);
            session_start();
        }

        // 检查登录状态
        $agentId = Session::get('agent_id');


        if (!$agentId) {
            // 未登录，跳转到登录页面
            return redirect('/agent/login');
        }

        if (!AgentDomainBrandService::canAgentAccessCurrentDomain((int)$agentId)) {
            Session::delete('agent_id');
            Session::delete('agent_username');
            Session::delete('agent_info');
            if (isset($_COOKIE['agent_token'])) {
                setcookie('agent_token', '', time() - 3600, '/');
            }
            return redirect('/agent/login');
        }


        // 获取代理商基本信息用于显示（关联密价等级表、分销等级）
        $distributionMode = strtolower(trim((string) SystemConfig::get('distribution_level_mode', 'legacy')));
        $isFixedDistributionMode = ($distributionMode === 'fixed');
        $agent = Db::table('agents')
            ->alias('a')
            ->leftJoin('secret_price_levels spl', 'a.secret_price_level_id = spl.id')
            ->leftJoin('invite_code ic', 'a.invite_code_id = ic.id')
            ->leftJoin('distribution_level dl', 'a.distribution_level_id = dl.id')
            ->field('a.*, spl.level_name as secret_price_level_name, ic.level_name as agent_level_name, dl.level_name as distribution_level_name')
            ->where('a.id', $agentId)
            ->find();
        if (!$agent) {
            session_destroy();
            return redirect('/agent/login');
        }
        
        // 处理代理等级显示
        if ($agent['parent_id'] == 0) {
            $agent['agent_level_text'] = '平台直属代理';
        } else {
            $agent['agent_level_text'] = $agent['agent_level_name'] ?: '普通代理';
        }

        // 分销等级模式适配：
        // fixed 模式优先显示固定分销等级，legacy 模式沿用邀请码等级
        if ($distributionMode === 'fixed') {
            if (!empty($agent['distribution_level_name'])) {
                $agent['agent_level_text'] = $agent['distribution_level_name'];
            } elseif ((int)$agent['parent_id'] === 0) {
                $agent['agent_level_text'] = '平台直属代理';
            } else {
                $agent['agent_level_text'] = '普通代理';
            }
        }

        $employeeAgentService = new EmployeeAgentService();
        $employeeContext = $employeeAgentService->getEmployeeContext((int)$agentId);
        $agent['is_employee'] = !empty($employeeContext['is_employee']) ? 1 : 0;
        $agent['employee_code'] = $employeeContext['employee_code'] ?? '';
        $agent['employee_group_name'] = $employeeContext['group_name'] ?? '';

        // 安全检查：验证是否通过管理员切换过来的
        $showAdminTab = $this->verifyAdminSwitch();

        // 获取系统配置
        $config = $this->getSystemConfig();

        // 插件显隐控制
        $pluginStatus = $this->checkPluginsStatus(['workorder', 'marketing', 'down_api']);
        $workorderPluginEnabled = $pluginStatus['workorder'];
        $marketingPluginEnabled = $pluginStatus['marketing'];
        $downApiPluginEnabled = $pluginStatus['down_api'];
        $agentCapability = \app\common\service\IdcardService::getAgentCapabilityState((int)$agentId);

        // 店铺摘要信息（顶部导航店铺卡使用）
        $shopInfo = Db::table('agent_shop')->where('agent_id', $agentId)->find();
        if (!$shopInfo) {
            $createResult = AgentService::createShop((int)$agentId);
            if (!empty($createResult['code'])) {
                $shopInfo = Db::table('agent_shop')->where('agent_id', $agentId)->find();
            }
        }
        if ($shopInfo && empty($shopInfo['shop_code'])) {
            $newShopCode = $this->generateUniqueShopCode();
            Db::table('agent_shop')->where('id', $shopInfo['id'])->update([
                'shop_code' => $newShopCode
            ]);
            $shopInfo['shop_code'] = $newShopCode;
        }

        $shopSummary = [
            'shop_name' => $shopInfo['shop_name'] ?? '未设置',
            'shop_code' => $shopInfo['shop_code'] ?? '',
            'theme_color' => $shopInfo['theme_color'] ?? '#1890ff',
            'status_text' => ((int)($shopInfo['status'] ?? 1) === 1) ? '正常' : '异常',
            'shop_code_display' => $shopInfo['shop_code'] ?? '',
            'agent_id' => (int)$agentId,
            'today_visits' => (int)($shopInfo['today_visits'] ?? 0),
            'today_orders' => 0,
            'shop_url' => !empty($shopInfo['shop_code']) ? request()->domain() . '/index/shop/index/shop_code/' . $shopInfo['shop_code'] : ''
        ];

        if (!empty($shopSummary['shop_code'])) {
            $todayStartStr = date('Y-m-d 00:00:00');
            $todayEndStr = date('Y-m-d 23:59:59');
            $shopSummary['today_orders'] = (int)Db::table('order')
                ->where('agent_id', (string)$agentId)
                ->where('create_time', '>=', $todayStartStr)
                ->where('create_time', '<=', $todayEndStr)
                ->count();
        }

        if (!empty($agentCapability['order_submit_required']) && empty($agentCapability['is_verified'])) {
            $shopSummary['status_text'] = '异常';
            $shopSummary['shop_code_display'] = '实名后可用';
        } elseif (!empty($agentCapability['contract_pending_review'])) {
            $shopSummary['status_text'] = '异常';
            $shopSummary['shop_code_display'] = '审核后可用';
        } elseif (!empty($agentCapability['contract_pending_payment'])) {
            $shopSummary['status_text'] = '异常';
            $shopSummary['shop_code_display'] = '签约后可用';
        }

        $upstreamContact = [
            'is_root' => ((int)$agent['parent_id'] === 0),
            'parent_name' => '平台客服',
            'contact_phone' => '',
            'service_qrcode' => ''
        ];
        if ((int)$agent['parent_id'] > 0) {
            $parentAgent = Db::table('agents')->where('id', (int)$agent['parent_id'])->find();
            if ($parentAgent) {
                $parentShop = Db::table('agent_shop')->where('agent_id', (int)$parentAgent['id'])->find();
                $upstreamContact = [
                    'is_root' => false,
                    'parent_name' => $parentAgent['username'] ?: '上游代理',
                    'contact_phone' => $parentShop['contact_phone'] ?? '',
                    'service_qrcode' => $parentShop['service_qrcode'] ?? ''
                ];
            }
        }

        View::assign('agent', $agent);
        View::assign('config', $config);
        View::assign('shopSummary', $shopSummary);
        View::assign('upstreamContact', $upstreamContact);
        View::assign('agentCapability', $agentCapability);
        View::assign('showAdminTab', $showAdminTab);
        View::assign('workorderPluginEnabled', $workorderPluginEnabled);
        View::assign('marketingPluginEnabled', $marketingPluginEnabled);
        View::assign('downApiPluginEnabled', $downApiPluginEnabled);
        return View::fetch('index/main');
    }

    /**
     * 验证是否通过管理员切换过来的
     * @return bool
     */
    private function verifyAdminSwitch()
    {
        // 检查是否有管理员切换标记的cookie
        if (!isset($_COOKIE['admin_login_agent'])) {
            return false;
        }
        
        $cookieValue = $_COOKIE['admin_login_agent'];
        
        // 验证cookie的有效性
        // cookie格式：timestamp|hash
        $parts = explode('|', $cookieValue);
        if (count($parts) !== 2) {
            return false;
        }
        
        $timestamp = intval($parts[0]);
        $hash = $parts[1];
        
        // 检查是否在30分钟内
        if (time() - $timestamp >= 1800) {
            return false;
        }
        
        // 验证hash（使用应用密钥）
        $secretKey = config('app.app_key', 'default_secret_key');
        $expectedHash = md5($timestamp . $secretKey);
        
        if ($hash !== $expectedHash) {
            return false;
        }
        
        return true;
    }

    // 默认首页 - 检查登录状态并跳转到合适页面
    public function index()
    {
        // 确保Session启动
        if (session_status() == PHP_SESSION_NONE) {
            $sessionPath = app()->getRuntimePath() . 'session';
            if (!is_dir($sessionPath)) {
                mkdir($sessionPath, 0755, true);
            }
            session_save_path($sessionPath);
            session_start();
        }

        // 检查登录状态
        $agentId = Session::get('agent_id');

        if (!$agentId) {
            // 未登录，跳转到登录页面
            error_log('Index page - No agent_id found, redirecting to login');
            return redirect('/agent/login');
        }

        error_log('Index page - Agent logged in, redirecting to main');

        // 已登录，跳转到主框架页面
        return redirect('/agent/index/main');
    }

    // 首页内容（在iframe中显示）
    public function home()
    {
        $agentId = $this->getAgentId();

        // 获取代理商信息
        $agent = Session::get('agent_info');
        if (!$agent) {
            // 如果Session中没有完整信息，从数据库获取
            $agent = Db::table('agents')->where('id', $agentId)->find();
            if (!$agent) {
                session_destroy();
                return redirect('/agent/login');
            }
        }

        // 格式化数据（使用balance字段）
        $agent['balance'] = number_format($agent['balance'] ?? 0, 2);
        $agent['total_money'] = number_format($agent['total_money'] ?? 0, 2);
        $agent['create_time_text'] = $agent['create_time'] ? date('Y-m-d H:i:s', $agent['create_time'] / 1000) : '-';
        $agent['last_login_time_text'] = $agent['last_login_time'] ? date('Y-m-d H:i:s', $agent['last_login_time'] / 1000) : '从未登录';
        $agent['verify_time_text'] = $agent['verify_time'] ? date('Y-m-d H:i:s', $agent['verify_time'] / 1000) : '-';
        
        // 代理等级映射
        $levelMap = [
            1 => '一级代理',
            2 => '二级代理',
            3 => '三级代理',
            4 => '四级代理',
            5 => '五级代理'
        ];
        $agent['agent_level_text'] = $levelMap[$agent['agent_level']] ?? '普通代理';
        
        // 实名认证状态
        $agent['is_verified_text'] = $agent['is_verified'] ? '已认证' : '未认证';
        
        // 脱敏处理
        if ($agent['mobile']) {
            // 如果手机号以U开头，说明是系统生成的占位符，显示"未设置"
            if (strpos($agent['mobile'], 'U') === 0) {
                $agent['mobile_masked'] = '未设置';
            } else {
                $agent['mobile_masked'] = substr($agent['mobile'], 0, 3) . '****' . substr($agent['mobile'], -4);
            }
        } else {
            $agent['mobile_masked'] = '-';
        }
        
        if ($agent['id_card']) {
            $agent['id_card_masked'] = substr($agent['id_card'], 0, 6) . '********' . substr($agent['id_card'], -4);
        } else {
            $agent['id_card_masked'] = '-';
        }

        // 获取dashboard数据
        $dashboardData = $this->getDashboardData($agentId);
        $dashboardData['agent_capability'] = \app\common\service\IdcardService::getAgentCapabilityState((int)$agentId);
        $employeeContext = (new EmployeeAgentService())->getEmployeeContext((int)$agentId);
        $agent['is_employee'] = !empty($employeeContext['is_employee']) ? 1 : 0;
        $agent['employee_code'] = $employeeContext['employee_code'] ?? '';
        $agent['employee_group_name'] = $employeeContext['group_name'] ?? '';
        
        View::assign('agent', $agent);
        View::assign('dashboardData', $dashboardData);
        return View::fetch('index/home');
    }

 

    // 修改密码
    public function changePassword()
    {
        $agentId = $this->getAgentId();

        if (request()->isPost()) {
            $oldPassword = input('old_password', '');
            $newPassword = input('new_password', '');
            $confirmPassword = input('confirm_password', '');

            // 数据验证
            if (empty($oldPassword)) {
                return json(['code' => 0, 'msg' => '请输入原密码']);
            }
            if (empty($newPassword)) {
                return json(['code' => 0, 'msg' => '请输入新密码']);
            }
            if ($newPassword !== $confirmPassword) {
                return json(['code' => 0, 'msg' => '两次输入的新密码不一致']);
            }
            if (strlen($newPassword) < 6) {
                return json(['code' => 0, 'msg' => '新密码长度不能少于6位']);
            }

            try {
                // 获取当前用户信息
                $agent = Db::table('agents')->where('id', $agentId)->find();
                if (!$agent) {
                    return json(['code' => 0, 'msg' => '用户不存在']);
                }

                // 验证原密码
                $inputOldPassword = md5($oldPassword . $agent['salt']);
                if ($inputOldPassword !== $agent['password']) {
                    return json(['code' => 0, 'msg' => '原密码错误']);
                }

                // 更新密码
                $salt = substr(md5(time()), 0, 10);
                $hashedNewPassword = md5($newPassword . $salt);

                Db::table('agents')
                    ->where('id', $agentId)
                    ->update([
                        'password' => $hashedNewPassword,
                        'salt' => $salt
                    ]);

                return json(['code' => 1, 'msg' => '密码修改成功']);

            } catch (\Exception $e) {
                return json(['code' => 0, 'msg' => '修改失败：' . $e->getMessage()]);
            }
        }

        return json(['code' => 0, 'msg' => '请求方式错误']);
    }

    // 获取当前余额
    public function getBalance()
    {
        $agentId = $this->getAgentId();

        try {
            $agent = Db::name('agents')->where('id', $agentId)->find();
            if (!$agent) {
                return json(['code' => 0, 'msg' => '代理商不存在']);
            }

            return json([
                'code' => 1,
                'msg' => '获取成功',
                'data' => [
                    'balance' => $agent['balance'] ?? '0.00'
                ]
            ]);

        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '获取失败：' . $e->getMessage()]);
        }
    }

    /**
     * 获取系统配置
     */
    private function getSystemConfig()
    {
        try {
            // 获取系统配置
            $configs = Db::table('system_config')->select();
            $result = [];

            foreach ($configs as $config) {
                $value = $config['config_value'];

                // 尝试解析JSON
                if (is_string($value) && (strpos($value, '{') === 0 || strpos($value, '[') === 0)) {
                    $decoded = json_decode($value, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $value = $decoded;
                    }
                }

                $result[$config['config_key']] = $value;
            }

            return AgentDomainBrandService::applyBrandConfig($result);
        } catch (\Exception $e) {
            // 返回默认配置
            return AgentDomainBrandService::applyBrandConfig([
                'site_name' => '流量卡管理系统',
                'site_copyright' => '© 2024 流量卡管理系统'
            ]);
        }
    }

    /**
     * 仪表板数据
     */
    public function dashboard()
    {
        $agentId = $this->getAgentId();

        // 获取dashboard数据
        $dashboardData = $this->getDashboardData($agentId);
        
        // 分配数据到视图
        View::assign('dashboardData', $dashboardData);
        
        return View::fetch('index/home');
    }

    /**
     * 获取dashboard数据
     */
    private function getDashboardData($agentId)
    {
        $pluginStatus = $this->checkPluginsStatus(['workorder', 'marketing']);
        $workorderPluginEnabled = !empty($pluginStatus['workorder']);
        $marketingPluginEnabled = !empty($pluginStatus['marketing']);
        $employeeAgentService = new EmployeeAgentService();
        $employeeContext = $employeeAgentService->getEmployeeContext((int)$agentId);
        $employeeTeamStats = !empty($employeeContext['is_employee']) ? $employeeAgentService->getTeamStats((int)$agentId) : [];

        // 将agent_id转换为字符串，因为order表中agent_id是varchar类型
        $agentIdStr = (string)$agentId;

        // 获取代理信息（包含统计字段）
        $agentInfo = Db::table('agents')->where('id', $agentId)->find();

        // 获取时间字符串（数据库中create_time是字符串格式）
        $last7DaysStr = date('Y-m-d 00:00:00', strtotime('-7 days'));
        $todayEndStr = date('Y-m-d 23:59:59');
        $monthStartStr = date('Y-m-01 00:00:00');
        $monthEndStr = date('Y-m-t 23:59:59');

        // 获取代理的店铺信息（自动修复：无店铺则创建，空shop_code则补齐）
        $shopInfo = Db::table('agent_shop')->where('agent_id', $agentId)->find();
        if (!$shopInfo) {
            $createResult = AgentService::createShop((int)$agentId);
            if (!empty($createResult['code'])) {
                $shopInfo = Db::table('agent_shop')->where('agent_id', $agentId)->find();
            }
        }
        if ($shopInfo && empty($shopInfo['shop_code'])) {
            $newShopCode = $this->generateUniqueShopCode();
            Db::table('agent_shop')->where('id', $shopInfo['id'])->update([
                'shop_code' => $newShopCode
            ]);
            $shopInfo['shop_code'] = $newShopCode;
        }
        $shopId = $shopInfo ? $shopInfo['id'] : 0;

        // 1. 访问量数据 - 从agent_shop表获取
        $todayVisits = $shopInfo ? $shopInfo['today_visits'] : 0;
        $totalVisits = $shopInfo ? $shopInfo['total_visits'] : 0;

        // 2. 今日订单量
        $todayStartStr = date('Y-m-d 00:00:00');
        $todayEndStr = date('Y-m-d 23:59:59');
        
        $todayOrderCount = Db::table('order')
            ->where('agent_id', $agentIdStr)
            ->where('create_time', '>=', $todayStartStr)
            ->where('create_time', '<=', $todayEndStr)
            ->count();

        // 最近7天佣金金额
        $todayOrderAmount = Db::table('order')
            ->where('agent_id', $agentIdStr)
            ->where('create_time', '>=', $last7DaysStr)
            ->where('create_time', '<=', $todayEndStr)
            ->sum('commission');

        // 订单状态统计（当前代理）
        $orderStatusRaw = Db::table('order')
            ->where('agent_id', $agentIdStr)
            ->field('order_status, COUNT(*) as cnt')
            ->group('order_status')
            ->select()
            ->toArray();

        $orderStatusStats = [
            0 => 0, // 已提交
            1 => 0, // 待发货
            2 => 0, // 已发货
            3 => 0, // 待传照片
            4 => 0, // 已激活
            5 => 0, // 已结算
            6 => 0, // 结算失败
            7 => 0  // 审核失败
        ];
        foreach ($orderStatusRaw as $row) {
            $status = (int)($row['order_status'] ?? -1);
            if (array_key_exists($status, $orderStatusStats)) {
                $orderStatusStats[$status] = (int)($row['cnt'] ?? 0);
            }
        }
        $totalStatusOrders = array_sum($orderStatusStats);
        $settledRatio = $totalStatusOrders > 0 ? round(($orderStatusStats[5] / $totalStatusOrders) * 100) : 0;

        // 顶部概览数据
        $estimatedCommissionTotal = (float)Db::table('order')
            ->where('agent_id', $agentIdStr)
            ->whereNotIn('order_status', [6, 7])
            ->sum('commission');
        $refundOrderCount = (int)Db::table('order')
            ->where('agent_id', $agentIdStr)
            ->where(function ($query) {
                $query->where('pay_status', 2)
                    ->whereOr(function ($subQuery) {
                        $subQuery->whereNotNull('refund_time')
                            ->where('refund_time', '<>', '')
                            ->where('refund_time', '<>', '0000-00-00 00:00:00');
                    });
            })
            ->count();

        $pendingPhotoCount = (int)Db::table('order')
            ->where('agent_id', $agentIdStr)
            ->where('order_status', 3)
            ->where('create_time', '>=', $last7DaysStr)
            ->where('create_time', '<=', $todayEndStr)
            ->count();

        $pendingDeliveryCount = (int)Db::table('order')
            ->where('agent_id', $agentIdStr)
            ->where('order_status', 1)
            ->where('update_time', '>=', $last7DaysStr)
            ->where('update_time', '<=', $todayEndStr)
            ->count();

        $pendingTicketCount = 0;
        if ($workorderPluginEnabled) {
            $pendingTicketCount = (int)Db::table('tickets')
                ->where('agent_id', $agentId)
                ->where('status', 2)
                ->count();
        }

        $overviewReminders = [];
        if ($pendingPhotoCount > 0) {
            $overviewReminders[] = [
                'title' => '待传照片',
                'content' => '最近7天内有 ' . $pendingPhotoCount . ' 个订单待上传照片，请尽快提醒客户补齐资料。',
                'url' => '/agent/order/index',
                'link_text' => '去处理'
            ];
        }
        if ($pendingDeliveryCount > 0) {
            $overviewReminders[] = [
                'title' => '待发货',
                'content' => '最近7天内有 ' . $pendingDeliveryCount . ' 个订单仍处于待发货状态，请及时跟进。',
                'url' => '/agent/order/index',
                'link_text' => '去处理'
            ];
        }
        if ($pendingTicketCount > 0) {
            $overviewReminders[] = [
                'title' => '工单提醒',
                'content' => '您提交的工单，有 ' . $pendingTicketCount . ' 个工单客服已接手处理，请及时查看并回复。',
                'url' => '/agent/ticket/index',
                'link_text' => '去处理'
            ];
        }
        if (empty($overviewReminders)) {
            $overviewReminders[] = [
                'title' => '状态正常',
                'content' => '最近7天暂无待传照片、待发货订单' . ($workorderPluginEnabled ? '和待处理工单' : '') . '，当前业务状态正常。',
                'url' => '',
                'link_text' => '查看详情'
            ];
        }

        // 转化率（最近7天订单数/最近7天访问量）
        $conversionRate = $todayVisits > 0 ? round(($todayOrderCount / $todayVisits) * 100, 1) : 0;

        // 3. 订单和激活数据
        // 顶部与首页统计优先使用 order 表实时数据，避免 agents 汇总字段未同步导致展示不准
        $monthOrderCount = (int)Db::table('order')
            ->where('agent_id', $agentIdStr)
            ->where('create_time', '>=', $monthStartStr)
            ->where('create_time', '<=', $monthEndStr)
            ->count();

        $monthActiveOrders = (int)Db::table('order')
            ->where('agent_id', $agentIdStr)
            ->where('order_status', 4)
            ->where('jh_time', '>=', $monthStartStr)
            ->where('jh_time', '<=', $monthEndStr)
            ->count();

        $totalOrders = (int)Db::table('order')
            ->where('agent_id', $agentIdStr)
            ->count();

        $totalActivatedOrders = (int)Db::table('order')
            ->where('agent_id', $agentIdStr)
            ->whereIn('order_status', [4, 5])
            ->count();

        // 计算总激活率
        $activationRate = $totalOrders > 0 ? round(($totalActivatedOrders / $totalOrders) * 100, 2) : 0;

        // 4. 新增代理商（月）- 当前代理的下级代理
        $monthStartMs = strtotime($monthStartStr) * 1000;
        $monthEndMs = strtotime($monthEndStr) * 1000;
        
        $monthNewAgents = Db::table('agents')
            ->where('parent_id', $agentId)
            ->where('create_time', '>=', $monthStartMs)
            ->where('create_time', '<=', $monthEndMs)
            ->count();

        // 总代理商数
        $totalAgents = Db::table('agents')
            ->where('parent_id', $agentId)
            ->count();

        // 5. 获取预估佣金（分别统计待结算和已结算，只统计有效记录）
        // 本月待结算佣金
        $monthPendingCommission = Db::table('agent_balance_logs')
            ->where('agent_id', $agentId)
            ->where('type', 'pending')
            ->where('status', 1)
            ->where('create_time', '>=', strtotime($monthStartStr))
            ->where('create_time', '<=', strtotime($monthEndStr))
            ->sum('amount');

        // 本月已结算佣金（包括订单佣金、上级抽成、密价奖励）
        $monthSettledCommission = Db::table('agent_balance_logs')
            ->where('agent_id', $agentId)
            ->where('type', 'in')
            ->whereIn('sub_type', ['order', 'parent', 'secret_price'])
            ->where('create_time', '>=', strtotime($monthStartStr))
            ->where('create_time', '<=', strtotime($monthEndStr))
            ->sum('amount');

        // 总待结算佣金
        $totalPendingCommission = Db::table('agent_balance_logs')
            ->where('agent_id', $agentId)
            ->where('type', 'pending')
            ->where('status', 1)
            ->sum('amount');

        // 总已结算佣金（包括订单佣金、上级抽成、密价奖励）
        $totalSettledCommission = Db::table('agent_balance_logs')
            ->where('agent_id', $agentId)
            ->where('type', 'in')
            ->whereIn('sub_type', ['order', 'parent', 'secret_price'])
            ->sum('amount');

        // 6. 获取最新产品（产品上新模块）
        $latestProducts = Db::table('product')
            ->where('status', 1)
            ->order('create_time', 'desc')
            ->limit(10)
            ->field('id,name,create_time')
            ->select();

        $todayLatestProducts = Db::table('product')
            ->where('status', 1)
            ->where('create_time', '>=', $todayStartStr)
            ->where('create_time', '<=', $todayEndStr)
            ->order('create_time', 'desc')
            ->limit(10)
            ->field('id,name,create_time')
            ->select();

        // 7. 获取公告列表（按排序和创建时间）
        $announcements = Db::table('contents')
            ->alias('c')
            ->leftJoin('content_categories cc', 'c.category_id = cc.id')
            ->where('c.type', 'announcement')
            ->where('c.status', 1) // 只获取已发布的公告
            ->order('c.sort_order', 'asc') // 按排序号升序
            ->order('c.create_time', 'desc') // 再按创建时间降序
            ->limit(5) // 获取最新5条公告
            ->field('c.id,c.title,c.content,c.sort_order,c.create_time,cc.name as category_name')
            ->select();

        // 8. 获取活动列表
        $activities = [];
        if ($marketingPluginEnabled) {
            $activities = Db::table('activities')
                ->where('status', 1)
                ->order('sort_order', 'asc')
                ->order('create_time', 'desc')
                ->limit(5)
                ->field('id,title,start_time,end_time,status,target_value,duration_type')
                ->select();
        }

        // 9. 获取最新工单列表（按最新回复时间排序）
        $latestTickets = [];
        if ($workorderPluginEnabled) {
            $latestTickets = Db::table('tickets')
                ->where('agent_id', $agentId)
                ->order('reply_time', 'desc')
                ->order('create_time', 'desc')
                ->limit(5)
                ->field('id,title,category_id,status,create_time,reply_time')
                ->select();
        }

        // 格式化产品数据
        $formatTimelineProducts = function ($products) {
            $result = [];
            foreach ($products as $product) {
                $createTime = strtotime((string)($product['create_time'] ?? ''));
                if ($createTime <= 0) {
                    $createTime = time();
                }

                $result[] = [
                    'id' => (int)($product['id'] ?? 0),
                    'name' => (string)($product['name'] ?? ''),
                    'time' => date('Y-m-d', $createTime),
                    'timestamp' => $createTime
                ];
            }
            return $result;
        };

        $formattedProducts = $formatTimelineProducts($latestProducts);
        $formattedTodayProducts = $formatTimelineProducts($todayLatestProducts);

        // 格式化公告数据
        $formattedAnnouncements = [];
        foreach ($announcements as $index => $announcement) {
            // 设置重要级（按顺序显示：1、2、3、4、5）
            $priority = ($index % 5) + 1; // 循环显示1-5级
            
            // 根据重要级设置颜色（使用Layui支持的颜色）
            $priorityColors = [
                1 => 'red',     // 1级-红色（最重要）
                2 => 'orange',  // 2级-橙色
                3 => 'blue',    // 3级-蓝色
                4 => 'green',   // 4级-绿色
                5 => 'cyan'     // 5级-青色
            ];
            $priorityColor = $priorityColors[$priority] ?? 'blue';
            
            $formattedAnnouncements[] = [
                'id' => $announcement['id'],
                'title' => $announcement['title'],
                'category_name' => $announcement['category_name'] ?: '未分类',
                'content' => $announcement['content'],
                'priority' => $priority,
                'priority_color' => $priorityColor,
                'create_time' => $announcement['create_time']
            ];
        }

        // 格式化活动数据
        $formattedActivities = [];
        $currentTime = time();
        foreach ($activities as $index => $activity) {
            // 计算活动状态和进度
            $startTime = $activity['start_time'];
            $endTime = $activity['end_time'];
            
            // 判断活动状态
            if ($currentTime < $startTime) {
                $status = '未开始';
                $statusClass = 'text-warning'; // 黄色
                $progress = 0;
            } elseif ($endTime && $currentTime > $endTime) {
                // 活动已结束，根据目标完成情况判断
                // 这里简化处理，可以根据实际业务逻辑调整
                $isCompleted = ($index % 3 === 0); // 简化：每3个中有1个是已完成
                if ($isCompleted) {
                    $status = '已完成';
                    $statusClass = 'text-danger'; // 红色
                    $progress = 100;
                } else {
                    $status = '已结束';
                    $statusClass = 'text-muted'; // 灰色
                    $progress = [60, 70, 80][$index % 3]; // 未完成但已结束的进度
                }
            } else {
                $status = '进行中';
                $statusClass = 'text-success'; // 绿色
                
                // 计算进度（基于时间进度）
                if ($endTime) {
                    $totalDuration = $endTime - $startTime;
                    $elapsedTime = $currentTime - $startTime;
                    $progress = min(100, max(0, ($elapsedTime / $totalDuration) * 100));
                } else {
                    // 长期活动，随机生成一个进度
                    $progress = [10, 30, 50, 70, 90][$index % 5];
                }
            }
            
            $formattedActivities[] = [
                'id' => $activity['id'],
                'title' => $activity['title'],
                'start_time' => date('Y-m-d', $startTime),
                'end_time' => $endTime ? date('Y-m-d', $endTime) : '长期',
                'status' => $status,
                'status_class' => $statusClass,
                'progress' => round($progress, 0)
            ];
        }

        // 格式化工单数据
        $formattedTickets = [];
        $ticketStatusMap = [
            1 => ['text' => '待处理', 'color' => '#ff9800'],
            2 => ['text' => '处理中', 'color' => '#2196f3'],
            3 => ['text' => '已解决', 'color' => '#4caf50'],
            4 => ['text' => '已关闭', 'color' => '#9e9e9e']
        ];
        $ticketCategoryMap = [
            1 => '技术支持',
            2 => '账务问题',
            3 => '产品咨询',
            4 => '其他问题'
        ];
        
        foreach ($latestTickets as $index => $ticket) {
            $status = $ticket['status'] ?: 1;
            $statusInfo = $ticketStatusMap[$status] ?? ['text' => '未知', 'color' => '#999'];
            $categoryText = $ticketCategoryMap[$ticket['category_id']] ?? '其他';
            
            $formattedTickets[] = [
                'id' => $ticket['id'],
                'display_number' => $index + 1, // 简单的1,2,3,4,5编号
                'title' => mb_strlen($ticket['title']) > 25 ? mb_substr($ticket['title'], 0, 25) . '...' : $ticket['title'],
                'category_text' => $categoryText,
                'status_text' => $statusInfo['text'],
                'status_color' => $statusInfo['color'],
                'create_time' => date('m-d H:i', strtotime($ticket['create_time']))
            ];
        }

        // 获取店铺基本信息
        $shopName = $shopInfo ? $shopInfo['shop_name'] : '未设置';
        $shopCode = $shopInfo ? $shopInfo['shop_code'] : '';
        $shopUrl = $shopCode ? request()->domain() . '/index/shop/index/shop_code/' . $shopCode : '';
        $shopTotalOrders = (int)($shopInfo['total_orders'] ?? 0);
        $shopMonthVisits = (int)($shopInfo['month_visits'] ?? 0);
        $shopMonthOrders = (int)($shopInfo['month_orders'] ?? 0);
        $shopTodayOrders = (int)($shopInfo['today_orders'] ?? 0);
        $verifiedAgents = (int)Db::table('agents')->where('parent_id', $agentId)->where('is_verified', 1)->count();
        $unverifiedAgents = max(0, (int)$totalAgents - $verifiedAgents);
        $shopTrendDatasets = $this->buildAgentTrendDatasets((int)$agentId);
        $shopMetricSets = $this->buildAgentMetricSets((int)$agentId, $shopInfo);

        // 获取上级代理信息（agentInfo已在函数开头获取）
        $parentAgentInfo = '平台直属';
        $parentContactPhone = '';
        $parentServiceQrcode = '';
        
        if ($agentInfo && isset($agentInfo['parent_id']) && $agentInfo['parent_id'] > 0) {
            $parentAgent = Db::table('agents')->where('id', $agentInfo['parent_id'])->find();
            
            if ($parentAgent) {
                // 获取上级代理的店铺信息
                $parentShop = Db::table('agent_shop')->where('agent_id', $parentAgent['id'])->find();
                
                $parentAgentInfo = $parentAgent['username'] ?: '未知代理';
                $parentContactPhone = $parentShop ? ($parentShop['contact_phone'] ?: '') : '';
                $parentServiceQrcode = $parentShop ? ($parentShop['service_qrcode'] ?: '') : '';
            }
        }

        // 准备数据
        $dashboard = [
            // 订单量（本月）/ 总订单量（全部）- 从agents表读取
            'month_orders' => number_format($monthOrderCount),
            'total_orders' => $this->formatNumber($totalOrders),
            
            // 激活订单（本月）/ 总激活数
            'month_activated_orders' => number_format($monthActiveOrders),
            'total_activated_orders' => number_format($totalActivatedOrders),
            'activation_rate' => $activationRate . '%',
            
            // 预估佣金（本月）/ 总预估佣金（全部）
            // 待结算佣金（本月）/ 总待结算佣金（全部）
            'month_pending_commission' => '¥' . number_format($monthPendingCommission, 2),
            'total_pending_commission' => '¥' . number_format($totalPendingCommission, 2),
            
            // 已结算佣金（本月）/ 总已结算佣金（全部）
            'month_settled_commission' => '¥' . number_format($monthSettledCommission, 2),
            'total_settled_commission' => '¥' . number_format($totalSettledCommission, 2),

            // 新增代理商（本月）/ 总代理商（全部）
            'month_new_agents' => $monthNewAgents,
            'total_agents' => number_format($totalAgents),
            
            // 店铺相关数据
            'today_visits' => number_format($todayVisits ?: 0), // 今日访问量
            'total_visits' => number_format($totalVisits ?: 0), // 总访问量
            'today_orders' => number_format($todayOrderCount ?: 0), // 今日订单量
            'month_visits' => number_format($shopMonthVisits),
            'month_shop_orders' => number_format($shopMonthOrders),
            'shop_total_orders' => number_format($shopTotalOrders),
            'verified_agents' => number_format($verifiedAgents),
            'unverified_agents' => number_format($unverifiedAgents),
            'shop_name' => $shopName,
            'shop_code' => $shopCode,
            'shop_url' => $shopUrl,
            'parent_agent_name' => $parentAgentInfo,
            'parent_contact_phone' => $parentContactPhone,
            'parent_service_qrcode' => $parentServiceQrcode,

            'shop_metric_sets' => $shopMetricSets,
            'shop_trend_datasets' => $shopTrendDatasets,
            
            // 订单状态统计
            'order_status_stats' => [
                'submitted' => $orderStatusStats[0],
                'pending_delivery' => $orderStatusStats[1],
                'shipped' => $orderStatusStats[2],
                'pending_photo' => $orderStatusStats[3],
                'activated' => $orderStatusStats[4],
                'settled' => $orderStatusStats[5],
                'settlement_failed' => $orderStatusStats[6],
                'audit_failed' => $orderStatusStats[7],
                'total' => $totalStatusOrders,
                'settled_ratio' => $settledRatio
            ],

            'overview_stats' => [
                [
                    'label' => '历史总佣金',
                    'value' => '¥' . number_format((float)($agentInfo['total_money'] ?? 0), 2)
                ],
                [
                    'label' => '店铺访问量',
                    'value' => number_format((int)$totalVisits)
                ],
                [
                    'label' => '总订单',
                    'value' => number_format((int)$totalOrders)
                ],
                [
                    'label' => '发展代理数',
                    'value' => number_format((int)$totalAgents)
                ],
                [
                    'label' => '当前余额',
                    'value' => '¥' . number_format((float)($agentInfo['balance'] ?? 0), 2)
                ]
            ],
            'overview_reminders' => $overviewReminders,
            
            // 最新产品数据
            'latest_products' => [
                'recent' => $formattedProducts,
                'today' => $formattedTodayProducts
            ],
            
            // 公告列表数据
            'announcements' => $formattedAnnouncements,
            
            // 活动列表数据
            'activities' => $formattedActivities,
            
            // 最新工单数据
            'latest_tickets' => $formattedTickets,
            'workorder_plugin_enabled' => $workorderPluginEnabled ? 1 : 0,
            'marketing_plugin_enabled' => $marketingPluginEnabled ? 1 : 0,
            'employee_context' => $employeeContext,
            'employee_team_stats' => $employeeTeamStats,
        ];

        if (!empty($employeeContext['is_employee'])) {
            $dashboard['overview_stats'] = [
                [
                    'label' => '员工号',
                    'value' => (string)($employeeContext['employee_code'] ?: '-')
                ],
                [
                    'label' => '本月团队订单',
                    'value' => number_format((int)($employeeTeamStats['month']['total_orders'] ?? 0))
                ],
                [
                    'label' => '全年团队订单',
                    'value' => number_format((int)($employeeTeamStats['year']['total_orders'] ?? 0))
                ],
                [
                    'label' => '本月待结算',
                    'value' => '¥' . number_format((float)($employeeTeamStats['month']['payable_amount'] ?? 0), 2)
                ],
                [
                    'label' => '当前余额',
                    'value' => '¥' . number_format((float)($agentInfo['balance'] ?? 0), 2)
                ]
            ];
        }

        return $dashboard;
    }

    /**
     * 格式化数字显示
     */
    private function formatNumber($number)
    {
        if ($number >= 10000) {
            return round($number / 10000, 1) . ' 万';
        }
        return number_format($number);
    }

    /**
     * 构建店铺趋势数据集
     */
    private function buildAgentTrendDatasets(int $agentId): array
    {
        $datasets = [];
        foreach ([7, 30] as $rangeDays) {
            $datasets[$rangeDays . '_day'] = $this->buildAgentTrendDataset($agentId, $rangeDays, 'day');
        }
        return $datasets;
    }

    private function buildAgentMetricSets(int $agentId, ?array $shopInfo): array
    {
        $sets = [];
        $shopId = (int)($shopInfo['id'] ?? 0);

        foreach ([7, 30] as $rangeDays) {
            $start = new \DateTimeImmutable(date('Y-m-d 00:00:00', strtotime('-' . ($rangeDays - 1) . ' days')));
            $end = new \DateTimeImmutable(date('Y-m-d 23:59:59'));
            $startStr = $start->format('Y-m-d H:i:s');
            $endStr = $end->format('Y-m-d H:i:s');
            $startMs = $start->getTimestamp() * 1000;
            $endMs = $end->getTimestamp() * 1000;

            $visitCount = 0;
            if ($shopId > 0) {
                $visitCount = (int)Db::table('agent_shop_visits')
                    ->where('shop_id', $shopId)
                    ->where('visit_date', '>=', $start->format('Y-m-d'))
                    ->where('visit_date', '<=', $end->format('Y-m-d'))
                    ->count();
            }

            $orderCount = (int)Db::table('order')
                ->where('agent_id', (string)$agentId)
                ->where('create_time', '>=', $startStr)
                ->where('create_time', '<=', $endStr)
                ->count();

            $activatedCount = (int)Db::table('order')
                ->where('agent_id', (string)$agentId)
                ->where('order_status', 4)
                ->where('jh_time', '>=', $startStr)
                ->where('jh_time', '<=', $endStr)
                ->count();

            $settledCount = (int)Db::table('order')
                ->where('agent_id', (string)$agentId)
                ->where('order_status', 5)
                ->where('js_time', '>=', $startStr)
                ->where('js_time', '<=', $endStr)
                ->count();

            $newAgentCount = (int)Db::table('agents')
                ->where('parent_id', $agentId)
                ->where('create_time', '>=', $startMs)
                ->where('create_time', '<=', $endMs)
                ->count();

            $verifiedAgentCount = (int)Db::table('agents')
                ->where('parent_id', $agentId)
                ->where('is_verified', 1)
                ->where('verify_time', '>=', $startMs)
                ->where('verify_time', '<=', $endMs)
                ->count();

            $sets[(string)$rangeDays] = [
                [
                    'label' => '店铺访问量',
                    'value' => number_format($visitCount),
                    'extra' => '近' . $rangeDays . '天访客'
                ],
                [
                    'label' => '订单量',
                    'value' => number_format($orderCount),
                    'extra' => '近' . $rangeDays . '天订单'
                ],
                [
                    'label' => '已激活订单',
                    'value' => number_format($activatedCount),
                    'extra' => '近' . $rangeDays . '天激活'
                ],
                [
                    'label' => '已结算订单',
                    'value' => number_format($settledCount),
                    'extra' => '近' . $rangeDays . '天结算'
                ],
                [
                    'label' => '新增代理数',
                    'value' => number_format($newAgentCount),
                    'extra' => '近' . $rangeDays . '天新增'
                ],
                [
                    'label' => '实名代理数',
                    'value' => number_format($verifiedAgentCount),
                    'extra' => '近' . $rangeDays . '天实名'
                ]
            ];
        }

        return $sets;
    }

    private function buildAgentTrendDataset(int $agentId, int $rangeDays, string $unit): array
    {
        $start = new \DateTimeImmutable(date('Y-m-d 00:00:00', strtotime('-' . ($rangeDays - 1) . ' days')));
        $end = new \DateTimeImmutable(date('Y-m-d 23:59:59'));
        $buckets = $this->buildTrendBuckets($start, $end, $unit);

        $orders = Db::table('order')
            ->where('agent_id', (string)$agentId)
            ->where('create_time', '>=', $start->format('Y-m-d H:i:s'))
            ->where('create_time', '<=', $end->format('Y-m-d H:i:s'))
            ->field('create_time')
            ->select()
            ->toArray();

        foreach ($orders as $order) {
            $timestamp = strtotime((string)($order['create_time'] ?? ''));
            if ($timestamp > 0) {
                $this->appendTrendCount($buckets, $timestamp, 'orders');
            }
        }

        $agents = Db::table('agents')
            ->where('parent_id', $agentId)
            ->where('create_time', '>=', $start->getTimestamp() * 1000)
            ->where('create_time', '<=', $end->getTimestamp() * 1000)
            ->field('create_time')
            ->select()
            ->toArray();

        foreach ($agents as $agent) {
            $timestamp = (int)floor(((int)($agent['create_time'] ?? 0)) / 1000);
            if ($timestamp > 0) {
                $this->appendTrendCount($buckets, $timestamp, 'agents');
            }
        }

        $labels = [];
        $orderCounts = [];
        $agentCounts = [];
        foreach ($buckets as $bucket) {
            $labels[] = $bucket['label'];
            $orderCounts[] = $bucket['orders'];
            $agentCounts[] = $bucket['agents'];
        }

        return [
            'range_days' => $rangeDays,
            'unit' => $unit,
            'labels' => $labels,
            'orders' => $orderCounts,
            'agents' => $agentCounts,
            'title' => $this->getTrendTitle($rangeDays, $unit)
        ];
    }

    private function getTrendTitle(int $rangeDays, string $unit): string
    {
        return '近' . $rangeDays . '天趋势';
    }

    private function buildTrendBuckets(\DateTimeImmutable $start, \DateTimeImmutable $end, string $unit): array
    {
        $buckets = [];

        if ($unit === 'week') {
            $cursor = $start;
            while ($cursor <= $end) {
                $bucketStart = $cursor;
                $bucketEnd = $cursor->modify('+6 days')->setTime(23, 59, 59);
                if ($bucketEnd > $end) {
                    $bucketEnd = $end;
                }
                $buckets[] = [
                    'start' => $bucketStart->getTimestamp(),
                    'end' => $bucketEnd->getTimestamp(),
                    'label' => $bucketStart->format('m/d') . '-' . $bucketEnd->format('m/d'),
                    'orders' => 0,
                    'agents' => 0
                ];
                $cursor = $bucketEnd->modify('+1 second');
            }
            return $buckets;
        }

        if ($unit === 'month') {
            $cursor = $start->modify('first day of this month')->setTime(0, 0, 0);
            while ($cursor <= $end) {
                $bucketStart = $cursor < $start ? $start : $cursor;
                $bucketEnd = $cursor->modify('last day of this month')->setTime(23, 59, 59);
                if ($bucketEnd > $end) {
                    $bucketEnd = $end;
                }
                $buckets[] = [
                    'start' => $bucketStart->getTimestamp(),
                    'end' => $bucketEnd->getTimestamp(),
                    'label' => $bucketStart->format('Y-m'),
                    'orders' => 0,
                    'agents' => 0
                ];
                $cursor = $cursor->modify('first day of next month')->setTime(0, 0, 0);
            }
            return $buckets;
        }

        $cursor = $start;
        while ($cursor <= $end) {
            $bucketStart = $cursor;
            $bucketEnd = $cursor->setTime(23, 59, 59);
            $buckets[] = [
                'start' => $bucketStart->getTimestamp(),
                'end' => $bucketEnd->getTimestamp(),
                'label' => $bucketStart->format('m/d'),
                'orders' => 0,
                'agents' => 0
            ];
            $cursor = $cursor->modify('+1 day');
        }
        return $buckets;
    }

    private function appendTrendCount(array &$buckets, int $timestamp, string $field): void
    {
        foreach ($buckets as &$bucket) {
            if ($timestamp >= $bucket['start'] && $timestamp <= $bucket['end']) {
                $bucket[$field] = (int)$bucket[$field] + 1;
                break;
            }
        }
        unset($bucket);
    }

    /**
     * 生成唯一店铺编码
     */
    private function generateUniqueShopCode()
    {
        $shopCode = bin2hex(random_bytes(4));
        while (Db::table('agent_shop')->where('shop_code', $shopCode)->find()) {
            $shopCode = bin2hex(random_bytes(4));
        }
        return $shopCode;
    }
}
