<?php

declare(strict_types=1);

namespace PHP94\Facade;

use PHP94\Session\Session as SessionSession;

class Session
{
    public static function getInstance(): SessionSession
    {
        return Container::get(SessionSession::class);
    }

    public static function set(string $name, $value)
    {
        self::getInstance()->set($name, $value);
    }

    public static function get(string $name, $default = null)
    {
        return self::getInstance()->get($name, $default);
    }

    public static function delete(string $name)
    {
        self::getInstance()->delete($name);
    }

    public static function has(string $name): bool
    {
        return self::getInstance()->has($name);
    }
}
