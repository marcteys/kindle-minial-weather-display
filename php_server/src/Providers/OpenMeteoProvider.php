<?php

namespace KindleWeather\Providers;

use KindleWeather\Models\WeatherData;
use KindleWeather\Config\Config;
use KindleWeather\Services\Logger;
use KindleWeather\Services\WeatherIconMapper;

/**
 * Open-Meteo weather provider
 * Fetches data from Open-Meteo API (free, no API key required)
 *
 * API Documentation: https://open-meteo.com/en/docs
 */
class OpenMeteoProvider implements WeatherProvider
{
    private const API_BASE_URL = 'https://api.open-meteo.com/v1';
    private const FORECAST_ENDPOINT = '/forecast';

    private WeatherIconMapper $iconMapper;

    public function __construct()
    {
        $this->iconMapper = new WeatherIconMapper();
        Logger::info("OpenMeteoProvider initialized (free API, no key required)");
    }

    public function getName(): string
    {
        return 'openmeteo';
    }

    /**
     * Fetch weather data from Open-Meteo API
     */
    public function fetchWeatherData(float $latitude, float $longitude): WeatherData
    {
        Logger::info("Fetching weather data from Open-Meteo API");

        $forecastData = $this->fetchForecast($latitude, $longitude);
        return $this->transformData($forecastData);
    }

    /**
     * Get weather data with caching
     */
    public function getWeatherData(float $latitude, float $longitude, bool $forceRefresh = false): WeatherData
    {
        $cacheFile = Config::getCacheFilePath($this->getName(), 'weather');
        $lastUpdateFile = Config::getCacheFilePath($this->getName(), 'last_update');

        // Check if we should use cache
        if (!$forceRefresh && file_exists($cacheFile) && file_exists($lastUpdateFile)) {
            $lastUpdate = (int) file_get_contents($lastUpdateFile);
            $timeSinceUpdate = time() - $lastUpdate;
            $cacheMaxAge = Config::CACHE_DURATION_MINUTES * 60;

            if ($timeSinceUpdate < $cacheMaxAge) {
                Logger::info("Using cached weather data (age: {$timeSinceUpdate}s)");
                $cachedData = json_decode(file_get_contents($cacheFile), true);
                return $this->weatherDataFromArray($cachedData);
            }
        }

        // Fetch fresh data
        try {
            Logger::info("Fetching fresh weather data from API");
            $weatherData = $this->fetchWeatherData($latitude, $longitude);

            // Save to cache
            file_put_contents($cacheFile, json_encode($weatherData->toArray()));
            file_put_contents($lastUpdateFile, time());

            return $weatherData;
        } catch (\Exception $e) {
            Logger::error("Failed to fetch weather data: " . $e->getMessage());

            // Try to use stale cache as fallback
            if (file_exists($cacheFile)) {
                Logger::info("Using stale cache as fallback");
                $cachedData = json_decode(file_get_contents($cacheFile), true);
                return $this->weatherDataFromArray($cachedData);
            }

            throw $e;
        }
    }

    /**
     * Fetch forecast data from Open-Meteo API
     */
    private function fetchForecast(float $latitude, float $longitude): array
    {
        $url = self::API_BASE_URL . self::FORECAST_ENDPOINT;

        $params = [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'timezone' => Config::TIMEZONE,
            'hourly' => 'temperature_2m,precipitation,precipitation_probability,weather_code',
            'forecast_days' => 3,
        ];

        $url .= '?' . http_build_query($params);

        Logger::info("Fetching forecast from: {$url}");

        $data = $this->makeApiRequest($url);

        if (!$data) {
            throw new \Exception("Failed to fetch forecast data from Open-Meteo");
        }

        return $data;
    }

