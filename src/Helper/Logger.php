<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace William\HyperfExtTron\Helper;

use DateTime;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Logger\LoggerFactory;

class Logger
{
    public static function create()
    {
        return ApplicationContext::getContainer()
            ->get(LoggerFactory::class)
            ->get('bot');
    }

    public static function info(string $string)
    {
        $time = self::getTime();
        $logger = ApplicationContext::getContainer()->get(LoggerFactory::class)->get();
        $logger->info($string);
    }

    public static function debug(string $string)
    {
        $time = self::getTime();
        $logger = ApplicationContext::getContainer()->get(LoggerFactory::class)->get();
        $logger->debug($string);
    }

    public static function getTime()
    {
        $date = new DateTime();
        return $date->format("Y-m-d H:i:s.u");
    }

    public static function error(string $msg)
    {
        $time = self::getTime();
        $logger = ApplicationContext::getContainer()->get(LoggerFactory::class)->get();
        $logger->error($msg);
    }
}
