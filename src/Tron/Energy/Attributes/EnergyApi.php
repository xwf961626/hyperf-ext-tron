<?php

namespace William\HyperfExtTron\Tron\Energy\Attributes;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

#[Attribute(Attribute::TARGET_CLASS)]
class EnergyApi extends AbstractAnnotation
{
    const API_WEIDU = 'weidu';
    const API_POOL = 'pool';

    public function __construct(
        public string $name
    )
    {
    }
}
