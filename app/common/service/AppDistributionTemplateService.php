<?php
namespace app\common\service;
/**
 * 打包服务🆕
 */
class AppDistributionTemplateService
{
    private $assetBase = '/assets/appdistribution';

    public function render(array $page, array $options = []): string
    {
        $template = $this->normalizeTemplate((string)($page['template'] ?? ''));
        $content = $this->renderOriginalContent($template, $this->buildData($page, $options));

        return $this->wrapHtml($page, $content, $options);
    }

    private function renderOriginalContent(string $template, array $data): string
    {
        $file = $this->viewTemplatePath($template);
        if (!is_file($file)) {
            $file = $this->viewTemplatePath('dibaqu_temp_1');
        }

        $source = file_get_contents($file);
        if (!preg_match('/<script[^>]+id=["\']content["\'][^>]*>(.*?)<\/script>/is', (string)$source, $match)) {
            return '';
        }

        $html = (string)$match[1];
        $html = preg_replace('/\s*<\?php\b.*$/is', '', $html);
        $html = $this->renderConditions($html, $data);
        $html = $this->renderPlaceholders($html, $data);
        $html = preg_replace('/\{\{[^}]+\}\}/', '', $html);
        $qrImg = '<img src="/qrcode.php?text=' . rawurlencode((string)($data['qrcode_url'] ?? '')) . '&size=180" class="img-responsive dibaqu-qrcode-img">';
        $html = preg_replace('/<img\s+src="\/qrcode\?link=[^"]*"[^>]*>/i', $qrImg, $html);
        return $html;
    }

    private function buildData(array $page, array $options): array
    {
        $hasAndroid = trim((string)($page['android_download_url'] ?? '')) !== '';
        $hasIos = trim((string)($page['ios_download_url'] ?? '')) !== '';
        $support = $hasAndroid && $hasIos ? 3 : ($hasAndroid ? 2 : 1);
        $appName = (string)($page['app_name'] ?? 'APP下载');

        $iconUrl = $this->resolveIconUrl((string)($page['icon_url'] ?? ''), $appName);

        return [
            'checked' => true,
            'support' => $support,
            'icon' => $iconUrl,
            'icon_300' => $iconUrl,
            'app_name' => $appName,
            'version' => '1.0.0',
            'version_code' => '',
            'app_size' => '--',
            'update_dt' => (string)($page['update_time_text'] ?? date('Y-m-d H:i:s')),
            'downurl' => (string)($page['download_entry_url'] ?? ''),
            'qrcode_url' => (string)($page['distribution_url'] ?? ''),
            'web_url' => (string)($page['distribution_url'] ?? ''),
            'app_intro' => '请使用手机扫码或点击下载安装。系统会根据设备自动选择 Android 或 iOS 安装包。',
            'remark' => '如在微信、QQ 中无法下载，请点击右上角菜单，选择在浏览器中打开。',
            'qq' => '',
            'screenshots' => '',
            'auth_code_dispense_url' => '',
            'VERSION' => '版本',
            'SIZE' => '大小',
            'UPDATE_TIME' => '更新时间',
            'DOWNLOAD_INSTALL' => '下载安装',
            'VIEW_IN_DESKTOP' => '请在手机浏览器打开本页面，或使用手机扫描二维码安装',
            'RESIGN' => '重新签名',
            'FOR_IOS_AND_ANDROID' => '适用于 Android / iOS 设备',
            'FOR_ANDROID' => '适用于 Android 设备',
            'FOR_IOS' => '适用于 iOS 设备',
            'SCAN_TIPS' => '手机扫描二维码安装',
            'APP_DESCRIPTION' => '应用简介',
            'APP_REMARK' => '安装提示',
            'APP_CONTACT' => '联系方式',
            'APP_SCREENSHOTS' => '应用截图',
            'REQUIRE_PWD' => '请输入下载密码',
            'DOWNLOAD_ENTER' => '进入下载',
            'BUY_AUTH_CODE' => '获取授权码',
            'SIGNING' => '签名中',
        ];
    }

