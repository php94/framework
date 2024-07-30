<?php

declare(strict_types=1);

namespace PHP94\Facade;

use PHP94\Emitter\Emitter as EmitterEmitter;
use Psr\Http\Message\ResponseInterface;

class Emitter
{
    public static function getInstance(): EmitterEmitter
    {
        return Container::get(EmitterEmitter::class);
    }

    public static function emit(ResponseInterface $response): void
    {
        self::getInstance()->emit($response);
    }
}
