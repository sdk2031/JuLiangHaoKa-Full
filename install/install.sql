/*
 Navicat Premium Data Transfer

 Source Server         : 巨量号卡本地
 Source Server Type    : MySQL
 Source Server Version : 50740
 Source Host           : localhost:3306
 Source Schema         : demo-hk

 Target Server Type    : MySQL
 Target Server Version : 50740
 File Encoding         : 65001

 Date: 27/06/2026 01:10:22
*/

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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '营销活动表' ROW_FORMAT = DYNAMIC;

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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '活动领取记录表' ROW_FORMAT = DYNAMIC;

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
-- Table structure for admin_employee_agent_attribution
-- ----------------------------
DROP TABLE IF EXISTS `admin_employee_agent_attribution`;
CREATE TABLE `admin_employee_agent_attribution`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_admin_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '员工管理员ID',
  `promoted_agent_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '员工发展/归属的代理ID，对应 agents.id',
  `origin_agent_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '归属链路起点代理ID',
  `attribution_source` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'invite' COMMENT '归属来源:invite/inherit/manual/migrate',
  `employee_suffix_snapshot` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '绑定时员工后缀快照',
  `source_invite_code` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '来源邀请码',
  `attribution_time` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `update_admin_id` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_promoted_agent_id`(`promoted_agent_id`) USING BTREE,
  INDEX `idx_employee_admin_id`(`employee_admin_id`) USING BTREE,
  INDEX `idx_origin_agent_id`(`origin_agent_id`) USING BTREE,
  INDEX `idx_employee_origin`(`employee_admin_id`, `origin_agent_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '总后台员工发展代理归属表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of admin_employee_agent_attribution
-- ----------------------------

-- ----------------------------
-- Table structure for admin_employee_invite_codes
-- ----------------------------
DROP TABLE IF EXISTS `admin_employee_invite_codes`;
CREATE TABLE `admin_employee_invite_codes`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_admin_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '员工管理员ID',
  `distribution_level_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '固定等级ID',
  `invite_code` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '员工专属邀请码',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态:1启用,0停用',
  `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_invite_code`(`invite_code`) USING BTREE,
  UNIQUE INDEX `uk_employee_level`(`employee_admin_id`, `distribution_level_id`) USING BTREE,
  INDEX `idx_employee_admin_id`(`employee_admin_id`) USING BTREE,
  INDEX `idx_distribution_level_id`(`distribution_level_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '员工专属邀请码表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of admin_employee_invite_codes
-- ----------------------------

