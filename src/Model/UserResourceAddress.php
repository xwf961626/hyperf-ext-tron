<?php

declare(strict_types=1);

namespace William\HyperfExtTron\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property string $name 
 * @property string $address 
 * @property string $mode 
 * @property string $type 
 * @property int $weight 
 * @property string $approve_status 
 * @property string $status 
 * @property string $balance 
 * @property int $energy_limit 
 * @property int $energy 
 * @property int $max_delegate_energy 
 * @property int $bandwidth_limit 
 * @property int $bandwidth 
 * @property int $max_delegate_bandwidth 
 * @property int $free_bandwidth 
 * @property int $power_limit 
 * @property int $power 
 * @property int $permission 
 * @property string $config 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 * @property string $last_delegate_at 
 * @property string $sort_num
 * @property string $operate_address
 */
class UserResourceAddress extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'user_resource_addresses';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [
        'name',
        'address',
        'mode',
        'type',
        'weight',
        'approve_status',
        'status',
        'balance',
        'energy_limit',
        'energy',
        'max_delegate_energy',
        'bandwidth_limit',
        'bandwidth',
        'max_delegate_bandwidth',
        'free_bandwidth',
        'power_limit',
        'power',
        'permission',
        'config',
        'last_delegate_at',
        'sort_num',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'weight' => 'integer', 'energy_limit' => 'integer', 'energy' => 'integer', 'max_delegate_energy' => 'integer', 'bandwidth_limit' => 'integer', 'bandwidth' => 'integer', 'max_delegate_bandwidth' => 'integer', 'free_bandwidth' => 'integer', 'power_limit' => 'integer', 'power' => 'integer', 'permission' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
