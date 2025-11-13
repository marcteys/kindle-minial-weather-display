<?php

/**
 * Debug endpoint - shows configuration and weather data
 */

require_once __DIR__ . '/autoload.php';

use KindleWeather\Controllers\WeatherImageController;
use KindleWeather\Config\Config;

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

date_default_timezone_set(Config::TIMEZONE);

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kindle Weather Display - Debug</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background-color: #f5f5f5;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        h2 {
            color: #666;
            margin-top: 30px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }
        pre {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            overflow-x: auto;
        }
        .status-ok {
            color: #28a745;
            font-weight: bold;
        }
        .status-error {
            color: #dc3545;
            font-weight: bold;
        }
        .info-box {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin: 15px 0;
        }
        .image-preview {
            border: 1px solid #ddd;
            max-width: 100%;
            margin-top: 15px;
        }
        .actions {
            margin: 20px 0;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <h1>Kindle Weather Display - Debug Information</h1>

    <div class="actions">
        <a href="?refresh=1" class="btn">Force API Refresh</a>
        <a href="api.php" class="btn" target="_blank">View Image</a>
        <a href="api.php?verbose=true" class="btn" target="_blank">View Logs</a>
    </div>

    <?php
    try {
        // Check if Imagick is installed
        echo '<h2>Extension Check</h2>';
        echo '<div class="info-box">';
        if (extension_loaded('imagick')) {
            echo '<span class="status-ok">✓ Imagick extension loaded</span><br>';
            $imagick = new Imagick();
            $version = $imagick->getVersion();
            echo 'Version: ' . ($version['versionString'] ?? 'Unknown') . '<br>';
        } else {
            echo '<span class="status-error">✗ Imagick extension not loaded</span><br>';
        }
        echo '</div>';

        // Show configuration
        echo '<h2>Configuration</h2>';
        echo '<pre>';
        print_r(Config::toArray());
        echo '</pre>';

        // Create controller
        $forceRefresh = isset($_GET['refresh']);
        $controller = new WeatherImageController();

        // Get debug info
        echo '<h2>Weather Data</h2>';
        $debugInfo = $controller->getDebugInfo();

        if ($debugInfo['status'] === 'success') {
            echo '<div class="info-box">';
            echo '<span class="status-ok">✓ Weather data loaded successfully</span><br>';
            echo 'Provider: ' . $debugInfo['provider'] . '<br>';
            echo 'Last Update: ' . $debugInfo['weather']['lastUpdateDate'] . ' ' . $debugInfo['weather']['lastUpdateTime'];
            echo '</div>';

            echo '<pre>';
            print_r($debugInfo['weather']);
            echo '</pre>';
        } else {
            echo '<div class="info-box">';
            echo '<span class="status-error">✗ Error loading weather data</span><br>';
            echo 'Message: ' . htmlspecialchars($debugInfo['message']);
            echo '</div>';
        }

        // Show image
        echo '<h2>Generated Image</h2>';
        echo '<div class="info-box">';

        if ($forceRefresh) {
            // Generate new image
            $controller->generateImage(['export' => true, 'force' => true]);
            echo '<span class="status-ok">✓ Image regenerated</span><br>';
        }

        if (file_exists(Config::IMAGE_OUTPUT_PATH)) {
            $imageSize = filesize(Config::IMAGE_OUTPUT_PATH);
            $imageModified = date('Y-m-d H:i:s', filemtime(Config::IMAGE_OUTPUT_PATH));

            echo 'File: ' . basename(Config::IMAGE_OUTPUT_PATH) . '<br>';
            echo 'Size: ' . number_format($imageSize) . ' bytes<br>';
            echo 'Last Modified: ' . $imageModified . '<br>';

            // Show image with cache buster
            $cacheBuster = time();
            echo '<img src="' . basename(Config::IMAGE_OUTPUT_PATH) . '?' . $cacheBuster . '" class="image-preview" alt="Weather Image">';
        } else {
            echo '<span class="status-error">✗ Image file not found</span>';
        }
        echo '</div>';

        // Show logs
        if (file_exists(Config::LOG_FILE)) {
            echo '<h2>Recent Logs</h2>';
            echo '<pre>';
            $logs = file_get_contents(Config::LOG_FILE);
            // Show last 50 lines
            $lines = explode("\n", $logs);
            $recentLines = array_slice($lines, -50);
            echo htmlspecialchars(implode("\n", $recentLines));
            echo '</pre>';
        }

    } catch (\Exception $e) {
        echo '<div class="info-box">';
        echo '<span class="status-error">✗ Fatal Error</span><br>';
        echo 'Message: ' . htmlspecialchars($e->getMessage()) . '<br>';
        echo 'File: ' . $e->getFile() . '<br>';
        echo 'Line: ' . $e->getLine();
        echo '</div>';

        echo '<h2>Stack Trace</h2>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    }
    ?>

</body>
</html>
