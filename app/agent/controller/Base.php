<?php
namespace app\agent\controller;

use think\facade\Session;

class Base
{
    /**
     * 构造函数📝
     */
    public function __construct()
    {
        // 调用初始化方法
        $this->initialize();
    }
    
    /**
     * 初始化 - 执行登录检查
     */
    public function initialize()
    {
        // 获取当前控制器和方法
        $controller = request()->controller();
        $action = request()->action();
        // 不需要登录验证的方法
        $allowActions = ['login', 'captcha', 'logout'];
        $isLoginController = ($controller === 'Login') || 
                            (strpos(get_class($this), 'Login') !== false);
        
        if ($isLoginController || in_array($action, $allowActions)) {
            return true;
        }
        
        // 执行登录检查
        $this->checkLogin();
    }
    
    /**
     * 检查登录状态
     */
    protected function checkLogin()
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

        // 如果Session中没有，尝试通过token验证
        if (!$agentId) {
            $token = $_COOKIE['agent_token'] ?? '';
            if (!empty($token)) {
                // 通过token查找代理
                $agent = \think\facade\Db::table('agents')
                    ->where('token', $token)
                    ->where('status', '1')
                    ->find();
                
                if ($agent) {
                    // token有效，恢复Session
                    Session::set('agent_id', $agent['id']);
                    Session::set('agent_username', $agent['username']);
                    Session::set('agent_info', $agent);
                    
                    $agentId = $agent['id'];
                }
            }
        }

        if (!$agentId) {
            // Ajax请求返回JSON
            if (request()->isAjax()) {
                json(['code' => 1, 'msg' => '请先登录', 'url' => '/agent/login'])->send();
                exit;
            }
            // 普通请求跳转到登录页
            redirect('/agent/login')->send();
            exit;
        }
        
        return true;
    }
    
    /**
     * 获取当前登录的代理ID
     */
    protected function getAgentId()
    {
        $agentId = Session::get('agent_id');
        
        // 如果Session中没有，尝试通过token获取
        if (!$agentId) {
            $token = $_COOKIE['agent_token'] ?? '';
            if (!empty($token)) {
                $agent = \think\facade\Db::table('agents')
                    ->where('token', $token)
                    ->where('status', '1')
                    ->find();
                if ($agent) {
                    $agentId = $agent['id'];
                }
            }
        }
        
        return $agentId;
    }

    /**
     * 获取当前登录代理信息
     */
    protected function getAgentInfo()
    {
        $agentInfo = Session::get('agent_info');
        
        // 如果Session中没有，尝试通过token获取
        if (!$agentInfo) {
            $token = $_COOKIE['agent_token'] ?? '';
            if (!empty($token)) {
                $agent = \think\facade\Db::table('agents')
                    ->where('token', $token)
                    ->where('status', '1')
                    ->find();
                if ($agent) {
                    $agentInfo = $agent;
                }
            }
        }
        
        return $agentInfo;
    }

    /**
     * 返回成功的JSON响应
     */
    protected function success($msg = '操作成功', $data = [], $code = 0)
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data
        ]);
    }

    /**
     * 分页响应（专门用于表格数据）
     */
    protected function paginate($data, $total, $page = 1, $limit = 15)
    {
        return json([
            'code' => 0,
            'msg' => '',
            'count' => $total,
            'data' => $data
        ]);
    }

    /**
     * 返回失败的JSON响应
     */
    protected function error($msg = '操作失败', $code = 0, $data = [])
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data
        ]);
    }
}