    private function renderConditions(string $html, array $data): string
    {
        $pattern = '/\{\{if\s+([^}]+)\}\}((?:(?!\{\{if\s).)*?)(?:\{\{else\}\}((?:(?!\{\{if\s).)*?))?\{\{\/if\}\}/s';
        $guard = 0;
        while (preg_match($pattern, $html) && $guard < 50) {
            $html = preg_replace_callback($pattern, function ($match) use ($data) {
                return $this->evaluateCondition(trim((string)$match[1]), $data)
                    ? (string)$match[2]
                    : (string)($match[3] ?? '');
            }, $html);
            $guard++;
        }
        return $html;
    }

    private function evaluateCondition(string $condition, array $data): bool
    {
        if ($condition === 'checked') {
            return !empty($data['checked']);
        }
        if ($condition === 'checked|false') {
            return empty($data['checked']);
        }
        if (preg_match('/^support\|equals>(\d+)$/', $condition, $match)) {
            return (int)($data['support'] ?? 0) === (int)$match[1];
        }
        return !empty($data[$condition]);
    }

    private function renderPlaceholders(string $html, array $data): string
    {
        return preg_replace_callback('/\{\{([A-Za-z0-9_]+)\}\}/', function ($match) use ($data) {
            $key = (string)$match[1];
            return htmlspecialchars((string)($data[$key] ?? ''), ENT_QUOTES);
        }, $html);
    }

