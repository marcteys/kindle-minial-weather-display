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
use KindleWeather\Services\ErrorImageRenderer;
use KindleWeather\Services\Logger;
use KindleWeather\Config\Config;

// Set error reporting based on verbose mode
$verbose = isset($_GET['verbose']) && $_GET['verbose'] === 'true';

// Register shutdown function to catch fatal errors
register_shutdown_function(function() use ($verbose) {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        // Create a fake exception from the error
        $exception = new \ErrorException(
            $error['message'],
            0,
            $error['type'],
            $error['file'],
            $error['line']
        );

        Logger::exception($exception);

        if ($verbose) {
            header('Content-Type: text/plain; charset=utf-8');
            echo "===== FATAL ERROR =====\n\n";
            echo "Message: " . $error['message'] . "\n";
            echo "File: " . $error['file'] . "\n";
            echo "Line: " . $error['line'] . "\n";
        } else {
            try {
                $errorImage = ErrorImageRenderer::generateErrorImage($exception, false);
                $tempFile = tempnam(sys_get_temp_dir(), 'error');
                $fileHandle = fopen($tempFile, 'w');
                $errorImage->writeImageFile($fileHandle);
                fclose($fileHandle);

                header('Content-Type: image/png');
                header('Content-Length: ' . filesize($tempFile));
                readfile($tempFile);
                unlink($tempFile);
            } catch (\Exception $e) {
                header('Content-Type: text/plain');
                echo "Critical Error: " . $error['message'];
            }
        }
    }
});

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
    // Log exception with full context
    Logger::exception($e);

    // If verbose mode, output text error
    if ($verbose) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo "===== ERROR =====\n\n";
        echo "Message: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . "\n";
        echo "Line: " . $e->getLine() . "\n\n";
        echo "Stack Trace:\n";
        echo $e->getTraceAsString();
        exit;
    }

    // Otherwise, generate error image for Kindle
    try {
        $errorImage = ErrorImageRenderer::generateErrorImage($e, false);

        // Save error image
        $outputPath = Config::IMAGE_OUTPUT_PATH;
        $fileHandle = fopen($outputPath, 'w');
        $errorImage->writeImageFile($fileHandle);
        fclose($fileHandle);

        // Output error image
        $fileSize = filesize($outputPath);
        header('Content-Type: image/' . Config::IMAGE_FORMAT);
        header('Content-Length: ' . $fileSize);
        echo file_get_contents($outputPath);

        Logger::info("Error image sent to client");
    } catch (\Exception $imageError) {
        // If we can't even generate error image, fall back to text
        http_response_code(500);
        header('Content-Type: text/plain');
        echo "Critical Error: " . $e->getMessage();
        Logger::error("Failed to generate error image: " . $imageError->getMessage());
    }
}
