<?php

declare(strict_types=1);

namespace William\HyperfExtTron\Tron\Energy\Model;

use William\HyperfExtTron\Tron\Energy\Apis\EnergyLogModelInterface;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $to_address
 * @property int $power
 * @property string $period
 * @property string $order_sn
 * @property string $order_status
 * @property string $tx_id
 * @property string $from_address
 * @property int $count
 * @property string $price
 * @property string $fee
 * @property string $amount
 * @property string $balance
 * @property string $response_json
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $energy_count
 */
class WeiduEnergyLog extends Model implements EnergyLogModelInterface
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'weidu_energy_logs';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'user_id' => 'integer', 'power' => 'integer', 'count' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime', 'energy_count' => 'integer'];

    public function getDelegateHash(): ?string
    {
        return $this->tx_id;
    }

    public function getUnDelegateHash(): ?string
    {
        return '';
    }

    public function getCostAmount(): ?string
    {
        return $this->amount;
    }

    public function getTime(): ?string
    {
        return $this->period;
    }


    public function getDelegateStatus(): ?string
    {
        return $this->order_status ?: 'pending';
    }

    public function getUnDelegateStatus(): ?string
    {
        return 'unknown';
    }

    public function getDelegatedAt()
    {
        return $this->updated_at;
    }

    public function getUnDelegatedAt()
    {
        return "无回收时间";
    }

    public function getFailReason(): ?string
    {
        return $this->fail_reason;
    }
}
