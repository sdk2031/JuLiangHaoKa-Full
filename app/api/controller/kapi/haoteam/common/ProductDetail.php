<?php
declare(strict_types=1);

namespace app\api\controller\kapi\haoteam\common;

use think\Model;

/**
 * 产品详情模型🆕
 */
class ProductDetail extends Model
{
    // 表名
    protected $name = 'product_detail';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
    
    // 字段信息
    protected $schema = [
        'id'           => 'int',
        'product_id'   => 'int',
        'sku'          => 'string',
        'attributes'   => 'string',
        'stock'        => 'int',
        'status'       => 'int',
        'create_time'  => 'int',
        'update_time'  => 'int',
        'delete_time'  => 'int',
    ];
    
    /**
     * 关联产品
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
} 