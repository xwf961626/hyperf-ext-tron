<?php

namespace William\HyperfExtTron\Tron;

/**
 * Class Transaction
 *
 * 表示一笔链上交易记录
 *
 * @property string $tx_id           交易哈希（唯一标识交易）
 * @property string $currency        币种（如 USDT、TRX、BTC）
 * @property float  $amount          转账金额
 * @property string $from            转出地址
 * @property string $to              转入地址
 * @property string $type            交易类型（如 deposit、withdraw）
 * @property int    $timestamp       交易时间戳（Unix 时间戳，单位秒）
 * @property string $timestamp_format 格式化的交易时间（如 2025-09-05 19:40:00）
 */
class Transaction
{
    public string $tx_id;
    public string $currency;
    public float $amount;
    public string $from;
    public string $to;
    public string $type;
    public int $timestamp;
    public string $timestamp_format;

    public static function of(array $array): Transaction
    {
        $tx = new self();
        $tx->tx_id = $array['tx_id'];
        $tx->currency = $array['currency'];
        $tx->amount = $array['amount'];
        $tx->from = $array['from'];
        $tx->to = $array['to'];
        $tx->type = $array['type'];
        if(isset($array['timestamp'])) {
            $tx->timestamp = $array['timestamp'];
            $tx->timestamp_format = date('Y-m-d H:i:s', $array['timestamp']);
        }
        return $tx;
    }
}