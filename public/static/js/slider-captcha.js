/**
 * 定制滑块验证码组件
 * 精美的滑块验证码实现 - 优化版本
 */
class SliderCaptcha {
    constructor(container, options = {}) {
        this.container = typeof container === 'string' ? document.querySelector(container) : container;
        this.options = {
            width: 300,
            height: 150,
            sliderWidth: 50,
            tolerance: 8,
            apiUrl: '/api/slider-captcha',
            onSuccess: null,
            onFail: null,
            ...options
        };
        
        this.captchaData = null;
        this.isSliding = false;
        this.startX = 0;
        this.startTime = 0;
        this.currentX = 0;
        this.isModalOpen = false;
        this.isVerified = false;
        
        this.init();
    }
    
    /**
     * 初始化验证码
     */
    init() {
        this.createSimpleSlider();
        // 事件绑定现在在 createSimpleSlider 中的 setTimeout 回调中进行
    }
    
    /**
     * 创建验证按钮
     */
    createSimpleSlider() {
        if (!this.container) {
            return;
        }
        
        this.container.innerHTML = `
            <div class="slider-captcha-simple">
                <button class="slider-verify-button" type="button">
                    <span class="slider-verify-text">点击按钮开始验证</span>
                    <div class="slider-verify-icon-container">
                        <svg class="slider-verify-icon" viewBox="0 0 24 24" width="18" height="18">
                            <path fill="currentColor" d="M12,1L3,5V11C3,16.55 6.84,21.74 12,23C17.16,21.74 21,16.55 21,11V5L12,1M10,17L6,13L7.41,11.59L10,14.18L16.59,7.59L18,9L10,17Z"/>
                        </svg>
                        <svg class="slider-verify-loading" viewBox="0 0 24 24" width="18" height="18" style="display: none;">
                            <path fill="currentColor" d="M12,4V2A10,10 0 0,0 2,12H4A8,8 0 0,1 12,4Z"/>
                        </svg>
                    </div>
                </button>
            </div>
        `;
        
        // 等待DOM更新后获取元素
        setTimeout(() => {
            this.verifyButton = this.container.querySelector('.slider-verify-button');
            this.verifyText = this.container.querySelector('.slider-verify-text');
            this.verifyIcon = this.container.querySelector('.slider-verify-icon');
            this.verifyLoading = this.container.querySelector('.slider-verify-loading');
            
            // 验证元素是否正确获取
            if (!this.verifyButton) {
                return;
            } else {
                // 重新绑定事件
                this.bindSimpleEvents();
            }
        }, 0);
    }

