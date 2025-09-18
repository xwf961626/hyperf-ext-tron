<?php

namespace William\HyperfExtTron\Tron;


class TransactionRequest extends BaseRequest
{
    /**
     * 每页项目数。默认 10
     * @var int
     */
    public $limit=10;
    /**
     * 起始编号。默认 0
     * @var int
     */
    public $start=0;
    /**
     * 开始时间
     * @var int
     */
    public $start_timestamp;
    /**
     * 时间结束
     * @var int
     */
    public $end_timestamp;
    /**
     * 寄件人地址
     * @var string
     */
    public $contract_address;
    public $relatedAddress;
    /**
     * 接收者的地址
     * @var string
     */
    public $toAddress;
    public $confirm;

    function getSdkResult()
    {
        $params = json_decode(json_encode($this), true);
        foreach ($params as $key=>$val) {
            if(!$val) {
                unset($params[$key]);
            }
        }
        return $params;
    }
}