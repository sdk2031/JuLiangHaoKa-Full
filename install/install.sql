

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for activities
-- ----------------------------
DROP TABLE IF EXISTS `activities`;
CREATE TABLE `activities`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '活动ID',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '活动标题',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '活动描述',
  `type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '活动类型：1=订单数量，2=推广下级',
  `duration_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '活动时长类型：1=当月，2=长期',
  `start_time` int(11) NOT NULL COMMENT '开始时间',
  `end_time` int(11) NULL DEFAULT NULL COMMENT '结束时间（长期活动为NULL）',
  `target_value` int(11) NOT NULL COMMENT '目标值（订单数量或下级数量）',
  `order_target` int(11) NULL DEFAULT NULL COMMENT '订单数量目标（组合条件时使用）',
  `referral_target` int(11) NULL DEFAULT NULL COMMENT '推广下级目标（组合条件时使用）',
  `condition_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '条件类型：1=单一条件，2=组合条件（订单+推广）',
  `reward_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '奖励类型：1=余额，2=实物',
  `reward_amount` decimal(10, 2) NULL DEFAULT NULL COMMENT '余额奖励金额',
  `reward_item` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '实物奖励名称',
  `reward_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '奖励描述',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0=禁用，1=启用',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE,
  INDEX `idx_type`(`type`) USING BTREE,
  INDEX `idx_duration_type`(`duration_type`) USING BTREE,
  INDEX `idx_start_time`(`start_time`) USING BTREE,
  INDEX `idx_end_time`(`end_time`) USING BTREE,
  INDEX `idx_condition_type`(`condition_type`) USING BTREE,
  INDEX `idx_order_target`(`order_target`) USING BTREE,
  INDEX `idx_referral_target`(`referral_target`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 16 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '营销活动表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of activities
-- ----------------------------

-- ----------------------------
-- Table structure for activity_claims
-- ----------------------------
DROP TABLE IF EXISTS `activity_claims`;
CREATE TABLE `activity_claims`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '领取记录ID',
  `activity_id` int(11) UNSIGNED NOT NULL COMMENT '活动ID',
  `agent_id` int(11) UNSIGNED NOT NULL COMMENT '代理商ID',
  `target_achieved` int(11) NOT NULL COMMENT '达成的目标值',
  `reward_type` tinyint(1) NOT NULL COMMENT '奖励类型：1=余额，2=实物',
  `reward_amount` decimal(10, 2) NULL DEFAULT NULL COMMENT '领取的余额金额',
  `reward_item` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '领取的实物奖励',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态：0=待发放，1=已发放，2=发放失败',
  `claim_time` int(11) NOT NULL COMMENT '领取时间',
  `process_time` int(11) NULL DEFAULT NULL COMMENT '处理时间',
  `remark` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_activity_agent`(`activity_id`, `agent_id`) USING BTREE,
  INDEX `idx_agent_id`(`agent_id`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE,
  INDEX `idx_claim_time`(`claim_time`) USING BTREE,
  CONSTRAINT `fk_activity_claims_activity_id` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_activity_claims_agent_id` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '活动领取记录表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of activity_claims
-- ----------------------------

-- ----------------------------
-- Table structure for activity_conditions
-- ----------------------------
DROP TABLE IF EXISTS `activity_conditions`;
CREATE TABLE `activity_conditions`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '条件ID',
  `activity_id` int(11) UNSIGNED NOT NULL COMMENT '活动ID',
  `condition_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '条件类型：order_count, referral_count, order_amount等',
  `target_value` int(11) NOT NULL COMMENT '目标值',
  `operator` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '>=' COMMENT '操作符：>=, =, >, <等',
  `weight` decimal(3, 2) NULL DEFAULT 1.00 COMMENT '权重（用于组合条件计算）',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '条件描述',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_activity_id`(`activity_id`) USING BTREE,
  INDEX `idx_condition_type`(`condition_type`) USING BTREE,
  CONSTRAINT `fk_activity_conditions_activity_id` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '活动条件详情表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of activity_conditions
-- ----------------------------

-- ----------------------------
-- Table structure for activity_rewards
-- ----------------------------
DROP TABLE IF EXISTS `activity_rewards`;
CREATE TABLE `activity_rewards`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '奖励ID',
  `activity_id` int(11) UNSIGNED NOT NULL COMMENT '活动ID',
  `reward_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '奖励类型：1=余额，2=实物',
  `reward_amount` decimal(10, 2) NULL DEFAULT NULL COMMENT '余额奖励金额',
  `reward_item` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '实物奖励名称',
  `reward_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '奖励描述',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_activity_id`(`activity_id`) USING BTREE,
  INDEX `idx_reward_type`(`reward_type`) USING BTREE,
  CONSTRAINT `fk_activity_rewards_activity_id` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '活动奖励记录表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of activity_rewards
-- ----------------------------

-- ----------------------------
-- Table structure for admin_agent_bind
-- ----------------------------
DROP TABLE IF EXISTS `admin_agent_bind`;
CREATE TABLE `admin_agent_bind`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `admin_id` int(11) NOT NULL COMMENT '管理员ID',
  `agent_id` int(11) NOT NULL COMMENT '代理商ID',
  `create_time` int(11) NOT NULL COMMENT '绑定时间',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态 1正常 0禁用',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_admin_agent`(`admin_id`, `agent_id`) USING BTREE,
  INDEX `idx_admin_id`(`admin_id`) USING BTREE,
  INDEX `idx_agent_id`(`agent_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '管理员代理商绑定表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of admin_agent_bind
-- ----------------------------


-- ----------------------------
-- Table structure for admin_operation_logs
-- ----------------------------
DROP TABLE IF EXISTS `admin_operation_logs`;
CREATE TABLE `admin_operation_logs`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `admin_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '',
  `module` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '',
  `action` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '',
  `request_method` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '',
  `request_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '',
  `ip_address` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `create_time` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_admin_id`(`admin_id`) USING BTREE,
  INDEX `idx_create_time`(`create_time`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '操作日志表' ROW_FORMAT = DYNAMIC;


CREATE TABLE IF NOT EXISTS `admin_role_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `role_id` int(11) NOT NULL COMMENT '角色ID',
  `permission` varchar(100) NOT NULL COMMENT '权限标识',
  `create_time` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_permission` (`role_id`, `permission`),
  KEY `idx_role_id` (`role_id`),
  KEY `idx_permission` (`permission`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='角色权限关系表';


-- ----------------------------
-- Table structure for admin_role_relation
-- ----------------------------
DROP TABLE IF EXISTS `admin_role_relation`;
CREATE TABLE `admin_role_relation`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL COMMENT '管理员ID',
  `role_id` int(11) NOT NULL COMMENT '角色ID',
  `create_time` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_admin_role`(`admin_id`, `role_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '管理员角色关联表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of admin_role_relation
-- ----------------------------

-- ----------------------------
-- Table structure for admin_roles
-- ----------------------------
DROP TABLE IF EXISTS `admin_roles`;
CREATE TABLE `admin_roles`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '角色ID',
  `role_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '角色名称',
  `role_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '角色标识',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '角色描述',
  `data_scope` tinyint(1) NOT NULL DEFAULT 1 COMMENT '数据权限：1=全部，2=自定义，3=本人',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0=禁用，1=启用',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `create_time` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `role_code`(`role_code`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '管理员角色表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of admin_roles
-- ----------------------------
INSERT INTO `admin_roles` VALUES (1, '超级管理员', 'super_admin', '拥有系统所有权限', 1, 1, 1, 1760426987, 1760426987);

-- ----------------------------
-- Table structure for admins
-- ----------------------------
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '用户名',
  `password` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '密码',
  `salt` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '密码盐',
  `nickname` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '昵称',
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '邮箱',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态 1正常 0禁用',
  `last_login_time` int(11) NOT NULL DEFAULT 0 COMMENT '最后登录时间',
  `last_login_ip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '最后登录IP',
  `create_time` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT 0 COMMENT '更新时间',
  `default_agent_id` int(11) NOT NULL DEFAULT 0 COMMENT '默认代理ID',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `username`(`username`) USING BTREE,
  INDEX `idx_default_agent_id`(`default_agent_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '管理员表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of admins
-- ----------------------------

-- ----------------------------
-- Table structure for agent_accounts
-- ----------------------------
DROP TABLE IF EXISTS `agent_accounts`;
CREATE TABLE `agent_accounts`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agent_id` int(11) NOT NULL COMMENT '代理商ID',
  `balance` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '余额',
  `total_recharge` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '总充值',
  `total_consume` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '总消费',
  `frozen_amount` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '冻结金额',
  `update_time` int(11) NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `agent_id`(`agent_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 14 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '代理商账户表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of agent_accounts
-- ----------------------------

-- ----------------------------
-- Table structure for agent_balance_logs
-- ----------------------------
DROP TABLE IF EXISTS `agent_balance_logs`;
CREATE TABLE `agent_balance_logs`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `agent_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '代理商ID',
  `order_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联订单ID（订单结算时使用）',
  `order_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '订单号（便于查询）',
  `withdraw_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联提现ID（提现相关操作时使用）',
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '变动类型：in=收入,out=支出,pending=待结算',
  `sub_type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '细分类型：order=订单佣金,parent=上级分佣,secret_price=密价奖励,markup=付费卡加价,withdraw=提现,withdraw_refund=提现退回,salary=工资,manual=手动',
  `amount` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '变动金额（正数为增加，负数为减少）',
  `balance_before` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '变动前余额',
  `balance_after` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '变动后余额',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '备注（预留）',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：1=有效，0=已作废（待结算已结算）',
  `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `agent_id`(`agent_id`) USING BTREE,
  INDEX `order_id`(`order_id`) USING BTREE,
  INDEX `order_no`(`order_no`) USING BTREE,
  INDEX `withdraw_id`(`withdraw_id`) USING BTREE,
  INDEX `type`(`type`) USING BTREE,
  INDEX `create_time`(`create_time`) USING BTREE,
  INDEX `idx_agent_type_time`(`agent_id`, `type`, `create_time`) USING BTREE,
  INDEX `idx_agent_order`(`agent_id`, `order_id`) USING BTREE,
  INDEX `idx_order_no`(`order_no`) USING BTREE,
  INDEX `idx_withdraw_id`(`withdraw_id`) USING BTREE,
  INDEX `idx_type`(`type`) USING BTREE,
  INDEX `idx_sub_type`(`sub_type`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE,
  INDEX `idx_type_status`(`type`, `status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 127 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '代理商余额变动日志表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of agent_balance_logs
-- ----------------------------

-- ----------------------------
-- Table structure for agent_idcard_logs
-- ----------------------------
DROP TABLE IF EXISTS `agent_idcard_logs`;
CREATE TABLE `agent_idcard_logs`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agent_id` int(11) NOT NULL COMMENT '代理商ID',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '姓名',
  `id_card` varchar(18) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '身份证号',
  `status` tinyint(1) NOT NULL COMMENT '认证状态 0=失败 1=成功',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '备注',
  `source` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'unknown' COMMENT '验证来源 web=电脑端 mobile=手机端',
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT 'IP地址',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `agent_id`(`agent_id`) USING BTREE,
  INDEX `create_time`(`create_time`) USING BTREE,
  INDEX `source`(`source`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 7 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '代理商实名认证日志' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of agent_idcard_logs
-- ----------------------------

-- ----------------------------
-- Table structure for agent_migrate_logs
-- ----------------------------
DROP TABLE IF EXISTS `agent_migrate_logs`;
CREATE TABLE `agent_migrate_logs`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agent_id` int(11) NOT NULL COMMENT '被迁移的代理ID',
  `agent_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '代理名称',
  `old_parent_id` int(11) NULL DEFAULT NULL COMMENT '原上级代理ID',
  `old_parent_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '原上级名称',
  `new_parent_id` int(11) NOT NULL COMMENT '新上级代理ID',
  `new_parent_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '新上级名称',
  `old_invite_code_id` int(11) NULL DEFAULT NULL COMMENT '原邀请码ID',
  `new_invite_code_id` int(11) NOT NULL COMMENT '新邀请码ID',
  `migrate_time` datetime NOT NULL COMMENT '迁移时间',
  `operator_id` int(11) NULL DEFAULT NULL COMMENT '操作人ID',
  `operator_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '操作人姓名',
  `affected_orders` int(11) NULL DEFAULT 0 COMMENT '影响的订单数（需要补充快照的）',
  `affected_order_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '受影响的订单ID列表(JSON)',
  `snapshot_success` int(11) NULL DEFAULT 0 COMMENT '快照生成成功数',
  `remark` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '迁移原因/备注',
  `status` tinyint(1) NULL DEFAULT 1 COMMENT '状态：1-成功 0-失败',
  `error_msg` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '错误信息',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_agent_id`(`agent_id`) USING BTREE,
  INDEX `idx_migrate_time`(`migrate_time`) USING BTREE,
  INDEX `idx_operator_id`(`operator_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 15 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '代理迁移记录表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of agent_migrate_logs
-- ----------------------------

-- ----------------------------
-- Table structure for agent_payment_methods
-- ----------------------------
DROP TABLE IF EXISTS `agent_payment_methods`;
CREATE TABLE `agent_payment_methods`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `agent_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '代理商ID',
  `payment_type` enum('alipay','bank') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'alipay' COMMENT '收款方式：alipay=支付宝,bank=银行卡',
  `account` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '收款账户',
  `account_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '收款人姓名',
  `bank_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '银行名称（银行卡专用）',
  `bank_branch` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '开户行支行（银行卡专用）',
  `is_default` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否默认：0=否,1=是',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0=禁用,1=启用',
  `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `agent_id`(`agent_id`) USING BTREE,
  INDEX `payment_type`(`payment_type`) USING BTREE,
  INDEX `is_default`(`is_default`) USING BTREE,
  INDEX `status`(`status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 13 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '代理商收款方式表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of agent_payment_methods
-- ----------------------------

-- ----------------------------
-- Table structure for agent_product_sort
-- ----------------------------
DROP TABLE IF EXISTS `agent_product_sort`;
CREATE TABLE `agent_product_sort`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `agent_id` int(11) UNSIGNED NOT NULL COMMENT '代理ID',
  `sort_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '排序数据JSON，数组顺序即为显示顺序',
  `create_time` datetime NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_agent_id`(`agent_id`) USING BTREE COMMENT '代理ID唯一索引'
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '代理产品自定义排序表（JSON格式）' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of agent_product_sort
-- ----------------------------

-- ----------------------------
-- Table structure for agent_shop
-- ----------------------------
DROP TABLE IF EXISTS `agent_shop`;
CREATE TABLE `agent_shop`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '店铺ID',
  `agent_id` int(11) NOT NULL COMMENT '代理ID',
  `shop_code` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '店铺唯一标识码',
  `shop_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '我的店铺' COMMENT '店铺名称',
  `shop_logo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '店铺Logo',
  `shop_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '店铺描述',
  `banner_images` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '轮播Banner图片JSON数组',
  `banner_links` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT 'Banner链接JSON数组，与banner_images对应',
  `banner_enabled` tinyint(1) NULL DEFAULT 1 COMMENT '是否启用Banner',
  `distribution_enabled` tinyint(1) NULL DEFAULT 0 COMMENT '是否启用分销功能',
  `default_agent_level` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '默认注册代理等级邀请码',
  `popup_notice` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '弹窗公告内容',
  `popup_enabled` tinyint(1) NULL DEFAULT 0 COMMENT '是否启用弹窗公告',
  `scroll_notice` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '横屏滚动公告',
  `scroll_enabled` tinyint(1) NULL DEFAULT 0 COMMENT '是否启用滚动公告',
  `theme_color` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '#1890ff' COMMENT '主题色',
  `contact_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '联系电话',
  `contact_wechat` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '微信号',
  `contact_qq` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT 'QQ号',
  `service_qrcode` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '客服二维码图片路径',
  `service_link` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '客服链接（微信客服链接等）',
  `service_text` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '联系客服' COMMENT '客服按钮文字',
  `total_visits` int(11) NULL DEFAULT 0 COMMENT '总访问量',
  `total_orders` int(11) NULL DEFAULT 0 COMMENT '总订单数',
  `month_visits` int(11) NULL DEFAULT 0 COMMENT '本月访问量',
  `month_orders` int(11) NULL DEFAULT 0 COMMENT '本月订单数',
  `today_visits` int(11) NULL DEFAULT 0 COMMENT '今日访问量',
  `today_orders` int(11) NULL DEFAULT 0 COMMENT '今日订单数',
  `status` tinyint(1) NULL DEFAULT 1 COMMENT '店铺状态 1启用 0禁用',
  `create_time` bigint(13) NOT NULL COMMENT '创建时间',
  `update_time` bigint(13) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `shop_code`(`shop_code`) USING BTREE,
  UNIQUE INDEX `agent_id`(`agent_id`) USING BTREE,
  INDEX `status`(`status`) USING BTREE,
  INDEX `distribution_enabled`(`distribution_enabled`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 30 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '代理店铺表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of agent_shop
-- ----------------------------

-- ----------------------------
-- Table structure for agent_shop_visits
-- ----------------------------
DROP TABLE IF EXISTS `agent_shop_visits`;
CREATE TABLE `agent_shop_visits`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shop_id` int(11) NOT NULL COMMENT '店铺ID',
  `agent_id` int(11) NOT NULL COMMENT '代理ID',
  `visitor_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '访客IP',
  `location` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT 'IP对应的地理位置',
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '用户代理',
  `referer` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '来源页面',
  `visit_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'shop' COMMENT '访问类型：shop店铺首页，product商品页面',
  `product_id` int(11) NULL DEFAULT NULL COMMENT '访问的商品ID（如果是商品页面）',
  `visit_time` bigint(13) NOT NULL COMMENT '访问时间',
  `visit_date` date NOT NULL COMMENT '访问日期',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `shop_id`(`shop_id`) USING BTREE,
  INDEX `agent_id`(`agent_id`) USING BTREE,
  INDEX `visit_date`(`visit_date`) USING BTREE,
  INDEX `visitor_ip`(`visitor_ip`) USING BTREE,
  INDEX `visit_type`(`visit_type`) USING BTREE,
  INDEX `product_id`(`product_id`) USING BTREE,
  INDEX `location`(`location`) USING BTREE,
  INDEX `idx_shop_time`(`shop_id`, `visit_time`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3078 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '店铺访问记录表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of agent_shop_visits
-- ----------------------------

-- ----------------------------
-- Table structure for agents
-- ----------------------------
DROP TABLE IF EXISTS `agents`;
CREATE TABLE `agents`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `parent_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '上级用户ID',
  `username` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户名',
  `mobile` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '手机号',
  `balance` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '余额（元）',
  `total_money` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '累计获得佣金总额',
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '密码',
  `wechat_openid` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'QQ OpenID',
  `qq_openid` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'QQ OpenID',
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '头像URL',
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'API认证令牌',
  `api_secret_key` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'API密钥',
  `api_callback_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'API回调URL',
  `api_enabled` tinyint(1) NULL DEFAULT 0 COMMENT 'API功能是否启用(0:禁用 1:启用)',
  `salt` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '密码盐',
  `status` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '状态0封禁  1正常',
  `invite_code_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '邀请码ID',
  `agent_level` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal' COMMENT '代理级别',
  `secret_price_level_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '密价等级ID',
  `auto_markup_enabled` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否启用自动加价(0-否,1-是)',
  `auto_markup_amount` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '自动加价金额(固定金额)',
  `total_orders` int(11) NOT NULL DEFAULT 0 COMMENT '总订单数',
  `total_jihuo` int(10) NOT NULL DEFAULT 0 COMMENT '总激活',
  `month_orders` int(11) NOT NULL DEFAULT 0 COMMENT '本月订单数',
  `month_jihuo` int(10) NOT NULL DEFAULT 0 COMMENT '本月激活',
  `real_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '实名姓名',
  `id_card` varchar(18) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '身份证号',
  `is_verified` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否实名认证 0=未认证 1=已认证',
  `verify_time` bigint(16) UNSIGNED NULL DEFAULT NULL COMMENT '认证时间',
  `create_time` bigint(16) UNSIGNED NULL DEFAULT NULL COMMENT '加入时间',
  `last_login_time` bigint(16) UNSIGNED NULL DEFAULT NULL COMMENT '最后登录时间',
  `last_login_ip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '最后登录IP',
  `login_failure` tinyint(4) UNSIGNED NOT NULL DEFAULT 0 COMMENT '登录失败次数',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `username`(`username`) USING BTREE,
  UNIQUE INDEX `mobile`(`mobile`) USING BTREE,
  UNIQUE INDEX `uk_wechat_openid`(`wechat_openid`) USING BTREE,
  UNIQUE INDEX `uk_qq_openid`(`qq_openid`) USING BTREE,
  INDEX `idx_secret_price_level`(`secret_price_level_id`) USING BTREE,
  INDEX `idx_token`(`token`) USING BTREE,
  INDEX `idx_api_enabled`(`api_enabled`) USING BTREE,
  INDEX `idx_api_secret_key`(`api_secret_key`) USING BTREE,
  INDEX `idx_wechat_openid`(`wechat_openid`) USING BTREE,
  INDEX `idx_qq_openid`(`qq_openid`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 10001 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '代理商表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of agents
-- ----------------------------

-- ----------------------------
-- Table structure for available_numbers
-- ----------------------------
DROP TABLE IF EXISTS `available_numbers`;
CREATE TABLE `available_numbers`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '号码',
  `operator` tinyint(1) NOT NULL COMMENT '运营商(1=移动,2=联通,3=电信,4=广电)',
  `province` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '省份',
  `city` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '城市',
  `number_type` tinyint(1) NULL DEFAULT 0 COMMENT '号码类型(0=普通,1=靓号)',
  `product_id` int(11) NULL DEFAULT NULL COMMENT '关联商品ID',
  `description` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '号码描述',
  `agent_id` int(11) NULL DEFAULT NULL COMMENT '代理ID',
  `reserve_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '预占IP',
  `is_used` tinyint(1) NULL DEFAULT 0 COMMENT '是否使用(0=未使用,1=已使用)',
  `status` tinyint(1) NULL DEFAULT 1 COMMENT '状态(0=禁用,1=启用)',
  `sort` int(11) NULL DEFAULT 0 COMMENT '排序',
  `created_time` int(11) NOT NULL COMMENT '创建时间',
  `updated_time` int(11) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_number`(`number`) USING BTREE,
  INDEX `idx_operator`(`operator`) USING BTREE,
  INDEX `idx_province_city`(`province`, `city`) USING BTREE,
  INDEX `idx_number_type`(`number_type`) USING BTREE,
  INDEX `idx_product_id`(`product_id`) USING BTREE,
  INDEX `idx_agent_id`(`agent_id`) USING BTREE,
  INDEX `idx_status_used`(`status`, `is_used`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 17 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '号码池管理表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of available_numbers
-- ----------------------------

-- ----------------------------
-- Table structure for blacklist
-- ----------------------------
DROP TABLE IF EXISTS `blacklist`;
CREATE TABLE `blacklist`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('mobile','id_card','both') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '黑名单类型：mobile-手机号，id_card-身份证，both-全部',
  `mobile` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '手机号',
  `id_card` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '身份证号',
  `hit_count` int(11) NULL DEFAULT 0 COMMENT '命中次数',
  `reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '拉黑原因',
  `source` enum('admin','agent','auto') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'admin' COMMENT '来源：admin-管理员添加，agent-代理添加，auto-店铺命中',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  `update_time` datetime NOT NULL COMMENT '更新时间',
  `status` tinyint(1) NULL DEFAULT 1 COMMENT '状态：1-启用，0-禁用',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_mobile`(`mobile`) USING BTREE,
  INDEX `idx_id_card`(`id_card`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE,
  INDEX `idx_source`(`source`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 9 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '黑名单表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of blacklist
-- ----------------------------

-- ----------------------------
-- Table structure for blacklist_config
-- ----------------------------
DROP TABLE IF EXISTS `blacklist_config`;
CREATE TABLE `blacklist_config`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `check_mobile` tinyint(1) NULL DEFAULT 1 COMMENT '是否检测手机号',
  `check_id_card` tinyint(1) NULL DEFAULT 1 COMMENT '是否检测身份证号',
  `is_enabled` tinyint(1) NULL DEFAULT 1 COMMENT '黑名单功能是否启用',
  `update_time` datetime NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '黑名单配置表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of blacklist_config
-- ----------------------------
INSERT INTO `blacklist_config` VALUES (1, 1, 1, 1, '2025-10-10 23:09:13');
INSERT INTO `blacklist_config` VALUES (2, 1, 1, 1, '2025-10-10 23:09:26');

-- ----------------------------
-- Table structure for blacklist_log
-- ----------------------------
DROP TABLE IF EXISTS `blacklist_log`;
CREATE TABLE `blacklist_log`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL COMMENT '操作管理员ID',
  `admin_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '操作管理员姓名',
  `action` enum('add','remove','hit','config') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '操作类型：add-添加，remove-移除，hit-命中，config-配置',
  `mobile` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '手机号',
  `id_card` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '身份证号',
  `reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '操作原因',
  `order_id` int(11) NULL DEFAULT NULL COMMENT '关联订单ID（如果是从订单拉黑）',
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '操作IP地址',
  `create_time` datetime NOT NULL COMMENT '操作时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_admin_id`(`admin_id`) USING BTREE,
  INDEX `idx_action`(`action`) USING BTREE,
  INDEX `idx_create_time`(`create_time`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 26 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '黑名单操作日志表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of blacklist_log
-- ----------------------------

-- ----------------------------
-- Table structure for config_api
-- ----------------------------
DROP TABLE IF EXISTS `config_api`;
CREATE TABLE `config_api`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `api_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'API类型标识(如mf58,lanchang等)',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'API名称',
  `api_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'API密钥/账号',
  `api_secret` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT 'API密钥/密码',
  `api_token` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT 'API Token（广梦云推广码/172号卡后台Token等）',
  `api_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'API地址',
  `callback_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '回调地址',
  `status` tinyint(1) NULL DEFAULT 0 COMMENT '状态(0-禁用,1-启用)',
  `commission_deduction_enabled` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否启用抽佣:0=否,1=是',
  `commission_deduction_amount` int(11) NOT NULL DEFAULT 0 COMMENT '抽佣金额(整数)',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '备注说明',
  `extra_config` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '额外配置JSON',
  `create_time` int(11) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) NULL DEFAULT NULL COMMENT '更新时间',
  `sync_settlement` tinyint(1) NOT NULL DEFAULT 0 COMMENT '同步结算状态:0=关闭,1=开启',
  `product_sync_enabled` tinyint(1) NULL DEFAULT 0 COMMENT '商品同步开关',
  `product_sync_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'light' COMMENT '同步方式:full/light/online',
  `product_sync_interval` int(11) NULL DEFAULT 60 COMMENT '同步间隔(分钟)',
  `product_shop_type` tinyint(1) NULL DEFAULT 0 COMMENT '商品类型:0=全部,1=次月返,2=秒返(蓝畅专用)',
  `product_sync_last_time` datetime NULL DEFAULT NULL COMMENT '上次同步时间',
  `product_sync_result` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '商品同步结果',
  `order_sync_enabled` tinyint(1) NULL DEFAULT 0 COMMENT '订单查询开关',
  `order_sync_interval` int(11) NULL DEFAULT 10 COMMENT '查询间隔(分钟)',
  `order_sync_limit` int(11) NULL DEFAULT 1000 COMMENT '每次查询订单数量',
  `order_sync_days` int(11) NULL DEFAULT 120 COMMENT '查询天数范围',
  `order_sync_last_time` datetime NULL DEFAULT NULL COMMENT '上次查询时间',
  `order_sync_result` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '订单查询结果',
  `product_filter_keywords` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '产品过滤关键词，逗号分隔',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 74 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'API接口配置表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of config_api
-- ----------------------------
INSERT INTO `config_api` VALUES (72, 'jlcloud', '巨量互联', '', '', '', '', NULL, 1, 0, 5, '', NULL, 1770980367, 1771045138, 1, 0, 'light', 60, 0, NULL, NULL, 0, 10, 1000, 120, '2026-02-14 12:59:13', '订单同步完成，查询范围：最近120天，共处理 2 个订单，成功 2 个，失败 0 个', '');
INSERT INTO `config_api` VALUES (73, 'gth91', '91敢探号', '', '', '', 'https://notify.91haoka.cn', NULL, 1, 0, 0, '', '{\"login_name\":\"星火网络通讯\",\"login_password\":\"qwe123890@\",\"supplier_name\":\"号卡秒反\",\"supplier_shop_id\":\"610319\",\"commission_deduction\":20}', 1771346218, 1772253442, 0, 0, 'light', 60, 0, NULL, NULL, 0, 10, 1000, 120, NULL, NULL, '');

-- ----------------------------
-- Table structure for config_h5
-- ----------------------------
DROP TABLE IF EXISTS `config_h5`;
CREATE TABLE `config_h5`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '配置ID',
  `config_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '配置键名',
  `config_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '配置值',
  `config_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'text' COMMENT '配置类型：text文本/number数字/switch开关/images图片数组/textarea多行文本/json对象',
  `config_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '配置名称',
  `config_desc` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '配置描述',
  `config_group` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'basic' COMMENT '配置分组：basic基础/banner轮播/advanced高级',
  `sort` int(11) NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) NULL DEFAULT 1 COMMENT '状态：1启用 0禁用',
  `create_time` datetime NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `config_key`(`config_key`) USING BTREE,
  INDEX `idx_group`(`config_group`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 17 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'H5配置表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of config_h5
-- ----------------------------
INSERT INTO `config_h5` VALUES (1, 'banner_images', '[\"/uploads/h5/20250930120656_68db5760e2195.png\"]', 'images', '轮播图片', '首页顶部轮播图片，支持上传多张图片，拖拽调整顺序', 'banner', 10, 1, '2025-09-30 11:57:31', '2025-12-12 20:30:39');
INSERT INTO `config_h5` VALUES (2, 'banner_links', '[\"\"]', 'json', '图片链接', '与轮播图片一一对应的跳转链接，格式：[\"链接1\",\"链接2\"]，不需要跳转可留空\"\"', 'banner', 20, 1, '2025-09-30 11:57:31', '2025-12-12 20:30:39');
INSERT INTO `config_h5` VALUES (3, 'banner_interval', '3000', 'number', '轮播间隔', '轮播图切换间隔时间（毫秒），建议3000-5000', 'banner', 30, 1, '2025-09-30 11:57:31', '2025-12-12 20:30:39');
INSERT INTO `config_h5` VALUES (4, 'banner_autoplay', '1', 'switch', '自动轮播', '是否自动播放轮播图：1开启 0关闭', 'banner', 40, 1, '2025-09-30 11:57:31', '2025-12-12 20:30:39');
INSERT INTO `config_h5` VALUES (5, 'online_service_url', '', 'text', '在线客服链接', '移动端消息页面的在线客服跳转链接', 'basic', 10, 1, '2025-10-01 01:06:19', '2026-03-04 23:59:10');
INSERT INTO `config_h5` VALUES (10, 'product_template', 'product-v1', 'radio', '产品页模板', '选择产品页面使用的模板版本', 'template', 1, 1, '2025-12-25 11:52:45', '2026-02-09 00:00:13');
INSERT INTO `config_h5` VALUES (11, 'order_template', 'order-v2', 'radio', '订单页模板', '选择订单页面使用的模板版本', 'template', 1, 1, '2025-12-25 11:52:45', '2026-02-09 00:00:13');
INSERT INTO `config_h5` VALUES (12, 'wechat_login_enabled', '0', 'radio', '微信登录开关', '控制移动端微信登录功能的开启状态', 'template', 1, 1, '2025-12-25 11:52:49', '2025-12-25 11:52:49');
INSERT INTO `config_h5` VALUES (13, 'qq_login_enabled', '0', 'radio', 'QQ登录开关', '控制移动端QQ登录功能的开启状态', 'template', 1, 1, '2025-12-25 11:52:49', '2025-12-25 11:52:49');
INSERT INTO `config_h5` VALUES (14, 'ai_api_key', 'sk-', 'text', 'AI API Key', '大模型API密钥', 'ai', 1, 1, '2026-02-08 23:02:08', '2026-03-04 23:59:19');
INSERT INTO `config_h5` VALUES (15, 'ai_api_url', 'https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions', 'text', 'AI API地址', '大模型API请求地址', 'ai', 1, 1, '2026-02-08 23:02:08', '2026-02-08 23:03:59');
INSERT INTO `config_h5` VALUES (16, 'ai_model', 'deepseek-v3.2', 'text', 'AI模型名称', '使用的大模型名称', 'ai', 1, 1, '2026-02-08 23:02:08', '2026-02-08 23:03:59');

-- ----------------------------
-- Table structure for config_oss
-- ----------------------------
DROP TABLE IF EXISTS `config_oss`;
CREATE TABLE `config_oss`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '配置ID',
  `provider` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '存储提供商：local=本地存储，tencent=腾讯云COS，aliyun=阿里云OSS',
  `enabled` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否启用此存储提供商 0=禁用 1=启用',
  `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否为默认存储 0=否 1=是',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '配置名称/备注',
  `config` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '存储配置（JSON格式）',
  `upload_path` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'uploads' COMMENT '上传路径前缀',
  `max_file_size` int(11) NOT NULL DEFAULT 10 COMMENT '最大文件大小（MB）',
  `allowed_extensions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '允许的文件扩展名（JSON格式）',
  `create_time` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `provider`(`provider`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '云存储配置表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of config_oss
-- ----------------------------
INSERT INTO `config_oss` VALUES (1, 'local', 0, 0, '本地存储', '[]', 'uploads', 10, '[\"jpg\",\"jpeg\",\"png\",\"gif\",\"bmp\",\"webp\",\"pdf\",\"doc\",\"docx\",\"xls\",\"xlsx\",\"zip\",\"rar\"]', 1757389004, 1772640024);
INSERT INTO `config_oss` VALUES (2, 'tencent', 0, 0, '腾讯云COS', '{\"secret_id\":\"\",\"secret_key\":\"\",\"region\":\"ap-guangzhou\",\"bucket\":\"\",\"domain\":\"\"}', 'uploads', 5, '[\"jpg\",\"jpeg\",\"png\",\"gif\",\"bmp\",\"webp\",\"pdf\",\"doc\",\"docx\",\"xls\",\"xlsx\",\"zip\",\"rar\"]', 1757389004, 1772640024);
INSERT INTO `config_oss` VALUES (3, 'aliyun', 0, 0, '阿里云OSS', '{\"access_key_id\":\"\",\"access_key_secret\":\"\",\"endpoint\":\"oss-cn-beijing.aliyuncs.com\",\"bucket\":\"\",\"domain\":\"\"}', 'uploads', 5, '[\"jpg\",\"jpeg\",\"png\",\"gif\",\"bmp\",\"webp\",\"pdf\",\"doc\",\"docx\",\"xls\",\"xlsx\",\"zip\",\"rar\"]', 1757389004, 1772640024);

-- ----------------------------
-- Table structure for config_sms
-- ----------------------------
DROP TABLE IF EXISTS `config_sms`;
CREATE TABLE `config_sms`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '配置名称/备注',
  `provider` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'wangweiyun' COMMENT '短信服务商',
  `app_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'AppCode',
  `template_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '模板ID',
  `api_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'https://wwsms.market.alicloudapi.com/send_sms' COMMENT 'API地址',
  `sign_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '短信签名',
  `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否默认配置 0-否 1-是',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态 0-禁用 1-启用',
  `create_time` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_provider`(`provider`) USING BTREE,
  INDEX `idx_is_default`(`is_default`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '短信配置表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of config_sms
-- ----------------------------
INSERT INTO `config_sms` VALUES (1, '1', 'wangweiyun', '', '', 'https://wwsms.market.alicloudapi.com/send_sms', '', 1, 1, 1756968815, 1757931087);

-- ----------------------------
-- Table structure for content_categories
-- ----------------------------
DROP TABLE IF EXISTS `content_categories`;
CREATE TABLE `content_categories`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '分类ID',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '分类名称',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '分类描述',
  `type` enum('announcement','article') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'announcement' COMMENT '分类类型：announcement=公告分类，article=文章分类',
  `sort_order` int(11) NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) NULL DEFAULT 1 COMMENT '状态 1=启用 0=禁用',
  `create_time` int(11) NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(11) NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `type`(`type`) USING BTREE,
  INDEX `status`(`status`) USING BTREE,
  INDEX `sort_order`(`sort_order`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 7 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '内容分类表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of content_categories
-- ----------------------------

-- ----------------------------
-- Table structure for content_reads
-- ----------------------------
DROP TABLE IF EXISTS `content_reads`;
CREATE TABLE `content_reads`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `content_id` int(11) NOT NULL COMMENT '内容ID',
  `agent_id` int(11) NOT NULL COMMENT '代理ID',
  `read_time` int(11) NULL DEFAULT 0 COMMENT '阅读时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `content_agent`(`content_id`, `agent_id`) USING BTREE,
  INDEX `agent_id`(`agent_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 30 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '内容阅读记录表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of content_reads
-- ----------------------------

-- ----------------------------
-- Table structure for contents
-- ----------------------------
DROP TABLE IF EXISTS `contents`;
CREATE TABLE `contents`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '内容ID',
  `category_id` int(11) NULL DEFAULT 0 COMMENT '分类ID',
  `type` enum('announcement','article') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'announcement' COMMENT '内容类型：announcement=公告，article=文章',
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '标题',
  `summary` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '摘要（文章用）',
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '内容',
  `image` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '图片',
  `is_popup` tinyint(1) NULL DEFAULT 0 COMMENT '是否弹窗（仅公告） 1=是 0=否',
  `popup_width` int(11) NULL DEFAULT 600 COMMENT '弹窗宽度（仅公告）',
  `popup_height` int(11) NULL DEFAULT 400 COMMENT '弹窗高度（仅公告）',
  `status` tinyint(1) NULL DEFAULT 1 COMMENT '状态 1=发布 0=草稿',
  `view_count` int(11) NULL DEFAULT 0 COMMENT '阅读量',
  `sort_order` int(11) NULL DEFAULT 0 COMMENT '排序',
  `create_time` int(11) NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(11) NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `category_id`(`category_id`) USING BTREE,
  INDEX `type`(`type`) USING BTREE,
  INDEX `status`(`status`) USING BTREE,
  INDEX `is_popup`(`is_popup`) USING BTREE,
  INDEX `sort_order`(`sort_order`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 13 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '内容表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of contents
-- ----------------------------

-- ----------------------------
-- Table structure for employee_group_members
-- ----------------------------
DROP TABLE IF EXISTS `employee_group_members`;
CREATE TABLE `employee_group_members`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `group_id` int(11) UNSIGNED NOT NULL COMMENT '组ID',
  `agent_id` int(11) UNSIGNED NOT NULL COMMENT '代理ID',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '加入时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_group_agent`(`group_id`, `agent_id`) USING BTREE,
  INDEX `idx_group_id`(`group_id`) USING BTREE,
  INDEX `idx_agent_id`(`agent_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 16 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '员工组成员表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of employee_group_members
-- ----------------------------

-- ----------------------------
-- Table structure for employee_groups
-- ----------------------------
DROP TABLE IF EXISTS `employee_groups`;
CREATE TABLE `employee_groups`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '组名称',
  `description` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '组描述',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_name`(`name`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '员工组表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of employee_groups
-- ----------------------------

-- ----------------------------
-- Table structure for image_template
-- ----------------------------
DROP TABLE IF EXISTS `image_template`;
CREATE TABLE `image_template`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '模板名称',
  `yidong_image` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '移动底图',
  `liantong_image` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '联通底图',
  `dianxin_image` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '电信底图',
  `guangdian_image` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '广电底图',
  `yidong_config` json NULL COMMENT '移动文字配置',
  `liantong_config` json NULL COMMENT '联通文字配置',
  `dianxin_config` json NULL COMMENT '电信文字配置',
  `guangdian_config` json NULL COMMENT '广电文字配置',
  `is_system` tinyint(1) NULL DEFAULT 0 COMMENT '是否系统预设 1是 0否',
  `is_active` tinyint(1) NULL DEFAULT 0 COMMENT '是否当前使用 1是 0否',
  `api_auto_generate` tinyint(1) NULL DEFAULT 0 COMMENT 'API同步后自动转图 1开启 0关闭',
  `status` tinyint(1) NULL DEFAULT 1 COMMENT '状态 1启用 0禁用',
  `create_time` datetime NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_is_active`(`is_active`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '商品图片模板表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of image_template
-- ----------------------------
INSERT INTO `image_template` VALUES (1, '预设模板-1', '/uploads/product/2026/02/06/165811_6985ad23680b0.png', '/uploads/product/2026/02/06/165553_6985ac99b9103.png', '/uploads/product/2026/02/06/165557_6985ac9d1b255.png', '/uploads/product/2026/02/06/165600_6985aca039b56.png', '{\"flow\": {\"x\": 66, \"y\": 293, \"bold\": 1, \"color\": \"#2d2f2e\", \"fontSize\": 80}, \"yuezu\": {\"x\": 346, \"y\": 295, \"bold\": 1, \"color\": \"#2d2f2e\", \"fontSize\": 80}, \"customTexts\": [{\"x\": 443, \"y\": 328, \"id\": 1, \"bold\": 1, \"text\": \"元/月\", \"color\": \"#2d2f2e\", \"field\": \"\", \"fontSize\": 36}, {\"x\": 78, \"y\": 423, \"id\": 2, \"bold\": 1, \"text\": \"\", \"color\": \"#2d2f38\", \"field\": \"{tags}\", \"fontSize\": 25}, {\"x\": 76, \"y\": 466, \"id\": 3, \"bold\": 1, \"text\": \"官方发卡，极速发货，支持4G/5G\", \"color\": \"#2d2f2e\", \"field\": \"\", \"fontSize\": 25}, {\"x\": 157, \"y\": 753, \"id\": 4, \"bold\": 0, \"text\": \"*该流量卡由\", \"color\": \"#ffffff\", \"field\": \"\", \"fontSize\": 20}, {\"x\": 371, \"y\": 753, \"id\": 5, \"bold\": 0, \"text\": \"提供服务，套餐详情请见落地页\", \"color\": \"#ffffff\", \"field\": \"\", \"fontSize\": 20}, {\"x\": 272, \"y\": 753, \"id\": 6, \"bold\": 1, \"text\": \"\", \"color\": \"#ffffff\", \"field\": \"{yys}\", \"fontSize\": 22}, {\"x\": 216, \"y\": 293, \"id\": 7, \"bold\": 1, \"text\": \"G\", \"color\": \"#2d2f2e\", \"field\": \"\", \"fontSize\": 80}, {\"x\": 559, \"y\": 43, \"id\": 8, \"bold\": 1, \"text\": \"\", \"color\": \"#ffffff\", \"field\": \"{call}\", \"fontSize\": 36}]}', '{\"flow\": {\"x\": 66, \"y\": 293, \"bold\": 1, \"color\": \"#ff6600\", \"fontSize\": 80}, \"yuezu\": {\"x\": 346, \"y\": 295, \"bold\": 1, \"color\": \"#ff6600\", \"fontSize\": 80}, \"customTexts\": [{\"x\": 443, \"y\": 328, \"id\": 1, \"bold\": 1, \"text\": \"元/月\", \"color\": \"#2d2f2e\", \"field\": \"\", \"fontSize\": 36}, {\"x\": 78, \"y\": 423, \"id\": 2, \"bold\": 1, \"text\": \"\", \"color\": \"#2d2f38\", \"field\": \"{tags}\", \"fontSize\": 25}, {\"x\": 76, \"y\": 466, \"id\": 3, \"bold\": 1, \"text\": \"官方发卡，极速发货，支持4G/5G\", \"color\": \"#2d2f2e\", \"field\": \"\", \"fontSize\": 25}, {\"x\": 157, \"y\": 753, \"id\": 4, \"bold\": 0, \"text\": \"*该流量卡由\", \"color\": \"#ffffff\", \"field\": \"\", \"fontSize\": 20}, {\"x\": 371, \"y\": 753, \"id\": 5, \"bold\": 0, \"text\": \"提供服务，套餐详情请见落地页\", \"color\": \"#ffffff\", \"field\": \"\", \"fontSize\": 20}, {\"x\": 272, \"y\": 753, \"id\": 6, \"bold\": 1, \"text\": \"\", \"color\": \"#ffffff\", \"field\": \"{yys}\", \"fontSize\": 22}, {\"x\": 216, \"y\": 293, \"id\": 7, \"bold\": 1, \"text\": \"G\", \"color\": \"#2d2f2e\", \"field\": \"\", \"fontSize\": 80}]}', '{\"flow\": {\"x\": 66, \"y\": 293, \"bold\": 1, \"color\": \"#ffffff\", \"fontSize\": 80}, \"yuezu\": {\"x\": 346, \"y\": 295, \"bold\": 1, \"color\": \"#ffffff\", \"fontSize\": 80}, \"customTexts\": [{\"x\": 443, \"y\": 328, \"id\": 1, \"bold\": 1, \"text\": \"元/月\", \"color\": \"#ffffff\", \"field\": \"\", \"fontSize\": 36}, {\"x\": 78, \"y\": 423, \"id\": 2, \"bold\": 1, \"text\": \"\", \"color\": \"#ffffff\", \"field\": \"{tags}\", \"fontSize\": 25}, {\"x\": 76, \"y\": 466, \"id\": 3, \"bold\": 1, \"text\": \"官方发卡，极速发货，支持4G/5G\", \"color\": \"#ffffff\", \"field\": \"\", \"fontSize\": 25}, {\"x\": 157, \"y\": 753, \"id\": 4, \"bold\": 0, \"text\": \"*该流量卡由\", \"color\": \"#ffffff\", \"field\": \"\", \"fontSize\": 20}, {\"x\": 371, \"y\": 753, \"id\": 5, \"bold\": 0, \"text\": \"提供服务，套餐详情请见落地页\", \"color\": \"#ffffff\", \"field\": \"\", \"fontSize\": 20}, {\"x\": 272, \"y\": 753, \"id\": 6, \"bold\": 1, \"text\": \"\", \"color\": \"#ffffff\", \"field\": \"{yys}\", \"fontSize\": 22}, {\"x\": 216, \"y\": 293, \"id\": 7, \"bold\": 1, \"text\": \"G\", \"color\": \"#2d2f2e\", \"field\": \"\", \"fontSize\": 80}]}', '{\"flow\": {\"x\": 66, \"y\": 293, \"bold\": 1, \"color\": \"#2d2f2e\", \"fontSize\": 80}, \"yuezu\": {\"x\": 346, \"y\": 295, \"bold\": 1, \"color\": \"#2d2f2e\", \"fontSize\": 80}, \"customTexts\": [{\"x\": 443, \"y\": 328, \"id\": 1, \"bold\": 1, \"text\": \"元/月\", \"color\": \"#2d2f2e\", \"field\": \"\", \"fontSize\": 36}, {\"x\": 78, \"y\": 423, \"id\": 2, \"bold\": 1, \"text\": \"\", \"color\": \"#2d2f38\", \"field\": \"{tags}\", \"fontSize\": 25}, {\"x\": 76, \"y\": 466, \"id\": 3, \"bold\": 1, \"text\": \"官方发卡，极速发货，支持4G/5G\", \"color\": \"#2d2f2e\", \"field\": \"\", \"fontSize\": 25}, {\"x\": 157, \"y\": 753, \"id\": 4, \"bold\": 0, \"text\": \"*该流量卡由\", \"color\": \"#ffffff\", \"field\": \"\", \"fontSize\": 20}, {\"x\": 371, \"y\": 753, \"id\": 5, \"bold\": 0, \"text\": \"提供服务，套餐详情请见落地页\", \"color\": \"#ffffff\", \"field\": \"\", \"fontSize\": 20}, {\"x\": 272, \"y\": 753, \"id\": 6, \"bold\": 1, \"text\": \"\", \"color\": \"#ffffff\", \"field\": \"{yys}\", \"fontSize\": 22}, {\"x\": 216, \"y\": 293, \"id\": 7, \"bold\": 1, \"text\": \"G\", \"color\": \"#2d2f2e\", \"field\": \"\", \"fontSize\": 80}]}', 1, 1, 0, 1, '2026-02-05 22:24:38', '2026-02-09 15:50:49');
INSERT INTO `image_template` VALUES (2, '预设模板-2', '/uploads/product/2026/02/06/165609_6985aca9c0542.png', '/uploads/product/2026/02/06/165613_6985acad58990.png', '/uploads/product/2026/02/06/165616_6985acb0e0831.png', '/uploads/product/2026/02/06/165621_6985acb539046.png', '{\"flow\": {\"x\": 52, \"y\": 247, \"bold\": 1, \"color\": \"#2d2f2e\", \"fontSize\": 80}, \"yuezu\": {\"x\": 285, \"y\": 254, \"bold\": 1, \"color\": \"#2d2f2e\", \"fontSize\": 80}, \"customTexts\": [{\"x\": 91, \"y\": 402, \"id\": 2, \"bold\": 1, \"text\": \"\", \"color\": \"#2d2f38\", \"field\": \"{tags}\", \"fontSize\": 25}, {\"x\": 91, \"y\": 441, \"id\": 3, \"bold\": 1, \"text\": \"官方发卡，极速发货，支持4G/5G\", \"color\": \"#2d2f2e\", \"field\": \"\", \"fontSize\": 25}]}', '{\"flow\": {\"x\": 52, \"y\": 247, \"bold\": 1, \"color\": \"#2d2f2e\", \"fontSize\": 80}, \"yuezu\": {\"x\": 285, \"y\": 254, \"bold\": 1, \"color\": \"#2d2f2e\", \"fontSize\": 80}, \"customTexts\": [{\"x\": 91, \"y\": 402, \"id\": 2, \"bold\": 1, \"text\": \"\", \"color\": \"#2d2f2e\", \"field\": \"{tags}\", \"fontSize\": 25}, {\"x\": 91, \"y\": 441, \"id\": 3, \"bold\": 1, \"text\": \"官方发卡，极速发货，支持4G/5G\", \"color\": \"#2d2f38\", \"field\": \"\", \"fontSize\": 25}]}', '{\"flow\": {\"x\": 52, \"y\": 247, \"bold\": 1, \"color\": \"#000000\", \"fontSize\": 80}, \"yuezu\": {\"x\": 285, \"y\": 254, \"bold\": 1, \"color\": \"#000000\", \"fontSize\": 80}, \"customTexts\": [{\"x\": 91, \"y\": 402, \"id\": 2, \"bold\": 1, \"text\": \"\", \"color\": \"#000000\", \"field\": \"{tags}\", \"fontSize\": 25}, {\"x\": 91, \"y\": 441, \"id\": 3, \"bold\": 1, \"text\": \"官方发卡，极速发货，支持4G/5G\", \"color\": \"#000000\", \"field\": \"\", \"fontSize\": 25}]}', '{\"flow\": {\"x\": 52, \"y\": 247, \"bold\": 1, \"color\": \"#2d2f2e\", \"fontSize\": 80}, \"yuezu\": {\"x\": 285, \"y\": 254, \"bold\": 1, \"color\": \"#2d2f2e\", \"fontSize\": 80}, \"customTexts\": [{\"x\": 91, \"y\": 402, \"id\": 2, \"bold\": 1, \"text\": \"\", \"color\": \"#2d2f2e\", \"field\": \"{tags}\", \"fontSize\": 25}, {\"x\": 91, \"y\": 441, \"id\": 3, \"bold\": 1, \"text\": \"官方发卡，极速发货，支持4G/5G\", \"color\": \"#2d2f38\", \"field\": \"\", \"fontSize\": 25}]}', 1, 0, 0, 1, '2026-02-06 16:56:32', '2026-02-07 23:31:04');

-- ----------------------------
-- Table structure for invite_code
-- ----------------------------
DROP TABLE IF EXISTS `invite_code`;
CREATE TABLE `invite_code`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `agent_id` int(11) UNSIGNED NOT NULL COMMENT '创建者代理商ID',
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '邀请码',
  `level_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '等级名称',
  `commission` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '抽佣金额',
  `commission_type` tinyint(4) NOT NULL DEFAULT 0 COMMENT '佣金类型：0=固定金额，1=百分比',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态 0=禁用 1=启用',
  `used_count` int(11) NOT NULL DEFAULT 0 COMMENT '使用次数',
  `max_uses` int(11) NULL DEFAULT NULL COMMENT '最大使用次数，NULL表示无限制',
  `upgrade_amount` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '升级抽佣金额',
  `upgrade_count` int(11) NULL DEFAULT 0 COMMENT '升级单量要求',
  `upgrade_requirement` int(11) NULL DEFAULT 0 COMMENT '升级单量要求',
  `create_time` int(11) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) NULL DEFAULT NULL COMMENT '更新时间',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '备注信息',
  `auto_upgrade` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否允许自动升级：0=否，1=是',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `code`(`code`) USING BTREE,
  INDEX `agent_id`(`agent_id`) USING BTREE,
  INDEX `status`(`status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 12 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '邀请码等级表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of invite_code
-- ----------------------------

-- ----------------------------
-- Table structure for messages
-- ----------------------------
DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '消息ID',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '消息标题',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '消息内容',
  `sender_id` int(11) NOT NULL DEFAULT 0 COMMENT '发送者ID，0表示系统/总后台',
  `sender_type` enum('admin','agent') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'admin' COMMENT '发送者类型',
  `receiver_id` int(11) NOT NULL COMMENT '接收者ID（代理ID）',
  `receiver_type` enum('agent') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'agent' COMMENT '接收者类型',
  `is_read` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否已读：0未读，1已读',
  `read_time` datetime NULL DEFAULT NULL COMMENT '阅读时间',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_sender`(`sender_id`, `sender_type`) USING BTREE,
  INDEX `idx_receiver`(`receiver_id`, `receiver_type`) USING BTREE,
  INDEX `idx_create_time`(`create_time`) USING BTREE,
  INDEX `idx_is_read`(`is_read`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 14 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '站内信表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of messages
-- ----------------------------

-- ----------------------------
-- Table structure for order
-- ----------------------------
DROP TABLE IF EXISTS `order`;
CREATE TABLE `order`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `order_no` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '本地订单号',
  `partner_order_no` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '合作伙伴订单号',
  `up_order_no` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '上游渠道订单号',
  `api_name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '渠道名称',
  `api_config_id` int(11) NULL DEFAULT 0 COMMENT 'API配置ID（用于多配置API）',
  `shop_code` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '店铺代码',
  `product_id` int(11) NULL DEFAULT NULL COMMENT '对接产品ID',
  `product_name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '商品名称',
  `product_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '产品图片路径',
  `order_status` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '0' COMMENT '订单状态(0-已提交,1-待发货,2-已发货,3-待传照片,4-已激活,5-已结算,6-结算失败,7-审核失败)',
  `express_company` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '物流公司',
  `tracking_number` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '物流单号',
  `customer_name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '客户姓名',
  `phone` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '电话号码',
  `idcard` varchar(18) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '身份证号码',
  `province` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '省份',
  `city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '城市',
  `district` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '区县',
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '详细地址',
  `remark` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '备注',
  `api_source` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'internal' COMMENT 'API来源(internal/partner)',
  `internal_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '内部备注（仅管理后台可见）',
  `agent_id` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '代理ID',
  `agent_change` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '代理层级快照JSON（订单创建时的层级关系）',
  `agent_change_time` datetime NULL DEFAULT NULL COMMENT '快照生成时间',
  `salary_paid` tinyint(1) NOT NULL DEFAULT 0 COMMENT '订单拥有者是否已发放工资',
  `photo_status` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '0' COMMENT '是否已上传照片(0-无需上传,1-未上传,2-已上传,3-待上传照片,4-已重新上传)',
  `name_count` int(11) NULL DEFAULT 0 COMMENT '姓名订单数量',
  `id_card_count` int(11) NULL DEFAULT 0 COMMENT '身份证订单数量',
  `phone_count` int(11) NULL DEFAULT 0 COMMENT '手机号订单数量',
  `commission` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '佣金',
  `js_type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '1' COMMENT '结算模式(1-次月返,2次月返)',
  `recharge_status` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '0' COMMENT '充值状态(1-已充值,0-待更新)',
  `recharge_amount` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '充值金额',
  `production_number` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '生产号码',
  `create_time` datetime NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `jh_time` datetime NULL DEFAULT NULL COMMENT '激活时间',
  `js_time` datetime NULL DEFAULT NULL COMMENT '结算时间',
  `flag_color` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '标旗颜色(blue/gray/red/yellow/green/cyan/purple)',
  `card_type` tinyint(1) NULL DEFAULT 0 COMMENT '卡类型：0免费卡 1付费卡',
  `card_price` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '卡费金额',
  `markup_price` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '订单拥有者加价金额',
  `total_price` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '订单总价（卡费+累计加价）',
  `pay_status` tinyint(1) NULL DEFAULT 0 COMMENT '支付状态：0未支付/免费 1已支付 2已退款',
  `pay_time` int(11) NULL DEFAULT NULL COMMENT '支付时间戳',
  `transaction_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '微信支付交易号',
  `refund_time` datetime NULL DEFAULT NULL COMMENT '退款时间',
  `refund_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '退款原因',
  `refund_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '退款交易号',
  `is_resubmit` tinyint(1) NULL DEFAULT 0 COMMENT '是否重提单(0-否,1-是)',
  `id_card_front` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '身份证正面',
  `id_card_back` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '身份证背面',
  `id_card_face` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '身份证人脸照',
  `id_card_four` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '第四证照片',
  `callback_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '订单回调地址',
  `callback_status` enum('none','pending','success','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'none' COMMENT '回调状态',
  `callback_retry_count` int(11) NULL DEFAULT 0 COMMENT '回调重试次数',
  `next_callback_time` int(11) NULL DEFAULT NULL COMMENT '下次回调时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `order_no`(`order_no`) USING BTREE,
  INDEX `shop_code`(`shop_code`) USING BTREE,
  INDEX `agent_id`(`agent_id`) USING BTREE,
  INDEX `product_id`(`product_id`) USING BTREE,
  INDEX `order_status`(`order_status`) USING BTREE,
  INDEX `idx_create_time_status`(`create_time`, `order_status`) USING BTREE,
  INDEX `idx_status_create_time`(`order_status`, `create_time`) USING BTREE,
  INDEX `idx_customer_name`(`customer_name`) USING BTREE,
  INDEX `idx_phone`(`phone`) USING BTREE,
  INDEX `idx_shop_code_create_time`(`shop_code`, `create_time`) USING BTREE,
  INDEX `idx_agent_create_time`(`agent_id`, `create_time`) USING BTREE,
  INDEX `idx_order_no_prefix`(`order_no`(20)) USING BTREE,
  INDEX `idx_multi_filter`(`order_status`, `shop_code`, `create_time`) USING BTREE,
  INDEX `idx_agent_change_time`(`agent_change_time`) USING BTREE,
  INDEX `idx_api_config_id`(`api_config_id`) USING BTREE,
  INDEX `idx_flag_color`(`flag_color`) USING BTREE,
  INDEX `idx_jh_time`(`jh_time`) USING BTREE,
  INDEX `idx_js_time`(`js_time`) USING BTREE,
  INDEX `idx_partner_order_no`(`partner_order_no`) USING BTREE,
  INDEX `idx_api_source`(`api_source`) USING BTREE,
  INDEX `idx_callback_status`(`callback_status`) USING BTREE,
  INDEX `idx_next_callback_time`(`next_callback_time`) USING BTREE,
  INDEX `idx_pay_status`(`pay_status`) USING BTREE,
  INDEX `idx_card_type`(`card_type`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 475 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '订单表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of order
-- ----------------------------

-- ----------------------------
-- Table structure for order_batch
-- ----------------------------
DROP TABLE IF EXISTS `order_batch`;
CREATE TABLE `order_batch`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '批次ID',
  `batch_no` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '批次号',
  `admin_id` int(11) NOT NULL COMMENT '操作管理员ID',
  `admin_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '操作管理员名称',
  `operation_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '操作类型(status-状态,remark-备注,production_number-生产号码,logistics-物流信息)',
  `target_status` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '目标状态(仅状态操作时有值)',
  `total_count` int(11) NULL DEFAULT 0 COMMENT '总订单数',
  `success_count` int(11) NULL DEFAULT 0 COMMENT '成功数量',
  `fail_count` int(11) NULL DEFAULT 0 COMMENT '失败数量',
  `status` tinyint(1) NULL DEFAULT 0 COMMENT '批次状态(0-待处理,1-处理中,2-已完成,3-已撤回)',
  `create_time` datetime NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `execute_time` datetime NULL DEFAULT NULL COMMENT '执行时间',
  `finish_time` datetime NULL DEFAULT NULL COMMENT '完成时间',
  `rollback_time` datetime NULL DEFAULT NULL COMMENT '撤回时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `batch_no`(`batch_no`) USING BTREE,
  INDEX `idx_admin_id`(`admin_id`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE,
  INDEX `idx_create_time`(`create_time`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 9 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '订单批量操作批次表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of order_batch
-- ----------------------------

-- ----------------------------
-- Table structure for order_batch_item
-- ----------------------------
DROP TABLE IF EXISTS `order_batch_item`;
CREATE TABLE `order_batch_item`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `batch_id` int(11) NOT NULL COMMENT '批次ID',
  `batch_no` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '批次号',
  `order_id` int(11) NULL DEFAULT NULL COMMENT '订单ID',
  `order_no` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '订单号',
  `old_status` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '原状态',
  `new_status` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '新状态',
  `old_remark` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '原备注',
  `new_remark` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '新备注',
  `old_production_number` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '原生产号码',
  `new_production_number` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '新生产号码',
  `old_express_company` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '原物流公司',
  `new_express_company` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '新物流公司',
  `old_tracking_number` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '原物流单号',
  `new_tracking_number` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '新物流单号',
  `execute_status` tinyint(1) NULL DEFAULT 0 COMMENT '执行状态(0-待处理,1-成功,2-失败,3-已撤回)',
  `fail_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '失败原因',
  `execute_time` datetime NULL DEFAULT NULL COMMENT '执行时间',
  `rollback_time` datetime NULL DEFAULT NULL COMMENT '撤回时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_batch_id`(`batch_id`) USING BTREE,
  INDEX `idx_batch_no`(`batch_no`) USING BTREE,
  INDEX `idx_order_no`(`order_no`) USING BTREE,
  INDEX `idx_execute_status`(`execute_status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 13 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '订单批量操作明细表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of order_batch_item
-- ----------------------------

-- ----------------------------
-- Table structure for order_photo_history
-- ----------------------------
DROP TABLE IF EXISTS `order_photo_history`;
CREATE TABLE `order_photo_history`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL COMMENT '订单ID',
  `photo_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '照片类型(单张:id_card_front等, 批量:batch)',
  `photo_paths` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '批量照片路径JSON数组',
  `file_size` int(11) NULL DEFAULT NULL COMMENT '文件大小',
  `upload_type` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'replaced' COMMENT '上传类型',
  `batch_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '批次ID',
  `agent_id` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '代理ID',
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'IP地址',
  `created_time` datetime NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `status` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'replaced' COMMENT '状态',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_order_id`(`order_id`) USING BTREE,
  INDEX `idx_batch_id`(`batch_id`) USING BTREE,
  INDEX `idx_created_time`(`created_time`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 40 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '订单照片历史记录表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of order_photo_history
-- ----------------------------

-- ----------------------------
-- Table structure for partner_api_logs
-- ----------------------------
DROP TABLE IF EXISTS `partner_api_logs`;
CREATE TABLE `partner_api_logs`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '请求ID',
  `agent_id` int(11) NOT NULL COMMENT '代理商ID',
  `action` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '接口动作',
  `method` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '请求方法',
  `url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '请求URL',
  `params` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '请求参数JSON',
  `result` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '返回结果JSON',
  `response_time` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '响应时间(带单位，如：340ms)',
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'IP地址',
  `user_agent` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'User Agent',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_request_id`(`request_id`) USING BTREE,
  INDEX `idx_agent_id`(`agent_id`) USING BTREE,
  INDEX `idx_action`(`action`) USING BTREE,
  INDEX `idx_create_time`(`create_time`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 17 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'API调用日志表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of partner_api_logs
-- ----------------------------

-- ----------------------------
-- Table structure for partner_callbacks
-- ----------------------------
DROP TABLE IF EXISTS `partner_callbacks`;
CREATE TABLE `partner_callbacks`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL COMMENT '订单ID',
  `order_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '订单号',
  `agent_id` int(11) NOT NULL COMMENT '代理商ID',
  `callback_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '回调URL',
  `callback_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '回调数据JSON',
  `status` enum('pending','success','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'pending' COMMENT '回调状态',
  `retry_count` int(11) NULL DEFAULT 0 COMMENT '重试次数',
  `max_retry` int(11) NULL DEFAULT 5 COMMENT '最大重试次数',
  `next_retry_time` int(11) NULL DEFAULT NULL COMMENT '下次重试时间',
  `response_code` int(11) NULL DEFAULT NULL COMMENT 'HTTP响应码',
  `response_time` int(11) NULL DEFAULT NULL COMMENT '响应时间(毫秒)',
  `response_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '响应数据',
  `error_msg` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '错误信息',
  `created_time` int(11) NOT NULL COMMENT '创建时间',
  `updated_time` int(11) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_order_id`(`order_id`) USING BTREE,
  INDEX `idx_agent_id`(`agent_id`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE,
  INDEX `idx_next_retry_time`(`next_retry_time`) USING BTREE,
  INDEX `idx_response_time`(`response_time`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 18 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '回调队列表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of partner_callbacks
-- ----------------------------

-- ----------------------------
-- Table structure for payment_configs
-- ----------------------------
DROP TABLE IF EXISTS `payment_configs`;
CREATE TABLE `payment_configs`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '支付类型',
  `config_key` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '配置键名',
  `config_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '配置值',
  `config_type` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'string' COMMENT '配置类型：string/int/bool/json',
  `is_required` tinyint(1) NULL DEFAULT 0 COMMENT '是否必填：0否 1是',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '配置说明',
  `create_time` int(11) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `payment_config`(`payment_type`, `config_key`) USING BTREE,
  INDEX `payment_type`(`payment_type`) USING BTREE,
  INDEX `idx_config_lookup`(`payment_type`, `config_key`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 34 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '支付配置详情表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of payment_configs
-- ----------------------------
INSERT INTO `payment_configs` VALUES (1, 'wechat', 'pay_mode', 'jsapi', 'string', 1, '支付模式：jsapi/h5/native', 1766405472, 1772640218);
INSERT INTO `payment_configs` VALUES (2, 'wechat', 'appid', '', 'string', 1, '公众号AppID（JSAPI模式需要）', 1766405472, 1772640218);
INSERT INTO `payment_configs` VALUES (3, 'wechat', 'app_secret', '', 'string', 0, '公众号AppSecret', 1766405472, 1772640218);
INSERT INTO `payment_configs` VALUES (4, 'wechat', 'mchid', '', 'string', 1, '商户号', 1766405472, 1772640218);
INSERT INTO `payment_configs` VALUES (5, 'wechat', 'api_key', '', 'string', 1, 'APIv2密钥（32位）', 1766405472, 1772640218);
INSERT INTO `payment_configs` VALUES (6, 'wechat', 'notify_url', 'https://你的域名/index/pay/notify/wechat', 'string', 1, '支付回调地址', 1766405472, 1772640218);
INSERT INTO `payment_configs` VALUES (7, 'wechat', 'auth_domain', 'https://你的域名', 'string', 0, '微信授权域名（用于JSAPI授权回调）', 1766405472, 1772640218);
INSERT INTO `payment_configs` VALUES (8, 'epay', 'merchant_id', '', 'string', 1, '商户ID', 1766405472, 1772640250);
INSERT INTO `payment_configs` VALUES (9, 'epay', 'merchant_key', '', 'string', 1, '商户密钥', 1766405472, 1772640250);
INSERT INTO `payment_configs` VALUES (10, 'epay', 'api_url', '', 'string', 1, 'API接口地址', 1766405472, 1772640250);
INSERT INTO `payment_configs` VALUES (11, 'epay', 'notify_url', 'https://你的域名/index/pay/notify/epay', 'string', 1, '异步回调地址', 1766405472, 1772640250);
INSERT INTO `payment_configs` VALUES (12, 'epay', 'return_url', 'https://你的域名/index/pay/success/epay', 'string', 0, '同步返回地址', 1766405472, 1772640250);
INSERT INTO `payment_configs` VALUES (13, 'alipay', 'app_id', '', 'string', 1, '支付宝应用ID', 1766405472, 1772640266);
INSERT INTO `payment_configs` VALUES (14, 'alipay', 'private_key', '', 'string', 1, '应用私钥', 1766405472, 1772640266);
INSERT INTO `payment_configs` VALUES (16, 'alipay', 'notify_url', 'https://你的域名/index/pay/alipayNotify', 'string', 1, '异步回调地址', 1766405472, 1772640266);
INSERT INTO `payment_configs` VALUES (17, 'alipay', 'return_url', 'https://你的域名/index/pay/success/alipay', 'string', 0, '同步返回地址', 1766405472, 1772640266);
INSERT INTO `payment_configs` VALUES (25, 'epay', 'platform_public_key', '', 'textarea', 1, '平台公钥（RSA签名方式）', 1766409022, 1772640250);
INSERT INTO `payment_configs` VALUES (26, 'epay', 'merchant_private_key', '', 'textarea', 1, '商户私钥（RSA签名方式）', 1766409022, 1772640250);
INSERT INTO `payment_configs` VALUES (27, 'alipay', 'alipay_public_key', '', 'string', 1, '支付宝公钥', 1767494605, 1772640266);
INSERT INTO `payment_configs` VALUES (28, 'alipay', 'pay_mode', 'PAGE', 'string', 1, '支付模式: FACE/WAP/PAGE', 1767494605, 1772640266);
INSERT INTO `payment_configs` VALUES (29, 'wechat', 'openid_mode', 'wechat', 'string', 0, 'OpenID获取方式：wechat=公众号，wework=企业微信', 1767586514, 1772640218);
INSERT INTO `payment_configs` VALUES (30, 'wechat', 'wework_corp_id', '', 'string', 1, '企业微信CorpID', 1767586514, 1772640218);
INSERT INTO `payment_configs` VALUES (31, 'wechat', 'wework_corp_secret', '', 'string', 1, '企业微信应用Secret', 1767586514, 1772640218);
INSERT INTO `payment_configs` VALUES (32, 'wechat', 'wework_agent_id', '1000002', 'string', 1, '企业微信应用AgentID', 1767586514, 1772640218);
INSERT INTO `payment_configs` VALUES (33, 'wechat', 'wework_redirect_uri', 'https://你的域名/index/pay/wework_callback', 'string', 0, '企业微信OAuth重定向URI（可选）', 1767586514, 1772640218);

-- ----------------------------
-- Table structure for payment_methods
-- ----------------------------
DROP TABLE IF EXISTS `payment_methods`;
CREATE TABLE `payment_methods`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '支付类型：wechat/epay/alipay/unionpay等',
  `payment_name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '支付方式显示名称',
  `is_enabled` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否启用：0关闭 1启用',
  `sort_order` int(11) NULL DEFAULT 0 COMMENT '排序权重，数字越小越靠前',
  `icon_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '支付图标URL',
  `description` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '支付方式描述',
  `create_time` int(11) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `payment_type`(`payment_type`) USING BTREE,
  INDEX `is_enabled`(`is_enabled`) USING BTREE,
  INDEX `sort_order`(`sort_order`) USING BTREE,
  INDEX `idx_payment_enabled`(`payment_type`, `is_enabled`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '支付方式主表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of payment_methods
-- ----------------------------
INSERT INTO `payment_methods` VALUES (1, 'wechat', '微信支付', 1, 1, NULL, '支持JSAPI/H5/Native三种支付模式', 1766405472, 1767586432);
INSERT INTO `payment_methods` VALUES (2, 'epay', '易支付', 0, 2, NULL, '第三方聚合支付，支持支付宝/微信/QQ钱包', 1766405472, 1766540338);
INSERT INTO `payment_methods` VALUES (3, 'alipay', '支付宝', 0, 3, '/static/images/pay/alipay.png', '支付宝官方支付，支持当面付、手机网站支付、电脑网站支付', 1767494605, 1767586432);

-- ----------------------------
-- Table structure for payment_records
-- ----------------------------
DROP TABLE IF EXISTS `payment_records`;
CREATE TABLE `payment_records`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL COMMENT '订单ID',
  `order_no` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '订单号',
  `agent_id` int(11) NOT NULL COMMENT '代理商ID',
  `customer_name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '客户姓名',
  `customer_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '客户手机号',
  `shop_code` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '店铺代码',
  `pay_channel` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'wechat' COMMENT '支付渠道：wechat/alipay',
  `pay_mode` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'NATIVE' COMMENT '支付方式：微信(JSAPI/H5/NATIVE) 支付宝(SCAN/WAP/APP)',
  `amount` decimal(10, 2) NOT NULL COMMENT '支付金额',
  `buyer_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '用户ID（微信openid/支付宝buyer_id）',
  `transaction_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '平台交易号（微信transaction_id/支付宝trade_no）',
  `prepay_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '预支付ID',
  `code_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '二维码链接（扫码支付）',
  `pay_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0待支付 1已支付 2已退款 3退款中',
  `pay_time` int(11) NULL DEFAULT NULL COMMENT '支付时间',
  `refund_no` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '退款单号',
  `refund_amount` decimal(10, 2) NULL DEFAULT NULL COMMENT '退款金额',
  `refund_time` int(11) NULL DEFAULT NULL COMMENT '退款时间',
  `refund_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '退款原因',
  `notify_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '支付回调原始数据',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_order_no`(`order_no`) USING BTREE,
  INDEX `idx_order_id`(`order_id`) USING BTREE,
  INDEX `idx_agent_id`(`agent_id`) USING BTREE,
  INDEX `idx_shop_code`(`shop_code`) USING BTREE,
  INDEX `idx_pay_channel`(`pay_channel`) USING BTREE,
  INDEX `idx_pay_mode`(`pay_mode`) USING BTREE,
  INDEX `idx_pay_status`(`pay_status`) USING BTREE,
  INDEX `idx_transaction_id`(`transaction_id`) USING BTREE,
  INDEX `idx_create_time`(`create_time`) USING BTREE,
  INDEX `idx_customer_name`(`customer_name`) USING BTREE,
  INDEX `idx_customer_phone`(`customer_phone`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 310 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '支付记录表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of payment_records
-- ----------------------------

-- ----------------------------
-- Table structure for plugin_license
-- ----------------------------
DROP TABLE IF EXISTS `plugin_license`;
CREATE TABLE `plugin_license`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `plugin_key` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '插件标识(workorder等)',
  `plugin_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '插件名称',
  `authcode` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '授权码(免费插件可为空)',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态:0=禁用,1=启用,2=即将上线',
  `create_time` bigint(16) UNSIGNED NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` bigint(16) UNSIGNED NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `authcode`(`authcode`) USING BTREE,
  INDEX `plugin_key`(`plugin_key`) USING BTREE,
  INDEX `status`(`status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 25 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '插件授权表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of plugin_license
-- ----------------------------
INSERT INTO `plugin_license` (`id`, `plugin_key`, `plugin_name`, `authcode`, `status`, `create_time`, `update_time`) VALUES
(1, 'workorder', '工单系统', NULL, 0, 1753721925, 1772639899),
(2, 'marketing', '营销活动', NULL, 0, 1753721925, 1772639902),
(3, 'message', '站内信', NULL, 0, 1753721925, 1772639897),
(4, 'saas', '代理贴牌', NULL, 2, 1753721925, 1753721925),
(5, 'ai_chat', '智能AI客服', NULL, 2, 1753721925, 1753768297),
(6, 'transfer', '一键转单', NULL, 2, 1753721925, 1753721925),
(7, 'app', '小程序+APP', NULL, 2, 1753721925, 1753721925),
(8, 'mf58', '58秒返', NULL, 0, 1753721925, 1772639871),
(9, 'haoky', '卡业联盟', NULL, 0, 1753721925, 1772639861),
(10, 'haoy', '号易', NULL, 0, 1753721925, 1772639859),
(11, 'hao172', '172号卡', NULL, 0, 1753721925, 1772639865),
(12, 'lanchang', '蓝畅速享', NULL, 0, 1753721925, 1772639863),
(13, 'tiancheng', '天城智控', NULL, 0, 1753721925, 1771749270),
(14, 'haoteam', '号卡极团', NULL, 0, 1753721925, 1772639862),
(16, 'jlcloud', '巨量互联', NULL, 1, 1753721925, 1772639874),
(17, 'longbao', '龙宝API', NULL, 0, 1759839622, 1772639868),
(18, 'jikeyun', '极客云API', NULL, 0, 1759989631, 1772639867),
(19, 'agent_migrate', '代理迁移', NULL, 0, 1760676733, 1772639896),
(20, 'guangmengyun', '广梦云', NULL, 0, 1769313834, 1772639858),
(22, 'gchk', '共创号卡', NULL, 0, 1770012332, 1772639869),
(23, 'imagetemplate', '一键转图', NULL, 0, 1770388019, 1771946254),
(24, 'gth91', '91敢探号', NULL, 1, 1771345767, 1771345767);
-- ----------------------------
-- Table structure for product
-- ----------------------------
DROP TABLE IF EXISTS `product`;
CREATE TABLE `product`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '产品名称',
  `number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '对接编号',
  `api_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '对接上游名称',
  `api_config_id` int(11) NULL DEFAULT 0 COMMENT 'API配置ID（用于多配置API）',
  `yys` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '运营商(移动/联通/电信/广电)',
  `product_image` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '产品首图',
  `detail_images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '详情图',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态(0-下架 1-上架 2-待上架)',
  `is_open` tinyint(1) NULL DEFAULT 1 COMMENT '是否对下游开放',
  `is_recommend` tinyint(1) NOT NULL DEFAULT 0 COMMENT '平台推荐：0=否，1=是',
  `admin_sort_order` int(11) NOT NULL DEFAULT 0 COMMENT '总后台排序值：0=未排序，数值越小越靠前',
  `commission` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '佣金',
  `js_type` tinyint(1) NULL DEFAULT NULL COMMENT '结算模式(1-秒返 2-次月返)',
  `js_require` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '结算要求',
  `create_time` datetime NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NULL DEFAULT CURRENT_TIMESTAMP COMMENT '更新时间',
  `yuezu` decimal(10, 2) NULL DEFAULT NULL COMMENT '月租',
  `selectNumber` tinyint(1) NULL DEFAULT 0 COMMENT '是否选号(0-否 1-是)',
  `isHot` tinyint(1) NULL DEFAULT 0 COMMENT '是否热门(0-否 1-是)',
  `hot_sort` int(11) NULL DEFAULT 0 COMMENT '热门排序权重，数字越大越靠前',
  `tags` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '产品标签',
  `flow` int(11) NULL DEFAULT 0 COMMENT '流量(GB)',
  `call` decimal(10, 2) NULL DEFAULT 0.00 COMMENT 'call minutes or price per minute',
  `sms` int(11) NULL DEFAULT 0 COMMENT '短信(条)',
  `first_chongzhi` int(11) NULL DEFAULT NULL COMMENT '首充金额(50或100)',
  `rule` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '首充规则',
  `peisong` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '配送方式',
  `kaika` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '开卡方式',
  `age` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '年龄',
  `heyue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '合约期',
  `jinfa` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '禁发区',
  `kefa` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '待更新' COMMENT '可发区',
  `guishudi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '待更新' COMMENT '归属地',
  `mark` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注信息',
  `is_id_photo` tinyint(1) NULL DEFAULT 0 COMMENT '是否上传身份证 0-否 1-是',
  `is_four_photo` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否需要第四证:0=否,1=是',
  `four_photo_title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '第四照标题',
  `four_photo` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '第四照查询链接',
  `card_type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '卡类型：0免费卡 1付费卡',
  `card_price` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '卡费金额（付费卡时有效）',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_api_config_id`(`api_config_id`) USING BTREE,
  INDEX `idx_admin_sort`(`admin_sort_order`) USING BTREE,
  INDEX `idx_recommend`(`is_recommend`) USING BTREE,
  INDEX `idx_is_open`(`is_open`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2294 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '产品表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of product
-- ----------------------------

-- ----------------------------
-- Table structure for product_agent_markup
-- ----------------------------
DROP TABLE IF EXISTS `product_agent_markup`;
CREATE TABLE `product_agent_markup`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agent_id` int(11) NOT NULL COMMENT '代理商ID',
  `product_id` int(11) NOT NULL COMMENT '产品ID',
  `markup_price` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '当前代理加价金额',
  `total_markup_price` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '累计加价（含所有上级加价）',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否启用：0禁用 1启用',
  `create_time` int(11) NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_agent_product`(`agent_id`, `product_id`) USING BTREE,
  INDEX `idx_agent_id`(`agent_id`) USING BTREE,
  INDEX `idx_product_id`(`product_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '代理产品加价表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of product_agent_markup
-- ----------------------------

-- ----------------------------
-- Table structure for product_collection
-- ----------------------------
DROP TABLE IF EXISTS `product_collection`;
CREATE TABLE `product_collection`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '合集ID',
  `agent_id` int(11) NULL DEFAULT 0 COMMENT '代理ID，0表示总后台创建的合集',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '合集名称',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '合集描述',
  `sort` int(11) NULL DEFAULT 0 COMMENT '排序（数字越小越靠前）',
  `status` tinyint(1) NULL DEFAULT 1 COMMENT '状态：1=启用，0=禁用',
  `create_time` datetime NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_agent_id`(`agent_id`) USING BTREE,
  INDEX `idx_sort`(`sort`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 12 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '产品合集表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of product_collection
-- ----------------------------

-- ----------------------------
-- Table structure for product_collection_item
-- ----------------------------
DROP TABLE IF EXISTS `product_collection_item`;
CREATE TABLE `product_collection_item`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '关联ID',
  `collection_id` int(11) NOT NULL COMMENT '合集ID',
  `product_id` int(11) NOT NULL COMMENT '产品ID',
  `sort` int(11) NULL DEFAULT 0 COMMENT '排序（数字越小越靠前）',
  `create_time` datetime NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_collection_product`(`collection_id`, `product_id`) USING BTREE,
  INDEX `idx_collection_id`(`collection_id`) USING BTREE,
  INDEX `idx_product_id`(`product_id`) USING BTREE,
  INDEX `idx_sort`(`sort`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 14 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '产品合集关联表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of product_collection_item
-- ----------------------------

-- ----------------------------
-- Table structure for product_custom_image
-- ----------------------------
DROP TABLE IF EXISTS `product_custom_image`;
CREATE TABLE `product_custom_image`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` int(10) UNSIGNED NOT NULL COMMENT '商品ID',
  `template_id` int(10) UNSIGNED NOT NULL COMMENT '模板ID',
  `image_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '生成的图片URL',
  `create_time` datetime NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_product_template`(`product_id`, `template_id`) USING BTREE,
  INDEX `idx_product_id`(`product_id`) USING BTREE,
  INDEX `idx_template_id`(`template_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 18 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '商品自定义图片表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of product_custom_image
-- ----------------------------

-- ----------------------------
-- Table structure for salary_payment_logs
-- ----------------------------
DROP TABLE IF EXISTS `salary_payment_logs`;
CREATE TABLE `salary_payment_logs`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `group_id` int(11) UNSIGNED NOT NULL COMMENT '组ID',
  `group_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '组名称（快照）',
  `agent_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '涉及的代理ID列表（JSON）',
  `agent_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '涉及代理数量',
  `total_order_count` int(11) NOT NULL DEFAULT 0 COMMENT '处理订单总数',
  `total_balance_cleared` decimal(12, 2) NOT NULL DEFAULT 0.00 COMMENT '清空的总余额',
  `operator_id` int(11) UNSIGNED NOT NULL COMMENT '操作人ID',
  `operator_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '操作人名称',
  `payout_month` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '发放月份(YYYY-MM)',
  `remark` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '备注',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '发放时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_group_id`(`group_id`) USING BTREE,
  INDEX `idx_created_at`(`created_at`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 13 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '工资发放记录表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of salary_payment_logs
-- ----------------------------

-- ----------------------------
-- Table structure for secret_price_levels
-- ----------------------------
DROP TABLE IF EXISTS `secret_price_levels`;
CREATE TABLE `secret_price_levels`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `level_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '密价等级名称',
  `icon` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '等级图标文件名',
  `secret_amount` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '密价金额（元）',
  `valid_start` bigint(16) UNSIGNED NULL DEFAULT NULL COMMENT '有效期开始时间',
  `valid_end` bigint(16) UNSIGNED NULL DEFAULT NULL COMMENT '有效期结束时间',
  `sort_order` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序（数字越小越靠前）',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0=禁用，1=启用',
  `create_time` bigint(16) UNSIGNED NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` bigint(16) UNSIGNED NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_status_sort`(`status`, `sort_order`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '密价等级表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of secret_price_levels
-- ----------------------------

-- ----------------------------
-- Table structure for sms_ip_limits
-- ----------------------------
DROP TABLE IF EXISTS `sms_ip_limits`;
CREATE TABLE `sms_ip_limits`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'IP地址',
  `sms_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '短信类型：register注册,login登录,withdraw提现等',
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '手机号码',
  `request_time` int(11) NOT NULL COMMENT '请求时间',
  `success` tinyint(1) NULL DEFAULT 0 COMMENT '是否成功发送：0失败，1成功',
  `user_agent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '用户代理',
  `referer` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '来源页面',
  `create_time` int(11) NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_ip_address`(`ip_address`) USING BTREE,
  INDEX `idx_request_time`(`request_time`) USING BTREE,
  INDEX `idx_sms_type`(`sms_type`) USING BTREE,
  INDEX `idx_ip_time`(`ip_address`, `request_time`) USING BTREE,
  INDEX `idx_create_time`(`create_time`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 96 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '短信IP限制日志表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of sms_ip_limits
-- ----------------------------

-- ----------------------------
-- Table structure for sms_logs
-- ----------------------------
DROP TABLE IF EXISTS `sms_logs`;
CREATE TABLE `sms_logs`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '手机号码',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '短信内容',
  `code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '验证码',
  `template_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '模板ID',
  `provider` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'wangweiyun' COMMENT '服务提供商',
  `sms_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '短信类型：register注册，withdraw提现，login登录，reset重置密码等',
  `status` tinyint(1) NULL DEFAULT 0 COMMENT '发送状态：0失败，1成功',
  `message` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '返回消息',
  `response_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '接口返回数据（JSON格式）',
  `send_time` int(11) NULL DEFAULT 0 COMMENT '发送时间',
  `expire_time` int(11) NULL DEFAULT 0 COMMENT '过期时间',
  `used` tinyint(1) NULL DEFAULT 0 COMMENT '是否已使用：0未使用，1已使用',
  `create_time` int(11) NULL DEFAULT 0 COMMENT '创建时间',
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '发送IP地址',
  `user_agent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '用户代理',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_phone`(`phone`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE,
  INDEX `idx_provider`(`provider`) USING BTREE,
  INDEX `idx_send_time`(`send_time`) USING BTREE,
  INDEX `idx_create_time`(`create_time`) USING BTREE,
  INDEX `idx_code`(`code`) USING BTREE,
  INDEX `idx_expire_time`(`expire_time`) USING BTREE,
  INDEX `idx_used`(`used`) USING BTREE,
  INDEX `idx_phone_code`(`phone`, `code`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 81 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '短信发送日志表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of sms_logs
-- ----------------------------

-- ----------------------------
-- Table structure for system_config
-- ----------------------------
DROP TABLE IF EXISTS `system_config`;
CREATE TABLE `system_config`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '配置ID',
  `config_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '配置键名',
  `config_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '配置值',
  `config_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'text' COMMENT '配置类型：text,textarea,number,radio,checkbox,select,image,file',
  `config_group` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'basic' COMMENT '配置分组：basic,upload,email,sms,security',
  `config_title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '配置标题',
  `config_desc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '配置描述',
  `config_options` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '配置选项（JSON格式，用于select,radio,checkbox类型）',
  `sort_order` int(11) NULL DEFAULT 0 COMMENT '排序',
  `is_required` tinyint(1) NULL DEFAULT 0 COMMENT '是否必填',
  `create_time` int(11) NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(11) NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `config_key`(`config_key`) USING BTREE,
  INDEX `config_group`(`config_group`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 85 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '系统配置表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of system_config
-- ----------------------------
INSERT INTO `system_config` VALUES (1, 'site_name', '', 'text', 'basic', '网站名称', '网站的名称，显示在浏览器标题栏', NULL, 1, 0, 1756710628, 1759390676);
INSERT INTO `system_config` VALUES (2, 'site_logo', '/logo.png', 'image', 'basic', '网站Logo', '网站的Logo图片', NULL, 2, 0, 1756710628, 1759390676);
INSERT INTO `system_config` VALUES (3, 'site_favicon', '/favicon.ico', 'image', 'basic', '网站图标', '网站的favicon图标', NULL, 3, 0, 1756710628, 1759390676);
INSERT INTO `system_config` VALUES (4, 'site_keywords', '流量卡,手机卡,电话卡', 'text', 'basic', '网站关键词', 'SEO关键词，多个用逗号分隔', NULL, 4, 0, 1756710628, 1756720756);
INSERT INTO `system_config` VALUES (5, 'site_description', '专业的流量卡管理系统', 'textarea', 'basic', '网站描述', 'SEO描述信息', NULL, 5, 0, 1756710628, 1756720756);
INSERT INTO `system_config` VALUES (6, 'site_copyright', '巨量号卡版权所有', 'text', 'basic', '版权信息', '网站底部显示的版权信息', NULL, 6, 0, 1756710628, 1759390676);
INSERT INTO `system_config` VALUES (7, 'site_icp', '', 'text', 'basic', 'ICP备案号', '网站ICP备案号', NULL, 7, 0, 1756710628, 1759390676);
INSERT INTO `system_config` VALUES (8, 'site_status', '1', 'radio', 'basic', '网站状态', '网站开启或关闭状态', NULL, 8, 0, 1756710628, 1759390676);
INSERT INTO `system_config` VALUES (9, 'upload_max_size', '10', 'number', 'upload', '文件大小限制', '上传文件的最大大小，单位MB', NULL, 1, 0, 1756710628, 1756710628);
INSERT INTO `system_config` VALUES (10, 'upload_allowed_ext', 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx', 'text', 'upload', '允许的文件类型', '允许上传的文件扩展名，用逗号分隔', NULL, 2, 0, 1756710628, 1756710628);
INSERT INTO `system_config` VALUES (11, 'email_smtp_host', 'smtp.qq.com', 'text', 'email', 'SMTP服务器', 'SMTP服务器地址', NULL, 1, 0, 1756710628, 1757931096);
INSERT INTO `system_config` VALUES (12, 'email_smtp_port', '465', 'number', 'email', 'SMTP端口', 'SMTP服务器端口', NULL, 2, 0, 1756710628, 1757931096);
INSERT INTO `system_config` VALUES (13, 'email_smtp_user', '', 'text', 'email', 'SMTP用户名', 'SMTP登录用户名', NULL, 3, 0, 1756710628, 1757931096);
INSERT INTO `system_config` VALUES (14, 'email_smtp_pass', '', 'password', 'email', 'SMTP密码', 'SMTP登录密码', NULL, 4, 0, 1756710628, 1757931096);
INSERT INTO `system_config` VALUES (15, 'sms_provider', 'wangweiyun', 'select', 'sms', '短信服务商', '选择短信服务提供商', NULL, 1, 0, 1756710628, 1756965977);
INSERT INTO `system_config` VALUES (16, 'sms_app_id', '', 'text', 'sms', '短信AppID', '短信服务的AppID', NULL, 2, 0, 1756710628, 1756965977);
INSERT INTO `system_config` VALUES (17, 'sms_app_key', '', 'password', 'sms', '短信AppKey', '短信服务的AppKey', NULL, 3, 0, 1756710628, 1756965977);
INSERT INTO `system_config` VALUES (18, 'login_fail_limit', '5', 'number', 'security', '登录失败限制', '登录失败次数限制', NULL, 1, 0, 1756710628, 1756710628);
INSERT INTO `system_config` VALUES (19, 'login_lock_time', '30', 'number', 'security', '锁定时间', '登录失败后锁定时间，单位分钟', NULL, 2, 0, 1756710628, 1756710628);
INSERT INTO `system_config` VALUES (20, 'file', '', 'text', 'basic', 'file', '', NULL, 0, 0, 1756715447, 1759390676);
INSERT INTO `system_config` VALUES (35, 'agent_register_verify', 'sms', 'text', 'basic', 'agent_register_verify', '注册验证方式', NULL, 0, 0, 1756972371, 1770564897);
INSERT INTO `system_config` VALUES (36, 'agent_withdraw_verify', 'sms', 'text', 'basic', 'agent_withdraw_verify', '提现验证方式', NULL, 0, 0, 1756972371, 1770564897);
INSERT INTO `system_config` VALUES (37, 'shop_order_verify', 'none', 'text', 'basic', 'shop_order_verify', '下单验证方式', NULL, 0, 0, 1756972371, 1770564897);
INSERT INTO `system_config` VALUES (38, 'verify_code_length', '6', 'text', 'basic', 'verify_code_length', '验证码长度', NULL, 0, 0, 1756972371, 1756972518);
INSERT INTO `system_config` VALUES (39, 'verify_code_expire', '300', 'text', 'basic', 'verify_code_expire', '验证码有效期', NULL, 0, 0, 1756972371, 1770564897);
INSERT INTO `system_config` VALUES (40, 'verify_code_interval', '60', 'text', 'basic', 'verify_code_interval', '获取间隔', NULL, 0, 0, 1756972371, 1770564897);
INSERT INTO `system_config` VALUES (54, 'idcard_enable', '1', 'text', 'other', 'idcard_enable', '是否实名认证', NULL, 0, 0, 1756984092, 1772640275);
INSERT INTO `system_config` VALUES (55, 'idcard_appcode', '', 'text', 'idcard', 'idcard_appcode', '实名认证appcode', NULL, 0, 0, 1756984092, 1772640275);
INSERT INTO `system_config` VALUES (58, 'auto_fill_verify_code', '1', 'radio', 'other', '验证码自动回填', '开启后，当用户多次获取验证码时，系统会自动将验证码回填到输入框中', NULL, 5, 0, 1756994086, 1770564897);
INSERT INTO `system_config` VALUES (59, 'auto_fill_trigger_count', '2', 'number', 'other', '回填触发次数', '用户获取验证码达到此次数时，自动回填验证码（默认3次）', NULL, 6, 0, 1756994086, 1770564897);
INSERT INTO `system_config` VALUES (60, 'sms_ip_hour_limit', '100', 'number', 'other', 'sms_ip_hour_limit', '小时短信获取次数', NULL, 0, 0, 1756996325, 1770564897);
INSERT INTO `system_config` VALUES (61, 'sms_ip_day_limit', '500', 'number', 'other', 'sms_ip_day_limit', '当天短信获取次数', NULL, 0, 0, 1756996325, 1770564897);
INSERT INTO `system_config` VALUES (64, 'min_withdraw_amount', '13', 'number', 'other', 'min_withdraw_amount', '最低提现金额', NULL, 0, 0, 1756998045, 1770564897);
INSERT INTO `system_config` VALUES (65, 'withdraw_fee_rate', '6', 'number', 'other', 'withdraw_fee_rate', '提现费率', NULL, 0, 0, 1756998045, 1770564897);
INSERT INTO `system_config` VALUES (66, 'min_withdraw_fee', '1', 'number', 'other', 'min_withdraw_fee', '最低手续费', NULL, 0, 0, 1756998045, 1770564897);
INSERT INTO `system_config` VALUES (67, 'max_withdraw_fee', '10', 'number', 'other', 'max_withdraw_fee', '最高手续费', NULL, 0, 0, 1756998045, 1770564897);
INSERT INTO `system_config` VALUES (68, 'account_security_deposit', '100', 'number', 'other', 'account_security_deposit', '保证金', NULL, 0, 0, 1756998045, 1770564897);
INSERT INTO `system_config` VALUES (69, 'security_deposit_description', '为保障平台资金安全，账户需保留一定金额作为保证金，用于处理售后等业务', 'textarea', 'other', 'security_deposit_description', '保证金说明', NULL, 0, 0, 1756998526, 1770564897);
INSERT INTO `system_config` VALUES (70, 'security_key', 'vmFdqzQx', 'text', 'basic', 'security_key', '安全密钥', NULL, 0, 0, 1757056326, 1759390676);
INSERT INTO `system_config` VALUES (71, 'api_sync_image_mode', 'original', 'select', 'api', 'API同步商品图片处理方式', '选择API同步商品时如何处理图片：本地存储（下载到服务器）、云存储（上传到云端）、原始链接（直接使用API图片链接）', '{\"local\":\"本地存储\",\"cloud\":\"云存储\",\"original\":\"原始链接\"}', 10, 1, 1757402687, 1758261534);
INSERT INTO `system_config` VALUES (72, 'logistics_enabled', '1', 'text', 'basic', '启用物流查询', '', NULL, 0, 0, 1757429919, 1757430292);
INSERT INTO `system_config` VALUES (73, 'logistics_provider', 'jumei', 'text', 'basic', '物流服务提供商', '', NULL, 0, 0, 1757429919, 1757430292);
INSERT INTO `system_config` VALUES (74, 'logistics_appcode', '', 'text', 'basic', '物流查询AppCode', '', NULL, 0, 0, 1757429919, 1757430292);
INSERT INTO `system_config` VALUES (75, 'logistics_api_url', 'https://jmexpresv2.market.alicloudapi.com', 'text', 'basic', '物流API地址', '', NULL, 0, 0, 1757429919, 1757430292);
INSERT INTO `system_config` VALUES (76, 'logistics_api_path', '/express/query-v2', 'text', 'basic', '物流查询路径', '', NULL, 0, 0, 1757429919, 1757430292);
INSERT INTO `system_config` VALUES (77, 'express_enabled', '1', 'text', 'basic', 'express_enabled', '', NULL, 0, 0, 1757430303, 1772640270);
INSERT INTO `system_config` VALUES (78, 'express_provider', 'jumei', 'text', 'basic', 'express_provider', '', NULL, 0, 0, 1757430303, 1772640270);
INSERT INTO `system_config` VALUES (79, 'express_appcode', '', 'text', 'basic', 'express_appcode', '', NULL, 0, 0, 1757430303, 1772640270);
INSERT INTO `system_config` VALUES (80, 'express_api_url', 'https://jmexpresv2.market.alicloudapi.com', 'text', 'basic', 'express_api_url', '', NULL, 0, 0, 1757430303, 1772640270);
INSERT INTO `system_config` VALUES (81, 'express_api_path', '/express/query-v2', 'text', 'basic', 'express_api_path', '', NULL, 0, 0, 1757430303, 1772640270);
INSERT INTO `system_config` VALUES (82, 'agent_id_start', '1', 'text', 'other', 'agent_id_start', '', NULL, 0, 0, 1757931854, 1770564897);
INSERT INTO `system_config` VALUES (83, 'order_prefix', 'HK', 'text', 'other', '订单号前缀', '', NULL, 0, 0, 1757932983, 1770564897);
INSERT INTO `system_config` VALUES (84, 'agent_resubmit_order_enabled', '1', 'text', 'basic', '代理重提开关', '', NULL, 0, 0, 1759564234, 1770564897);

-- ----------------------------
-- Table structure for temp_orders
-- ----------------------------
DROP TABLE IF EXISTS `temp_orders`;
CREATE TABLE `temp_orders`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `temp_order_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `session_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `create_time` int(11) NOT NULL,
  `expire_time` int(11) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `temp_order_no`(`temp_order_no`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 192 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of temp_orders
-- ----------------------------

-- ----------------------------
-- Table structure for ticket_attachments
-- ----------------------------
DROP TABLE IF EXISTS `ticket_attachments`;
CREATE TABLE `ticket_attachments`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '附件ID',
  `ticket_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '工单ID',
  `reply_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '回复ID(0表示工单主体附件)',
  `filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件名',
  `original_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '原始文件名',
  `file_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件路径',
  `file_size` int(11) NOT NULL DEFAULT 0 COMMENT '文件大小(字节)',
  `file_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件类型',
  `upload_user_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '上传用户类型(1:代理商,2:管理员)',
  `upload_user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '上传用户ID',
  `create_time` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `ticket_id`(`ticket_id`) USING BTREE,
  INDEX `reply_id`(`reply_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '工单附件表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of ticket_attachments
-- ----------------------------

-- ----------------------------
-- Table structure for ticket_categories
-- ----------------------------
DROP TABLE IF EXISTS `ticket_categories`;
CREATE TABLE `ticket_categories`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '分类ID',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类名称',
  `description` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类描述',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态(0:禁用,1:启用)',
  `create_time` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 7 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '工单分类配置表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of ticket_categories
-- ----------------------------

-- ----------------------------
-- Table structure for ticket_replies
-- ----------------------------
DROP TABLE IF EXISTS `ticket_replies`;
CREATE TABLE `ticket_replies`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '回复ID',
  `ticket_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '工单ID',
  `user_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '回复用户类型(1:代理商,2:管理员)',
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '回复用户ID',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '回复内容',
  `attachments` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '附件信息(JSON格式)',
  `is_internal` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否内部备注(0:否,1:是)',
  `create_time` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `ticket_id`(`ticket_id`) USING BTREE,
  INDEX `user_type`(`user_type`) USING BTREE,
  INDEX `create_time`(`create_time`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 16 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '工单回复表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of ticket_replies
-- ----------------------------

-- ----------------------------
-- Table structure for tickets
-- ----------------------------
DROP TABLE IF EXISTS `tickets`;
CREATE TABLE `tickets`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '工单ID',
  `ticket_no` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '工单编号',
  `agent_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '提交代理商ID',
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '工单标题',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '工单内容',
  `category_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '工单分类ID',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态(1:待处理,2:处理中,3:已解决,4:已关闭)',
  `admin_reply` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '管理员回复',
  `reply_time` int(11) NOT NULL DEFAULT 0 COMMENT '回复时间',
  `close_time` int(11) NOT NULL DEFAULT 0 COMMENT '关闭时间',
  `create_time` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `ticket_no`(`ticket_no`) USING BTREE,
  INDEX `agent_id`(`agent_id`) USING BTREE,
  INDEX `status`(`status`) USING BTREE,
  INDEX `category_id`(`category_id`) USING BTREE,
  INDEX `create_time`(`create_time`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 14 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '工单表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of tickets
-- ----------------------------

-- ----------------------------
-- Table structure for version_file_logs
-- ----------------------------
DROP TABLE IF EXISTS `version_file_logs`;
CREATE TABLE `version_file_logs`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `version_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '版本ID',
  `auth_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '授权码',
  `file_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '文件名',
  `file_md5` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '文件MD5',
  `process_result` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '处理结果',
  `process_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '处理状态:0失败,1成功',
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '错误信息',
  `extra_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '额外数据(JSON)',
  `process_time` int(11) NOT NULL COMMENT '处理时间戳',
  `create_time` int(11) NOT NULL COMMENT '创建时间戳',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_version_auth`(`version_id`, `auth_code`) USING BTREE,
  INDEX `idx_file_md5`(`file_name`, `file_md5`) USING BTREE,
  INDEX `idx_process_status`(`process_status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '版本文件处理日志表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of version_file_logs
-- ----------------------------

-- ----------------------------
-- Table structure for version_sql_logs
-- ----------------------------
DROP TABLE IF EXISTS `version_sql_logs`;
CREATE TABLE `version_sql_logs`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `version_id` int(11) NOT NULL COMMENT '版本ID',
  `auth_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '执行的授权码',
  `sql_content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '执行的SQL内容',
  `execution_result` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '执行结果',
  `execution_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '执行状态 1成功 0失败',
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '错误信息',
  `execution_time` int(11) NOT NULL DEFAULT 0 COMMENT '执行时间',
  `create_time` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `version_id`(`version_id`) USING BTREE,
  INDEX `auth_code`(`auth_code`) USING BTREE,
  INDEX `execution_time`(`execution_time`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 8 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '版本SQL执行日志表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of version_sql_logs
-- ----------------------------

-- ----------------------------
-- Table structure for withdraws
-- ----------------------------
DROP TABLE IF EXISTS `withdraws`;
CREATE TABLE `withdraws`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `withdraw_no` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '提现编号',
  `agent_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '代理商ID',
  `payment_method_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '收款方式ID（关联agent_payment_methods表）',
  `withdraw_type` enum('alipay','bank') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'alipay' COMMENT '提现方式：alipay=支付宝,bank=银行卡',
  `account` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '收款账户',
  `account_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '收款人姓名',
  `bank_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '银行名称（银行卡专用）',
  `bank_branch` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '开户行支行（银行卡专用）',
  `amount` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '申请金额',
  `fee` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '手续费',
  `actual_amount` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '实际到账金额',
  `status` enum('pending','success','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending' COMMENT '状态：pending=待处理,success=已完成,rejected=已拒绝',
  `remark` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '申请备注',
  `admin_remark` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '管理员处理备注',
  `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '申请时间',
  `process_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `withdraw_no`(`withdraw_no`) USING BTREE,
  INDEX `agent_id`(`agent_id`) USING BTREE,
  INDEX `payment_method_id`(`payment_method_id`) USING BTREE,
  INDEX `status`(`status`) USING BTREE,
  INDEX `create_time`(`create_time`) USING BTREE,
  INDEX `idx_agent_status`(`agent_id`, `status`) USING BTREE,
  INDEX `idx_status_time`(`status`, `create_time`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 25 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '提现申请表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of withdraws
-- ----------------------------

-- ----------------------------
-- View structure for v_enabled_payments
-- ----------------------------
DROP VIEW IF EXISTS `v_enabled_payments`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `v_enabled_payments` AS select `pm`.`payment_type` AS `payment_type`,`pm`.`payment_name` AS `payment_name`,`pm`.`sort_order` AS `sort_order`,`pm`.`icon_url` AS `icon_url`,`pm`.`description` AS `description`,group_concat(concat(`pc`.`config_key`,':',`pc`.`config_value`) order by `pc`.`config_key` ASC separator '|') AS `configs` from (`payment_methods` `pm` left join `payment_configs` `pc` on((`pm`.`payment_type` = `pc`.`payment_type`))) where (`pm`.`is_enabled` = 1) group by `pm`.`payment_type`,`pm`.`payment_name`,`pm`.`sort_order`,`pm`.`icon_url`,`pm`.`description` order by `pm`.`sort_order`;

SET FOREIGN_KEY_CHECKS = 1;
