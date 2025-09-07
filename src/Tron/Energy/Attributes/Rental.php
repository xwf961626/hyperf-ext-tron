<?php

namespace William\HyperfExtTron\Tron\Energy\Attributes;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

#[Attribute(Attribute::TARGET_CLASS)]
class Rental extends AbstractAnnotation
{
    const BALANCE_QUICK_RENT = 'balance_quick_rent'; // 余额闪租
    const QUICK_RENT = 'quick_rent'; // 闪租
    const RENT = 'rent'; // 能量租赁

    public function __construct(
        public string $name
    )
    {
    }
}
