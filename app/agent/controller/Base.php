<?php
namespace app\agent\controller;

use app\common\service\AgentDomainBrandService;
use app\agent\service\AgentTokenService;
use app\common\traits\SafeCacheTrait;
class Base
{
    use SafeCacheTrait;

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
        $allowActions = ['login', 'captcha', 'logout', 'image'];
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
        $agent = AgentTokenService::getCurrentAgent();
        $agentId = $agent ? (int)$agent['id'] : 0;
        $domainDenied = false;

        if ($agentId && !AgentDomainBrandService::canAgentAccessCurrentDomain((int)$agentId)) {
            $domainDenied = true;
            $this->clearAgentAuth();
            $agentId = 0;
        }

        if (!$agentId) {
            // Ajax请求返回JSON
            if (request()->isAjax()) {
                $msg = $domainDenied ? '当前域名无访问权限，请使用所属代理域名登录' : '请先登录';
                json(['code' => 1, 'msg' => $msg, 'url' => '/#/agent'])->send();
                exit;
            }
            // 普通请求跳转到 Vue 登录页
            redirect($this->vueLoginUrl('agent'))->send();
            exit;
        }
        
        return true;
    }

    protected function vueLoginUrl(string $loginType): string
    {
        $path = $loginType === 'agent' ? '/#/agent' : '/#/admin';
        $scheme = request()->header('X-Forwarded-Proto') ?: request()->scheme();
        $host = request()->header('X-Forwarded-Host') ?: request()->server('HTTP_HOST', request()->host());
        if (preg_match('/:(9000|8000)$/', $host)) {
            $host = preg_replace('/:(9000|8000)$/', ':3006', $host);
            return $scheme . '://' . $host . $path;
        }
        return $path;
    }

    /**
     * 清理代理登录态
     */
    protected function clearAgentAuth()
    {
        AgentTokenService::revokeCurrentToken();
        if (isset($_COOKIE['agent_token'])) {
            setcookie('agent_token', '', time() - 3600, '/');
        }
    }
    
    /**
     * 获取当前登录的代理ID
     */
    protected function getAgentId()
    {
        return AgentTokenService::getCurrentAgentId();
    }

    /**
     * 获取当前登录代理信息
     */
    protected function getAgentInfo()
    {
        return AgentTokenService::getCurrentAgent();
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