    private function wrapHtml(array $page, string $content, array $options): string
    {
        $template = $this->normalizeTemplate((string)($page['template'] ?? ''));
        $appName = htmlspecialchars((string)($page['app_name'] ?? 'APP下载'), ENT_QUOTES);
        $androidDownloadUrl = htmlspecialchars($this->platformDownloadUrl($page, 'android'), ENT_QUOTES);
        $iosDownloadUrl = htmlspecialchars($this->platformDownloadUrl($page, 'ios'), ENT_QUOTES);
        $hasAndroid = trim((string)($page['android_download_url'] ?? '')) !== '';
        $hasIos = trim((string)($page['ios_download_url'] ?? '')) !== '';
        $maskClass = !empty($options['is_embedded_browser']) || !empty($options['browser_tip']) ? ' show' : '';
        $runtimeConfig = [
            'distributionUrl' => (string)($page['distribution_url'] ?? ''),
            'androidDownloadUrl' => $this->platformDownloadUrl($page, 'android'),
            'iosDownloadUrl' => $this->platformDownloadUrl($page, 'ios'),
            'hasAndroid' => $hasAndroid,
            'hasIos' => $hasIos,
            'appName' => (string)($page['app_name'] ?? 'APP下载'),
            'template' => $this->templateName($template),
        ];

        return '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">'
            . '<title>' . $appName . '</title>'
            . '<link rel="stylesheet" href="' . $this->assetBase . '/pack/bootstrap-3.3.7-dist/css/bootstrap.min.css">'
            . '<link rel="stylesheet" href="' . $this->assetBase . '/index/css/style.css">'
            . '<link rel="stylesheet" href="' . $this->assetBase . '/index/css/custom.css">'
            . '<link rel="stylesheet" href="' . $this->assetBase . '/index/css/appstyle.css">'
            . '<link rel="stylesheet" href="' . $this->assetBase . '/pack/swiper.5.4.1/swiper.min.css">'
            . '<link rel="stylesheet" href="//at.alicdn.com/t/font_780494_9oilb5iic5f.css">'
            . '<link rel="stylesheet" href="' . $this->assetBase . '/index/css/base.css">'
            . '<link rel="stylesheet" href="' . $this->assetBase . '/index/css/main.css">'
            . '<link rel="stylesheet" href="' . $this->assetBase . '/index/css/h5.css">'
            . '<style>' . $this->renderCss($template) . '</style></head><body>'
            . $content
            . '<div class="app-extra-actions">'
            . '<a class="' . ($hasAndroid ? '' : 'disabled') . '" href="' . $androidDownloadUrl . '">Android版</a>'
            . '<a class="' . ($hasIos ? '' : 'disabled') . '" href="' . $iosDownloadUrl . '">iOS版</a>'
            . '<button id="copyLink" type="button">复制链接</button><button id="downloadPoster" type="button">二维码海报</button>'
            . '</div>'
            . '<div class="browser-mask' . $maskClass . '" id="browserMask"><div style="position:absolute;right:22px;top:16px;">请点击右上角</div><div class="browser-box"><div class="browser-title">请在浏览器中打开</div><div class="browser-sub">当前应用内置浏览器可能会拦截安装包，请使用右上角菜单打开到浏览器后再下载。</div><button class="mask-btn" id="copyInMask" type="button">复制链接</button><button class="mask-btn secondary" id="closeMask" type="button">知道了</button></div></div>'
            . '<canvas id="posterCanvas" width="720" height="980"></canvas>'
            . '<script src="' . $this->assetBase . '/index/js/jquery.min.js"></script>'
            . '<script src="' . $this->assetBase . '/index/js/bootstrap.min.js"></script>'
            . '<script src="' . $this->assetBase . '/index/js/clipboard.min.js"></script>'
            . '<script src="' . $this->assetBase . '/index/js/markup.js"></script>'
            . '<script src="' . $this->assetBase . '/index/js/publish/ua-parser.min.js"></script>'
            . '<script src="' . $this->assetBase . '/index/js/template.js"></script>'
            . '<script src="' . $this->assetBase . '/pack/swiper.5.4.1/swiper.min.js"></script>'
            . '<script src="/assets/libs/qrcode.min.js"></script>'
            . '<script>window.appDistribution=' . json_encode($runtimeConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';</script>'
            . '<script>' . $this->renderJs($template) . '</script></body></html>';
    }

    private function templateFile(string $template): string
    {
        return $this->templateName($template) . '.html';
    }

    private function templateName(string $template): string
    {
        return 'template' . (int)substr($template, -1);
    }

    private function viewTemplatePath(string $template): string
    {
        return $this->viewBasePath()
            . DIRECTORY_SEPARATOR . 'index'
            . DIRECTORY_SEPARATOR . 'view'
            . DIRECTORY_SEPARATOR . 'appdistribution'
            . DIRECTORY_SEPARATOR . $this->templateFile($template);
    }

    private function viewBasePath(): string
    {
        return dirname(__DIR__, 2);
    }

    private function renderCss(string $template): string
    {
        return $this->readViewAsset('assets/common.css')
            . "\n"
            . $this->readViewAsset('assets/' . $this->templateName($template) . '.css');
    }

    private function renderJs(string $template): string
    {
        return $this->readViewAsset('assets/common.js')
            . "\n"
            . $this->readViewAsset('assets/' . $this->templateName($template) . '.js');
    }

    private function readViewAsset(string $relativePath): string
    {
        $path = $this->viewBasePath()
            . DIRECTORY_SEPARATOR . 'index'
            . DIRECTORY_SEPARATOR . 'view'
            . DIRECTORY_SEPARATOR . 'appdistribution'
            . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        return is_file($path) ? (string)file_get_contents($path) : '';
    }

    private function normalizeTemplate(string $template): string
    {
        if (preg_match('/^dibaqu_temp_[1-6]$/', $template)) {
            return $template;
        }
        if ($template === 'dibaqu_2') {
            return 'dibaqu_temp_2';
        }
        return 'dibaqu_temp_1';
    }

    private function platformDownloadUrl(array $page, string $platform): string
    {
        return rtrim((string)request()->domain(), '/') . '/index/appdistribution/download?slug=' . rawurlencode((string)($page['slug'] ?? '')) . '&platform=' . $platform;
    }

    private function resolveIconUrl(string $iconUrl, string $appName): string
    {
        $iconUrl = trim($iconUrl);
        if ($iconUrl !== '') {
            return $iconUrl;
        }
        return $this->buildIconDataUri($appName);
    }

    private function buildIconDataUri(string $appName): string
    {
        $initial = mb_substr($appName !== '' ? $appName : 'A', 0, 1, 'UTF-8');
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="160" height="160" viewBox="0 0 160 160"><rect width="160" height="160" rx="34" fill="#1abc9c"/><text x="80" y="100" text-anchor="middle" font-size="74" font-family="Arial, sans-serif" font-weight="700" fill="#fff">' . htmlspecialchars($initial, ENT_QUOTES) . '</text></svg>';
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
