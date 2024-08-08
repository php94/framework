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
                'installed' => true,
                'disabled' => file_exists($root . '/config/' . $appname . '/disabled.lock'),
            ];
            return true;
        } elseif (is_string($dir) && is_dir($dir)) {
            self::$lists[$appname] = [
                'dir' => $dir,
                'core' => false,
                'installed' => file_exists($root . '/config/' . $appname . '/installed.lock'),
                'disabled' => file_exists($root . '/config/' . $appname . '/disabled.lock'),
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

    public static function isInstalled(string $appname): bool
    {
        return isset(self::$lists[$appname]) && self::$lists[$appname]['installed'];
    }

    public static function isDisabled(string $appname): bool
    {
        return isset(self::$lists[$appname]) && self::$lists[$appname]['disabled'];
    }

    public static function isCore(string $appname): bool
    {
        return isset(self::$lists[$appname]) && self::$lists[$appname]['core'];
    }

    public static function isActive(string $appname): bool
    {
        return self::isInstalled($appname) && !self::isDisabled($appname);
    }

    public static function all(): array
    {
        return array_keys(self::$lists);
    }

    public static function allActive(): array
    {
        $res = [];
        foreach (self::$lists as $key => $value) {
            if ($value['installed'] && !$value['disabled']) {
                $res[] = $key;
            }
        }
        return $res;
    }
}
