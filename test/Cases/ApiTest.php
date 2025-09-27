<?php

namespace HyperfTest\Cases;

use Hyperf\Testing\TestCase;
use William\HyperfExtTron\Model\ResourceDelegate;

class ApiTest extends TestCase
{
    public function testAliasname()
    {
        $builder = ResourceDelegate::with(['apiInfo']);

        $list = $builder->orderBy('id', 'desc')->get();
        print_r($list->toArray());
    }
}