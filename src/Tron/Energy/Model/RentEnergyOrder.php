<?php

declare(strict_types=1);

namespace William\HyperfExtTron\Tron\Energy\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property int $user_id 
 * @property array $time_expire_way
 * @property int $energy_num 
 * @property string $user_address 
 * @property string $price 
 * @property int $pay_type 
 * @property int $status 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 * @property string $pay_address 
 * @property int $energy_log_id 
 * @property string $usdt_price 
 * @property string $trx_price 
 * @property int $pay_order_id 
 * @property string $energy_policy 
 */
class RentEnergyOrder extends Model
{
    const PER_ENERGY_NUM = 65500;
    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR = 'error';
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'rent_energy_orders';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'user_id' => 'integer', 'time_expire_way' => 'integer', 'energy_num' => 'integer', 'pay_type' => 'integer', 'status' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime', 'energy_log_id' => 'integer', 'pay_order_id' => 'integer'];
}
