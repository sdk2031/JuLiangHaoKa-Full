<?php
// +----------------------------------------------------------------------
// | 路由设置
// +----------------------------------------------------------------------

return [
    // pathinfo分隔符
    'pathinfo_depr'         => '/',
    // URL伪静态后缀
    'url_html_suffix'       => false,
    // URL普通方式参数 用于自动生成
    'url_common_param'      => false,
    // URL参数方式 0 按名称成对解析 1 按顺序解析
    'url_param_type'        => 0,
    // 是否开启路由延迟解析
    'url_lazy_route'        => false,
    // 是否强制使用路由
    'url_route_must'        => false,
    // 合并路由规则
    'route_rule_merge'      => false,
    // 路由是否完全匹配
    'route_complete_match'  => false,
    // 使用注解路由
    'route_annotation'      => false,
    // 域名根，如thinkphp.cn
    'url_domain_root'       => '',
    // 是否自动转换URL中的控制器和操作名
    'url_convert'           => true,
    // 默认的访问控制器层
    'url_controller_layer'  => 'controller',
    // 表单请求类型伪装变量
    'var_method'            => '_method',
    // 表单ajax伪装变量
    'var_ajax'              => '_ajax',
    // 表单pjax伪装变量
    'var_pjax'              => '_pjax',
    // 是否开启请求缓存 true自动缓存 支持设置请求缓存规则
    'request_cache'         => false,
    // 请求缓存有效期
    'request_cache_expire'  => null,
    // 全局请求缓存排除规则
    'request_cache_except'  => [],
    // 是否开启路由缓存
    'route_check_cache'     => false,
    // 路由缓存的Key
    'route_check_cache_key' => '',
    // 路由缓存类型及参数
    'route_cache_option'    => [],

    // 默认跳转页面对应的模板文件
    'dispatch_success_tmpl' => app()->getThinkPath() . 'tpl/dispatch_jump.tpl',
    'dispatch_error_tmpl'   => app()->getThinkPath() . 'tpl/dispatch_jump.tpl',

    // 异常页面的模板文件
    'exception_tmpl'        => app()->getThinkPath() . 'tpl/think_exception.tpl',

    // 错误显示信息,非调试模式有效
    'error_message'         => '页面错误！请稍后再试～',
    // 显示错误信息
    'show_error_msg'        => false,
];
