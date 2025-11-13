<?php

namespace KindleWeather\Services;

use KindleWeather\Config\Config;

/**
 * Simple logging service
 */
class Logger
{
    /**
     * Log a message to the log file
     */
    public static function log(string $message, string $level = 'INFO'): void
    {
        if (!Config::LOG_ENABLED) {
            return;
        }

        Config::getLogDir(); // Ensure directory exists

        $timestamp = date('Y-m-d H:i:s');
        $formattedMessage = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;

        error_log($formattedMessage, 3, Config::LOG_FILE);
    }

    public static function info(string $message): void
    {
        self::log($message, 'INFO');
    }

    public static function error(string $message): void
    {
        self::log($message, 'ERROR');
    }

    public static function warning(string $message): void
    {
        self::log($message, 'WARNING');
    }

    public static function debug(string $message): void
    {
        self::log($message, 'DEBUG');
    }
}
