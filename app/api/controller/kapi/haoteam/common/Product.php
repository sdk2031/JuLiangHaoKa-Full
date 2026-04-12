<?php

namespace app\api\controller\kapi\haoteam\common;

use think\Model;

/**
 * 产品模型🆕
 */
class Product extends Model
{
    // 表名
    protected $name = 'product';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
    
    // 时间戳格式为日期时间
    protected $dateFormat = 'Y-m-d H:i:s';
    
    // 字段信息 - 包含所有数据库字段
    protected $schema = [
        'id'              => 'int',
        'name'            => 'string',
        'product_image'   => 'string',
        'detail_images'   => 'string',
        'number'          => 'string',
        'api_name'        => 'string',
        'api_config_id'   => 'int',
        'yys'             => 'string',
        'status'          => 'int',
        'commission'      => 'float',
        'js_type'         => 'int',
        'js_require'      => 'string',
        'create_time'     => 'datetime',
        'update_time'     => 'datetime',
        'yuezu'           => 'float',
        'selectNumber'    => 'int',
        'isHot'           => 'int',
        'hot_sort'        => 'int',
        'tags'            => 'string',
        'flow'            => 'int',
        'dingxiang'       => 'int',
        'call'            => 'int',
        'sms'             => 'int',
        'first_chongzhi'  => 'int',
        'rule'            => 'string',
        'peisong'         => 'string',
        'kaika'           => 'string',
        'age'             => 'string',
        'heyue'           => 'string',
        'jinfa'           => 'string',
        'kefa'            => 'string',
        'guishudi'        => 'string',
        'mark'            => 'string',
        'is_id_photo'     => 'int',
        'is_four_photo'   => 'int',
        'four_photo_title' => 'string',
        'four_photo'      => 'string',
        'beizhu'          => 'string',
    ];
    
    /**
     * 关联产品详情
     */
    public function details()
    {
        return $this->hasMany(ProductDetail::class, 'product_id', 'id');
    }
} 
