<?php

declare(strict_types=1);

namespace PHP94;

use Closure;
use Composer\Autoload\ClassLoader;
use ErrorException;
use Exception;
use PHP94\Cache\NullCache;
use PHP94\Container\Container as ContainerContainer;
use PHP94\Emitter\Emitter;
use PHP94\Event\Event as EventEvent;
use PHP94\Factory\RequestFactory;
use PHP94\Factory\ResponseFactory;
use PHP94\Factory\ServerRequestFactory;
use PHP94\Factory\StreamFactory;
use PHP94\Factory\UploadedFileFactory;
use PHP94\Factory\UriFactory;
use PHP94\Logger\Logger;
use PHP94\Template\Template;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use ReflectionClass;

class Framework
{
    public static function run()
    {
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            if (!(error_reporting() & $errno)) {
                return false;
            }
            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        });

        $container = Container::getInstance();
        foreach ([
            ContainerContainer::class => $container,
            LoggerInterface::class => Logger::class,
            CacheInterface::class => NullCache::class,
            ResponseFactoryInterface::class => ResponseFactory::class,
            UriFactoryInterface::class => UriFactory::class,
            ServerRequestFactoryInterface::class => ServerRequestFactory::class,
            RequestFactoryInterface::class => RequestFactory::class,
            StreamFactoryInterface::class => StreamFactory::class,
            UploadedFileFactoryInterface::class => UploadedFileFactory::class,
            ContainerInterface::class => ContainerContainer::class,
            EventDispatcherInterface::class => EventEvent::class,
            ListenerProviderInterface::class => EventEvent::class,
            Template::class => function (
                Template $template
            ) {
                $template->setCache(Cache::getInstance());
                $template->addFinder(function (string $tpl): ?string {
                    if (strpos($tpl, '@')) {
                        list($file, $appname) = explode('@', $tpl);
                        if ($appname && $file && App::isActive($appname)) {
                            $dir = App::getDir($appname);
                            $fullname = $dir . '/src/template/' . $file;
                            if (is_file($fullname)) {
                                return file_get_contents($fullname);
                            }
                            $fullname = $dir . '/src/template/' . $file . '.php';
                            if (is_file($fullname)) {
                                return file_get_contents($fullname);
                            }
                            $fullname = $dir . '/src/template/' . $file . '.tpl';
                            if (is_file($fullname)) {
                                return file_get_contents($fullname);
                            }
                        }
                    }
                    return null;
                });
                $template->assign([
                    'db' => Container::get(Db::class),
                    'cache' => Container::get(Cache::class),
                    'logger' => Container::get(Logger::class),
                    'router' => Container::get(Router::class),
                    'config' => Container::get(Config::class),
                    'session' => Container::get(Session::class),
                    'request' => Container::get(Request::class),
                    'lang' => Container::get(Lang::class),
                    'template' => $template,
                    'container' => Container::getInstance(),
                ]);
                $template->extend('/\{cache\s*(.*)\s*\}([\s\S]*)\{\/cache\}/Ui', function ($matchs) {
                    $params = array_filter(explode(',', trim($matchs[1])));
                    if (!isset($params[0])) {
                        $params[0] = 3600;
                    }
                    if (!isset($params[1])) {
                        $params[1] = 'tpl_extend_cache_' . md5($matchs[2]);
                    }
                    return '<?php echo call_user_func(function($args){
                        extract($args);
                        if (!$cache->has(\'' . $params[1] . '\')) {
                            $res = $template->renderFromString(base64_decode(\'' . base64_encode($matchs[2]) . '\'), $args, \'__' . $params[1] . '\');
                            $cache->set(\'' . $params[1] . '\', $res, ' . $params[0] . ');
                        }else{
                            $res = $cache->get(\'' . $params[1] . '\');
                        }
                        return $res;
                    }, get_defined_vars());?>';
                });
                $template->extend('/__ROOT__/Ui', function ($matchs) {
                    if (
                        (!empty($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == 'https')
                        || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
                        || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443')
                    ) {
                        $schema = 'https';
                    } else {
                        $schema = 'http';
                    }
                    $script_name = '/' . implode('/', array_filter(explode('/', $_SERVER['SCRIPT_NAME'])));
                    $root = strlen(dirname($script_name)) > 1 ? dirname($script_name) : '';

                    return $schema . '://' . $_SERVER['HTTP_HOST'] . $root;
                });
            },
        ] as $id => $obj) {
            if (is_array($obj)) {
                Container::setArgument($id, $obj);
            } elseif (is_string($obj)) {
                Container::set($id, function () use ($obj) {
                    return Container::get($obj);
                });
            } elseif ($obj instanceof Closure) {
                Container::set($id, $obj);
            } elseif (is_object($obj)) {
                Container::set($id, function () use ($obj) {
                    return $obj;
                });
            } else {
                throw new Exception('the option ' . $id . ' cannot config..');
            }
        }
        self::initApp();
        self::initLang();
        self::initEvent();
        Event::dispatch(Container::getInstance());
        Event::dispatch(Router::getInstance());
        Event::dispatch(Handler::getInstance());
        $response = Handler::getInstance()->handle(Request::getServerRequest());
        (new Emitter())->emit($response);
    }

    public static function execute(callable $callable, array $params = [])
    {
        $args = Container::reflectArguments($callable, $params);
        return call_user_func($callable, ...$args);
    }

    private static function initEvent()
    {
        Event::getInstance()->addProvider(new class implements ListenerProviderInterface
        {
            public function getListenersForEvent(object $event): iterable
            {
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
        });
    }

    private static function initApp()
    {
        $root = dirname((new ReflectionClass(ClassLoader::class))->getFileName(), 3);
        if (file_exists($root . '/vendor/composer/installed.json')) {
            foreach (json_decode(file_get_contents($root . '/vendor/composer/installed.json'), true)['packages'] as $pkg) {
                if ($pkg['type'] != 'php94') {
                    continue;
                }
                App::add($pkg['name']);
            }
        }

        spl_autoload_register(function (string $class) use ($root) {
            $paths = explode('\\', $class);
            if (isset($paths[3]) && $paths[0] == 'App') {
                $appname = strtolower(preg_replace('/([A-Z])/', "-$1", lcfirst($paths[1])))
                    . '/'
                    . strtolower(preg_replace('/([A-Z])/', "-$1", lcfirst($paths[2])));
                if (App::isActive($appname)) {
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
            if (App::has($appname)) {
                continue;
            }
            App::add($appname, $root . '/app/' . $appname);
        }
    }

    private static function initLang()
    {
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
            Lang::setLangs(...array_keys($xlang));
        }

        Lang::addFinder(function (string $key): ?string {
            static $langs = [];
            if (strpos($key, '@')) {
                list($index, $appname) = explode('@', $key);
                if ($appname && $index && App::isActive($appname)) {
                    $dir = App::getDir($appname);
                    foreach (Lang::getLangs() as $lang) {
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
                foreach (Lang::getLangs() as $lang) {
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
