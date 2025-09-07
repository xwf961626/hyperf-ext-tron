<?php
declare(strict_types=1);

namespace William\HyperfExtTron\Model;


use William\HyperfExtTron\Tron\Energy\Apis\EnergyLogModelInterface;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property int $resource_address_id
 * @property string $type
 * @property int $count
 * @property int $lock_duration
 * @property int $price
 * @property string $amount
 * @property string $lock_amount
 * @property string $receive_address
 * @property string $delegate_hash
 * @property string $undelegate_hash
 * @property string $status
 * @property string $undelegate_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $text
 * @property int $user_id
 * @property int $source
 * @property string $source_info
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
    protected array $fillable = [];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'resource_address_id' => 'integer', 'count' => 'integer', 'lock_duration' => 'integer', 'price' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime', 'user_id' => 'integer', 'source' => 'integer'];

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
