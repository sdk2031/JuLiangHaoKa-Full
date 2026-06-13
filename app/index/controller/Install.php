<?php
namespace app\index\controller;

use think\facade\Db;
use think\facade\View;
use think\facade\Config;
use think\facade\Cache;
use app\common\service\PermissionService;

class Install
{
    // 安装标识文件路径🆕
    private $installLockFile = '';
    
    public function __construct()
    {
        $this->installLockFile = app()->getRootPath() . 'install/install.lock';
    }
    
    public function index()
    {

        if ($this->isInstalled()) {
            return $this->showAlreadyInstalled();
        }
        
        View::assign([
            'title' => '系统安装向导',
            'step' => 1,
            'total_steps' => 5
        ]);
        
        return View::fetch('install/welcome');
    }

    /**
     * Vue安装向导初始化数据
     */
    public function vueInfo()
    {
        $envData = $this->checkEnvironment();
        $phpExtensions = array();
        $phpFunctions = array();
        $directories = array();

        foreach ($envData as $key => $check) {
            if (!is_array($check) || !isset($check['type'])) {
                continue;
            }
            $item = array(
                'key' => $key,
                'name' => $check['name'] ?? $key,
                'status' => !empty($check['status']),
                'required' => (($check['type'] ?? '') === 'required'),
                'description' => $check['description'] ?? '',
            );
            if (in_array($key, array('pdo', 'pdo_mysql', 'mbstring', 'openssl', 'curl', 'fileinfo', 'gd', 'json'))) {
                $phpExtensions[] = $item;
            } elseif ($key === 'exec_function') {
                $phpFunctions[] = $item;
            } elseif (strpos($key, 'dir_') === 0) {
                $dirName = str_replace('dir_', '', $key);
                $dirName = str_replace('_', '/', $dirName);
                $directories[] = array(
                    'name' => $dirName,
                    'status' => !empty($check['status']),
                    'required' => true,
                    'description' => $check['description'] ?? '',
                );
            }
        }

        $databaseConfig = array(
            'hostname' => 'localhost',
            'hostport' => '3306',
            'database' => '',
            'username' => '',
            'password' => ''
        );
        $sessionConfig = session('install_db_config');
        if (is_array($sessionConfig) && !empty($sessionConfig['hostname'])) {
            $databaseConfig = array_merge($databaseConfig, array(
                'hostname' => (string)($sessionConfig['hostname'] ?? 'localhost'),
                'hostport' => (string)($sessionConfig['hostport'] ?? '3306'),
                'database' => (string)($sessionConfig['database'] ?? ''),
                'username' => (string)($sessionConfig['username'] ?? ''),
                'password' => (string)($sessionConfig['password'] ?? '')
            ));
        }

        $hasExistingConfig = $this->hasValidDatabaseConfig();
        $existingConfigInfo = null;
        if ($hasExistingConfig) {
            try {
                $config = $this->getInstallDatabaseConfig();
                $existingConfigInfo = array(
                    'hostname' => (string)($config['hostname'] ?? ''),
                    'database' => (string)($config['database'] ?? ''),
                    'username' => (string)($config['username'] ?? '')
                );
            } catch (\Exception $e) {
                $hasExistingConfig = false;
            }
        }

        return json(array(
            'code' => 1,
            'msg' => 'success',
            'data' => array(
                'installed' => $this->isInstalled(),
                'php_version' => PHP_VERSION,
                'can_continue' => !empty($envData['can_continue']),
                'php_extensions' => $phpExtensions,
                'php_functions' => $phpFunctions,
                'directories' => $directories,
                'database_config' => $databaseConfig,
                'has_existing_config' => $hasExistingConfig,
                'existing_config' => $existingConfigInfo,
            )
        ));
    }
    
    /**
     * 环境检测
     */
    public function check()
    {
        if ($this->isInstalled()) {
            return $this->showAlreadyInstalled();
        }
        $envData = $this->checkEnvironment();
        $php_extensions = array();
        $php_functions = array();
        $directories = array();
        
        foreach ($envData as $key => $check) {
            if (is_array($check) && isset($check['type'])) {
                if (in_array($key, array('pdo', 'pdo_mysql', 'mbstring', 'openssl', 'curl', 'fileinfo', 'gd', 'json'))) {
                    $php_extensions[$key] = array(
                        'name' => $check['name'],
                        'status' => $check['status'],
                        'required' => $check['type'] === 'required',
                        'description' => isset($check['description']) ? $check['description'] : ''
                    );
                }
                elseif ($key === 'exec_function') {
                    $php_functions[$key] = array(
                        'name' => $check['name'],
                        'status' => $check['status'],
                        'required' => $check['type'] === 'required',
                        'description' => isset($check['description']) ? $check['description'] : ''
                    );
                }
                elseif (strpos($key, 'dir_') === 0) {
                    $dirName = str_replace('dir_', '', $key);
                    $dirName = str_replace('_', '/', $dirName);
                    $directories[$dirName] = $check['status'];
                }
            }
        }
        
        View::assign([
            'title' => '环境检测',
            'step' => 2,
            'total_steps' => 5,
            'php_extensions' => $php_extensions,
            'php_functions' => $php_functions,
            'directories' => $directories,
            'can_continue' => $envData['can_continue']
        ]);
        
        return View::fetch('install/check');
    }

    public function database()
    {
        if ($this->isInstalled()) {
            return $this->showAlreadyInstalled();
        }

        if (request()->isPost()) {
            try {
                $data = $this->normalizeDatabaseInput(input('post.'));
                $action = input('get.action', '');
                
                // 如果是测试连接
                if ($action === 'test') {
                    $result = $this->testDatabaseConnection($data);
                    if ($result['success']) {
                        return json(array('success' => true, 'message' => '数据库连接测试成功'));
                    } else {
                        return json(array('success' => false, 'message' => $result['error']));
                    }
                }
                
                // 正式配置数据库
                $result = $this->testDatabaseConnection($data);
                
                if ($result['success']) {
                    // 保存数据库配置
                    $this->saveDatabaseConfig($data);
                    return json(array('code' => 1, 'msg' => '数据库连接成功', 'url' => '/index/install/setup'));
                } else {
                    return json(array('code' => 0, 'msg' => $result['error']));
                }
            } catch (\Exception $e) {
                // 记录错误日志
                error_log('安装程序数据库配置错误: ' . $e->getMessage());
                return json(array('code' => 0, 'msg' => '配置过程中发生错误: ' . $e->getMessage()));
            }
        }
        
        // 检查是否已有数据库配置
        $hasExistingConfig = $this->hasValidDatabaseConfig();
        $existingConfigInfo = null;
        $viewConfig = array(
            'hostname' => 'localhost',
            'hostport' => '3306',
            'database' => '',
            'username' => '',
            'password' => ''
        );

        $sessionConfig = session('install_db_config');
        if (is_array($sessionConfig) && !empty($sessionConfig['hostname'])) {
            $viewConfig = array_merge($viewConfig, array(
                'hostname' => (string)($sessionConfig['hostname'] ?? 'localhost'),
                'hostport' => (string)($sessionConfig['hostport'] ?? '3306'),
                'database' => (string)($sessionConfig['database'] ?? ''),
                'username' => (string)($sessionConfig['username'] ?? ''),
                'password' => (string)($sessionConfig['password'] ?? '')
            ));
        }
        
        if ($hasExistingConfig) {
            try {
                $config = $this->getInstallDatabaseConfig();
                $existingConfigInfo = array(
                    'hostname' => (string)($config['hostname'] ?? ''),
                    'database' => (string)($config['database'] ?? ''),
                    'username' => (string)($config['username'] ?? '')
                );
            } catch (\Exception $e) {
                $hasExistingConfig = false;
            }
        }
        
        View::assign([
            'title' => '数据库配置',
            'step' => 3,
            'total_steps' => 5,
            'has_existing_config' => $hasExistingConfig,
            'existing_config' => $existingConfigInfo,
            'config' => $viewConfig
        ]);
        
        return View::fetch('install/database');
    }

