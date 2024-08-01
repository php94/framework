<?php

declare(strict_types=1);

namespace PHP94\Help;

use PHP94\Facade\Framework;

class Request
{
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

    private static function getServerRequest()
    {
        return Framework::getServerRequest();
    }
}
