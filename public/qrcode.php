<?php
/**
 * 真正的二维码生成接口
 * 使用phpqrcode库生成标准QR码
 */

// 引入phpqrcode库
require_once 'phpqrcode/phpqrcode.php';

// 设置响应头
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// 获取参数
$text = $_GET['text'] ?? $_POST['text'] ?? '';
$size = intval($_GET['size'] ?? $_POST['size'] ?? 200);
$format = $_GET['format'] ?? $_POST['format'] ?? 'png';

// 验证参数
if (empty($text)) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => '缺少text参数']);
    exit;
}

// 限制大小范围
$size = max(100, min(1000, $size));

try {
    // 计算矩阵点大小
    $matrixPointSize = max(1, min(10, intval($size / 25)));

    // 错误纠正级别
    $errorCorrectionLevel = 'H'; // 最高级别

    if ($format === 'base64') {
        // 生成到内存缓冲区
        ob_start();
        QRcode::png($text, false, $errorCorrectionLevel, $matrixPointSize, 2);
        $imageData = ob_get_contents();
        ob_end_clean();

        // 返回base64格式
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => 'data:image/png;base64,' . base64_encode($imageData),
            'method' => 'phpqrcode'
        ]);
    } else {
        // 直接输出PNG图片
        header('Content-Type: image/png');
        QRcode::png($text, false, $errorCorrectionLevel, $matrixPointSize, 2);
    }

} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => '生成二维码失败: ' . $e->getMessage()]);
}

?>
