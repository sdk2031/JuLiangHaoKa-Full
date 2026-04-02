/**
 * 支付处理模块
 * 处理微信支付相关功能
 */
class PaymentHandler {
    constructor() {
        this.openid = null;
        this.isWechatEnv = this.detectWechatEnvironment();
        this.init();
    }

    /**
     * 初始化支付处理器
     */
    init() {
        this.loadOpenid();
        // 在微信环境下，立即检查并获取openid
        if (this.isWechatEnv) {
            this.ensureOpenidReady();
        }
    }

    /**
     * 检测是否在微信环境
     */
    detectWechatEnvironment() {
        const ua = navigator.userAgent.toLowerCase();
        return ua.includes('micromessenger');
    }

    /**
     * 加载已保存的openid
     */
    loadOpenid() {
        // 从URL参数获取
        const urlParams = new URLSearchParams(window.location.search);
        const urlOpenid = urlParams.get('openid');
        if (urlOpenid) {
            this.openid = urlOpenid;
            localStorage.setItem('wechat_openid', urlOpenid);
            return;
        }

        // 从localStorage获取
        this.openid = localStorage.getItem('wechat_openid');
    }

    /**
     * 确保openid准备就绪
     */
    async ensureOpenidReady() {
        // 如果已经有openid，无需处理
        if (this.openid) return;

        // 检查URL是否包含授权回调参数，如果是回调页面则不再触发授权
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('code')) {
            console.log('检测到授权回调参数，跳过自动授权');
            return;
        }

        try {
            // 检查当前页面是否需要JSAPI支付
            const response = await fetch('/index/pay/checkPaymentMode', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            const data = await response.json();
            if (data.code === 1 && data.data.mode === 'JSAPI') {
                // 需要JSAPI支付且没有openid，立即跳转获取
                this.performImmediateAuth();
            }
        } catch (error) {
            console.log('检查支付模式失败:', error);
        }
    }

