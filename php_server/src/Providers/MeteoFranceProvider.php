<?php

namespace KindleWeather\Providers;

use KindleWeather\Models\WeatherData;
use KindleWeather\Config\Config;
use KindleWeather\Services\Logger;
use KindleWeather\Services\WeatherIconMapper;

/**
 * Météo France weather provider
 * Fetches data from Météo France API
 */
class MeteoFranceProvider implements WeatherProvider
{
    private const API_BASE_URL = 'https://rpcache-aa.meteofrance.com/internet2018client/2.0';
    private const FORECAST_ENDPOINT = '/forecast';
    private const RAINCAST_ENDPOINT = '/nowcast/rain';

    private string $token;
    private WeatherIconMapper $iconMapper;

    public function __construct(string $token)
    {
        $this->token = $token;
        $this->iconMapper = new WeatherIconMapper();
    }

    public function getName(): string
    {
        return 'meteofrance';
    }

    /**
     * Fetch weather data from Météo France API
     */
    public function fetchWeatherData(float $latitude, float $longitude): WeatherData
    {
        Logger::info("Fetching weather data from Météo France API");

        // Fetch forecast and raincast data
        $forecastData = $this->fetchForecast($latitude, $longitude);
        $raincastData = $this->fetchRaincast($latitude, $longitude);

        // Process and transform data
        return $this->transformData($forecastData, $raincastData);
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
            Logger::exception($e);

            // Try to use stale cache as fallback
            if (file_exists($cacheFile)) {
                Logger::warning("Using stale cached data as fallback");
                $cachedData = json_decode(file_get_contents($cacheFile), true);
                return $this->weatherDataFromArray($cachedData);
            }

            throw $e;
        }
    }

    /**
     * Fetch forecast data from API
     */
    private function fetchForecast(float $latitude, float $longitude): array
    {
        $url = self::API_BASE_URL . self::FORECAST_ENDPOINT
            . "?lat={$latitude}&lon={$longitude}"
            . "&instants=morning,afternoon,evening,night"
            . "&token={$this->token}";

        Logger::debug("Forecast URL: {$url}");

        $data = $this->makeApiRequest($url);

        if (!$data) {
            throw new \Exception("Failed to fetch forecast data from Météo France");
        }

        return $data;
    }

    /**
     * Fetch raincast (precipitation) data from API
     */
    private function fetchRaincast(float $latitude, float $longitude): array
    {
        $url = self::API_BASE_URL . self::RAINCAST_ENDPOINT
            . "?lat={$latitude}&lon={$longitude}"
            . "&token={$this->token}";

        Logger::debug("Raincast URL: {$url}");

        $data = $this->makeApiRequest($url);

        if (!$data) {
            throw new \Exception("Failed to fetch raincast data from Météo France");
        }

        return $data;
    }

    /**
     * Make HTTP request to API with timeout
     */
    private function makeApiRequest(string $url): ?array
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => Config::API_TIMEOUT_SECONDS,
            ],
            'socket' => [
                'connect_timeout' => Config::API_TIMEOUT_SECONDS,
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * Transform raw API data into WeatherData model
     */
    private function transformData(array $forecastData, array $raincastData): WeatherData
    {
        $now = time();
        $dateFormatter = new \IntlDateFormatter(
            Config::LOCALE,
            \IntlDateFormatter::SHORT,
            \IntlDateFormatter::SHORT
        );
        $dateFormatter->setPattern('EEEE d MMM');

        // Process precipitations
        $precipitations = $this->processPrecipitations($raincastData);

        // Process forecasts
        $forecasts = $this->processForecasts($forecastData);

        return new WeatherData(
            $dateFormatter->format(new \DateTime()),
            date('H\hi', $now),
            $now,
            $precipitations,
            $forecasts
        );
    }

    /**
     * Process precipitation data
     */
    private function processPrecipitations(array $raincastData): ?array
    {
        if (!isset($raincastData['properties']['forecast'])) {
            return null;
        }

        $precipitations = [];
        $totalIntensity = 0;
        $count = min(Config::PRECIPITATION_FORECAST_COUNT, count($raincastData['properties']['forecast']));

        for ($i = 0; $i < $count; $i++) {
            $forecast = $raincastData['properties']['forecast'][$i];
            $timestamp = strtotime($forecast['time']);
            $intensity = (int) $forecast['rain_intensity'];

            $precipitations[] = [
                'time' => date('H\hi', $timestamp),
                'value' => $intensity,
            ];

            $totalIntensity += $intensity;
        }

        // Only return precipitations if there's actual rain
        return ($totalIntensity > 0) ? $precipitations : null;
    }

    /**
     * Process forecast data
     */
    private function processForecasts(array $forecastData): array
    {
        if (!isset($forecastData['properties']['forecast'])) {
            return [];
        }

        $forecasts = [];
        $count = min(Config::WEATHER_FORECAST_PERIODS, count($forecastData['properties']['forecast']));

        // Get min/max temperatures from daily forecast
        $minTemp = isset($forecastData['properties']['daily_forecast'][0])
            ? round($forecastData['properties']['daily_forecast'][0]['T_min'])
            : 0;
        $maxTemp = isset($forecastData['properties']['daily_forecast'][0])
            ? round($forecastData['properties']['daily_forecast'][0]['T_max'])
            : 0;

        for ($i = 0; $i < $count; $i++) {
            $forecast = $forecastData['properties']['forecast'][$i];

            $momentText = $this->formatMomentText($forecast['moment_day']);
            $weatherDescription = $forecast['weather_description'];
            $iconText = $this->iconMapper->getIconName($weatherDescription);
            $isNight = $forecast['moment_day'] === 'Nuit';

            $forecasts[] = [
                'temperature' => round($forecast['T']),
                'minTemperature' => $minTemp,
                'maxTemperature' => $maxTemp,
                'iconText' => $iconText,
                'iconChar' => $this->iconMapper->getIconCharacter($iconText, $isNight),
                'weatherText' => $weatherDescription,
                'moment' => $momentText,
            ];
        }

        return $forecasts;
    }

    /**
     * Format moment text (Matin, Après-midi, etc.)
     */
    private function formatMomentText(string $moment): string
    {
        $formatted = ucwords($moment, '-');

        // Shorten "Après-Midi" for display
        if ($formatted === 'Après-Midi') {
            return "Aprèm'";
        }

        return $formatted;
    }

    /**
     * Reconstruct WeatherData from cached array
     */
    private function weatherDataFromArray(array $data): WeatherData
    {
        return new WeatherData(
            $data['lastUpdateDate'],
            $data['lastUpdateTime'],
            $data['lastUpdateTimestamp'],
            $data['precipitations'],
            $data['forecasts']
        );
    }
}
