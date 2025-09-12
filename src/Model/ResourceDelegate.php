<?php
declare(strict_types=1);

namespace William\HyperfExtTron\Model;


use William\HyperfExtTron\Tron\Energy\Apis\EnergyLogModelInterface;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 
 * @property int $user_id 
 * @property int $quantity 
 * @property int $status 
 * @property string $fail_reason 
 * @property string $tx_id 
 * @property string $response_text 
 * @property string $source 
 * @property string $source_info 
 * @property string $address 
 * @property string $from_address 
 * @property string $time 
 * @property string $resource 
 * @property string $api 
 * @property string $lock_amount 
 * @property int $lock_duration 
 * @property int $resource_address_id 
 * @property string $price 
 * @property string $delegate_at 
 * @property string $undelegate_at 
 * @property string $expired_dt 
 * @property string $undelegate_hash 
 * @property int $undelegate_status 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class ResourceDelegate extends Model implements EnergyLogModelInterface
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'resource_delegates';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [
        'user_id',
        'quantity',
        'status',
        'fail_reason',
        'tx_id',
        'response_text',
        'source',
        'source_info',
        'address',
        'from_address',
        'time',
        'resource',
        'api',
        'lock_amount',
        'lock_duration',
        'resource_address_id',
        'price',
        'delegate_at',
        'undelegate_at',
        'expired_dt',
        'undelegate_hash',
        'undelegate_status',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'user_id' => 'integer', 'quantity' => 'integer', 'status' => 'integer', 'lock_duration' => 'integer', 'resource_address_id' => 'integer', 'undelegate_status' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    function getSuccessStatus()
    {
        return $this->status != -1 ? 1 : 0;
    }

    function getFailReason(): ?string
    {
        return $this->fail_reason;
    }


    public function getCostAmount(): ?string
    {
        return '-';
    }

    public function getDelegateHash(): ?string
    {
        return $this->delegate_hash;
    }

    public function getUnDelegateHash(): ?string
    {
        return $this->undelegate_hash;
    }

    public function getTime(): ?string
    {
        return $this->lock_duration . '秒';
    }

    public function getDelegateStatus(): ?string
    {
        return $this->status > -1 ? 'success' : 'error';
    }

    public function getUnDelegateStatus(): ?string
    {
        return $this->un_delegate_status;
    }

    public function getDelegatedAt()
    {
        return $this->created_at;
    }

    public function getUnDelegatedAt()
    {
        return $this->undelegate_at;
    }
}
