<?php
/**
 * 密码管理工具
 * 功能：重置管理员密码、生成加盐密码、验证密码等
 * 
 * 常用命令（复制使用）：
 * php password_tool.php help                    # 显示帮助
 * php password_tool.php config                  # 显示数据库配置
 * php password_tool.php list                    # 查看管理员列表
 * php password_tool.php reset admin 123456      # 重置admin密码为123456
 * php password_tool.php hash mypassword         # 生成加盐密码
 * php password_tool.php verify pass hash salt   # 验证密码
 */

// 检查是否在命令行运行
if (php_sapi_name() !== 'cli') {
    die('此脚本只能在命令行运行');
}

// 读取.env文件配置
function parseEnvFile($filePath) {
    $config = [];
    if (!file_exists($filePath)) {
        return $config;
    }
    
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $currentSection = '';
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // 跳过注释行
        if (empty($line) || $line[0] === '#' || $line[0] === ';') {
            continue;
        }
        
        // 处理节（section）
        if (preg_match('/^\[(.+)\]$/', $line, $matches)) {
            $currentSection = strtolower($matches[1]);
            continue;
        }
        
        // 处理键值对
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // 移除引号
            if (($value[0] === '"' && substr($value, -1) === '"') ||
                ($value[0] === "'" && substr($value, -1) === "'")) {
                $value = substr($value, 1, -1);
            }
            
            if ($currentSection) {
                $config[$currentSection][strtolower($key)] = $value;
            } else {
                $config[strtolower($key)] = $value;
            }
        }
    }
    
    return $config;
}

// 数据库配置（自动读取.env文件）
function getDatabaseConfig() {
    // 尝试读取.env文件
    $envFile = __DIR__ . '/.env';
    $envConfig = parseEnvFile($envFile);
    
    // 默认配置
    $defaultConfig = [
        'host' => 'localhost',
        'port' => 3306,
        'dbname' => 'root',
        'username' => 'root',
        'password' => 'root'
    ];
    
    // 如果找到数据库配置，使用.env文件的配置
    if (isset($envConfig['database'])) {
        $db = $envConfig['database'];
        return [
            'host' => $db['hostname'] ?? $defaultConfig['host'],
            'port' => (int)($db['hostport'] ?? $defaultConfig['port']),
            'dbname' => $db['database'] ?? $defaultConfig['dbname'],
            'username' => $db['username'] ?? $defaultConfig['username'],
            'password' => $db['password'] ?? $defaultConfig['password']
        ];
    }
    
    // 如果没有找到，使用默认配置
    return $defaultConfig;
}

