<?php

declare(strict_types=1);

namespace PHP94\Facade;

use DateInterval;
use Psr\SimpleCache\CacheInterface;

class Cache
{
    public static function getInstance(): CacheInterface
    {
        return Container::get(CacheInterface::class);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::getInstance()->get($key, $default);
    }

    public static function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        return self::getInstance()->set($key, $value, $ttl);
    }

    public static function delete(string $key): bool
    {
        return self::getInstance()->delete($key);
    }

    public static function clear(): bool
    {
        return self::getInstance()->clear();
    }

    public static function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        yield self::getInstance()->getMultiple($keys, $default);
    }

    public static function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        return self::getInstance()->setMultiple($values, $ttl);
    }

    public static function deleteMultiple(iterable $keys): bool
    {
        return self::getInstance()->deleteMultiple($keys);
    }

    public static function has(string $key): bool
    {
        return self::getInstance()->has($key);
    }
}
