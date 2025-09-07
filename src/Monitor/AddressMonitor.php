<?php

declare(strict_types=1);

namespace William\HyperfExtTron\Monitor;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property int $user_id 
 * @property string $address 
 * @property int $type 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 * @property int $chain_id 
 * @property int $status 
 */
class AddressMonitor extends Model
{
    const TYPE_QUOTA = 1;
    const TYPE_MONITOR = 2;
    const TYPE_RECHARGE = 3;
    const TYPE_RENT = 4;
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'address_monitors';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [
        'address',
        'user_id',
        'type',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'user_id' => 'integer', 'type' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime', 'chain_id' => 'integer', 'status' => 'integer'];
}
