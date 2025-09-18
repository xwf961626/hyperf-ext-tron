<?php

namespace William\HyperfExtTron\Tron;

class Account
{
    public $address;
    public $balance;
    public $votes = [];
    public $net_usage;
    public $create_time;
    public $latest_opration_time;
    public $latest_withdraw_time;
    public $latest_consume_time;
    public $latest_consume_free_time;
    public $net_window_size;
    public $net_window_optimized;
    public $account_resource;
    public $owner_permission;
    public $active_permission = [];
    public $frozenV2 = [];
    public $delegated_frozenV2_balance_for_bandwidth;
    public $acquired_delegated_frozenV2_balance_for_bandwidth;
    public $assetV2 = [];
    public $free_asset_net_usageV2 = [];
    public $asset_optimized;
    public StakeV2 $stakeV2;

    public function __construct(array $arr = [])
    {
        // Initialize all fields from the $arr input array
        $this->address = $arr['address'] ?? null;
        $this->balance = $arr['balance'] ?? 0;
        $this->votes = $arr['votes'] ?? [];
        $this->net_usage = $arr['net_usage'] ?? 0;
        $this->create_time = $arr['create_time'] ?? 0;
        $this->latest_opration_time = $arr['latest_opration_time'] ?? 0;
        $this->latest_withdraw_time = $arr['latest_withdraw_time'] ?? 0;
        $this->latest_consume_time = $arr['latest_consume_time'] ?? 0;
        $this->latest_consume_free_time = $arr['latest_consume_free_time'] ?? 0;
        $this->net_window_size = $arr['net_window_size'] ?? 0;
        $this->net_window_optimized = $arr['net_window_optimized'] ?? false;

        // Account resource (nested object)
        $this->account_resource = $arr['account_resource'] ?? [];

        // Owner permission (nested object)
        $this->owner_permission = $arr['owner_permission'] ?? [];

        // Active permissions (array of objects)
        $this->active_permission = $arr['active_permission'] ?? [];

        // Frozen data (array of objects)
        $this->frozenV2 = $arr['frozenV2'] ?? [];

        // Delegated frozen V2 balance for bandwidth and acquired delegated frozen balance for bandwidth
        $this->delegated_frozenV2_balance_for_bandwidth = $arr['delegated_frozenV2_balance_for_bandwidth'] ?? 0;
        $this->acquired_delegated_frozenV2_balance_for_bandwidth = $arr['acquired_delegated_frozenV2_balance_for_bandwidth'] ?? 0;

        // Asset V2 (array of objects)
        $this->assetV2 = $arr['assetV2'] ?? [];

        // Free asset net usage V2 (array of objects)
        $this->free_asset_net_usageV2 = $arr['free_asset_net_usageV2'] ?? [];

        // Asset optimized (boolean)
        $this->asset_optimized = $arr['asset_optimized'] ?? false;

        $this->initStakeV2();
    }

    protected function initStakeV2(): void
    {
        $stakeV2 = new StakeV2();
        if ($this->frozenV2) {
            foreach ($this->frozenV2 as $value) {
                if (isset($value['amount'])) {
                    if (isset($value['type']) && $value['type'] === 'ENERGY') {
                        $stakeV2->frozenForEnergyV2 = $value['amount'];
                    } else {
                        $stakeV2->frozenForBandWidthV2 = $value['amount'];
                    }
                }
            }
        }
        $stakeV2->delegatedFrozenV2BalanceForEnergy = $this->account_resource['delegated_frozenV2_balance_for_energy'] ?? 0;
        $stakeV2->delegatedFrozenV2BalanceForBandwidth = $this->delegated_frozenV2_balance_for_bandwidth;
        $stakeV2->totalFrozenV2 = $stakeV2->frozenForEnergyV2 + $stakeV2->frozenForBandWidthV2 +
            $stakeV2->delegatedFrozenV2BalanceForEnergy + $stakeV2->delegatedFrozenV2BalanceForBandwidth;
        $this->stakeV2 = $stakeV2;
    }

    /**
     * 是否质押
     * @return bool
     */
    public function isStake(): bool
    {
        return $this->stakeV2->totalFrozenV2 > 0;
    }
}
