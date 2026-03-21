<?php

return array(
    // 1. 控制台🆕
    array(
        'id' => 'dashboard',
        'name' => '控制台',
        'icon' => 'layui-icon-home',
        'route' => '/admin/index/dashboard',
        'permission' => 'dashboard:view',
        'type' => 1,
        'sort' => 1,
    ),
    
    // 2. 组织架构
    array(
        'id' => 'organization',
        'name' => '组织架构',
        'icon' => 'layui-icon-component',
        'route' => '',
        'type' => 1,
        'sort' => 2,
        'children' => array(
            array(
                'id' => 'admin_user_list',
                'name' => '管理员列表',
                'route' => '/admin/adminuser/index',
                'permission' => 'system:admin',
                'type' => 1,
                'sort' => 1,
                'children' => array(
                    array('id' => 'admin_add_btn', 'name' => '添加管理员', 'type' => 2, 'permission' => 'system:admin:add', 'visible' => false, 'sort' => 1),
                    array('id' => 'admin_edit_btn', 'name' => '编辑管理员', 'type' => 2, 'permission' => 'system:admin:edit', 'visible' => false, 'sort' => 2),
                ),
            ),
            array(
                'id' => 'role_manage',
                'name' => '角色管理',
                'route' => '/admin/role/index',
                'permission' => 'system:role',
                'type' => 1,
                'sort' => 2,
            ),
            array(
                'id' => 'employee_group_manage',
                'name' => '员工管理',
                'route' => '/admin/employee/index',
                'permission' => 'employee:group:view',
                'type' => 1,
                'sort' => 3,
                'children' => array(
                    array('id' => 'employee_group_add_btn', 'name' => '添加分组', 'type' => 2, 'permission' => 'employee:group:add', 'visible' => false, 'sort' => 1),
                    array('id' => 'employee_group_edit_btn', 'name' => '编辑分组', 'type' => 2, 'permission' => 'employee:group:edit', 'visible' => false, 'sort' => 2),
                    array('id' => 'employee_group_delete_btn', 'name' => '删除分组', 'type' => 2, 'permission' => 'employee:group:delete', 'visible' => false, 'sort' => 3),
                    array('id' => 'employee_member_add_btn', 'name' => '添加成员', 'type' => 2, 'permission' => 'employee:member:add', 'visible' => false, 'sort' => 4),
                    array('id' => 'employee_member_remove_btn', 'name' => '移除成员', 'type' => 2, 'permission' => 'employee:member:remove', 'visible' => false, 'sort' => 5),
                    array('id' => 'employee_salary_payout_btn', 'name' => '工资发放', 'type' => 2, 'permission' => 'employee:salary:payout', 'visible' => false, 'sort' => 6),
                    array('id' => 'employee_payout_logs_btn', 'name' => '发放记录', 'type' => 2, 'permission' => 'employee:logs:view', 'visible' => false, 'sort' => 7),
                    array('id' => 'employee_detail_btn', 'name' => '员工详情', 'type' => 2, 'permission' => 'employee:detail:view', 'visible' => false, 'sort' => 8),
                ),
            ),
        ),
    ),
    
    // 3. 产品管理
    array(
        'id' => 'product_manage',
        'name' => '产品管理',
        'icon' => 'layui-icon-template-1',
        'route' => '',
        'permission' => 'product:manage',  // 父菜单独立权限
        'type' => 1,
        'sort' => 3,
        'children' => array(
            array(
                'id' => 'product_list',
                'name' => '产品列表',
                'route' => '/admin/product/index',
                'permission' => 'product:list',
                'type' => 1,
                'sort' => 1,
                'children' => array(
                    array('id' => 'product_add_btn', 'name' => '添加产品', 'type' => 2, 'permission' => 'product:add', 'visible' => false, 'sort' => 1),
                    array('id' => 'product_edit_btn', 'name' => '编辑产品', 'type' => 2, 'permission' => 'product:edit', 'visible' => false, 'sort' => 2),
                ),
            ),
            array(
                'id' => 'product_add_page',
                'name' => '添加产品页面',
                'route' => '/admin/product/add',
                'permission' => 'product:add:page',
                'type' => 1,
                'sort' => 2,
            ),
            array(
                'id' => 'product_collection',
                'name' => '产品合集',
                'route' => '/admin/product_collection/index',
                'permission' => 'product:collection',
                'type' => 1,
                'sort' => 3,
            ),
            array(
                'id' => 'number_pool_manage',
                'name' => '号码池管理',
                'route' => '/admin/number_pool/index',
                'permission' => 'product:number_pool',
                'type' => 1,
                'sort' => 4,
            ),
        ),
    ),
    
    // 4. 订单管理
    array(
        'id' => 'order_manage',
        'name' => '订单管理',
        'icon' => 'layui-icon-form',
        'route' => '',
        'permission' => 'order:manage',  // 父菜单独立权限
        'type' => 1,
        'sort' => 4,
        'children' => array(
            array(
                'id' => 'order_list',
                'name' => '订单列表',
                'route' => '/admin/order/index',
                'permission' => 'order:list',
                'type' => 1,
                'sort' => 1,
                'children' => array(
                    array('id' => 'order_view_btn', 'name' => '查看订单', 'type' => 2, 'permission' => 'order:view', 'visible' => false, 'sort' => 1),
                ),
            ),
            array(
                'id' => 'order_batch',
                'name' => '批量处理',
                'route' => '/admin/order_batch/index',
                'permission' => 'order:batch',
                'type' => 1,
                'sort' => 2,
            ),
        ),
    ),
    
    // 5. 代理管理
    array(
        'id' => 'agent_manage',
        'name' => '代理管理',
        'icon' => 'layui-icon-user',
        'route' => '',
        'permission' => 'agent:manage',  
        'type' => 1,
        'sort' => 5,
        'children' => array(
            array(
                'id' => 'agent_list',
                'name' => '代理列表',
                'route' => '/admin/agent/index',
                'permission' => 'agent:list',
                'type' => 1,
                'sort' => 1,
                'children' => array(
                    array('id' => 'agent_edit_btn', 'name' => '编辑代理', 'type' => 2, 'permission' => 'agent:edit', 'visible' => false, 'sort' => 1),
                ),
            ),
            array(
                'id' => 'secret_price',
                'name' => '密价等级',
                'route' => '/admin/secretpricelevel/index',
                'permission' => 'agent:price',
                'type' => 1,
                'sort' => 2,
            ),
            array(
                'id' => 'agent_migrate',
                'name' => '代理迁移',
                'route' => '/admin/agentmigrate/index',
                'permission' => 'agent:migrate',
                'type' => 1,
                'sort' => 3,
                'plugin' => 'agent_migrate', 
            ),
        ),
    ),
    
    // 6. 财务管理
    array(
        'id' => 'finance_manage',
        'name' => '财务管理',
        'icon' => 'layui-icon-rmb',
        'route' => '',
        'permission' => 'finance:manage',  
        'type' => 1,
        'sort' => 6,
        'children' => array(
            array(
                'id' => 'withdraw_manage',
                'name' => '提现管理',
                'route' => '/admin/withdraw/index',
                'permission' => 'finance:withdraw',
                'type' => 1,
                'sort' => 1,
                'children' => array(
                    array('id' => 'withdraw_approve_btn', 'name' => '审核通过', 'type' => 2, 'permission' => 'finance:approve', 'visible' => false, 'sort' => 1),
                    array('id' => 'withdraw_reject_btn', 'name' => '审核拒绝', 'type' => 2, 'permission' => 'finance:reject', 'visible' => false, 'sort' => 2),
                ),
            ),
            array(
                'id' => 'balance_log',
                'name' => '资金变动',
                'route' => '/admin/balance/logs',
                'permission' => 'finance:log',
                'type' => 1,
                'sort' => 2,
            ),
            array(
                'id' => 'payment_records',
                'name' => '支付记录',
                'route' => '/admin/payment/records',
                'permission' => 'finance:payment',
                'type' => 1,
                'sort' => 3,
            ),
        ),
    ),
    
    // 7. 工单管理
    array(
        'id' => 'ticket_manage',
        'name' => '工单管理',
        'icon' => 'layui-icon-service',
        'route' => '',
        'permission' => 'service:manage',  
        'type' => 1,
        'sort' => 7,
        'plugin' => 'workorder', 
        'children' => array(
            array(
                'id' => 'ticket_list',
                'name' => '工单列表',
                'route' => '/admin/ticket/index',
                'permission' => 'service:ticket',
                'type' => 1,
                'sort' => 1,
            ),
            array(
                'id' => 'ticket_category',
                'name' => '分类管理',
                'route' => '/admin/ticketcategory/index',
                'permission' => 'service:category',
                'type' => 1,
                'sort' => 2,
            ),
        ),
    ),
    
    // 8. 营销活动
    array(
        'id' => 'activity_manage',
        'name' => '营销活动',
        'icon' => 'layui-icon-gift',
        'route' => '',
        'permission' => 'marketing:manage',  
        'type' => 1,
        'sort' => 8,
        'plugin' => 'marketing',
        'children' => array(
            array(
                'id' => 'activity_list',
                'name' => '活动管理',
                'route' => '/admin/activity/index',
                'permission' => 'marketing:activity',
                'type' => 1,
                'sort' => 1,
            ),
            array(
                'id' => 'activity_claims',
                'name' => '领取记录',
                'route' => '/admin/activity/claims',
                'permission' => 'marketing:claims',
                'type' => 1,
                'sort' => 2,
            ),
        ),
    ),
    
    // 9. 内容管理
    array(
        'id' => 'content_manage',
        'name' => '内容管理',
        'icon' => 'layui-icon-notice',
        'route' => '',
        'permission' => 'content:manage',  
        'type' => 1,
        'sort' => 9,
        'children' => array(
            array(
                'id' => 'announcement_manage',
                'name' => '公告管理',
                'route' => '/admin/announcement/index',
                'permission' => 'content:announcement',
                'type' => 1,
                'sort' => 1,
            ),
            array(
                'id' => 'article_manage',
                'name' => '文章管理',
                'route' => '/admin/article/index',
                'permission' => 'content:article',
                'type' => 1,
                'sort' => 2,
            ),
            array(
                'id' => 'message_manage',
                'name' => '站内信管理',
                'route' => '/admin/message/index',
                'permission' => 'service:message',
                'type' => 1,
                'sort' => 3,
            ),
        ),
    ),
    
    // 10. 短信日志
    array(
        'id' => 'sms_log',
        'name' => '短信日志',
        'icon' => 'layui-icon-cellphone',
        'route' => '/admin/sms/logs',
        'permission' => 'system:sms',
        'type' => 1,
        'sort' => 10,
    ),
    
    // 11. 黑名单
    array(
        'id' => 'blacklist_manage',
        'name' => '黑名单',
        'icon' => 'layui-icon-username',
        'route' => '',
        'permission' => 'system:blacklist:manage', 
        'type' => 1,
        'sort' => 11,
        'children' => array(
            array(
                'id' => 'blacklist_list',
                'name' => '黑名单管理',
                'route' => '/admin/blacklist/index',
                'permission' => 'system:blacklist',
                'type' => 1,
                'sort' => 1,
            ),
            array(
                'id' => 'blacklist_log',
                'name' => '操作日志',
                'route' => '/admin/blacklist/log',
                'permission' => 'system:blacklist:log',
                'type' => 1,
                'sort' => 2,
            ),
        ),
    ),
    
    // 12. H5管理
    array(
        'id' => 'h5_manage',
        'name' => 'H5管理',
        'icon' => 'layui-icon-component',
        'route' => '',
        'permission' => 'h5:manage',  
        'type' => 1,
        'sort' => 12,
        'plugin' => 'h5', 
        'children' => array(
            array(
                'id' => 'h5_config',
                'name' => '配置管理',
                'route' => '/admin/configh5/index',
                'permission' => 'h5:config',
                'type' => 1,
                'sort' => 1,
            ),
        ),
    ),
    
    // 13. 一键转图
    array(
        'id' => 'imagetemplate',
        'name' => '一键转图',
        'icon' => 'layui-icon-picture',
        'route' => '/admin/imagetemplate/index',
        'permission' => 'imagetemplate:manage',
        'type' => 1,
        'sort' => 13,
        'plugin' => 'imagetemplate', 
    ),
    
    // 14. 渠道API
    array(
        'id' => 'api_manage',
        'name' => '渠道API',
        'icon' => 'layui-icon-link',
        'route' => '/admin/api/index',
        'permission' => 'product:api',
        'type' => 1,
        'sort' => 14,
    ),
    
    // 15. 系统管理
    array(
        'id' => 'system_manage',
        'name' => '系统管理',
        'icon' => 'layui-icon-set',
        'route' => '',
        'permission' => 'system:manage',  
        'type' => 1,
        'sort' => 15,
        'children' => array(
            array(
                'id' => 'system_config',
                'name' => '系统配置',
                'route' => '/admin/system/config',
                'permission' => 'system:config',
                'type' => 1,
                'sort' => 1,
            ),
            array(
                'id' => 'system_backup',
                'name' => '数据备份',
                'route' => '/admin/backup/index',
                'permission' => 'system:backup',
                'type' => 1,
                'sort' => 2,
            ),
            array(
                'id' => 'tool',
                'name' => '运维工具',
                'route' => '/admin/tool/index',
                'permission' => 'tool:index',
                'type' => 1,
                'sort' => 3,
            ),
        ),
    ),
    
    // 16. 插件市场
    array(
        'id' => 'plugin_market',
        'name' => '插件市场',
        'icon' => 'layui-icon-app',
        'route' => '/admin/pluginmarket/index',
        'permission' => 'plugin:market',
        'type' => 1,
        'sort' => 16,
    ),
);
