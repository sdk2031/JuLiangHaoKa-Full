<?php
/**
 * 模板文件直接访问处理器
 * 用于头部菜单模板加载，绕过复杂的路由系统
 */

// 设置错误报告
error_reporting(0);
ini_set('display_errors', 0);

// 获取请求的模板名称
$template = $_GET['t'] ?? '';

// 基本安全检查
if (empty($template)) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['code' => 404, 'msg' => 'Template parameter missing']);
    exit;
}

// 安全检查：只允许访问指定的模板文件
$allowedTemplates = [
    'tpl-message',
    'tpl-note', 
    'tpl-theme',
    'tpl-password',
    'tpl-lock-screen'
];

if (!in_array($template, $allowedTemplates)) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['code' => 404, 'msg' => 'Template not allowed']);
    exit;
}

// 检查模板文件是否存在
$templatePath = __DIR__ . '/static/easyweb/page/tpl/' . $template . '.html';
if (!file_exists($templatePath)) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['code' => 404, 'msg' => 'Template file not found']);
    exit;
}

// 读取并返回模板内容
$content = file_get_contents($templatePath);
if ($content === false) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['code' => 500, 'msg' => 'Failed to read template']);
    exit;
}

// 设置正确的内容类型并返回HTML
header('Content-Type: text/html; charset=utf-8');
echo $content;
?> 