    /**
     * 创建完整验证弹窗
     */
    createModal() {
        // 创建遮罩层
        this.modal = document.createElement('div');
        this.modal.className = 'slider-captcha-modal';
        this.modal.innerHTML = `
            <div class="slider-captcha-modal-content">
                <div class="slider-captcha-status-bar"></div>
                <div class="slider-captcha-header">
                    <span class="slider-captcha-title">拖动滑块完成验证</span>
                    <div class="slider-captcha-actions">
                        <button class="slider-captcha-refresh" title="刷新验证码">
                            <svg viewBox="0 0 24 24" width="16" height="16">
                                <path fill="currentColor" d="M17.65,6.35C16.2,4.9 14.21,4 12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20C15.73,20 18.84,17.45 19.73,14H17.65C16.83,16.33 14.61,18 12,18A6,6 0 0,1 6,12A6,6 0 0,1 12,6C13.66,6 15.14,6.69 16.22,7.78L13,11H20V4L17.65,6.35Z"/>
                            </svg>
                        </button>
                        <button class="slider-captcha-close">&times;</button>
                    </div>
                </div>
                <div class="slider-captcha-container">
                    <div class="slider-captcha-panel">
                        <div class="slider-captcha-bg">
                            <canvas class="slider-captcha-canvas" width="${this.options.width}" height="${this.options.height}"></canvas>
                            <div class="slider-captcha-block" style="width: 50px; height: 50px;"></div>
                        </div>
                        <div class="slider-captcha-modal-control">
                            <div class="slider-captcha-track">
                                <div class="slider-captcha-track-bg"></div>
                                <div class="slider-captcha-slider">
                                    <div class="slider-captcha-slider-icon">
                                        <svg viewBox="0 0 24 24" width="16" height="16">
                                            <path fill="currentColor" d="M8.59,16.58L13.17,12L8.59,7.41L10,6L16,12L10,18L8.59,16.58Z"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            <div class="slider-captcha-text">拖动上方滑块到正确位置</div>
                        </div>
                    </div>
                    <div class="slider-captcha-loading" style="display: none;">
                        <div class="slider-captcha-spinner"></div>
                        <span>正在验证...</span>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(this.modal);
        
        // 获取弹窗内的DOM元素
        this.canvas = this.modal.querySelector('.slider-captcha-canvas');
        this.ctx = this.canvas.getContext('2d');
        
        // 确保canvas内部尺寸与CSS显示尺寸一致
        this.adjustCanvasSize();
        this.block = this.modal.querySelector('.slider-captcha-block');
        this.slider = this.modal.querySelector('.slider-captcha-slider');
        this.track = this.modal.querySelector('.slider-captcha-track');
        this.trackBg = this.modal.querySelector('.slider-captcha-track-bg');
        this.text = this.modal.querySelector('.slider-captcha-text');
        this.refresh = this.modal.querySelector('.slider-captcha-refresh');
        this.loading = this.modal.querySelector('.slider-captcha-loading');
        this.panel = this.modal.querySelector('.slider-captcha-panel');
        this.closeBtn = this.modal.querySelector('.slider-captcha-close');
        
        // 绑定弹窗事件
        this.bindModalEvents();
    }
    
    /**
     * 绑定验证按钮事件
     */
    bindSimpleEvents() {
        // 确保按钮元素存在
        if (!this.verifyButton) {
            return;
        }
        
        // 点击按钮开始验证
        this.verifyButton.addEventListener('click', this.openModal.bind(this));
    }


    /**
     * 绑定弹窗内滑块事件
     */
    bindModalEvents() {
        // 滑块拖拽事件
        this.slider.addEventListener('mousedown', this.onSliderMouseDown.bind(this));
        this.slider.addEventListener('touchstart', this.onSliderTouchStart.bind(this), { passive: false });
        
        // 刷新按钮
        this.refresh.addEventListener('click', this.generateCaptcha.bind(this));
        
        // 关闭按钮
        this.closeBtn.addEventListener('click', this.closeModal.bind(this));
        
        // 移除了鼠标悬停自动关闭功能，只保留点击交互
        
        // 全局事件（避免重复绑定）
        if (!this.globalEventsbound) {
            this.boundMouseMove = this.onMouseMove.bind(this);
            this.boundMouseUp = this.onMouseUp.bind(this);
            this.boundTouchMove = this.onTouchMove.bind(this);
            this.boundTouchEnd = this.onTouchEnd.bind(this);
            
            document.addEventListener('mousemove', this.boundMouseMove);
            document.addEventListener('mouseup', this.boundMouseUp);
            document.addEventListener('touchmove', this.boundTouchMove, { passive: false });
            document.addEventListener('touchend', this.boundTouchEnd);
            
            this.globalEventsbound = true;
        }
        
        // ESC键关闭
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isModalOpen) {
                this.closeModal();
            }
        });
    }

    /**
     * 打开验证弹窗
     */
    openModal() {
        if (this.isModalOpen || this.isVerified) return;
        
        // 显示按钮加载状态
        this.showButtonLoading();
        
        this.isModalOpen = true;
        
        // 创建弹窗（如果还没创建）
        if (!this.modal) {
            this.createModal();
        }
        
        // 计算弹窗位置（显示在按钮上方）
        this.positionModal();
        
        // 显示弹窗
        this.modal.style.display = 'block';
        setTimeout(() => {
            this.modal.classList.add('show');
        }, 10);
        
        // 生成验证码
        this.generateCaptcha();
    }

    /**
     * 显示按钮加载状态
     */
    showButtonLoading() {
        if (!this.verifyText || !this.verifyIcon || !this.verifyLoading) return;
        
        this.verifyText.textContent = '正在加载...';
        this.verifyIcon.style.display = 'none';
        this.verifyLoading.style.display = 'inline-block';
    }

    /**
     * 隐藏按钮加载状态
     */
    hideButtonLoading() {
        if (!this.verifyText || !this.verifyIcon || !this.verifyLoading) return;
        
        this.verifyText.textContent = '点击按钮开始验证';
        this.verifyIcon.style.display = 'inline-block';
        this.verifyLoading.style.display = 'none';
    }

    /**
     * 计算并设置弹窗位置
     */
    positionModal() {
        const containerRect = this.container.getBoundingClientRect();
        const modalContent = this.modal.querySelector('.slider-captcha-modal-content');
        
        // 弹窗尺寸
        const modalWidth = 340;
        const modalHeight = 280;
        
        // 寻找登录框作为参考点
        const loginWrapper = document.querySelector('.login-wrapper');
        
        if (loginWrapper) {
            const referenceRect = loginWrapper.getBoundingClientRect();
            
            // 检测是否为移动端
            const isMobile = window.innerWidth <= 767;
            
            if (isMobile) {
                // 移动端：弹窗完全居中显示
                modalContent.style.position = 'fixed';
                modalContent.style.left = '50%';
                modalContent.style.top = '50%';
                modalContent.style.transform = 'translate(-50%, -50%)';
                modalContent.style.margin = '0';
                modalContent.style.zIndex = '99999';
            } else {
                // 桌面端：相对于登录框定位
                const left = referenceRect.left + (referenceRect.width - modalWidth) / 2;
                const top = referenceRect.top - modalHeight + 330;
                
                // 边界检测
                const finalLeft = Math.max(10, Math.min(left, window.innerWidth - modalWidth - 10));
                const finalTop = Math.max(10, top);
                
                modalContent.style.position = 'fixed';
                modalContent.style.left = finalLeft + 'px';
                modalContent.style.top = finalTop + 'px';
                modalContent.style.margin = '0';
            }
        } else {
            // 回退到原来的按钮定位方式
            const buttonCenter = containerRect.left + containerRect.width / 2;
            const left = buttonCenter - modalWidth / 2;
            let top = containerRect.top - modalHeight - 10;
            
            if (top < 10) {
                top = containerRect.bottom + 10;
            }
            
            const finalLeft = Math.max(10, Math.min(left, window.innerWidth - modalWidth - 10));
            
            modalContent.style.position = 'fixed';
            modalContent.style.left = finalLeft + 'px';
            modalContent.style.top = top + 'px';
            modalContent.style.margin = '0';
        }
        
        // 移除箭头相关样式
        modalContent.classList.remove('arrow-top');
        modalContent.style.removeProperty('--arrow-left');
    }

    /**
     * 关闭验证弹窗
     */
    closeModal() {
        if (!this.isModalOpen) return;
        
        this.isModalOpen = false;
        this.modal.classList.remove('show');
        
        // 如果验证未成功，恢复按钮状态
        if (!this.isVerified) {
            this.hideButtonLoading();
        }
        
        setTimeout(() => {
            this.modal.style.display = 'none';
        }, 300);
    }
    
    /**
     * 生成验证码
     */
    async generateCaptcha() {
        this.showLoading();
        
        try {
            const response = await fetch(this.options.apiUrl + '/generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.captchaData = data.data;
                
                // 重新调整canvas尺寸（响应式变化时）
                this.adjustCanvasSize();
                
                this.drawBackground();
                this.drawBlock();
                this.reset();
            } else {
                this.showError(data.message || '生成验证码失败');
            }
        } catch (error) {
            this.showError('网络错误，请重试');
        } finally {
            this.hideLoading();
        }
    }
    
    /**
     * 调整canvas尺寸以匹配CSS显示尺寸
     */
    adjustCanvasSize() {
        // 获取canvas的CSS显示尺寸
        const rect = this.canvas.getBoundingClientRect();
        const computedStyle = window.getComputedStyle(this.canvas);
        const displayWidth = parseInt(computedStyle.width);
        const displayHeight = parseInt(computedStyle.height);
        
        // 设置canvas内部尺寸与显示尺寸一致
        this.canvas.width = displayWidth;
        this.canvas.height = displayHeight;
        
        // 更新options以反映实际尺寸
        this.options.width = displayWidth;
        this.options.height = displayHeight;
        
    }

    /**
     * 绘制背景
     */
    drawBackground() {
        const { width, height } = this.options;
        
        if (this.captchaData && this.captchaData.background_image) {
            // 使用真实图片作为背景
            this.loadBackgroundImage(this.captchaData.background_image);
        } else {
            // fallback: 使用渐变背景
            const gradient = this.ctx.createLinearGradient(0, 0, 0, height);
            gradient.addColorStop(0, '#f0f8ff');
            gradient.addColorStop(0.5, '#e6f3ff');
            gradient.addColorStop(1, '#ddeeff');
            
            this.ctx.fillStyle = gradient;
            this.ctx.fillRect(0, 0, width, height);
            
            // 添加一些装饰性元素
            this.drawDecorations();
        }
    }

    /**
     * 加载背景图片
     */
    loadBackgroundImage(imageSrc) {
        const img = new Image();
        img.crossOrigin = 'anonymous';
        
        img.onload = () => {
            // 清除画布
            this.ctx.clearRect(0, 0, this.options.width, this.options.height);
            
            // 绘制背景图片，自适应画布大小
            this.ctx.drawImage(img, 0, 0, this.options.width, this.options.height);
            
            // 检测背景亮度并调整拼图块样式
            this.adjustBlockVisibility();
            
            // 在背景上创建拼图缺口
            this.createPuzzleHole();
        };
        
        img.onerror = () => {
            // 使用默认渐变背景
            const gradient = this.ctx.createLinearGradient(0, 0, 0, this.options.height);
            gradient.addColorStop(0, '#f0f8ff');
            gradient.addColorStop(0.5, '#e6f3ff');
            gradient.addColorStop(1, '#ddeeff');
            
            this.ctx.fillStyle = gradient;
            this.ctx.fillRect(0, 0, this.options.width, this.options.height);
            this.drawDecorations();
            this.drawBlockOutline();
        };
        
        img.src = imageSrc;
    }

    /**
     * 检测背景亮度并调整拼图块可见性
     */
    adjustBlockVisibility() {
        if (!this.captchaData) return;
        
        const { target_position } = this.captchaData;
        const blockSize = 50; // 固定50×50像素的正方形
        const blockY = (this.options.height - blockSize) / 2;
        
        // 获取拼图块区域的像素数据
        const imageData = this.ctx.getImageData(target_position, blockY, blockSize, blockSize);
        const data = imageData.data;
        
        // 计算平均亮度
        let totalBrightness = 0;
        let pixelCount = 0;
        
        for (let i = 0; i < data.length; i += 4) {
            const r = data[i];
            const g = data[i + 1];
            const b = data[i + 2];
            const brightness = (r * 0.299 + g * 0.587 + b * 0.114);
            totalBrightness += brightness;
            pixelCount++;
        }
        
        const avgBrightness = totalBrightness / pixelCount;
        
        // 根据亮度调整拼图块样式
        this.blockBrightness = avgBrightness;
    }

    /**
     * 在背景上创建拼图缺口
     */
    createPuzzleHole() {
        if (!this.captchaData) return;
        
        const { target_position } = this.captchaData;
        const blockSize = 50; // 固定50×50像素的正方形
        const blockY = (this.canvas.height - blockSize) / 2;
        
        
        // 设置合成模式为"destination-out"来创建透明区域
        this.ctx.globalCompositeOperation = 'destination-out';
        
        // 直接绘制正方形缺口
        this.ctx.fillStyle = 'rgba(0, 0, 0, 1)';
        this.ctx.fillRect(target_position, blockY, blockSize, blockSize);
        
        // 恢复正常绘制模式
        this.ctx.globalCompositeOperation = 'source-over';
        
        // 绘制缺口边框
        this.drawPuzzleHoleBorder(target_position, blockY, blockSize);
    }

    /**
     * 绘制拼图形状
     */
    drawPuzzleShape(x, y, size) {
        this.ctx.beginPath();
        
        // 绘制基本正方形
        this.ctx.rect(x, y, size, size);
        
        this.ctx.fill();
    }

    /**
     * 绘制拼图缺口边框
     */
    drawPuzzleHoleBorder(x, y, size) {
        // 简化边框绘制，确保正方形
        this.ctx.strokeStyle = 'rgba(255, 255, 255, 0.8)';
        this.ctx.lineWidth = 2;
        this.ctx.shadowColor = 'rgba(0, 0, 0, 0.5)';
        this.ctx.shadowBlur = 4;
        
        this.ctx.beginPath();
        // 使用strokeRect直接绘制正方形边框
        this.ctx.strokeRect(x, y, size, size);
        
        // 清除阴影
        this.ctx.shadowColor = 'transparent';
        this.ctx.shadowBlur = 0;
    }
    
    /**
     * 绘制装饰元素
     */
    drawDecorations() {
        const { width, height } = this.options;
        
        // 绘制一些圆点装饰
        for (let i = 0; i < 20; i++) {
            const x = Math.random() * width;
            const y = Math.random() * height;
            const radius = Math.random() * 2 + 1;
            const alpha = Math.random() * 0.3 + 0.1;
            
            this.ctx.fillStyle = `rgba(100, 150, 255, ${alpha})`;
            this.ctx.beginPath();
            this.ctx.arc(x, y, radius, 0, Math.PI * 2);
            this.ctx.fill();
        }
        
        // 绘制一些线条装饰
        for (let i = 0; i < 5; i++) {
            const x1 = Math.random() * width;
            const y1 = Math.random() * height;
            const x2 = Math.random() * width;
            const y2 = Math.random() * height;
            const alpha = Math.random() * 0.2 + 0.05;
            
            this.ctx.strokeStyle = `rgba(120, 160, 255, ${alpha})`;
            this.ctx.lineWidth = 1;
            this.ctx.beginPath();
            this.ctx.moveTo(x1, y1);
            this.ctx.lineTo(x2, y2);
            this.ctx.stroke();
        }
    }
    
    /**
     * 绘制滑块
     */
    drawBlock() {
        if (!this.captchaData) return;
        
        const { target_position } = this.captchaData;
        const blockSize = 50; // 固定50×50像素的正方形
        
        // 设置滑块位置和大小 - 初始位置在左边，不在目标位置
        const blockY = (this.canvas.height - blockSize) / 2;
        this.block.style.left = '0px'; // 初始位置在最左边
        this.block.style.top = blockY + 'px';
        this.block.style.width = blockSize + 'px';
        this.block.style.height = blockSize + 'px';
        
        // 存储目标位置供后续使用
        this.targetPosition = target_position;
        
        
        if (this.captchaData.block_image) {
            // 使用真实图片作为拼图块
            this.loadBlockImage(this.captchaData.block_image);
        } else {
            // fallback: 绘制轮廓
            this.drawBlockOutline();
        }
    }

    /**
     * 绘制拼图块轮廓
     */
    drawBlockOutline() {
        if (!this.captchaData) return;
        
        const { target_position } = this.captchaData;
        const blockSize = 50; // 固定50×50像素的正方形
        const blockY = (this.options.height - blockSize) / 2;
        
        // 在画布上绘制滑块轮廓 - 移除绿色，使用透明轮廓
        this.ctx.strokeStyle = 'rgba(255, 255, 255, 0.8)';
        this.ctx.lineWidth = 2;
        this.ctx.setLineDash([5, 5]);
        this.ctx.strokeRect(target_position, blockY, blockSize, blockSize);
        this.ctx.setLineDash([]);
    }

    /**
     * 加载拼图块图片
     */
    loadBlockImage(imageSrc) {
        if (!this.captchaData) return;
        
        const img = new Image();
        img.crossOrigin = 'anonymous';
        
        img.onload = () => {
            // 将图片设置为拼图块的背景，不添加任何装饰
            this.block.style.backgroundImage = `url(${imageSrc})`;
            this.block.style.backgroundSize = '50px 50px';
            this.block.style.backgroundRepeat = 'no-repeat';
            this.block.style.backgroundPosition = 'center';
            
            // 确保没有边框和装饰
            this.block.style.border = 'none';
            this.block.style.borderRadius = '0';
            this.block.style.boxShadow = 'none';
            this.block.style.outline = 'none';
            
        };
        
        img.onerror = () => {
            this.drawBlockOutline();
        };
        
        img.src = imageSrc;
    }

    /**
     * 添加拼图形状遮罩 (已禁用，保持图片显示)
     */
    addPuzzleShapeMask() {
        // 不做任何操作，保持背景图片显示
    }
    
    /**
     * 鼠标按下事件
     */
    onSliderMouseDown(e) {
        if (this.isSliding) return;
        e.preventDefault();
        this.startSliding(e.clientX);
    }
    
    /**
     * 触摸开始事件
     */
    onSliderTouchStart(e) {
        if (this.isSliding) return;
        e.preventDefault();
        this.startSliding(e.touches[0].clientX);
    }
    
    /**
     * 开始滑动
     */
    startSliding(clientX) {
        if (!this.slider || !this.text) return;
        
        this.isSliding = true;
        this.startX = clientX;
        this.startTime = Date.now();
        this.currentX = 0;
        
        this.slider.classList.add('sliding');
        this.text.textContent = '拖动滑块...';
        
        // 阻止页面滚动
        document.body.style.overflow = 'hidden';
    }
    
    /**
     * 鼠标移动事件
     */
    onMouseMove(e) {
        if (!this.isSliding) return;
        this.updateSliderPosition(e.clientX);
    }
    
    /**
     * 触摸移动事件
     */
    onTouchMove(e) {
        if (!this.isSliding) return;
        e.preventDefault();
        this.updateSliderPosition(e.touches[0].clientX);
    }
    
    /**
     * 更新滑块位置
     */
    updateSliderPosition(clientX) {
        if (!this.track || !this.slider) return;
        
        const deltaX = clientX - this.startX;
        const maxDistance = this.track.offsetWidth - this.slider.offsetWidth;
        
        this.currentX = Math.max(0, Math.min(deltaX, maxDistance));
        
        // 临时禁用动画以获得即时响应
        this.trackBg.style.transition = 'none';
        this.slider.style.transition = 'none';
        
        // 更新滑块位置
        this.slider.style.transform = `translateX(${this.currentX}px)`;
        this.trackBg.style.width = (this.currentX + this.slider.offsetWidth) + 'px';
        
        // 同步更新画布中的拼图块位置
        this.updatePuzzleBlockPosition();
    }

    /**
     * 更新拼图块位置
     */
    updatePuzzleBlockPosition() {
        if (!this.block || !this.captchaData) return;
        
        // 计算拼图块应该移动的距离
        // 滑块移动距离 * (画布宽度 / 滑块轨道宽度)
        const blockMoveDistance = this.currentX * (this.options.width / this.track.offsetWidth);
        
        // 更新拼图块位置
        this.block.style.transform = `translateX(${blockMoveDistance}px)`;
    }
    
    /**
     * 鼠标释放事件
     */
    onMouseUp(e) {
        if (!this.isSliding) return;
        this.endSliding();
    }
    
    /**
     * 触摸结束事件
     */
    onTouchEnd(e) {
        if (!this.isSliding) return;
        this.endSliding();
    }
    
    /**
     * 结束滑动
     */
    async endSliding() {
        if (!this.isSliding) return;
        
        this.isSliding = false;
        this.slider.classList.remove('sliding');
        
        // 恢复动画效果
        if (this.trackBg) {
            this.trackBg.style.transition = '';
        }
        if (this.slider) {
            this.slider.style.transition = '';
        }
        
        // 恢复页面滚动
        document.body.style.overflow = '';
        
        const slideTime = Date.now() - this.startTime;
        
        await this.verify(this.currentX, slideTime);
    }
    
    /**
     * 验证滑块位置
     */
    async verify(position, slideTime) {
        if (!this.captchaData) return;
        
        this.showLoading();
        
        try {
            // 计算实际的滑块位置（相对于画布的位置）
            const actualPosition = position * (this.options.width / this.track.offsetWidth);
            
            const response = await fetch(this.options.apiUrl + '/verify', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    captcha_id: this.captchaData.captcha_id,
                    position: actualPosition,
                    slide_time: slideTime
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showSuccess();
                if (this.options.onSuccess) {
                    this.options.onSuccess(data);
                }
            } else {
                this.showError(data.message || '验证失败');
                if (this.options.onFail) {
                    this.options.onFail(data);
                }
                setTimeout(() => this.reset(), 1000);
            }
        } catch (error) {
            this.showError('网络错误，请重试');
            setTimeout(() => this.reset(), 1000);
        } finally {
            this.hideLoading();
        }
    }
    
    /**
     * 显示成功状态
     */
    showSuccess() {
        this.isVerified = true;
        this.text.textContent = '验证成功';
        this.slider.classList.add('success');
        this.trackBg.classList.add('success');
        this.panel.classList.add('success');
        
        // 更新简单滑块状态
        this.updateSimpleSliderSuccess();
        
        // 2秒后自动关闭弹窗
        setTimeout(() => {
            this.closeModal();
        }, 2000);
    }

    /**
     * 更新验证按钮为成功状态
     */
    updateSimpleSliderSuccess() {
        if (!this.verifyText || !this.verifyButton || !this.verifyIcon || !this.verifyLoading) return;
        
        this.verifyText.textContent = '验证通过';
        this.verifyButton.classList.add('success');
        
        // 隐藏加载图标，显示成功图标
        this.verifyLoading.style.display = 'none';
        this.verifyIcon.style.display = 'inline-block';
        
        // 更改图标为对勾
        this.verifyIcon.innerHTML = `
            <path fill="currentColor" d="M9,20.42L2.79,14.21L5.62,11.38L9,14.77L18.88,4.88L21.71,7.71L9,20.42Z"/>
        `;
    }
    
    /**
     * 显示错误状态
     */
    showError(message) {
        this.text.textContent = message;
        this.slider.classList.add('error');
        this.trackBg.classList.add('error');
        this.panel.classList.add('error');
        
        // 显示顶部错误状态条
        const statusBar = this.modal.querySelector('.slider-captcha-status-bar');
        if (statusBar) {
            statusBar.classList.add('error');
        }
        
    }
    
    /**
     * 重置验证码
     */
    reset() {
        if (!this.slider || !this.trackBg || !this.text || !this.panel) return;
        
        this.currentX = 0;
        this.slider.style.transform = 'translateX(0)';
        this.trackBg.style.width = '0';
        this.text.textContent = '拖动上方滑块到正确位置';
        
        // 重置拼图块位置和样式
        if (this.block) {
            this.block.style.transform = 'translateX(0)';
            
            // 清理之前的覆盖层
            const existingOverlay = this.block.querySelector('.puzzle-block-overlay');
            if (existingOverlay) {
                existingOverlay.remove();
            }
        }
        
        // 清除状态类
        this.slider.classList.remove('success', 'error', 'sliding');
        this.trackBg.classList.remove('success', 'error');
        this.panel.classList.remove('success', 'error');
        
        // 重置状态条
        const statusBar = this.modal.querySelector('.slider-captcha-status-bar');
        if (statusBar) {
            statusBar.classList.remove('error');
        }
        
        
        // 恢复页面滚动
        document.body.style.overflow = '';
    }
    
    /**
     * 显示加载状态
     */
    showLoading() {
        this.loading.style.display = 'flex';
        this.panel.style.opacity = '0.6';
    }
    
    /**
     * 隐藏加载状态
     */
    hideLoading() {
        this.loading.style.display = 'none';
        this.panel.style.opacity = '1';
    }
    
    /**
     * 重置验证码（用于登录失败后重新验证）
     */
    resetCaptcha() {
        this.isVerified = false;
        
        // 重置验证按钮状态
        if (this.verifyText && this.verifyButton && this.verifyIcon && this.verifyLoading) {
            this.verifyText.textContent = '点击按钮开始验证';
            this.verifyButton.classList.remove('success');
            
            // 恢复原始图标
            this.verifyLoading.style.display = 'none';
            this.verifyIcon.style.display = 'inline-block';
            this.verifyIcon.innerHTML = `
                <path fill="currentColor" d="M12,1L3,5V11C3,16.55 6.84,21.74 12,23C17.16,21.74 21,16.55 21,11V5L12,1M10,17L6,13L7.41,11.59L10,14.18L16.59,7.59L18,9L10,17Z"/>
            `;
        }
        
        // 重置弹窗内的状态
        if (this.modal) {
            this.currentX = 0;
            if (this.slider) {
                this.slider.style.transform = 'translateX(0)';
                this.slider.classList.remove('success', 'error', 'sliding');
            }
            if (this.trackBg) {
                this.trackBg.style.width = '0';
                this.trackBg.classList.remove('success', 'error');
            }
            if (this.block) {
                this.block.style.transform = 'translateX(0)';
            }
            if (this.text) {
                this.text.textContent = '拖动上方滑块到正确位置';
            }
            if (this.panel) {
                this.panel.classList.remove('success', 'error');
            }
        }
        
        // 关闭弹窗
        if (this.isModalOpen) {
            this.closeModal();
        }
    }

    /**
     * 销毁验证码
     */
    destroy() {
        // 移除事件监听器
        if (this.globalEventsbound) {
            document.removeEventListener('mousemove', this.boundMouseMove);
            document.removeEventListener('mouseup', this.boundMouseUp);
            document.removeEventListener('touchmove', this.boundTouchMove);
            document.removeEventListener('touchend', this.boundTouchEnd);
            this.globalEventsbound = false;
        }
        
        // 恢复页面滚动
        document.body.style.overflow = '';
        
        // 移除弹窗
        if (this.modal && this.modal.parentNode) {
            document.body.removeChild(this.modal);
        }
        
        // 清空容器
        this.container.innerHTML = '';
    }
}

// 全局暴露
window.SliderCaptcha = SliderCaptcha;