// 创建数据库连接
function createConnection() {
    $config = getDatabaseConfig();
    try {
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        return $pdo;
    } catch (PDOException $e) {
        echo "❌ 数据库连接失败: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// 生成盐值
function generateSalt() {
    return substr(md5(time() . rand()), 0, 10);
}

// 生成加盐密码
function hashPassword($password, $salt = null) {
    if ($salt === null) {
        $salt = generateSalt();
    }
    return [
        'hash' => md5($password . $salt),
        'salt' => $salt
    ];
}

// 验证密码
function verifyPassword($inputPassword, $storedHash, $salt = '') {
    if (empty($salt)) {
        // 无盐MD5
        return md5($inputPassword) === $storedHash;
    } else {
        // 加盐MD5
        return md5($inputPassword . $salt) === $storedHash;
    }
}

// 显示帮助信息
function showHelp() {
    echo "密码管理工具使用说明：\n\n";
    echo "1. 重置管理员密码：\n";
    echo "   php password_tool.php reset admin用户名 新密码\n";
    echo "   例如: php password_tool.php reset admin admin123\n\n";
    
    echo "2. 生成加盐密码：\n";
    echo "   php password_tool.php hash 密码\n";
    echo "   例如: php password_tool.php hash mypassword\n\n";
    
    echo "3. 验证密码：\n";
    echo "   php password_tool.php verify 原始密码 密码哈希 [盐值]\n";
    echo "   例如: php password_tool.php verify admin123 hash_value salt_value\n\n";
    
    echo "4. 查看管理员信息：\n";
    echo "   php password_tool.php list\n\n";
    
    echo "5. 显示数据库配置：\n";
    echo "   php password_tool.php config\n\n";
    
    echo "6. 显示帮助：\n";
    echo "   php password_tool.php help\n\n";
}

// 显示数据库配置信息
function showConfig() {
    echo "🔧 数据库配置信息：\n\n";
    
    // 显示.env文件状态
    $envFile = __DIR__ . '/.env';
    if (file_exists($envFile)) {
        echo "📄 .env文件: 存在 ({$envFile})\n";
        $envConfig = parseEnvFile($envFile);
        if (isset($envConfig['database'])) {
            echo "✅ 数据库配置: 已找到\n\n";
            echo "📋 .env文件中的数据库配置：\n";
            foreach ($envConfig['database'] as $key => $value) {
                $displayValue = ($key === 'password') ? str_repeat('*', strlen($value)) : $value;
                echo "   " . strtoupper($key) . " = {$displayValue}\n";
            }
        } else {
            echo "❌ 数据库配置: 未找到\n";
        }
    } else {
        echo "❌ .env文件: 不存在\n";
    }
    
    echo "\n";
    
    // 显示实际使用的配置
    $config = getDatabaseConfig();
    echo "🎯 实际使用的配置：\n";
    echo "   主机: {$config['host']}\n";
    echo "   端口: {$config['port']}\n";
    echo "   数据库: {$config['dbname']}\n";
    echo "   用户名: {$config['username']}\n";
    echo "   密码: " . str_repeat('*', strlen($config['password'])) . "\n\n";
    
    // 测试连接
    try {
        $pdo = createConnection();
        echo "✅ 数据库连接测试: 成功\n";
    } catch (Exception $e) {
        echo "❌ 数据库连接测试: 失败 - " . $e->getMessage() . "\n";
    }
}

// 列出管理员信息
function listAdmins() {
    $pdo = createConnection();
    echo "✅ 数据库连接成功\n\n";
    
    $stmt = $pdo->query("SELECT id, username, password, salt, nickname, status, last_login_time FROM admins ORDER BY id");
    $admins = $stmt->fetchAll();
    
    if (empty($admins)) {
        echo "❌ 未找到管理员账户\n";
        return;
    }
    
    echo "📋 管理员账户列表：\n";
    echo str_repeat("-", 80) . "\n";
    printf("%-4s %-15s %-35s %-12s %-15s %-6s %s\n", "ID", "用户名", "密码哈希", "盐值", "昵称", "状态", "最后登录");
    echo str_repeat("-", 80) . "\n";
    
    foreach ($admins as $admin) {
        $lastLogin = $admin['last_login_time'] ? date('Y-m-d H:i', $admin['last_login_time']) : '从未登录';
        $status = $admin['status'] == 1 ? '正常' : '禁用';
        $salt = $admin['salt'] ?: '(无)';
        $hash = substr($admin['password'], 0, 32);
        
        printf("%-4s %-15s %-35s %-12s %-15s %-6s %s\n", 
            $admin['id'], 
            $admin['username'], 
            $hash, 
            $salt, 
            $admin['nickname'], 
            $status, 
            $lastLogin
        );
    }
    echo str_repeat("-", 80) . "\n";
}

// 重置管理员密码
function resetAdminPassword($username, $newPassword) {
    if (strlen($newPassword) < 6) {
        echo "❌ 密码长度不能少于6位\n";
        exit(1);
    }
    
    $pdo = createConnection();
    echo "✅ 数据库连接成功\n";
    
    // 查找管理员
    $stmt = $pdo->prepare("SELECT id, username, password, salt FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        echo "❌ 未找到用户名为 '{$username}' 的管理员\n";
        exit(1);
    }
    
    echo "📋 找到管理员信息：\n";
    echo "   ID: {$admin['id']}\n";
    echo "   用户名: {$admin['username']}\n";
    echo "   当前密码哈希: {$admin['password']}\n";
    echo "   当前盐值: " . ($admin['salt'] ?: '(空)') . "\n\n";
    
    // 生成新的盐值和密码哈希
    $result = hashPassword($newPassword);
    
    echo "🔧 生成新的密码信息：\n";
    echo "   新密码: {$newPassword}\n";
    echo "   新盐值: {$result['salt']}\n";
    echo "   新密码哈希: {$result['hash']}\n\n";
    
    // 确认操作
    echo "⚠️  确认要修改管理员 '{$username}' 的密码吗？(输入 yes 确认): ";
    $handle = fopen("php://stdin", "r");
    $confirmation = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($confirmation) !== 'yes') {
        echo "❌ 操作已取消\n";
        exit(0);
    }
    
    // 更新密码
    $stmt = $pdo->prepare("UPDATE admins SET password = ?, salt = ?, update_time = ? WHERE id = ?");
    $updateResult = $stmt->execute([$result['hash'], $result['salt'], time(), $admin['id']]);
    
    if ($updateResult) {
        echo "✅ 密码修改成功！\n\n";
        echo "🔑 新的登录信息：\n";
        echo "   用户名: {$username}\n";
        echo "   密码: {$newPassword}\n\n";
    } else {
        echo "❌ 密码修改失败\n";
        exit(1);
    }
}

// 主程序
if ($argc < 2) {
    showHelp();
    exit(1);
}

$command = $argv[1];

switch ($command) {
    case 'help':
    case '--help':
    case '-h':
        showHelp();
        break;
        
    case 'list':
        listAdmins();
        break;
        
    case 'reset':
        if ($argc < 4) {
            echo "❌ 参数不足\n";
            echo "用法: php password_tool.php reset 用户名 新密码\n";
            exit(1);
        }
        resetAdminPassword($argv[2], $argv[3]);
        break;
        
    case 'hash':
        if ($argc < 3) {
            echo "❌ 参数不足\n";
            echo "用法: php password_tool.php hash 密码\n";
            exit(1);
        }
        $result = hashPassword($argv[2]);
        echo "🔐 密码加盐结果：\n";
        echo "   原始密码: {$argv[2]}\n";
        echo "   盐值: {$result['salt']}\n";
        echo "   哈希值: {$result['hash']}\n";
        break;
        
    case 'verify':
        if ($argc < 4) {
            echo "❌ 参数不足\n";
            echo "用法: php password_tool.php verify 原始密码 哈希值 [盐值]\n";
            exit(1);
        }
        $password = $argv[2];
        $hash = $argv[3];
        $salt = $argc > 4 ? $argv[4] : '';
        
        $isValid = verifyPassword($password, $hash, $salt);
        echo "🔍 密码验证结果：\n";
        echo "   原始密码: {$password}\n";
        echo "   哈希值: {$hash}\n";
        echo "   盐值: " . ($salt ?: '(无)') . "\n";
        echo "   验证结果: " . ($isValid ? "✅ 匹配" : "❌ 不匹配") . "\n";
        break;
        
    case 'config':
        showConfig();
        break;
        
    default:
        echo "❌ 未知命令: {$command}\n\n";
        showHelp();
        exit(1);
}

echo "\n";
?>
