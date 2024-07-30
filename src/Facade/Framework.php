<?php

declare(strict_types=1);

namespace PHP94\Facade;

use PHP94\Framework as PHP94Framework;

class Framework
{
    public static function getInstance(): PHP94Framework
    {
        return Container::get(PHP94Framework::class);
    }

    public static function run()
    {
        self::getInstance()->run();
    }

    public static function execute(callable $callable, array $params = [])
    {
        return self::getInstance()->execute($callable, $params);
    }

    public static function getServerRequest()
    {
        return self::getInstance()->getServerRequest();
    }
}
