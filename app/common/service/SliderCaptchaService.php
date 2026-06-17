<?php

namespace app\common\service;

use think\facade\Cache;

/**
 * 滑块验证码服务
 * 定制开发的精美滑块验证码系统 🆕
 */
class SliderCaptchaService
{
    /**
     * 生成滑块验证码数据
     * @return array
     */
    public static function generate()
    {
        
        // 生成随机滑块位置 (50-250px之间)
        $targetPosition = rand(50, 250);
        
        // 生成验证码唯一标识
        $captchaId = md5(uniqid() . time());
        
        // 随机选择背景图片
        $backgroundImage = self::getRandomImage('backgrounds');
        
        // 如果有背景图片，自动从中切出拼图块
        $blockImage = null;
        if ($backgroundImage) {
            // 计算切图位置（在目标位置切出拼图块）
            $blockY = (150 - 50) / 2; // 画布高度150，拼图块高度50，居中
            $blockImage = self::generatePuzzleBlock($backgroundImage, $targetPosition, $blockY, 50, 50);
        }
        
        // 如果自动切图失败，尝试使用预制的拼图块
        if (!$blockImage) {
            $blockImage = self::getRandomImage('blocks');
        }
        
        // 生成干扰数据 (防止暴力破解)
        $decoyPositions = [];
        for ($i = 0; $i < 3; $i++) {
            $decoy = rand(30, 280);
            if (abs($decoy - $targetPosition) > 30) {
                $decoyPositions[] = $decoy;
            }
        }
        
        $sessionData = [
            'id' => $captchaId,
            'position' => $targetPosition,
            'decoys' => $decoyPositions,
            'background_image' => $backgroundImage,
            'block_image' => $blockImage,
            'time' => time(),
            'attempts' => 0
        ];
        
        Cache::set('slider_captcha_' . $captchaId, $sessionData, 300);
        
        return [
            'captcha_id' => $captchaId,
            'target_position' => $targetPosition,
            'decoy_positions' => $decoyPositions,
            'background_image' => $backgroundImage,
            'block_image' => $blockImage,
            'max_width' => 300,
            'tolerance' => 8 // 允许误差范围
        ];
    }

    /**
     * 随机获取图片
     * @param string $type 图片类型 (backgrounds/blocks)
     * @return string|null
     */
    private static function getRandomImage($type)
    {
        $imagePath = public_path() . '/static/images/captcha/' . $type . '/';
        
        if (!is_dir($imagePath)) {
            return null;
        }
        
        $images = [];
        $extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        foreach ($extensions as $ext) {
            $files = glob($imagePath . '*.' . $ext);
            $images = array_merge($images, $files);
        }
        
        if (empty($images)) {
            return null;
        }
        
        $randomImage = $images[array_rand($images)];
        $filename = basename($randomImage);
        
        return '/static/images/captcha/' . $type . '/' . $filename;
    }

