<?php

namespace KindleWeather\Controllers;

use KindleWeather\Config\Config;
use KindleWeather\Models\WeatherData;
use KindleWeather\Providers\WeatherProvider;
use KindleWeather\Providers\MeteoFranceProvider;
use KindleWeather\Providers\OpenWeatherMapProvider;
use KindleWeather\Services\ImageRenderer;
use KindleWeather\Services\Logger;
use Imagick;

/**
 * Main controller for weather image generation
 * Orchestrates weather data fetching and image rendering
 */
class WeatherImageController
{
    private WeatherProvider $weatherProvider;

    public function __construct(?WeatherProvider $weatherProvider = null)
    {
        if ($weatherProvider === null) {
            $this->weatherProvider = $this->createWeatherProvider();
        } else {
            $this->weatherProvider = $weatherProvider;
        }
    }

    /**
     * Generate weather image and output to browser or file
     */
    public function generateImage(array $params = []): void
    {
        try {
            Logger::info("===== Starting new image generation =====");
            Logger::info("Provider: " . $this->weatherProvider->getName());

            // Parse parameters
            $verbose = $params['verbose'] ?? false;
            $forceRefresh = $params['force'] ?? false;
            $batteryLevel = isset($params['battery']) ? (int) $params['battery'] : null;
            $export = $params['export'] ?? false;

            if ($batteryLevel !== null) {
                Logger::info("Battery level: {$batteryLevel}%");
            }

            // Get weather data
            $weatherData = $this->getWeatherData($forceRefresh);

            // Render image
            $image = $this->renderWeatherImage($weatherData, $batteryLevel);

            // Convert to grayscale for e-ink
            $image = ImageRenderer::convertToGrayscale($image);

            // Output image
            if ($verbose) {
                $this->outputVerbose();
            } else {
                $this->outputImage($image, $export);
            }

            Logger::info("===== Image generation completed =====");
        } catch (\Exception $e) {
            Logger::exception($e);

            if ($verbose ?? false) {
                $this->outputError($e);
            } else {
                throw $e;
            }
        }
    }

    /**
     * Get weather data from provider
     */
    private function getWeatherData(bool $forceRefresh): WeatherData
    {
        $latitude = (float) Config::LATITUDE;
        $longitude = (float) Config::LONGITUDE;

        return $this->weatherProvider->getWeatherData($latitude, $longitude, $forceRefresh);
    }

    /**
     * Render weather image
     */
    private function renderWeatherImage(WeatherData $weatherData, ?int $batteryLevel): Imagick
    {
        $renderer = new ImageRenderer($weatherData, $batteryLevel);
        return $renderer->render();
    }

    /**
     * Output image to browser or save to file
     */
    private function outputImage(Imagick $image, bool $export = false): void
    {
        $format = Config::IMAGE_FORMAT;

        // Save to file
        $outputPath = Config::IMAGE_OUTPUT_PATH;
        $fileHandle = fopen($outputPath, 'w');
        $image->writeImageFile($fileHandle);
        fclose($fileHandle);

        Logger::info("Image saved to: {$outputPath}");

        // Output to browser
        if (!$export) {
            $fileSize = filesize($outputPath);

            header('Content-Type: image/' . $format);
            header('Content-Length: ' . $fileSize);

            echo file_get_contents($outputPath);

            Logger::info("Image sent to browser (size: {$fileSize} bytes)");
        }
    }

    /**
     * Output verbose debug information
     */
    private function outputVerbose(): void
    {
        header('Content-Type: text/plain; charset=utf-8');

        echo "===== Kindle Weather Display - Debug Mode =====\n\n";
        echo "Configuration:\n";
        echo "--------------\n";
        print_r(Config::toArray());
        echo "\n\n";

        if (file_exists(Config::LOG_FILE)) {
            echo "Recent Logs:\n";
            echo "------------\n";
            echo file_get_contents(Config::LOG_FILE);
        } else {
            echo "No log file found.\n";
        }
    }

    /**
     * Output error information
     */
    private function outputError(\Exception $e): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        http_response_code(500);

        echo "===== Error =====\n\n";
        echo "Message: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . "\n";
        echo "Line: " . $e->getLine() . "\n\n";
        echo "Stack Trace:\n";
        echo $e->getTraceAsString();
    }

    /**
     * Create weather provider based on configuration
     */
    private function createWeatherProvider(): WeatherProvider
    {
        $providerName = Config::getWeatherProvider();

        switch ($providerName) {
            case 'meteofrance':
                if (empty(Config::METEOFRANCE_TOKEN)) {
                    throw new \Exception("Météo France token not configured");
                }
                return new MeteoFranceProvider(Config::METEOFRANCE_TOKEN);

            case 'openweathermap':
                if (empty(Config::OPENWEATHERMAP_API_KEY)) {
                    throw new \Exception("OpenWeatherMap API key not configured");
                }
                return new OpenWeatherMapProvider(Config::OPENWEATHERMAP_API_KEY);

            default:
                throw new \Exception("Unknown weather provider: {$providerName}");
        }
    }

    /**
     * Get debug information as array
     */
    public function getDebugInfo(): array
    {
        try {
            $weatherData = $this->getWeatherData(false);

            return [
                'status' => 'success',
                'provider' => $this->weatherProvider->getName(),
                'config' => Config::toArray(),
                'weather' => $weatherData->toArray(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'provider' => $this->weatherProvider->getName(),
            ];
        }
    }
}
