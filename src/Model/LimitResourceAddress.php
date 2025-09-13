<?php

declare(strict_types=1);

namespace William\HyperfExtTron\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property string $name 
 * @property string $address 
 * @property string $resource 
 * @property int $current_quantity 
 * @property int $total_quantity 
 * @property int $min_quantity 
 * @property int $send_quantity 
 * @property int $status 
 * @property int $send_times 
 * @property int $max_times 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class LimitResourceAddress extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'limit_resource_addresses';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [
        'id',
        'name',
        'address',
        'resource',
        'current_quantity',
        'total_quantity',
        'min_quantity',
        'send_quantity',
        'status',
        'send_times',
        'max_times',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'current_quantity' => 'integer', 'total_quantity' => 'integer', 'min_quantity' => 'integer', 'send_quantity' => 'integer', 'status' => 'integer', 'send_times' => 'integer', 'max_times' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
