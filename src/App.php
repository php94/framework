<?php

declare(strict_types=1);

namespace PHP94;

use Composer\Autoload\ClassLoader;
use Composer\InstalledVersions;
use Exception;
use ReflectionClass;

class App
{
    private static $lists = [];

    public static function add(string $appname, string $dir = null): bool
    {
        $root = dirname((new ReflectionClass(ClassLoader::class))->getFileName(), 3);
        if (InstalledVersions::isInstalled($appname)) {
            self::$lists[$appname] = [
                'dir' => $root . '/vendor/' . $appname,
                'core' => true,
            ];
            return true;
        } elseif (is_string($dir) && is_dir($dir)) {
            self::$lists[$appname] = [
                'dir' => $dir,
                'core' => false,
            ];
            return true;
        }
        return false;
    }

    public static function has(string $appname): bool
    {
        return isset(self::$lists[$appname]);
    }

    public static function getDir(string $appname): string
    {
        if (!self::has($appname)) {
            throw new Exception($appname . ' is not found!');
        }
        return self::$lists[$appname]['dir'];
    }

    public static function isCore(string $appname): bool
    {
        return isset(self::$lists[$appname]) && self::$lists[$appname]['core'];
    }

    public static function all(): array
    {
        return array_keys(self::$lists);
    }
}
