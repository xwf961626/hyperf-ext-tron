<?php

declare(strict_types=1);

namespace William\HyperfExtTron\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property string $name 
 * @property string $url 
 * @property string $api_key 
 * @property string $api_secret 
 * @property string $balance 
 * @property int $price 
 * @property string $status 
 * @property string|null $callback_url
 * @property int $weight
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class Api extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'apis';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [
        'name',
        'url',
        'api_key',
        'api_secret',
        'balance',
        'price',
        'status',
        'weight',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'price' => 'integer', 'weight' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
