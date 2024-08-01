<?php

declare(strict_types=1);

namespace PHP94\Facade;

use PHP94\Event\Event as EventEvent;
use PHP94\ListenerProvider;
use Psr\EventDispatcher\ListenerProviderInterface;

class Event
{
    public static function dispatch(object $event)
    {
        return self::getInstance()->dispatch($event);
    }

    public static function listen(string $event, callable $callback)
    {
        self::getListenerProvider()->listen($event, $callback);
    }

    public static function addProvider(ListenerProviderInterface $listenerProvider): EventEvent
    {
        return self::getInstance()->addProvider($listenerProvider);
    }

    public static function getInstance(): EventEvent
    {
        return Container::get(EventEvent::class);
    }

    private static function getListenerProvider(): ListenerProvider
    {
        return Container::get(ListenerProvider::class);
    }
}
