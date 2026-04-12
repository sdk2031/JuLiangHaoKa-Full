<?php

namespace app\api\controller\kapi\haoteam\common;

use think\Model;

/**
 * API配置模型🆕
 */
class ApiConfig extends Model
{
    // 表名
    protected $name = 'config_api';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
    
    // 字段信息
    protected $schema = [
        'id'           => 'int',
        'api_type'     => 'string',
        'name'         => 'string',
        'api_key'      => 'string',
        'api_secret'   => 'string',
        'api_url'      => 'string',
        'status'       => 'int',
        'commission_deduction_amount' => 'int',
        'sync_settlement' => 'int',
        'create_time'  => 'int',
        'update_time'  => 'int',
        'delete_time'  => 'int',
    ];
} 
