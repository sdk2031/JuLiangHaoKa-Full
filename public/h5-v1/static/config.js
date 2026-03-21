// ========================================
// 自动部署配置 - 自动识别后端URL
// ========================================

// 自动获取当前域名作为后端API地址
function getAutoApiBaseUrl() {
  // 获取当前页面的协议和域名
  const protocol = window.location.protocol; // http: 或 https:
  const hostname = window.location.hostname;  // 域名
  const port = window.location.port;         // 端口号
  
  // 构建完整的后端URL
  let baseUrl = protocol + '//' + hostname;
  
  // 如果有端口号且不是默认端口，则添加端口号
  if (port && port !== '80' && port !== '443') {
    baseUrl += ':' + port;
  }
  
  return baseUrl;
}

// 主要配置 - 自动获取后端地址
window.DEPLOY_CONFIG = {
  API_BASE_URL: getAutoApiBaseUrl(),  // 🚀 自动识别后端地址
  
  API_REQUEST_CONFIG: {
    TIMEOUT: 10000,
    RETRY_COUNT: 3,
    ENABLE_LOG: true
  },
  
  APP_INFO: {
    NAME: '代理商管理系统',
    VERSION: '1.0.0',
    DESCRIPTION: '代理商管理系统移动端'
  },
  
  FEATURE_FLAGS: {
    DEBUG_MODE: false,
    CACHE_ENABLED: true,
    ERROR_REPORT: false,
    PERFORMANCE_MONITOR: false
  }
};

// 多种方式确保配置生效
(function() {
  const API_URL = window.DEPLOY_CONFIG.API_BASE_URL;
  
  // 方式1: 全局函数
  window.getApiBaseUrl = function() {
    return API_URL;
  };
  
  // 方式2: 全局变量
  window.API_BASE_URL = API_URL;
  
  // 方式3: 覆盖可能的配置对象
  window.API_CONFIG = {
    BASE_URL: API_URL,
    TIMEOUT: 10000,
    RETRY_COUNT: 3
  };
  
  // 方式4: 拦截XMLHttpRequest和fetch
  const originalOpen = XMLHttpRequest.prototype.open;
  XMLHttpRequest.prototype.open = function(method, url, ...args) {
    // 如果是相对路径，添加我们的基础URL
    if (url && url.startsWith('/') && !url.startsWith('//')) {
      url = API_URL + url;
      console.log('XHR请求被重定向到:', url);
    }
    return originalOpen.call(this, method, url, ...args);
  };
  
  // 方式5: 拦截fetch
  const originalFetch = window.fetch;
  window.fetch = function(url, options) {
    if (typeof url === 'string' && url.startsWith('/') && !url.startsWith('//')) {
      url = API_URL + url;
      console.log('Fetch请求被重定向到:', url);
    }
    return originalFetch.call(this, url, options);
  };
  
  console.log('🚀 自动部署配置已加载');
  console.log('🌐 当前页面地址:', window.location.href);
  console.log('🎯 自动识别的API地址:', API_URL);
  console.log('✅ 请求拦截器已设置');
  console.log('📋 完整配置:', window.DEPLOY_CONFIG);
})();
