<?php

namespace William\HyperfExtTron\Tron\Energy\Apis;

interface EnergyLogModelInterface
{
    /**
     * 获取代理资源的 hash
     * @return ?string
     */
    public function getDelegateHash(): ?string;

    /**
     * 获取回收资源的 hash
     * @return ?string
     */
    public function getUnDelegateHash(): ?string;

    /**
     * 获取第三方平台的花费
     * @return ?string
     */
    public function getCostAmount(): ?string;

    /**
     * 获取能量到期时间
     *
     * @return string|null
     */
    public function getTime(): ?string;


    public function getDelegateStatus();
    public function getDelegatedAt();
    public function getUnDelegatedAt();

    public function getUnDelegateStatus();
    public function getFailReason(): ?string;
}
