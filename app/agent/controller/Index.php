<?php
namespace app\agent\controller;

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


        // 获取代理商基本信息用于显示（关联密价等级表）
        $agent = Db::table('agents')
            ->alias('a')
            ->leftJoin('secret_price_levels spl', 'a.secret_price_level_id = spl.id')
            ->leftJoin('invite_code ic', 'a.invite_code_id = ic.id')
            ->field('a.*, spl.level_name as secret_price_level_name, ic.level_name as agent_level_name')
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

        // 安全检查：验证是否通过管理员切换过来的
        $showAdminTab = $this->verifyAdminSwitch();

        // 获取系统配置
        $config = $this->getSystemConfig();

        // 插件显隐控制
        $pluginStatus = $this->checkPluginsStatus(['workorder', 'marketing', 'down_api']);
        $workorderPluginEnabled = $pluginStatus['workorder'];
        $marketingPluginEnabled = $pluginStatus['marketing'];
        $downApiPluginEnabled = $pluginStatus['down_api'];

        View::assign('agent', $agent);
        View::assign('config', $config);
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

            return $result;
        } catch (\Exception $e) {
            // 返回默认配置
            return [
                'site_name' => '流量卡管理系统',
                'site_copyright' => '© 2024 流量卡管理系统'
            ];
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
        // 将agent_id转换为字符串，因为order表中agent_id是varchar类型
        $agentIdStr = (string)$agentId;

        // 获取代理信息（包含统计字段）
        $agentInfo = Db::table('agents')->where('id', $agentId)->find();

        // 获取时间字符串（数据库中create_time是字符串格式）
        $last7DaysStr = date('Y-m-d 00:00:00', strtotime('-7 days'));
        $todayEndStr = date('Y-m-d 23:59:59');
        $monthStartStr = date('Y-m-01 00:00:00');
        $monthEndStr = date('Y-m-t 23:59:59');

        // 获取代理的店铺信息
        $shopInfo = Db::table('agent_shop')->where('agent_id', $agentId)->find();
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

        // 转化率（最近7天订单数/最近7天访问量）
        $conversionRate = $todayVisits > 0 ? round(($todayOrderCount / $todayVisits) * 100, 1) : 0;

        // 3. 订单和激活数据 - 直接从agents表读取现成的统计字段（更高效）
        $monthOrderCount = $agentInfo['month_orders'] ?? 0;      // 本月订单数
        $monthActiveOrders = $agentInfo['month_jihuo'] ?? 0;     // 本月激活
        $totalOrders = $agentInfo['total_orders'] ?? 0;          // 总订单数
        $totalActivatedOrders = $agentInfo['total_jihuo'] ?? 0;  // 总激活

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
            ->where('status', 1) // 只获取启用的产品
            ->order('create_time', 'desc') // 按创建时间降序
            ->limit(8) // 获取最新8个产品
            ->field('name,create_time')
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
        $activities = Db::table('activities')
            ->where('status', 1) // 只获取启用的活动
            ->order('sort_order', 'asc') // 按排序号升序
            ->order('create_time', 'desc') // 再按创建时间降序
            ->limit(5) // 获取最新5条活动
            ->field('id,title,start_time,end_time,status,target_value,duration_type')
            ->select();

        // 9. 获取最新工单列表（按最新回复时间排序）
        $latestTickets = Db::table('tickets')
            ->where('agent_id', $agentId)
            ->order('reply_time', 'desc') // 按最新回复时间降序
            ->order('create_time', 'desc') // 再按创建时间降序
            ->limit(5) // 获取最新5条工单
            ->field('id,title,category_id,status,create_time,reply_time')
            ->select();

        // 格式化产品数据
        $formattedProducts = [];
        foreach ($latestProducts as $index => $product) {
            $isActive = $index % 2 === 0; // 第0、2、4...个（即第1、3、5...个）激活
            // 限制产品名称长度，避免布局问题
            $productName = $product['name'];
            if (mb_strlen($productName) > 20) {
                $productName = mb_substr($productName, 0, 20) . '...';
            }
            
            $formattedProducts[] = [
                'name' => $productName,
                'time' => date('H:i', strtotime($product['create_time'])),
                'date' => date('m-d', strtotime($product['create_time'])),
                'is_active' => $isActive,
                'icon_class' => $isActive ? 'layui-icon layui-timeline-axis active' : 'layui-icon layui-timeline-axis'
            ];
        }

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
        return [
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
            'shop_name' => $shopName,
            'shop_code' => $shopCode,
            'shop_url' => $shopUrl,
            'parent_agent_name' => $parentAgentInfo,
            'parent_contact_phone' => $parentContactPhone,
            'parent_service_qrcode' => $parentServiceQrcode,
            
            // 最新产品数据
            'latest_products' => $formattedProducts,
            
            // 公告列表数据
            'announcements' => $formattedAnnouncements,
            
            // 活动列表数据
            'activities' => $formattedActivities,
            
            // 最新工单数据
            'latest_tickets' => $formattedTickets
        ];
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
}
