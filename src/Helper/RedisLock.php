<?php

namespace William\HyperfExtTron\Helper;

use Hyperf\Redis\Redis;

class RedisLock
{
    protected Redis $redis;
    protected string $lockKey;
    protected int $timeout;

    public function __construct(Redis $redis, string $lockKey, int $timeout = 30)
    {
        $this->redis = $redis;
        $this->lockKey = $lockKey;  // 锁的唯一标识
        $this->timeout = $timeout;  // 获取锁的超时时间
    }

    // 获取锁
    public function acquire(): bool
    {
        $startTime = time();
        while (time() - $startTime < $this->timeout) {
            // 使用 SETNX 获取锁
            if ($this->redis->setnx($this->lockKey, 1)) {
                // 获取锁成功
                return true;
            }
            // 等待一段时间后重试
            usleep(100000); // 100ms
        }
        // 获取锁失败
        return false;
    }

    // 释放锁
    public function release(): bool
    {
        // 确保只有持有锁的进程释放锁
        return $this->redis->del($this->lockKey) > 0;
    }
}
