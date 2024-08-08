<?php

declare(strict_types=1);

namespace PHP94;

use PHP94\Template\Template as TemplateTemplate;
use Psr\SimpleCache\CacheInterface;

class Template
{
    public static function getInstance(): TemplateTemplate
    {
        return Container::get(TemplateTemplate::class);
    }

    public static function setCache(CacheInterface $cache): TemplateTemplate
    {
        return self::getInstance()->setCache($cache);
    }

    public static function addFinder(callable $callable, $priority = 0): TemplateTemplate
    {
        return self::getInstance()->addFinder($callable, $priority);
    }

    public static function extend(string $preg, callable $callback): TemplateTemplate
    {
        return self::getInstance()->extend($preg, $callback);
    }

    public static function assign($name, $value = null): TemplateTemplate
    {
        return self::getInstance()->assign($name, $value);
    }

    public static function render(string $tpl, array $data = []): string
    {
        return self::getInstance()->render($tpl, $data);
    }

    public static function renderString(string $string, array $data = [], string $filename = null): string
    {
        return self::getInstance()->renderString($string, $data, $filename);
    }

    public static function parse(string $string): string
    {
        return self::getInstance()->parse($string);
    }
}
