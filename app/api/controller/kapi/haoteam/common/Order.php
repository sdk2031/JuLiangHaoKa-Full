<?php
declare(strict_types=1);

namespace app\api\controller\kapi\haoteam\common;

use think\Model;

/**
 * 订单模型🆕
 */
class Order extends Model
{
    // 表名
    protected $name = 'order';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
    
    // 字段信息
    protected $schema = [
        'id'              => 'int',
        'order_no'        => 'string',
        'up_order_no'     => 'string',
        'product_id'      => 'int',
        'product_name'    => 'string',  // 产品名称
        'customer_name'   => 'string',
        'phone'           => 'string',
        'idcard'          => 'string',  // 身份证号码
        'address'         => 'string',
        'api_name'        => 'string',
        'api_config_id'   => 'int',
        'amount'          => 'float',
        'order_status'    => 'string',
        'pay_status'      => 'string',
        'express_company' => 'string',
        'tracking_number' => 'string',
        'remark'          => 'string',
        'user_id'         => 'int',
        'create_time'     => 'datetime',
        'update_time'     => 'datetime',
        'jh_time'         => 'datetime', // 激活时间
        'js_time'         => 'datetime', // 结算时间
        'delete_time'     => 'datetime',
        'shop_code'       => 'string',  // 店铺代码
        'production_number' => 'string', // 生产号码
        'carrier'         => 'string',  // 运营商
        'settlement_mode' => 'string',  // 结算模式
        'product_channel' => 'string',  // 产品渠道
        'phone_location'  => 'string',  // 号码归属地
        'order_source'    => 'string',  // 订单来源
        'recharge_status' => 'string',  // 充值状态(recharged-已充值,pending-待更新)
        'recharge_amount' => 'float',   // 充值金额
        'province'        => 'string',  // 省份
        'city'            => 'string',  // 城市
        'district'        => 'string',  // 区县
        'agent_id'        => 'string',  // 代理ID
        'product_image'   => 'string',  // 产品图片路径
        'id_card_front'   => 'string',  // 身份证正面
        'id_card_back'    => 'string',  // 身份证反面
        'id_card_face'    => 'string',  // 身份证人脸照片
        'id_card_four'    => 'string',  // 第四证照片
        'photo_status'    => 'string',  // 是否已上传照片(2-有,1-无,0-无需上传)
        'name_count'      => 'int',     // 姓名订单数量
        'id_card_count'   => 'int',     // 身份证订单数量
        'phone_count'     => 'int',     // 手机号订单数量
        'commission'       => 'float',  // 佣金
        'js_type'          => 'string', // 结算模式
    ];
}