-- ----------------------------
-- Table structure for admin_employee_profile
-- ----------------------------
DROP TABLE IF EXISTS `admin_employee_profile`;
CREATE TABLE `admin_employee_profile`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '管理员ID',
  `employee_code` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '员工号',
  `employee_suffix` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '推广后缀',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态:1启用,0停用',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `update_admin_id` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_admin_id`(`admin_id`) USING BTREE,
  UNIQUE INDEX `uk_employee_code`(`employee_code`) USING BTREE,
  UNIQUE INDEX `uk_employee_suffix`(`employee_suffix`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '总后台员工扩展资料' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of admin_employee_profile
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

-- ----------------------------
-- Records of admin_operation_logs
-- ----------------------------

-- ----------------------------
-- Table structure for admin_role_permissions
-- ----------------------------
DROP TABLE IF EXISTS `admin_role_permissions`;
CREATE TABLE `admin_role_permissions`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `role_id` int(11) NOT NULL COMMENT '角色ID',
  `permission` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '权限标识',
  `create_time` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_role_permission`(`role_id`, `permission`) USING BTREE,
  INDEX `idx_role_id`(`role_id`) USING BTREE,
  INDEX `idx_permission`(`permission`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '角色权限关系表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of admin_role_permissions
-- ----------------------------

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
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '管理员角色关联表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of admin_role_relation
-- ----------------------------
INSERT INTO `admin_role_relation` VALUES (1, 1, 1, 1760426987);

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
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '管理员角色表' ROW_FORMAT = DYNAMIC;

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
  `login_token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '当前登录Token标识',
  `default_agent_id` int(11) NOT NULL DEFAULT 0 COMMENT '默认代理ID',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `username`(`username`) USING BTREE,
  INDEX `idx_default_agent_id`(`default_agent_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '管理员表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of admins
-- ----------------------------
INSERT INTO `admins` VALUES (1, 'admin', 'b14461b89d6d5a49e19cc419852ed2bb', '001f1d9a87', '超级管理员', '', 1, 1782357835, '127.0.0.1', 1775569696, 1782357835, '7520175c404376d10c655e7efd88a58b', 5012);

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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '代理商账户表' ROW_FORMAT = DYNAMIC;

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
  `wallet_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'normal' COMMENT '余额类型：normal=代理余额,api_balance=API预存余额',
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
  INDEX `idx_type_status`(`type`, `status`) USING BTREE,
  INDEX `idx_wallet_type`(`wallet_type`) USING BTREE,
  INDEX `idx_agent_wallet_time`(`agent_id`, `wallet_type`, `create_time`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '代理商余额变动日志表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of agent_balance_logs
-- ----------------------------

-- ----------------------------
-- Table structure for agent_employee_profile
-- ----------------------------
DROP TABLE IF EXISTS `agent_employee_profile`;
CREATE TABLE `agent_employee_profile`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `agent_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '代理ID',
  `employee_code` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '员工号',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态:1启用,0停用',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `update_admin_id` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_agent_id`(`agent_id`) USING BTREE,
  UNIQUE INDEX `uk_employee_code`(`employee_code`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '员工代理档案' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of agent_employee_profile
-- ----------------------------

-- ----------------------------
-- Table structure for agent_groups
-- ----------------------------
DROP TABLE IF EXISTS `agent_groups`;
CREATE TABLE `agent_groups`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '分组ID',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '分组名称',
  `description` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '分组描述',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态:1启用,0禁用',
  `create_time` datetime NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_status_sort`(`status`, `sort`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '代理分组表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of agent_groups
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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '代理商实名认证日志' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of agent_idcard_logs
-- ----------------------------

-- ----------------------------
-- Table structure for agent_login_tokens
-- ----------------------------
DROP TABLE IF EXISTS `agent_login_tokens`;
CREATE TABLE `agent_login_tokens`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `agent_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `token` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `expires_at` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `last_active_time` int(10) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uniq_token`(`token`) USING BTREE,
  INDEX `idx_agent_id`(`agent_id`) USING BTREE,
  INDEX `idx_expires_at`(`expires_at`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '代理多端登录Token' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of agent_login_tokens
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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '代理迁移记录表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of agent_migrate_logs
-- ----------------------------

-- ----------------------------
-- Table structure for agent_payment_methods
-- ----------------------------
DROP TABLE IF EXISTS `agent_payment_subjects`;
CREATE TABLE `agent_payment_subjects`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `agent_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '代理商ID',
  `subject_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '收款主体姓名',
  `mobile` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '签约手机号',
  `id_card` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '签约证件号',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0=禁用,1=启用',
  `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_agent_status`(`agent_id`, `status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '代理商收款主体表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for agent_payment_methods
-- ----------------------------
DROP TABLE IF EXISTS `agent_payment_methods`;
CREATE TABLE `agent_payment_methods`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `agent_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '代理商ID',
  `subject_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '收款主体ID',
  `payment_type` enum('alipay','bank','wechat') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'alipay' COMMENT '收款方式：alipay=支付宝,bank=银行卡,wechat=微信',
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
  INDEX `subject_id`(`subject_id`) USING BTREE,
  INDEX `payment_type`(`payment_type`) USING BTREE,
  INDEX `is_default`(`is_default`) USING BTREE,
  INDEX `status`(`status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '代理商收款方式表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of agent_payment_methods
-- ----------------------------

-- ----------------------------
-- Table structure for agent_payout_contracts
-- ----------------------------
DROP TABLE IF EXISTS `agent_payout_contracts`;
CREATE TABLE `agent_payout_contracts`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `agent_id` int(11) NOT NULL COMMENT '代理ID',
  `subject_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '收款主体ID',
  `provider_key` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '渠道标识',
  `contract_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '签约状态 0未签约 1签约中 2已签约 3失败',
  `contract_no` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '签约单号/协议号',
  `bind_channel` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '绑定渠道(wechat/alipay/bankcard)',
  `openid` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '微信openid(如需要)',
  `unionid` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '微信unionid',
  `mobile` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '签约手机号',
  `real_name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '签约实名',
  `id_card` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '签约证件号',
  `sign_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '签约引导链接',
  `raw_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '签约原始报文(JSON)',
  `fail_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '失败原因',
  `signed_at` int(11) NOT NULL DEFAULT 0 COMMENT '签约完成时间戳',
  `create_time` int(11) NOT NULL DEFAULT 0,
  `update_time` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_agent_provider_subject`(`agent_id`, `provider_key`, `subject_id`) USING BTREE,
  INDEX `idx_provider_status`(`provider_key`, `contract_status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '代理打款签约绑定表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of agent_payout_contracts
-- ----------------------------

-- ----------------------------
-- Table structure for agent_product_block
-- ----------------------------
DROP TABLE IF EXISTS `agent_product_block`;
CREATE TABLE `agent_product_block`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `agent_id` int(11) NOT NULL COMMENT '代理ID',
  `product_id` int(11) NOT NULL COMMENT '产品ID',
  `block_shop` tinyint(1) NOT NULL DEFAULT 0 COMMENT '屏蔽店铺展示',
  `block_sub_agent` tinyint(1) NOT NULL DEFAULT 0 COMMENT '屏蔽下级推广',
  `create_time` datetime NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `agent_product`(`agent_id`, `product_id`) USING BTREE,
  INDEX `product_id`(`product_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '代理产品屏蔽设置' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of agent_product_block
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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '代理产品自定义排序表（JSON格式）' ROW_FORMAT = DYNAMIC;

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
  `public_token` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '店铺公开访问Token',
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
  UNIQUE INDEX `public_token`(`public_token`) USING BTREE,
  INDEX `status`(`status`) USING BTREE,
  INDEX `distribution_enabled`(`distribution_enabled`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '代理店铺表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of agent_shop
-- ----------------------------

-- ----------------------------
-- Table structure for agent_shop_product
-- ----------------------------
DROP TABLE IF EXISTS `agent_shop_product`;
CREATE TABLE `agent_shop_product`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `token` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '商品公开访问Token',
  `shop_id` int(11) NOT NULL COMMENT '店铺ID',
  `shop_code` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '店铺编码',
  `product_id` int(11) NOT NULL COMMENT '商品ID',
  `create_time` bigint(13) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `token`(`token`) USING BTREE,
  UNIQUE INDEX `shop_product`(`shop_id`, `product_id`) USING BTREE,
  INDEX `shop_code`(`shop_code`) USING BTREE,
  INDEX `product_id`(`product_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '代理店铺商品公开链接表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of agent_shop_product
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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '店铺访问记录表' ROW_FORMAT = DYNAMIC;

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
  `group_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '代理分组ID',
  `username` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户名',
  `mobile` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '手机号',
  `balance` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '余额（元）',
  `api_balance` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT 'API预存余额',
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
  `freeze_reason` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '冻结原因',
  `fadada_sign_status` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none' COMMENT '法大大签约状态 none/pending_payment/pending_review/approved/rejected',
  `fadada_sign_record_id` int(11) NOT NULL DEFAULT 0 COMMENT '最近法大大签约记录ID',
  `submit_order_enabled` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否允许提单',
  `invite_agent_enabled` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否允许发展代理',
  `withdraw_enabled` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否允许提现',
  `invite_code_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '邀请码ID',
  `distribution_level_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '固定分销等级ID（fixed模式使用）',
  `agent_custom_domain` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'agent_代理自定义域名(仅host)',
  `agent_custom_domain_enabled` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'agent_代理自定义域名启用状态',
  `agent_custom_site_name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'agent_代理自定义系统名称',
  `agent_custom_logo` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'agent_代理自定义Logo',
  `agent_custom_icp` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'agent_OEM ICP备案号',
  `agent_level` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal' COMMENT '代理级别',
  `secret_price_level_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '密价等级ID',
  `secret_price_valid_start` int(11) UNSIGNED NULL DEFAULT NULL COMMENT '个人激励绑定开始时间',
  `secret_price_valid_end` int(11) UNSIGNED NULL DEFAULT NULL COMMENT '个人激励绑定结束时间',
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
  INDEX `idx_qq_openid`(`qq_openid`) USING BTREE,
  INDEX `idx_distribution_level_id`(`distribution_level_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '代理商表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of agents
-- ----------------------------

-- ----------------------------
-- Table structure for agreement_protocols
-- ----------------------------
DROP TABLE IF EXISTS `agreement_protocols`;
CREATE TABLE `agreement_protocols`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '协议ID',
  `title` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '协议标题',
  `content` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '协议内容',
  `scenes` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '生效场景，逗号分隔',
  `is_default_order` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否全部商品默认下单协议',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1启用',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `create_time` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_scene_status`(`status`, `sort_order`) USING BTREE,
  INDEX `idx_default_order`(`is_default_order`, `status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '协议管理' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of agreement_protocols
-- ----------------------------

-- ----------------------------
-- Table structure for app_application_artifacts
-- ----------------------------
DROP TABLE IF EXISTS `app_application_artifacts`;
CREATE TABLE `app_application_artifacts`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `app_id` int(11) NOT NULL DEFAULT 0,
  `platform` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `title` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `source_type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'manual',
  `package_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `download_url` varchar(800) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `link_url` varchar(800) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `qrcode_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `file_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `version_name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `pack_task_id` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `sort` int(11) NOT NULL DEFAULT 100,
  `remark` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `create_time` int(11) NOT NULL DEFAULT 0,
  `update_time` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_pack_task`(`pack_task_id`) USING BTREE,
  INDEX `idx_app`(`app_id`) USING BTREE,
  INDEX `idx_platform`(`platform`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '应用端资源' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of app_application_artifacts
-- ----------------------------

-- ----------------------------
-- Table structure for app_application_categories
-- ----------------------------
DROP TABLE IF EXISTS `app_application_categories`;
CREATE TABLE `app_application_categories`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `sort` int(11) NOT NULL DEFAULT 100,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `create_time` int(11) NOT NULL DEFAULT 0,
  `update_time` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_sort`(`sort`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '应用分类' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of app_application_categories
-- ----------------------------
INSERT INTO `app_application_categories` VALUES (1, '默认分类', 100, 1, '系统自动创建', 1780630727, 1780630727);

-- ----------------------------
-- Table structure for app_application_deleted_syncs
-- ----------------------------
DROP TABLE IF EXISTS `app_application_deleted_syncs`;
CREATE TABLE `app_application_deleted_syncs`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sync_key` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `app_name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `target_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `create_time` int(11) NOT NULL DEFAULT 0,
  `update_time` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uniq_sync_key`(`sync_key`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '应用删除同步屏蔽' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of app_application_deleted_syncs
-- ----------------------------

-- ----------------------------
-- Table structure for app_applications
-- ----------------------------
DROP TABLE IF EXISTS `app_applications`;
CREATE TABLE `app_applications`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL DEFAULT 0,
  `sync_key` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `app_name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `logo` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `target_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `template` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'dibaqu_temp_1',
  `publish_agent` tinyint(1) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `sort` int(11) NOT NULL DEFAULT 100,
  `remark` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `create_time` int(11) NOT NULL DEFAULT 0,
  `update_time` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_sync_key`(`sync_key`) USING BTREE,
  INDEX `idx_category`(`category_id`) USING BTREE,
  INDEX `idx_status_sort`(`status`, `sort`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '应用管理' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of app_applications
-- ----------------------------

-- ----------------------------
-- Table structure for app_distribution_pages
-- ----------------------------
DROP TABLE IF EXISTS `app_distribution_pages`;
CREATE TABLE `app_distribution_pages`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `app_id` int(11) NOT NULL DEFAULT 0,
  `slug` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `sync_key` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `template` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'dibaqu_1',
  `publish_agent` tinyint(1) NOT NULL DEFAULT 0,
  `app_name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `target_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `icon_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `android_task_id` int(11) NOT NULL DEFAULT 0,
  `android_package_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `android_download_url` varchar(800) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `android_certificate_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `ios_task_id` int(11) NOT NULL DEFAULT 0,
  `ios_package_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `ios_download_url` varchar(800) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `create_time` int(11) NOT NULL DEFAULT 0,
  `update_time` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uniq_slug`(`slug`) USING BTREE,
  UNIQUE INDEX `uniq_sync_key`(`sync_key`) USING BTREE,
  INDEX `idx_update_time`(`update_time`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'APP分发页' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of app_distribution_pages
-- ----------------------------

-- ----------------------------
-- Table structure for app_pack_tasks
-- ----------------------------
DROP TABLE IF EXISTS `app_pack_tasks`;
CREATE TABLE `app_pack_tasks`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `local_task_no` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `remote_task_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `platform` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `app_name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `target_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `icon_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `package_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `certificate_mode` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `certificate_id` int(11) NOT NULL DEFAULT 0,
  `certificate_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `top_bar_color` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `splash_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `charge_points` int(11) NOT NULL DEFAULT 0,
  `credits_balance_after` int(11) NULL DEFAULT NULL,
  `download_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `origin_download_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `transferred_url` varchar(800) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `transferred_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `transferred_provider` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `transferred_time` int(11) NOT NULL DEFAULT 0,
  `wrapper_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `wrapper_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `status` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `remote_response` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `operator_id` int(11) NOT NULL DEFAULT 0,
  `operator_name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `create_time` int(11) NOT NULL DEFAULT 0,
  `update_time` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_platform`(`platform`) USING BTREE,
  INDEX `idx_remote_task_id`(`remote_task_id`) USING BTREE,
  INDEX `idx_create_time`(`create_time`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'APP远程打包记录' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of app_pack_tasks
-- ----------------------------

-- ----------------------------
-- Table structure for available_numbers
-- ----------------------------
DROP TABLE IF EXISTS `available_numbers`;
CREATE TABLE `available_numbers`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '号码',
  `iccid` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT 'ICCID',
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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '号码池管理表' ROW_FORMAT = DYNAMIC;

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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '黑名单表' ROW_FORMAT = DYNAMIC;

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
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '黑名单配置表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of blacklist_config
-- ----------------------------
INSERT INTO `blacklist_config` VALUES (1, 1, 1, 1, '2025-10-10 23:09:13');
INSERT INTO `blacklist_config` VALUES (2, 1, 1, 1, '2025-10-10 23:09:26');
INSERT INTO `blacklist_config` VALUES (3, 1, 1, 1, '2026-05-19 00:14:04');

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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '黑名单操作日志表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of blacklist_log
-- ----------------------------

-- ----------------------------
-- Table structure for cloudexport_callback_logs
-- ----------------------------
DROP TABLE IF EXISTS `cloudexport_callback_logs`;
CREATE TABLE `cloudexport_callback_logs`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `config_id` int(11) NOT NULL DEFAULT 0,
  `order_no` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `request_body` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `parsed_row` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'success',
  `message` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `created_time` datetime NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_config_id`(`config_id`) USING BTREE,
  INDEX `idx_order_no`(`order_no`) USING BTREE,
  INDEX `idx_created_time`(`created_time`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'WPS回调历史' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of cloudexport_callback_logs
-- ----------------------------

-- ----------------------------
-- Table structure for cloudexport_push_logs
-- ----------------------------
DROP TABLE IF EXISTS `cloudexport_push_logs`;
CREATE TABLE `cloudexport_push_logs`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `config_id` int(11) NOT NULL DEFAULT 0,
  `order_id` int(11) NOT NULL DEFAULT 0,
  `order_no` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `event_type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `trigger_source` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `webhook_url` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `request_body` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `response_body` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `message` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `http_code` int(11) NOT NULL DEFAULT 0,
  `retry_count` int(11) NOT NULL DEFAULT 0,
  `created_time` datetime NOT NULL,
  `updated_time` datetime NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_config_id`(`config_id`) USING BTREE,
  INDEX `idx_order_no`(`order_no`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE,
  INDEX `idx_created_time`(`created_time`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'WPS推送历史' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of cloudexport_push_logs
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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'API接口配置表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of config_api
-- ----------------------------

-- ----------------------------
-- Table structure for config_cloudexport
-- ----------------------------
DROP TABLE IF EXISTS `config_cloudexport`;
CREATE TABLE `config_cloudexport`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL DEFAULT 0 COMMENT '产品ID，0=按渠道导出',
  `api_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '渠道名称（自营/上游名）',
  `self_channel_id` int(11) NOT NULL DEFAULT 0 COMMENT '自营渠道ID（非自营为0）',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `export_fields` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '导出字段，逗号分隔',
  `export_column_map` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '字段到列号映射JSON',
  `create_time` datetime NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_product_channel`(`product_id`, `api_name`, `self_channel_id`) USING BTREE,
  INDEX `idx_product_id`(`product_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of config_cloudexport
-- ----------------------------
INSERT INTO `config_cloudexport` VALUES (1, 0, '', 0, '金山文档', 'order_no,order_create_time,product_name,customer_name,phone,idcard,address,id_card_photos,photo_reupload_count,custom_order_fields,production_number,iccid,puk', '{\"order_no\":\"订单号\",\"order_create_time\":\"订单创建时间\",\"product_name\":\"产品名称\",\"customer_name\":\"姓名\",\"phone\":\"电话号码\",\"idcard\":\"身份证号\",\"address\":\"收货地址\",\"id_card_photos\":\"照片\",\"photo_reupload_count\":\"照片重传次数\",\"custom_order_fields\":\"自定义表单\",\"production_number\":\"生产号码\",\"iccid\":\"ICCID\",\"puk\":\"PUK\",\"__sync_order_no\":\"订单号\",\"__sync_production_number\":\"生产号码\",\"__sync_iccid\":\"ICCID\",\"__sync_puk\":\"PUK\",\"__sync_express_company\":\"快递公司\",\"__sync_tracking_number\":\"快递单号\",\"__sync_remark\":\"备注\",\"__sync_order_status\":\"订单状态\",\"__sync_fulfillment_status\":\"订单状态\",\"__sync_activation_status\":\"激活状态\",\"__sync_settlement_status\":\"结算状态\",\"__push_webhook_url\":\"https://www.kdocs.cn/chatflow/ap***\",\"__callback_trigger_webhook_url\":\"https://www.kdocs.cn/chatflow/ap***\",\"__export_mode\":\"all\",\"__sync_status_map_0\":\"已提交\",\"__sync_status_map_1\":\"待发货\",\"__sync_status_map_2\":\"已发货\",\"__sync_status_map_3\":\"待传照片\",\"__sync_status_map_7\":\"审核失败\",\"__table_name\":\"数据表\",\"__sheet_name\":\"表格数据\",\"__callback_cron_enabled\":1,\"__callback_cron_interval\":1,\"__callback_cron_batch_size\":50,\"__callback_cron_last_time\":\"2026-06-09 11:14:41\",\"__callback_cron_last_result\":\"成功\"}', '2026-03-26 22:31:19', '2026-06-25 14:47:14');

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
) ENGINE = InnoDB AUTO_INCREMENT = 26 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'H5配置表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of config_h5
-- ----------------------------
INSERT INTO `config_h5` VALUES (1, 'banner_images', '[\"/uploads/h5/20250930120656_68db5760e2195.png\"]', 'images', '轮播图片', '首页顶部轮播图片，支持上传多张图片，拖拽调整顺序', 'banner', 10, 1, '2025-09-30 11:57:31', '2025-12-12 20:30:39');
INSERT INTO `config_h5` VALUES (2, 'banner_links', '[\"\"]', 'json', '图片链接', '与轮播图片一一对应的跳转链接，格式：[\"链接1\",\"链接2\"]，不需要跳转可留空\"\"', 'banner', 20, 1, '2025-09-30 11:57:31', '2025-12-12 20:30:39');
INSERT INTO `config_h5` VALUES (3, 'banner_interval', '3000', 'number', '轮播间隔', '轮播图切换间隔时间（毫秒），建议3000-5000', 'banner', 30, 1, '2025-09-30 11:57:31', '2025-12-12 20:30:39');
INSERT INTO `config_h5` VALUES (4, 'banner_autoplay', '1', 'switch', '自动轮播', '是否自动播放轮播图：1开启 0关闭', 'banner', 40, 1, '2025-09-30 11:57:31', '2025-12-12 20:30:39');
INSERT INTO `config_h5` VALUES (5, 'online_service_url', '', 'text', '在线客服链接', '移动端消息页面的在线客服跳转链接', 'basic', 10, 1, '2025-10-01 01:06:19', '2026-03-20 17:18:20');
INSERT INTO `config_h5` VALUES (10, 'product_template', 'product-v1', 'radio', '产品页模板', '兼容旧配置：产品页面使用的模板版本', 'template', 1, 1, '2025-12-25 11:52:45', '2026-06-25 14:47:48');
INSERT INTO `config_h5` VALUES (11, 'order_template', 'order-v1', 'radio', '订单页模板', '选择订单页面使用的模板版本', 'template', 1, 1, '2025-12-25 11:52:45', '2026-06-25 14:47:48');
INSERT INTO `config_h5` VALUES (12, 'wechat_login_enabled', '0', 'radio', '微信登录开关', '控制移动端微信登录功能的开启状态', 'template', 1, 1, '2025-12-25 11:52:49', '2026-06-25 14:48:09');
INSERT INTO `config_h5` VALUES (13, 'qq_login_enabled', '0', 'radio', 'QQ登录开关', '控制移动端QQ登录功能的开启状态', 'template', 1, 1, '2025-12-25 11:52:49', '2026-06-25 14:48:09');
INSERT INTO `config_h5` VALUES (14, 'ai_api_key', '', 'text', 'AI API Key', '大模型API密钥', 'ai', 1, 1, '2026-02-08 23:02:08', '2026-06-25 14:48:30');
INSERT INTO `config_h5` VALUES (15, 'ai_api_url', 'https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions', 'text', 'AI API地址', '大模型API请求地址', 'ai', 1, 1, '2026-02-08 23:02:08', '2026-02-08 23:03:59');
INSERT INTO `config_h5` VALUES (16, 'ai_model', 'deepseek-v3.2', 'text', 'AI模型名称', '使用的大模型名称', 'ai', 1, 1, '2026-02-08 23:02:08', '2026-02-08 23:03:59');
INSERT INTO `config_h5` VALUES (17, 'custom_menus', '[]', 'textarea', '自定义菜单', '个人中心页面的自定义菜单配置', 'menu', 1, 1, '2026-03-08 14:20:16', '2026-06-25 14:48:18');
INSERT INTO `config_h5` VALUES (18, 'h5_client_version', 'v1', 'radio', '手机端版本', '代理手机端使用的版本（v1/v2）', 'template', 1, 1, '2026-03-19 12:10:49', '2026-06-25 14:47:48');
INSERT INTO `config_h5` VALUES (19, 'shop_index_template', 'index1', 'radio', '店铺首页模板', '店铺首页使用的模板版本', 'template', 41, 1, '2026-03-21 23:17:55', '2026-06-25 14:47:48');
INSERT INTO `config_h5` VALUES (20, 'shop_product_template', 'product1', 'radio', '店铺详情模板', '店铺详情/下单页使用的模板版本', 'template', 42, 1, '2026-03-21 23:17:55', '2026-06-25 14:47:48');
INSERT INTO `config_h5` VALUES (21, 'h5_login_top_image', '', 'text', '登录页顶图', '手机端登录页顶部图片', 'basic', 1, 1, '2026-03-28 15:13:42', '2026-06-25 14:48:47');
INSERT INTO `config_h5` VALUES (22, 'h5_login_title_line1', '', 'text', '登录页首行文案', '手机端登录页顶部首行文案', 'basic', 1, 1, '2026-03-28 15:13:42', '2026-06-25 14:48:47');
INSERT INTO `config_h5` VALUES (23, 'h5_login_title_line2', '', 'text', '登录页次行文案', '手机端登录页顶部次行文案', 'basic', 1, 1, '2026-03-28 15:13:42', '2026-06-25 14:48:47');
INSERT INTO `config_h5` VALUES (24, 'h5_login_title_align', 'left', 'radio', '登录页文案对齐', '手机端登录页顶部文案对齐方式', 'basic', 1, 1, '2026-03-28 15:13:42', '2026-06-25 14:48:47');
INSERT INTO `config_h5` VALUES (25, 'shop_template', 'template1', 'radio', '店铺模板', '店铺首页、详情/下单页和公共页面统一使用的模板套系', 'template', 43, 1, '2026-06-10 10:50:17', '2026-06-25 14:47:48');

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
INSERT INTO `config_oss` VALUES (1, 'local', 0, 0, '本地存储', '[]', 'uploads', 5, '[\"jpg\",\"jpeg\",\"png\",\"gif\",\"bmp\",\"webp\",\"pdf\",\"doc\",\"docx\",\"xls\",\"xlsx\",\"zip\",\"rar\"]', 1757389004, 1782370191);
INSERT INTO `config_oss` VALUES (2, 'tencent', 0, 0, '腾讯云COS', '{\"secret_id\":\"\",\"secret_key\":\"\",\"region\":\"ap-shanghai\",\"bucket\":\"\",\"domain\":\"\",\"source_domain\":\"\",\"cdn_domain\":\"\"}', 'uploads', 5, '[\"jpg\",\"jpeg\",\"png\",\"gif\",\"bmp\",\"webp\",\"pdf\",\"doc\",\"docx\",\"xls\",\"xlsx\",\"zip\",\"rar\"]', 1757389004, 1782370191);
INSERT INTO `config_oss` VALUES (3, 'aliyun', 0, 0, '阿里云OSS', '{\"access_key_id\":\"\",\"access_key_secret\":\"\",\"endpoint\":\"\",\"bucket\":\"\",\"domain\":\"\"}', 'uploads', 5, '[\"jpg\",\"jpeg\",\"png\",\"gif\",\"bmp\",\"webp\",\"pdf\",\"doc\",\"docx\",\"xls\",\"xlsx\",\"zip\",\"rar\"]', 1757389004, 1782370191);

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
  `verify_template_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '验证码模板ID',
  `notice_template_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '通知模板ID',
  `verify_sign_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '验证码签名',
  `notice_sign_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '通知签名',
  `tencent_secret_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '腾讯云SecretId',
  `tencent_secret_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '腾讯云SecretKey',
  `tencent_sdk_app_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '腾讯云短信SdkAppId',
  `tencent_region` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'ap-guangzhou' COMMENT '腾讯云地域',
  `aliyun_access_key_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '阿里云AccessKeyId',
  `aliyun_access_key_secret` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '阿里云AccessKeySecret',
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
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '短信配置表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of config_sms
-- ----------------------------
INSERT INTO `config_sms` VALUES (2, '望为云短信', 'wangweiyun', '111', 'WW_023568aofkiexjymng', 'WW_023568aofkiexjymng', '', '', '', '', '', '', '', '', '', 'https://wwsms.market.alicloudapi.com/send_sms', '', 1, 1, 1775997583, 1782370405);

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
INSERT INTO `content_categories` VALUES (1, '系统公告', '系统相关的重要公告', 'announcement', 5, 1, 1757563322, 1757572426);
INSERT INTO `content_categories` VALUES (2, '活动公告', '各类活动相关公告', 'announcement', 2, 1, 1757563322, 1757563322);
INSERT INTO `content_categories` VALUES (3, '维护公告', '系统维护相关公告', 'announcement', 3, 1, 1757563322, 1757563322);
INSERT INTO `content_categories` VALUES (4, '使用教程', '系统使用相关教程', 'article', 1, 1, 1757563322, 1757563322);
INSERT INTO `content_categories` VALUES (5, '常见问题', '常见问题解答', 'article', 2, 1, 1757563322, 1757563322);
INSERT INTO `content_categories` VALUES (6, '政策说明', '相关政策说明文档', 'article', 3, 1, 1757563322, 1757563322);

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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '内容阅读记录表' ROW_FORMAT = DYNAMIC;

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
  `popup_interval_hours` int(11) NULL DEFAULT 24 COMMENT '弹窗再次展示间隔小时（仅公告）',
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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '内容表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of contents
-- ----------------------------

-- ----------------------------
-- Table structure for distribution_level
-- ----------------------------
DROP TABLE IF EXISTS `distribution_level`;
CREATE TABLE `distribution_level`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '等级ID',
  `level_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '等级名称',
  `level_order` int(11) NOT NULL DEFAULT 1 COMMENT '等级序号，值越小级别越高',
  `commission` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '上级对该等级的固定抽佣金额',
  `commission_type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '抽佣方式:0固定金额,1百分比',
  `logo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '等级logo',
  `bg_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '等级背景图',
  `text_color` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '#3F516D' COMMENT '卡片文字颜色',
  `agent_data_scope` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'direct' COMMENT '代理后台数据可视范围:direct直属下级,all_descendants全部下级',
  `agent_scope_label` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '代理后台权限标签',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0禁用，1启用',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '备注',
  `create_time` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_level_order`(`level_order`) USING BTREE,
  INDEX `idx_status_order`(`status`, `level_order`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '固定分销等级表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of distribution_level
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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '员工组成员表' ROW_FORMAT = DYNAMIC;

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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '员工组表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of employee_groups
-- ----------------------------

-- ----------------------------
-- Table structure for employee_salary_settlement_logs
-- ----------------------------
DROP TABLE IF EXISTS `employee_salary_settlement_logs`;
CREATE TABLE `employee_salary_settlement_logs`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `group_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '员工组ID',
  `group_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '员工组名称快照',
  `agent_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '代理ID',
  `agent_username` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '代理账号快照',
  `employee_code` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '员工号快照',
  `settlement_month` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '结算月份 YYYY-MM',
  `settled_amount` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '本次结算金额',
  `balance_before` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '扣减前余额',
  `balance_after` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '扣减后余额',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态:1成功',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `operator_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作员ID',
  `operator_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '操作员名称',
  `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_group_month`(`group_id`, `settlement_month`) USING BTREE,
  INDEX `idx_agent_month`(`agent_id`, `settlement_month`) USING BTREE,
  INDEX `idx_create_time`(`create_time`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '员工线下结算日志' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of employee_salary_settlement_logs
-- ----------------------------

-- ----------------------------
-- Table structure for fadada_sign_records
-- ----------------------------
DROP TABLE IF EXISTS `fadada_sign_records`;
CREATE TABLE `fadada_sign_records`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agent_id` int(11) NOT NULL DEFAULT 0 COMMENT '代理ID',
  `agent_name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '姓名',
  `id_card` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '证件号',
  `mobile` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '手机号',
  `amount` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '支付金额',
  `order_no` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '支付订单号',
  `payment_record_id` int(11) NOT NULL DEFAULT 0 COMMENT '支付记录ID',
  `pay_channel` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '支付渠道',
  `pay_mode` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '支付模式',
  `pay_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '支付状态 0待支付 1已支付',
  `sign_status` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending_payment' COMMENT '签约状态 pending_payment/pending_review/approved/rejected',
  `prompt_position` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'register' COMMENT '提示位置 register/withdraw',
  `description_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '签约说明快照',
  `qrcode_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '二维码内容快照',
  `access_token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '签约访问凭证',
  `access_token_expire_time` int(11) NOT NULL DEFAULT 0 COMMENT '签约凭证过期时间',
  `review_admin_id` int(11) NOT NULL DEFAULT 0 COMMENT '审核管理员ID',
  `review_time` int(11) NOT NULL DEFAULT 0 COMMENT '审核时间',
  `reject_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '驳回原因',
  `create_time` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_order_no`(`order_no`) USING BTREE,
  INDEX `idx_agent_id`(`agent_id`) USING BTREE,
  INDEX `idx_pay_status`(`pay_status`) USING BTREE,
  INDEX `idx_sign_status`(`sign_status`) USING BTREE,
  INDEX `idx_access_token`(`access_token`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '法大大签约记录表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of fadada_sign_records
-- ----------------------------

-- ----------------------------
-- Table structure for image_template
-- ----------------------------
DROP TABLE IF EXISTS `image_template`;
CREATE TABLE `image_template`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '模板名称',
  `product_type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'flow_card' COMMENT '适用产品类型',
  `category_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '适用产品分类ID，0为该类型全部分类',
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
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '商品图片模板表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of image_template
-- ----------------------------
INSERT INTO `image_template` VALUES (1, '预设模板-1', 'flow_card', 0, '/uploads/product/2026/02/06/165811_6985ad23680b0.png', '/uploads/product/2026/02/06/165553_6985ac99b9103.png', '/uploads/product/2026/02/06/165557_6985ac9d1b255.png', '/uploads/product/2026/02/06/165600_6985aca039b56.png', '{\"customTexts\": [{\"x\": 443, \"y\": 328, \"id\": 1, \"bold\": 1, \"text\": \"元/月\", \"align\": \"center\", \"color\": \"#2d2f2e\", \"field\": \"\", \"fontSize\": 36, \"fontFamily\": \"msyh\"}, {\"x\": 78, \"y\": 423, \"id\": 2, \"bold\": 1, \"text\": \"\", \"align\": \"center\", \"color\": \"#2d2f38\", \"field\": \"{tags}\", \"fontSize\": 25, \"fontFamily\": \"msyh\"}, {\"x\": 76, \"y\": 466, \"id\": 3, \"bold\": 1, \"text\": \"官方发卡，极速发货，支持4G/5G\", \"align\": \"center\", \"color\": \"#2d2f2e\", \"field\": \"\", \"fontSize\": 25, \"fontFamily\": \"msyh\"}, {\"x\": 157, \"y\": 753, \"id\": 4, \"bold\": 0, \"text\": \"*该流量卡由\", \"align\": \"center\", \"color\": \"#ffffff\", \"field\": \"\", \"fontSize\": 20, \"fontFamily\": \"msyh\"}, {\"x\": 371, \"y\": 753, \"id\": 5, \"bold\": 0, \"text\": \"提供服务，套餐详情请见落地页\", \"align\": \"center\", \"color\": \"#ffffff\", \"field\": \"\", \"fontSize\": 20, \"fontFamily\": \"msyh\"}, {\"x\": 272, \"y\": 753, \"id\": 6, \"bold\": 1, \"text\": \"\", \"align\": \"center\", \"color\": \"#ffffff\", \"field\": \"{yys}\", \"fontSize\": 22, \"fontFamily\": \"msyh\"}, {\"x\": 216, \"y\": 293, \"id\": 7, \"bold\": 1, \"text\": \"G\", \"align\": \"center\", \"color\": \"#2d2f2e\", \"field\": \"\", \"fontSize\": 80, \"fontFamily\": \"msyh\"}]}', '{\"customTexts\": [{\"x\": 443, \"y\": 328, \"id\": 1, \"bold\": 1, \"text\": \"元/月\", \"align\": \"center\", \"color\": \"#2d2f2e\", \"field\": \"\", \"fontSize\": 36, \"fontFamily\": \"msyh\"}, {\"x\": 78, \"y\": 423, \"id\": 2, \"bold\": 1, \"text\": \"\", \"align\": \"center\", \"color\": \"#2d2f38\", \"field\": \"{tags}\", \"fontSize\": 25, \"fontFamily\": \"msyh\"}, {\"x\": 76, \"y\": 466, \"id\": 3, \"bold\": 1, \"text\": \"官方发卡，极速发货，支持4G/5G\", \"align\": \"center\", \"color\": \"#2d2f2e\", \"field\": \"\", \"fontSize\": 25, \"fontFamily\": \"msyh\"}, {\"x\": 157, \"y\": 753, \"id\": 4, \"bold\": 0, \"text\": \"*该流量卡由\", \"align\": \"center\", \"color\": \"#ffffff\", \"field\": \"\", \"fontSize\": 20, \"fontFamily\": \"msyh\"}, {\"x\": 371, \"y\": 753, \"id\": 5, \"bold\": 0, \"text\": \"提供服务，套餐详情请见落地页\", \"align\": \"center\", \"color\": \"#ffffff\", \"field\": \"\", \"fontSize\": 20, \"fontFamily\": \"msyh\"}, {\"x\": 272, \"y\": 753, \"id\": 6, \"bold\": 1, \"text\": \"\", \"align\": \"center\", \"color\": \"#ffffff\", \"field\": \"{yys}\", \"fontSize\": 22, \"fontFamily\": \"msyh\"}, {\"x\": 216, \"y\": 293, \"id\": 7, \"bold\": 1, \"text\": \"G\", \"align\": \"center\", \"color\": \"#2d2f2e\", \"field\": \"\", \"fontSize\": 80, \"fontFamily\": \"msyh\"}]}', '{\"customTexts\": [{\"x\": 443, \"y\": 328, \"id\": 1, \"bold\": 1, \"text\": \"元/月\", \"align\": \"center\", \"color\": \"#ffffff\", \"field\": \"\", \"fontSize\": 36, \"fontFamily\": \"msyh\"}, {\"x\": 78, \"y\": 423, \"id\": 2, \"bold\": 1, \"text\": \"\", \"align\": \"center\", \"color\": \"#ffffff\", \"field\": \"{tags}\", \"fontSize\": 25, \"fontFamily\": \"msyh\"}, {\"x\": 76, \"y\": 466, \"id\": 3, \"bold\": 1, \"text\": \"官方发卡，极速发货，支持4G/5G\", \"align\": \"center\", \"color\": \"#ffffff\", \"field\": \"\", \"fontSize\": 25, \"fontFamily\": \"msyh\"}, {\"x\": 157, \"y\": 753, \"id\": 4, \"bold\": 0, \"text\": \"*该流量卡由\", \"align\": \"center\", \"color\": \"#ffffff\", \"field\": \"\", \"fontSize\": 20, \"fontFamily\": \"msyh\"}, {\"x\": 371, \"y\": 753, \"id\": 5, \"bold\": 0, \"text\": \"提供服务，套餐详情请见落地页\", \"align\": \"center\", \"color\": \"#ffffff\", \"field\": \"\", \"fontSize\": 20, \"fontFamily\": \"msyh\"}, {\"x\": 272, \"y\": 753, \"id\": 6, \"bold\": 1, \"text\": \"\", \"align\": \"center\", \"color\": \"#ffffff\", \"field\": \"{yys}\", \"fontSize\": 22, \"fontFamily\": \"msyh\"}, {\"x\": 216, \"y\": 293, \"id\": 7, \"bold\": 1, \"text\": \"G\", \"align\": \"center\", \"color\": \"#2d2f2e\", \"field\": \"\", \"fontSize\": 80, \"fontFamily\": \"msyh\"}]}', '{\"customTexts\": [{\"x\": 443, \"y\": 328, \"id\": 1, \"bold\": 1, \"text\": \"元/月\", \"align\": \"center\", \"color\": \"#2d2f2e\", \"field\": \"\", \"fontSize\": 36, \"fontFamily\": \"msyh\"}, {\"x\": 78, \"y\": 423, \"id\": 2, \"bold\": 1, \"text\": \"\", \"align\": \"center\", \"color\": \"#2d2f38\", \"field\": \"{tags}\", \"fontSize\": 25, \"fontFamily\": \"msyh\"}, {\"x\": 76, \"y\": 466, \"id\": 3, \"bold\": 1, \"text\": \"官方发卡，极速发货，支持4G/5G\", \"align\": \"center\", \"color\": \"#2d2f2e\", \"field\": \"\", \"fontSize\": 25, \"fontFamily\": \"msyh\"}, {\"x\": 157, \"y\": 753, \"id\": 4, \"bold\": 0, \"text\": \"*该流量卡由\", \"align\": \"center\", \"color\": \"#ffffff\", \"field\": \"\", \"fontSize\": 20, \"fontFamily\": \"msyh\"}, {\"x\": 371, \"y\": 753, \"id\": 5, \"bold\": 0, \"text\": \"提供服务，套餐详情请见落地页\", \"align\": \"center\", \"color\": \"#ffffff\", \"field\": \"\", \"fontSize\": 20, \"fontFamily\": \"msyh\"}, {\"x\": 272, \"y\": 753, \"id\": 6, \"bold\": 1, \"text\": \"\", \"align\": \"center\", \"color\": \"#ffffff\", \"field\": \"{yys}\", \"fontSize\": 22, \"fontFamily\": \"msyh\"}, {\"x\": 216, \"y\": 293, \"id\": 7, \"bold\": 1, \"text\": \"G\", \"align\": \"center\", \"color\": \"#2d2f2e\", \"field\": \"\", \"fontSize\": 80, \"fontFamily\": \"msyh\"}]}', 1, 1, 0, 1, '2026-02-05 22:24:38', '2026-06-25 14:55:35');
INSERT INTO `image_template` VALUES (2, '预设模板-2', 'flow_card', 0, '/uploads/product/2026/02/06/165609_6985aca9c0542.png', '/uploads/product/2026/02/06/165613_6985acad58990.png', '/uploads/product/2026/02/06/165616_6985acb0e0831.png', '/uploads/product/2026/02/06/165621_6985acb539046.png', '{\"flow\": {\"x\": 52, \"y\": 247, \"bold\": 1, \"color\": \"#2d2f2e\", \"fontSize\": 80}, \"yuezu\": {\"x\": 285, \"y\": 254, \"bold\": 1, \"color\": \"#2d2f2e\", \"fontSize\": 80}, \"customTexts\": [{\"x\": 91, \"y\": 402, \"id\": 2, \"bold\": 1, \"text\": \"\", \"color\": \"#2d2f38\", \"field\": \"{tags}\", \"fontSize\": 25}, {\"x\": 91, \"y\": 441, \"id\": 3, \"bold\": 1, \"text\": \"官方发卡，极速发货，支持4G/5G\", \"color\": \"#2d2f2e\", \"field\": \"\", \"fontSize\": 25}]}', '{\"flow\": {\"x\": 52, \"y\": 247, \"bold\": 1, \"color\": \"#2d2f2e\", \"fontSize\": 80}, \"yuezu\": {\"x\": 285, \"y\": 254, \"bold\": 1, \"color\": \"#2d2f2e\", \"fontSize\": 80}, \"customTexts\": [{\"x\": 91, \"y\": 402, \"id\": 2, \"bold\": 1, \"text\": \"\", \"color\": \"#2d2f2e\", \"field\": \"{tags}\", \"fontSize\": 25}, {\"x\": 91, \"y\": 441, \"id\": 3, \"bold\": 1, \"text\": \"官方发卡，极速发货，支持4G/5G\", \"color\": \"#2d2f38\", \"field\": \"\", \"fontSize\": 25}]}', '{\"flow\": {\"x\": 52, \"y\": 247, \"bold\": 1, \"color\": \"#000000\", \"fontSize\": 80}, \"yuezu\": {\"x\": 285, \"y\": 254, \"bold\": 1, \"color\": \"#000000\", \"fontSize\": 80}, \"customTexts\": [{\"x\": 91, \"y\": 402, \"id\": 2, \"bold\": 1, \"text\": \"\", \"color\": \"#000000\", \"field\": \"{tags}\", \"fontSize\": 25}, {\"x\": 91, \"y\": 441, \"id\": 3, \"bold\": 1, \"text\": \"官方发卡，极速发货，支持4G/5G\", \"color\": \"#000000\", \"field\": \"\", \"fontSize\": 25}]}', '{\"flow\": {\"x\": 52, \"y\": 247, \"bold\": 1, \"color\": \"#2d2f2e\", \"fontSize\": 80}, \"yuezu\": {\"x\": 285, \"y\": 254, \"bold\": 1, \"color\": \"#2d2f2e\", \"fontSize\": 80}, \"customTexts\": [{\"x\": 91, \"y\": 402, \"id\": 2, \"bold\": 1, \"text\": \"\", \"color\": \"#2d2f2e\", \"field\": \"{tags}\", \"fontSize\": 25}, {\"x\": 91, \"y\": 441, \"id\": 3, \"bold\": 1, \"text\": \"官方发卡，极速发货，支持4G/5G\", \"color\": \"#2d2f38\", \"field\": \"\", \"fontSize\": 25}]}', 1, 0, 0, 1, '2026-02-06 16:56:32', '2026-03-29 13:22:00');

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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '邀请码等级表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of invite_code
-- ----------------------------

-- ----------------------------
-- Table structure for invite_code_fixed
-- ----------------------------
DROP TABLE IF EXISTS `invite_code_fixed`;
CREATE TABLE `invite_code_fixed`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `agent_id` int(11) UNSIGNED NOT NULL COMMENT '上级代理ID',
  `distribution_level_id` int(11) UNSIGNED NOT NULL COMMENT '下级等级ID',
  `invite_code` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '固定模式邀请码(手动设置)',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1启用 0禁用',
  `create_time` datetime NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_invite_code`(`invite_code`) USING BTREE,
  UNIQUE INDEX `uk_agent_level`(`agent_id`, `distribution_level_id`) USING BTREE,
  INDEX `idx_agent_id`(`agent_id`) USING BTREE,
  INDEX `idx_level_id`(`distribution_level_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '固定分销模式邀请码' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of invite_code_fixed
-- ----------------------------

-- ----------------------------
-- Table structure for invite_code_reserved
-- ----------------------------
DROP TABLE IF EXISTS `invite_code_reserved`;
CREATE TABLE `invite_code_reserved`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `invite_code` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `create_admin_id` int(11) NOT NULL DEFAULT 0,
  `update_admin_id` int(11) NOT NULL DEFAULT 0,
  `create_time` datetime NULL DEFAULT NULL,
  `update_time` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_invite_code`(`invite_code`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of invite_code_reserved
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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '站内信表' ROW_FORMAT = DYNAMIC;

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
  `self_channel_id` int(11) NOT NULL DEFAULT 0 COMMENT '自营渠道ID快照，创建订单时写入',
  `shop_code` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '店铺代码',
  `product_id` int(11) NULL DEFAULT NULL COMMENT '对接产品ID',
  `product_name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '商品名称',
  `product_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '产品图片路径',
  `fulfillment_status` tinyint(3) UNSIGNED NOT NULL DEFAULT 2 COMMENT '订单状态：0待支付,1支付超时,2已提交,3初步审核,9信息待补充,4待发货,5已发货,6待传照片,7新照待审核,8审核失败,100订单已完成,101订单已取消',
  `activation_status` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '激活状态：0未激活,1已激活,2激活且充值',
  `settlement_status` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '结算状态：0未结算,1待结算,2已结算,3拒绝结算,4佣金追溯',
  `refund_status` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '退款状态：0未申请退款,1退款中,2已退款,3拒绝退款',
  `express_company` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '物流公司',
  `tracking_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '物流单号',
  `customer_name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '客户姓名',
  `phone` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '电话号码',
  `idcard` varchar(18) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '身份证号码',
  `province` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '省份',
  `city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '城市',
  `district` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '区县',
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '详细地址',
  `custom_order_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '下单自定义字段JSON',
  `submit_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '下单IP',
  `submit_ip_location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'IP定位',
  `submit_ip_order_count` int(11) NOT NULL DEFAULT 0 COMMENT '同IP下单数',
  `idcard_native_place` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '身份证籍贯',
  `idcard_gender` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '身份证性别',
  `idcard_age` int(11) NOT NULL DEFAULT 0 COMMENT '身份证年龄',
  `product_guishudi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '产品归属地',
  `security_check_status` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '安全校验状态',
  `puk` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'PUK码',
  `submit_sms_notified` tinyint(1) NOT NULL DEFAULT 0 COMMENT '订单提交短信已通知',
  `submit_sms_notice_time` datetime NULL DEFAULT NULL COMMENT '订单提交短信通知时间',
  `pending_ship_sms_notified` tinyint(1) NOT NULL DEFAULT 0 COMMENT '待发货短信已通知',
  `pending_ship_sms_notice_time` datetime NULL DEFAULT NULL COMMENT '待发货短信通知时间',
  `ship_sms_notified` tinyint(1) NOT NULL DEFAULT 0 COMMENT '发货短信已通知',
  `ship_sms_notice_time` datetime NULL DEFAULT NULL COMMENT '发货短信通知时间',
  `review_failed_sms_notified` tinyint(1) NOT NULL DEFAULT 0 COMMENT '审核失败短信是否已发送',
  `review_failed_sms_notice_time` datetime NULL DEFAULT NULL COMMENT '审核失败短信发送时间',
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
  `iccid` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'ICCID',
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
  `api_pay_amount` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'API预存实际扣费金额',
  `api_pay_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'API预存支付状态：0未扣费 1已扣费 2已退款',
  `api_pay_time` int(11) NULL DEFAULT NULL COMMENT 'API预存扣费时间戳',
  `api_refund_time` datetime NULL DEFAULT NULL COMMENT 'API预存退款时间',
  `api_pay_log_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'API预存扣费流水ID',
  `api_refund_log_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'API预存退款流水ID',
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
  INDEX `idx_fulfillment_status`(`fulfillment_status`) USING BTREE,
  INDEX `idx_activation_status`(`activation_status`) USING BTREE,
  INDEX `idx_settlement_status`(`settlement_status`) USING BTREE,
  INDEX `idx_refund_status`(`refund_status`) USING BTREE,
  INDEX `idx_order_state_combo`(`fulfillment_status`, `activation_status`, `settlement_status`, `refund_status`, `create_time`) USING BTREE,
  INDEX `idx_create_time_status`(`create_time`, `fulfillment_status`, `activation_status`, `settlement_status`) USING BTREE,
  INDEX `idx_status_create_time`(`fulfillment_status`, `activation_status`, `settlement_status`, `create_time`) USING BTREE,
  INDEX `idx_customer_name`(`customer_name`) USING BTREE,
  INDEX `idx_phone`(`phone`) USING BTREE,
  INDEX `idx_shop_code_create_time`(`shop_code`, `create_time`) USING BTREE,
  INDEX `idx_agent_create_time`(`agent_id`, `create_time`) USING BTREE,
  INDEX `idx_order_no_prefix`(`order_no`(20)) USING BTREE,
  INDEX `idx_multi_filter`(`fulfillment_status`, `activation_status`, `settlement_status`, `shop_code`, `create_time`) USING BTREE,
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
  INDEX `idx_card_type`(`card_type`) USING BTREE,
  INDEX `idx_self_channel_id`(`self_channel_id`) USING BTREE,
  INDEX `idx_iccid`(`iccid`) USING BTREE,
  INDEX `idx_api_pay_status`(`api_pay_status`) USING BTREE,
  INDEX `idx_api_pay_log_id`(`api_pay_log_id`) USING BTREE,
  INDEX `idx_order_limit_name`(`customer_name`, `product_id`, `fulfillment_status`, `card_type`, `pay_status`) USING BTREE,
  INDEX `idx_order_limit_phone`(`phone`, `product_id`, `fulfillment_status`, `card_type`, `pay_status`) USING BTREE,
  INDEX `idx_order_limit_idcard`(`idcard`, `product_id`, `fulfillment_status`, `card_type`, `pay_status`) USING BTREE,
  INDEX `idx_order_limit_production`(`product_id`, `production_number`, `fulfillment_status`, `card_type`, `pay_status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '订单表' ROW_FORMAT = DYNAMIC;

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
  `remark` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '批次备注',
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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '订单批量操作批次表' ROW_FORMAT = DYNAMIC;

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
  `old_fulfillment_status` tinyint(3) NULL DEFAULT NULL COMMENT '原订单状态',
  `new_fulfillment_status` tinyint(3) NULL DEFAULT NULL COMMENT '新订单状态',
  `old_activation_status` tinyint(3) NULL DEFAULT NULL COMMENT '原激活状态',
  `new_activation_status` tinyint(3) NULL DEFAULT NULL COMMENT '新激活状态',
  `old_settlement_status` tinyint(3) NULL DEFAULT NULL COMMENT '原结算状态',
  `new_settlement_status` tinyint(3) NULL DEFAULT NULL COMMENT '新结算状态',
  `old_jh_time` datetime NULL DEFAULT NULL COMMENT '原激活时间',
  `new_jh_time` datetime NULL DEFAULT NULL COMMENT '本批次写入激活时间',
  `old_js_time` datetime NULL DEFAULT NULL COMMENT '原结算时间',
  `new_js_time` datetime NULL DEFAULT NULL COMMENT '本批次写入结算时间',
  `old_recharge_status` varchar(20) NULL DEFAULT NULL COMMENT '原充值状态',
  `old_recharge_amount` decimal(10,2) NULL DEFAULT NULL COMMENT '原充值金额',
  `new_recharge_amount` decimal(10,2) NULL DEFAULT NULL COMMENT '新充值金额',
  `commission_log_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '本批次生成或结算的佣金流水ID',
  `old_remark` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '原备注',
  `new_remark` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '新备注',
  `old_production_number` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '原生产号码',
  `new_production_number` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '新生产号码',
  `old_express_company` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '原物流公司',
  `new_express_company` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '新物流公司',
  `old_tracking_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '原物流单号',
  `new_tracking_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '新物流单号',
  `execute_status` tinyint(1) NULL DEFAULT 0 COMMENT '执行状态(0-待处理,1-成功,2-失败,3-已撤回)',
  `fail_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '失败原因',
  `execute_time` datetime NULL DEFAULT NULL COMMENT '执行时间',
  `rollback_time` datetime NULL DEFAULT NULL COMMENT '撤回时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_batch_id`(`batch_id`) USING BTREE,
  INDEX `idx_batch_no`(`batch_no`) USING BTREE,
  INDEX `idx_order_no`(`order_no`) USING BTREE,
  INDEX `idx_execute_status`(`execute_status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '订单批量操作明细表' ROW_FORMAT = DYNAMIC;

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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '订单照片历史记录表' ROW_FORMAT = DYNAMIC;

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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'API调用日志表' ROW_FORMAT = DYNAMIC;

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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '回调队列表' ROW_FORMAT = DYNAMIC;

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
) ENGINE = InnoDB AUTO_INCREMENT = 55 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '支付配置详情表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of payment_configs
-- ----------------------------
INSERT INTO `payment_configs` VALUES (1, 'wechat', 'pay_mode', 'native,jsapi', 'string', 1, '支付模式：jsapi/h5/native', 1766405472, 1782370696);
INSERT INTO `payment_configs` VALUES (2, 'wechat', 'appid', '', 'string', 1, '公众号AppID（JSAPI模式需要）', 1766405472, 1782370696);
INSERT INTO `payment_configs` VALUES (3, 'wechat', 'app_secret', '', 'string', 0, '公众号AppSecret', 1766405472, 1782370696);
INSERT INTO `payment_configs` VALUES (4, 'wechat', 'mchid', '', 'string', 1, '商户号', 1766405472, 1782370696);
INSERT INTO `payment_configs` VALUES (5, 'wechat', 'api_key', '', 'string', 1, 'APIv2密钥（32位）', 1766405472, 1782370696);
INSERT INTO `payment_configs` VALUES (6, 'wechat', 'notify_url', 'http://t32x4czs.beesnat.com/index/pay/notify/wechat', 'string', 1, '支付回调地址', 1766405472, 1782370696);
INSERT INTO `payment_configs` VALUES (7, 'wechat', 'auth_domain', 'http://t32x4czs.beesnat.com', 'string', 0, '微信授权域名（用于JSAPI授权回调）', 1766405472, 1782370696);
INSERT INTO `payment_configs` VALUES (13, 'alipay', 'app_id', '', 'string', 1, '支付宝应用ID', 1766405472, 1782370704);
INSERT INTO `payment_configs` VALUES (14, 'alipay', 'private_key', '', 'string', 1, '应用私钥', 1766405472, 1782370704);
INSERT INTO `payment_configs` VALUES (16, 'alipay', 'notify_url', 'https://localhost:3006/index/pay/alipayNotify', 'string', 1, '异步回调地址', 1766405472, 1782370704);
INSERT INTO `payment_configs` VALUES (17, 'alipay', 'return_url', 'https://localhost:3006/index/pay/alipayReturn', 'string', 0, '同步返回地址', 1766405472, 1782370704);
INSERT INTO `payment_configs` VALUES (27, 'alipay', 'alipay_public_key', 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAppMoAmkjC+NpUHx22vVjvLHWzliYHRvxxik1OoieDnXTOehS1b66YXDEZSq/Q357BQJL1ImrWTxAnc016oYAQJKkr1DcpU7/lZ1iIeouIycaiqHCWlTSj+WoDREEsqpkxKMrKRdmWp9ATya9i/AqQF4uGl7iVmo7U31UN+OcuMscX3iaZiBpuj1kVPSingTF3sp+Nt9vSkRNxskkvJJRk6Pm5Vga0EpDspwANdY6ckO8oN6wuS4Km3QGQir8D/0rWdTTX4n1OJH2OGCDMH1wVn76mFZmvzYvJSXS7e0SMrF+b6rKN4reCZCJGp4KPKGmYylpGqnYym2CvZVgGCquqwIDAQAB', 'string', 1, '支付宝公钥', 1767494605, 1774708955);
INSERT INTO `payment_configs` VALUES (28, 'alipay', 'pay_mode', 'FACE,WAP,PAGE', 'string', 1, '支付模式: FACE/WAP/PAGE', 1767494605, 1782370704);
INSERT INTO `payment_configs` VALUES (29, 'wechat', 'openid_mode', 'wechat', 'string', 0, 'OpenID获取方式：wechat=公众号，wework=企业微信', 1767586514, 1782370696);
INSERT INTO `payment_configs` VALUES (30, 'wechat', 'wework_corp_id', '', 'string', 1, '企业微信CorpID', 1767586514, 1782370696);
INSERT INTO `payment_configs` VALUES (31, 'wechat', 'wework_corp_secret', '', 'string', 1, '企业微信应用Secret', 1767586514, 1782370696);
INSERT INTO `payment_configs` VALUES (32, 'wechat', 'wework_agent_id', '', 'string', 1, '企业微信应用AgentID', 1767586514, 1782370696);
INSERT INTO `payment_configs` VALUES (33, 'wechat', 'wework_redirect_uri', 'http://t32x4czs.beesnat.com/index/pay/wework_callback', 'string', 0, '企业微信OAuth重定向URI（可选）', 1767586514, 1782370696);
INSERT INTO `payment_configs` VALUES (34, 'wechat', 'file', '', 'text', 0, 'file', 1774711906, 1782370696);
INSERT INTO `payment_configs` VALUES (35, 'wechat', 'ssl_cert_path', '', 'text', 0, 'ssl_cert_path', 1774711906, 1782370696);
INSERT INTO `payment_configs` VALUES (36, 'wechat', 'ssl_key_path', '', 'text', 0, 'ssl_key_path', 1774711906, 1782370696);
INSERT INTO `payment_configs` VALUES (37, 'wechat', 'cert_p12_path', '', 'text', 0, 'cert_p12_path', 1774712677, 1782370696);
INSERT INTO `payment_configs` VALUES (38, 'alipay', 'app_cert_sn', '', 'text', 0, 'app_cert_sn', 1774975155, 1782370704);
INSERT INTO `payment_configs` VALUES (39, 'alipay', 'alipay_root_cert_sn', '687b59193f3f462dd5336e5abf83c5d8_02941eef3187dddf3d3b83462e1dfcf6', 'text', 0, 'alipay_root_cert_sn', 1774975155, 1782370704);
INSERT INTO `payment_configs` VALUES (40, 'alipay', 'file', '', 'text', 0, 'file', 1774975155, 1782370704);
INSERT INTO `payment_configs` VALUES (41, 'alipay', 'app_cert_path', '', 'text', 0, 'app_cert_path', 1774975155, 1782370704);
INSERT INTO `payment_configs` VALUES (42, 'alipay', 'alipay_cert_path', '', 'text', 0, 'alipay_cert_path', 1774975155, 1782370704);
INSERT INTO `payment_configs` VALUES (43, 'alipay', 'alipay_root_cert_path', '', 'text', 0, 'alipay_root_cert_path', 1774975155, 1782370704);
INSERT INTO `payment_configs` VALUES (44, 'wechat', 'wework_enabled', '0', 'switch', 0, '启用企业微信OpenID', 1776919710, 1782370696);
INSERT INTO `payment_configs` VALUES (45, 'wechat', 'serial_no', '', 'text', 0, '证书序列号', 1776919710, 1782370696);
INSERT INTO `payment_configs` VALUES (46, 'epay', 'merchant_id', '', 'text', 1, '商户ID', 1782267351, 1782370715);
INSERT INTO `payment_configs` VALUES (47, 'epay', 'api_url', '', 'text', 1, 'API接口地址', 1782267351, 1782370715);
INSERT INTO `payment_configs` VALUES (48, 'epay', 'notify_url', 'https://t32x4czs.beesnat.com/index/pay/notify/epay', 'text', 0, '异步回调地址', 1782267351, 1782370715);
INSERT INTO `payment_configs` VALUES (49, 'epay', 'return_url', 'https://t32x4czs.beesnat.com/index/pay/success/epay', 'text', 0, '同步返回地址', 1782267351, 1782370715);
INSERT INTO `payment_configs` VALUES (50, 'epay', 'platform_public_key', '', 'textarea', 1, '平台公钥', 1782267351, 1782370715);
INSERT INTO `payment_configs` VALUES (51, 'epay', 'merchant_private_key', '', 'textarea', 1, '商户私钥', 1782267351, 1782370715);
INSERT INTO `payment_configs` VALUES (52, 'epay', 'merchant_public_key', '', 'textarea', 0, '商户公钥', 1782269597, 1782269597);
INSERT INTO `payment_configs` VALUES (53, 'epay', 'wechat_checkout_route', 'off', 'hidden', 0, '微信支付结算通道', 1782281955, 1782282976);
INSERT INTO `payment_configs` VALUES (54, 'epay', 'alipay_checkout_route', 'off', 'hidden', 0, '支付宝支付结算通道', 1782281955, 1782282976);

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
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '支付方式主表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of payment_methods
-- ----------------------------
INSERT INTO `payment_methods` VALUES (1, 'wechat', '微信支付', 0, 1, NULL, '支持JSAPI/H5/Native三种支付模式', 1766405472, 1782282976);
INSERT INTO `payment_methods` VALUES (3, 'alipay', '支付宝', 0, 3, '/static/images/pay/alipay.png', '支付宝官方支付，支持当面付、手机网站支付、电脑网站支付', 1767494605, 1782282976);
INSERT INTO `payment_methods` VALUES (4, 'epay', '易支付', 0, 30, NULL, '第三方聚合支付配置', 1782267351, 1782282976);

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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '支付记录表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of payment_records
-- ----------------------------

-- ----------------------------
-- Table structure for payout_provider_configs
-- ----------------------------
DROP TABLE IF EXISTS `payout_provider_configs`;
CREATE TABLE `payout_provider_configs`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `provider_key` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '渠道标识，如 yun_account',
  `provider_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '渠道名称',
  `is_enabled` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否启用',
  `auto_payout_enabled` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否自动打款',
  `auto_min_amount` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '自动打款最小金额',
  `auto_max_amount` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '自动打款最大金额(0表示不限制)',
  `config_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '配置JSON(商户号/密钥/证书等)',
  `last_balance` decimal(16, 2) NOT NULL DEFAULT 0.00 COMMENT '最近余额缓存',
  `last_balance_time` int(11) NOT NULL DEFAULT 0 COMMENT '余额更新时间戳',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` int(11) NOT NULL DEFAULT 0,
  `update_time` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_provider_key`(`provider_key`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '通用打款渠道配置' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of payout_provider_configs
-- ----------------------------
INSERT INTO `payout_provider_configs` VALUES (1, 'yun_account', '云账户打款', 0, 0, 0.00, 500.00, '{\"dealer_id\":\"\",\"broker_id\":\"\",\"base_url\":\"https:\\/\\/api-service.yunzhanghu.com\\/\",\"private_key_path\":\"\",\"platform_public_key_path\":\"\",\"notify_url\":\"https:\\/\\/t32x4czs.beesnat.com\\/api\\/payout\\/callback\",\"retry_task_enabled\":0,\"retry_max_count\":51,\"retry_cooldown_seconds\":3001,\"auto_channel_wechat\":1,\"auto_channel_alipay\":1,\"auto_channel_bankcard\":1,\"sign_required_wechat\":1,\"sign_required_alipay\":1,\"sign_required_bankcard\":1,\"app_key\":\"\",\"app_des3_key\":\"\",\"app_private_key\":\"\",\"yzh_public_key\":\"\",\"sign_type\":\"sha256\"}', 8.00, 1776078669, '', 1774720630, 1782370792);
INSERT INTO `payout_provider_configs` VALUES (2, 'alipay', '支付宝自动打款', 0, 0, 0.00, 0.00, '{\"retry_task_enabled\":0,\"retry_max_count\":5,\"retry_cooldown_seconds\":300,\"order_title\":\"\",\"transfer_remark\":\"\",\"payee_identity_type\":\"\",\"app_id\":\"\",\"private_key\":\"\",\"app_cert_path\":\"\",\"alipay_cert_path\":\"\",\"alipay_root_cert_path\":\"\"}', 0.00, 0, '', 1776832052, 1782370812);
INSERT INTO `payout_provider_configs` VALUES (3, 'sby', '身边云佣金保', 0, 0, 0.00, 0.00, '{\"base_url\":\"\",\"mer_id\":\"\",\"api_key\":\"\",\"merchant_private_key\":\"\",\"platform_public_key\":\"\",\"api_version\":\"V1.0\",\"encrypt_type\":\"DES\",\"provider_id\":\"\",\"task_id\":\"\",\"appid\":\"\",\"sign_notify_url\":\"\",\"payout_notify_url\":\"\",\"sign_redirect_url\":\"\",\"face_redirect_url\":\"\",\"redirect_button_name\":\"返回\",\"redirect_type\":\"NAVIGATE_BACK\",\"payment_memo\":\"业务服务\",\"face_required\":1,\"id_card_photos_required\":0,\"retry_task_enabled\":1,\"retry_max_count\":5,\"retry_cooldown_seconds\":300,\"auto_channel_wechat\":1,\"auto_channel_alipay\":1,\"auto_channel_bankcard\":1,\"sign_required_wechat\":1,\"sign_required_alipay\":1,\"sign_required_bankcard\":1}', 0.00, 0, '', 0, 0);

-- ----------------------------
-- Table structure for payout_trade_logs
-- ----------------------------
DROP TABLE IF EXISTS `payout_trade_logs`;
CREATE TABLE `payout_trade_logs`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `withdraw_id` int(11) NOT NULL DEFAULT 0 COMMENT '提现单ID',
  `withdraw_no` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '提现单号',
  `agent_id` int(11) NOT NULL DEFAULT 0 COMMENT '代理ID',
  `provider_key` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '打款渠道标识',
  `payout_channel` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '打款渠道',
  `idempotency_key` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '幂等键',
  `provider_order_no` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '通道单号',
  `amount` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '打款金额',
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'processing' COMMENT 'processing/success/failed',
  `request_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '请求报文',
  `response_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '响应报文',
  `callback_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '回调报文',
  `fail_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '失败原因',
  `retry_count` int(11) NOT NULL DEFAULT 0 COMMENT '重试次数',
  `create_time` int(11) NOT NULL DEFAULT 0,
  `update_time` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_idempotency_key`(`idempotency_key`) USING BTREE,
  INDEX `idx_withdraw_id`(`withdraw_id`) USING BTREE,
  INDEX `idx_provider_order_no`(`provider_order_no`) USING BTREE,
  INDEX `idx_status_update_time`(`status`, `update_time`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '通用打款流水日志' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of payout_trade_logs
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
) ENGINE = InnoDB AUTO_INCREMENT = 39 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '插件授权表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of plugin_license
-- ----------------------------
INSERT INTO `plugin_license` VALUES (1, 'workorder', '工单系统', NULL, 0, 1753721925, 1778994313);
INSERT INTO `plugin_license` VALUES (2, 'marketing', '营销活动', NULL, 1, 1753721925, 1782373193);
INSERT INTO `plugin_license` VALUES (3, 'message', '站内信', NULL, 0, 1753721925, 1770136632);
INSERT INTO `plugin_license` VALUES (4, 'saas', '代理贴牌', NULL, 0, 1753721925, 1753721925);
INSERT INTO `plugin_license` VALUES (5, 'ai_chat', '智能AI客服', NULL, 0, 1753721925, 1753768297);
INSERT INTO `plugin_license` VALUES (6, 'transfer', '一键转单', NULL, 0, 1753721925, 1753721925);
INSERT INTO `plugin_license` VALUES (7, 'app', '小程序+APP', NULL, 0, 1753721925, 1753721925);
INSERT INTO `plugin_license` VALUES (8, 'mf58', '58秒返', NULL, 0, 1753721925, 1773905300);
INSERT INTO `plugin_license` VALUES (9, 'haoky', '卡业联盟', NULL, 0, 1753721925, 1760069125);
INSERT INTO `plugin_license` VALUES (10, 'haoy', '号易', NULL, 0, 1753721925, 1759982334);
INSERT INTO `plugin_license` VALUES (11, 'hao172', '172号卡', NULL, 0, 1753721925, 1753764611);
INSERT INTO `plugin_license` VALUES (12, 'lanchang', '蓝畅速享', NULL, 0, 1753721925, 1770136604);
INSERT INTO `plugin_license` VALUES (13, 'tiancheng', '天城智控API', 'B074B93AC9DF2337', 0, 1753721925, 1782373379);
INSERT INTO `plugin_license` VALUES (14, 'haoteam', '号卡极团', NULL, 0, 1753721925, 1774945610);
INSERT INTO `plugin_license` VALUES (15, '91', '91敢探号', NULL, 0, 1753721925, 1753721925);
INSERT INTO `plugin_license` VALUES (16, 'jlcloud', '巨量互联', 'D4808C79B0CC4D44', 0, 1753721925, 1770973328);
INSERT INTO `plugin_license` VALUES (17, 'longbao', '龙宝API', 'A3D03FE13F9F2EC0', 0, 1759839622, 1759987761);
INSERT INTO `plugin_license` VALUES (18, 'jikeyun', '极客云API', '085A561168C2E83D', 0, 1759989631, 1760031791);
INSERT INTO `plugin_license` VALUES (19, 'agent_migrate', '代理迁移', 'F5CE1F6EE909D054', 0, 1760676733, 1773902118);
INSERT INTO `plugin_license` VALUES (20, 'guangmengyun', '广梦云', 'C40C6BD774A68EEB', 0, 1769313834, 1772590943);
INSERT INTO `plugin_license` VALUES (22, 'gchk', '共创号卡', 'A724CFBC34404D39', 0, 1770012332, 1770387868);
INSERT INTO `plugin_license` VALUES (23, 'imagetemplate', '一键转图', '3FD92934683491C2', 0, 1770388019, 1775045842);
INSERT INTO `plugin_license` VALUES (24, 'gth91', '91敢探号', NULL, 0, 1771345767, 1771345767);
INSERT INTO `plugin_license` VALUES (25, 'h5', 'H5代理手机端v1', NULL, 1, 1772762463, 1782371472);
INSERT INTO `plugin_license` VALUES (26, 'down_api', '开放API', NULL, 0, 1773368230, 1782283707);
INSERT INTO `plugin_license` VALUES (27, 'pay_card', '在线支付', NULL, 0, 1773413805, 1776919967);
INSERT INTO `plugin_license` VALUES (28, 'oauth_qq', 'QQ快捷登录', NULL, 1, 1773544650, 1782373181);
INSERT INTO `plugin_license` VALUES (29, 'oauth_wechat', '微信快捷登录', '9YHLBJMEL1QFWC5V', 0, 1773544861, 1778853055);
INSERT INTO `plugin_license` VALUES (30, 'mp', '公众号接口', NULL, 0, 1773893396, 1774942235);
INSERT INTO `plugin_license` VALUES (31, 'h5_v2', 'H5代理手机端v2', 'EF478FE0A24D1122', 0, 1773893444, 1773998295);
INSERT INTO `plugin_license` VALUES (32, 'secret_price', '商务多级密价', NULL, 0, 1773905037, 1773905174);
INSERT INTO `plugin_license` VALUES (33, 'yunzhanghu', '云账户自动打款', NULL, 0, 1774952681, 1778853045);
INSERT INTO `plugin_license` VALUES (34, 'wps_excel', '金山文档多维表', NULL, 0, 1775104920, 1775104920);
INSERT INTO `plugin_license` VALUES (35, 'fadada', '法大大扫码签', '05045DFB8E1705B8', 0, 1775909649, 1782371725);
INSERT INTO `plugin_license` VALUES (36, 'app_pack', 'APP打包服务', 'LUNTVP974TKQ4VOQ', 0, 1777297550, 1777528916);
INSERT INTO `plugin_license` VALUES (37, 'mini_app', '小程序', NULL, 0, 1781464581, 1781464581);
INSERT INTO `plugin_license` VALUES (38, 'oem', '代理贴牌', NULL, 0, 1782360505, 1782360505);

-- ----------------------------
-- Table structure for product
-- ----------------------------
DROP TABLE IF EXISTS `product`;
CREATE TABLE `product`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '产品名称',
  `number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '对接编号',
  `external_order_url` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '第三方下单地址',
  `external_order_tip` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '第三方选号提示文案',
  `external_order_guide_enabled` tinyint(1) NOT NULL DEFAULT 0 COMMENT '第三方下单是否显示操作示例',
  `external_order_guide_images` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '第三方下单操作示例图片JSON',
  `api_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '对接上游名称',
  `api_config_id` int(11) NULL DEFAULT 0 COMMENT 'API配置ID（用于多配置API）',
  `self_channel_id` int(11) NOT NULL DEFAULT 0 COMMENT '自营渠道ID，0=未设置',
  `yys` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '运营商(移动/联通/电信/广电)',
  `product_image` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '产品首图',
  `detail_images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '详情图',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态(0-下架 1-上架 2-待上架)',
  `is_open` tinyint(1) NULL DEFAULT 1 COMMENT '是否对下游开放',
  `is_recommend` tinyint(1) NOT NULL DEFAULT 0 COMMENT '平台推荐：0=否，1=是',
  `admin_sort_order` int(11) NOT NULL DEFAULT 0 COMMENT '总后台排序值：0=未排序，数值越小越靠前',
  `commission` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '佣金',
  `commission_note` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '佣金说明标签',
  `js_type` tinyint(1) NULL DEFAULT NULL COMMENT '结算模式(1-秒返 2-次月返)',
  `js_require` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '结算要求',
  `create_time` datetime NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NULL DEFAULT CURRENT_TIMESTAMP COMMENT '更新时间',
  `yuezu` decimal(10, 2) NULL DEFAULT NULL COMMENT '月租',
  `selectNumber` tinyint(1) NULL DEFAULT 0 COMMENT '是否选号(0-否 1-是)',
  `need_idcard` tinyint(1) NOT NULL DEFAULT 1 COMMENT '下单是否需要身份证号：1需要 0不需要',
  `iccid_auto_push` tinyint(1) NULL DEFAULT 0 COMMENT 'ICCID自动推送',
  `isHot` tinyint(1) NULL DEFAULT 0 COMMENT '是否热门(0-否 1-是)',
  `hot_sort` int(11) NULL DEFAULT 0 COMMENT '热门排序权重，数字越大越靠前',
  `tags` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '产品标签',
  `visible_group_ids` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '可见代理分组ID，逗号分隔，空=全部可见',
  `order_protocol_ids` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '额外下单协议ID，逗号分隔',
  `order_protocol_mode` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'inherit' COMMENT '下单协议模式：inherit跟随默认 custom自定义 none不使用',
  `flow` int(11) NULL DEFAULT 0 COMMENT '流量(GB)',
  `dingxiang` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '定向流量(GB)',
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
  `order_process` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '下单流程JSON',
  `product_popup` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '产品弹窗内容',
  `submit_success_info` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '提单后信息',
  `product_custom_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '产品下单自定义字段JSON',
  `is_id_photo` tinyint(1) NULL DEFAULT 0 COMMENT '是否上传身份证 0-否 1-是',
  `is_four_photo` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否需要第四证:0=否,1=是',
  `four_photo_title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '第四照标题',
  `four_photo` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '第四照查询链接',
  `four_photo_guide_enabled` tinyint(1) NOT NULL DEFAULT 0 COMMENT '四证查询是否显示操作示例',
  `four_photo_guide_images` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '四证查询操作示例图片JSON',
  `card_type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '卡类型：0免费卡 1付费卡',
  `product_category` tinyint(1) NOT NULL DEFAULT 0 COMMENT '商品分类：0流量卡，1宽带',
  `category_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '产品分类ID',
  `product_type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'flow_card' COMMENT '产品类型',
  `card_price` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '卡费金额（付费卡时有效）',
  `card_price_note` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '卡费说明标签',
  `card_price_user_note` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户可见卡费说明标签',
  `card_price_text` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '卡费自定义文案',
  `policy_order_security_check` tinyint(1) NULL DEFAULT NULL COMMENT '订单安全校验覆盖 NULL跟系统',
  `policy_shop_order_verify` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '店铺下单验证覆盖 NULL跟系统 none/sms/image',
  `policy_shop_order_idcard_verify` tinyint(1) NULL DEFAULT NULL COMMENT '下单二要素覆盖 NULL跟系统',
  `policy_product_ship_sms_notice` tinyint(1) NULL DEFAULT NULL COMMENT '发货短信通知覆盖 NULL跟系统',
  `policy_order_submit_sms_notice` tinyint(1) NULL DEFAULT NULL COMMENT '订单提交短信通知覆盖 NULL跟系统',
  `policy_order_pending_ship_sms_notice` tinyint(1) NULL DEFAULT NULL COMMENT '订单待发货短信通知覆盖 NULL跟系统',
  `policy_order_review_failed_sms_notice` tinyint(1) NULL DEFAULT NULL COMMENT '订单审核失败短信通知覆盖 NULL跟系统',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_api_config_id`(`api_config_id`) USING BTREE,
  INDEX `idx_admin_sort`(`admin_sort_order`) USING BTREE,
  INDEX `idx_recommend`(`is_recommend`) USING BTREE,
  INDEX `idx_is_open`(`is_open`) USING BTREE,
  INDEX `idx_self_channel_id`(`self_channel_id`) USING BTREE,
  INDEX `idx_product_category`(`product_category`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '产品表' ROW_FORMAT = DYNAMIC;

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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '代理产品加价表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of product_agent_markup
-- ----------------------------

-- ----------------------------
-- Table structure for product_categories
-- ----------------------------
DROP TABLE IF EXISTS `product_categories`;
CREATE TABLE `product_categories`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '分类名称',
  `product_type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'flow_card' COMMENT '默认产品类型',
  `description` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '分类说明',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT '排序，越小越靠前',
  `is_priority` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否优先展示',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态',
  `create_time` datetime NULL DEFAULT NULL,
  `update_time` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_status_sort`(`status`, `sort_order`, `id`) USING BTREE,
  INDEX `idx_hidden_priority`(`is_priority`, `sort_order`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '产品分类' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of product_categories
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
  `visible_group_ids` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '可见代理分组ID，逗号分隔，空=全部可见',
  `sort` int(11) NULL DEFAULT 0 COMMENT '排序（数字越小越靠前）',
  `status` tinyint(1) NULL DEFAULT 1 COMMENT '状态：1=启用，0=禁用',
  `create_time` datetime NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_agent_id`(`agent_id`) USING BTREE,
  INDEX `idx_sort`(`sort`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '产品合集表' ROW_FORMAT = DYNAMIC;

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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '产品合集关联表' ROW_FORMAT = DYNAMIC;

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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '商品自定义图片表' ROW_FORMAT = DYNAMIC;

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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '工资发放记录表' ROW_FORMAT = DYNAMIC;

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
  `icon` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '等级图片URL',
  `secret_amount` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '密价金额（元）',
  `valid_start` bigint(16) UNSIGNED NULL DEFAULT NULL COMMENT '有效期开始时间',
  `valid_end` bigint(16) UNSIGNED NULL DEFAULT NULL COMMENT '有效期结束时间',
  `sort_order` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序（数字越小越靠前）',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：0=禁用，1=启用',
  `create_time` bigint(16) UNSIGNED NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` bigint(16) UNSIGNED NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_status_sort`(`status`, `sort_order`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '密价等级表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of secret_price_levels
-- ----------------------------

-- ----------------------------
-- Table structure for self_channel
-- ----------------------------
DROP TABLE IF EXISTS `self_channel`;
CREATE TABLE `self_channel`  (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '渠道名称',
  `code` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '渠道编码(唯一)',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态(0禁用 1启用)',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '备注',
  `create_time` datetime NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_code`(`code`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '自营渠道表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of self_channel
-- ----------------------------
INSERT INTO `self_channel` VALUES (1, '默认渠道', 'SC260402125357667', 1, '', '2026-04-02 12:53:57', '2026-04-02 12:53:57');

-- ----------------------------
-- Table structure for site_page_versions
-- ----------------------------
DROP TABLE IF EXISTS `site_page_versions`;
CREATE TABLE `site_page_versions`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `page_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `version` int(11) NOT NULL DEFAULT 1,
  `content_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `created_by` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `create_time` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_page_version`(`page_id`, `version`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 9 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of site_page_versions
-- ----------------------------
INSERT INTO `site_page_versions` VALUES (1, 1, 2, '{\"entry\":{\"mode\":\"site\",\"pcUrl\":\"/#/agent\",\"mobileUrl\":\"/#/h5\"},\"nav\":{\"homeLink\":\"\",\"productText\":\"产品中心1\",\"productLink\":\"#products\",\"platformText\":\"平台介绍\",\"platformLink\":\"#platform\",\"omniText\":\"多端互通\",\"omniLink\":\"#omni\",\"agencyText\":\"代理加盟\",\"agencyLink\":\"#agency\",\"agentLoginText\":\"代理登录\",\"agentLoginLink\":\"/#/agent\"},\"hero\":{\"eyebrow\":\"5G DATA CARD PLATFORM\",\"title\":\"优选流量卡\\n在线办理更省心\",\"lead\":\"精选多运营商套餐，流量、月租、通话等信息清晰展示，在线查看产品、提交办理、查询进度，满足日常上网与移动办公需求。\",\"primaryText\":\"查看产品中心\",\"primaryLink\":\"#products\",\"secondaryText\":\"了解代理加盟\",\"secondaryLink\":\"#agency\",\"image\":\"\"},\"visible\":{\"hero\":true,\"highlights\":true,\"platform\":true,\"core\":true,\"omni\":true,\"products\":true,\"agency\":true},\"highlights\":[{\"title\":\"套餐信息清晰\",\"desc\":\"流量、月租、通话等核心信息一目了然。\",\"target\":\"#products\"},{\"title\":\"在线便捷办理\",\"desc\":\"查看产品详情后，可进入对应办理流程。\",\"target\":\"#products\"},{\"title\":\"订单进度可查\",\"desc\":\"提交后可查询办理状态，减少等待焦虑。\",\"target\":\"#platform\"},{\"title\":\"代理合作支持\",\"desc\":\"为合作伙伴提供产品资料和推广服务。\",\"target\":\"#agency\"},{\"title\":\"产品持续更新\",\"desc\":\"套餐资料持续维护，便于获取新选择。\",\"target\":\"#products\"},{\"title\":\"多端访问顺畅\",\"desc\":\"电脑端、移动 H5、小程序与 APP 多端查看。\",\"target\":\"#omni\"}],\"sections\":{\"platform\":{\"title\":\"可靠产品，便捷服务\",\"desc\":\"围绕用户办卡、用卡、查单和咨询的真实需求，提供清晰易懂的服务体验。\"},\"core\":{\"title\":\"流量卡服务核心特点\",\"desc\":\"从产品展示、在线办理到订单跟进和服务支持，让办卡流程更清楚、更省心。\"},\"omni\":{\"title\":\"多平台互通互联\",\"desc\":\"电脑端、移动 H5、小程序与 APP 多端联动，用户查看更方便，合作伙伴服务更顺手。\"},\"products\":{\"eyebrow\":\"产品中心\",\"title\":\"精选流量卡产品\",\"desc\":\"展示套餐名称、运营商、流量、月租、通话等关键信息，方便快速了解并选择适合自己的产品。\",\"buttonText\":\"进入产品中心\",\"buttonLink\":\"/#/product\"},\"agency\":{\"eyebrow\":\"代理加盟\",\"title\":\"携手合作，共同拓展流量卡服务市场\",\"desc\":\"适合通信门店、社群团长、地推团队和线上渠道合作，平台提供产品资料、办理入口、订单查询与服务支持。\",\"buttonText\":\"代理登录\",\"buttonLink\":\"/#/agent\"}},\"capabilities\":[{\"icon\":\"ri:search-eye-line\",\"title\":\"查产品\",\"desc\":\"套餐名称、运营商、月租和流量信息集中呈现，用户可快速了解产品重点。\",\"points\":[\"套餐名称\",\"运营商\",\"月租信息\",\"流量信息\"]},{\"icon\":\"ri:smartphone-line\",\"title\":\"办套餐\",\"desc\":\"从查看产品到资料提交都有清晰指引，让用户在线办理路径更顺畅。\",\"points\":[\"在线查看\",\"快速选择\",\"资料提交\",\"办理指引\"]},{\"icon\":\"ri:file-search-line\",\"title\":\"查进度\",\"desc\":\"提交后可查看办理进度和结果提醒，减少反复咨询和等待焦虑。\",\"points\":[\"订单查询\",\"状态跟进\",\"资料补充\",\"结果提醒\"]},{\"icon\":\"ri:customer-service-2-line\",\"title\":\"享服务\",\"desc\":\"咨询、协助、办理说明和售后支持集中承接，用户遇到问题可及时处理。\",\"points\":[\"客服咨询\",\"问题协助\",\"办理说明\",\"售后支持\"]},{\"icon\":\"ri:team-line\",\"title\":\"代理合作\",\"desc\":\"为合作伙伴提供产品资料、推广入口和渠道服务，方便拓展客户。\",\"points\":[\"合作入口\",\"产品资料\",\"推广支持\",\"渠道服务\"]},{\"icon\":\"ri:shield-check-line\",\"title\":\"放心选择\",\"desc\":\"正规产品、信息透明、流程清楚，让用户选择和办理都更踏实。\",\"points\":[\"正规产品\",\"信息透明\",\"流程清晰\",\"服务可达\"]}],\"coreFeatures\":[{\"title\":\"优选套餐\",\"desc\":\"聚合不同使用场景的流量卡产品，方便快速筛选。\"},{\"title\":\"在线查看\",\"desc\":\"产品图片、套餐信息和办理说明集中展示。\"},{\"title\":\"快速办理\",\"desc\":\"用户可从产品详情进入办理流程，路径更清楚。\"},{\"title\":\"多场景适用\",\"desc\":\"覆盖日常上网、短视频、办公出行和备用流量。\"},{\"title\":\"进度可查\",\"desc\":\"提交后可通过查询入口了解办理状态。\"},{\"title\":\"资料提醒\",\"desc\":\"需要补充资料时，用户可按指引继续处理。\"},{\"title\":\"代理协作\",\"desc\":\"合作伙伴可通过专属入口服务自己的客户。\"},{\"title\":\"分享推广\",\"desc\":\"适合社群、门店、朋友圈和线上渠道传播。\"},{\"title\":\"客户服务\",\"desc\":\"提供咨询与协助入口，处理办理和查询问题。\"},{\"title\":\"信息透明\",\"desc\":\"关键套餐信息清楚展示，减少反复沟通。\"},{\"title\":\"移动适配\",\"desc\":\"手机端查看、分享和提交更顺手。\"},{\"title\":\"持续更新\",\"desc\":\"产品资料持续维护，便于用户获取新套餐。\"}],\"omniPlatforms\":[{\"icon\":\"ri:computer-line\",\"title\":\"电脑端\",\"desc\":\"品牌展示、产品中心、平台介绍和代理加盟统一承载。\",\"image\":\"\"},{\"icon\":\"ri:html5-line\",\"title\":\"移动 H5\",\"desc\":\"手机浏览更轻便，适合从社群、短信和短视频场景访问。\",\"image\":\"\"},{\"icon\":\"ri:wechat-line\",\"title\":\"小程序\",\"desc\":\"适合通过微信场景分享产品、承接咨询和办理线索。\",\"image\":\"\"},{\"icon\":\"ri:app-store-line\",\"title\":\"APP\",\"desc\":\"适合沉淀常用入口，支持产品查看、分享推广和客户服务。\",\"image\":\"\"}],\"agencySteps\":[{\"icon\":\"ri:user-add-line\",\"title\":\"开通合作账号\",\"desc\":\"获得专属代理入口，方便查看产品和服务客户。\"},{\"icon\":\"ri:share-forward-line\",\"title\":\"分享产品链接\",\"desc\":\"适合社群、门店、朋友圈和线上渠道推广。\"},{\"icon\":\"ri:file-search-line\",\"title\":\"跟进订单状态\",\"desc\":\"及时查看客户办理状态，提升服务体验。\"},{\"icon\":\"ri:customer-service-2-line\",\"title\":\"获得服务支持\",\"desc\":\"平台持续提供产品资料和业务协助。\"}],\"footer\":{\"slogan\":\"高速流量卡服务平台\"}}', 1, 1781462141);
INSERT INTO `site_page_versions` VALUES (2, 1, 3, '{\"entry\":{\"mode\":\"site\",\"pcUrl\":\"/#/agent\",\"mobileUrl\":\"/#/h5\"},\"nav\":{\"homeLink\":\"\",\"productText\":\"产品中心\",\"productLink\":\"#products\",\"platformText\":\"平台介绍\",\"platformLink\":\"#platform\",\"omniText\":\"多端互通\",\"omniLink\":\"#omni\",\"agencyText\":\"代理加盟\",\"agencyLink\":\"#agency\",\"agentLoginText\":\"代理登录\",\"agentLoginLink\":\"/#/agent\"},\"hero\":{\"eyebrow\":\"5G DATA CARD PLATFORM\",\"title\":\"优选流量卡\\n在线办理更省心\",\"lead\":\"精选多运营商套餐，流量、月租、通话等信息清晰展示，在线查看产品、提交办理、查询进度，满足日常上网与移动办公需求。\",\"primaryText\":\"查看产品中心\",\"primaryLink\":\"#products\",\"secondaryText\":\"了解代理加盟\",\"secondaryLink\":\"#agency\",\"image\":\"\"},\"visible\":{\"hero\":true,\"highlights\":true,\"platform\":true,\"core\":true,\"omni\":true,\"products\":true,\"agency\":true},\"highlights\":[{\"title\":\"套餐信息清晰\",\"desc\":\"流量、月租、通话等核心信息一目了然。\",\"target\":\"#products\"},{\"title\":\"在线便捷办理\",\"desc\":\"查看产品详情后，可进入对应办理流程。\",\"target\":\"#products\"},{\"title\":\"订单进度可查\",\"desc\":\"提交后可查询办理状态，减少等待焦虑。\",\"target\":\"#platform\"},{\"title\":\"代理合作支持\",\"desc\":\"为合作伙伴提供产品资料和推广服务。\",\"target\":\"#agency\"},{\"title\":\"产品持续更新\",\"desc\":\"套餐资料持续维护，便于获取新选择。\",\"target\":\"#products\"},{\"title\":\"多端访问顺畅\",\"desc\":\"电脑端、移动 H5、小程序与 APP 多端查看。\",\"target\":\"#omni\"}],\"sections\":{\"platform\":{\"title\":\"可靠产品，便捷服务\",\"desc\":\"围绕用户办卡、用卡、查单和咨询的真实需求，提供清晰易懂的服务体验。\"},\"core\":{\"title\":\"流量卡服务核心特点\",\"desc\":\"从产品展示、在线办理到订单跟进和服务支持，让办卡流程更清楚、更省心。\"},\"omni\":{\"title\":\"多平台互通互联\",\"desc\":\"电脑端、移动 H5、小程序与 APP 多端联动，用户查看更方便，合作伙伴服务更顺手。\"},\"products\":{\"eyebrow\":\"产品中心\",\"title\":\"精选流量卡产品\",\"desc\":\"展示套餐名称、运营商、流量、月租、通话等关键信息，方便快速了解并选择适合自己的产品。\",\"buttonText\":\"进入产品中心\",\"buttonLink\":\"/#/product\"},\"agency\":{\"eyebrow\":\"代理加盟\",\"title\":\"携手合作，共同拓展流量卡服务市场\",\"desc\":\"适合通信门店、社群团长、地推团队和线上渠道合作，平台提供产品资料、办理入口、订单查询与服务支持。\",\"buttonText\":\"代理登录\",\"buttonLink\":\"/#/agent\"}},\"capabilities\":[{\"icon\":\"ri:search-eye-line\",\"title\":\"查产品\",\"desc\":\"套餐名称、运营商、月租和流量信息集中呈现，用户可快速了解产品重点。\",\"points\":[\"套餐名称\",\"运营商\",\"月租信息\",\"流量信息\"]},{\"icon\":\"ri:smartphone-line\",\"title\":\"办套餐\",\"desc\":\"从查看产品到资料提交都有清晰指引，让用户在线办理路径更顺畅。\",\"points\":[\"在线查看\",\"快速选择\",\"资料提交\",\"办理指引\"]},{\"icon\":\"ri:file-search-line\",\"title\":\"查进度\",\"desc\":\"提交后可查看办理进度和结果提醒，减少反复咨询和等待焦虑。\",\"points\":[\"订单查询\",\"状态跟进\",\"资料补充\",\"结果提醒\"]},{\"icon\":\"ri:customer-service-2-line\",\"title\":\"享服务\",\"desc\":\"咨询、协助、办理说明和售后支持集中承接，用户遇到问题可及时处理。\",\"points\":[\"客服咨询\",\"问题协助\",\"办理说明\",\"售后支持\"]},{\"icon\":\"ri:team-line\",\"title\":\"代理合作\",\"desc\":\"为合作伙伴提供产品资料、推广入口和渠道服务，方便拓展客户。\",\"points\":[\"合作入口\",\"产品资料\",\"推广支持\",\"渠道服务\"]},{\"icon\":\"ri:shield-check-line\",\"title\":\"放心选择\",\"desc\":\"正规产品、信息透明、流程清楚，让用户选择和办理都更踏实。\",\"points\":[\"正规产品\",\"信息透明\",\"流程清晰\",\"服务可达\"]}],\"coreFeatures\":[{\"title\":\"优选套餐\",\"desc\":\"聚合不同使用场景的流量卡产品，方便快速筛选。\"},{\"title\":\"在线查看\",\"desc\":\"产品图片、套餐信息和办理说明集中展示。\"},{\"title\":\"快速办理\",\"desc\":\"用户可从产品详情进入办理流程，路径更清楚。\"},{\"title\":\"多场景适用\",\"desc\":\"覆盖日常上网、短视频、办公出行和备用流量。\"},{\"title\":\"进度可查\",\"desc\":\"提交后可通过查询入口了解办理状态。\"},{\"title\":\"资料提醒\",\"desc\":\"需要补充资料时，用户可按指引继续处理。\"},{\"title\":\"代理协作\",\"desc\":\"合作伙伴可通过专属入口服务自己的客户。\"},{\"title\":\"分享推广\",\"desc\":\"适合社群、门店、朋友圈和线上渠道传播。\"},{\"title\":\"客户服务\",\"desc\":\"提供咨询与协助入口，处理办理和查询问题。\"},{\"title\":\"信息透明\",\"desc\":\"关键套餐信息清楚展示，减少反复沟通。\"},{\"title\":\"移动适配\",\"desc\":\"手机端查看、分享和提交更顺手。\"},{\"title\":\"持续更新\",\"desc\":\"产品资料持续维护，便于用户获取新套餐。\"}],\"omniPlatforms\":[{\"icon\":\"ri:computer-line\",\"title\":\"电脑端\",\"desc\":\"品牌展示、产品中心、平台介绍和代理加盟统一承载。\",\"image\":\"\"},{\"icon\":\"ri:html5-line\",\"title\":\"移动 H5\",\"desc\":\"手机浏览更轻便，适合从社群、短信和短视频场景访问。\",\"image\":\"\"},{\"icon\":\"ri:wechat-line\",\"title\":\"小程序\",\"desc\":\"适合通过微信场景分享产品、承接咨询和办理线索。\",\"image\":\"\"},{\"icon\":\"ri:app-store-line\",\"title\":\"APP\",\"desc\":\"适合沉淀常用入口，支持产品查看、分享推广和客户服务。\",\"image\":\"\"}],\"agencySteps\":[{\"icon\":\"ri:user-add-line\",\"title\":\"开通合作账号\",\"desc\":\"获得专属代理入口，方便查看产品和服务客户。\"},{\"icon\":\"ri:share-forward-line\",\"title\":\"分享产品链接\",\"desc\":\"适合社群、门店、朋友圈和线上渠道推广。\"},{\"icon\":\"ri:file-search-line\",\"title\":\"跟进订单状态\",\"desc\":\"及时查看客户办理状态，提升服务体验。\"},{\"icon\":\"ri:customer-service-2-line\",\"title\":\"获得服务支持\",\"desc\":\"平台持续提供产品资料和业务协助。\"}],\"footer\":{\"slogan\":\"高速流量卡服务平台\"}}', 1, 1781462158);
INSERT INTO `site_page_versions` VALUES (3, 1, 4, '{\"entry\":{\"mode\":\"agent\",\"pcUrl\":\"/#/agent\",\"mobileUrl\":\"/#/h5\"},\"nav\":{\"homeLink\":\"\",\"productText\":\"产品中心\",\"productLink\":\"#products\",\"platformText\":\"平台介绍\",\"platformLink\":\"#platform\",\"omniText\":\"多端互通\",\"omniLink\":\"#omni\",\"agencyText\":\"代理加盟\",\"agencyLink\":\"#agency\",\"agentLoginText\":\"代理登录\",\"agentLoginLink\":\"/#/agent\"},\"hero\":{\"eyebrow\":\"5G DATA CARD PLATFORM\",\"title\":\"优选流量卡\\n在线办理更省心\",\"lead\":\"精选多运营商套餐，流量、月租、通话等信息清晰展示，在线查看产品、提交办理、查询进度，满足日常上网与移动办公需求。\",\"primaryText\":\"查看产品中心\",\"primaryLink\":\"#products\",\"secondaryText\":\"了解代理加盟\",\"secondaryLink\":\"#agency\",\"image\":\"\"},\"visible\":{\"hero\":true,\"highlights\":true,\"platform\":true,\"core\":true,\"omni\":true,\"products\":true,\"agency\":true},\"highlights\":[{\"title\":\"套餐信息清晰\",\"desc\":\"流量、月租、通话等核心信息一目了然。\",\"target\":\"#products\"},{\"title\":\"在线便捷办理\",\"desc\":\"查看产品详情后，可进入对应办理流程。\",\"target\":\"#products\"},{\"title\":\"订单进度可查\",\"desc\":\"提交后可查询办理状态，减少等待焦虑。\",\"target\":\"#platform\"},{\"title\":\"代理合作支持\",\"desc\":\"为合作伙伴提供产品资料和推广服务。\",\"target\":\"#agency\"},{\"title\":\"产品持续更新\",\"desc\":\"套餐资料持续维护，便于获取新选择。\",\"target\":\"#products\"},{\"title\":\"多端访问顺畅\",\"desc\":\"电脑端、移动 H5、小程序与 APP 多端查看。\",\"target\":\"#omni\"}],\"sections\":{\"platform\":{\"title\":\"可靠产品，便捷服务\",\"desc\":\"围绕用户办卡、用卡、查单和咨询的真实需求，提供清晰易懂的服务体验。\"},\"core\":{\"title\":\"流量卡服务核心特点\",\"desc\":\"从产品展示、在线办理到订单跟进和服务支持，让办卡流程更清楚、更省心。\"},\"omni\":{\"title\":\"多平台互通互联\",\"desc\":\"电脑端、移动 H5、小程序与 APP 多端联动，用户查看更方便，合作伙伴服务更顺手。\"},\"products\":{\"eyebrow\":\"产品中心\",\"title\":\"精选流量卡产品\",\"desc\":\"展示套餐名称、运营商、流量、月租、通话等关键信息，方便快速了解并选择适合自己的产品。\",\"buttonText\":\"进入产品中心\",\"buttonLink\":\"/#/product\"},\"agency\":{\"eyebrow\":\"代理加盟\",\"title\":\"携手合作，共同拓展流量卡服务市场\",\"desc\":\"适合通信门店、社群团长、地推团队和线上渠道合作，平台提供产品资料、办理入口、订单查询与服务支持。\",\"buttonText\":\"代理登录\",\"buttonLink\":\"/#/agent\"}},\"capabilities\":[{\"icon\":\"ri:search-eye-line\",\"title\":\"查产品\",\"desc\":\"套餐名称、运营商、月租和流量信息集中呈现，用户可快速了解产品重点。\",\"points\":[\"套餐名称\",\"运营商\",\"月租信息\",\"流量信息\"]},{\"icon\":\"ri:smartphone-line\",\"title\":\"办套餐\",\"desc\":\"从查看产品到资料提交都有清晰指引，让用户在线办理路径更顺畅。\",\"points\":[\"在线查看\",\"快速选择\",\"资料提交\",\"办理指引\"]},{\"icon\":\"ri:file-search-line\",\"title\":\"查进度\",\"desc\":\"提交后可查看办理进度和结果提醒，减少反复咨询和等待焦虑。\",\"points\":[\"订单查询\",\"状态跟进\",\"资料补充\",\"结果提醒\"]},{\"icon\":\"ri:customer-service-2-line\",\"title\":\"享服务\",\"desc\":\"咨询、协助、办理说明和售后支持集中承接，用户遇到问题可及时处理。\",\"points\":[\"客服咨询\",\"问题协助\",\"办理说明\",\"售后支持\"]},{\"icon\":\"ri:team-line\",\"title\":\"代理合作\",\"desc\":\"为合作伙伴提供产品资料、推广入口和渠道服务，方便拓展客户。\",\"points\":[\"合作入口\",\"产品资料\",\"推广支持\",\"渠道服务\"]},{\"icon\":\"ri:shield-check-line\",\"title\":\"放心选择\",\"desc\":\"正规产品、信息透明、流程清楚，让用户选择和办理都更踏实。\",\"points\":[\"正规产品\",\"信息透明\",\"流程清晰\",\"服务可达\"]}],\"coreFeatures\":[{\"title\":\"优选套餐\",\"desc\":\"聚合不同使用场景的流量卡产品，方便快速筛选。\"},{\"title\":\"在线查看\",\"desc\":\"产品图片、套餐信息和办理说明集中展示。\"},{\"title\":\"快速办理\",\"desc\":\"用户可从产品详情进入办理流程，路径更清楚。\"},{\"title\":\"多场景适用\",\"desc\":\"覆盖日常上网、短视频、办公出行和备用流量。\"},{\"title\":\"进度可查\",\"desc\":\"提交后可通过查询入口了解办理状态。\"},{\"title\":\"资料提醒\",\"desc\":\"需要补充资料时，用户可按指引继续处理。\"},{\"title\":\"代理协作\",\"desc\":\"合作伙伴可通过专属入口服务自己的客户。\"},{\"title\":\"分享推广\",\"desc\":\"适合社群、门店、朋友圈和线上渠道传播。\"},{\"title\":\"客户服务\",\"desc\":\"提供咨询与协助入口，处理办理和查询问题。\"},{\"title\":\"信息透明\",\"desc\":\"关键套餐信息清楚展示，减少反复沟通。\"},{\"title\":\"移动适配\",\"desc\":\"手机端查看、分享和提交更顺手。\"},{\"title\":\"持续更新\",\"desc\":\"产品资料持续维护，便于用户获取新套餐。\"}],\"omniPlatforms\":[{\"icon\":\"ri:computer-line\",\"title\":\"电脑端\",\"desc\":\"品牌展示、产品中心、平台介绍和代理加盟统一承载。\",\"image\":\"\"},{\"icon\":\"ri:html5-line\",\"title\":\"移动 H5\",\"desc\":\"手机浏览更轻便，适合从社群、短信和短视频场景访问。\",\"image\":\"\"},{\"icon\":\"ri:wechat-line\",\"title\":\"小程序\",\"desc\":\"适合通过微信场景分享产品、承接咨询和办理线索。\",\"image\":\"\"},{\"icon\":\"ri:app-store-line\",\"title\":\"APP\",\"desc\":\"适合沉淀常用入口，支持产品查看、分享推广和客户服务。\",\"image\":\"\"}],\"agencySteps\":[{\"icon\":\"ri:user-add-line\",\"title\":\"开通合作账号\",\"desc\":\"获得专属代理入口，方便查看产品和服务客户。\"},{\"icon\":\"ri:share-forward-line\",\"title\":\"分享产品链接\",\"desc\":\"适合社群、门店、朋友圈和线上渠道推广。\"},{\"icon\":\"ri:file-search-line\",\"title\":\"跟进订单状态\",\"desc\":\"及时查看客户办理状态，提升服务体验。\"},{\"icon\":\"ri:customer-service-2-line\",\"title\":\"获得服务支持\",\"desc\":\"平台持续提供产品资料和业务协助。\"}],\"footer\":{\"slogan\":\"高速流量卡服务平台\"}}', 1, 1781462169);
INSERT INTO `site_page_versions` VALUES (4, 1, 5, '{\"entry\":{\"mode\":\"site\",\"pcUrl\":\"/#/agent\",\"mobileUrl\":\"/#/h5\"},\"nav\":{\"homeLink\":\"\",\"productText\":\"产品中心\",\"productLink\":\"#products\",\"platformText\":\"平台介绍\",\"platformLink\":\"#platform\",\"omniText\":\"多端互通\",\"omniLink\":\"#omni\",\"agencyText\":\"代理加盟\",\"agencyLink\":\"#agency\",\"agentLoginText\":\"代理登录\",\"agentLoginLink\":\"/#/agent\"},\"hero\":{\"eyebrow\":\"5G DATA CARD PLATFORM\",\"title\":\"优选流量卡\\n在线办理更省心\",\"lead\":\"精选多运营商套餐，流量、月租、通话等信息清晰展示，在线查看产品、提交办理、查询进度，满足日常上网与移动办公需求。\",\"primaryText\":\"查看产品中心\",\"primaryLink\":\"#products\",\"secondaryText\":\"了解代理加盟\",\"secondaryLink\":\"#agency\",\"image\":\"\"},\"visible\":{\"hero\":true,\"highlights\":true,\"platform\":true,\"core\":true,\"omni\":true,\"products\":true,\"agency\":true},\"highlights\":[{\"title\":\"套餐信息清晰\",\"desc\":\"流量、月租、通话等核心信息一目了然。\",\"target\":\"#products\"},{\"title\":\"在线便捷办理\",\"desc\":\"查看产品详情后，可进入对应办理流程。\",\"target\":\"#products\"},{\"title\":\"订单进度可查\",\"desc\":\"提交后可查询办理状态，减少等待焦虑。\",\"target\":\"#platform\"},{\"title\":\"代理合作支持\",\"desc\":\"为合作伙伴提供产品资料和推广服务。\",\"target\":\"#agency\"},{\"title\":\"产品持续更新\",\"desc\":\"套餐资料持续维护，便于获取新选择。\",\"target\":\"#products\"},{\"title\":\"多端访问顺畅\",\"desc\":\"电脑端、移动 H5、小程序与 APP 多端查看。\",\"target\":\"#omni\"}],\"sections\":{\"platform\":{\"title\":\"可靠产品，便捷服务\",\"desc\":\"围绕用户办卡、用卡、查单和咨询的真实需求，提供清晰易懂的服务体验。\"},\"core\":{\"title\":\"流量卡服务核心特点\",\"desc\":\"从产品展示、在线办理到订单跟进和服务支持，让办卡流程更清楚、更省心。\"},\"omni\":{\"title\":\"多平台互通互联\",\"desc\":\"电脑端、移动 H5、小程序与 APP 多端联动，用户查看更方便，合作伙伴服务更顺手。\"},\"products\":{\"eyebrow\":\"产品中心\",\"title\":\"精选流量卡产品\",\"desc\":\"展示套餐名称、运营商、流量、月租、通话等关键信息，方便快速了解并选择适合自己的产品。\",\"buttonText\":\"进入产品中心\",\"buttonLink\":\"/#/product\"},\"agency\":{\"eyebrow\":\"代理加盟\",\"title\":\"携手合作，共同拓展流量卡服务市场\",\"desc\":\"适合通信门店、社群团长、地推团队和线上渠道合作，平台提供产品资料、办理入口、订单查询与服务支持。\",\"buttonText\":\"代理登录\",\"buttonLink\":\"/#/agent\"}},\"capabilities\":[{\"icon\":\"ri:search-eye-line\",\"title\":\"查产品\",\"desc\":\"套餐名称、运营商、月租和流量信息集中呈现，用户可快速了解产品重点。\",\"points\":[\"套餐名称\",\"运营商\",\"月租信息\",\"流量信息\"]},{\"icon\":\"ri:smartphone-line\",\"title\":\"办套餐\",\"desc\":\"从查看产品到资料提交都有清晰指引，让用户在线办理路径更顺畅。\",\"points\":[\"在线查看\",\"快速选择\",\"资料提交\",\"办理指引\"]},{\"icon\":\"ri:file-search-line\",\"title\":\"查进度\",\"desc\":\"提交后可查看办理进度和结果提醒，减少反复咨询和等待焦虑。\",\"points\":[\"订单查询\",\"状态跟进\",\"资料补充\",\"结果提醒\"]},{\"icon\":\"ri:customer-service-2-line\",\"title\":\"享服务\",\"desc\":\"咨询、协助、办理说明和售后支持集中承接，用户遇到问题可及时处理。\",\"points\":[\"客服咨询\",\"问题协助\",\"办理说明\",\"售后支持\"]},{\"icon\":\"ri:team-line\",\"title\":\"代理合作\",\"desc\":\"为合作伙伴提供产品资料、推广入口和渠道服务，方便拓展客户。\",\"points\":[\"合作入口\",\"产品资料\",\"推广支持\",\"渠道服务\"]},{\"icon\":\"ri:shield-check-line\",\"title\":\"放心选择\",\"desc\":\"正规产品、信息透明、流程清楚，让用户选择和办理都更踏实。\",\"points\":[\"正规产品\",\"信息透明\",\"流程清晰\",\"服务可达\"]}],\"coreFeatures\":[{\"title\":\"优选套餐\",\"desc\":\"聚合不同使用场景的流量卡产品，方便快速筛选。\"},{\"title\":\"在线查看\",\"desc\":\"产品图片、套餐信息和办理说明集中展示。\"},{\"title\":\"快速办理\",\"desc\":\"用户可从产品详情进入办理流程，路径更清楚。\"},{\"title\":\"多场景适用\",\"desc\":\"覆盖日常上网、短视频、办公出行和备用流量。\"},{\"title\":\"进度可查\",\"desc\":\"提交后可通过查询入口了解办理状态。\"},{\"title\":\"资料提醒\",\"desc\":\"需要补充资料时，用户可按指引继续处理。\"},{\"title\":\"代理协作\",\"desc\":\"合作伙伴可通过专属入口服务自己的客户。\"},{\"title\":\"分享推广\",\"desc\":\"适合社群、门店、朋友圈和线上渠道传播。\"},{\"title\":\"客户服务\",\"desc\":\"提供咨询与协助入口，处理办理和查询问题。\"},{\"title\":\"信息透明\",\"desc\":\"关键套餐信息清楚展示，减少反复沟通。\"},{\"title\":\"移动适配\",\"desc\":\"手机端查看、分享和提交更顺手。\"},{\"title\":\"持续更新\",\"desc\":\"产品资料持续维护，便于用户获取新套餐。\"}],\"omniPlatforms\":[{\"icon\":\"ri:computer-line\",\"title\":\"电脑端\",\"desc\":\"品牌展示、产品中心、平台介绍和代理加盟统一承载。\",\"image\":\"\"},{\"icon\":\"ri:html5-line\",\"title\":\"移动 H5\",\"desc\":\"手机浏览更轻便，适合从社群、短信和短视频场景访问。\",\"image\":\"\"},{\"icon\":\"ri:wechat-line\",\"title\":\"小程序\",\"desc\":\"适合通过微信场景分享产品、承接咨询和办理线索。\",\"image\":\"\"},{\"icon\":\"ri:app-store-line\",\"title\":\"APP\",\"desc\":\"适合沉淀常用入口，支持产品查看、分享推广和客户服务。\",\"image\":\"\"}],\"agencySteps\":[{\"icon\":\"ri:user-add-line\",\"title\":\"开通合作账号\",\"desc\":\"获得专属代理入口，方便查看产品和服务客户。\"},{\"icon\":\"ri:share-forward-line\",\"title\":\"分享产品链接\",\"desc\":\"适合社群、门店、朋友圈和线上渠道推广。\"},{\"icon\":\"ri:file-search-line\",\"title\":\"跟进订单状态\",\"desc\":\"及时查看客户办理状态，提升服务体验。\"},{\"icon\":\"ri:customer-service-2-line\",\"title\":\"获得服务支持\",\"desc\":\"平台持续提供产品资料和业务协助。\"}],\"footer\":{\"slogan\":\"高速流量卡服务平台\"}}', 1, 1781462176);
INSERT INTO `site_page_versions` VALUES (5, 1, 6, '{\"entry\":{\"mode\":\"site\",\"pcUrl\":\"/#/agent\",\"mobileUrl\":\"/#/h5\"},\"nav\":{\"homeLink\":\"\",\"productText\":\"产品中心\",\"productLink\":\"#products\",\"platformText\":\"平台介绍\",\"platformLink\":\"#platform\",\"omniText\":\"多端互通\",\"omniLink\":\"#omni\",\"agencyText\":\"代理加盟\",\"agencyLink\":\"#agency\",\"agentLoginText\":\"代理登录\",\"agentLoginLink\":\"/#/agent\"},\"hero\":{\"eyebrow\":\"5G DATA CARD PLATFORM\",\"title\":\"优选流量卡\\n在线办理更省心\",\"lead\":\"精选多运营商套餐，流量、月租、通话等信息清晰展示，在线查看产品、提交办理、查询进度，满足日常上网与移动办公需求。\",\"primaryText\":\"查看产品中心\",\"primaryLink\":\"#products\",\"secondaryText\":\"了解代理加盟\",\"secondaryLink\":\"#agency\",\"image\":\"\"},\"visible\":{\"hero\":true,\"highlights\":true,\"platform\":true,\"core\":false,\"omni\":true,\"products\":true,\"agency\":true},\"highlights\":[{\"title\":\"套餐信息清晰\",\"desc\":\"流量、月租、通话等核心信息一目了然。\",\"target\":\"#products\"},{\"title\":\"在线便捷办理\",\"desc\":\"查看产品详情后，可进入对应办理流程。\",\"target\":\"#products\"},{\"title\":\"订单进度可查\",\"desc\":\"提交后可查询办理状态，减少等待焦虑。\",\"target\":\"#platform\"},{\"title\":\"代理合作支持\",\"desc\":\"为合作伙伴提供产品资料和推广服务。\",\"target\":\"#agency\"},{\"title\":\"产品持续更新\",\"desc\":\"套餐资料持续维护，便于获取新选择。\",\"target\":\"#products\"},{\"title\":\"多端访问顺畅\",\"desc\":\"电脑端、移动 H5、小程序与 APP 多端查看。\",\"target\":\"#omni\"}],\"sections\":{\"platform\":{\"title\":\"可靠产品，便捷服务\",\"desc\":\"围绕用户办卡、用卡、查单和咨询的真实需求，提供清晰易懂的服务体验。\"},\"core\":{\"title\":\"流量卡服务核心特点\",\"desc\":\"从产品展示、在线办理到订单跟进和服务支持，让办卡流程更清楚、更省心。\"},\"omni\":{\"title\":\"多平台互通互联\",\"desc\":\"电脑端、移动 H5、小程序与 APP 多端联动，用户查看更方便，合作伙伴服务更顺手。\"},\"products\":{\"eyebrow\":\"产品中心\",\"title\":\"精选流量卡产品\",\"desc\":\"展示套餐名称、运营商、流量、月租、通话等关键信息，方便快速了解并选择适合自己的产品。\",\"buttonText\":\"进入产品中心\",\"buttonLink\":\"/#/product\"},\"agency\":{\"eyebrow\":\"代理加盟\",\"title\":\"携手合作，共同拓展流量卡服务市场\",\"desc\":\"适合通信门店、社群团长、地推团队和线上渠道合作，平台提供产品资料、办理入口、订单查询与服务支持。\",\"buttonText\":\"代理登录\",\"buttonLink\":\"/#/agent\"}},\"capabilities\":[{\"icon\":\"ri:search-eye-line\",\"title\":\"查产品\",\"desc\":\"套餐名称、运营商、月租和流量信息集中呈现，用户可快速了解产品重点。\",\"points\":[\"套餐名称\",\"运营商\",\"月租信息\",\"流量信息\"]},{\"icon\":\"ri:smartphone-line\",\"title\":\"办套餐\",\"desc\":\"从查看产品到资料提交都有清晰指引，让用户在线办理路径更顺畅。\",\"points\":[\"在线查看\",\"快速选择\",\"资料提交\",\"办理指引\"]},{\"icon\":\"ri:file-search-line\",\"title\":\"查进度\",\"desc\":\"提交后可查看办理进度和结果提醒，减少反复咨询和等待焦虑。\",\"points\":[\"订单查询\",\"状态跟进\",\"资料补充\",\"结果提醒\"]},{\"icon\":\"ri:customer-service-2-line\",\"title\":\"享服务\",\"desc\":\"咨询、协助、办理说明和售后支持集中承接，用户遇到问题可及时处理。\",\"points\":[\"客服咨询\",\"问题协助\",\"办理说明\",\"售后支持\"]},{\"icon\":\"ri:team-line\",\"title\":\"代理合作\",\"desc\":\"为合作伙伴提供产品资料、推广入口和渠道服务，方便拓展客户。\",\"points\":[\"合作入口\",\"产品资料\",\"推广支持\",\"渠道服务\"]},{\"icon\":\"ri:shield-check-line\",\"title\":\"放心选择\",\"desc\":\"正规产品、信息透明、流程清楚，让用户选择和办理都更踏实。\",\"points\":[\"正规产品\",\"信息透明\",\"流程清晰\",\"服务可达\"]}],\"coreFeatures\":[{\"title\":\"优选套餐\",\"desc\":\"聚合不同使用场景的流量卡产品，方便快速筛选。\"},{\"title\":\"在线查看\",\"desc\":\"产品图片、套餐信息和办理说明集中展示。\"},{\"title\":\"快速办理\",\"desc\":\"用户可从产品详情进入办理流程，路径更清楚。\"},{\"title\":\"多场景适用\",\"desc\":\"覆盖日常上网、短视频、办公出行和备用流量。\"},{\"title\":\"进度可查\",\"desc\":\"提交后可通过查询入口了解办理状态。\"},{\"title\":\"资料提醒\",\"desc\":\"需要补充资料时，用户可按指引继续处理。\"},{\"title\":\"代理协作\",\"desc\":\"合作伙伴可通过专属入口服务自己的客户。\"},{\"title\":\"分享推广\",\"desc\":\"适合社群、门店、朋友圈和线上渠道传播。\"},{\"title\":\"客户服务\",\"desc\":\"提供咨询与协助入口，处理办理和查询问题。\"},{\"title\":\"信息透明\",\"desc\":\"关键套餐信息清楚展示，减少反复沟通。\"},{\"title\":\"移动适配\",\"desc\":\"手机端查看、分享和提交更顺手。\"},{\"title\":\"持续更新\",\"desc\":\"产品资料持续维护，便于用户获取新套餐。\"}],\"omniPlatforms\":[{\"icon\":\"ri:computer-line\",\"title\":\"电脑端\",\"desc\":\"品牌展示、产品中心、平台介绍和代理加盟统一承载。\",\"image\":\"\"},{\"icon\":\"ri:html5-line\",\"title\":\"移动 H5\",\"desc\":\"手机浏览更轻便，适合从社群、短信和短视频场景访问。\",\"image\":\"\"},{\"icon\":\"ri:wechat-line\",\"title\":\"小程序\",\"desc\":\"适合通过微信场景分享产品、承接咨询和办理线索。\",\"image\":\"\"},{\"icon\":\"ri:app-store-line\",\"title\":\"APP\",\"desc\":\"适合沉淀常用入口，支持产品查看、分享推广和客户服务。\",\"image\":\"\"}],\"agencySteps\":[{\"icon\":\"ri:user-add-line\",\"title\":\"开通合作账号\",\"desc\":\"获得专属代理入口，方便查看产品和服务客户。\"},{\"icon\":\"ri:share-forward-line\",\"title\":\"分享产品链接\",\"desc\":\"适合社群、门店、朋友圈和线上渠道推广。\"},{\"icon\":\"ri:file-search-line\",\"title\":\"跟进订单状态\",\"desc\":\"及时查看客户办理状态，提升服务体验。\"},{\"icon\":\"ri:customer-service-2-line\",\"title\":\"获得服务支持\",\"desc\":\"平台持续提供产品资料和业务协助。\"}],\"footer\":{\"slogan\":\"高速流量卡服务平台\"}}', 1, 1781463663);
INSERT INTO `site_page_versions` VALUES (6, 1, 7, '{\"entry\":{\"mode\":\"site\",\"pcUrl\":\"/#/agent\",\"mobileUrl\":\"/#/h5\"},\"nav\":{\"homeLink\":\"\",\"productText\":\"产品中心\",\"productLink\":\"#products\",\"platformText\":\"平台介绍\",\"platformLink\":\"#platform\",\"omniText\":\"多端互通\",\"omniLink\":\"#omni\",\"agencyText\":\"代理加盟\",\"agencyLink\":\"#agency\",\"agentLoginText\":\"代理登录\",\"agentLoginLink\":\"/#/agent\"},\"hero\":{\"eyebrow\":\"5G DATA CARD PLATFORM\",\"title\":\"优选流量卡\\n在线办理更省心\",\"lead\":\"精选多运营商套餐，流量、月租、通话等信息清晰展示，在线查看产品、提交办理、查询进度，满足日常上网与移动办公需求。\",\"primaryText\":\"查看产品中心\",\"primaryLink\":\"#products\",\"secondaryText\":\"了解代理加盟\",\"secondaryLink\":\"#agency\",\"image\":\"\"},\"visible\":{\"hero\":true,\"highlights\":true,\"platform\":true,\"core\":true,\"omni\":true,\"products\":true,\"agency\":true},\"highlights\":[{\"title\":\"套餐信息清晰\",\"desc\":\"流量、月租、通话等核心信息一目了然。\",\"target\":\"#products\"},{\"title\":\"在线便捷办理\",\"desc\":\"查看产品详情后，可进入对应办理流程。\",\"target\":\"#products\"},{\"title\":\"订单进度可查\",\"desc\":\"提交后可查询办理状态，减少等待焦虑。\",\"target\":\"#platform\"},{\"title\":\"代理合作支持\",\"desc\":\"为合作伙伴提供产品资料和推广服务。\",\"target\":\"#agency\"},{\"title\":\"产品持续更新\",\"desc\":\"套餐资料持续维护，便于获取新选择。\",\"target\":\"#products\"},{\"title\":\"多端访问顺畅\",\"desc\":\"电脑端、移动 H5、小程序与 APP 多端查看。\",\"target\":\"#omni\"}],\"sections\":{\"platform\":{\"title\":\"可靠产品，便捷服务\",\"desc\":\"围绕用户办卡、用卡、查单和咨询的真实需求，提供清晰易懂的服务体验。\"},\"core\":{\"title\":\"流量卡服务核心特点\",\"desc\":\"从产品展示、在线办理到订单跟进和服务支持，让办卡流程更清楚、更省心。\"},\"omni\":{\"title\":\"多平台互通互联\",\"desc\":\"电脑端、移动 H5、小程序与 APP 多端联动，用户查看更方便，合作伙伴服务更顺手。\"},\"products\":{\"eyebrow\":\"产品中心\",\"title\":\"精选流量卡产品\",\"desc\":\"展示套餐名称、运营商、流量、月租、通话等关键信息，方便快速了解并选择适合自己的产品。\",\"buttonText\":\"进入产品中心\",\"buttonLink\":\"/#/product\"},\"agency\":{\"eyebrow\":\"代理加盟\",\"title\":\"携手合作，共同拓展流量卡服务市场\",\"desc\":\"适合通信门店、社群团长、地推团队和线上渠道合作，平台提供产品资料、办理入口、订单查询与服务支持。\",\"buttonText\":\"代理登录\",\"buttonLink\":\"/#/agent\"}},\"capabilities\":[{\"icon\":\"ri:search-eye-line\",\"title\":\"查产品\",\"desc\":\"套餐名称、运营商、月租和流量信息集中呈现，用户可快速了解产品重点。\",\"points\":[\"套餐名称\",\"运营商\",\"月租信息\",\"流量信息\"]},{\"icon\":\"ri:smartphone-line\",\"title\":\"办套餐\",\"desc\":\"从查看产品到资料提交都有清晰指引，让用户在线办理路径更顺畅。\",\"points\":[\"在线查看\",\"快速选择\",\"资料提交\",\"办理指引\"]},{\"icon\":\"ri:file-search-line\",\"title\":\"查进度\",\"desc\":\"提交后可查看办理进度和结果提醒，减少反复咨询和等待焦虑。\",\"points\":[\"订单查询\",\"状态跟进\",\"资料补充\",\"结果提醒\"]},{\"icon\":\"ri:customer-service-2-line\",\"title\":\"享服务\",\"desc\":\"咨询、协助、办理说明和售后支持集中承接，用户遇到问题可及时处理。\",\"points\":[\"客服咨询\",\"问题协助\",\"办理说明\",\"售后支持\"]},{\"icon\":\"ri:team-line\",\"title\":\"代理合作\",\"desc\":\"为合作伙伴提供产品资料、推广入口和渠道服务，方便拓展客户。\",\"points\":[\"合作入口\",\"产品资料\",\"推广支持\",\"渠道服务\"]},{\"icon\":\"ri:shield-check-line\",\"title\":\"放心选择\",\"desc\":\"正规产品、信息透明、流程清楚，让用户选择和办理都更踏实。\",\"points\":[\"正规产品\",\"信息透明\",\"流程清晰\",\"服务可达\"]}],\"coreFeatures\":[{\"title\":\"优选套餐\",\"desc\":\"聚合不同使用场景的流量卡产品，方便快速筛选。\"},{\"title\":\"在线查看\",\"desc\":\"产品图片、套餐信息和办理说明集中展示。\"},{\"title\":\"快速办理\",\"desc\":\"用户可从产品详情进入办理流程，路径更清楚。\"},{\"title\":\"多场景适用\",\"desc\":\"覆盖日常上网、短视频、办公出行和备用流量。\"},{\"title\":\"进度可查\",\"desc\":\"提交后可通过查询入口了解办理状态。\"},{\"title\":\"资料提醒\",\"desc\":\"需要补充资料时，用户可按指引继续处理。\"},{\"title\":\"代理协作\",\"desc\":\"合作伙伴可通过专属入口服务自己的客户。\"},{\"title\":\"分享推广\",\"desc\":\"适合社群、门店、朋友圈和线上渠道传播。\"},{\"title\":\"客户服务\",\"desc\":\"提供咨询与协助入口，处理办理和查询问题。\"},{\"title\":\"信息透明\",\"desc\":\"关键套餐信息清楚展示，减少反复沟通。\"},{\"title\":\"移动适配\",\"desc\":\"手机端查看、分享和提交更顺手。\"},{\"title\":\"持续更新\",\"desc\":\"产品资料持续维护，便于用户获取新套餐。\"}],\"omniPlatforms\":[{\"icon\":\"ri:computer-line\",\"title\":\"电脑端\",\"desc\":\"品牌展示、产品中心、平台介绍和代理加盟统一承载。\",\"image\":\"\"},{\"icon\":\"ri:html5-line\",\"title\":\"移动 H5\",\"desc\":\"手机浏览更轻便，适合从社群、短信和短视频场景访问。\",\"image\":\"\"},{\"icon\":\"ri:wechat-line\",\"title\":\"小程序\",\"desc\":\"适合通过微信场景分享产品、承接咨询和办理线索。\",\"image\":\"\"},{\"icon\":\"ri:app-store-line\",\"title\":\"APP\",\"desc\":\"适合沉淀常用入口，支持产品查看、分享推广和客户服务。\",\"image\":\"\"}],\"agencySteps\":[{\"icon\":\"ri:user-add-line\",\"title\":\"开通合作账号\",\"desc\":\"获得专属代理入口，方便查看产品和服务客户。\"},{\"icon\":\"ri:share-forward-line\",\"title\":\"分享产品链接\",\"desc\":\"适合社群、门店、朋友圈和线上渠道推广。\"},{\"icon\":\"ri:file-search-line\",\"title\":\"跟进订单状态\",\"desc\":\"及时查看客户办理状态，提升服务体验。\"},{\"icon\":\"ri:customer-service-2-line\",\"title\":\"获得服务支持\",\"desc\":\"平台持续提供产品资料和业务协助。\"}],\"footer\":{\"slogan\":\"高速流量卡服务平台\"}}', 1, 1781463674);
INSERT INTO `site_page_versions` VALUES (7, 1, 8, '{\"entry\":{\"mode\":\"site\",\"pcUrl\":\"/#/agent\",\"mobileUrl\":\"/#/h5\"},\"nav\":{\"homeLink\":\"\",\"productText\":\"产品中心\",\"productLink\":\"#products\",\"platformText\":\"平台介绍\",\"platformLink\":\"#platform\",\"omniText\":\"多端互通\",\"omniLink\":\"#omni\",\"agencyText\":\"代理加盟\",\"agencyLink\":\"#agency\",\"agentLoginText\":\"代理登录\",\"agentLoginLink\":\"/#/agent\"},\"hero\":{\"eyebrow\":\"5G DATA CARD PLATFORM\",\"title\":\"优选流量卡\\n在线办理更省心\",\"lead\":\"精选多运营商套餐，流量、月租、通话等信息清晰展示，在线查看产品、提交办理、查询进度，满足日常上网与移动办公需求。\",\"primaryText\":\"查看产品中心\",\"primaryLink\":\"#products\",\"secondaryText\":\"了解代理加盟\",\"secondaryLink\":\"#agency\",\"image\":\"\"},\"visible\":{\"hero\":true,\"highlights\":true,\"platform\":false,\"core\":true,\"omni\":true,\"products\":true,\"agency\":true},\"highlights\":[{\"title\":\"套餐信息清晰\",\"desc\":\"流量、月租、通话等核心信息一目了然。\",\"target\":\"#products\"},{\"title\":\"在线便捷办理\",\"desc\":\"查看产品详情后，可进入对应办理流程。\",\"target\":\"#products\"},{\"title\":\"订单进度可查\",\"desc\":\"提交后可查询办理状态，减少等待焦虑。\",\"target\":\"#platform\"},{\"title\":\"代理合作支持\",\"desc\":\"为合作伙伴提供产品资料和推广服务。\",\"target\":\"#agency\"},{\"title\":\"产品持续更新\",\"desc\":\"套餐资料持续维护，便于获取新选择。\",\"target\":\"#products\"},{\"title\":\"多端访问顺畅\",\"desc\":\"电脑端、移动 H5、小程序与 APP 多端查看。\",\"target\":\"#omni\"}],\"sections\":{\"platform\":{\"title\":\"可靠产品，便捷服务\",\"desc\":\"围绕用户办卡、用卡、查单和咨询的真实需求，提供清晰易懂的服务体验。\"},\"core\":{\"title\":\"流量卡服务核心特点\",\"desc\":\"从产品展示、在线办理到订单跟进和服务支持，让办卡流程更清楚、更省心。\"},\"omni\":{\"title\":\"多平台互通互联\",\"desc\":\"电脑端、移动 H5、小程序与 APP 多端联动，用户查看更方便，合作伙伴服务更顺手。\"},\"products\":{\"eyebrow\":\"产品中心\",\"title\":\"精选流量卡产品\",\"desc\":\"展示套餐名称、运营商、流量、月租、通话等关键信息，方便快速了解并选择适合自己的产品。\",\"buttonText\":\"进入产品中心\",\"buttonLink\":\"/#/product\"},\"agency\":{\"eyebrow\":\"代理加盟\",\"title\":\"携手合作，共同拓展流量卡服务市场\",\"desc\":\"适合通信门店、社群团长、地推团队和线上渠道合作，平台提供产品资料、办理入口、订单查询与服务支持。\",\"buttonText\":\"代理登录\",\"buttonLink\":\"/#/agent\"}},\"capabilities\":[{\"icon\":\"ri:search-eye-line\",\"title\":\"查产品\",\"desc\":\"套餐名称、运营商、月租和流量信息集中呈现，用户可快速了解产品重点。\",\"points\":[\"套餐名称\",\"运营商\",\"月租信息\",\"流量信息\"]},{\"icon\":\"ri:smartphone-line\",\"title\":\"办套餐\",\"desc\":\"从查看产品到资料提交都有清晰指引，让用户在线办理路径更顺畅。\",\"points\":[\"在线查看\",\"快速选择\",\"资料提交\",\"办理指引\"]},{\"icon\":\"ri:file-search-line\",\"title\":\"查进度\",\"desc\":\"提交后可查看办理进度和结果提醒，减少反复咨询和等待焦虑。\",\"points\":[\"订单查询\",\"状态跟进\",\"资料补充\",\"结果提醒\"]},{\"icon\":\"ri:customer-service-2-line\",\"title\":\"享服务\",\"desc\":\"咨询、协助、办理说明和售后支持集中承接，用户遇到问题可及时处理。\",\"points\":[\"客服咨询\",\"问题协助\",\"办理说明\",\"售后支持\"]},{\"icon\":\"ri:team-line\",\"title\":\"代理合作\",\"desc\":\"为合作伙伴提供产品资料、推广入口和渠道服务，方便拓展客户。\",\"points\":[\"合作入口\",\"产品资料\",\"推广支持\",\"渠道服务\"]},{\"icon\":\"ri:shield-check-line\",\"title\":\"放心选择\",\"desc\":\"正规产品、信息透明、流程清楚，让用户选择和办理都更踏实。\",\"points\":[\"正规产品\",\"信息透明\",\"流程清晰\",\"服务可达\"]}],\"coreFeatures\":[{\"title\":\"优选套餐\",\"desc\":\"聚合不同使用场景的流量卡产品，方便快速筛选。\"},{\"title\":\"在线查看\",\"desc\":\"产品图片、套餐信息和办理说明集中展示。\"},{\"title\":\"快速办理\",\"desc\":\"用户可从产品详情进入办理流程，路径更清楚。\"},{\"title\":\"多场景适用\",\"desc\":\"覆盖日常上网、短视频、办公出行和备用流量。\"},{\"title\":\"进度可查\",\"desc\":\"提交后可通过查询入口了解办理状态。\"},{\"title\":\"资料提醒\",\"desc\":\"需要补充资料时，用户可按指引继续处理。\"},{\"title\":\"代理协作\",\"desc\":\"合作伙伴可通过专属入口服务自己的客户。\"},{\"title\":\"分享推广\",\"desc\":\"适合社群、门店、朋友圈和线上渠道传播。\"},{\"title\":\"客户服务\",\"desc\":\"提供咨询与协助入口，处理办理和查询问题。\"},{\"title\":\"信息透明\",\"desc\":\"关键套餐信息清楚展示，减少反复沟通。\"},{\"title\":\"移动适配\",\"desc\":\"手机端查看、分享和提交更顺手。\"},{\"title\":\"持续更新\",\"desc\":\"产品资料持续维护，便于用户获取新套餐。\"}],\"omniPlatforms\":[{\"icon\":\"ri:computer-line\",\"title\":\"电脑端\",\"desc\":\"品牌展示、产品中心、平台介绍和代理加盟统一承载。\",\"image\":\"\"},{\"icon\":\"ri:html5-line\",\"title\":\"移动 H5\",\"desc\":\"手机浏览更轻便，适合从社群、短信和短视频场景访问。\",\"image\":\"\"},{\"icon\":\"ri:wechat-line\",\"title\":\"小程序\",\"desc\":\"适合通过微信场景分享产品、承接咨询和办理线索。\",\"image\":\"\"},{\"icon\":\"ri:app-store-line\",\"title\":\"APP\",\"desc\":\"适合沉淀常用入口，支持产品查看、分享推广和客户服务。\",\"image\":\"\"}],\"agencySteps\":[{\"icon\":\"ri:user-add-line\",\"title\":\"开通合作账号\",\"desc\":\"获得专属代理入口，方便查看产品和服务客户。\"},{\"icon\":\"ri:share-forward-line\",\"title\":\"分享产品链接\",\"desc\":\"适合社群、门店、朋友圈和线上渠道推广。\"},{\"icon\":\"ri:file-search-line\",\"title\":\"跟进订单状态\",\"desc\":\"及时查看客户办理状态，提升服务体验。\"},{\"icon\":\"ri:customer-service-2-line\",\"title\":\"获得服务支持\",\"desc\":\"平台持续提供产品资料和业务协助。\"}],\"footer\":{\"slogan\":\"高速流量卡服务平台\"}}', 1, 1781463681);
INSERT INTO `site_page_versions` VALUES (8, 1, 9, '{\"entry\":{\"mode\":\"site\",\"pcUrl\":\"/#/agent\",\"mobileUrl\":\"/#/h5\"},\"nav\":{\"homeLink\":\"\",\"productText\":\"产品中心\",\"productLink\":\"#products\",\"platformText\":\"平台介绍\",\"platformLink\":\"#platform\",\"omniText\":\"多端互通\",\"omniLink\":\"#omni\",\"agencyText\":\"代理加盟\",\"agencyLink\":\"#agency\",\"agentLoginText\":\"代理登录\",\"agentLoginLink\":\"/#/agent\"},\"hero\":{\"eyebrow\":\"5G DATA CARD PLATFORM\",\"title\":\"优选流量卡\\n在线办理更省心\",\"lead\":\"精选多运营商套餐，流量、月租、通话等信息清晰展示，在线查看产品、提交办理、查询进度，满足日常上网与移动办公需求。\",\"primaryText\":\"查看产品中心\",\"primaryLink\":\"#products\",\"secondaryText\":\"了解代理加盟\",\"secondaryLink\":\"#agency\",\"image\":\"\"},\"visible\":{\"hero\":true,\"highlights\":true,\"platform\":true,\"core\":true,\"omni\":true,\"products\":true,\"agency\":true},\"highlights\":[{\"title\":\"套餐信息清晰\",\"desc\":\"流量、月租、通话等核心信息一目了然。\",\"target\":\"#products\"},{\"title\":\"在线便捷办理\",\"desc\":\"查看产品详情后，可进入对应办理流程。\",\"target\":\"#products\"},{\"title\":\"订单进度可查\",\"desc\":\"提交后可查询办理状态，减少等待焦虑。\",\"target\":\"#platform\"},{\"title\":\"代理合作支持\",\"desc\":\"为合作伙伴提供产品资料和推广服务。\",\"target\":\"#agency\"},{\"title\":\"产品持续更新\",\"desc\":\"套餐资料持续维护，便于获取新选择。\",\"target\":\"#products\"},{\"title\":\"多端访问顺畅\",\"desc\":\"电脑端、移动 H5、小程序与 APP 多端查看。\",\"target\":\"#omni\"}],\"sections\":{\"platform\":{\"title\":\"可靠产品，便捷服务\",\"desc\":\"围绕用户办卡、用卡、查单和咨询的真实需求，提供清晰易懂的服务体验。\"},\"core\":{\"title\":\"流量卡服务核心特点\",\"desc\":\"从产品展示、在线办理到订单跟进和服务支持，让办卡流程更清楚、更省心。\"},\"omni\":{\"title\":\"多平台互通互联\",\"desc\":\"电脑端、移动 H5、小程序与 APP 多端联动，用户查看更方便，合作伙伴服务更顺手。\"},\"products\":{\"eyebrow\":\"产品中心\",\"title\":\"精选流量卡产品\",\"desc\":\"展示套餐名称、运营商、流量、月租、通话等关键信息，方便快速了解并选择适合自己的产品。\",\"buttonText\":\"进入产品中心\",\"buttonLink\":\"/#/product\"},\"agency\":{\"eyebrow\":\"代理加盟\",\"title\":\"携手合作，共同拓展流量卡服务市场\",\"desc\":\"适合通信门店、社群团长、地推团队和线上渠道合作，平台提供产品资料、办理入口、订单查询与服务支持。\",\"buttonText\":\"代理登录\",\"buttonLink\":\"/#/agent\"}},\"capabilities\":[{\"icon\":\"ri:search-eye-line\",\"title\":\"查产品\",\"desc\":\"套餐名称、运营商、月租和流量信息集中呈现，用户可快速了解产品重点。\",\"points\":[\"套餐名称\",\"运营商\",\"月租信息\",\"流量信息\"]},{\"icon\":\"ri:smartphone-line\",\"title\":\"办套餐\",\"desc\":\"从查看产品到资料提交都有清晰指引，让用户在线办理路径更顺畅。\",\"points\":[\"在线查看\",\"快速选择\",\"资料提交\",\"办理指引\"]},{\"icon\":\"ri:file-search-line\",\"title\":\"查进度\",\"desc\":\"提交后可查看办理进度和结果提醒，减少反复咨询和等待焦虑。\",\"points\":[\"订单查询\",\"状态跟进\",\"资料补充\",\"结果提醒\"]},{\"icon\":\"ri:customer-service-2-line\",\"title\":\"享服务\",\"desc\":\"咨询、协助、办理说明和售后支持集中承接，用户遇到问题可及时处理。\",\"points\":[\"客服咨询\",\"问题协助\",\"办理说明\",\"售后支持\"]},{\"icon\":\"ri:team-line\",\"title\":\"代理合作\",\"desc\":\"为合作伙伴提供产品资料、推广入口和渠道服务，方便拓展客户。\",\"points\":[\"合作入口\",\"产品资料\",\"推广支持\",\"渠道服务\"]},{\"icon\":\"ri:shield-check-line\",\"title\":\"放心选择\",\"desc\":\"正规产品、信息透明、流程清楚，让用户选择和办理都更踏实。\",\"points\":[\"正规产品\",\"信息透明\",\"流程清晰\",\"服务可达\"]}],\"coreFeatures\":[{\"title\":\"优选套餐\",\"desc\":\"聚合不同使用场景的流量卡产品，方便快速筛选。\"},{\"title\":\"在线查看\",\"desc\":\"产品图片、套餐信息和办理说明集中展示。\"},{\"title\":\"快速办理\",\"desc\":\"用户可从产品详情进入办理流程，路径更清楚。\"},{\"title\":\"多场景适用\",\"desc\":\"覆盖日常上网、短视频、办公出行和备用流量。\"},{\"title\":\"进度可查\",\"desc\":\"提交后可通过查询入口了解办理状态。\"},{\"title\":\"资料提醒\",\"desc\":\"需要补充资料时，用户可按指引继续处理。\"},{\"title\":\"代理协作\",\"desc\":\"合作伙伴可通过专属入口服务自己的客户。\"},{\"title\":\"分享推广\",\"desc\":\"适合社群、门店、朋友圈和线上渠道传播。\"},{\"title\":\"客户服务\",\"desc\":\"提供咨询与协助入口，处理办理和查询问题。\"},{\"title\":\"信息透明\",\"desc\":\"关键套餐信息清楚展示，减少反复沟通。\"},{\"title\":\"移动适配\",\"desc\":\"手机端查看、分享和提交更顺手。\"},{\"title\":\"持续更新\",\"desc\":\"产品资料持续维护，便于用户获取新套餐。\"}],\"omniPlatforms\":[{\"icon\":\"ri:computer-line\",\"title\":\"电脑端\",\"desc\":\"品牌展示、产品中心、平台介绍和代理加盟统一承载。\",\"image\":\"\"},{\"icon\":\"ri:html5-line\",\"title\":\"移动 H5\",\"desc\":\"手机浏览更轻便，适合从社群、短信和短视频场景访问。\",\"image\":\"\"},{\"icon\":\"ri:wechat-line\",\"title\":\"小程序\",\"desc\":\"适合通过微信场景分享产品、承接咨询和办理线索。\",\"image\":\"\"},{\"icon\":\"ri:app-store-line\",\"title\":\"APP\",\"desc\":\"适合沉淀常用入口，支持产品查看、分享推广和客户服务。\",\"image\":\"\"}],\"agencySteps\":[{\"icon\":\"ri:user-add-line\",\"title\":\"开通合作账号\",\"desc\":\"获得专属代理入口，方便查看产品和服务客户。\"},{\"icon\":\"ri:share-forward-line\",\"title\":\"分享产品链接\",\"desc\":\"适合社群、门店、朋友圈和线上渠道推广。\"},{\"icon\":\"ri:file-search-line\",\"title\":\"跟进订单状态\",\"desc\":\"及时查看客户办理状态，提升服务体验。\"},{\"icon\":\"ri:customer-service-2-line\",\"title\":\"获得服务支持\",\"desc\":\"平台持续提供产品资料和业务协助。\"}],\"footer\":{\"slogan\":\"高速流量卡服务平台\"}}', 1, 1781463689);

-- ----------------------------
-- Table structure for site_pages
-- ----------------------------
DROP TABLE IF EXISTS `site_pages`;
CREATE TABLE `site_pages`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `page_key` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `page_name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `draft_content_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `published_content_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `seo_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `seo_keywords` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `seo_description` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `version` int(11) NOT NULL DEFAULT 1,
  `create_time` int(11) NOT NULL DEFAULT 0,
  `update_time` int(11) NOT NULL DEFAULT 0,
  `published_at` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uniq_page_key`(`page_key`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of site_pages
-- ----------------------------
INSERT INTO `site_pages` VALUES (1, 'home', '官网首页', '{\"entry\":{\"mode\":\"site\",\"pcUrl\":\"/#/agent\",\"mobileUrl\":\"/#/h5\"},\"nav\":{\"homeLink\":\"\",\"productText\":\"产品中心\",\"productLink\":\"#products\",\"platformText\":\"平台介绍\",\"platformLink\":\"#platform\",\"omniText\":\"多端互通\",\"omniLink\":\"#omni\",\"agencyText\":\"代理加盟\",\"agencyLink\":\"#agency\",\"agentLoginText\":\"代理登录\",\"agentLoginLink\":\"/#/agent\"},\"hero\":{\"eyebrow\":\"5G DATA CARD PLATFORM\",\"title\":\"优选流量卡\\n在线办理更省心\",\"lead\":\"精选多运营商套餐，流量、月租、通话等信息清晰展示，在线查看产品、提交办理、查询进度，满足日常上网与移动办公需求。\",\"primaryText\":\"查看产品中心\",\"primaryLink\":\"#products\",\"secondaryText\":\"了解代理加盟\",\"secondaryLink\":\"#agency\",\"image\":\"\"},\"visible\":{\"hero\":true,\"highlights\":true,\"platform\":true,\"core\":true,\"omni\":true,\"products\":true,\"agency\":true},\"highlights\":[{\"title\":\"套餐信息清晰\",\"desc\":\"流量、月租、通话等核心信息一目了然。\",\"target\":\"#products\"},{\"title\":\"在线便捷办理\",\"desc\":\"查看产品详情后，可进入对应办理流程。\",\"target\":\"#products\"},{\"title\":\"订单进度可查\",\"desc\":\"提交后可查询办理状态，减少等待焦虑。\",\"target\":\"#platform\"},{\"title\":\"代理合作支持\",\"desc\":\"为合作伙伴提供产品资料和推广服务。\",\"target\":\"#agency\"}],\"sections\":{\"platform\":{\"title\":\"可靠产品，便捷服务\",\"desc\":\"围绕用户办卡、用卡、查单和咨询的真实需求，提供清晰易懂的服务体验。\"},\"core\":{\"title\":\"流量卡服务核心特点\",\"desc\":\"从产品展示、在线办理到订单跟进和服务支持，让办卡流程更清楚、更省心。\"},\"omni\":{\"title\":\"多平台互通互联\",\"desc\":\"电脑端、移动 H5、小程序与 APP 多端联动，用户查看更方便，合作伙伴服务更顺手。\"},\"products\":{\"eyebrow\":\"产品中心\",\"title\":\"精选流量卡产品\",\"desc\":\"展示套餐名称、运营商、流量、月租、通话等关键信息，方便快速了解并选择适合自己的产品。\",\"buttonText\":\"进入产品中心\",\"buttonLink\":\"/#/product\"},\"agency\":{\"eyebrow\":\"代理加盟\",\"title\":\"携手合作，共同拓展流量卡服务市场\",\"desc\":\"适合通信门店、社群团长、地推团队和线上渠道合作，平台提供产品资料、办理入口、订单查询与服务支持。\",\"buttonText\":\"代理登录\",\"buttonLink\":\"/#/agent\"}},\"capabilities\":[{\"icon\":\"ri:search-eye-line\",\"title\":\"查产品\",\"desc\":\"套餐名称、运营商、月租和流量信息集中呈现，用户可快速了解产品重点。\",\"points\":[\"套餐名称\",\"运营商\",\"月租信息\",\"流量信息\"]},{\"icon\":\"ri:smartphone-line\",\"title\":\"办套餐\",\"desc\":\"从查看产品到资料提交都有清晰指引，让用户在线办理路径更顺畅。\",\"points\":[\"在线查看\",\"快速选择\",\"资料提交\",\"办理指引\"]},{\"icon\":\"ri:file-search-line\",\"title\":\"查进度\",\"desc\":\"提交后可查看办理进度和结果提醒，减少反复咨询和等待焦虑。\",\"points\":[\"订单查询\",\"状态跟进\",\"资料补充\",\"结果提醒\"]},{\"icon\":\"ri:customer-service-2-line\",\"title\":\"享服务\",\"desc\":\"咨询、协助、办理说明和售后支持集中承接，用户遇到问题可及时处理。\",\"points\":[\"客服咨询\",\"问题协助\",\"办理说明\",\"售后支持\"]},{\"icon\":\"ri:team-line\",\"title\":\"代理合作\",\"desc\":\"为合作伙伴提供产品资料、推广入口和渠道服务，方便拓展客户。\",\"points\":[\"合作入口\",\"产品资料\",\"推广支持\",\"渠道服务\"]},{\"icon\":\"ri:shield-check-line\",\"title\":\"放心选择\",\"desc\":\"正规产品、信息透明、流程清楚，让用户选择和办理都更踏实。\",\"points\":[\"正规产品\",\"信息透明\",\"流程清晰\",\"服务可达\"]}],\"coreFeatures\":[{\"title\":\"优选套餐\",\"desc\":\"聚合不同使用场景的流量卡产品，方便快速筛选。\"},{\"title\":\"在线查看\",\"desc\":\"产品图片、套餐信息和办理说明集中展示。\"},{\"title\":\"快速办理\",\"desc\":\"用户可从产品详情进入办理流程，路径更清楚。\"},{\"title\":\"多场景适用\",\"desc\":\"覆盖日常上网、短视频、办公出行和备用流量。\"},{\"title\":\"进度可查\",\"desc\":\"提交后可通过查询入口了解办理状态。\"},{\"title\":\"资料提醒\",\"desc\":\"需要补充资料时，用户可按指引继续处理。\"},{\"title\":\"代理协作\",\"desc\":\"合作伙伴可通过专属入口服务自己的客户。\"},{\"title\":\"分享推广\",\"desc\":\"适合社群、门店、朋友圈和线上渠道传播。\"},{\"title\":\"客户服务\",\"desc\":\"提供咨询与协助入口，处理办理和查询问题。\"},{\"title\":\"信息透明\",\"desc\":\"关键套餐信息清楚展示，减少反复沟通。\"},{\"title\":\"移动适配\",\"desc\":\"手机端查看、分享和提交更顺手。\"},{\"title\":\"持续更新\",\"desc\":\"产品资料持续维护，便于用户获取新套餐。\"}],\"omniPlatforms\":[{\"icon\":\"ri:computer-line\",\"title\":\"电脑端\",\"desc\":\"品牌展示、产品中心、平台介绍和代理加盟统一承载。\",\"image\":\"\"},{\"icon\":\"ri:html5-line\",\"title\":\"移动 H5\",\"desc\":\"手机浏览更轻便，适合从社群、短信和短视频场景访问。\",\"image\":\"\"},{\"icon\":\"ri:wechat-line\",\"title\":\"小程序\",\"desc\":\"适合通过微信场景分享产品、承接咨询和办理线索。\",\"image\":\"\"},{\"icon\":\"ri:app-store-line\",\"title\":\"APP\",\"desc\":\"适合沉淀常用入口，支持产品查看、分享推广和客户服务。\",\"image\":\"\"}],\"agencySteps\":[{\"icon\":\"ri:user-add-line\",\"title\":\"开通合作账号\",\"desc\":\"获得专属代理入口，方便查看产品和服务客户。\"},{\"icon\":\"ri:share-forward-line\",\"title\":\"分享产品链接\",\"desc\":\"适合社群、门店、朋友圈和线上渠道推广。\"},{\"icon\":\"ri:file-search-line\",\"title\":\"跟进订单状态\",\"desc\":\"及时查看客户办理状态，提升服务体验。\"},{\"icon\":\"ri:customer-service-2-line\",\"title\":\"获得服务支持\",\"desc\":\"平台持续提供产品资料和业务协助。\"}],\"footer\":{\"slogan\":\"高速流量卡服务平台\"}}', '{\"entry\":{\"mode\":\"site\",\"pcUrl\":\"/#/agent\",\"mobileUrl\":\"/#/h5\"},\"nav\":{\"homeLink\":\"\",\"productText\":\"产品中心\",\"productLink\":\"#products\",\"platformText\":\"平台介绍\",\"platformLink\":\"#platform\",\"omniText\":\"多端互通\",\"omniLink\":\"#omni\",\"agencyText\":\"代理加盟\",\"agencyLink\":\"#agency\",\"agentLoginText\":\"代理登录\",\"agentLoginLink\":\"/#/agent\"},\"hero\":{\"eyebrow\":\"5G DATA CARD PLATFORM\",\"title\":\"优选流量卡\\n在线办理更省心\",\"lead\":\"精选多运营商套餐，流量、月租、通话等信息清晰展示，在线查看产品、提交办理、查询进度，满足日常上网与移动办公需求。\",\"primaryText\":\"查看产品中心\",\"primaryLink\":\"#products\",\"secondaryText\":\"了解代理加盟\",\"secondaryLink\":\"#agency\",\"image\":\"\"},\"visible\":{\"hero\":true,\"highlights\":true,\"platform\":true,\"core\":true,\"omni\":true,\"products\":true,\"agency\":true},\"highlights\":[{\"title\":\"套餐信息清晰\",\"desc\":\"流量、月租、通话等核心信息一目了然。\",\"target\":\"#products\"},{\"title\":\"在线便捷办理\",\"desc\":\"查看产品详情后，可进入对应办理流程。\",\"target\":\"#products\"},{\"title\":\"订单进度可查\",\"desc\":\"提交后可查询办理状态，减少等待焦虑。\",\"target\":\"#platform\"},{\"title\":\"代理合作支持\",\"desc\":\"为合作伙伴提供产品资料和推广服务。\",\"target\":\"#agency\"},{\"title\":\"产品持续更新\",\"desc\":\"套餐资料持续维护，便于获取新选择。\",\"target\":\"#products\"},{\"title\":\"多端访问顺畅\",\"desc\":\"电脑端、移动 H5、小程序与 APP 多端查看。\",\"target\":\"#omni\"}],\"sections\":{\"platform\":{\"title\":\"可靠产品，便捷服务\",\"desc\":\"围绕用户办卡、用卡、查单和咨询的真实需求，提供清晰易懂的服务体验。\"},\"core\":{\"title\":\"流量卡服务核心特点\",\"desc\":\"从产品展示、在线办理到订单跟进和服务支持，让办卡流程更清楚、更省心。\"},\"omni\":{\"title\":\"多平台互通互联\",\"desc\":\"电脑端、移动 H5、小程序与 APP 多端联动，用户查看更方便，合作伙伴服务更顺手。\"},\"products\":{\"eyebrow\":\"产品中心\",\"title\":\"精选流量卡产品\",\"desc\":\"展示套餐名称、运营商、流量、月租、通话等关键信息，方便快速了解并选择适合自己的产品。\",\"buttonText\":\"进入产品中心\",\"buttonLink\":\"/#/product\"},\"agency\":{\"eyebrow\":\"代理加盟\",\"title\":\"携手合作，共同拓展流量卡服务市场\",\"desc\":\"适合通信门店、社群团长、地推团队和线上渠道合作，平台提供产品资料、办理入口、订单查询与服务支持。\",\"buttonText\":\"代理登录\",\"buttonLink\":\"/#/agent\"}},\"capabilities\":[{\"icon\":\"ri:search-eye-line\",\"title\":\"查产品\",\"desc\":\"套餐名称、运营商、月租和流量信息集中呈现，用户可快速了解产品重点。\",\"points\":[\"套餐名称\",\"运营商\",\"月租信息\",\"流量信息\"]},{\"icon\":\"ri:smartphone-line\",\"title\":\"办套餐\",\"desc\":\"从查看产品到资料提交都有清晰指引，让用户在线办理路径更顺畅。\",\"points\":[\"在线查看\",\"快速选择\",\"资料提交\",\"办理指引\"]},{\"icon\":\"ri:file-search-line\",\"title\":\"查进度\",\"desc\":\"提交后可查看办理进度和结果提醒，减少反复咨询和等待焦虑。\",\"points\":[\"订单查询\",\"状态跟进\",\"资料补充\",\"结果提醒\"]},{\"icon\":\"ri:customer-service-2-line\",\"title\":\"享服务\",\"desc\":\"咨询、协助、办理说明和售后支持集中承接，用户遇到问题可及时处理。\",\"points\":[\"客服咨询\",\"问题协助\",\"办理说明\",\"售后支持\"]},{\"icon\":\"ri:team-line\",\"title\":\"代理合作\",\"desc\":\"为合作伙伴提供产品资料、推广入口和渠道服务，方便拓展客户。\",\"points\":[\"合作入口\",\"产品资料\",\"推广支持\",\"渠道服务\"]},{\"icon\":\"ri:shield-check-line\",\"title\":\"放心选择\",\"desc\":\"正规产品、信息透明、流程清楚，让用户选择和办理都更踏实。\",\"points\":[\"正规产品\",\"信息透明\",\"流程清晰\",\"服务可达\"]}],\"coreFeatures\":[{\"title\":\"优选套餐\",\"desc\":\"聚合不同使用场景的流量卡产品，方便快速筛选。\"},{\"title\":\"在线查看\",\"desc\":\"产品图片、套餐信息和办理说明集中展示。\"},{\"title\":\"快速办理\",\"desc\":\"用户可从产品详情进入办理流程，路径更清楚。\"},{\"title\":\"多场景适用\",\"desc\":\"覆盖日常上网、短视频、办公出行和备用流量。\"},{\"title\":\"进度可查\",\"desc\":\"提交后可通过查询入口了解办理状态。\"},{\"title\":\"资料提醒\",\"desc\":\"需要补充资料时，用户可按指引继续处理。\"},{\"title\":\"代理协作\",\"desc\":\"合作伙伴可通过专属入口服务自己的客户。\"},{\"title\":\"分享推广\",\"desc\":\"适合社群、门店、朋友圈和线上渠道传播。\"},{\"title\":\"客户服务\",\"desc\":\"提供咨询与协助入口，处理办理和查询问题。\"},{\"title\":\"信息透明\",\"desc\":\"关键套餐信息清楚展示，减少反复沟通。\"},{\"title\":\"移动适配\",\"desc\":\"手机端查看、分享和提交更顺手。\"},{\"title\":\"持续更新\",\"desc\":\"产品资料持续维护，便于用户获取新套餐。\"}],\"omniPlatforms\":[{\"icon\":\"ri:computer-line\",\"title\":\"电脑端\",\"desc\":\"品牌展示、产品中心、平台介绍和代理加盟统一承载。\",\"image\":\"\"},{\"icon\":\"ri:html5-line\",\"title\":\"移动 H5\",\"desc\":\"手机浏览更轻便，适合从社群、短信和短视频场景访问。\",\"image\":\"\"},{\"icon\":\"ri:wechat-line\",\"title\":\"小程序\",\"desc\":\"适合通过微信场景分享产品、承接咨询和办理线索。\",\"image\":\"\"},{\"icon\":\"ri:app-store-line\",\"title\":\"APP\",\"desc\":\"适合沉淀常用入口，支持产品查看、分享推广和客户服务。\",\"image\":\"\"}],\"agencySteps\":[{\"icon\":\"ri:user-add-line\",\"title\":\"开通合作账号\",\"desc\":\"获得专属代理入口，方便查看产品和服务客户。\"},{\"icon\":\"ri:share-forward-line\",\"title\":\"分享产品链接\",\"desc\":\"适合社群、门店、朋友圈和线上渠道推广。\"},{\"icon\":\"ri:file-search-line\",\"title\":\"跟进订单状态\",\"desc\":\"及时查看客户办理状态，提升服务体验。\"},{\"icon\":\"ri:customer-service-2-line\",\"title\":\"获得服务支持\",\"desc\":\"平台持续提供产品资料和业务协助。\"}],\"footer\":{\"slogan\":\"高速流量卡服务平台\"}}', '', '', '', 1, 9, 1781458263, 1781464218, 1781463689);

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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '短信IP限制日志表' ROW_FORMAT = DYNAMIC;

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
  `template_scene_key` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '通知模板键名',
  `provider` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'wangweiyun' COMMENT '服务提供商',
  `sms_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '短信类型：register注册，withdraw提现，login登录，reset重置密码等',
  `scene_source` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '通知场景来源',
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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '短信发送日志表' ROW_FORMAT = DYNAMIC;

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
) ENGINE = InnoDB AUTO_INCREMENT = 166 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '系统配置表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of system_config
-- ----------------------------
INSERT INTO `system_config` VALUES (1, 'site_name', '巨量号卡管理系统', 'text', 'basic', '网站名称', '网站的名称，显示在浏览器标题栏', NULL, 1, 0, 1756710628, 1782045753);
INSERT INTO `system_config` VALUES (2, 'site_logo', 'https://jlhk-1321005103.cos.ap-shanghai.myqcloud.com/uploads/media/site/basic/2026/06/09/6a279e59c17df.png', 'image', 'basic', '网站Logo', '网站的Logo图片', NULL, 2, 0, 1756710628, 1782045753);
INSERT INTO `system_config` VALUES (3, 'site_favicon', 'http://127.0.0.1:9000/favicon.png', 'image', 'basic', '网站图标', '网站的favicon图标', NULL, 3, 0, 1756710628, 1780980490);
INSERT INTO `system_config` VALUES (4, 'site_keywords', '流量卡,手机卡,电话卡', 'text', 'basic', '网站关键词', 'SEO关键词，多个用逗号分隔', NULL, 4, 0, 1756710628, 1756720756);
INSERT INTO `system_config` VALUES (5, 'site_description', '专业的流量卡管理系统', 'textarea', 'basic', '网站描述', 'SEO描述信息', NULL, 5, 0, 1756710628, 1756720756);
INSERT INTO `system_config` VALUES (6, 'site_copyright', '巨量号卡版权所有', 'text', 'basic', '版权信息', '网站底部显示的版权信息', NULL, 6, 0, 1756710628, 1782045753);
INSERT INTO `system_config` VALUES (7, 'site_icp', '京ICP5555555555555555', 'text', 'basic', 'ICP备案号', '网站ICP备案号', NULL, 7, 0, 1756710628, 1782045753);
INSERT INTO `system_config` VALUES (8, 'site_status', '1', 'radio', 'basic', '网站状态', '网站开启或关闭状态', NULL, 8, 0, 1756710628, 1782045753);
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
INSERT INTO `system_config` VALUES (20, 'file', '', 'text', 'basic', 'file', '', NULL, 0, 0, 1756715447, 1775820561);
INSERT INTO `system_config` VALUES (35, 'agent_register_verify', 'image', 'text', 'basic', 'agent_register_verify', '注册验证方式', NULL, 0, 0, 1756972371, 1775019279);
INSERT INTO `system_config` VALUES (36, 'agent_withdraw_verify', 'none', 'text', 'basic', 'agent_withdraw_verify', '提现验证方式', NULL, 0, 0, 1756972371, 1775019279);
INSERT INTO `system_config` VALUES (37, 'shop_order_verify', 'none', 'text', 'basic', 'shop_order_verify', '下单验证方式', NULL, 0, 0, 1756972371, 1775019279);
INSERT INTO `system_config` VALUES (38, 'verify_code_length', '6', 'text', 'basic', 'verify_code_length', '验证码长度', NULL, 0, 0, 1756972371, 1756972518);
INSERT INTO `system_config` VALUES (39, 'verify_code_expire', '300', 'text', 'basic', 'verify_code_expire', '验证码有效期', NULL, 0, 0, 1756972371, 1782370405);
INSERT INTO `system_config` VALUES (40, 'verify_code_interval', '60', 'text', 'basic', 'verify_code_interval', '获取间隔', NULL, 0, 0, 1756972371, 1782370405);
INSERT INTO `system_config` VALUES (54, 'idcard_enable', '1', 'text', 'other', 'idcard_enable', '是否实名认证', NULL, 0, 0, 1756984092, 1780395626);
INSERT INTO `system_config` VALUES (55, 'idcard_appcode', '', 'text', 'idcard', 'idcard_appcode', '实名认证appcode', NULL, 0, 0, 1756984092, 1780395626);
INSERT INTO `system_config` VALUES (58, 'auto_fill_verify_code', '0', 'radio', 'other', '验证码自动回填', '开启后，当用户多次获取验证码时，系统会自动将验证码回填到输入框中', NULL, 5, 0, 1756994086, 1775020761);
INSERT INTO `system_config` VALUES (59, 'auto_fill_trigger_count', '2', 'number', 'other', '回填触发次数', '用户获取验证码达到此次数时，自动回填验证码（默认3次）', NULL, 6, 0, 1756994086, 1778858507);
INSERT INTO `system_config` VALUES (60, 'sms_ip_hour_limit', '100', 'number', 'other', 'sms_ip_hour_limit', '小时短信获取次数', NULL, 0, 0, 1756996325, 1782370405);
INSERT INTO `system_config` VALUES (61, 'sms_ip_day_limit', '500', 'number', 'other', 'sms_ip_day_limit', '当天短信获取次数', NULL, 0, 0, 1756996325, 1782370405);
INSERT INTO `system_config` VALUES (64, 'min_withdraw_amount', '1', 'number', 'other', 'min_withdraw_amount', '最低提现金额', NULL, 0, 0, 1756998045, 1780369639);
INSERT INTO `system_config` VALUES (65, 'withdraw_fee_rate', '6', 'number', 'other', 'withdraw_fee_rate', '提现费率', NULL, 0, 0, 1756998045, 1780369639);
INSERT INTO `system_config` VALUES (66, 'min_withdraw_fee', '0', 'number', 'other', 'min_withdraw_fee', '最低手续费', NULL, 0, 0, 1756998045, 1780369639);
INSERT INTO `system_config` VALUES (67, 'max_withdraw_fee', '10', 'number', 'other', 'max_withdraw_fee', '最高手续费', NULL, 0, 0, 1756998045, 1780369639);
INSERT INTO `system_config` VALUES (68, 'account_security_deposit', '100', 'number', 'other', 'account_security_deposit', '保证金', NULL, 0, 0, 1756998045, 1780369639);
INSERT INTO `system_config` VALUES (69, 'security_deposit_description', '为保障平台资金安全，账户需保留一定金额作为保证金，用于处理售后等业务.', 'textarea', 'other', 'security_deposit_description', '保证金说明', NULL, 0, 0, 1756998526, 1780369639);
INSERT INTO `system_config` VALUES (70, 'security_key', 'vmFdqzQxQgWIQBfcYr1yS2aM7fqF66vc', 'text', 'basic', 'security_key', '安全密钥', NULL, 0, 0, 1757056326, 1782045753);
INSERT INTO `system_config` VALUES (71, 'api_sync_image_mode', 'original', 'select', 'api', 'API同步商品图片处理方式', '选择API同步商品时如何处理图片：本地存储（下载到服务器）、云存储（上传到云端）、原始链接（直接使用API图片链接）', '{\"local\":\"本地存储\",\"cloud\":\"云存储\",\"original\":\"原始链接\"}', 10, 1, 1757402687, 1775400395);
INSERT INTO `system_config` VALUES (72, 'logistics_enabled', '1', 'text', 'basic', '启用物流查询', '', NULL, 0, 0, 1757429919, 1757430292);
INSERT INTO `system_config` VALUES (73, 'logistics_provider', 'jumei', 'text', 'basic', '物流服务提供商', '', NULL, 0, 0, 1757429919, 1757430292);
INSERT INTO `system_config` VALUES (74, 'logistics_appcode', '', 'text', 'basic', '物流查询AppCode', '', NULL, 0, 0, 1757429919, 1757430292);
INSERT INTO `system_config` VALUES (75, 'logistics_api_url', 'https://jmexpresv2.market.alicloudapi.com', 'text', 'basic', '物流API地址', '', NULL, 0, 0, 1757429919, 1757430292);
INSERT INTO `system_config` VALUES (76, 'logistics_api_path', '/express/query-v2', 'text', 'basic', '物流查询路径', '', NULL, 0, 0, 1757429919, 1757430292);
INSERT INTO `system_config` VALUES (77, 'express_enabled', '1', 'text', 'basic', 'express_enabled', '', NULL, 0, 0, 1757430303, 1782370298);
INSERT INTO `system_config` VALUES (78, 'express_provider', 'jumei', 'text', 'basic', 'express_provider', '', NULL, 0, 0, 1757430303, 1782370298);
INSERT INTO `system_config` VALUES (79, 'express_appcode', '', 'text', 'basic', 'express_appcode', '', NULL, 0, 0, 1757430303, 1782370298);
INSERT INTO `system_config` VALUES (80, 'express_api_url', 'https://jmexpresv2.market.alicloudapi.com', 'text', 'basic', 'express_api_url', '', NULL, 0, 0, 1757430303, 1782370298);
INSERT INTO `system_config` VALUES (81, 'express_api_path', '/express/query-v2', 'text', 'basic', 'express_api_path', '', NULL, 0, 0, 1757430303, 1782370298);
INSERT INTO `system_config` VALUES (82, 'agent_id_start', '1', 'text', 'other', 'agent_id_start', '', NULL, 0, 0, 1757931854, 1780369639);
INSERT INTO `system_config` VALUES (83, 'order_prefix', 'HK', 'text', 'other', '订单号前缀', '', NULL, 0, 0, 1757932983, 1780369639);
INSERT INTO `system_config` VALUES (84, 'agent_resubmit_order_enabled', '1', 'text', 'basic', '代理重提开关', '', NULL, 0, 0, 1759564234, 1781166907);
INSERT INTO `system_config` VALUES (85, 'wechat_official_appid', '', 'text', 'basic', 'wechat_official_appid', '', NULL, 0, 0, 1773294218, 1782370293);
INSERT INTO `system_config` VALUES (86, 'wechat_official_appsecret', '', 'text', 'basic', 'wechat_official_appsecret', '', NULL, 0, 0, 1773294218, 1782370293);
INSERT INTO `system_config` VALUES (87, 'wechat_login_mode', 'relay', 'text', 'basic', 'wechat_login_mode', '', NULL, 0, 0, 1773904789, 1782370293);
INSERT INTO `system_config` VALUES (88, 'distribution_level_mode', 'fixed', 'radio', 'other', '分销等级模式', 'legacy=代理自定义等级，fixed=总后台固定等级', NULL, 50, 0, 1774325986, 1780581829);
INSERT INTO `system_config` VALUES (89, 'cloud_export_vendor', 'kdocs_webhook', 'text', 'basic', 'cloud_export_vendor', '', NULL, 0, 0, 1774508338, 1774508392);
INSERT INTO `system_config` VALUES (90, 'cloud_export_webhook_url', 'http://t9497cc8.natappfree.cc/api/cloudexport.hook/receive', 'text', 'basic', 'cloud_export_webhook_url', '', NULL, 0, 0, 1774508338, 1774508392);
INSERT INTO `system_config` VALUES (91, 'cloud_export_token', '', 'text', 'basic', 'cloud_export_token', '', NULL, 0, 0, 1774508338, 1774508392);
INSERT INTO `system_config` VALUES (92, 'cloud_export_receiver_token', '', 'text', 'basic', 'cloud_export_receiver_token', '', NULL, 0, 0, 1774535500, 1780973757);
INSERT INTO `system_config` VALUES (93, 'wechat_login_enabled', '0', 'text', 'basic', 'wechat_login_enabled', '', NULL, 0, 0, 1774852577, 1782370293);
INSERT INTO `system_config` VALUES (94, 'user_agreement_content', '<div>更新日期：2023 年 10 月 1 日</div>\n<div>&nbsp;</div>\n<div>生效日期：2023 年 1 月 1 日</div>\n<div>&nbsp;</div>\n<ol>\n<li>服务条款的确认</li>\n</ol>\n<div>&nbsp;</div>\n<div>欢迎您使用本平台提供的流量卡管理服务。在使用我们的服务前，请仔细阅读本用户协议（以下简称 \"本协议\"）。您的注册、登录、使用等行为将视为对本协议的接受，并同意接受本协议各项条款的约束。</div>\n<div>&nbsp;</div>\n<ol start=\"2\">\n<li>服务内容</li>\n</ol>\n<div>&nbsp;</div>\n<div>本平台为用户提供流量卡销售、查询等相关服务，包括但不限于：</div>\n<div>&nbsp;</div>\n<div>流量卡产品展示与销售\n<div>&nbsp;</div>\n在线支付与订单管理\n<div>&nbsp;</div>\n客户服务与技术支持\n<div>&nbsp;</div>\n用户账户管理</div>\n<div>&nbsp;</div>\n<ol start=\"3\">\n<li>用户权利与义务</li>\n</ol>\n<div>&nbsp;</div>\n<div>3.1 用户权利：</div>\n<div>&nbsp;</div>\n<div>享受平台提供的各项服务\n<div>&nbsp;</div>\n保护个人隐私和数据安全\n<div>&nbsp;</div>\n对服务质量进行监督和建议</div>\n<div>&nbsp;</div>\n<div>3.2 用户义务：</div>\n<div>&nbsp;</div>\n<div>提供真实、准确的个人信息\n<div>&nbsp;</div>\n遵守国家法律法规和平台规定\n<div>&nbsp;</div>\n不得进行任何损害平台利益的行为\n<div>&nbsp;</div>\n妥善保管账户信息，不得转让或出借</div>\n<div>&nbsp;</div>\n<ol start=\"4\">\n<li>服务规范</li>\n</ol>\n<div>&nbsp;</div>\n<div>用户在使用本平台服务时，不得：</div>\n<div>&nbsp;</div>\n<div>发布违法、违规信息\n<div>&nbsp;</div>\n进行恶意攻击或破坏系统安全\n<div>&nbsp;</div>\n使用技术手段干扰平台正常运行\n<div>&nbsp;</div>\n进行任何形式的商业欺诈行为</div>\n<div>&nbsp;</div>\n<ol start=\"5\">\n<li>费用与支付</li>\n</ol>\n<div>&nbsp;</div>\n<div>用户使用付费服务时，应按照平台公示的价格支付相应费用。支付完成后，除特殊情况外，费用不予退还。</div>\n<div>&nbsp;</div>\n<ol start=\"6\">\n<li>责任限制</li>\n</ol>\n<div>&nbsp;</div>\n<div>本平台对因系统维护、网络故障、第三方原因等导致的服务中断不承担责任。但我们会尽力维护系统稳定性，保障用户体验。</div>\n<div>&nbsp;</div>\n<ol start=\"7\">\n<li>协议变更</li>\n</ol>\n<div>&nbsp;</div>\n<div>本协议可能会根据业务发展需要进行更新，更新后的协议将在平台公布。继续使用服务即视为同意更新后的协议。</div>\n<div>&nbsp;</div>\n<ol start=\"8\">\n<li>联系我们</li>\n</ol>\n<div>&nbsp;</div>\n<div>如您对本协议有任何疑问，请通过平台客服联系我们。</div>\n<div>&nbsp;</div>\n<div>感谢您选择我们的服务！</div>', 'textarea', 'basic', '用户协议', '', NULL, 0, 0, 1775184317, 1779001791);
INSERT INTO `system_config` VALUES (95, 'privacy_policy_content', '<p>隐私保护政策</p><p><br></p><p><span style=\"font-family: Arial;\">更新日期：2023 年 10 月 1 日</span></p><p><br></p><p>生效日期：2023 年 10 月 1 日</p><p><br></p><ol><li>隐私政策概述</li></ol><p><br></p><p>我们非常重视用户的隐私保护。本隐私政策说明了我们如何收集、使用、存储和保护您的个人信息。使用我们的服务即表示您同意本隐私政策的内容。</p><p><br></p><ol><li>信息收集</li></ol><p><br></p><p>2.1 我们收集的信息类型：</p><p><br></p><p>账户信息：用户名、手机号、邮箱等注册信息 身份信息：实名认证所需的姓名、身份证号等 交易信息：订单记录、支付信息、收货地址等 设备信息：设备型号、操作系统、IP 地址等 使用信息：访问记录、操作日志等</p><p><br></p><p>2.2 信息收集方式：</p><p><br></p><p>您实名认证服务的信息 使用服务时自动收集的信息 通过技术手段收集的信息</p><p><br></p><ol><li>信息使用</li></ol><p><br></p><p>我们使用收集的信息用于：</p><p><br></p><p>提供和改进我们的服务 处理订单和支付 进行身份验证和安全保护 客户服务和技术支持 发送重要通知和更新 数据分析和业务优化</p><p><br></p><ol><li>信息共享</li></ol><p><br></p><p>除以下情况外，我们不会与第三方共享您的个人信息：</p><p><br></p><p>获得您的明确授权 法律法规要求 与服务提供商合作（如支付、物流等） 维护平台安全和用户权益</p><p><br></p><ol><li>信息存储与安全</li></ol><p><br></p><p>5.1 数据存储：</p><p><br></p><p>数据存储在中华人民共和国境内 采用行业标准的安全措施保护数据 定期备份和安全检查</p><p><br></p><p>5.2 安全保护：</p><p><br></p><p>数据加密传输和存储 访问权限控制 安全监控和异常检测</p><p><br></p><ol><li>您的权利</li></ol><p><br></p><p>您对个人信息享有以下权利：</p><p><br></p><p>知情权：了解个人信息的处理情况 访问权：查询您的个人信息 更正权：更正不准确的个人信息 删除权：在特定条件下删除个人信息 撤回同意：撤回对个人信息处理的同意</p><p><br></p><ol><li>Cookie 和类似技术</li></ol><p><br></p><p>我们使用 Cookie 和类似技术来改善用户体验，包括：</p><p><br></p><p>记住登录状态 保存用户偏好设置 分析网站使用情况 提供个性化服务</p><p><br></p><ol><li>政策更新</li></ol><p><br></p><p>我们可能会更新本隐私政策。重大变更将通过适当方式通知您，继续使用服务即视为接受更新后的政策。</p><p><br></p><p>我们承诺保护您的隐私安全！</p>', 'textarea', 'basic', '隐私协议', '', NULL, 0, 0, 1775184317, 1779001791);
INSERT INTO `system_config` VALUES (96, 'tencent_notice_tpl_order_ship_id', '', 'text', 'sms', '腾讯云-订单发货模板ID', '', NULL, 0, 0, 1775530218, 1782370406);
INSERT INTO `system_config` VALUES (97, 'tencent_notice_tpl_order_review_failed_id', '', 'text', 'sms', '腾讯云-订单审核失败模板ID', '', NULL, 0, 0, 1775530218, 1782370406);
INSERT INTO `system_config` VALUES (98, 'tencent_notice_tpl_agent_withdraw_processing_id', '', 'text', 'sms', '腾讯云-提现受理模板ID', '', NULL, 0, 0, 1775530218, 1782370406);
INSERT INTO `system_config` VALUES (99, 'tencent_notice_tpl_agent_withdraw_success_id', '', 'text', 'sms', '腾讯云-提现成功模板ID', '', NULL, 0, 0, 1775530218, 1782370406);
INSERT INTO `system_config` VALUES (100, 'tencent_notice_tpl_agent_withdraw_rejected_id', '', 'text', 'sms', '腾讯云-提现驳回模板ID', '', NULL, 0, 0, 1775530218, 1782370406);
INSERT INTO `system_config` VALUES (101, 'tencent_notice_tpl_agent_withdraw_failed_id', '', 'text', 'sms', '腾讯云-提现失败模板ID', '', NULL, 0, 0, 1775530218, 1782370406);
INSERT INTO `system_config` VALUES (102, 'tencent_notice_tpl_agent_level_change_id', '', 'text', 'sms', '腾讯云-等级调整模板ID', '', NULL, 0, 0, 1775530218, 1782370406);
INSERT INTO `system_config` VALUES (103, 'aliyun_notice_tpl_order_ship_id', '', 'text', 'sms', '阿里云-订单发货模板ID', '', NULL, 0, 0, 1775530218, 1782370406);
INSERT INTO `system_config` VALUES (104, 'aliyun_notice_tpl_order_review_failed_id', '', 'text', 'sms', '阿里云-订单审核失败模板ID', '', NULL, 0, 0, 1775530218, 1782370406);
INSERT INTO `system_config` VALUES (105, 'aliyun_notice_tpl_agent_withdraw_processing_id', '', 'text', 'sms', '阿里云-提现受理模板ID', '', NULL, 0, 0, 1775530218, 1782370406);
INSERT INTO `system_config` VALUES (106, 'aliyun_notice_tpl_agent_withdraw_success_id', '', 'text', 'sms', '阿里云-提现成功模板ID', '', NULL, 0, 0, 1775530218, 1782370406);
INSERT INTO `system_config` VALUES (107, 'aliyun_notice_tpl_agent_withdraw_rejected_id', '', 'text', 'sms', '阿里云-提现驳回模板ID', '', NULL, 0, 0, 1775530218, 1782370406);
INSERT INTO `system_config` VALUES (108, 'aliyun_notice_tpl_agent_withdraw_failed_id', '', 'text', 'sms', '阿里云-提现失败模板ID', '', NULL, 0, 0, 1775530218, 1782370406);
INSERT INTO `system_config` VALUES (109, 'aliyun_notice_tpl_agent_level_change_id', '', 'text', 'sms', '阿里云-等级调整模板ID', '', NULL, 0, 0, 1775530218, 1782370406);
INSERT INTO `system_config` VALUES (110, 'sms_notice_tpl_order_ship', '您的订单已发货；请您联系商家，查看物流信息，祝您生活愉快，事事顺遂；', 'textarea', 'sms', '订单发货短信模板', '', NULL, 0, 0, 1775530218, 1782370406);
INSERT INTO `system_config` VALUES (111, 'sms_notice_tpl_order_review_failed', '您提交的套餐审核失败，订单金额已原路退回注意查收；具体失败原因详询店铺客服。', 'textarea', 'sms', '订单审核失败短信模板', '', NULL, 0, 0, 1775530218, 1782370406);
INSERT INTO `system_config` VALUES (112, 'sms_notice_tpl_agent_withdraw_processing', '提现已受理，单号{withdraw_no}，金额{amount}元。', 'textarea', 'sms', '代理提现受理短信模板', '', NULL, 0, 0, 1775530218, 1782370406);
INSERT INTO `system_config` VALUES (113, 'sms_notice_tpl_agent_withdraw_success', '提现申请处理成功，金额{1}元已转入您账户，平台已为您代扣代缴个人所得税，请注意查收。', 'textarea', 'sms', '代理提现成功短信模板', '', NULL, 0, 0, 1775530218, 1782370406);
INSERT INTO `system_config` VALUES (114, 'sms_notice_tpl_agent_withdraw_rejected', '申请提现未成功，请您更换提现方式后重试。请核对账户信息后重新提交。', 'textarea', 'sms', '代理提现驳回短信模板', '', NULL, 0, 0, 1775530218, 1782370406);
INSERT INTO `system_config` VALUES (115, 'sms_notice_tpl_agent_withdraw_failed', '提现失败，单号{withdraw_no}，原因{reason_text}。', 'textarea', 'sms', '代理提现失败短信模板', '', NULL, 0, 0, 1775530218, 1782370406);
INSERT INTO `system_config` VALUES (116, 'sms_notice_tpl_agent_level_change', '等级已调整，原等级{old_level}，新等级{new_level}。', 'textarea', 'sms', '代理等级调整短信模板', '', NULL, 0, 0, 1775530218, 1782370406);
INSERT INTO `system_config` VALUES (117, 'tencent_notice_tpl_order_pending_photo_id', '', 'text', 'sms', '腾讯云-订单待传照片模板ID', '', NULL, 0, 0, 1775541445, 1782370406);
INSERT INTO `system_config` VALUES (118, 'aliyun_notice_tpl_order_pending_photo_id', '', 'text', 'sms', '阿里云-订单待传照片模板ID', '', NULL, 0, 0, 1775541445, 1782370406);
INSERT INTO `system_config` VALUES (119, 'sms_notice_tpl_order_pending_photo', '您的订单图片审核失败，请在原订单重新上传三照，审核成功后发货。', 'textarea', 'sms', '订单待传照片短信模板', '', NULL, 0, 0, 1775541445, 1782370406);
INSERT INTO `system_config` VALUES (120, 'agent_realname_feature_access', '[\"order_submit\",\"invite_agent\",\"withdraw\"]', 'text', 'basic', 'agent_realname_feature_access', '', NULL, 0, 0, 1775558298, 1781166907);
INSERT INTO `system_config` VALUES (121, 'distribution_level_mode_initialized', '1', 'text', 'basic', 'distribution_level_mode_initialized', '', NULL, 0, 0, 1775570120, 1780581829);
INSERT INTO `system_config` VALUES (122, 'shop_order_smart_recognition', '1', 'text', 'basic', 'shop_order_smart_recognition', '', NULL, 0, 0, 1775719620, 1781166907);
INSERT INTO `system_config` VALUES (123, 'admin_login_bg_url', '', 'text', 'basic', '总后台登录页背景图', '', NULL, 0, 0, 1775820561, 1782045753);
INSERT INTO `system_config` VALUES (124, 'agent_login_bg_url', '', 'text', 'basic', '代理后台登录页背景图', '', NULL, 0, 0, 1775820561, 1782045753);
INSERT INTO `system_config` VALUES (125, 'agent_login_panel_bg_url', '', 'text', 'basic', '代理登录框左侧小图', '', NULL, 0, 0, 1775820561, 1782045753);
INSERT INTO `system_config` VALUES (126, 'fadada_sign_enabled', '1', 'text', 'basic', 'fadada_sign_enabled', '', NULL, 0, 0, 1775822969, 1782307118);
INSERT INTO `system_config` VALUES (127, 'fadada_sign_amount', '1.00', 'text', 'basic', 'fadada_sign_amount', '', NULL, 0, 0, 1775822969, 1782307118);
INSERT INTO `system_config` VALUES (128, 'fadada_sign_description', '<p style=\"text-align: center;\"><br></p><p style=\"text-align: center;\"><span style=\"color: rgb(35, 111, 161);\"><strong>使用账号实名人信息签约1</strong></span></p><p style=\"text-align: center;\"><strong>如若提供虚假信息，承担主要责任<br></strong>签约分两步骤<br>1.填写您本人信息并上传证照提交<br>2.点击查看详情进入签署电子签名</p><p style=\"text-align: center;\"><strong><br>最后提示50元认证费不要支付，直接关闭即可<br>（如下图所示）<br>??长按扫码签约??</strong></p><p style=\"text-align: center;\"><br></p><p style=\"text-align: center;\"><strong>或<br>点击下方链接前往签约<br>https://fdd1.cn/IoTiktjFUFn<br></strong></p><p style=\"text-align: center;\"><strong>特别提醒：</strong></p><p style=\"text-align: center;\"><strong>填写信息上传照片后，不要返回</strong></p><p style=\"text-align: center;\"><strong>点击下图查看详情，进入完成电子签名<br>弹出图②才是完成签署</strong></p><p style=\"text-align: center;\"><img src=\"https://wendang-1321005103.cos.ap-guangzhou.myqcloud.com/uploads/media/product/2026/06/03/6a203545de081.jpg\" alt=\"签约结束.jpg\" data-href=\"\" style=\"\"></p><p style=\"text-align: center;\"><strong><br></strong></p><p style=\"text-align: center;\"> </p>', 'text', 'basic', 'fadada_sign_description', '', NULL, 0, 0, 1775822969, 1782307118);
INSERT INTO `system_config` VALUES (129, 'fadada_sign_qrcode_content', '<p style=\"text-align: center;\"><br></p><p style=\"text-align: center;\"><span style=\"color: rgb(35, 111, 161);\"><strong>使用账号实名人信息签约2</strong></span></p><p style=\"text-align: center;\"><strong>如若提供虚假信息，承担主要责任<br></strong>签约分两步骤<br>1.填写您本人信息并上传证照提交<br>2.点击查看详情进入签署电子签名</p><p style=\"text-align: center;\"><strong><br>最后提示50元认证费不要支付，直接关闭即可<br>（如下图所示）<br>??长按扫码签约??</strong></p><p style=\"text-align: center;\"><br></p><p style=\"text-align: center;\"><strong>或<br>点击下方链接前往签约<br>https://fdd1.cn/IoTiktjFUFn<br></strong></p><p style=\"text-align: center;\"><strong>特别提醒：</strong></p><p style=\"text-align: center;\"><strong>填写信息上传照片后，不要返回</strong></p><p style=\"text-align: center;\"><strong>点击下图查看详情，进入完成电子签名<br>弹出图②才是完成签署</strong></p><p style=\"text-align: center;\"><br></p><p style=\"text-align: center;\"><strong><br></strong></p><p style=\"text-align: center;\"> </p>', 'text', 'basic', 'fadada_sign_qrcode_content', '', NULL, 0, 0, 1775822969, 1782307118);
INSERT INTO `system_config` VALUES (130, 'fadada_sign_prompt_position', 'register', 'text', 'basic', 'fadada_sign_prompt_position', '', NULL, 0, 0, 1775822969, 1776050355);
INSERT INTO `system_config` VALUES (131, 'agent_paid_card_markup_enabled', '1', 'text', 'basic', 'agent_paid_card_markup_enabled', '', NULL, 0, 0, 1776350263, 1781166907);
INSERT INTO `system_config` VALUES (132, 'agent_paid_card_markup_max', '100.00', 'text', 'basic', 'agent_paid_card_markup_max', '', NULL, 0, 0, 1776350263, 1781166907);
INSERT INTO `system_config` VALUES (133, 'fixed_mode_allow_peer_agent', '0', 'text', 'basic', 'fixed_mode_allow_peer_agent', '', NULL, 0, 0, 1776831859, 1781166907);
INSERT INTO `system_config` VALUES (134, 'fixed_mode_allow_manual_invite_code', '1', 'text', 'basic', 'fixed_mode_allow_manual_invite_code', '', NULL, 0, 0, 1776831859, 1781166907);
INSERT INTO `system_config` VALUES (135, 'fadada_sign_pending_account_status', '1', 'text', 'basic', 'fadada_sign_pending_account_status', '', NULL, 0, 0, 1776843203, 1782307118);
INSERT INTO `system_config` VALUES (136, 'fadada_sign_pending_withdraw_enabled', '1', 'text', 'basic', 'fadada_sign_pending_withdraw_enabled', '', NULL, 0, 0, 1776843203, 1782307118);
INSERT INTO `system_config` VALUES (137, 'fadada_sign_pending_submit_order_enabled', '0', 'text', 'basic', 'fadada_sign_pending_submit_order_enabled', '', NULL, 0, 0, 1776843203, 1782307118);
INSERT INTO `system_config` VALUES (138, 'fadada_sign_pending_invite_agent_enabled', '1', 'text', 'basic', 'fadada_sign_pending_invite_agent_enabled', '', NULL, 0, 0, 1776843203, 1782307118);
INSERT INTO `system_config` VALUES (139, 'idcard_three_appcode', '', 'text', 'idcard', '三要素AppCode', '', NULL, 0, 0, 1777023406, 1780395626);
INSERT INTO `system_config` VALUES (140, 'product_shop_order_factor_type_map', '{\"3367\":\"two\",\"3368\":\"two\"}', 'text', 'basic', 'product_shop_order_factor_type_map', '', NULL, 0, 0, 1777024892, 1782274095);
INSERT INTO `system_config` VALUES (141, 'shop_order_factor_type', 'two', 'text', 'basic', 'shop_order_factor_type', '', NULL, 0, 0, 1777024955, 1781166907);
INSERT INTO `system_config` VALUES (142, 'agent_realname_factor_type', 'two', 'text', 'basic', 'agent_realname_factor_type', '', NULL, 0, 0, 1777024955, 1781166907);
INSERT INTO `system_config` VALUES (143, 'app_pack_last_credits_balance', '220', 'text', 'basic', 'app_pack_last_credits_balance', '', NULL, 0, 0, 1777297660, 1782366991);
INSERT INTO `system_config` VALUES (144, 'app_pack_remote_config', '{\"enabled\":1,\"api_base_url\":\"https:\\/\\/auth.mi000.cn\\/api\",\"auth_code\":\"LUNTVP974TKQ4VOQ\",\"domain\":\"localhost\",\"api_secret_key\":\"0cBx3W7PfrZ86OGX6fswhq9YbglRFAM7\",\"public_base_url\":\"http:\\/\\/localhost\",\"public_cert_points\":30,\"independent_cert_points\":30,\"cloud_signature_extra_points\":10,\"ios_points\":30,\"independent_signatures\":[{\"id\":8,\"name\":\"NAC0423B1\"},{\"id\":7,\"name\":\"NAD35C615\"}]}', 'text', 'basic', 'app_pack_remote_config', '', NULL, 0, 0, 1777298330, 1782315729);
INSERT INTO `system_config` VALUES (145, 'withdraw_help_content', '<img src=\"https://wendang-1321005103.cos.ap-guangzhou.myqcloud.com/uploads/product/2026/05/13/6a044da769fe2.png\" alt=\"\" data-href=\"\" />', 'textarea', 'other', '提现说明', '', NULL, 0, 0, 1777383078, 1780369639);
INSERT INTO `system_config` VALUES (146, 'agent_withdraw_enabled', '1', 'text', 'basic', 'agent_withdraw_enabled', '', NULL, 0, 0, 1777955470, 1781166907);
INSERT INTO `system_config` VALUES (147, 'agent_withdraw_payment_method', '[\"wechat\",\"bank\",\"alipay\"]', 'text', 'basic', 'agent_withdraw_payment_method', '', NULL, 0, 0, 1777955470, 1781166907);
INSERT INTO `system_config` VALUES (148, 'wwy_verify_tpl_order_verify_id', '', 'text', 'sms', '望为云-下单验证模板ID', '', NULL, 0, 0, 1778858159, 1782370406);
INSERT INTO `system_config` VALUES (149, 'tencent_verify_tpl_order_verify_id', '', 'text', 'sms', '腾讯云-下单验证模板ID', '', NULL, 0, 0, 1778858159, 1782370406);
INSERT INTO `system_config` VALUES (150, 'aliyun_verify_tpl_order_verify_id', '', 'text', 'sms', '阿里云-下单验证模板ID', '', NULL, 0, 0, 1778858159, 1782370406);
INSERT INTO `system_config` VALUES (151, 'sms_verify_tpl_order_verify', '', 'textarea', 'sms', '下单验证短信模板', '', NULL, 0, 0, 1778858159, 1782370406);
INSERT INTO `system_config` VALUES (152, 'site_police_record', '京ICP66666', 'text', 'basic', '公安网备案号', '', NULL, 0, 0, 1778929466, 1782045753);
INSERT INTO `system_config` VALUES (153, 'order_batch_settlement_password_hash', '', 'password', 'other', '导入结算密码', '导入结算密码', NULL, 0, 0, 1778988050, 1780975736);
INSERT INTO `system_config` VALUES (154, 'agent_login_template', 'animated', 'text', 'basic', '代理登录页模板', '', NULL, 0, 0, 1779000924, 1782045753);
INSERT INTO `system_config` VALUES (155, 'order_risk_pending_payment_expire_minutes', '30', 'text', 'basic', 'order_risk_pending_payment_expire_minutes', '', NULL, 0, 0, 1779088420, 1781166907);
INSERT INTO `system_config` VALUES (156, 'order_risk_pending_payment_reuse_minutes', '30', 'text', 'basic', 'order_risk_pending_payment_reuse_minutes', '', NULL, 0, 0, 1779088420, 1781166907);
INSERT INTO `system_config` VALUES (157, 'order_risk_hide_pending_orders', '0', 'text', 'basic', 'order_risk_hide_pending_orders', '', NULL, 0, 0, 1779088420, 1781166907);
INSERT INTO `system_config` VALUES (158, 'agent_realname_two_factor_max_attempts', '3', 'number', 'idcard', '代理二要素调用次数限制', '', NULL, 0, 0, 1780395626, 1780395626);
INSERT INTO `system_config` VALUES (159, 'contract_sign_url', '', 'text', 'basic', 'contract_sign_url', '', NULL, 0, 0, 1780490294, 1782307118);
INSERT INTO `system_config` VALUES (160, 'contract_sign_query_csrf_token', '', 'text', 'basic', 'contract_sign_query_csrf_token', '', NULL, 0, 0, 1780490294, 1782307118);
INSERT INTO `system_config` VALUES (161, 'contract_sign_query_cookie', '', 'text', 'basic', 'contract_sign_query_cookie', '', NULL, 0, 0, 1780490294, 1782307118);
INSERT INTO `system_config` VALUES (162, 'contract_sign_query_interval_seconds', '600', 'text', 'basic', 'contract_sign_query_interval_seconds', '', NULL, 0, 0, 1780490294, 1782307118);
INSERT INTO `system_config` VALUES (163, 'online_service_url', 'https://www.baidu.com', 'text', 'basic', '在线客服链接', '', NULL, 0, 0, 1780551989, 1782045753);
INSERT INTO `system_config` VALUES (164, 'admin_login_suffix', 'admin', 'text', 'basic', '总后台登录后缀', '', NULL, 0, 0, 1780742688, 1782045753);
INSERT INTO `system_config` VALUES (165, 'express_cache_minutes', '30', 'number', 'express', '快递查询缓存时间(分钟)', '', NULL, 0, 0, 1782370298, 1782370298);

-- ----------------------------
-- Table structure for system_event_logs
-- ----------------------------
DROP TABLE IF EXISTS `system_event_logs`;
CREATE TABLE `system_event_logs`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `category` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '日志分类',
  `level` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'info' COMMENT '日志级别',
  `actor_type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'admin' COMMENT '操作者类型',
  `actor_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作者ID',
  `actor_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '操作者名称',
  `action` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '动作标识',
  `target_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '对象类型',
  `target_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '对象ID',
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '日志标题',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '日志内容',
  `ip_address` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'IP地址',
  `user_agent` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'User-Agent',
  `request_method` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '请求方式',
  `request_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '请求地址',
  `before_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '变更前数据',
  `after_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '变更后数据',
  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '上下文数据',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0失败 1成功',
  `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_category_time`(`category`, `create_time`) USING BTREE,
  INDEX `idx_actor`(`actor_type`, `actor_id`) USING BTREE,
  INDEX `idx_level`(`level`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1101 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '系统事件日志表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of system_event_logs
-- ----------------------------
INSERT INTO `system_event_logs` VALUES (1088, 'operation', 'success', 'admin', 1, '超级管理员', 'pluginmarket/disable', 'pluginmarket', '', '插件市场 - 禁用', '插件禁用成功', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', 'POST', 'https://localhost:3006/admin/pluginmarket/disable', '', '', '{\"route\":\"\\/pluginmarket\\/disable\",\"params\":{\"plugin_key\":\"tiancheng\"},\"response\":{\"code\":1,\"msg\":\"插件禁用成功\"},\"duration_ms\":35}', 1, 1782371352);
INSERT INTO `system_event_logs` VALUES (1089, 'operation', 'success', 'admin', 1, '超级管理员', 'pluginmarket/enable', 'pluginmarket', '', '插件市场 - 启用', '插件启用成功', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', 'POST', 'https://localhost:3006/admin/pluginmarket/enable', '', '', '{\"route\":\"\\/pluginmarket\\/enable\",\"params\":{\"plugin_key\":\"h5\",\"plugin_type\":\"1\",\"authcode\":\"***\",\"auth_source\":\"package\"},\"response\":{\"code\":1,\"msg\":\"插件启用成功\"},\"duration_ms\":48}', 1, 1782371472);
INSERT INTO `system_event_logs` VALUES (1090, 'operation', 'success', 'admin', 1, '超级管理员', 'pluginmarket/enable', 'pluginmarket', '', '插件市场 - 启用', '插件启用成功', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', 'POST', 'https://localhost:3006/admin/pluginmarket/enable', '', '', '{\"route\":\"\\/pluginmarket\\/enable\",\"params\":{\"plugin_key\":\"fadada\",\"plugin_type\":\"1\",\"authcode\":\"***\",\"auth_source\":\"\"},\"response\":{\"code\":1,\"msg\":\"插件启用成功\"},\"duration_ms\":69}', 1, 1782371692);
INSERT INTO `system_event_logs` VALUES (1091, 'operation', 'success', 'admin', 1, '超级管理员', 'pluginmarket/disable', 'pluginmarket', '', '插件市场 - 禁用', '插件禁用成功', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', 'POST', 'https://localhost:3006/admin/pluginmarket/disable', '', '', '{\"route\":\"\\/pluginmarket\\/disable\",\"params\":{\"plugin_key\":\"fadada\"},\"response\":{\"code\":1,\"msg\":\"插件禁用成功\"},\"duration_ms\":68}', 1, 1782371702);
INSERT INTO `system_event_logs` VALUES (1092, 'operation', 'success', 'admin', 1, '超级管理员', 'pluginmarket/enable', 'pluginmarket', '', '插件市场 - 启用', '插件启用成功', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', 'POST', 'https://localhost:3006/admin/pluginmarket/enable', '', '', '{\"route\":\"\\/pluginmarket\\/enable\",\"params\":{\"plugin_key\":\"fadada\",\"plugin_type\":\"1\",\"authcode\":\"***\",\"auth_source\":\"\"},\"response\":{\"code\":1,\"msg\":\"插件启用成功\"},\"duration_ms\":50}', 1, 1782371722);
INSERT INTO `system_event_logs` VALUES (1093, 'operation', 'success', 'admin', 1, '超级管理员', 'pluginmarket/disable', 'pluginmarket', '', '插件市场 - 禁用', '插件禁用成功', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', 'POST', 'https://localhost:3006/admin/pluginmarket/disable', '', '', '{\"route\":\"\\/pluginmarket\\/disable\",\"params\":{\"plugin_key\":\"fadada\"},\"response\":{\"code\":1,\"msg\":\"插件禁用成功\"},\"duration_ms\":40}', 1, 1782371725);
INSERT INTO `system_event_logs` VALUES (1094, 'operation', 'success', 'admin', 1, '超级管理员', 'pluginmarket/checkstatus', 'pluginmarket', '', '插件市场 - 状态变更', '状态检查完成', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', 'GET', 'https://localhost:3006/admin/pluginmarket/checkStatus?plugin_key=fadada', '', '', '{\"route\":\"\\/pluginmarket\\/checkstatus\",\"params\":{\"plugin_key\":\"fadada\"},\"response\":{\"code\":1,\"msg\":\"状态检查完成\"},\"duration_ms\":275}', 1, 1782371798);
INSERT INTO `system_event_logs` VALUES (1095, 'operation', 'success', 'admin', 1, '超级管理员', 'pluginmarket/checkstatus', 'pluginmarket', '', '插件市场 - 状态变更', '状态检查完成', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', 'GET', 'https://localhost:3006/admin/pluginmarket/checkStatus?plugin_key=fadada', '', '', '{\"route\":\"\\/pluginmarket\\/checkstatus\",\"params\":{\"plugin_key\":\"fadada\"},\"response\":{\"code\":1,\"msg\":\"状态检查完成\"},\"duration_ms\":242}', 1, 1782371869);
INSERT INTO `system_event_logs` VALUES (1096, 'permission', 'error', 'admin', 1, '超级管理员', 'pluginmarket/updateauthcode', 'pluginmarket', '', '插件市场 - 更新', '插件未授权，当前套餐不包含此插件', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', 'POST', 'https://localhost:3006/admin/pluginmarket/updateAuthcode', '', '', '{\"route\":\"\\/pluginmarket\\/updateauthcode\",\"params\":{\"plugin_key\":\"fadada\",\"authcode\":\"***\"},\"response\":{\"code\":0,\"msg\":\"插件未授权，当前套餐不包含此插件\"},\"duration_ms\":191}', 0, 1782371943);
INSERT INTO `system_event_logs` VALUES (1097, 'operation', 'success', 'admin', 1, '超级管理员', 'pluginmarket/enable', 'pluginmarket', '', '插件市场 - 启用', '插件启用成功', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', 'POST', 'https://localhost:3006/admin/pluginmarket/enable', '', '', '{\"route\":\"\\/pluginmarket\\/enable\",\"params\":{\"plugin_key\":\"oauth_qq\",\"plugin_type\":\"1\",\"authcode\":\"***\",\"auth_source\":\"package\"},\"response\":{\"code\":1,\"msg\":\"插件启用成功\"},\"duration_ms\":73}', 1, 1782373181);
INSERT INTO `system_event_logs` VALUES (1098, 'operation', 'success', 'admin', 1, '超级管理员', 'pluginmarket/enable', 'pluginmarket', '', '插件市场 - 启用', '插件启用成功', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', 'POST', 'https://localhost:3006/admin/pluginmarket/enable', '', '', '{\"route\":\"\\/pluginmarket\\/enable\",\"params\":{\"plugin_key\":\"marketing\",\"plugin_type\":\"1\",\"authcode\":\"***\",\"auth_source\":\"package\"},\"response\":{\"code\":1,\"msg\":\"插件启用成功\"},\"duration_ms\":58}', 1, 1782373193);
INSERT INTO `system_event_logs` VALUES (1099, 'operation', 'success', 'admin', 1, '超级管理员', 'pluginmarket/enable', 'pluginmarket', '', '插件市场 - 启用', '插件启用成功', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', 'POST', 'https://localhost:3006/admin/pluginmarket/enable', '', '', '{\"route\":\"\\/pluginmarket\\/enable\",\"params\":{\"plugin_key\":\"tiancheng\",\"plugin_type\":\"1\",\"authcode\":\"***\",\"auth_source\":\"package\"},\"response\":{\"code\":1,\"msg\":\"插件启用成功\"},\"duration_ms\":63}', 1, 1782373375);
INSERT INTO `system_event_logs` VALUES (1100, 'operation', 'success', 'admin', 1, '超级管理员', 'pluginmarket/disable', 'pluginmarket', '', '插件市场 - 禁用', '插件禁用成功', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', 'POST', 'https://localhost:3006/admin/pluginmarket/disable', '', '', '{\"route\":\"\\/pluginmarket\\/disable\",\"params\":{\"plugin_key\":\"tiancheng\"},\"response\":{\"code\":1,\"msg\":\"插件禁用成功\"},\"duration_ms\":63}', 1, 1782373379);

-- ----------------------------
-- Table structure for system_policy
-- ----------------------------
DROP TABLE IF EXISTS `system_policy`;
CREATE TABLE `system_policy`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_security_check` tinyint(1) NOT NULL DEFAULT 1 COMMENT '订单安全校验',
  `agent_register_verify` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'none' COMMENT '代理注册验证 none/sms/image',
  `shop_order_verify` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'none' COMMENT '店铺下单验证 none/sms/image',
  `shop_order_idcard_verify` tinyint(1) NOT NULL DEFAULT 0 COMMENT '下单二要素',
  `agent_realname_verify` tinyint(1) NOT NULL DEFAULT 0 COMMENT '代理实名认证能力',
  `agent_realname_two_factor_verify` tinyint(1) NOT NULL DEFAULT 1 COMMENT '代理实名二要素',
  `agent_withdraw_realname_required` tinyint(1) NOT NULL DEFAULT 0 COMMENT '提现是否需要实名',
  `agent_withdraw_verify` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'none' COMMENT '代理提现验证 none/sms/image',
  `verify_code_auto_fill` tinyint(1) NOT NULL DEFAULT 0 COMMENT '验证码自动回填',
  `sms_notice_order_ship` tinyint(1) NOT NULL DEFAULT 0 COMMENT '短信通知-订单发货',
  `sms_notice_order_submit` tinyint(1) NOT NULL DEFAULT 0 COMMENT '短信通知-订单提交',
  `sms_notice_order_pending_ship` tinyint(1) NOT NULL DEFAULT 0 COMMENT '短信通知-订单待发货',
  `sms_notice_order_review_failed` tinyint(1) NOT NULL DEFAULT 0 COMMENT '短信通知-订单审核失败',
  `sms_notice_agent_withdraw` tinyint(1) NOT NULL DEFAULT 0 COMMENT '短信通知-代理提现',
  `sms_notice_agent_level_change` tinyint(1) NOT NULL DEFAULT 0 COMMENT '短信通知-代理等级调整',
  `product_ship_sms_notice` tinyint(1) NOT NULL DEFAULT 0 COMMENT '发货短信通知',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` datetime NULL DEFAULT NULL,
  `update_time` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '系统默认策略' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of system_policy
-- ----------------------------
INSERT INTO `system_policy` (`id`, `order_security_check`, `agent_register_verify`, `shop_order_verify`, `shop_order_idcard_verify`, `agent_realname_verify`, `agent_realname_two_factor_verify`, `agent_withdraw_realname_required`, `agent_withdraw_verify`, `verify_code_auto_fill`, `sms_notice_order_ship`, `sms_notice_order_submit`, `sms_notice_order_pending_ship`, `sms_notice_order_review_failed`, `sms_notice_agent_withdraw`, `sms_notice_agent_level_change`, `product_ship_sms_notice`, `remark`, `create_time`, `update_time`) VALUES (1, 1, 'image', 'none', 0, 0, 1, 0, 'image', 0, 0, 0, 0, 0, 0, 0, 0, '初始化默认策略', '2026-04-06 00:40:42', '2026-06-11 16:35:07');

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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '工单分类配置表' ROW_FORMAT = DYNAMIC;

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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '工单回复表' ROW_FORMAT = DYNAMIC;

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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '工单表' ROW_FORMAT = DYNAMIC;

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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '版本文件处理日志表' ROW_FORMAT = DYNAMIC;

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
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '版本SQL执行日志表' ROW_FORMAT = DYNAMIC;

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
  `withdraw_type` enum('alipay','bank','wechat') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'alipay' COMMENT '提现方式：alipay=支付宝,bank=银行卡,wechat=微信',
  `payout_provider_key` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '打款通道标识(yun_account/alipay_merchant/...)',
  `payout_channel` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '打款渠道(wechat/alipay/bankcard)',
  `payout_order_no` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '通道打款单号',
  `payout_idempotency_key` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '幂等键',
  `payout_request_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '打款请求报文(JSON)',
  `payout_response_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '打款响应报文(JSON)',
  `payout_callback_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'none' COMMENT '回调状态(none/processing/success/failed)',
  `payout_callback_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '回调报文(JSON)',
  `payout_fail_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '失败原因',
  `payout_retry_count` int(11) NOT NULL DEFAULT 0 COMMENT '重试次数',
  `payout_last_retry_time` int(11) NOT NULL DEFAULT 0 COMMENT '最后重试时间戳',
  `payout_risk_pass` tinyint(1) NOT NULL DEFAULT 1 COMMENT '风控是否通过(1通过0拦截)',
  `payout_success_time` int(11) NOT NULL DEFAULT 0 COMMENT '打款成功时间戳',
  `account` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '收款账户',
  `account_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '收款人姓名',
  `bank_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '银行名称（银行卡专用）',
  `bank_branch` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '开户行支行（银行卡专用）',
  `amount` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '申请金额',
  `fee` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '手续费',
  `actual_amount` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '实际到账金额',
  `status` enum('pending','processing','success','failed','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending' COMMENT '状态：pending=待处理,processing=处理中,success=已完成,failed=打款失败,rejected=已拒绝',
  `remark` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '申请备注',
  `admin_remark` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '管理员处理备注',
  `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '申请时间',
  `process_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '处理时间',
  `agent_sms_notice_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '代理提现短信最近通知状态',
  `agent_sms_notice_time` int(11) NOT NULL DEFAULT 0 COMMENT '代理提现短信最近通知时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `withdraw_no`(`withdraw_no`) USING BTREE,
  UNIQUE INDEX `uk_payout_idempotency`(`payout_idempotency_key`) USING BTREE,
  INDEX `agent_id`(`agent_id`) USING BTREE,
  INDEX `payment_method_id`(`payment_method_id`) USING BTREE,
  INDEX `status`(`status`) USING BTREE,
  INDEX `create_time`(`create_time`) USING BTREE,
  INDEX `idx_agent_status`(`agent_id`, `status`) USING BTREE,
  INDEX `idx_status_time`(`status`, `create_time`) USING BTREE,
  INDEX `idx_payout_provider`(`payout_provider_key`) USING BTREE,
  INDEX `idx_payout_channel`(`payout_channel`) USING BTREE,
  INDEX `idx_payout_order_no`(`payout_order_no`) USING BTREE,
  INDEX `idx_payout_callback_status`(`payout_callback_status`) USING BTREE,
  INDEX `idx_payout_success_time`(`payout_success_time`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '提现申请表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of withdraws
-- ----------------------------

-- ----------------------------
-- View structure for v_enabled_payments
-- ----------------------------
DROP VIEW IF EXISTS `v_enabled_payments`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `v_enabled_payments` AS select `pm`.`payment_type` AS `payment_type`,`pm`.`payment_name` AS `payment_name`,`pm`.`sort_order` AS `sort_order`,`pm`.`icon_url` AS `icon_url`,`pm`.`description` AS `description`,group_concat(concat(`pc`.`config_key`,':',`pc`.`config_value`) order by `pc`.`config_key` ASC separator '|') AS `configs` from (`payment_methods` `pm` left join `payment_configs` `pc` on((`pm`.`payment_type` = `pc`.`payment_type`))) where (`pm`.`is_enabled` = 1) group by `pm`.`payment_type`,`pm`.`payment_name`,`pm`.`sort_order`,`pm`.`icon_url`,`pm`.`description` order by `pm`.`sort_order`;

-- ----------------------------
-- Table structure for team incentive policies
-- ----------------------------
DROP TABLE IF EXISTS `agent_team_incentives`;
DROP TABLE IF EXISTS `team_incentive_policies`;
CREATE TABLE `team_incentive_policies` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `policy_name` varchar(100) NOT NULL DEFAULT '' COMMENT '团队激励政策名称',
  `icon` varchar(500) NULL DEFAULT '' COMMENT '等级图片URL',
  `reward_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '每单固定奖励',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_policy_name`(`policy_name`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '团队激励政策' ROW_FORMAT = DYNAMIC;

CREATE TABLE `agent_team_incentives` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `agent_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '团队负责代理ID',
  `policy_id` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `valid_start` int(11) UNSIGNED NULL DEFAULT NULL COMMENT '团队激励绑定开始时间',
  `valid_end` int(11) UNSIGNED NULL DEFAULT NULL COMMENT '团队激励绑定结束时间',
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_agent_id`(`agent_id`) USING BTREE,
  INDEX `idx_policy_status`(`policy_id`, `status`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '代理团队激励绑定' ROW_FORMAT = DYNAMIC;

SET FOREIGN_KEY_CHECKS = 1;
