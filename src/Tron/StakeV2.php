<?php

namespace William\HyperfExtTron\Tron;


class StakeV2
{
    /**
     * @var int 总质押的 TRX 数量，包括冻结用于带宽、能量和委托的 TRX
     */
    public int $totalFrozenV2 = 0;

    /**
     * @var int 用于冻结能量的 TRX 数量
     */
    public int $frozenForEnergyV2 = 0;

    /**
     * @var int 用于冻结带宽的 TRX 数量
     */
    public int $frozenForBandWidthV2 = 0;

    /**
     * @var int 用于能量的委托冻结 TRX 数量
     */
    public int $delegatedFrozenV2BalanceForEnergy = 0;

    /**
     * @var int 用于带宽的委托冻结 TRX 数量
     */
    public int $delegatedFrozenV2BalanceForBandwidth = 0;

    public function __construct()
    {
    }
}