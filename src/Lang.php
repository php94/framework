<?php

declare(strict_types=1);

namespace PHP94;

use Composer\Autoload\ClassLoader;
use Exception;
use ReflectionClass;
use SplPriorityQueue;

class Lang
{
    private static $langs = [];

    public static function get(string $key = '', string $default = null): ?string
    {
        self::init();
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
        self::init();
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
        self::init();
        return self::$langs;
    }

    public static function addFinder(callable $callable, $priority = 0)
    {
        self::init();
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

    private static function init()
    {
        static $init;
        if ($init) {
            return;
        }
        $init = 1;
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            $xlang = [];
            foreach ($languages as $language) {
                $parts = explode(';q=', trim($language));
                $fullLanguage = trim($parts[0]);
                if (preg_match('/^[a-zA-Z0-9_-]+$/', $fullLanguage)) {
                    $quality = (isset($parts[1])) ? trim($parts[1]) : '1';
                    $xlang[$fullLanguage] = floatval($quality);
                }
            }
            arsort($xlang);
            self::setLangs(...array_keys($xlang));
        }

        self::addFinder(function (string $key): ?string {
            static $langs = [];
            if (strpos($key, '@')) {
                list($index, $appname) = explode('@', $key);
                if ($appname && $index && App::isActive($appname)) {
                    $dir = App::getDir($appname);
                    foreach (self::getLangs() as $lang) {
                        if (!isset($langs[$appname . '.' . $lang])) {
                            $langs[$appname . '.' . $lang] = [];
                            $fullname = $dir . '/src/lang/' . $lang . '.php';
                            if (is_file($fullname)) {
                                $tmp = self::requireFile($fullname);
                                if (is_array($tmp)) {
                                    $langs[$appname . '.' . $lang] = $tmp;
                                }
                            }
                        }
                        if (isset($langs[$appname . '.' . $lang][$index])) {
                            return $langs[$appname . '.' . $lang][$index];
                        }
                    }
                }
            } else {
                $root = dirname((new ReflectionClass(ClassLoader::class))->getFileName(), 3);
                foreach (self::getLangs() as $lang) {
                    if (!isset($langs[$lang])) {
                        $langs[$lang] = [];
                        $fullname = $root . '/lang/' . $lang . '.php';
                        if (is_file($fullname)) {
                            $tmp = self::requireFile($fullname);
                            if (is_array($tmp)) {
                                $langs[$lang] = $tmp;
                            }
                        }
                    }
                    if (isset($langs[$lang][$key])) {
                        return $langs[$lang][$key];
                    }
                }
            }
            return null;
        });
    }

    private static function requireFile(string $file)
    {
        static $loader;
        if (!$loader) {
            $loader = new class()
            {
                public function load(string $file)
                {
                    return require $file;
                }
            };
        }
        return $loader->load($file);
    }
}
