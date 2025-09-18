<?php

namespace William\HyperfExtTron\Tron;


class TransactionRequest extends BaseRequest
{
    public bool $only_confirmed = false;
    public bool $only_unconfirmed = false;
    public int $limit = 20;
    public int $min_timestamp = 0;
    public int|null $max_timestamp = null;
    public string $contract_address = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t';
    public string|null $fingerprint = null;
    public string $order_by = 'block_timestamp,desc';
    public bool $only_to = false;
    public bool $only_from = false;

    function getSdkResult()
    {
        $params = json_decode(json_encode($this), true);
        foreach ($params as $key => $val) {
            if (!$val) {
                unset($params[$key]);
            }
        }
        return $params;
    }
}