    /**
     * Make API request using cURL
     */
    private function makeApiRequest(string $url): ?array
    {
        if (!function_exists('curl_init')) {
            Logger::error("cURL extension is not installed");
            throw new \Exception("cURL extension is required but not installed");
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, Config::API_TIMEOUT_SECONDS);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        curl_close($ch);

        if ($response === false) {
            Logger::error("cURL request failed");
            Logger::error("cURL Error Number: " . $curlErrno);
            Logger::error("cURL Error Message: " . $curlError);
            return null;
        }

        Logger::info("API Response - HTTP Status: {$httpCode}");

        if ($httpCode !== 200) {
            Logger::error("API returned non-200 status code: {$httpCode}");
            Logger::error("Response preview: " . substr($response, 0, 500));
            return null;
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Logger::error("Failed to decode JSON response");
            Logger::error("JSON Error: " . json_last_error_msg());
            Logger::error("Response preview: " . substr($response, 0, 500));
            return null;
        }

        return $data;
    }

    /**
     * Transform Open-Meteo API data into WeatherData model
     */
    private function transformData(array $forecastData): WeatherData
    {
        $now = time();
        $dateFormatter = new \IntlDateFormatter(
            Config::LOCALE,
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::NONE,
            Config::TIMEZONE
        );

        // Process hourly data into forecast periods
        $forecasts = $this->processForecasts($forecastData['hourly']);

        // Process precipitation data (next 90 minutes)
        $precipitations = $this->processPrecipitations($forecastData['hourly']);

        return new WeatherData(
            $dateFormatter->format(new \DateTime()),
            date('H\hi', $now),
            $now,
            $precipitations,
            $forecasts
        );
    }

    /**
     * Process hourly forecast data into 6 periods
     * Returns forecasts for: now, +6h, +12h, +18h, +24h, +30h
     */
    private function processForecasts(array $hourlyData): array
    {
        $forecasts = [];
        $times = $hourlyData['time'] ?? [];
        $temperatures = $hourlyData['temperature_2m'] ?? [];
        $weatherCodes = $hourlyData['weather_code'] ?? [];
        $precipProbs = $hourlyData['precipitation_probability'] ?? [];

        if (empty($times)) {
            Logger::warning("No hourly data available");
            return [];
        }

        // Get data for specific hour offsets: 0, 6, 12, 18, 24, 30
        $offsets = [0, 6, 12, 18, 24, 30];

        foreach ($offsets as $offset) {
            if ($offset >= count($times)) {
                break;
            }

            $time = $times[$offset];
            $temp = round($temperatures[$offset] ?? 0);
            $weatherCode = $weatherCodes[$offset] ?? 0;
            $precipProb = $precipProbs[$offset] ?? 0;

            // Convert WMO weather code to weather description and icon
            $weatherInfo = $this->getWeatherInfoFromCode($weatherCode);

            // Determine period label from time
            $hour = (int) date('H', strtotime($time));
            $periodLabel = $this->getPeriodLabel($hour);

            $forecasts[] = [
                'temperature' => $temp,
                'weatherText' => $weatherInfo['description'],
                'weatherIcon' => $weatherInfo['icon'],
                'precipitationProbability' => $precipProb,
                'time' => date('H:i', strtotime($time)),
                'period' => $periodLabel,
            ];
        }

        return $forecasts;
    }

    /**
     * Process precipitation data for next 90 minutes (9 points, 10-min intervals)
     */
    private function processPrecipitations(array $hourlyData): ?array
    {
        $times = $hourlyData['time'] ?? [];
        $precipitation = $hourlyData['precipitation'] ?? [];

        if (empty($times)) {
            return null;
        }

        $precipitations = [];

        // Get first 2 hours of data (indices 0 and 1), interpolate to 9 points
        for ($i = 0; $i < 9; $i++) {
            // Calculate index in hourly array (0-1 range for first 2 hours)
            $hourIndex = min(1, $i / 9.0);
            $arrayIndex = (int) $hourIndex;

            // Get precipitation value (mm/h)
            $precipValue = $precipitation[$arrayIndex] ?? 0;

            // Convert to intensity (0-4 scale)
            $intensity = $this->precipitationToIntensity($precipValue);

            $precipitations[] = [
                'intensity' => $intensity,
                'time' => '+' . ($i * 10) . 'min',
            ];
        }

        return $precipitations;
    }

