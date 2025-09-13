<?php

namespace William\HyperfExtTron\Tron;

class AccountResource
{
    public int $currentEnergy = 0;
    public int $totalEnergy = 0;
    public int $currentNet = 0;
    public int $totalNet = 0;
    public int $totalEnergyWeight = 0;
    public int $totalNetWeight = 0;
    public int $totalNetLimit = 0;
    public int $totalEnergyLimit = 0;

    public static function of(mixed $result): self
    {
        $self = new self();
        $energyLimit = $result->EnergyLimit ?? 0;
        $energyUsed = $result->EnergyUsed ?? 0;
        $netLimit = $result->NetLimit ?? 0;
        $netUsed = $result->NetUsed ?? 0;
        $freeNetLimit = $result->freeNetLimit ?? 0;
        $freeNetUsed = $result->freeNetUsed ?? 0;
        $freeNet = $freeNetLimit - $freeNetUsed;
        $self->currentEnergy = $energyLimit - $energyUsed;
        $self->totalEnergy = $energyLimit;

        $self->currentNet = $netLimit - $netUsed + $freeNet;
        $self->totalNet = $netLimit + $freeNet;
        $self->totalEnergyWeight = $result->TotalEnergyWeight ?? 0;
        $self->totalNetWeight = $result->TotalNetWeight ?? 0;
        $self->totalNetLimit = $result->TotalNetLimit ?? 0;
        $self->totalEnergyLimit = $result->TotalEnergyLimit ?? 0;
        return $self;
    }
}