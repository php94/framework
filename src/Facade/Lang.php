<?php

declare(strict_types=1);

namespace PHP94\Facade;

use PHP94\Lang as PHP94Lang;

class Lang
{
    public static function getInstance(): PHP94Lang
    {
        return Container::get(PHP94Lang::class);
    }

    public static function setLangs(string ...$langs): PHP94Lang
    {
        return self::getInstance()->setLangs(...$langs);
    }

    public static function addFinder(callable $callable, $priority = 0): PHP94Lang
    {
        return self::getInstance()->addFinder($callable, $priority);
    }

    public static function get(string $key = '', $default = null): ?string
    {
        return self::getInstance()->get($key, $default);
    }
}
