<?php

declare(strict_types=1);

namespace PHPAPP;

use PHPAPP\Facade\App;
use PHPAPP\Facade\Config;
use PHPAPP\Facade\Framework;
use Psr\EventDispatcher\ListenerProviderInterface;

class ListenerProvider implements ListenerProviderInterface
{
    private $listeners = [];

    public function getListenersForEvent(object $event): iterable
    {
        foreach ($this->listeners as $vo) {
            if (is_a($event, $vo['event'])) {
                yield function ($event) use ($vo) {
                    Framework::execute($vo['callback'], [
                        $vo['event'] => $event,
                    ]);
                };
            }
        }
        foreach (Config::get('listen', []) as $key => $value) {
            if (is_a($event, $key)) {
                if (is_callable($value)) {
                    yield function ($event) use ($key, $value) {
                        Framework::execute($value, [
                            $key => $event,
                        ]);
                    };
                }
            }
        }
        foreach (App::allActive() as $appname) {
            foreach (Config::get('listen@' . $appname, []) as $key => $value) {
                if (is_a($event, $key)) {
                    if (is_callable($value)) {
                        yield function ($event) use ($key, $value) {
                            Framework::execute($value, [
                                $key => $event,
                            ]);
                        };
                    }
                }
            }
        }
    }

    public function listen(string $event, callable $callback): self
    {
        $this->listeners[] = [
            'event' => $event,
            'callback' => $callback
        ];
        return $this;
    }
}