    /**
     * 数据库配置（兼容别名，避免部分环境对 database 路径的拦截）
     */
    public function dbconfig()
    {
        return $this->database();
    }
    
    /**
     * 系统设置和管理员账户创建
     */
    public function setup()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $action = isset($data['action']) ? (string)$data['action'] : '';

            if ($action === 'start') {
                if ($this->isInstalled()) {
                    return json(array('code' => 0, 'msg' => '系统已安装，无需重复安装'));
                }
                return $this->startInstallTask($data);
            }
            if ($action === 'progress') {
                $taskId = trim((string)($data['task_id'] ?? session('install_task_id', '')));
                $task = $taskId !== '' ? $this->loadInstallTask($taskId) : null;
                if ($this->isInstalled()) {
                    // 若任务仍在运行，继续按任务推进，避免 lock 创建后误报成功
                    if (is_array($task) && (($task['status'] ?? '') === 'running')) {
                        return $this->processInstallTask($data);
                    }
                    return json(array(
                        'code' => 1,
                        'msg' => '安装完成',
                        'data' => array(
                            'task_id' => $taskId,
                            'progress' => 100,
                            'current_step' => '安装完成',
                            'logs' => is_array($task) ? ($task['logs'] ?? array()) : array(),
                            'completed' => true,
                            'url' => '/index/install/complete'
                        )
                    ));
                }
                return $this->processInstallTask($data);
            }

            if ($this->isInstalled()) {
                return json(array('code' => 0, 'msg' => '系统已安装，无需重复安装'));
            }

