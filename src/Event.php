<?php

declare(strict_types=1);

namespace PHP94;

use PHP94\Event\Event as EventEvent;

class Event
{
    public static function getInstance(): EventEvent
    {
        return Container::get(EventEvent::class);
    }

    public static function dispatch(object $event)
    {
        return self::getInstance()->dispatch($event);
    }

    public static function listen(string $event, callable $callback)
    {
        self::getInstance()->listen($event, $callback);
    }
}
