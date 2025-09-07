<?php

declare(strict_types=1);

namespace William\HyperfExtTron\Tron\Energy\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property string $quickrent_address 
 * @property string $user_address 
 * @property string $receive_price 
 * @property string $transfer_hash 
 * @property int $status 
 * @property string $notify_date 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 * @property int $energy_log_id 
 * @property int $energy_num 
 * @property string $energy_policy 
 * @property string $energy_period 
 * @property string $fail_reason 
 */
class QuickrentOrder extends Model
{
    const STATUS_ERROR = 'error';
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'quickrent_orders';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'status' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime', 'energy_log_id' => 'integer', 'energy_num' => 'integer'];
}
