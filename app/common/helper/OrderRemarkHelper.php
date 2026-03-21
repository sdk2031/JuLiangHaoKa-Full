<?php

namespace app\common\helper;

class OrderRemarkHelper
{
    /**
     * 添加备注到时间线（避免重复）
     * 
     * @param string $currentRemark 当前备注（可能是旧格式字符串或新格式JSON）
     * @param string $newContent 新备注内容
     * @return string 返回JSON格式的备注
     */
    public static function append($currentRemark, $newContent)
    {
        if (empty($newContent)) {
            return $currentRemark;
        }
        
        // 解析当前备注
        $timeline = self::parse($currentRemark);
        
        // 检查是否已存在相同内容（避免重复）
        foreach ($timeline as $item) {
            if (isset($item['content']) && $item['content'] === $newContent) {
                // 已存在相同内容，不重复添加
                return self::encode($timeline);
            }
        }
        
        // 添加新记录
        $newItem = [
            'time' => date('Y-m-d H:i:s'),
            'content' => $newContent
        ];
        
        $timeline[] = $newItem;
        
        return self::encode($timeline);
    }
    
    /**
     * 解析备注为时间线数组
     * 兼容旧格式（纯文本）和新格式（JSON）
     * 
     * @param string $remark 备注内容
     * @return array 时间线数组
     */
    public static function parse($remark)
    {
        if (empty($remark)) {
            return [];
        }
        
        // 尝试解析JSON
        $decoded = json_decode($remark, true);
        
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            // 是有效的JSON数组
            return $decoded;
        }

        $lines = preg_split('/[\r\n]+/', $remark);
        $timeline = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            if (preg_match('/^(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})\s+(.+)$/', $line, $matches)) {
                $timeline[] = [
                    'time' => $matches[1],
                    'content' => $matches[2]
                ];
            } 
            elseif (preg_match('/^\[(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})\]\s*(.+)$/', $line, $matches)) {
                $timeline[] = [
                    'time' => $matches[1],
                    'content' => $matches[2]
                ];
            }
            else {
                $timeline[] = [
                    'time' => '',
                    'content' => $line
                ];
            }
        }
        
        return $timeline;
    }
    
    /**
     * 编码时间线为JSON字符串
     * 
     * @param array $timeline 时间线数组
     * @return string JSON字符串
     */
    public static function encode($timeline)
    {
        if (empty($timeline)) {
            return '';
        }
        
        return json_encode($timeline, JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * 格式化时间线为可读文本（用于显示）
     * 
     * @param string $remark 备注内容（JSON格式）
     * @param string $format 格式：'text'=纯文本, 'html'=HTML格式
     * @return string 格式化后的文本
     */
    public static function format($remark, $format = 'text')
    {
        $timeline = self::parse($remark);
        
        if (empty($timeline)) {
            return '';
        }
        
        $lines = [];
        
        foreach ($timeline as $item) {
            $time = $item['time'] ?? '';
            $content = $item['content'] ?? '';
            $source = $item['source'] ?? '';
            
            if ($format === 'html') {
                $line = '<div class="remark-item">';
                if (!empty($time)) {
                    $line .= '<span class="remark-time">' . htmlspecialchars($time) . '</span> ';
                }
                if (!empty($source)) {
                    $line .= '<span class="remark-source">[' . htmlspecialchars($source) . ']</span> ';
                }
                $line .= '<span class="remark-content">' . htmlspecialchars($content) . '</span>';
                $line .= '</div>';
            } else {
                $parts = [];
                if (!empty($time)) {
                    $parts[] = $time;
                }
                if (!empty($source)) {
                    $parts[] = '[' . $source . ']';
                }
                $parts[] = $content;
                $line = implode(' ', $parts);
            }
            
            $lines[] = $line;
        }
        
        return $format === 'html' ? implode('', $lines) : implode("\n", $lines);
    }
    
    /**
     * 获取最新一条备注
     * 
     * @param string $remark 备注内容
     * @return array|null 最新备注项
     */
    public static function getLatest($remark)
    {
        $timeline = self::parse($remark);
        
        if (empty($timeline)) {
            return null;
        }
        
        return end($timeline);
    }
    
    /**
     * 获取备注条数
     * 
     * @param string $remark 备注内容
     * @return int 条数
     */
    public static function count($remark)
    {
        return count(self::parse($remark));
    }
}
