<?php

namespace William\HyperfExtTron\Tron\Energy;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class Utils
{
    public static function buildMerchantKey(): string
    {
        return md5(env('SIGNKEY_PRE') . time() . mt_rand(1, 1000000));
    }

    public static function makeOrderNo(): string
    {
        return date('ymdHis') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT) .
            str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    }

    public static function isJson(string $string): bool
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * 解密
     * @param $encryptedData
     * @return string
     */
    public static function cryptoDecrypt($encryptedData): string
    {
        if (empty($encryptedData)) return '';
        $key = env('CRYPTO_KEY'); // 一个随机的 32 字节密钥
        $iv = env('CRYPTO_IV'); // 一个随机的 16 字节初始向量
        $method = 'AES-256-CBC'; // 使用 AES-256-CBC 加密算法
        $decryptedData = openssl_decrypt(base64_decode($encryptedData), $method, $key, OPENSSL_RAW_DATA, $iv); // 执行解密操作
        return rtrim($decryptedData, "\0");
    }

    public static function cryptoEncrypt($encryptedData): string
    {
        $key = env('CRYPTO_KEY'); // 一个随机的 32 字节密钥
        $iv = env('CRYPTO_IV'); // 一个随机的 16 字节初始向量
        $method = 'AES-256-CBC'; // 使用 AES-256-CBC 加密算法
        $encryptedData = openssl_encrypt($encryptedData, $method, $key, OPENSSL_RAW_DATA, $iv); // 执行解密操作
        return base64_encode($encryptedData);
    }

    public static function buildCryptoKey($size): string
    {
        $key = openssl_random_pseudo_bytes($size / 2);
        return bin2hex($key);
    }

    public static function isValidTronAddress($address): bool|int
    {
        //return true;
        // TRON 地址的正则表达式模式
        /*$pattern = '/^T[a-zA-Z0-9]{33}$/';
        return preg_match($pattern, $address);*/
        return (new Tron())->isAddress($address);
    }

    /**
     * 将地址变短
     * @param $address
     * @return string
     */
    public static function shortAddress($address)
    {
        return substr($address, 0, 6) . '...' . substr($address, -6);
    }

    public static function getAddressLink($address)
    {
        return "https://tronscan.org/#/transaction/{$address}";
    }

    /**
     * @param $amount
     * @param $flag bool 是否需要除以1000000
     * @return string
     */
    public static function formatAmount($amount, $flag = true)
    {
        if ($flag) {
            $result = number_format($amount / 1000000, 6, '.', '');
        } else {
            $result = number_format($amount, 6, '.', '');
        }
        $result = rtrim($result, '0'); // 去除末尾的0
        return rtrim($result, '.');
    }

    /**
     * 获取u转t的汇率
     * @return int|mixed
     */
    public static function getUsdt2TrxRate()
    {
        $trx2usdtrate = self::getTrx2UsdtRate();
        return $trx2usdtrate > 0 ? round(1 / $trx2usdtrate, 3) : 0;
    }
    

    public static function getTrx2UsdtRate()
    {
        $url = "https://api.binance.com/api/v3/ticker/price?symbol=TRXUSDT";
        $options = [
            'verify' => false,
        ];
//        if (env('APP_ENV') == 'dev') {
//            $options['proxy'] = [
//                'http' => 'http://127.0.0.1:33210',
//                'https' => 'http://127.0.0.1:33210',
//            ];
//        }
        $client = new Client($options);
        $resp = $client->get($url);
        $result = $resp->getBody()->getContents();
        $retArr = json_decode($result, true);
        return $retArr['price'];
    }

    /**
     * 计算需要补充的能量数
     * @param mixed $currentEnergy 当前可用能量
     * @param mixed $energyLimit 当前总能量
     * @param mixed $toRate 需要补充到的能量百分比
     * @return float|int
     */
    public static function getNeedPower(mixed $currentEnergy, mixed $energyLimit, mixed $toRate): float|int
    {
        $b = $toRate / 100;
        error_log("Rate:" . $b);
        return ($b * $energyLimit - $currentEnergy) / (1 - $b);
    }

    public static function getTrxBalance($address): float
    {
        return round(intval(\William\HyperfExtTronFacades\Tron::balance($address)) / 1000000, 6);
    }

    public static function formatPrice($price)
    {
        // 保留到一位小数
        $firstDecimal = number_format($price, 2, '.', ''); // 取一位小数

        // 随机生成后三位小数部分
        $randomDecimals = str_pad(rand(11, 99), 2, '0', STR_PAD_LEFT); // 补足三位数，不足时前面补0

        // 将整数和小数拼接为符合格式的字符串
        $formattedPrice = $firstDecimal . $randomDecimals;

        return $formattedPrice;
    }

    public static function getPrices(string $trx_price)
    {
        Log::info("trx price = $trx_price");
        $usdt2trxrate = self::getUsdt2TrxRate();
        Log::info("usdt2trxrate = $usdt2trxrate");
        // $lowerRate = AdminSetting::getSetting('usdt2trxrate_lower');
        // Log::info("usdt2trxrate_lower = $lowerRate");
        // $rate = $usdt2trxrate * (1 + $lowerRate);
        $rate = $usdt2trxrate;
        Log::info("rate = $rate");
        $trx_price = self::formatPrice($trx_price);
        $usdt_price = $trx_price == 0 ? 0 : round($trx_price / $rate, 4);
        return [$usdt_price, $trx_price];
    }

    
    /**
     * 根据官方汇率计算USDT和TRX价格
     *
     * 此方法接收一个USDT价格作为输入，使用官方的USDT到TRX的汇率来计算对应的TRX价格。
     * 它会记录相关的日志信息，包括输入的USDT价格、汇率和计算出的比率。
     * 最后返回一个包含格式化后的USDT价格和计算出的TRX价格的数组。
     *
     * @param string $u 输入的USDT价格
     * @return array 包含格式化后的USDT价格和计算出的TRX价格的数组
     */
    public static function getPricesOfficial(string $u)
    {
        // 记录输入的USDT价格
        Log::info("trx price = $u");
        // 获取USDT到TRX的汇率
        $usdt2trxrate = self::getUsdt2TrxRate();
        // 记录USDT到TRX的汇率
        Log::info("usdt2trxrate = $usdt2trxrate");
        // 计算比率
        $rate = $usdt2trxrate ;
        // 记录计算出的比率
        Log::info("rate = $rate");
        // 格式化输入的USDT价格
        $u = self::formatPrice($u);
        // 计算TRX价格
        $trx_price =  round($u * $rate, 4);
        // 返回格式化后的USDT价格和计算出的TRX价格
        return [$u, $trx_price];
    }
}
