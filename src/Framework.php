<?php

declare(strict_types=1);

namespace PHP94;

use Closure;
use ErrorException;
use Exception;
use GuzzleHttp\Psr7\ServerRequest;
use PHP94\Cache\NullCache;
use PHP94\Container\Container as ContainerContainer;
use PHP94\Db\Db as DbDb;
use PHP94\Event\Event as EventEvent;
use PHP94\Facade\App;
use PHP94\Facade\Cache;
use PHP94\Facade\Config;
use PHP94\Facade\Container;
use PHP94\Facade\Db;
use PHP94\Facade\Emitter;
use PHP94\Facade\Event;
use PHP94\Facade\Factory;
use PHP94\Facade\Handler;
use PHP94\Facade\Logger;
use PHP94\Facade\Router;
use PHP94\Facade\Session;
use PHP94\Factory\Factory as FactoryFactory;
use PHP94\Handler\Handler as HandlerHandler;
use PHP94\Help\Request;
use PHP94\ListenerProvider;
use PHP94\Logger\Logger as LoggerLogger;
use PHP94\Template\Template;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

class Framework
{
    public function __construct()
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
            LoggerInterface::class => LoggerLogger::class,
            CacheInterface::class => NullCache::class,
            ResponseFactoryInterface::class => FactoryFactory::class,
            UriFactoryInterface::class => FactoryFactory::class,
            ServerRequestFactoryInterface::class => FactoryFactory::class,
            RequestFactoryInterface::class => FactoryFactory::class,
            StreamFactoryInterface::class => FactoryFactory::class,
            UploadedFileFactoryInterface::class => FactoryFactory::class,
            ContainerInterface::class => ContainerContainer::class,
            EventDispatcherInterface::class => EventEvent::class,
            ListenerProviderInterface::class => EventEvent::class,
            EventEvent::class => function (
                EventEvent $event,
                ListenerProvider $listenerProvider
            ) {
                $event->addProvider($listenerProvider);
            },
            HandlerHandler::class => function () {
                $appname = $this->getServerRequest()->getAttribute('appname', '');
                if (App::isActive($appname)) {
                    $cls = $this->getServerRequest()->getAttribute('handler', '');
                    if (class_exists($cls) && is_subclass_of($cls, RequestHandlerInterface::class, true)) {
                        $handler = Container::get($cls);
                    }
                }
                if (!isset($handler)) {
                    $handler = new class implements RequestHandlerInterface
                    {
                        public function handle(ServerRequestInterface $request): ResponseInterface
                        {
                            return Factory::createResponse(404);
                        }
                    };
                }
                return new HandlerHandler(Container::getInstance(), $handler);
            },
            DbDb::class => [
                'master_config' => Config::get('database.master', []),
                'slaves_config' => Config::get('database.slaves', []),
            ],
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
                    'db' => Db::getInstance(),
                    'cache' => Cache::getInstance(),
                    'logger' => Logger::getInstance(),
                    'router' => Router::getInstance(),
                    'config' => Config::getInstance(),
                    'session' => Session::getInstance(),
                    'request' => Request::getInstance(),
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
    }

    public function run()
    {
        Event::dispatch(Container::getInstance());
        Event::dispatch(Router::getInstance());
        Event::dispatch(Handler::getInstance());
        $response = Handler::handle($this->getServerRequest());
        Emitter::emit($response);
    }

    public function execute(callable $callable, array $params = [])
    {
        $args = Container::reflectArguments($callable, $params);
        return call_user_func($callable, ...$args);
    }

    public function getServerRequest(): ServerRequestInterface
    {
        $uri = ServerRequest::getUriFromGlobals();
        if (strpos($_SERVER['REQUEST_URI'] ?? '', $_SERVER['SCRIPT_NAME']) === 0) {
            $base = $_SERVER['SCRIPT_NAME'];
        } else {
            $base = strlen(dirname($_SERVER['SCRIPT_NAME'])) > 1 ? dirname($_SERVER['SCRIPT_NAME']) : '';
        }
        $res = Router::dispatch(substr($uri->getPath(), strlen($base)));
        if ($res) {
            $class = $res[0] ?? '';
            $paths = explode('\\', $class);
            if (isset($paths[4]) && $paths[0] == 'App' && $paths[3] == 'Http') {
                $appname = strtolower(preg_replace('/([A-Z])/', "-$1", lcfirst($paths[1]) . '/' . lcfirst($paths[2])));
                return ServerRequest::fromGlobals()
                    ->withQueryParams(array_merge($_GET, $res[1]))
                    ->withAttribute('appname', $appname)
                    ->withAttribute('handler', $class);
            }
        } else {
            $paths = explode('/', $uri->getPath());
            $pathx = explode('/', $_SERVER['SCRIPT_NAME']);
            foreach ($pathx as $key => $value) {
                if (isset($paths[$key]) && ($paths[$key] == $value)) {
                    unset($paths[$key]);
                }
            }
            if (count($paths) >= 3) {
                array_splice($paths, 0, 0, 'App');
                array_splice($paths, 3, 0, 'Http');
                $class = str_replace(['-'], [''], ucwords(implode('\\', $paths), '\\-'));
                $paths = explode('\\', $class);
                if (isset($paths[4]) && $paths[0] == 'App' && $paths[3] == 'Http') {
                    $appname = strtolower(preg_replace('/([A-Z])/', "-$1", lcfirst($paths[1]) . '/' . lcfirst($paths[2])));
                    return ServerRequest::fromGlobals()
                        ->withAttribute('appname', $appname)
                        ->withAttribute('handler', $class);
                }
            }
        }
        return ServerRequest::fromGlobals();
    }
}
