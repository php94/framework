<?php

declare(strict_types=1);

namespace PHP94;

use Exception;
use SplPriorityQueue;

class Lang
{
    private static $langs = [];

    public static function get(string $key = '', string $default = null): ?string
    {
        foreach (clone self::getFinderQueue() as $finder) {
            $res = $finder($key);
            if (!is_null($res)) {
                return $res;
            }
        }
        return $default;
    }

    public static function setLangs(string ...$langs)
    {
        $res = [];
        foreach ($langs as $vo) {
            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $vo)) {
                throw new Exception('the lang must preg /^[a-zA-Z0-9_-]+$/');
            } else {
                $res[] = $vo;
            }
        }
        self::$langs = $res;
    }

    public static function getLangs(): array
    {
        return self::$langs;
    }

    public static function addFinder(callable $callable, $priority = 0)
    {
        self::getFinderQueue()->insert($callable, $priority);
    }

    private static function getFinderQueue(): SplPriorityQueue
    {
        static $queue;
        if (!$queue) {
            $queue = new SplPriorityQueue;
        }
        return $queue;
    }
}
