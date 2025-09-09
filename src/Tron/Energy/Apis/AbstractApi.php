<?php

namespace William\HyperfExtTron\Tron\Energy\Apis;

use William\HyperfExtTron\Model\Api;

/**
 * @property Api $model
 */
abstract class AbstractApi implements ApiInterface
{
    public ?Api $model;

    public function setModel(mixed $api)
    {
        $this->model = $api;
    }
}
