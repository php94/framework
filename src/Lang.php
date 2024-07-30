<?php

declare(strict_types=1);

namespace PHP94;

use Composer\Autoload\ClassLoader;
use Exception;
use PHP94\Facade\App;
use ReflectionClass;
use SplPriorityQueue;

class Lang
{
    private $root;
    private $langs = [];
    protected $finder;

    public function __construct()
    {
        $this->root = dirname((new ReflectionClass(ClassLoader::class))->getFileName(), 3);

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
            $this->langs = array_keys($xlang);
        }

        $this->finder = new SplPriorityQueue;
        $this->addFinder(function (string $key): ?string {
            static $langs = [];
            if (strpos($key, '@')) {
                list($index, $appname) = explode('@', $key);
                if ($appname && $index && App::isActive($appname)) {
                    $dir = App::getDir($appname);
                    foreach ($this->langs as $lang) {
                        if (!isset($langs[$appname . '.' . $lang])) {
                            $langs[$appname . '.' . $lang] = [];
                            $fullname = $dir . '/src/lang/' . $lang . '.php';
                            if (is_file($fullname)) {
                                $tmp = $this->requireFile($fullname);
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
                foreach ($this->langs as $lang) {
                    if (!isset($langs[$lang])) {
                        $langs[$lang] = [];
                        $fullname = $this->root . '/lang/' . $lang . '.php';
                        if (is_file($fullname)) {
                            $tmp = $this->requireFile($fullname);
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

    public function setLangs(string ...$langs): self
    {
        $res = [];
        foreach ($langs as $vo) {
            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $vo)) {
                throw new Exception('the lang must preg /^[a-zA-Z0-9_-]+$/');
            } else {
                $res[] = $vo;
            }
        }
        $this->langs = $res;
        return $this;
    }

    public function addFinder(callable $callable, $priority = 0): self
    {
        $this->finder->insert($callable, $priority);
        return $this;
    }

    public function get(string $key = '', string $default = null): ?string
    {
        foreach (clone $this->finder as $finder) {
            $res = $finder($key);
            if (!is_null($res)) {
                return $res;
            }
        }
        return $default;
    }

    private function requireFile(string $file)
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
