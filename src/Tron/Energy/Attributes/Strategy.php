<?php

namespace William\HyperfExtTron\Tron\Energy\Attributes;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

#[Attribute(Attribute::TARGET_CLASS)]
class Strategy extends AbstractAnnotation
{
    const TIMES = 'times';  // 按时间区间设置自动切换能量来源
    const MANUAL = 'manual'; // 手动切换，切到什么是什么

    public function __construct(
        public string $name
    )
    {
    }
}