            // 兼容旧安装调用方式
            $result = $this->createSystem($data);
            if ($result['success']) {
                return json(array('code' => 1, 'msg' => '安装完成！', 'url' => '/index/install/complete'));
            }
            return json(array('code' => 0, 'msg' => $result['error']));
        }

        if ($this->isInstalled()) {
            return $this->showAlreadyInstalled();
        }
        
        View::assign([
            'title' => '系统设置',
            'step' => 4,
            'total_steps' => 5
        ]);
        
        return View::fetch('install/setup');
    }

    private function startInstallTask($data)
    {
        $adminUsername = trim((string)($data['admin_username'] ?? ''));
        $adminPassword = (string)($data['admin_password'] ?? '');
        $adminPasswordConfirm = (string)($data['admin_password_confirm'] ?? '');
        $siteName = trim((string)($data['site_name'] ?? ''));
        $authCode = trim((string)($data['auth_code'] ?? ''));

        if ($adminUsername === '' || $adminPassword === '' || $adminPasswordConfirm === '' || $siteName === '' || $authCode === '') {
            return json(array('code' => 0, 'msg' => '请填写所有必填项'));
        }
        if (strlen($adminPassword) < 6) {
            return json(array('code' => 0, 'msg' => '管理员密码长度至少6位'));
        }
        if ($adminPassword !== $adminPasswordConfirm) {
            return json(array('code' => 0, 'msg' => '两次输入的管理员密码不一致'));
        }

        $taskId = 'install_' . md5(uniqid('', true) . mt_rand(1000, 9999));
        $phases = $this->getInstallPhases();
        $task = array(
            'task_id' => $taskId,
            'status' => 'running',
            'created_at' => time(),
            'updated_at' => time(),
            'current_phase' => 0,
            'total_phases' => count($phases),
            'progress' => 0,
            'logs' => array(),
            'error' => '',
            'data' => array(
                'admin_username' => $adminUsername,
                'admin_password' => $adminPassword,
                'admin_password_confirm' => $adminPasswordConfirm,
                'site_name' => $siteName,
                'auth_code' => $authCode,
            ),
            'sql_state' => array(
                'prepared' => false,
                'cache_file' => '',
                'index' => 0,
                'total' => 0,
                'executed' => 0,
                'skipped' => 0
            )
        );
        $this->appendTaskLog($task, '安装任务已创建');
        $this->saveInstallTask($task);
        session('install_task_id', $taskId);

        return json(array(
            'code' => 1,
            'msg' => '安装任务已启动',
            'data' => array(
                'task_id' => $taskId,
                'progress' => 0,
                'current_step' => '准备安装',
                'logs' => $task['logs']
            )
        ));
    }

    private function processInstallTask($data)
    {
        $taskId = trim((string)($data['task_id'] ?? session('install_task_id', '')));
        if ($taskId === '') {
            return json(array('code' => 0, 'msg' => '安装任务不存在，请重新开始安装'));
        }

        $task = $this->loadInstallTask($taskId);
        if (empty($task)) {
            return json(array('code' => 0, 'msg' => '安装任务已丢失，请重新开始安装'));
        }

        if (($task['status'] ?? '') === 'completed') {
            return json(array(
                'code' => 1,
                'msg' => '安装完成',
                'data' => array(
                    'task_id' => $taskId,
                    'progress' => 100,
                    'current_step' => '安装完成',
                    'logs' => $task['logs'],
                    'completed' => true,
                    'url' => '/index/install/complete'
                )
            ));
        }

        if (($task['status'] ?? '') === 'failed') {
            return json(array(
                'code' => 0,
                'msg' => $task['error'] ?: '安装失败',
                'data' => array(
                    'task_id' => $taskId,
                    'progress' => (int)($task['progress'] ?? 0),
                    'current_step' => '安装失败',
                    'logs' => $task['logs']
                )
            ));
        }

        try {
            $this->runInstallTaskPhase($task);
            $this->saveInstallTask($task);
        } catch (\Exception $e) {
            $task['status'] = 'failed';
            $task['error'] = '安装失败：' . $e->getMessage();
            $this->appendTaskLog($task, $task['error'], 'error');
            $this->saveInstallTask($task);

            return json(array(
                'code' => 0,
                'msg' => $task['error'],
                'data' => array(
                    'task_id' => $taskId,
                    'progress' => (int)($task['progress'] ?? 0),
                    'current_step' => '安装失败',
                    'logs' => $task['logs']
                )
            ));
        }

        $completed = (($task['status'] ?? '') === 'completed');
        return json(array(
            'code' => 1,
            'msg' => $completed ? '安装完成' : '安装进行中',
            'data' => array(
                'task_id' => $taskId,
                'progress' => (int)($task['progress'] ?? 0),
                'current_step' => $this->getTaskCurrentStepName($task),
                'logs' => $task['logs'],
                'completed' => $completed,
                'url' => $completed ? '/index/install/complete' : ''
            )
        ));
    }

    private function runInstallTaskPhase(&$task)
    {
        $phases = $this->getInstallPhases();
        $currentPhase = (int)($task['current_phase'] ?? 0);
        $totalPhases = count($phases);

        if ($currentPhase >= $totalPhases) {
            $task['status'] = 'completed';
            $task['progress'] = 100;
            $this->appendTaskLog($task, '安装已完成', 'success');
            return;
        }

        $phase = $phases[$currentPhase];
        $phaseKey = $phase['key'];
        $phaseName = $phase['name'];
        $this->appendTaskLog($task, $phaseName . '...');

        if ($phaseKey === 'prepare_permissions') {
            $permissionResult = PermissionService::autoFix();
            $failedDirs = array();
            foreach (($permissionResult['directories'] ?? array()) as $dir => $result) {
                if (empty($result['success']) || empty($result['writable'])) {
                    $failedDirs[] = $dir;
                }
            }
            if (!empty($failedDirs)) {
                throw new \Exception('目录初始化失败：' . implode(',', $failedDirs));
            }
            $this->appendTaskLog($task, '目录与权限检查完成', 'success');
            $this->advanceTaskPhase($task);
            return;
        }

        if ($phaseKey === 'reload_config') {
            $this->reloadDatabaseConfig();
            $this->appendTaskLog($task, '数据库配置重载完成', 'success');
            $this->advanceTaskPhase($task);
            return;
        }

        if ($phaseKey === 'verify_db') {
            $this->verifyDatabaseConnection();
            $this->appendTaskLog($task, '数据库连接验证通过', 'success');
            $this->advanceTaskPhase($task);
            return;
        }

        if ($phaseKey === 'import_db') {
            $finished = $this->importDatabaseByBatch($task, 80);
            if ($finished) {
                $this->appendTaskLog($task, '数据库结构导入完成', 'success');
                $this->advanceTaskPhase($task);
            }
            return;
        }

        if ($phaseKey === 'create_admin') {
            $this->createAdminAccount($task['data']);
            $this->appendTaskLog($task, '管理员账户创建完成', 'success');
            $this->advanceTaskPhase($task);
            return;
        }

        if ($phaseKey === 'create_data') {
            $this->createSystemData($task['data']);
            $this->appendTaskLog($task, '系统基础数据写入完成', 'success');
            $this->advanceTaskPhase($task);
            return;
        }

        if ($phaseKey === 'verify_seed') {
            $this->verifyInstallSeedData();
            $this->appendTaskLog($task, '初始化数据校验通过', 'success');
            $this->advanceTaskPhase($task);
            return;
        }

        if ($phaseKey === 'create_lock') {
            $lockDir = dirname($this->installLockFile);
            if (!is_dir($lockDir)) {
                @mkdir($lockDir, 0775, true);
            }
            $result = file_put_contents($this->installLockFile, date('Y-m-d H:i:s'));
            if ($result === false) {
                throw new \Exception('无法创建安装锁文件，请检查 install 目录权限');
            }
            $this->appendTaskLog($task, '安装锁文件创建完成', 'success');
            $this->advanceTaskPhase($task);
            return;
        }

        if ($phaseKey === 'final_report') {
            $finalReport = PermissionService::generateReport();
            $this->appendTaskLog($task, '最终权限检查完成，状态：' . ($finalReport['status'] ?? 'ok'), 'success');
            $this->advanceTaskPhase($task);
            if ((int)($task['current_phase'] ?? 0) >= $totalPhases) {
                $task['status'] = 'completed';
                $task['progress'] = 100;
                $this->appendTaskLog($task, '系统安装成功', 'success');
                $this->cleanupInstallTaskFiles($task);
            }
            return;
        }

        throw new \Exception('未知安装步骤：' . $phaseKey);
    }

    private function importDatabaseByBatch(&$task, $batchSize = 80)
    {
        if (empty($task['sql_state']['prepared'])) {
            $sqlFile = app()->getRootPath() . 'install/install.sql';
            if (!file_exists($sqlFile)) {
                throw new \Exception('数据库脚本文件不存在');
            }

            $sql = file_get_contents($sqlFile);
            if ($sql === false) {
                throw new \Exception('读取 install.sql 失败');
            }

            if (!mb_check_encoding($sql, 'UTF-8')) {
                $sql = mb_convert_encoding($sql, 'UTF-8', 'auto');
            }
            $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql);
            $sql = preg_replace('/^--.*$/m', '', $sql);
            $sql = preg_replace('/\/\*!\d+[^*]*\*\/;?/s', '', $sql);
            $sql = preg_replace('/\/\*(?!\!).*?\*\//s', '', $sql);

            $statements = $this->splitSqlStatements($sql);
            $cacheFile = $this->getInstallTaskDir() . DIRECTORY_SEPARATOR . $task['task_id'] . '_sql.php';
            $cacheContent = "<?php\nreturn " . var_export($statements, true) . ";\n";
            if (file_put_contents($cacheFile, $cacheContent) === false) {
                throw new \Exception('无法写入安装SQL缓存文件');
            }

            $task['sql_state']['prepared'] = true;
            $task['sql_state']['cache_file'] = $cacheFile;
            $task['sql_state']['index'] = 0;
            $task['sql_state']['total'] = count($statements);
            $task['sql_state']['executed'] = 0;
            $task['sql_state']['skipped'] = 0;
            $this->appendTaskLog($task, 'SQL预处理完成，共 ' . count($statements) . ' 条语句');
        }

        $cacheFile = (string)$task['sql_state']['cache_file'];
        if (!file_exists($cacheFile)) {
            throw new \Exception('安装SQL缓存已丢失，请重新安装');
        }
        $statements = include $cacheFile;
        if (!is_array($statements)) {
            throw new \Exception('安装SQL缓存格式错误');
        }

        $total = (int)$task['sql_state']['total'];
        $index = (int)$task['sql_state']['index'];
        if ($total <= 0) {
            return true;
        }
        if ($index >= $total) {
            return true;
        }

        $maxSeconds = 8.0;
        $startTime = microtime(true);
        $maxStatementsPerRequest = max(10, (int)$batchSize);
        $processed = 0;
        $end = $index;

        try {
            Db::execute('SET FOREIGN_KEY_CHECKS = 0');
            Db::execute('SET SQL_MODE = ""');
        } catch (\Throwable $e) {
        }

        for ($i = $index; $i < $total; $i++) {
            if ($processed >= $maxStatementsPerRequest) {
                break;
            }
            if ((microtime(true) - $startTime) >= $maxSeconds) {
                break;
            }

            $statement = trim((string)$statements[$i]);
            if ($statement === '') {
                $end = $i + 1;
                $task['sql_state']['index'] = $end;
                continue;
            }

            try {
                if (preg_match('/^(LOCK TABLES|UNLOCK TABLES)/i', $statement)) {
                    $task['sql_state']['skipped']++;
                } elseif (preg_match('/^ALTER TABLE.*(?:DISABLE|ENABLE) KEYS/i', $statement)) {
                    $task['sql_state']['skipped']++;
                } elseif (preg_match('/^SET\s+/i', $statement)) {
                    try {
                        Db::execute($statement);
                        $task['sql_state']['executed']++;
                    } catch (\Throwable $ignored) {
                        $task['sql_state']['skipped']++;
                    }
                } elseif (preg_match('/^DROP\s+(TABLE|VIEW)\s+IF\s+EXISTS/i', $statement)) {
                    try {
                        Db::execute($statement);
                        $task['sql_state']['executed']++;
                    } catch (\Throwable $ignored) {
                        $task['sql_state']['skipped']++;
                    }
                } else {
                    Db::execute($statement);
                    $task['sql_state']['executed']++;
                }
            } catch (\Throwable $e) {
                $snippet = mb_substr($statement, 0, 180, 'UTF-8');
                throw new \Exception('SQL执行失败（第' . ($i + 1) . '条）：' . $e->getMessage() . '，SQL片段：' . $snippet);
            }

            $processed++;
            $end = $i + 1;
            $task['sql_state']['index'] = $end;
        }

        try {
            Db::execute('SET FOREIGN_KEY_CHECKS = 1');
        } catch (\Throwable $e) {
        }

        if ($end <= $index) {
            $end = min($index + 1, $total);
            $task['sql_state']['index'] = $end;
            $task['sql_state']['skipped']++;
        }
        $phaseStart = 3; 
        $phaseRatio = $total > 0 ? ($end / $total) : 1;
        $task['progress'] = min(99, (int)floor((($phaseStart + $phaseRatio) / max(1, (int)$task['total_phases'])) * 100));
        $this->appendTaskLog(
            $task,
            '数据库导入进度：' . $end . '/' . $total . '（已执行 ' . (int)$task['sql_state']['executed'] . '，跳过 ' . (int)$task['sql_state']['skipped'] . '）'
        );

        return ($end >= $total);
    }

    private function getInstallPhases()
    {
        return array(
            array('key' => 'prepare_permissions', 'name' => '初始化目录与权限'),
            array('key' => 'reload_config', 'name' => '重载数据库配置'),
            array('key' => 'verify_db', 'name' => '验证数据库连接'),
            array('key' => 'import_db', 'name' => '导入数据库结构'),
            array('key' => 'create_admin', 'name' => '创建管理员账户'),
            array('key' => 'create_data', 'name' => '写入系统基础数据'),
            array('key' => 'verify_seed', 'name' => '校验初始化数据'),
            array('key' => 'create_lock', 'name' => '创建安装锁文件'),
            array('key' => 'final_report', 'name' => '完成安装收尾')
        );
    }

    private function verifyInstallSeedData()
    {
        try {
            $checks = array(
                'admin_roles' => 1,
                'system_config' => 1,
                'payment_methods' => 1
            );

            $failed = array();
            foreach ($checks as $table => $minRows) {
                try {
                    $count = (int)Db::name($table)->count();
                } catch (\Throwable $e) {
                    $failed[] = $table . '表不存在';
                    continue;
                }
                if ($count < $minRows) {
                    $failed[] = $table . '数据为空';
                }
            }

            if (!empty($failed)) {
                throw new \Exception('初始化数据校验失败：' . implode('，', $failed));
            }
        } catch (\Throwable $e) {
            throw new \Exception('初始化数据校验失败：' . $e->getMessage());
        }
    }

    private function getTaskCurrentStepName($task)
    {
        $phases = $this->getInstallPhases();
        $index = (int)($task['current_phase'] ?? 0);
        if (($task['status'] ?? '') === 'completed') {
            return '安装完成';
        }
        if (isset($phases[$index])) {
            return $phases[$index]['name'];
        }
        return '处理中';
    }

    private function advanceTaskPhase(&$task)
    {
        $task['current_phase'] = (int)$task['current_phase'] + 1;
        $task['updated_at'] = time();
        $total = max(1, (int)$task['total_phases']);
        $task['progress'] = min(100, (int)floor(((int)$task['current_phase'] / $total) * 100));
    }

    private function appendTaskLog(&$task, $message, $type = 'info')
    {
        if (!isset($task['logs']) || !is_array($task['logs'])) {
            $task['logs'] = array();
        }
        $task['logs'][] = array(
            'time' => date('H:i:s'),
            'type' => $type,
            'message' => $message
        );
        if (count($task['logs']) > 400) {
            $task['logs'] = array_slice($task['logs'], -400);
        }
        $task['updated_at'] = time();
    }

    private function getInstallTaskDir()
    {
        $dir = app()->getRuntimePath() . 'install_task';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }

    private function getInstallTaskFile($taskId)
    {
        $safeTaskId = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$taskId);
        return $this->getInstallTaskDir() . DIRECTORY_SEPARATOR . $safeTaskId . '.json';
    }

    private function saveInstallTask($task)
    {
        $file = $this->getInstallTaskFile((string)$task['task_id']);
        $json = json_encode($task, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        if ($json === false) {
            $json = '{"status":"failed","error":"install_task_json_encode_failed"}';
        }
        file_put_contents($file, $json, LOCK_EX);
    }

    private function loadInstallTask($taskId)
    {
        $file = $this->getInstallTaskFile((string)$taskId);
        if (!file_exists($file)) {
            return null;
        }
        $raw = file_get_contents($file);
        if ($raw === false || $raw === '') {
            return null;
        }
        $task = json_decode($raw, true);
        return is_array($task) ? $task : null;
    }

    private function cleanupInstallTaskFiles($task)
    {
        $cacheFile = (string)($task['sql_state']['cache_file'] ?? '');
        if ($cacheFile !== '' && file_exists($cacheFile)) {
            @unlink($cacheFile);
        }
    }
    
    /**
     * 安装完成
     */
    public function complete()
    {
        if (!$this->isInstalled()) {
            return redirect('/index/install');
        }
        
        $this->clearAllSessions();
        
        View::assign([
            'title' => '安装完成',
            'step' => 5,
            'total_steps' => 5,
            'admin_url' => '/admin',
            'system_info' => [
                'site_name' => '系统名称',
                'site_url' => request()->domain(),
                'admin_username' => 'admin'
            ]
        ]);
        
        return View::fetch('install/complete');
    }
    
    /**
     * 清除旧登录凭据
     */
    private function clearAllSessions()
    {
        try {
            foreach (['agent_token', 'admin_login_agent'] as $cookieName) {
                if (isset($_COOKIE[$cookieName])) {
                    setcookie($cookieName, '', time() - 3600, '/');
                }
            }
        } catch (\Exception $e) {
        }
    }

    public function verifyAuthCode()
    {
        if ($this->isInstalled()) {
            return json([
                'code' => 0,
                'msg' => '系统已安装，无法验证授权码'
            ]);
        }
        
        $authCode = input('post.auth_code', '');
        
        if (empty($authCode)) {
            return json([
                'code' => 0,
                'msg' => '请输入授权码'
            ]);
        }
        
        try {
            if (!defined('AUTH_CORE_LOADED')) {
                define('AUTH_CORE_LOADED', true);
            }
            

            $authCorePath = dirname(app()->getAppPath()) . '/auth/auth_core.php';
            
            if (!file_exists($authCorePath)) {
                return json([
                    'code' => 0,
                    'msg' => '授权核心文件不存在，请检查系统文件完整性'
                ]);
            }
            
            require_once $authCorePath;
            

            $versionFile = app()->getRootPath() . 'version.php';
            $versionInfo = array('version' => '1.0.0');
            
            if (file_exists($versionFile)) {
                $versionInfo = include $versionFile;
            }
            
            $currentVersion = isset($versionInfo['version']) ? $versionInfo['version'] : '1.0.0';
            

            $authCore = new \WXHKAuthCore($authCode, $currentVersion);
            

            $authCore->clearOfflineAuth();
            

            $result = $authCore->forceRemoteAuth();
            
            if ($result['success']) {

                $message = isset($result['message']) ? $result['message'] : '';
                if (strpos($message, '离线备用模式') !== false || strpos($message, '离线') !== false) {
                    return json(array(
                        'code' => 0,
                        'msg' => '安装时必须进行在线授权验证，请检查网络连接或授权码是否正确'
                    ));
                }
                
                $domain = isset($result['data']['domain']) ? $result['data']['domain'] : '';
                $expires_at = isset($result['data']['expires_at']) ? $result['data']['expires_at'] : '';
                
                return json(array(
                    'code' => 1,
                    'msg' => '授权码验证成功',
                    'data' => array(
                        'domain' => $domain,
                        'expires_at' => $expires_at
                    )
                ));
            } else {
                $message = isset($result['message']) ? $result['message'] : '授权码验证失败';
                return json(array(
                    'code' => 0,
                    'msg' => $message
                ));
            }
            
        } catch (\Exception $e) {
            error_log('授权码验证异常: ' . $e->getMessage());
            return json([
                'code' => 0,
                'msg' => '验证过程发生错误：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 检查是否已经安装
     */
    private function isInstalled()
    {
        return file_exists($this->installLockFile);
    }
    
    /**
     * 显示已安装提示
     */
    private function showAlreadyInstalled()
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>系统已安装</title>
    <style>
        body { font-family: "Microsoft YaHei", Arial, sans-serif; background: #f5f7fa; margin: 0; padding: 50px; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 2px 12px rgba(0,0,0,0.1); text-align: center; }
        h1 { color: #67C23A; margin-bottom: 20px; }
        p { color: #606266; line-height: 1.6; margin-bottom: 15px; }
        .btn { display: inline-block; padding: 10px 20px; background: #409EFF; color: #fff; text-decoration: none; border-radius: 4px; margin: 5px; }
        .btn:hover { background: #66b1ff; }
        .tips { background: #fff6f7; border: 1px solid #fbc4c4; padding: 15px; border-radius: 4px; margin: 20px 0; color: #f56c6c; }
    </style>
</head>
<body>
    <div class="container">
        <h1>✓ 系统已安装</h1>
        <p>系统已经成功安装，无需重复安装。</p>
        <div class="tips">
            <strong>提示：</strong>如需重新安装，请删除 <code>install/install.lock</code> 文件。
        </div>
        <a href="/" class="btn">访问首页</a>
        <a href="/admin" class="btn">管理后台</a>
    </div>
</body>
</html>';
        return response($html);
    }
    

    private function checkEnvironment()
    {
        $checks = array(
            'php_version' => array(
                'name' => 'PHP版本',
                'required' => '7.4.0',
                'current' => PHP_VERSION,
                'status' => version_compare(PHP_VERSION, '7.4.0', '>='),
                'type' => 'required'
            ),
            'pdo' => array(
                'name' => 'PDO扩展',
                'required' => '支持',
                'current' => extension_loaded('pdo') ? '已安装' : '未安装',
                'status' => extension_loaded('pdo'),
                'type' => 'required'
            ),
            'pdo_mysql' => array(
                'name' => 'PDO_MySQL扩展',
                'required' => '支持',
                'current' => extension_loaded('pdo_mysql') ? '已安装' : '未安装',
                'status' => extension_loaded('pdo_mysql'),
                'type' => 'required'
            ),
            'mbstring' => array(
                'name' => 'mbstring扩展',
                'required' => '支持',
                'current' => extension_loaded('mbstring') ? '已安装' : '未安装',
                'status' => extension_loaded('mbstring'),
                'type' => 'required'
            ),
            'openssl' => array(
                'name' => 'openssl扩展',
                'required' => '支持',
                'current' => extension_loaded('openssl') ? '已安装' : '未安装',
                'status' => extension_loaded('openssl'),
                'type' => 'required'
            ),
            'curl' => array(
                'name' => 'curl扩展',
                'required' => '支持',
                'current' => extension_loaded('curl') ? '已安装' : '未安装',
                'status' => extension_loaded('curl'),
                'type' => 'required'
            ),
            'fileinfo' => array(
                'name' => 'fileinfo扩展',
                'required' => '支持',
                'current' => extension_loaded('fileinfo') ? '已安装' : '未安装',
                'status' => extension_loaded('fileinfo'),
                'type' => 'required',
                'description' => '用于文件类型检测，确保上传文件的安全性'
            ),
            'gd' => array(
                'name' => 'GD扩展',
                'required' => '支持',
                'current' => extension_loaded('gd') ? '已安装' : '未安装',
                'status' => extension_loaded('gd'),
                'type' => 'required',
                'description' => '用于图片处理（海报生成、验证码等）'
            ),
            'exec_function' => array(
                'name' => 'exec函数',
                'required' => '启用',
                'current' => $this->checkExecFunction() ? '已启用' : '已禁用',
                'status' => $this->checkExecFunction(),
                'type' => 'optional',
                'description' => '用于执行系统命令（数据库备份、定时任务等）'
            )
        );
        
        // 检查目录权限
        $directories = array(
            '/runtime',
            '/config',
            '/public/uploads'
        );
        
        foreach ($directories as $dir) {
            $path = app()->getRootPath() . ltrim($dir, '/');
            $writable = false;
            if (is_dir($path)) {
                $writable = is_writable($path);
            } else {
                $writable = @mkdir($path, 0775, true);
                if ($writable) {
                    $writable = is_writable($path);
                }
            }
            $checks['dir_' . str_replace('/', '_', $dir)] = array(
                'name' => $dir . ' 目录',
                'required' => '可写',
                'current' => $writable ? '可写' : '不可写',
                'status' => $writable,
                'type' => 'required'
            );
        }
        
        // 检查是否所有必需项都通过
        $required_passed = true;
        foreach ($checks as $check) {
            if ($check['type'] === 'required' && !$check['status']) {
                $required_passed = false;
                break;
            }
        }
        
        $checks['can_continue'] = $required_passed;
        
        return $checks;
    }
    
    /**
     * 检查 exec 函数是否可用
     */
    private function checkExecFunction()
    {
        // 检查函数是否存在
        if (!function_exists('exec')) {
            return false;
        }
        
        $disabled = ini_get('disable_functions');
        if ($disabled) {
            $disabledFunctions = array_map('trim', explode(',', $disabled));
            if (in_array('exec', $disabledFunctions)) {
                return false;
            }
        }
        
        // 尝试执行一个简单的命令来验证
        try {
            $output = array();
            $returnVar = 0;
            @exec('echo test', $output, $returnVar);
            
            // 如果能正常执行并返回结果，说明 exec 可用
            return ($returnVar === 0 && !empty($output));
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * 测试数据库连接
     */
    private function testDatabaseConnection($config)
    {
        try {
            $config = $this->normalizeDatabaseInput($config);
            if ($config['database'] === '' || $config['username'] === '') {
                return array('success' => false, 'error' => '数据库名和用户名不能为空');
            }
            if (!preg_match('/^[A-Za-z0-9_\\-]+$/', $config['database'])) {
                return array('success' => false, 'error' => '数据库名仅支持字母、数字、下划线、中划线');
            }

            // 第一步：测试服务器连接（不指定数据库）
            $dsn = "mysql:host={$config['hostname']};port={$config['hostport']};charset=utf8mb4";
            $pdo = new \PDO($dsn, $config['username'], $config['password']);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            
            // 第二步：检查数据库是否存在
            $stmt = $pdo->prepare('SHOW DATABASES LIKE ?');
            $stmt->execute([$config['database']]);
            $dbExists = $stmt->rowCount() > 0;
            
            if (!$dbExists) {
                // 数据库不存在，尝试创建
                try {
                    $dbName = str_replace('`', '``', $config['database']);
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
                    error_log("数据库 {$config['database']} 不存在，已自动创建");
                } catch (\Exception $e) {
                    // 如果没有创建数据库的权限，返回友好提示
                    return array(
                        'success' => false, 
                        'error' => "数据库 '{$config['database']}' 不存在，且当前用户没有创建数据库的权限。请手动创建数据库或使用具有CREATE权限的用户。"
                    );
                }
            }
            
            // 第三步：测试连接到指定数据库
            $dsn = "mysql:host={$config['hostname']};port={$config['hostport']};dbname={$config['database']};charset=utf8mb4";
            $pdo = new \PDO($dsn, $config['username'], $config['password']);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            
            return array('success' => true, 'db_created' => !$dbExists);
            
        } catch (\Exception $e) {
            // 将技术错误转换为用户友好的提示
            $userFriendlyError = $this->translateDatabaseError($e->getMessage());
            return array('success' => false, 'error' => $userFriendlyError);
        }
    }
    

    private function translateDatabaseError($errorMessage)
    {
        $errorMap = array(
            'Access denied' => '数据库用户名或密码错误，请检查登录凭据',
            'Unknown database' => '指定的数据库不存在，请先创建数据库或检查数据库名称',
            'Connection refused' => '无法连接到数据库服务器，请检查数据库服务是否启动',
            'Connection timed out' => '数据库连接超时，请检查数据库服务器地址和端口',
            'No route to host' => '无法访问数据库服务器，请检查服务器地址和网络连接',
            'php_network_getaddresses' => '无法解析数据库主机地址，请检查主机名是否正确',
            'Unknown MySQL server host' => '数据库主机地址无效，请检查主机名或IP地址',
            'Too many connections' => '数据库连接数已满，请稍后重试或联系管理员',
            'Can\'t connect to MySQL server' => '无法连接到MySQL服务器，请检查服务器状态和端口配置'
        );
        
        foreach ($errorMap as $keyword => $friendlyMessage) {
            if (strpos($errorMessage, $keyword) !== false) {
                return $friendlyMessage;
            }
        }
        
        return '数据库连接失败，请检查以下项目：1) 数据库服务器地址和端口是否正确；2) 用户名和密码是否正确；3) 数据库是否存在；4) 数据库服务是否正常运行';
    }
    

    private function saveDatabaseConfig($config)
    {
        try {
            $config = $this->normalizeDatabaseInput($config);
            $envFile = app()->getRootPath() . '.env';
        

        if ($this->hasValidDatabaseConfig()) {

            if (file_exists($envFile)) {
                $envBackupFile = app()->getRootPath() . '.env_backup_' . date('YmdHis');
                copy($envFile, $envBackupFile);
            }
        }
        
        $envContent = array(
            '[DATABASE]',
            'TYPE = mysql',
            'HOSTNAME = ' . $this->formatEnvValue($config['hostname']),
            'DATABASE = ' . $this->formatEnvValue($config['database']),
            'USERNAME = ' . $this->formatEnvValue($config['username']),
            'PASSWORD = ' . $this->formatEnvValue($config['password']),
            'HOSTPORT = ' . $this->formatEnvValue($config['hostport']),
            'CHARSET = utf8mb4',
            'DEBUG = false',
            '',
            '[LANG]',
            'default_lang = zh-cn'
        );
        
        $result = file_put_contents($envFile, implode("\n", $envContent));
        if ($result === false) {
            throw new \Exception('无法写入 .env 文件，请检查文件权限');
        }

        $this->writeDatabasePhpConfig($config);
        
        PermissionService::setSensitiveFilePermissions();

            if (function_exists('opcache_reset')) {
                opcache_reset();
            }
            
            if (class_exists('\think\facade\Env')) {
                \think\facade\Env::load(app()->getRootPath() . '.env');
            }

            session('install_db_config', [
                'type' => 'mysql',
                'hostname' => trim((string)($config['hostname'] ?? 'localhost')),
                'database' => trim((string)($config['database'] ?? '')),
                'username' => trim((string)($config['username'] ?? '')),
                'password' => (string)($config['password'] ?? ''),
                'hostport' => trim((string)($config['hostport'] ?? '3306')),
                'charset' => 'utf8mb4',
                'prefix' => ''
            ]);
        } catch (\Exception $e) {
            error_log('保存数据库配置失败: ' . $e->getMessage());
            throw new \Exception('保存数据库配置失败: ' . $e->getMessage());
        }
    }

    private function writeDatabasePhpConfig($config)
    {
        $databaseConfigFile = app()->getConfigPath() . 'database.php';
        if (!file_exists($databaseConfigFile)) {
            throw new \Exception('配置文件不存在: ' . $databaseConfigFile);
        }

        $content = file_get_contents($databaseConfigFile);
        if ($content === false) {
            throw new \Exception('读取数据库配置文件失败');
        }


        $replaceMap = [
            'hostname' => "'" . $this->escapePhpString((string)($config['hostname'] ?? 'localhost')) . "'",
            'hostport' => "'" . $this->escapePhpString((string)($config['hostport'] ?? '3306')) . "'",
            'database' => "'" . $this->escapePhpString((string)($config['database'] ?? '')) . "'",
            'username' => "'" . $this->escapePhpString((string)($config['username'] ?? '')) . "'",
            'password' => "'" . $this->escapePhpString((string)($config['password'] ?? '')) . "'",
        ];

        foreach ($replaceMap as $key => $value) {
            $pattern = "/('" . preg_quote($key, '/') . "'\\s*=>\\s*)([^,]+)(,)/u";
            $content = preg_replace($pattern, '$1' . $value . '$3', $content, 1);
        }

        $writeResult = file_put_contents($databaseConfigFile, $content);
        if ($writeResult === false) {
            throw new \Exception('写入数据库配置文件失败，请检查文件权限');
        }
    }


    private function escapePhpString($value)
    {
        return str_replace(["\\", "'"], ["\\\\", "\\'"], (string)$value);
    }

    private function formatEnvValue($value)
    {
        $value = (string)$value;
        $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
        return '"' . $escaped . '"';
    }

    private function normalizeDatabaseInput($config)
    {
        return array(
            'hostname' => trim((string)($config['hostname'] ?? 'localhost')),
            'hostport' => trim((string)($config['hostport'] ?? '3306')),
            'database' => trim((string)($config['database'] ?? '')),
            'username' => trim((string)($config['username'] ?? '')),
            'password' => (string)($config['password'] ?? ''),
        );
    }
    
    private function createSystem($data)
    {
        try {

            $permissionResult = PermissionService::autoFix();
            
            $dirResults = $permissionResult['directories'];
            $failedDirs = array();
            foreach ($dirResults as $dir => $result) {
                if (!$result['success'] || !$result['writable']) {
                    $failedDirs[] = $dir;
                }
            }
            
            if (!empty($failedDirs)) {
                $errorMsg = "目录初始化失败：\n" . implode("\n", $failedDirs);
                throw new \Exception($errorMsg);
            }
            
            // 记录权限检查结果
            $check = $permissionResult['check'];
            if ($check['error_count'] > 0) {
                error_log("权限检查发现 {$check['error_count']} 个错误");
                foreach ($check['issues'] as $issue) {
                    if ($issue['severity'] === 'error') {
                        error_log("权限错误: {$issue['message']}");
                    }
                }
            }
            
            if ($check['warning_count'] > 0) {
                error_log("权限检查发现 {$check['warning_count']} 个警告");
            }
            
            error_log('目录和权限初始化完成');
            

            $this->reloadDatabaseConfig();
            $this->verifyDatabaseConnection();
            $this->importDatabase();
            $this->createAdminAccount($data);
            $this->createSystemData($data);
            $lockDir = dirname($this->installLockFile);
            if (!is_dir($lockDir)) {
                @mkdir($lockDir, 0775, true);
            }
            
            $result = file_put_contents($this->installLockFile, date('Y-m-d H:i:s'));
            if ($result === false) {
                throw new \Exception('无法创建安装锁文件，请检查 install 目录权限');
            }
            
            // 7. 最终权限检查和报告
            $finalReport = PermissionService::generateReport();
            // 如果有问题，记录详细信息
            if (!empty($finalReport['issues'])) {
                foreach ($finalReport['issues'] as $issue) {
                    error_log("权限问题: {$issue['message']}");
                }
            }
            
            return array(
                'success' => true,
                'permission_report' => $finalReport
            );
        } catch (\Exception $e) {
            return array('success' => false, 'error' => '安装失败：' . $e->getMessage());
        }
    }
    
    /**
     * 重新加载数据库配置
     */
    private function reloadDatabaseConfig()
    {
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        
        if (method_exists('\think\facade\Cache', 'clear')) {
            \think\facade\Cache::clear();
        }
        
        \think\facade\Config::load(app()->getConfigPath() . 'database.php', 'database');

        if (class_exists('\think\facade\Env')) {
            \think\facade\Env::load(app()->getRootPath() . '.env');
        }

        $installDbConfig = $this->getInstallDatabaseConfig();
        if (!empty($installDbConfig['hostname'])) {
            \think\facade\Config::set([
                'connections' => [
                    'mysql' => $installDbConfig
                ],
                'default' => 'mysql'
            ], 'database');
        }
        usleep(100000); 
        
        $dbConfig = $this->getInstallDatabaseConfig();
        if (empty($dbConfig) || empty($dbConfig['hostname'])) {
            throw new \Exception('数据库配置加载失败，请检查配置文件');
        }
    }
    
    /**
     * 验证数据库连接
     */
    private function verifyDatabaseConnection()
    {
        try {
            $dbConfig = $this->getInstallDatabaseConfig();
            if (empty($dbConfig) || empty($dbConfig['hostname'])) {
                throw new \Exception('数据库配置不完整');
            }

            $hostname = trim((string)($dbConfig['hostname'] ?? ''));
            $hostport = trim((string)($dbConfig['hostport'] ?? '3306'));
            $database = trim((string)($dbConfig['database'] ?? ''));
            $username = trim((string)($dbConfig['username'] ?? ''));
            $password = (string)($dbConfig['password'] ?? '');

            if ($username === '') {
                throw new \Exception('数据库用户名为空');
            }
            if ($database === '') {
                throw new \Exception('数据库名为空');
            }

            $dsn = "mysql:host={$hostname};port={$hostport};dbname={$database};charset=utf8mb4";
            $pdo = new \PDO($dsn, $username, $password);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $stmt = $pdo->query('SELECT 1 AS test');
            $row = $stmt ? $stmt->fetch(\PDO::FETCH_ASSOC) : null;
            if (empty($row) || !isset($row['test'])) {
                throw new \Exception('数据库连接测试失败');
            }
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            throw new \Exception('数据库连接失败：' . $errorMsg . '。请检查数据库配置是否正确。');
        }
    }

    private function getInstallDatabaseConfig()
    {

        $sessionConfig = session('install_db_config');
        if (is_array($sessionConfig) && !empty($sessionConfig['hostname'])) {
            return $sessionConfig;
        }


        $envFile = app()->getRootPath() . '.env';
        if (file_exists($envFile)) {
            $parsed = @parse_ini_file($envFile, true, INI_SCANNER_RAW);
            if (is_array($parsed) && isset($parsed['DATABASE']) && is_array($parsed['DATABASE'])) {
                $db = $parsed['DATABASE'];
                if (!empty($db['HOSTNAME'])) {
                    return [
                        'type' => strtolower((string)($db['TYPE'] ?? 'mysql')),
                        'hostname' => trim((string)($db['HOSTNAME'] ?? 'localhost')),
                        'database' => trim((string)($db['DATABASE'] ?? '')),
                        'username' => trim((string)($db['USERNAME'] ?? '')),
                        'password' => (string)($db['PASSWORD'] ?? ''),
                        'hostport' => trim((string)($db['HOSTPORT'] ?? '3306')),
                        'charset' => trim((string)($db['CHARSET'] ?? 'utf8mb4')),
                        'prefix' => ''
                    ];
                }
            }
        }

        // 3) 回退系统配置
        return config('database.connections.mysql');
    }
    
    /**
     * 导入数据库结构
     */
    private function importDatabase()
    {
        $sqlFile = app()->getRootPath() . 'install/install.sql';
        if (!file_exists($sqlFile)) {
            throw new \Exception('数据库脚本文件不存在');
        }
        
        try {
            // 禁用外键检查和自动提交
            Db::execute('SET FOREIGN_KEY_CHECKS = 0');
            Db::execute('SET AUTOCOMMIT = 0');
            Db::execute('SET SQL_MODE = ""'); 
            
            $sql = file_get_contents($sqlFile);
            if (!mb_check_encoding($sql, 'UTF-8')) {
                error_log('警告: install.sql 文件编码不是UTF-8，尝试自动转换');
                $sql = mb_convert_encoding($sql, 'UTF-8', 'auto');
            }
            $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql);
            $sql = preg_replace('/^--.*$/m', '', $sql);
            $sql = preg_replace('/\/\*!\d+[^*]*\*\/;?/s', '', $sql);
            

            $sql = preg_replace('/\/\*(?!\!).*?\*\//s', '', $sql);
            

            $statements = $this->splitSqlStatements($sql);
            
            $totalStatements = count($statements);
            error_log("准备执行 {$totalStatements} 条SQL语句");
            

            Db::startTrans();
            
            $executedCount = 0;
            $skippedCount = 0;
            $lineNumber = 0;
            $lastProgressLog = 0;
            
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (empty($statement)) continue;
                
                $lineNumber++;
                

                if ($lineNumber - $lastProgressLog >= 50) {
                    $progress = round(($lineNumber / $totalStatements) * 100, 1);
                    error_log("安装进度: {$progress}% ({$lineNumber}/{$totalStatements})");
                    $lastProgressLog = $lineNumber;
                }
                
                if (preg_match('/^(LOCK TABLES|UNLOCK TABLES)/i', $statement)) {
                    $skippedCount++;
                    continue;
                }
                
                if (preg_match('/^ALTER TABLE.*(?:DISABLE|ENABLE) KEYS/i', $statement)) {
                    $skippedCount++;
                    continue;
                }
                
                if (preg_match('/^SET\s+/i', $statement)) {
                    try {
                        Db::execute($statement);
                        $executedCount++;
                    } catch (\Exception $e) {
                        $skippedCount++;
                    }
                    continue;
                }
                
                if (preg_match('/^DROP\s+(TABLE|VIEW)\s+IF\s+EXISTS/i', $statement)) {
                    try {
                        Db::execute($statement);
                        $executedCount++;
                    } catch (\Exception $e) {
                    }
                    continue;
                }

                try {
                    Db::execute($statement);
                    $executedCount++;
                } catch (\Exception $e) {
                    $errorMsg = "SQL执行失败: " . $e->getMessage();
                    
                    $tableName = 'unknown';
                    if (preg_match('/(?:INSERT INTO|CREATE TABLE|ALTER TABLE|DROP TABLE|CREATE VIEW)\s+`?(\w+)`?/i', $statement, $matches)) {
                        $tableName = $matches[1];
                    }
                    
                    // 提取SQL类型
                    $sqlType = 'UNKNOWN';
                    if (preg_match('/^(\w+)/', $statement, $matches)) {
                        $sqlType = strtoupper($matches[1]);
                    }

                    // 回滚事务
                    Db::rollback();
                    Db::execute('SET FOREIGN_KEY_CHECKS = 1');
                    Db::execute('SET AUTOCOMMIT = 1');
                    
                    // 构造友好的错误提示
                    $shortStatement = mb_substr($statement, 0, 100, 'UTF-8');
                    throw new \Exception("数据库安装失败！\n语句编号: {$lineNumber}\n表: {$tableName}\n错误: {$e->getMessage()}\nSQL片段: {$shortStatement}...\n\n请检查日志文件获取完整错误信息");
                }
            }
            
            // 提交事务
            Db::commit();
            Db::execute('SET FOREIGN_KEY_CHECKS = 1');
            Db::execute('SET AUTOCOMMIT = 1');
            
        } catch (\Exception $e) {
            Db::rollback();
            
            try {
                Db::execute('SET FOREIGN_KEY_CHECKS = 1');
                Db::execute('SET AUTOCOMMIT = 1');
            } catch (\Exception $ignored) {}
            
            throw $e;
        }
    }
    
    /**
     * 创建管理员账户
     */
    private function createAdminAccount($data)
    {
        $adminUsername = trim((string)($data['admin_username'] ?? $data['username'] ?? 'admin'));
        $adminPasswordRaw = (string)($data['admin_password'] ?? $data['password'] ?? '');
        $adminNickname = trim((string)($data['admin_name'] ?? ''));
        if ($adminNickname === '') {
            $adminNickname = $adminUsername !== '' ? $adminUsername : '超级管理员';
        }

        if ($adminUsername === '') {
            throw new \Exception('管理员用户名不能为空');
        }
        if ($adminPasswordRaw === '') {
            throw new \Exception('管理员密码不能为空');
        }

        // 检查admins表是否为空
        $existingAdminCount = Db::table('admins')->count();
        if ($existingAdminCount > 0) {
            error_log("警告：admins表不为空，已有 {$existingAdminCount} 个管理员");
        }
        // 清空管理员和角色关系，确保可固定写入ID=1
        try {
            Db::execute('TRUNCATE TABLE admin_role_relation');
            Db::execute('TRUNCATE TABLE admins');
            Db::execute('ALTER TABLE admins AUTO_INCREMENT = 1');
            error_log("已TRUNCATE admins/admin_role_relation并重置AUTO_INCREMENT");
        } catch (\Exception $e) {
            // 某些环境禁用TRUNCATE时，回退到DELETE
            Db::table('admin_role_relation')->delete(true);
            Db::table('admins')->delete(true);
            try {
                Db::execute('ALTER TABLE admins AUTO_INCREMENT = 1');
            } catch (\Exception $ignored) {
            }
            error_log("TRUNCATE失败，已回退DELETE清空管理员数据: " . $e->getMessage());
        }
        
        $salt = substr(md5(time()), 0, 10);
        $password = md5($adminPasswordRaw . $salt);
        $currentTime = time();
        
        // 插入管理员账户（强制ID=1，避免AUTO_INCREMENT异常导致ID漂移）
        Db::table('admins')->insert([
            'id' => 1,
            'username' => $adminUsername,
            'password' => $password,
            'salt' => $salt,
            'nickname' => $adminNickname,
            'status' => 1,
            'create_time' => $currentTime,
            'update_time' => $currentTime
        ]);
        $adminId = 1;
        
        if ($adminId != 1) {
            throw new \Exception("管理员ID必须是1，但实际创建的ID是 {$adminId}。请检查数据库AUTO_INCREMENT设置。");
        }


        try {
            Db::execute('ALTER TABLE admins AUTO_INCREMENT = 2');
        } catch (\Exception $e) {
           
        }

        Db::table('admin_role_relation')->where('admin_id', $adminId)->delete();
        

        Db::table('admin_role_relation')->insert([
            'admin_id' => $adminId,
            'role_id' => 1, 
            'create_time' => $currentTime
        ]);
        error_log("创建管理员角色关联成功，admin_id: {$adminId}, role_id: 1");
        
        return $adminId;
    }
    
    /**
     * 创建系统基础数据
     */
    private function createSystemData($data)
    {
        if (!empty($data['site_name'])) {
            $exists = Db::table('system_config')->where('config_key', 'site_name')->find();
            if ($exists) {
                Db::table('system_config')
                    ->where('config_key', 'site_name')
                    ->update([
                        'config_value' => $data['site_name'],
                        'update_time' => time()
                    ]);
            }
        }
        
        if (!empty($data['auth_code'])) {
            $this->saveAuthCode($data['auth_code']);
        }
    }
    
    /**
     * 保存授权码到version.php文件
     */
    private function saveAuthCode($authCode)
    {
        try {
            $versionFile = app()->getRootPath() . 'version.php';
            
            // 读取现有的version.php文件
            if (file_exists($versionFile)) {
                $versionData = include($versionFile);
                
                if (isset($versionData['last_check_time']) && !is_numeric($versionData['last_check_time'])) {
                    $versionData['last_check_time'] = time();
                }
            } else {
                $versionData = array(
                    'version' => '1.9.0',
                    'auth_code' => '',
                    'release_time' => date('Y-m-d H:i:s'),
                    'expire_time' => '',
                    'update_expire_time' => date('Y-m-d H:i:s', strtotime('+1 year')),
                    'last_check_time' => time(),
                    'last_update_check' => date('Y-m-d H:i:s'),
                    'version_name' => 'v1.9.0',
                    'version_update_time' => date('Y-m-d H:i:s'),
                    'app_name' => '',
                    'agent_wechat' => '',
                );
            }
            
            // 更新授权码和检查时间
            $versionData['auth_code'] = $authCode;
            $versionData['last_update_check'] = date('Y-m-d H:i:s');
            
            // 生成新的PHP文件内容
            $phpContent = "<?php\n";
            $phpContent .= "/**\n";
            $phpContent .= " * 版本配置文件\n";
            $phpContent .= " * 授权信息更新时间: " . date('Y-m-d H:i:s') . "\n";
            $phpContent .= " */\n";
            $phpContent .= "return array(\n";
            
            foreach ($versionData as $key => $value) {
                // 特殊处理 last_check_time，保持为 time() 函数调用
                if ($key === 'last_check_time') {
                    $phpContent .= "    '{$key}' => time(),\n";
                } elseif (is_string($value)) {
                    // 字符串值用单引号包裹
                    $phpContent .= "    '{$key}' => '{$value}',\n";
                } elseif (is_numeric($value)) {
                    // 数字值不加引号
                    $phpContent .= "    '{$key}' => {$value},\n";
                } elseif (is_bool($value)) {
                    // 布尔值
                    $phpContent .= "    '{$key}' => " . ($value ? 'true' : 'false') . ",\n";
                } elseif (is_null($value)) {
                    // null 值
                    $phpContent .= "    '{$key}' => null,\n";
                } else {
                    // 其他类型转为字符串
                    $phpContent .= "    '{$key}' => '" . addslashes((string)$value) . "',\n";
                }
            }
            
            $phpContent .= ");\n";
            $phpContent .= "?>";
            
            // 写入文件
            $result = file_put_contents($versionFile, $phpContent);
            if ($result === false) {
                throw new \Exception('无法写入version.php文件');
            }
            
            error_log("授权码已保存到 version.php，授权码: {$authCode}");
            
        } catch (\Exception $e) {
            error_log('保存授权码失败: ' . $e->getMessage());
            throw new \Exception('保存授权码失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 检查是否已有有效的数据库配置
     */
    private function hasValidDatabaseConfig()
    {
        try {
            // 检查.env文件是否存在
            $envFile = app()->getRootPath() . '.env';
            if (!file_exists($envFile)) {
                return false;
            }
            
            // 尝试获取数据库配置
            $config = config('database.connections.mysql');
            
            // 检查关键配置项是否存在且不为空
            if (empty($config['hostname']) || 
                empty($config['database']) || 
                empty($config['username'])) {
                return false;
            }
            
            return true;
            
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * 智能分割SQL语句
     * 考虑字符串中的分号不作为分隔符
     */
    private function splitSqlStatements($sql)
    {
        $statements = array();
        $currentStatement = '';
        $inString = false;
        $stringChar = '';
        $inBacktick = false;
        $inBlockComment = false;

        $lines = preg_split("/\r\n|\n|\r/", (string)$sql);
        foreach ($lines as $line) {
            $length = strlen($line);
            for ($i = 0; $i < $length; $i++) {
                $char = $line[$i];
                $prevChar = $i > 0 ? $line[$i - 1] : '';
                $nextChar = $i < $length - 1 ? $line[$i + 1] : '';

                if ($inBlockComment) {
                    if ($char === '*' && $nextChar === '/') {
                        $inBlockComment = false;
                        $i++;
                    }
                    continue;
                }

                if (!$inString && !$inBacktick) {
                    if ($char === '#' ) {
                        break;
                    }
                    if ($char === '-' && $nextChar === '-' && ($i + 2 >= $length || ctype_space($line[$i + 2]))) {
                        break;
                    }
                    if ($char === '/' && $nextChar === '*' && ($i + 2 >= $length || $line[$i + 2] !== '!')) {
                        $inBlockComment = true;
                        $i++;
                        continue;
                    }
                }

                if ($char === '`' && $prevChar !== '\\' && !$inString) {
                    $inBacktick = !$inBacktick;
                }

                if (($char === "'" || $char === '"') && $prevChar !== '\\' && !$inBacktick) {
                    if (!$inString) {
                        $inString = true;
                        $stringChar = $char;
                    } elseif ($char === $stringChar) {
                        if ($nextChar === $stringChar) {
                            $currentStatement .= $char;
                            $i++;
                            continue;
                        }
                        $inString = false;
                        $stringChar = '';
                    }
                }

                if ($char === ';' && !$inString && !$inBacktick) {
                    $statement = trim($currentStatement);
                    if ($statement !== '') {
                        $statements[] = $statement;
                    }
                    $currentStatement = '';
                    continue;
                }

                $currentStatement .= $char;
            }

            if ($currentStatement !== '') {
                $currentStatement .= "\n";
            }
        }

        $statement = trim($currentStatement);
        if ($statement !== '') {
            $statements[] = $statement;
        }

        return $statements;
    }
    
}
