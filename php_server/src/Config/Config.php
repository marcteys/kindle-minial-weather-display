<?php

namespace KindleWeather\Config;

/**
 * Configuration class for Kindle Weather Display
 * Centralizes all application settings
 */
class Config
{
    // Secrets cache
    private static $secrets = null;

    // Weather Provider Settings
    const WEATHER_PROVIDER = 'openmeteo'; // Options: 'meteofrance', 'openweathermap', 'openmeteo', 'hybrid'

    // Location Settings
    const LATITUDE = '47.216523';
    const LONGITUDE = '-1.574932';
    const TIMEZONE = 'Europe/Paris';
    const LOCALE = 'fr_FR';

    // Cache Settings
    const CACHE_DURATION_MINUTES = 10;
    const CACHE_DIR = __DIR__ . '/../../cache';

    // Image Settings
    const IMAGE_WIDTH = 600;
    const IMAGE_HEIGHT = 800;
    const IMAGE_FORMAT = 'png';
    const IMAGE_OUTPUT_PATH = __DIR__ . '/../../weatherImage.png';

    // Image Processing
    const GRAYSCALE_COLORSPACE = 30;
    const GRAYSCALE_TREE_DEPTH = 10;
    const GRAYSCALE_DITHER = 20;
    const GRAYSCALE_COLORS = 16;

    // Background Images
    const PHOTOS_DIR = __DIR__ . '/../../Photos';
    const DEFAULT_PHOTO_FOLDER = 'cloud';

    // Fonts
    const FONT_DIR = __DIR__ . '/../../fonts';
    const FONT_REGULAR = self::FONT_DIR . '/D-DIN.ttf';
    const FONT_BOLD = self::FONT_DIR . '/D-DIN-Bold.ttf';
    const FONT_EXPANDED = self::FONT_DIR . '/D-DINExp.ttf';
    const FONT_WEATHER_ICONS = self::FONT_DIR . '/weathericons-regular-webfont.ttf';
    const WEATHER_ICONS_XML = __DIR__ . '/../../weathericons.xml';

    // Logging
    const LOG_FILE = __DIR__ . '/../../logs/app.log';
    const LOG_ENABLED = true;

    // Battery Settings
    const BATTERY_LOW_THRESHOLD = 8;
    const BATTERY_QUERYSTRING_THRESHOLD = 30;

    // Display Settings
    const PRECIPITATION_FORECAST_COUNT = 9;
    const WEATHER_FORECAST_PERIODS = 6;

    // Network Settings
    const API_TIMEOUT_SECONDS = 10; // Increased for slower connections

    /**
     * Get the active weather provider name
     */
    public static function getWeatherProvider(): string
    {
        return self::WEATHER_PROVIDER;
    }

    /**
     * Get full path for cache directory
     */
    public static function getCacheDir(): string
    {
        $dir = self::CACHE_DIR;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    /**
     * Get full path for logs directory
     */
    public static function getLogDir(): string
    {
        $dir = dirname(self::LOG_FILE);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    /**
     * Get cache file path for weather provider
     */
    public static function getCacheFilePath(string $provider, string $type): string
    {
        return self::getCacheDir() . "/{$provider}_{$type}.json";
    }

    /**
     * Get all configuration as array (for debugging)
     */
    public static function toArray(): array
    {
        return [
            'weather_provider' => self::WEATHER_PROVIDER,
            'location' => [
                'latitude' => self::LATITUDE,
                'longitude' => self::LONGITUDE,
                'timezone' => self::TIMEZONE,
                'locale' => self::LOCALE,
            ],
            'cache' => [
                'duration_minutes' => self::CACHE_DURATION_MINUTES,
                'directory' => self::getCacheDir(),
            ],
            'image' => [
                'width' => self::IMAGE_WIDTH,
                'height' => self::IMAGE_HEIGHT,
                'format' => self::IMAGE_FORMAT,
            ],
            'logging' => [
                'enabled' => self::LOG_ENABLED,
                'file' => self::LOG_FILE,
            ],
        ];
    }

    /**
     * Load secrets from secrets.php file
     *
     * @throws \Exception If secrets file is missing
     */
    private static function loadSecrets(): array
    {
        if (self::$secrets === null) {
            $secretsFile = __DIR__ . '/../../secrets.php';

            if (!file_exists($secretsFile)) {
                throw new \Exception(
                    "secrets.php not found. Please copy secrets.php.example to secrets.php and add your API credentials."
                );
            }

            self::$secrets = require $secretsFile;

            if (!is_array(self::$secrets)) {
                throw new \Exception("secrets.php must return an array of credentials");
            }
        }

        return self::$secrets;
    }

    /**
     * Get Météo France API token
     */
    public static function getMeteoFranceToken(): string
    {
        $secrets = self::loadSecrets();

        if (!isset($secrets['meteofrance_token'])) {
            throw new \Exception("meteofrance_token not found in secrets.php");
        }

        return $secrets['meteofrance_token'];
    }

    /**
     * Get OpenWeatherMap API key
     */
    public static function getOpenWeatherMapApiKey(): string
    {
        $secrets = self::loadSecrets();

        if (!isset($secrets['openweathermap_api_key'])) {
            throw new \Exception("openweathermap_api_key not found in secrets.php");
        }

        return $secrets['openweathermap_api_key'];
    }
}
