#!/bin/bash

echo "🔒 巨量号卡管理系统 - 安全权限设置"
echo "=================================="

# 检测Web服务器用户
detect_web_user() {
    if id "www" &>/dev/null; then
        echo "www"
    elif id "www-data" &>/dev/null; then
        echo "www-data"
    elif id "nginx" &>/dev/null; then
        echo "nginx"  
    elif id "apache" &>/dev/null; then
        echo "apache"
    else
        echo "www"  # 默认使用www
    fi
}

WEB_USER=$(detect_web_user)
echo "🔍 检测到Web服务器用户: $WEB_USER"

# 设置正确的文件所有权
echo "📁 设置文件所有权..."
chown -R $WEB_USER:$WEB_USER .

# 设置安全的文件权限
echo "🔐 设置安全权限..."

# 目录权限：755 (rwxr-xr-x)
find . -type d -exec chmod 755 {} \;

# 一般文件权限：644 (rw-r--r--)
find . -type f -exec chmod 644 {} \;

# 可执行文件权限：755 (rwxr-xr-x)
chmod 755 think
chmod 755 setup_security.sh
chmod 755 *.Psl 2>/dev/null || true

# 配置文件特殊权限：666 (rw-rw-rw-)
chmod 666 .env 2>/dev/null || echo "⚠️  .env文件不存在，安装时会自动创建"
chmod 666 config/database.php

# 重要目录的写入权限
chmod 755 runtime/
chmod 755 public/uploads/
chmod 755 install/

# 递归设置运行时目录权限
chmod -R 755 runtime/
chmod -R 755 public/uploads/

# 安全检查
echo ""
echo "🛡️  安全检查："

# 检查是否有root用户的文件
ROOT_FILES=$(find . -user root 2>/dev/null | wc -l)
if [ $ROOT_FILES -eq 0 ]; then
    echo "✅ 没有root用户的文件"
else
    echo "⚠️  发现 $ROOT_FILES 个root用户的文件"
    find . -user root 2>/dev/null | head -5
fi

# 检查关键文件权限
echo ""
echo "📋 关键文件权限检查："
echo "   .env: $(ls -l .env 2>/dev/null | awk '{print $1, $3, $4}' || echo '文件不存在')"
echo "   config/database.php: $(ls -l config/database.php | awk '{print $1, $3, $4}')"
echo "   runtime/: $(ls -ld runtime/ | awk '{print $1, $3, $4}')"
echo "   install/: $(ls -ld install/ | awk '{print $1, $3, $4}')"

# 检查Web服务器进程用户
echo ""
echo "🌐 Web服务器进程："
ps aux | grep -E "(nginx|apache|httpd)" | grep -v grep | head -3

echo ""
echo "✅ 安全权限设置完成！"
echo ""
echo "🎯 安全建议："
echo "   1. 定期检查文件权限"
echo "   2. 避免使用root用户运行Web应用"
echo "   3. 监控敏感文件的访问"
echo "   4. 定期更新系统和应用"
echo ""
echo "📝 如需重新设置权限，请运行："
echo "   chmod +x setup_security.sh && ./setup_security.sh"
