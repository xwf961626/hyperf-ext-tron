<?php

declare(strict_types=1);

namespace William\HyperfExtTron\Tron;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property string $api_key 
 * @property string $type 
 * @property string $status 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class TronApiKey extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'tron_api_keys';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [
        'api_key',
        'type',
        'status'
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
