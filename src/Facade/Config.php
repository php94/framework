<?php

declare(strict_types=1);

namespace PHP94\Facade;

use PHP94\Config as PHP94Config;

class Config
{
    public static function getInstance(): PHP94Config
    {
        return Container::get(PHP94Config::class);
    }

    public static function get(string $key = '', $default = null)
    {
        return self::getInstance()->get($key, $default);
    }

    public static function set(string $key, $value = null)
    {
        return self::getInstance()->set($key, $value);
    }

    public static function save(string $key, $value)
    {
        return self::getInstance()->save($key, $value);
    }
}
