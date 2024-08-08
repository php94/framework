<?php

declare(strict_types=1);

namespace PHP94;

class Session
{
    public static function set(string $name, $value)
    {
        $_SESSION[self::getPrefix() . $name] = serialize($value);
    }

    public static function get(string $name, $default = null)
    {
        return isset($_SESSION[self::getPrefix() . $name]) ? unserialize($_SESSION[self::getPrefix() . $name]) : $default;
    }

    public static function delete(string $name)
    {
        unset($_SESSION[self::getPrefix() . $name]);
    }

    public static function has(string $name): bool
    {
        return isset($_SESSION[self::getPrefix() . $name]);
    }

    private static function getPrefix(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        return dirname('/' . implode('/', array_filter(
            explode('/', $_SERVER['SCRIPT_NAME']),
            function ($val) {
                return strlen($val) > 0 ? true : false;
            }
        )));
    }
}
