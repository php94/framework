<?php

declare(strict_types=1);

namespace PHP94\Facade;

use Closure;
use PHP94\Container\Container as ContainerContainer;

class Container
{
    public static function getInstance(): ContainerContainer
    {
        static $container;
        if ($container == null) {
            $container = new ContainerContainer;
        }
        return $container;
    }

    public static function get(string $id, bool $new = false)
    {
        return self::getInstance()->get($id, $new);
    }

    public static function has(string $id): bool
    {
        return self::getInstance()->has($id);
    }

    public static function set(string $id, Closure $fn): ContainerContainer
    {
        return self::getInstance()->set($id, $fn);
    }

    public static function setArgument(string $id, array $args): ContainerContainer
    {
        return self::getInstance()->setArgument($id, $args);
    }

    public static function reflectArguments($callable, array $default = []): array
    {
        return self::getInstance()->reflectArguments($callable, $default);
    }
}
