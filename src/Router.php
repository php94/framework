<?php

declare(strict_types=1);

namespace PHP94;

use PHP94\Router\Router as RouterRouter;

class Router
{
    public static function getInstance(): RouterRouter
    {
        return Container::get(RouterRouter::class);
    }

    public static function addGroup(string $prefix, callable $callback, array $params = []): RouterRouter
    {
        return self::getInstance()->addGroup($prefix, $callback, $params);
    }

    public static function addRoute(
        string $route,
        string $handler,
        string $name = null,
        array $params = [],
    ): RouterRouter {
        return self::getInstance()->addRoute($route, $handler, $name, $params);
    }

    public static function dispatch(string $uri): ?array
    {
        return self::getInstance()->dispatch($uri);
    }

    public static function build(string $name, array $querys = []): string
    {
        return self::getInstance()->build($name, $querys);
    }

    public static function getData(): array
    {
        return self::getInstance()->getData();
    }

    public static function setBaseUrl(string $baseurl): RouterRouter
    {
        return self::getInstance()->setBaseUrl($baseurl);
    }

    public static function getBaseUrl(): string
    {
        return self::getInstance()->getBaseUrl();
    }
}
