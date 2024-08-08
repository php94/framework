<?php

declare(strict_types=1);

namespace PHP94;

use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;

class Request
{
    public static function getInstance(): Request
    {
        return Container::get(Request::class);
    }

    public static function has(string $field): bool
    {
        $fields = self::fieldFilter($field);
        $type = array_shift($fields);
        switch ($type) {
            case 'server':
                return self::isSetValue(self::getServerRequest()->getServerParams(), $fields);
                break;

            case 'get':
                return self::isSetValue(self::getServerRequest()->getQueryParams(), $fields);
                break;

            case 'post':
                return self::isSetValue(self::getServerRequest()->getParsedBody(), $fields);
                break;

            case 'request':
                return self::isSetValue(self::getServerRequest()->getQueryParams(), $fields) || self::isSetValue(self::getServerRequest()->getParsedBody(), $fields);
                break;

            case 'cookie':
                return self::isSetValue(self::getServerRequest()->getCookieParams(), $fields);
                break;

            case 'file':
                return self::isSetValue(self::getServerRequest()->getUploadedFiles(), $fields);
                break;

            case 'attr':
                return self::isSetValue(self::getServerRequest()->getAttributes(), $fields);
                break;

            case 'header':
                return self::isSetValue(self::getServerRequest()->getHeaders(), $fields);
                break;

            default:
                return false;
                break;
        }
    }

    public static function server(string $field = '', $default = null)
    {
        return self::getValue(self::getServerRequest()->getServerParams(), self::fieldFilter($field), $default);
    }

    public static function get(string $field = '', $default = null)
    {
        return self::getValue(self::getServerRequest()->getQueryParams(), self::fieldFilter($field), $default);
    }

    public static function post(string $field = '', $default = null)
    {
        return self::getValue(self::getServerRequest()->getParsedBody(), self::fieldFilter($field), $default);
    }

    public static function request(string $field = '', $default = null)
    {
        if (self::has('get.' . $field)) {
            return self::getValue(self::getServerRequest()->getQueryParams(), self::fieldFilter($field), $default);
        } else {
            return self::getValue(self::getServerRequest()->getParsedBody(), self::fieldFilter($field), $default);
        }
    }

    public static function cookie(string $field = '', $default = null)
    {
        return self::getValue(self::getServerRequest()->getCookieParams(), self::fieldFilter($field), $default);
    }

    public static function file(string $field = '', $default = null)
    {
        return self::getValue(self::getServerRequest()->getUploadedFiles(), self::fieldFilter($field), $default);
    }

    public static function attr(string $field = '', $default = null)
    {
        return self::getValue(self::getServerRequest()->getAttributes(), self::fieldFilter($field), $default);
    }

    public static function header(string $field = '', $default = null)
    {
        return self::getValue(self::getServerRequest()->getHeaders(), self::fieldFilter($field), $default);
    }

    public static function getServerRequest(): ServerRequestInterface
    {
        static $serverRequest;
        if (!$serverRequest) {
            $serverRequest = ServerRequest::fromGlobals();
            $path = ServerRequest::getUriFromGlobals()->getPath();
            if (strpos($_SERVER['REQUEST_URI'] ?? '', $_SERVER['SCRIPT_NAME']) === 0) {
                $base = $_SERVER['SCRIPT_NAME'];
            } else {
                $base = strlen(dirname($_SERVER['SCRIPT_NAME'])) > 1 ? dirname($_SERVER['SCRIPT_NAME']) : '';
            }
            $res = Router::dispatch(substr($path, strlen($base)));
            if ($res) {
                $class = $res[0] ?? '';
                $paths = explode('\\', $class);
                if (isset($paths[4]) && $paths[0] == 'App' && $paths[3] == 'Http') {
                    $appname = strtolower(preg_replace('/([A-Z])/', "-$1", lcfirst($paths[1]) . '/' . lcfirst($paths[2])));
                    $serverRequest = $serverRequest
                        ->withQueryParams(array_merge($_GET, $res[1]))
                        ->withAttribute('appname', $appname)
                        ->withAttribute('handler', $class);
                }
            } else {
                $paths = explode('/', $path);
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
                        $serverRequest = $serverRequest
                            ->withAttribute('appname', $appname)
                            ->withAttribute('handler', $class);
                    }
                }
            }
        }
        return $serverRequest;
    }

    private static function isSetValue(array $data = [], array $arr = []): bool
    {
        $key = array_shift($arr);
        if (!$arr) {
            return isset($data[$key]);
        }
        if (!isset($data[$key])) {
            return false;
        }
        return self::isSetValue($data[$key], $arr);
    }

    private static function getValue($data = [], array $arr = [], $default = null)
    {
        if (!$arr) {
            return $data;
        }
        if (!is_array($data)) {
            return $default;
        }
        $key = array_shift($arr);
        if (!$arr) {
            return isset($data[$key]) ? $data[$key] : $default;
        }
        if (!isset($data[$key])) {
            return $default;
        }
        return self::getValue($data[$key], $arr, $default);
    }

    private static function fieldFilter(string $field): array
    {
        return array_filter(
            explode('.', $field),
            function ($val) {
                return strlen($val) > 0 ? true : false;
            }
        );
    }
}
