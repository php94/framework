<?php

declare(strict_types=1);

namespace PHP94\Facade;

use PHP94\Handler\Handler as HandlerHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

class Handler
{
    public static function getInstance(): HandlerHandler
    {
        return Container::get(HandlerHandler::class);
    }

    public static function pushMiddleware(...$middlewares)
    {
        return self::getInstance()->pushMiddleware(...$middlewares);
    }

    public static function unShiftMiddleware(...$middlewares)
    {
        return self::getInstance()->unShiftMiddleware(...$middlewares);
    }

    public static function popMiddleware(): ?MiddlewareInterface
    {
        return self::getInstance()->popMiddleware();
    }

    public static function shiftMiddleware(): ?MiddlewareInterface
    {
        return self::getInstance()->shiftMiddleware();
    }

    public static function handle(ServerRequestInterface $serverRequest): ResponseInterface
    {
        return self::getInstance()->handle($serverRequest);
    }
}
