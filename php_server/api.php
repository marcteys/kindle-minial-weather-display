<?php

/**
 * Main API endpoint for weather image generation
 *
 * Usage:
 *   GET /api.php                          - Generate image with current weather
 *   GET /api.php?force=true               - Force API refresh, bypass cache
 *   GET /api.php?battery=50               - Include battery level indicator
 *   GET /api.php?verbose=true             - Show debug information
 *   GET /api.php?export=true              - Save image but don't output to browser
 *
 * You can also specify the provider in the URL:
 *   GET /api.php?provider=meteofrance
 *   GET /api.php?provider=openweathermap
 */

require_once __DIR__ . '/autoload.php';

use KindleWeather\Controllers\WeatherImageController;
use KindleWeather\Providers\MeteoFranceProvider;
use KindleWeather\Providers\OpenWeatherMapProvider;
use KindleWeather\Config\Config;

// Set error reporting based on verbose mode
$verbose = isset($_GET['verbose']) && $_GET['verbose'] === 'true';

if ($verbose) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ERROR | E_PARSE);
}

// Set timezone
date_default_timezone_set(Config::TIMEZONE);
setlocale(LC_TIME, Config::LOCALE, 'French');

// Parse request parameters
$params = [
    'verbose' => $verbose,
    'force' => isset($_GET['force']) && $_GET['force'] === 'true',
    'export' => isset($_GET['export']) && $_GET['export'] === 'true',
    'battery' => $_GET['battery'] ?? null,
];

// Check for provider override
$provider = null;
if (isset($_GET['provider'])) {
    $providerName = $_GET['provider'];

    switch ($providerName) {
        case 'meteofrance':
            if (!empty(Config::METEOFRANCE_TOKEN)) {
                $provider = new MeteoFranceProvider(Config::METEOFRANCE_TOKEN);
            }
            break;

        case 'openweathermap':
            if (!empty(Config::OPENWEATHERMAP_API_KEY)) {
                $provider = new OpenWeatherMapProvider(Config::OPENWEATHERMAP_API_KEY);
            }
            break;
    }
}

// Create controller and generate image
try {
    $controller = new WeatherImageController($provider);
    $controller->generateImage($params);
} catch (\Exception $e) {
    // Log error
    error_log("API Error: " . $e->getMessage());

    // Send error response
    http_response_code(500);
    header('Content-Type: text/plain');

    if ($verbose) {
        echo "Error: " . $e->getMessage() . "\n\n";
        echo $e->getTraceAsString();
    } else {
        echo "Error generating weather image. Enable verbose mode for details.";
    }
}
