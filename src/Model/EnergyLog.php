<?php

declare(strict_types=1);

namespace William\HyperfExtTron\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property int $user_id 
 * @property int $power_count 
 * @property int $status 
 * @property string $fail_reason 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 * @property string $tx_id 
 * @property string $response_text 
 * @property int $source 
 * @property string $source_info 
 * @property string $address 
 * @property string $time 
 * @property string $energy_policy 
 * @property string $lock_amount 
 * @property int $lock_duration 
 * @property int $resource_address_id 
 * @property string $price 
 * @property string $undelegate_at 
 * @property string $undelegate_hash 
 * @property int $undelegate_status
 * @property string|null $from_address
 * @property string|null $expired_dt
 */
class EnergyLog extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'energy_logs';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [
        'user_id',
        'power_count',
        'status',
        'fail_reason',
        'tx_id',
        'response_text',
        'source',
        'source_info',
        'address',
        'time',
        'energy_policy',
        'lock_amount',
        'lock_duration',
        'resource_address_id',
        'price',
        'undelegate_at',
        'undelegate_hash',
        'undelegate_status',
        'from_address',
        'expired_dt',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'user_id' => 'integer', 'power_count' => 'integer', 'status' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime', 'source' => 'integer', 'lock_duration' => 'integer', 'resource_address_id' => 'integer', 'undelegate_status' => 'integer'];
}
