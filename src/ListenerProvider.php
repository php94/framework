<?php

declare(strict_types=1);

namespace PHP94;

use PHP94\Facade\App;
use PHP94\Facade\Config;
use PHP94\Facade\Framework;
use Psr\EventDispatcher\ListenerProviderInterface;

class ListenerProvider implements ListenerProviderInterface
{
    public function getListenersForEvent(object $event): iterable
    {
        foreach (Config::get('listen', []) as $key => $value) {
            if (is_a($event, $key)) {
                yield function ($event) use ($key, $value) {
                    Framework::execute($value, [
                        $key => $event,
                    ]);
                };
            }
        }
        foreach (App::allActive() as $appname) {
            foreach (Config::get('listen@' . $appname, []) as $key => $value) {
                if (is_a($event, $key)) {
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
