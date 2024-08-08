<?php

declare(strict_types=1);

namespace PHP94;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Handler implements RequestHandlerInterface
{
    private static $middlewares = [];

    public static function getInstance(): self
    {
        return Container::get(Handler::class);
    }

    public static function pushMiddleware(...$middlewares)
    {
        array_push(self::$middlewares, ...$middlewares);
    }

    public static function unShiftMiddleware(...$middlewares)
    {
        array_unshift(self::$middlewares, ...$middlewares);
    }

    public static function popMiddleware(): ?MiddlewareInterface
    {
        if ($middleware = array_pop(self::$middlewares)) {
            return Container::get($middleware);
        }
        return null;
    }

    public static function shiftMiddleware(): ?MiddlewareInterface
    {
        if ($middleware = array_shift(self::$middlewares)) {
            return Container::get($middleware);
        }
        return null;
    }

    public function handle(ServerRequestInterface $serverRequest): ResponseInterface
    {
        if ($middleware = self::shiftMiddleware()) {
            return $middleware->process($serverRequest, $this);
        } elseif ($handler = $this->getRequestHandler()) {
            return $handler->handle($serverRequest);
        } else {
            return Factory::createResponse(404);
        }
    }

    private function getRequestHandler(): ?RequestHandlerInterface
    {
        $appname = Request::getServerRequest()->getAttribute('appname', '');
        if (App::isActive($appname)) {
            $cls = Request::getServerRequest()->getAttribute('handler', '');
            if (class_exists($cls) && is_subclass_of($cls, RequestHandlerInterface::class, true)) {
                return Container::get($cls);
            }
        }
        return null;
    }
}