    /**
     * 立即执行授权
     */
    async performImmediateAuth() {
        if (this.openid) return;

        try {
            // 构建当前页面的授权回调URL
            const currentUrl = window.location.href.split('?')[0];
            const callbackUrl = encodeURIComponent(currentUrl);
            
            // 获取授权URL
            const response = await fetch(`/index/pay/getAuthUrl?redirect_uri=${callbackUrl}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            const data = await response.json();
            if (data.code === 1 && data.data.auth_url) {
                // 跳转到微信授权页面
                window.location.href = data.data.auth_url;
            } else {
                console.error('获取授权URL失败:', data.msg);
            }
        } catch (error) {
            console.error('获取授权URL异常:', error);
        }
    }

    /**
     * 处理支付提交
     */
    async handlePaymentSubmit(formData, shopCode) {
        // 确保openid已添加
        if (this.openid) {
            formData.append('openid', this.openid);
        }

        try {
            const response = await fetch('/index/shop/submitOrderWithPayment', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            return await this.processPaymentResponse(data, formData, shopCode);
        } catch (error) {
            throw new Error('网络错误: ' + error.message);
        }
    }

    /**
     * 处理支付响应
     */
    async processPaymentResponse(data, originalFormData, shopCode) {
        if (data.code === 1) {
            // 支付订单创建成功
            if (data.data.payment_type === 'epay' && data.data.pay_url) {
                // 易支付 - 直接跳转到易支付页面
                return this.handleEpayRedirect(data.data.order_no, data.data.pay_url);
            } else if (data.data.need_payment && data.data.payment_data) {
                return await this.handlePaymentData(data.data.order_no, data.data.total_price, data.data.payment_data);
            } else if (data.data.need_payment && data.data.code_url) {
                // Native支付
                return this.showPaymentQrCode(data.data.order_no, data.data.total_price, data.data.code_url);
            } else {
                // 免费卡
                return { success: true, type: 'free', data: data.data };
            }
        } else if (data.code === 2) {
            // 需要授权 - 进行授权后重新提交
            return await this.handleAuthAndRetry(data.data.auth_url, originalFormData, shopCode);
        } else {
            throw new Error(data.msg || '订单提交失败');
        }
    }

    /**
     * 处理授权并重试支付
     */
    async handleAuthAndRetry(authUrl, originalFormData, shopCode) {
        // 保存原始表单数据，授权完成后重新提交
        sessionStorage.setItem('pending_payment_data', JSON.stringify({
            formData: Array.from(originalFormData.entries()),
            shopCode: shopCode
        }));

        // 直接跳转授权，不返回Promise（因为页面会跳转）
        window.location.href = authUrl;
        
        // 返回一个标识，让调用方知道正在跳转授权
        return { success: true, type: 'auth_redirect', message: '正在跳转授权...' };
    }

    /**
     * 处理支付数据
     */
    async handlePaymentData(orderNo, totalPrice, paymentData) {
        if (paymentData.mode === 'JSAPI') {
            return await this.directJsapiPay(orderNo, paymentData);
        } else if (paymentData.mode === 'NATIVE') {
            return this.showPaymentQrCode(orderNo, totalPrice, paymentData.code_url);
        }
    }

    /**
     * 直接调起JSAPI支付
     */
    async directJsapiPay(orderNo, paymentData) {
        return new Promise((resolve, reject) => {
            const params = paymentData.params || paymentData;
            
            if (typeof wx !== 'undefined') {
                // 使用新版微信JS-SDK
                wx.requestPayment({
                    timeStamp: params.timeStamp,
                    nonceStr: params.nonceStr,
                    package: params.package,
                    signType: params.signType,
                    paySign: params.paySign,
                    success: function (res) {
                        resolve({ 
                            success: true, 
                            type: 'jsapi', 
                            message: '支付成功',
                            orderNo: orderNo 
                        });
                    },
                    fail: function (res) {
                        if (res.errMsg && res.errMsg.indexOf('cancel') !== -1) {
                            reject(new Error('支付已取消'));
                        } else {
                            reject(new Error('支付失败：' + (res.errMsg || '未知错误')));
                        }
                    }
                });
            } else if (typeof WeixinJSBridge !== 'undefined') {
                // 使用旧版WeixinJSBridge
                WeixinJSBridge.invoke('getBrandWCPayRequest', {
                    appId: params.appId,
                    timeStamp: params.timeStamp,
                    nonceStr: params.nonceStr,
                    package: params.package,
                    signType: params.signType,
                    paySign: params.paySign
                }, function(res) {
                    if (res.err_msg === 'get_brand_wcpay_request:ok') {
                        resolve({ 
                            success: true, 
                            type: 'jsapi', 
                            message: '支付成功',
                            orderNo: orderNo 
                        });
                    } else if (res.err_msg === 'get_brand_wcpay_request:cancel') {
                        reject(new Error('支付已取消'));
                    } else {
                        reject(new Error('支付失败：' + res.err_msg));
                    }
                });
            } else {
                // 微信JS-SDK不可用，跳转支付页面
                window.location.href = '/index/pay/index?order_no=' + orderNo;
                resolve({ 
                    success: true, 
                    type: 'redirect', 
                    message: '正在跳转到支付页面...',
                    orderNo: orderNo 
                });
            }
        });
    }

    /**
     * 处理易支付跳转
     */
    handleEpayRedirect(orderNo, payUrl) {
        // 直接跳转到易支付页面
        window.location.href = payUrl;
        
        return {
            success: true,
            type: 'epay_redirect',
            message: '正在跳转到易支付页面...',
            orderNo: orderNo
        };
    }

    /**
     * 显示支付二维码
     */
    showPaymentQrCode(orderNo, totalPrice, codeUrl) {
        // 这里需要layui支持，暂时返回数据让调用方处理UI
        return {
            success: true,
            type: 'qrcode',
            data: {
                orderNo: orderNo,
                totalPrice: totalPrice,
                codeUrl: codeUrl
            }
        };
    }

    /**
     * 检查并处理授权回调
     */
    handleAuthCallback() {
        const urlParams = new URLSearchParams(window.location.search);
        const authCallback = urlParams.get('auth_callback');
        const openid = urlParams.get('openid');

        if (authCallback && openid) {
            // 保存openid
            this.openid = openid;
            localStorage.setItem('wechat_openid', openid);

            // 检查是否有待处理的支付数据
            const pendingData = sessionStorage.getItem('pending_payment_data');
            if (pendingData) {
                sessionStorage.removeItem('pending_payment_data');
                const { formData, shopCode } = JSON.parse(pendingData);
                
                // 重新构建FormData
                const newFormData = new FormData();
                formData.forEach(([key, value]) => {
                    newFormData.append(key, value);
                });

                // 自动重新提交支付
                setTimeout(() => {
                    this.handlePaymentSubmit(newFormData, shopCode);
                }, 100);
            }

            // 清理URL参数
            const cleanUrl = window.location.href.split('?')[0];
            window.history.replaceState({}, document.title, cleanUrl);
        }
    }
}

// 全局支付处理器实例
window.paymentHandler = new PaymentHandler();

// 页面加载完成后处理授权回调
document.addEventListener('DOMContentLoaded', function() {
    window.paymentHandler.handleAuthCallback();
});