    /**
     * 自动生成拼图块（从背景图片中切出）
     * @param string $backgroundPath 背景图片路径
     * @param int $x 切出位置X
     * @param int $y 切出位置Y
     * @param int $width 拼图块宽度
     * @param int $height 拼图块高度
     * @return string|false 生成的拼图块路径
     */
    public static function generatePuzzleBlock($backgroundPath, $x, $y, $width = 50, $height = 50)
    {
        $fullPath = public_path() . $backgroundPath;
        
        if (!file_exists($fullPath)) {
            return false;
        }

        if (!function_exists('getimagesize') || !function_exists('imagecreatetruecolor') || !function_exists('imagepng')) {
            return false;
        }
        
        // 获取图片信息
        $imageInfo = getimagesize($fullPath);
        if (!$imageInfo) {
            return false;
        }
        
        // 创建源图片资源
        switch ($imageInfo[2]) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($fullPath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($fullPath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($fullPath);
                break;
            default:
                return false;
        }

        if (!$sourceImage) {
            return false;
        }
        
        // 创建拼图块画布
        $puzzleBlock = imagecreatetruecolor($width, $height);
        
        // 设置透明背景
        imagealphablending($puzzleBlock, false);
        imagesavealpha($puzzleBlock, true);
        $transparent = imagecolorallocatealpha($puzzleBlock, 0, 0, 0, 127);
        imagefill($puzzleBlock, 0, 0, $transparent);
        
        // 复制图片区域到拼图块
        imagecopy($puzzleBlock, $sourceImage, 0, 0, $x, $y, $width, $height);
        
        // 生成拼图块文件名
        $blockFilename = 'block_' . time() . '_' . rand(1000, 9999) . '.png';
        $blockDir = public_path() . '/static/images/captcha/blocks/';
        if (!is_dir($blockDir) && !@mkdir($blockDir, 0755, true) && !is_dir($blockDir)) {
            imagedestroy($sourceImage);
            imagedestroy($puzzleBlock);
            return false;
        }
        if (!is_writable($blockDir)) {
            imagedestroy($sourceImage);
            imagedestroy($puzzleBlock);
            return false;
        }
        $blockPath = $blockDir . $blockFilename;
        
        // 保存拼图块
        $success = imagepng($puzzleBlock, $blockPath);
        
        // 清理资源
        imagedestroy($sourceImage);
        imagedestroy($puzzleBlock);
        
        return $success ? '/static/images/captcha/blocks/' . $blockFilename : false;
    }
    
    /**
     * 验证滑块位置
     * @param string $captchaId 验证码ID
     * @param int $userPosition 用户滑动位置
     * @param int $slideTime 滑动耗时(毫秒)
     * @return array
     */
    public static function verify($captchaId, $userPosition, $slideTime = 0)
    {
        
        $captchaData = Cache::get('slider_captcha_' . $captchaId);
        
        if (!$captchaData) {
            return [
                'success' => false,
                'message' => '验证码已过期，请刷新重试'
            ];
        }
        
        // 验证ID是否匹配
        if ($captchaData['id'] !== $captchaId) {
            return [
                'success' => false,
                'message' => '验证码无效'
            ];
        }
        
        // 检查验证码是否过期 (5分钟)
        if (time() - $captchaData['time'] > 300) {
            return [
                'success' => false,
                'message' => '验证码已过期，请刷新重试'
            ];
        }
        
        // 检查尝试次数 (最多5次)
        if ($captchaData['attempts'] >= 5) {
            Cache::delete('slider_captcha_' . $captchaId);
            return [
                'success' => false,
                'message' => '尝试次数过多，请刷新重试'
            ];
        }
        
        // 增加尝试次数
        $captchaData['attempts']++;
        Cache::set('slider_captcha_' . $captchaId, $captchaData, 300);
        
        // 验证滑动时间 (防止机器人，正常人滑动至少需要200ms)
        if ($slideTime > 0 && $slideTime < 200) {
            return [
                'success' => false,
                'message' => '滑动过快，请重新验证'
            ];
        }
        
        // 验证滑动时间不能太长 (超过30秒视为异常)
        if ($slideTime > 30000) {
            return [
                'success' => false,
                'message' => '滑动超时，请重新验证'
            ];
        }
        
        // 验证位置是否正确
        $targetPosition = $captchaData['position'];
        $tolerance = 8; // 允许8px误差
        
        if (abs($userPosition - $targetPosition) <= $tolerance) {
            Cache::delete('slider_captcha_' . $captchaId);
            
            $verifyToken = self::makeVerifyToken($captchaId);
            Cache::set('slider_verified_' . $verifyToken, [
                'time' => time(),
                'client' => self::clientFingerprint(),
            ], 300);
            
            return [
                'success' => true,
                'message' => '验证成功',
                'verify_token' => $verifyToken
            ];
        } else {
            return [
                'success' => false,
                'message' => '位置不正确，请重新滑动'
            ];
        }
    }
    
    /**
     * 检查是否已通过验证
     * @return bool
     */
    public static function isVerified()
    {
        return false;
    }

    /**
     * 检查一次性验证 token
     */
    public static function isVerifiedToken($verifyToken)
    {
        $verifyToken = trim((string)$verifyToken);
        if ($verifyToken === '') {
            return false;
        }

        if (self::isValidSignedToken($verifyToken)) {
            return true;
        }

        $verifyData = Cache::get('slider_verified_' . $verifyToken);
        if (!$verifyData || !is_array($verifyData)) {
            return false;
        }

        if (time() - intval($verifyData['time'] ?? 0) > 300) {
            Cache::delete('slider_verified_' . $verifyToken);
            return false;
        }

        if (isset($verifyData['client']) && $verifyData['client'] !== self::clientFingerprint()) {
            Cache::delete('slider_verified_' . $verifyToken);
            return false;
        }

        return true;
    }
    
    /**
     * 清除验证状态
     */
    public static function clearVerification($verifyToken = '')
    {
        $verifyToken = trim((string)$verifyToken);
        if ($verifyToken !== '') {
            Cache::delete('slider_verified_' . $verifyToken);
        }
    }

    private static function makeVerifyToken($captchaId)
    {
        $payload = [
            'captcha_id' => (string)$captchaId,
            'time' => time(),
            'nonce' => bin2hex(random_bytes(8)),
            'client' => self::clientFingerprint(),
        ];
        $payloadText = self::base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $signature = hash_hmac('sha256', $payloadText, self::signingKey());

        return 'v2.' . $payloadText . '.' . $signature;
    }

    private static function isValidSignedToken($verifyToken)
    {
        $parts = explode('.', $verifyToken);
        if (count($parts) !== 3 || $parts[0] !== 'v2') {
            return false;
        }

        $payloadText = $parts[1];
        $signature = $parts[2];
        $expected = hash_hmac('sha256', $payloadText, self::signingKey());
        if (!hash_equals($expected, $signature)) {
            return false;
        }

        $payloadJson = self::base64UrlDecode($payloadText);
        $payload = $payloadJson ? json_decode($payloadJson, true) : null;
        if (!is_array($payload)) {
            return false;
        }

        $time = intval($payload['time'] ?? 0);
        if ($time <= 0 || time() - $time > 300) {
            return false;
        }

        $client = (string)($payload['client'] ?? '');
        return $client === '' || hash_equals($client, self::clientFingerprint());
    }

    private static function clientFingerprint()
    {
        return hash('sha256', (string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
    }

    private static function signingKey()
    {
        return app()->getRootPath() . '|slider-captcha';
    }

    private static function base64UrlEncode($value)
    {
        return rtrim(strtr(base64_encode((string)$value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode($value)
    {
        $value = strtr((string)$value, '-_', '+/');
        $padding = strlen($value) % 4;
        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }
        return base64_decode($value, true);
    }
    
    /**
     * 生成验证码背景图片 (可选功能)
     * @return string base64图片数据
     */
    public static function generateBackgroundImage()
    {
        // 创建300x150的画布
        $width = 300;
        $height = 150;
        $image = imagecreatetruecolor($width, $height);
        
        // 设置渐变背景
        for ($i = 0; $i < $height; $i++) {
            $r = 240 - ($i * 0.3);
            $g = 248 - ($i * 0.2);
            $b = 255 - ($i * 0.1);
            $color = imagecolorallocate($image, $r, $g, $b);
            imageline($image, 0, $i, $width, $i, $color);
        }
        
        // 添加一些随机干扰线
        for ($i = 0; $i < 5; $i++) {
            $color = imagecolorallocate($image, rand(200, 230), rand(200, 230), rand(200, 230));
            imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $color);
        }
        
        // 输出为base64
        ob_start();
        imagepng($image);
        $imageData = ob_get_contents();
        ob_end_clean();
        imagedestroy($image);
        
        return 'data:image/png;base64,' . base64_encode($imageData);
    }
}
