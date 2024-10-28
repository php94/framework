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
use PHP94\Logger\Logger as LoggerLogger;
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

        self::initApp();
        self::initContainer();
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

    private static function initContainer()
    {
        $container = Container::getInstance();
        foreach (
            [
                ContainerContainer::class => $container,
                LoggerInterface::class => LoggerLogger::class,
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
                EventEvent::class => function (
                    EventEvent $event
                ) {
                    $event->addProvider(new class implements ListenerProviderInterface
                    {
                        public function getListenersForEvent(object $event): iterable
                        {
                            $files = [];
                            $root = dirname((new ReflectionClass(ClassLoader::class))->getFileName(), 3);
                            if (file_exists($root . '/config/listen.php')) {
                                $files[] = $root . '/config/listen.php';
                            }
                            foreach (App::all() as $appname) {
                                if (file_exists(App::getDir($appname) . '/src/config/listen.php')) {
                                    $files[] = App::getDir($appname) . '/src/config/listen.php';
                                }
                            }
                            foreach ($files as $file) {
                                $listens = self::requireFile($file);
                                if (is_array($listens)) {
                                    foreach ($listens as $key => $value) {
                                        if (is_a($event, $key)) {
                                            if (is_callable($value)) {
                                                yield $value;
                                            }
                                        }
                                    }
                                }
                            }
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
                    });
                },
                Template::class => function (
                    Template $template
                ) {
                    $template->setCache(Cache::getInstance());
                    $template->addFinder(function (string $tpl): ?string {
                        if (strpos($tpl, '@')) {
                            list($file, $appname) = explode('@', $tpl);
                            if ($appname && $file && App::has($appname)) {
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
                        'db' => self::lazyLoad(Db::class),
                        'cache' => self::lazyLoad(Cache::class),
                        'logger' => self::lazyLoad(Logger::class),
                        'router' => self::lazyLoad(Router::class),
                        'config' => self::lazyLoad(Config::class),
                        'session' => self::lazyLoad(Session::class),
                        'request' => self::lazyLoad(Request::class),
                        'lang' => self::lazyLoad(Lang::class),
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
            ] as $id => $obj
        ) {
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
                if (App::has($appname)) {
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
            if (!file_exists($root . '/config/' . $appname . '/installed.lock')) {
                continue;
            }
            if (file_exists($root . '/config/' . $appname . '/disabled.lock')) {
                continue;
            }
            App::add($appname, $root . '/app/' . $appname);
        }
    }

    private static function lazyLoad(string $cls)
    {
        return new class($cls)
        {
            private $cls;
            public function __construct(string $cls)
            {
                $this->cls = $cls;
            }
            public function __call($name, $arguments)
            {
                return call_user_func($this->cls . '::' . $name, ...$arguments);
            }
        };
    }
}
