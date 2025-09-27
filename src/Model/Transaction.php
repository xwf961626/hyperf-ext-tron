<?php

declare(strict_types=1);

namespace William\HyperfExtTron\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property string $hash 
 * @property string $type 
 * @property int $block_id 
 * @property string $contract 
 * @property string $transacted_at 
 * @property int $transacted_time 
 * @property int $transacted_amount_decimals 
 * @property string $client_id 
 * @property int $result 
 * @property string $amount 
 * @property string $from 
 * @property string $to 
 * @property string $coin_name 
 * @property string $text 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class Transaction extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'transactions';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [
        'hash',
        'type',
        'block_id',
        'contract',
        'transacted_at',
        'transacted_time',
        'transacted_amount_decimals',
        'client_id',
        'result',
        'amount',
        'from',
        'to',
        'coin_name',
        'text',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'block_id' => 'integer', 'transacted_time' => 'integer', 'transacted_amount_decimals' => 'integer', 'result' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
