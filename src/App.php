<?php

declare(strict_types=1);

namespace PHP94;

use Composer\Autoload\ClassLoader;
use Exception;
use ReflectionClass;

class App
{
    private $lists = [];

    public function __construct()
    {
        $root = dirname((new ReflectionClass(ClassLoader::class))->getFileName(), 3);
        if (file_exists($root . '/vendor/composer/installed.json')) {
            foreach (json_decode(file_get_contents($root . '/vendor/composer/installed.json'), true)['packages'] as $pkg) {
                if ($pkg['type'] != 'php94') {
                    continue;
                }
                $this->lists[$pkg['name']] = [
                    'dir' => $root . '/vendor/' . $pkg['name'],
                    'core' => true,
                    'installed' => true,
                    'disabled' => file_exists($root . '/config/' . $pkg['name'] . '/disabled.lock'),
                ];
            }
        }

        spl_autoload_register(function (string $class) use ($root) {
            $paths = explode('\\', $class);
            if (isset($paths[3]) && $paths[0] == 'App') {
                $appname = strtolower(preg_replace('/([A-Z])/', "-$1", lcfirst($paths[1])))
                    . '/'
                    . strtolower(preg_replace('/([A-Z])/', "-$1", lcfirst($paths[2])));
                if ($this->isActive($appname)) {
                    $file = $root . '/app/' . $appname
                        . '/src/library/'
                        . str_replace('\\', '/', substr($class, strlen($paths[0]) + strlen($paths[1]) + strlen($paths[2]) + 3))
                        . '.php';
                    if (file_exists($file)) {
                        include $file;
                    }
                }
            }
        });

        foreach (glob($root . '/app/*/*/composer.json') as $file) {
            $appname = substr(substr($file, strlen($root . '/app/')), 0, -strlen('/composer.json'));
            if (isset($this->lists[$appname])) {
                continue;
            }
            $this->lists[$appname] = [
                'dir' => $root . '/app/' . $appname,
                'core' => false,
                'installed' => file_exists($root . '/config/' . $appname . '/installed.lock'),
                'disabled' => file_exists($root . '/config/' . $appname . '/disabled.lock'),
            ];
        }
    }

    public function has(string $appname): bool
    {
        return isset($this->lists[$appname]);
    }

    public function getDir(string $appname): string
    {
        if (!$this->has($appname)) {
            throw new Exception($appname . ' is not found!');
        }
        return $this->lists[$appname]['dir'];
    }

    public function isInstalled(string $appname): bool
    {
        return isset($this->lists[$appname]) && $this->lists[$appname]['installed'];
    }

    public function isDisabled(string $appname): bool
    {
        return isset($this->lists[$appname]) && $this->lists[$appname]['disabled'];
    }

    public function isCore(string $appname): bool
    {
        return isset($this->lists[$appname]) && $this->lists[$appname]['core'];
    }

    public function isActive(string $appname): bool
    {
        return $this->isInstalled($appname) && !$this->isDisabled($appname);
    }

    public function all(): array
    {
        return array_keys($this->lists);
    }

    public function allActive(): array
    {
        $res = [];
        foreach ($this->lists as $key => $value) {
            if ($value['installed'] && !$value['disabled']) {
                $res[] = $key;
            }
        }
        return $res;
    }
}
