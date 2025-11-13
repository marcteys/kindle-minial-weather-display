<?php

namespace KindleWeather\Services;

use KindleWeather\Config\Config;

/**
 * Simple logging service with automatic file/line tracking
 */
class Logger
{
    /**
     * Log a message to the log file with automatic file/line detection
     */
    public static function log(string $message, string $level = 'INFO'): void
    {
        if (!Config::LOG_ENABLED) {
            return;
        }

        Config::getLogDir(); // Ensure directory exists

        // Get caller information from backtrace
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = $backtrace[1] ?? null;

        $timestamp = date('Y-m-d H:i:s');
        $location = '';

        // Add file and line information for ERROR and WARNING levels
        if (($level === 'ERROR' || $level === 'WARNING') && $caller) {
            $file = isset($caller['file']) ? basename($caller['file']) : 'unknown';
            $line = $caller['line'] ?? '?';
            $location = " [{$file}:{$line}]";
        }

        $formattedMessage = "[{$timestamp}] [{$level}]{$location} {$message}" . PHP_EOL;

        error_log($formattedMessage, 3, Config::LOG_FILE);
    }

    /**
     * Log an exception with full context
     */
    public static function exception(\Exception $e, string $level = 'ERROR'): void
    {
        if (!Config::LOG_ENABLED) {
            return;
        }

        Config::getLogDir();

        $timestamp = date('Y-m-d H:i:s');
        $file = basename($e->getFile());
        $line = $e->getLine();
        $type = get_class($e);

        // Log exception header
        $message = "[{$timestamp}] [{$level}] [{$file}:{$line}] Exception: " . $type . PHP_EOL;
        $message .= "[{$timestamp}] [{$level}] Message: " . $e->getMessage() . PHP_EOL;
        $message .= "[{$timestamp}] [{$level}] Stack trace:" . PHP_EOL;

        // Format stack trace
        $trace = $e->getTraceAsString();
        $traceLines = explode("\n", $trace);
        foreach ($traceLines as $traceLine) {
            if (trim($traceLine)) {
                $message .= "[{$timestamp}] [{$level}]   " . $traceLine . PHP_EOL;
            }
        }

        error_log($message, 3, Config::LOG_FILE);
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