    /**
     * Convert precipitation mm/h to intensity level (0-4)
     */
    private function precipitationToIntensity(float $mmPerHour): int
    {
        if ($mmPerHour < 0.1) return 0; // No rain
        if ($mmPerHour < 2.5) return 1; // Light
        if ($mmPerHour < 10) return 2;  // Moderate
        if ($mmPerHour < 50) return 3;  // Heavy
        return 4;                        // Very heavy
    }

    /**
     * Get weather description and icon from WMO weather code
     * WMO codes: https://open-meteo.com/en/docs
     */
    private function getWeatherInfoFromCode(int $code): array
    {
        $descriptions = [
            0 => ['description' => 'Clear sky', 'icon' => 'day-sunny'],
            1 => ['description' => 'Mainly clear', 'icon' => 'day-sunny'],
            2 => ['description' => 'Partly cloudy', 'icon' => 'day-cloudy'],
            3 => ['description' => 'Overcast', 'icon' => 'cloudy'],
            45 => ['description' => 'Fog', 'icon' => 'fog'],
            48 => ['description' => 'Depositing rime fog', 'icon' => 'fog'],
            51 => ['description' => 'Light drizzle', 'icon' => 'sprinkle'],
            53 => ['description' => 'Moderate drizzle', 'icon' => 'sprinkle'],
            55 => ['description' => 'Dense drizzle', 'icon' => 'showers'],
            56 => ['description' => 'Freezing drizzle', 'icon' => 'sleet'],
            57 => ['description' => 'Freezing drizzle', 'icon' => 'sleet'],
            61 => ['description' => 'Light rain', 'icon' => 'rain'],
            63 => ['description' => 'Moderate rain', 'icon' => 'rain'],
            65 => ['description' => 'Heavy rain', 'icon' => 'rain'],
            66 => ['description' => 'Freezing rain', 'icon' => 'sleet'],
            67 => ['description' => 'Freezing rain', 'icon' => 'sleet'],
            71 => ['description' => 'Light snow', 'icon' => 'snow'],
            73 => ['description' => 'Moderate snow', 'icon' => 'snow'],
            75 => ['description' => 'Heavy snow', 'icon' => 'snow'],
            77 => ['description' => 'Snow grains', 'icon' => 'snow'],
            80 => ['description' => 'Light rain showers', 'icon' => 'showers'],
            81 => ['description' => 'Moderate rain showers', 'icon' => 'showers'],
            82 => ['description' => 'Violent rain showers', 'icon' => 'showers'],
            85 => ['description' => 'Light snow showers', 'icon' => 'snow'],
            86 => ['description' => 'Heavy snow showers', 'icon' => 'snow'],
            95 => ['description' => 'Thunderstorm', 'icon' => 'thunderstorm'],
            96 => ['description' => 'Thunderstorm with hail', 'icon' => 'thunderstorm'],
            99 => ['description' => 'Thunderstorm with hail', 'icon' => 'thunderstorm'],
        ];

        $info = $descriptions[$code] ?? ['description' => 'Unknown', 'icon' => 'na'];

        // Get the actual icon character from the mapper
        $iconChar = $this->iconMapper->getIconForDescription($info['icon']);

        return [
            'description' => $info['description'],
            'icon' => $iconChar,
        ];
    }

    /**
     * Get period label from hour of day
     */
    private function getPeriodLabel(int $hour): string
    {
        if ($hour >= 6 && $hour < 12) return 'Morning';
        if ($hour >= 12 && $hour < 18) return 'Afternoon';
        if ($hour >= 18 && $hour < 22) return 'Evening';
        return 'Night';
    }

    /**
     * Create WeatherData from array (for cache loading)
     */
    private function weatherDataFromArray(array $data): WeatherData
    {
        return new WeatherData(
            $data['lastUpdateDate'] ?? '',
            $data['lastUpdateTime'] ?? '',
            $data['lastUpdateTimestamp'] ?? time(),
            $data['precipitations'] ?? null,
            $data['forecasts'] ?? []
        );
    }
}
