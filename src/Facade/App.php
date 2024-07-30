<?php

declare(strict_types=1);

namespace PHP94\Facade;

use PHP94\App as PHP94App;

class App
{
    public static function getInstance(): PHP94App
    {
        return Container::get(PHP94App::class);
    }

    public static function has(string $appname): bool
    {
        return self::getInstance()->has($appname);
    }

    public static function getDir(string $appname): string
    {
        return self::getInstance()->getDir($appname);
    }

    public static function isInstalled(string $appname): bool
    {
        return self::getInstance()->isInstalled($appname);
    }

    public static function isDisabled(string $appname): bool
    {
        return self::getInstance()->isDisabled($appname);
    }

    public static function isCore(string $appname): bool
    {
        return self::getInstance()->isCore($appname);
    }

    public static function isActive(string $appname): bool
    {
        return self::getInstance()->isActive($appname);
    }

    public static function all(): array
    {
        return self::getInstance()->all();
    }

    public static function allActive(): array
    {
        return self::getInstance()->allActive();
    }
}
