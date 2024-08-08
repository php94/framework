<?php

declare(strict_types=1);

namespace PHP94;

use PHP94\Logger\Logger as LoggerLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Stringable;

class Logger
{
    public static function getInstance(): LoggerLogger
    {
        return Container::get(LoggerLogger::class);
    }

    public static function addLogger(
        LoggerInterface $logger,
        $levels = [
            LogLevel::EMERGENCY,
            LogLevel::ALERT,
            LogLevel::CRITICAL,
            LogLevel::ERROR,
            LogLevel::WARNING,
            LogLevel::NOTICE,
            LogLevel::INFO,
            LogLevel::DEBUG,
        ]
    ): LoggerLogger {
        return self::getInstance()->addLogger($logger, $levels);
    }

    public static function emergency(string|Stringable $message, array $context = []): void
    {
        self::log(LogLevel::EMERGENCY, $message, $context);
    }

    public static function alert(string|Stringable $message, array $context = []): void
    {
        self::log(LogLevel::ALERT, $message, $context);
    }

    public static function critical(string|Stringable $message, array $context = []): void
    {
        self::log(LogLevel::CRITICAL, $message, $context);
    }

    public static function error(string|Stringable $message, array $context = []): void
    {
        self::log(LogLevel::ERROR, $message, $context);
    }

    public static function warning(string|Stringable $message, array $context = []): void
    {
        self::log(LogLevel::WARNING, $message, $context);
    }

    public static function notice(string|Stringable $message, array $context = []): void
    {
        self::log(LogLevel::NOTICE, $message, $context);
    }

    public static function info(string|Stringable $message, array $context = []): void
    {
        self::log(LogLevel::INFO, $message, $context);
    }

    public static function debug(string|Stringable $message, array $context = []): void
    {
        self::log(LogLevel::DEBUG, $message, $context);
    }

    public static function log($level, string|Stringable $message, array $context = []): void
    {
        self::getInstance()->log($level, $message, $context);
    }
}
